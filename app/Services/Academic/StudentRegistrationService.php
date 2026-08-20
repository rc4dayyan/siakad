<?php

namespace App\Services\Academic;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StudentRegistrationService
{
    /**
     * @return array{registration: RegistrasiMahasiswa, moved: bool, previous_class_id: int|null}
     */
    public function assignToClass(
        Mahasiswa $student,
        TahunAkademik $period,
        Kelas $class,
        ?Dosen $academicAdvisor,
        int $semester,
        string $academicStatus,
        int $creditLimit
    ): array {
        return DB::transaction(function () use (
            $student,
            $period,
            $class,
            $academicAdvisor,
            $semester,
            $academicStatus,
            $creditLimit
        ): array {
            $lockedClass = Kelas::query()->lockForUpdate()->findOrFail($class->getKey());
            $lockedStudent = Mahasiswa::query()->lockForUpdate()->findOrFail($student->getKey());
            $registration = RegistrasiMahasiswa::query()
                ->where('mahasiswa_id', $lockedStudent->id)
                ->where('taka_id', $period->id)
                ->lockForUpdate()
                ->first();
            $previousClassId = $registration?->kelas_id;
            $sourceClass = $registration?->kelas_id
                ? Kelas::query()->find($registration->kelas_id)
                : $lockedStudent->kelas()->first();

            if ((int) $lockedClass->taka_id !== (int) $period->id) {
                throw ValidationException::withMessages([
                    'mahasiswa_id' => 'Kelas tujuan tidak berada pada periode akademik yang dipilih.',
                ]);
            }

            if ($sourceClass && (int) $sourceClass->pstudi_id !== (int) $lockedClass->pstudi_id) {
                throw ValidationException::withMessages([
                    'mahasiswa_id' => 'Mahasiswa hanya dapat ditempatkan pada kelas dari program studi yang sama.',
                ]);
            }

            if ($registration && (int) $registration->kelas_id === (int) $lockedClass->id) {
                throw ValidationException::withMessages([
                    'mahasiswa_id' => 'Mahasiswa sudah terdaftar pada kelas ini.',
                ]);
            }

            $occupancy = Mahasiswa::query()
                ->forAcademicClass($period, $lockedClass->id)
                ->where('id', '!=', $lockedStudent->id)
                ->count();
            if ($lockedClass->capacity && $occupancy >= (int) $lockedClass->capacity) {
                throw ValidationException::withMessages([
                    'mahasiswa_id' => 'Kelas tujuan sudah mencapai kapasitas maksimum.',
                ]);
            }

            if ($registration) {
                if ($registration->hasTerminalAcademicStatus()) {
                    throw ValidationException::withMessages([
                        'mahasiswa_id' => 'Mahasiswa dengan status akademik terminal tidak dapat dipindahkan kelas.',
                    ]);
                }

                if (Schema::hasTable('krs') && $registration->krs()->whereHas('items')->exists()) {
                    throw ValidationException::withMessages([
                        'mahasiswa_id' => 'Mahasiswa tidak dapat dipindahkan karena sudah memiliki mata kuliah pada KRS periode ini.',
                    ]);
                }

                $registration->update(['kelas_id' => $lockedClass->id]);
                $moved = true;
            } else {
                if (! $academicAdvisor) {
                    throw ValidationException::withMessages([
                        'dosen_wali_id' => 'Dosen wali aktif wajib dipilih untuk registrasi baru.',
                    ]);
                }

                $this->validateRegistration(
                    $lockedStudent,
                    $period,
                    $semester,
                    $academicStatus,
                    $lockedClass,
                    $creditLimit
                );

                $registration = RegistrasiMahasiswa::create([
                    'mahasiswa_id' => $lockedStudent->id,
                    'taka_id' => $period->id,
                    'semester_mahasiswa' => $semester,
                    'status_akademik' => $academicStatus,
                    'status_registrasi' => RegistrasiMahasiswa::STATUS_REGISTRASI_TERDAFTAR,
                    'kelas_id' => $lockedClass->id,
                    'dosen_wali_id' => $academicAdvisor->id,
                    'batas_sks' => $creditLimit,
                ]);
                $moved = false;
            }

            // Dipertahankan sampai TA-306 selesai memigrasikan seluruh pembacaan ke registrasi.
            $lockedStudent->update([
                'taka_id' => $period->id,
                'class_id' => $lockedClass->id,
            ]);

            return [
                'registration' => $registration->fresh(),
                'moved' => $moved,
                'previous_class_id' => $previousClassId,
            ];
        });
    }

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
