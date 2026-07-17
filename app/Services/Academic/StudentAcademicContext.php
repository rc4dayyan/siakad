<?php

namespace App\Services\Academic;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;

class StudentAcademicContext
{
    public function registrationFor(Mahasiswa $student, ?TahunAkademik $period): ?RegistrasiMahasiswa
    {
        if (! $period) {
            return null;
        }

        return $student->registrasiAkademik()
            ->where('taka_id', $period->id)
            ->with(['taka', 'kelas', 'dosenWali'])
            ->first();
    }

    public function classFor(Mahasiswa $student, ?TahunAkademik $period): ?Kelas
    {
        $registration = $this->registrationFor($student, $period);

        if ($registration) {
            return $registration->kelas;
        }

        return $this->legacyClassFor($student, $period);
    }

    public function classIdFor(Mahasiswa $student, ?TahunAkademik $period): ?int
    {
        return $this->classFor($student, $period)?->id;
    }

    public function profileRegistration(Mahasiswa $student, ?TahunAkademik $activePeriod): ?RegistrasiMahasiswa
    {
        return $this->registrationFor($student, $activePeriod)
            ?? $student->registrasiAkademik()
                ->with(['taka', 'kelas', 'dosenWali'])
                ->latest('id')
                ->first();
    }

    private function legacyClassFor(Mahasiswa $student, ?TahunAkademik $period): ?Kelas
    {
        if (! $period || (int) $student->getRawOriginal('class_id') < 1) {
            return null;
        }

        $legacyClass = Kelas::with('taka')
            ->find((int) $student->getRawOriginal('class_id'));

        if (! $legacyClass || (int) $legacyClass->taka_id !== (int) $period->id) {
            return null;
        }

        return $legacyClass;
    }
}
