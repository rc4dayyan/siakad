<?php

namespace App\Services\Academic;

use App\Models\RegistrasiMahasiswa;
use App\Models\RiwayatStatusAkademikMahasiswa;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicStatusService
{
    public function __construct(private readonly AcademicAuditService $audit) {}

    public function change(
        RegistrasiMahasiswa $registration,
        string $newStatus,
        string $reason,
        CarbonInterface $effectiveDate,
        User $actor
    ): RiwayatStatusAkademikMahasiswa {
        return DB::transaction(function () use ($registration, $newStatus, $reason, $effectiveDate, $actor) {
            $lockedRegistration = RegistrasiMahasiswa::query()
                ->with('taka')
                ->lockForUpdate()
                ->findOrFail($registration->getKey());

            if (! $lockedRegistration->taka?->isWritable()) {
                throw ValidationException::withMessages([
                    'academic_period' => 'Status akademik pada periode yang ditutup atau diarsipkan tidak dapat diubah.',
                ]);
            }

            if (mb_strlen(trim($reason)) < 5) {
                throw ValidationException::withMessages([
                    'alasan' => 'Alasan perubahan status minimal 5 karakter.',
                ]);
            }

            if ($effectiveDate->startOfDay()->greaterThan(Carbon::today())) {
                throw ValidationException::withMessages([
                    'berlaku_mulai' => 'Tanggal berlaku tidak boleh melewati hari ini.',
                ]);
            }

            if (! $lockedRegistration->canTransitionTo($newStatus)) {
                throw ValidationException::withMessages([
                    'status_akademik' => $this->transitionError($lockedRegistration->status_akademik, $newStatus),
                ]);
            }

            $history = $lockedRegistration->riwayatStatus()->create([
                'status_sebelumnya' => $lockedRegistration->status_akademik,
                'status_baru' => $newStatus,
                'alasan' => trim($reason),
                'berlaku_mulai' => $effectiveDate->toDateString(),
                'changed_by' => $actor->getKey(),
            ]);

            $lockedRegistration->update(['status_akademik' => $newStatus]);
            $this->audit->record('student.academic_status_changed', $lockedRegistration, $lockedRegistration->taka_id, $actor,
                ['status_akademik' => $history->status_sebelumnya],
                ['status_akademik' => $newStatus], ['reason' => trim($reason), 'effective_date' => $effectiveDate->toDateString()]);

            return $history;
        });
    }

    private function transitionError(string $currentStatus, string $newStatus): string
    {
        if (! in_array($newStatus, RegistrasiMahasiswa::academicStatuses(), true)) {
            return 'Status akademik tujuan tidak valid.';
        }

        if ($currentStatus === $newStatus) {
            return 'Status akademik baru harus berbeda dari status saat ini.';
        }

        return sprintf(
            'Perubahan status dari %s menjadi %s tidak diizinkan.',
            RegistrasiMahasiswa::academicStatusLabel($currentStatus),
            RegistrasiMahasiswa::academicStatusLabel($newStatus)
        );
    }
}
