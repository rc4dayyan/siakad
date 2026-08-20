<?php

namespace App\Services\Academic;

use App\Models\JadwalMingguan;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\PenawaranMataKuliah;
use App\Models\PertemuanKuliah;
use App\Models\RegistrasiMahasiswa;
use App\Models\User;

class AcademicDashboardService
{
    public function __construct(private readonly AcademicPeriodContext $periods) {}

    /** @return array<string, mixed> */
    public function forUser(User $user): array
    {
        $period = $this->periods->current($user);
        $emptyStatuses = collect([
            Krs::STATUS_DRAFT,
            Krs::STATUS_SUBMITTED,
            Krs::STATUS_APPROVED,
            Krs::STATUS_REJECTED,
            Krs::STATUS_LOCKED,
        ])->mapWithKeys(fn (string $status) => [$status => 0])->all();

        if (! $period) {
            return [
                'period' => null,
                'registrations' => 0,
                'activeStudents' => 0,
                'classes' => 0,
                'offerings' => 0,
                'weeklySchedules' => 0,
                'meetings' => 0,
                'krsStatuses' => $emptyStatuses,
                'krsCompletion' => 0,
                'withoutKrs' => 0,
                'offeringsWithoutSchedule' => 0,
            ];
        }

        $registrations = RegistrasiMahasiswa::query()->forAcademicPeriod($period);
        $krs = Krs::query()->whereHas(
            'registrasiMahasiswa',
            fn ($query) => $query->where('taka_id', $period->id)
        );
        $krsStatuses = (clone $krs)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();
        $krsStatuses = array_replace($emptyStatuses, $krsStatuses);
        $approvedKrs = $krsStatuses[Krs::STATUS_APPROVED] + $krsStatuses[Krs::STATUS_LOCKED];
        $registrationCount = (clone $registrations)->count();

        return [
            'period' => $period,
            'registrations' => $registrationCount,
            'activeStudents' => (clone $registrations)
                ->where('status_akademik', RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF)
                ->count(),
            'classes' => Kelas::query()->forAcademicPeriod($period)->count(),
            'offerings' => PenawaranMataKuliah::query()->forAcademicPeriod($period)->count(),
            'weeklySchedules' => JadwalMingguan::query()->forAcademicPeriod($period)->count(),
            'meetings' => PertemuanKuliah::query()
                ->whereHas('jadwalMingguan', fn ($query) => $query->forAcademicPeriod($period))
                ->count(),
            'krsStatuses' => $krsStatuses,
            'krsCompletion' => $registrationCount > 0
                ? (int) round(($approvedKrs / $registrationCount) * 100)
                : 0,
            'withoutKrs' => (clone $registrations)->whereDoesntHave('krs')->count(),
            'offeringsWithoutSchedule' => PenawaranMataKuliah::query()
                ->forAcademicPeriod($period)
                ->whereDoesntHave('jadwalMingguans')
                ->count(),
        ];
    }
}
