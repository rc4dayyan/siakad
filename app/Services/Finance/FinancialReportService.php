<?php

namespace App\Services\Finance;

use App\Models\HistoryTagihan;
use App\Models\RegistrasiMahasiswa;
use App\Models\TagihanKuliah;
use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FinancialReportService
{
    public function forPeriod(TahunAkademik|int $period): array
    {
        $periodId = $period instanceof TahunAkademik ? $period->getKey() : $period;
        $bills = TagihanKuliah::query()
            ->forAcademicPeriod($periodId)
            ->where('status', TagihanKuliah::STATUS_TERBIT)
            ->with('targetMahasiswa')
            ->get();
        $paidBillIds = HistoryTagihan::query()
            ->where('taka_id', $periodId)
            ->where(fn (Builder $query) => $query->where('status', 'lunas')->orWhere('stat', 1))
            ->whereNotNull('tagihan_kuliah_id')
            ->pluck('tagihan_kuliah_id')
            ->unique();
        $payments = HistoryTagihan::query()
            ->where('taka_id', $periodId)
            ->where(fn (Builder $query) => $query->where('status', 'lunas')->orWhere('stat', 1))
            ->get();
        $registrations = RegistrasiMahasiswa::query()
            ->forAcademicPeriod($periodId)
            ->with(['kelas.pstudi', 'kelas.proku'])
            ->get()
            ->keyBy('mahasiswa_id');

        return [
            'ringkasan' => [
                'jumlah_tagihan' => $bills->count(),
                'total_tagihan' => (int) $bills->sum('nominal'),
                'jumlah_pembayaran' => $payments->count(),
                'total_pembayaran' => (int) $payments->sum('nominal'),
                'total_tunggakan' => (int) $bills->whereNotIn('id', $paidBillIds)->sum('nominal'),
            ],
            'per_prodi' => $this->breakdown($bills, $paidBillIds, $registrations, 'prodi'),
            'per_proku' => $this->breakdown($bills, $paidBillIds, $registrations, 'proku'),
            'per_status_mahasiswa' => $this->breakdown($bills, $paidBillIds, $registrations, 'status'),
        ];
    }

    private function breakdown(Collection $bills, Collection $paidBillIds, Collection $registrations, string $dimension): Collection
    {
        return $bills->groupBy(function (TagihanKuliah $bill) use ($registrations, $dimension): string {
            $registration = $registrations->get($bill->target_mahasiswa_id);

            return match ($dimension) {
                'prodi' => $registration?->kelas?->pstudi?->name ?? 'Tanpa Program Studi',
                'proku' => $registration?->kelas?->proku?->name ?? 'Tanpa Program Kuliah',
                default => $registration?->academic_status_label ?? 'Tanpa Status',
            };
        })->map(fn (Collection $items, string $label) => [
            'label' => $label,
            'jumlah' => $items->count(),
            'tagihan' => (int) $items->sum('nominal'),
            'tunggakan' => (int) $items->whereNotIn('id', $paidBillIds)->sum('nominal'),
        ])->values();
    }
}
