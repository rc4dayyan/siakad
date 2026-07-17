<?php

namespace App\Services\Academic;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\ProsesKenaikanSemester;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

class SemesterPromotionService
{
    public const ACTION_ACTIVATE = 'aktifkan';

    public const ACTION_PRESERVE = 'pertahankan';

    public const ACTION_SKIP = 'lewati';

    public function __construct(
        private readonly StudentRegistrationService $registrationService,
        private readonly AcademicAuditService $audit
    ) {}

    public static function statusActions(): array
    {
        return [self::ACTION_ACTIVATE, self::ACTION_PRESERVE, self::ACTION_SKIP];
    }

    public function preview(
        TahunAkademik $sourcePeriod,
        TahunAkademik $targetPeriod,
        Kelas $sourceClass,
        Kelas $targetClass,
        string $leaveAction,
        string $inactiveAction
    ): Collection {
        $this->validateConfiguration(
            $sourcePeriod,
            $targetPeriod,
            $sourceClass,
            $targetClass,
            $leaveAction,
            $inactiveAction
        );

        $existingTargetStudents = RegistrasiMahasiswa::query()
            ->where('taka_id', $targetPeriod->id)
            ->pluck('mahasiswa_id')
            ->flip();

        return $this->sourceQuery($sourcePeriod, $sourceClass)
            ->get()
            ->map(function (RegistrasiMahasiswa $registration) use (
                $leaveAction,
                $inactiveAction,
                $existingTargetStudents
            ): array {
                $decision = $this->decisionFor($registration, $leaveAction, $inactiveAction);

                if ($existingTargetStudents->has($registration->mahasiswa_id)) {
                    $decision = [
                        'action' => self::ACTION_SKIP,
                        'target_status' => null,
                        'reason' => 'Sudah terdaftar pada periode tujuan.',
                    ];
                }

                return [
                    'registration_id' => $registration->id,
                    'student_code' => $registration->mahasiswa->mhs_code,
                    'student_number' => $registration->mahasiswa->mhs_nim,
                    'student_name' => $registration->mahasiswa->mhs_name,
                    'source_semester' => $registration->semester_mahasiswa,
                    'target_semester' => min(14, $registration->semester_mahasiswa + 1),
                    'source_status' => $registration->status_akademik,
                    ...$decision,
                ];
            });
    }

