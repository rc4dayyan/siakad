<?php

namespace App\Services\Academic;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentRegistrationService
{
    public function register(
        Mahasiswa $student,
        TahunAkademik $period,
        int $semester,
        string $academicStatus,
        Kelas $class,
        Dosen $academicAdvisor,
        int $creditLimit
    ): RegistrasiMahasiswa {
        return DB::transaction(function () use (
            $student,
            $period,
            $semester,
            $academicStatus,
            $class,
            $academicAdvisor,
            $creditLimit
        ): RegistrasiMahasiswa {
            $lockedStudent = Mahasiswa::query()->lockForUpdate()->findOrFail($student->getKey());
            $this->validateRegistration(
                $lockedStudent,
                $period,
                $semester,
                $academicStatus,
                $class,
                $creditLimit
            );

            $registration = RegistrasiMahasiswa::create([
                'mahasiswa_id' => $lockedStudent->id,
                'taka_id' => $period->id,
                'semester_mahasiswa' => $semester,
                'status_akademik' => $academicStatus,
                'status_registrasi' => RegistrasiMahasiswa::STATUS_REGISTRASI_TERDAFTAR,
                'kelas_id' => $class->id,
                'dosen_wali_id' => $academicAdvisor->id,
                'batas_sks' => $creditLimit,
            ]);

            // Dipertahankan sampai TA-306 selesai memigrasikan seluruh pembacaan ke registrasi.
            $lockedStudent->update([
                'taka_id' => $period->id,
                'class_id' => $class->id,
            ]);

            return $registration;
        });
    }

    private function validateRegistration(
        Mahasiswa $student,
        TahunAkademik $period,
        int $semester,
        string $academicStatus,
        Kelas $class,
        int $creditLimit
    ): void {
        if (! $period->isWritable()) {
            throw ValidationException::withMessages([
                'academic_period' => 'Registrasi hanya dapat dibuat pada periode draft atau aktif.',
            ]);
        }

        if (RegistrasiMahasiswa::query()
            ->where('mahasiswa_id', $student->id)
            ->where('taka_id', $period->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'registration' => 'Mahasiswa sudah terdaftar pada periode akademik ini.',
            ]);
        }

        $latestRegistration = RegistrasiMahasiswa::query()
            ->where('mahasiswa_id', $student->id)
            ->with('taka')
            ->get()
            ->sortByDesc(fn (RegistrasiMahasiswa $registration) => [
                $registration->taka?->starts_at?->timestamp ?? 0,
                $registration->id,
            ])
            ->first();

        if ($latestRegistration?->hasTerminalAcademicStatus()) {
            throw ValidationException::withMessages([
                'status_akademik' => 'Mahasiswa dengan status '
                    .$latestRegistration->academic_status_label
                    .' tidak dapat diregistrasikan kembali.',
            ]);
        }

        if (! in_array($academicStatus, RegistrasiMahasiswa::academicStatuses(), true)) {
            throw ValidationException::withMessages([
                'status_akademik' => 'Status akademik yang dipilih tidak valid.',
            ]);
        }

        if ($semester < 1 || $semester > 14) {
            throw ValidationException::withMessages([
                'semester_mahasiswa' => 'Semester mahasiswa harus antara 1 sampai 14.',
            ]);
        }

        if ((int) $class->taka_id !== (int) $period->id) {
            throw ValidationException::withMessages([
                'kelas_id' => 'Kelas harus berasal dari periode akademik yang dipilih.',
            ]);
        }

        if ($creditLimit < 1 || $creditLimit > 24) {
            throw ValidationException::withMessages([
                'batas_sks' => 'Batas SKS harus antara 1 sampai 24.',
            ]);
        }
    }
}
