<?php

namespace App\Services\Academic;

use App\Models\RegistrasiMahasiswa;
use App\Services\Finance\FinancialEligibilityService;
use Illuminate\Validation\ValidationException;

class KrsEligibilityService
{
    public function __construct(private readonly FinancialEligibilityService $financialEligibility) {}

    public function isEligible(RegistrasiMahasiswa $registration): bool
    {
        return $registration->status_akademik === RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF
            && $registration->status_registrasi === RegistrasiMahasiswa::STATUS_REGISTRASI_TERDAFTAR
            && $this->financialEligibility->isEligible($registration);
    }

    public function ensureEligible(RegistrasiMahasiswa $registration): void
    {
        if ($this->isEligible($registration)) {
            return;
        }

        throw ValidationException::withMessages([
            'krs' => $this->ineligibilityReason($registration),
        ]);
    }

    public function ineligibilityReason(RegistrasiMahasiswa $registration): string
    {
        if ($registration->status_akademik !== RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF) {
            return 'KRS hanya dapat diisi oleh mahasiswa berstatus akademik aktif. Status saat ini: '
                .$registration->academic_status_label.'.';
        }

        if ($registration->status_registrasi !== RegistrasiMahasiswa::STATUS_REGISTRASI_TERDAFTAR) {
            return 'KRS hanya dapat diisi setelah registrasi mahasiswa berstatus terdaftar.';
        }

        return FinancialEligibilityService::BLOCK_REASON;
    }
}