    public function execute(
        TahunAkademik $sourcePeriod,
        TahunAkademik $targetPeriod,
        Kelas $sourceClass,
        Kelas $targetClass,
        Dosen $academicAdvisor,
        string $leaveAction,
        string $inactiveAction,
        User $actor,
        int $chunkSize = 200
    ): ProsesKenaikanSemester {
        $this->validateConfiguration(
            $sourcePeriod,
            $targetPeriod,
            $sourceClass,
            $targetClass,
            $leaveAction,
            $inactiveAction
        );

        if ((int) $academicAdvisor->getRawOriginal('dsn_stat') !== 1) {
            throw ValidationException::withMessages([
                'dosen_wali_id' => 'Dosen wali tujuan harus berstatus aktif.',
            ]);
        }

        $sourceQuery = $this->sourceQuery($sourcePeriod, $sourceClass);
        $run = ProsesKenaikanSemester::create([
            'periode_sumber_id' => $sourcePeriod->id,
            'periode_tujuan_id' => $targetPeriod->id,
            'kelas_sumber_id' => $sourceClass->id,
            'kelas_tujuan_id' => $targetClass->id,
            'dosen_wali_id' => $academicAdvisor->id,
            'diproses_oleh' => $actor->id,
            'keputusan_cuti' => $leaveAction,
            'keputusan_nonaktif' => $inactiveAction,
            'status_proses' => 'running',
            'jumlah_sumber' => (clone $sourceQuery)->count(),
        ]);
        $summary = ['berhasil' => [], 'dilewati' => [], 'gagal' => []];
        $counts = ['success' => 0, 'skipped' => 0, 'failed' => 0];

        $sourceQuery->chunkById($chunkSize, function ($registrations) use (
            $targetPeriod,
            $targetClass,
            $academicAdvisor,
            $leaveAction,
            $inactiveAction,
            &$summary,
            &$counts
        ): void {
            foreach ($registrations as $registration) {
                $decision = $this->decisionFor($registration, $leaveAction, $inactiveAction);

                if ($decision['action'] === self::ACTION_SKIP) {
                    $counts['skipped']++;
                    $summary['dilewati'][] = $this->summaryRow($registration, $decision['reason']);

                    continue;
                }

                try {
                    $created = $this->registrationService->register(
                        $registration->mahasiswa,
                        $targetPeriod,
                        min(14, $registration->semester_mahasiswa + 1),
                        $decision['target_status'],
                        $targetClass,
                        $academicAdvisor,
                        $registration->batas_sks
                    );
                    $counts['success']++;
                    $summary['berhasil'][] = $this->summaryRow(
                        $registration,
                        'Registrasi #'.$created->id.' berhasil dibuat.'
                    );
                } catch (ValidationException $exception) {
                    $errors = collect($exception->errors())->flatten()->implode(' ');

                    if (str_contains($errors, 'sudah terdaftar')) {
                        $counts['skipped']++;
                        $summary['dilewati'][] = $this->summaryRow($registration, $errors);
                    } else {
                        $counts['failed']++;
                        $summary['gagal'][] = $this->summaryRow($registration, $errors);
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $counts['failed']++;
                    $summary['gagal'][] = $this->summaryRow(
                        $registration,
                        'Terjadi kegagalan saat menyimpan registrasi.'
                    );
                }
            }
        }, 'registrasi_mahasiswas.id', 'id');

        $run->update([
            'status_proses' => $counts['failed'] > 0 ? 'selesai_dengan_kegagalan' : 'selesai',
            'jumlah_berhasil' => $counts['success'],
            'jumlah_dilewati' => $counts['skipped'],
            'jumlah_gagal' => $counts['failed'],
            'ringkasan' => $summary,
        ]);
        $this->audit->record('student.semester_promotion_bulk', $run, $targetPeriod->id, $actor, null,
            ['success' => $counts['success'], 'skipped' => $counts['skipped'], 'failed' => $counts['failed']],
            ['source_period_id' => $sourcePeriod->id]);

        return $run->fresh();
    }

    private function sourceQuery(TahunAkademik $period, Kelas $class)
    {
        return RegistrasiMahasiswa::query()
            ->where('taka_id', $period->id)
            ->where('kelas_id', $class->id)
            ->with(['mahasiswa', 'taka'])
            ->orderBy('id');
    }

    private function decisionFor(
        RegistrasiMahasiswa $registration,
        string $leaveAction,
        string $inactiveAction
    ): array {
        if ($registration->hasTerminalAcademicStatus()) {
            return [
                'action' => self::ACTION_SKIP,
                'target_status' => null,
                'reason' => 'Status terminal: '.$registration->academic_status_label.'.',
            ];
        }

        $selectedAction = match ($registration->status_akademik) {
            RegistrasiMahasiswa::STATUS_AKADEMIK_CUTI => $leaveAction,
            RegistrasiMahasiswa::STATUS_AKADEMIK_NONAKTIF => $inactiveAction,
            default => self::ACTION_ACTIVATE,
        };

        if ($selectedAction === self::ACTION_SKIP) {
            return [
                'action' => self::ACTION_SKIP,
                'target_status' => null,
                'reason' => 'Dilewati berdasarkan keputusan status '.$registration->academic_status_label.'.',
            ];
        }

        return [
            'action' => $selectedAction,
            'target_status' => $selectedAction === self::ACTION_PRESERVE
                ? $registration->status_akademik
                : RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF,
            'reason' => $selectedAction === self::ACTION_PRESERVE
                ? 'Status '.$registration->academic_status_label.' dipertahankan.'
                : 'Dilanjutkan sebagai mahasiswa aktif.',
        ];
    }

    private function validateConfiguration(
        TahunAkademik $sourcePeriod,
        TahunAkademik $targetPeriod,
        Kelas $sourceClass,
        Kelas $targetClass,
        string $leaveAction,
        string $inactiveAction
    ): void {
        if ($sourcePeriod->is($targetPeriod)) {
            throw ValidationException::withMessages([
                'periode_tujuan' => 'Periode tujuan harus berbeda dari periode sumber.',
            ]);
        }

        if (! $targetPeriod->isWritable()) {
            throw ValidationException::withMessages([
                'periode_tujuan' => 'Periode tujuan harus berstatus draft atau aktif.',
            ]);
        }

        if ((int) $sourceClass->taka_id !== (int) $sourcePeriod->id) {
            throw ValidationException::withMessages([
                'kelas_sumber' => 'Kelas sumber tidak berasal dari periode sumber.',
            ]);
        }

        if ((int) $targetClass->taka_id !== (int) $targetPeriod->id) {
            throw ValidationException::withMessages([
                'kelas_tujuan' => 'Kelas tujuan tidak berasal dari periode tujuan.',
            ]);
        }

        if (! in_array($leaveAction, self::statusActions(), true)) {
            throw ValidationException::withMessages(['keputusan_cuti' => 'Keputusan mahasiswa cuti tidak valid.']);
        }

        if (! in_array($inactiveAction, self::statusActions(), true)) {
            throw ValidationException::withMessages(['keputusan_nonaktif' => 'Keputusan mahasiswa nonaktif tidak valid.']);
        }
    }

    private function summaryRow(RegistrasiMahasiswa $registration, string $message): array
    {
        return [
            'mahasiswa_id' => $registration->mahasiswa_id,
            'kode' => $registration->mahasiswa->mhs_code,
            'nim' => $registration->mahasiswa->mhs_nim,
            'nama' => $registration->mahasiswa->mhs_name,
            'pesan' => $message,
        ];
    }
}
