<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\AcademicStatusService;
use App\Services\Academic\KrsEligibilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcademicStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        (require database_path('migrations/2014_10_12_000000_create_users_table.php'))->up();
        (require database_path('migrations/2024_04_26_060533_create_tahun_akademiks_table.php'))->up();
        (require database_path('migrations/2026_07_17_000003_extend_tahun_akademiks_for_period_lifecycle.php'))->up();
        (require database_path('migrations/2024_03_09_024013_create_mahasiswas_table.php'))->up();
        (require database_path('migrations/2024_03_09_024021_create_dosens_table.php'))->up();
        (require database_path('migrations/2024_04_27_041303_create_kelas_table.php'))->up();
        (require database_path('migrations/2026_07_17_000005_create_registrasi_mahasiswas_table.php'))->up();
        (require database_path('migrations/2026_07_17_000006_create_riwayat_status_akademik_mahasiswas_table.php'))->up();
    }

    public function test_all_statuses_and_allowed_transitions_are_defined(): void
    {
        $registration = $this->registration();

        $this->assertSame([
            'aktif',
            'cuti',
            'nonaktif',
            'lulus',
            'drop_out',
            'mengundurkan_diri',
        ], RegistrasiMahasiswa::academicStatuses());
        $this->assertTrue($registration->canTransitionTo(RegistrasiMahasiswa::STATUS_AKADEMIK_CUTI));
        $this->assertTrue($registration->canTransitionTo(RegistrasiMahasiswa::STATUS_AKADEMIK_LULUS));

        $registration->status_akademik = RegistrasiMahasiswa::STATUS_AKADEMIK_LULUS;

        $this->assertSame([], $registration->allowedAcademicStatusTransitions());
        $this->assertFalse($registration->canTransitionTo(RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF));
    }

    public function test_status_change_updates_registration_and_records_audit_history(): void
    {
        $registration = $this->registration();
        $actor = $this->webAdmin();
        $effectiveDate = CarbonImmutable::today()->subDay();

        $history = app(AcademicStatusService::class)->change(
            $registration,
            RegistrasiMahasiswa::STATUS_AKADEMIK_CUTI,
            'Mahasiswa mengajukan cuti semester berjalan.',
            $effectiveDate,
            $actor
        );

        $this->assertSame(RegistrasiMahasiswa::STATUS_AKADEMIK_CUTI, $registration->fresh()->status_akademik);
        $this->assertSame(RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF, $history->status_sebelumnya);
        $this->assertSame(RegistrasiMahasiswa::STATUS_AKADEMIK_CUTI, $history->status_baru);
        $this->assertTrue($history->changedBy->is($actor));
        $this->assertSame($effectiveDate->toDateString(), $history->berlaku_mulai->toDateString());
    }

    public function test_terminal_status_cannot_be_reactivated(): void
    {
        $registration = $this->registration([
            'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_DROP_OUT,
        ]);

        try {
            app(AcademicStatusService::class)->change(
                $registration,
                RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF,
                'Permintaan aktivasi kembali.',
                CarbonImmutable::today(),
                $this->webAdmin()
            );
            $this->fail('Transisi dari status terminal seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status_akademik', $exception->errors());
        }

        $this->assertSame(0, $registration->riwayatStatus()->count());
        $this->assertSame(RegistrasiMahasiswa::STATUS_AKADEMIK_DROP_OUT, $registration->fresh()->status_akademik);
    }

    public function test_status_in_closed_period_cannot_be_changed(): void
    {
        $registration = $this->registration([], TahunAkademik::STATUS_CLOSED);

        $this->expectException(ValidationException::class);
        app(AcademicStatusService::class)->change(
            $registration,
            RegistrasiMahasiswa::STATUS_AKADEMIK_CUTI,
            'Cuti pada periode yang sudah ditutup.',
            CarbonImmutable::today(),
            $this->webAdmin()
        );
    }

    public function test_staff_endpoint_changes_status_only_in_selected_writable_period(): void
    {
        $period = $this->period();
        $student = Mahasiswa::factory()->create();
        $registration = $this->registration([
            'mahasiswa_id' => $student->id,
            'taka_id' => $period->id,
        ], period: $period);

        $response = $this
            ->actingAs($this->webAdmin())
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->from(route('web-admin.workers.student-edit', $student->mhs_code))
            ->patch(route('web-admin.workers.student-academic-status-update', $student->mhs_code), [
                'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_NONAKTIF,
                'alasan' => 'Tidak melakukan registrasi administrasi.',
                'berlaku_mulai' => now()->subDay()->toDateString(),
            ]);

        $response->assertRedirect(route('web-admin.workers.student-edit', $student->mhs_code));
        $response->assertSessionHasNoErrors();
        $this->assertSame(RegistrasiMahasiswa::STATUS_AKADEMIK_NONAKTIF, $registration->fresh()->status_akademik);
        $this->assertDatabaseHas('riwayat_status_akademik_mahasiswas', [
            'registrasi_mahasiswa_id' => $registration->id,
            'status_sebelumnya' => RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF,
            'status_baru' => RegistrasiMahasiswa::STATUS_AKADEMIK_NONAKTIF,
        ]);
    }

    public function test_staff_endpoint_validates_reason_and_effective_date(): void
    {
        $period = $this->period();
        $student = Mahasiswa::factory()->create();
        $registration = $this->registration([
            'mahasiswa_id' => $student->id,
            'taka_id' => $period->id,
        ], period: $period);

        $response = $this
            ->actingAs($this->webAdmin())
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->patch(route('web-admin.workers.student-academic-status-update', $student->mhs_code), [
                'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_CUTI,
                'alasan' => 'x',
                'berlaku_mulai' => now()->addDay()->toDateString(),
            ]);

        $response->assertSessionHasErrors(['alasan', 'berlaku_mulai']);
        $this->assertSame(RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF, $registration->fresh()->status_akademik);
        $this->assertDatabaseCount('riwayat_status_akademik_mahasiswas', 0);
    }

    public function test_staff_can_update_student_profile_without_changing_legacy_class_or_password(): void
    {
        $student = Mahasiswa::factory()->create([
            'class_id' => 77,
            'password' => Hash::make('password-lama'),
        ]);
        $originalPassword = $student->getRawOriginal('password');

        $response = $this
            ->actingAs($this->webAdmin())
            ->patch(route('web-admin.workers.student-update', $student->mhs_code), [
                'mhs_name' => $student->mhs_name,
                'mhs_nim' => $student->mhs_nim,
                'mhs_phone' => $student->getRawOriginal('mhs_phone'),
                'mhs_mail' => $student->mhs_mail,
                'mhs_birthplace' => $student->mhs_birthplace,
                'mhs_birthdate' => $student->mhs_birthdate,
            ]);

        $response->assertSessionHasNoErrors();
        $student->refresh();
        $this->assertSame(77, (int) $student->getRawOriginal('class_id'));
        $this->assertSame($originalPassword, $student->getRawOriginal('password'));
    }

    public function test_staff_without_academic_authority_cannot_change_status(): void
    {
        $period = $this->period();
        $student = Mahasiswa::factory()->create();
        $registration = $this->registration([
            'mahasiswa_id' => $student->id,
            'taka_id' => $period->id,
        ], period: $period);
        $financeStaff = $this->webAdmin();
        $financeStaff->update(['type' => 1]);

        $response = $this
            ->actingAs($financeStaff)
            ->withSession([AcademicPeriodContext::SESSION_KEY => $period->id])
            ->patch(route('web-admin.workers.student-academic-status-update', $student->mhs_code), [
                'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_CUTI,
                'alasan' => 'Pengajuan oleh staf tanpa kewenangan.',
                'berlaku_mulai' => now()->toDateString(),
            ]);

        $response->assertRedirect(route('error.access'));
        $this->assertSame(RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF, $registration->fresh()->status_akademik);
        $this->assertDatabaseCount('riwayat_status_akademik_mahasiswas', 0);
    }

    public function test_krs_eligibility_rejects_cuti_nonaktif_and_terminal_statuses(): void
    {
        $service = app(KrsEligibilityService::class);
        $registration = $this->registration();

        $this->assertTrue($service->isEligible($registration));
        $service->ensureEligible($registration);

        foreach ([
            RegistrasiMahasiswa::STATUS_AKADEMIK_CUTI,
            RegistrasiMahasiswa::STATUS_AKADEMIK_NONAKTIF,
            RegistrasiMahasiswa::STATUS_AKADEMIK_LULUS,
            RegistrasiMahasiswa::STATUS_AKADEMIK_DROP_OUT,
            RegistrasiMahasiswa::STATUS_AKADEMIK_MENGUNDURKAN_DIRI,
        ] as $status) {
            $registration->status_akademik = $status;
            $this->assertFalse($service->isEligible($registration));
        }

        $registration->status_akademik = RegistrasiMahasiswa::STATUS_AKADEMIK_CUTI;

        try {
            $service->ensureEligible($registration);
            $this->fail('Mahasiswa cuti seharusnya tidak dapat mengisi KRS.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('krs', $exception->errors());
        }
    }

    private function registration(
        array $attributes = [],
        string $periodStatus = TahunAkademik::STATUS_ACTIVE,
        ?TahunAkademik $period = null
    ): RegistrasiMahasiswa {
        $period ??= $this->period($periodStatus);

        return RegistrasiMahasiswa::factory()->create(array_merge([
            'taka_id' => $period->id,
        ], $attributes));
    }

    private function period(string $status = TahunAkademik::STATUS_ACTIVE): TahunAkademik
    {
        return TahunAkademik::factory()->create([
            'status' => $status,
            'is_active' => $status === TahunAkademik::STATUS_ACTIVE,
            'starts_at' => '2026-02-01',
            'ends_at' => '2026-07-31',
        ]);
    }

    private function webAdmin(): User
    {
        return User::create([
            'type' => 0,
            'code' => fake()->unique()->bothify('ADM-####'),
            'name' => fake()->name(),
            'user' => fake()->unique()->userName(),
            'phone' => fake()->unique()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'status' => 1,
        ]);
    }
}
