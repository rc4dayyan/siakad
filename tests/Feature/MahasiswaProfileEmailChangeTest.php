<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MahasiswaProfileEmailChangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('mahasiswas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('mhs_stat')->default(1);
            $table->string('mhs_nim')->unique();
            $table->string('mhs_name');
            $table->string('mhs_code')->unique();
            $table->string('mhs_user')->unique();
            $table->string('password');
            $table->string('mhs_mail')->unique();
            $table->string('mhs_phone');
            $table->string('mhs_parent_father')->nullable();
            $table->string('mhs_parent_father_phone')->nullable();
            $table->string('mhs_parent_mother')->nullable();
            $table->string('mhs_parent_mother_phone')->nullable();
            $table->string('mhs_wali_name')->nullable();
            $table->string('mhs_wali_phone')->nullable();
            $table->text('mhs_addr_domisili')->nullable();
            $table->string('mhs_addr_kelurahan')->nullable();
            $table->string('mhs_addr_kecamatan')->nullable();
            $table->string('mhs_addr_kota')->nullable();
            $table->string('mhs_addr_provinsi')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        (require database_path('migrations/2026_09_17_000001_add_email_change_tracking_to_mahasiswas_table.php'))->up();
    }

    public function test_student_can_change_academic_email_once(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student, 'mahasiswa')
            ->from(route('mahasiswa.home-profile'))
            ->patch(route('mahasiswa.home-profile-save-kontak'), $this->contactPayload('new-email@example.test'));

        $response->assertRedirect(route('mahasiswa.home-profile'));
        $response->assertSessionHasNoErrors();
        $student->refresh();

        $this->assertSame('new-email@example.test', $student->mhs_mail);
        $this->assertNotNull($student->mhs_email_changed_at);
    }

    public function test_student_cannot_change_academic_email_a_second_time(): void
    {
        $student = $this->student([
            'mhs_mail' => 'first-change@example.test',
            'mhs_email_changed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($student, 'mahasiswa')
            ->from(route('mahasiswa.home-profile'))
            ->patch(route('mahasiswa.home-profile-save-kontak'), $this->contactPayload('second-change@example.test'));

        $response->assertRedirect(route('mahasiswa.home-profile'));
        $response->assertSessionHasErrors('mhs_mail');
        $this->assertSame('first-change@example.test', $student->fresh()->mhs_mail);
    }

    public function test_locked_email_does_not_prevent_other_contact_updates(): void
    {
        $student = $this->student([
            'mhs_email_changed_at' => now()->subDay(),
        ]);
        $payload = $this->contactPayload($student->mhs_mail);
        $payload['mhs_phone'] = '081299988877';

        $response = $this->actingAs($student, 'mahasiswa')
            ->from(route('mahasiswa.home-profile'))
            ->patch(route('mahasiswa.home-profile-save-kontak'), $payload);

        $response->assertRedirect(route('mahasiswa.home-profile'));
        $response->assertSessionHasNoErrors();
        $this->assertSame('6281299988877', $student->fresh()->mhs_phone);
    }

    private function student(array $overrides = []): Mahasiswa
    {
        return Mahasiswa::query()->create(array_merge([
            'mhs_stat' => 1,
            'mhs_nim' => '2026000001',
            'mhs_name' => 'Mahasiswa Uji',
            'mhs_code' => 'MHS-EMAIL-001',
            'mhs_user' => 'mhs-email-001',
            'password' => Hash::make('password'),
            'mhs_mail' => 'student@example.test',
            'mhs_phone' => '081234567890',
        ], $overrides));
    }

    private function contactPayload(string $email): array
    {
        return [
            'profile_section' => 'contact',
            'mhs_mail' => $email,
            'mhs_phone' => '081234567890',
        ];
    }
}
