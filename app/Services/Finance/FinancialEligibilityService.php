<?php

namespace App\Services\Finance;

use App\Models\OverrideKeuanganKrs;
use App\Models\RegistrasiMahasiswa;
use App\Models\TagihanKuliah;
use App\Models\User;
use App\Services\Academic\AcademicAuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FinancialEligibilityService
{
    public const BLOCK_REASON = 'KRS belum dapat diajukan karena masih ada kewajiban administrasi yang belum diselesaikan.';

    public function __construct(private readonly AcademicAuditService $audit) {}

    public function isEligible(RegistrasiMahasiswa $registration): bool
    {
        if (! Schema::hasTable('tagihan_kuliahs') || ! Schema::hasColumn('tagihan_kuliahs', 'taka_id')) {
            return true;
        }

        return $this->hasActiveOverride($registration) || ! $this->unpaidRequiredBills($registration)->exists();
    }

    public function ensureEligible(RegistrasiMahasiswa $registration): void
    {
        if (! $this->isEligible($registration)) {
            throw ValidationException::withMessages(['krs' => self::BLOCK_REASON]);
        }
    }

    public function unpaidRequiredBills(RegistrasiMahasiswa $registration): Builder
    {
        return TagihanKuliah::query()
            ->forAcademicPeriod($registration->taka_id)
            ->forStudent($registration->mahasiswa_id)
            ->where('status', TagihanKuliah::STATUS_TERBIT)
            ->where('wajib_lunas_krs', true)
            ->whereDoesntHave('pembayarans', fn (Builder $query) => $query
                ->where(fn (Builder $status) => $status->where('status', 'lunas')->orWhere('stat', 1)));
    }

    public function createOverride(
        RegistrasiMahasiswa $registration,
        User $actor,
        string $reason,
        ?string $validUntil = null
    ): OverrideKeuanganKrs {
        abort_unless(in_array((int) $actor->raw_type, [0, 1], true), 403);

        validator(['alasan' => $reason], ['alasan' => ['required', 'string', 'min:10', 'max:1000']])->validate();

        $override = OverrideKeuanganKrs::create([
            'registrasi_mahasiswa_id' => $registration->id,
            'actor_id' => $actor->id,
            'alasan' => $reason,
            'berlaku_sampai' => $validUntil,
        ]);
        $this->audit->record('krs.financial_override_created', $override, $registration->taka_id, $actor, null,
            ['registration_id' => $registration->id, 'valid_until' => $validUntil], ['reason' => $reason]);

        return $override;
    }

    private function hasActiveOverride(RegistrasiMahasiswa $registration): bool
    {
        if (! Schema::hasTable('override_keuangan_krs')) {
            return false;
        }

        return OverrideKeuanganKrs::query()
            ->where('registrasi_mahasiswa_id', $registration->id)
            ->where(fn (Builder $query) => $query->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', now()))
            ->exists();
    }
}
