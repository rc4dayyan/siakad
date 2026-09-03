<?php

namespace App\Services\Academic;

use App\Models\JadwalMingguan;
use App\Models\PenawaranMataKuliah;
use App\Models\Ruang;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicScheduleGeneratorService
{
    public function __construct(private readonly ScheduleConflictService $conflicts) {}

    /** @return array{created: int, skipped: int} */
    public function generate(
        TahunAkademik $period,
        array $days,
        string $dayStartsAt,
        string $dayEndsAt,
        int $minutesPerCredit,
        int $gapMinutes,
        bool $dryRun = false
    ): array {
        $rooms = Ruang::query()
            ->whereIn('type', [0, 1])
            ->orderBy('kapasitas')
            ->orderBy('name')
            ->get();

        if ($rooms->isEmpty()) {
            $this->reject('Belum ada ruang kelas atau laboratorium untuk membuat jadwal.');
        }

        $offerings = PenawaranMataKuliah::query()
            ->forAcademicPeriod($period)
            ->with(['masterMataKuliah', 'kelas', 'dosenUtama'])
            ->withCount('jadwalMingguans')
            ->orderBy('id')
            ->get();
        $requiredOfferings = $offerings->where('wajib_dijadwalkan', true);
        $skipped = $requiredOfferings->where('jadwal_mingguans_count', '>', 0)->count();
        $unscheduled = $requiredOfferings
            ->where('jadwal_mingguans_count', 0)
            ->sortByDesc(fn (PenawaranMataKuliah $offering): string => str_pad((string) $offering->sks, 2, '0', STR_PAD_LEFT)
                .'|'.str_pad((string) $offering->kapasitas, 5, '0', STR_PAD_LEFT))
            ->values();

        if ($offerings->isEmpty()) {
            $this->reject('Belum ada penawaran mata kuliah pada periode yang dipilih.');
        }

        $dailyMinutes = $this->minutes($dayEndsAt) - $this->minutes($dayStartsAt);
        $maximumCreditsPerSession = intdiv($dailyMinutes, $minutesPerCredit);
        if ($maximumCreditsPerSession < 1) {
            $this->reject("Rentang jam harian minimal harus memuat 1 SKS ({$minutesPerCredit} menit).");
        }

        $occupancy = $this->occupancy($period);

        DB::beginTransaction();

        try {
            $created = 0;

            foreach ($unscheduled as $offering) {
                if (! $offering->kelas || ! $offering->dosenUtama) {
                    $this->reject("Penawaran {$offering->code} belum memiliki kelas atau dosen utama.");
                }

                $requiredCapacity = max(
                    (int) $offering->kapasitas,
                    $offering->pesertaDisetujui()->count()
                );
                $eligibleRooms = $rooms->where('kapasitas', '>=', $requiredCapacity);

                if ($eligibleRooms->isEmpty()) {
                    $this->reject("Tidak ada ruang dengan kapasitas minimal {$requiredCapacity} untuk {$offering->masterMataKuliah?->name} — {$offering->kelas->name}.");
                }

                $sessionCredits = $this->splitCredits(
                    max(1, (int) $offering->sks),
                    $maximumCreditsPerSession
                );

                foreach ($sessionCredits as $sessionIndex => $credits) {
                    $duration = $credits * $minutesPerCredit;
                    $schedule = $this->findAvailableSchedule(
                        $offering,
                        $eligibleRooms,
                        $days,
                        $dayStartsAt,
                        $dayEndsAt,
                        $duration,
                        $gapMinutes,
                        $occupancy
                    );

                    if (! $schedule) {
                        $sessionLabel = count($sessionCredits) > 1
                            ? ' sesi '.($sessionIndex + 1).' dari '.count($sessionCredits)." ({$credits} SKS)"
                            : '';
                        $this->reject("Slot jadwal tidak mencukupi untuk {$offering->masterMataKuliah?->name} — {$offering->kelas->name}{$sessionLabel}. Tambah hari atau rentang jam; tambah ruang hanya membantu jika bentroknya pada ruang.");
                    }

                    $this->conflicts->validate($offering, $schedule);
                    JadwalMingguan::create([
                        ...$schedule,
                        'sks' => $credits,
                        'code' => 'JMG-AUTO-'.Str::upper(Str::random(12)),
                        'fingerprint' => JadwalMingguan::fingerprint($schedule),
                    ]);
                    $this->reserve($occupancy, $schedule);
                    $created++;
                }
            }

            $dryRun ? DB::rollBack() : DB::commit();

            return compact('created', 'skipped');
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }
    }

    private function findAvailableSchedule(
        PenawaranMataKuliah $offering,
        $rooms,
        array $days,
        string $dayStartsAt,
        string $dayEndsAt,
        int $duration,
        int $gapMinutes,
        array $occupancy
    ): ?array {
        $startBoundary = $this->minutes($dayStartsAt);
        $endBoundary = $this->minutes($dayEndsAt);

        foreach ($days as $day) {
            for ($start = $startBoundary; $start + $duration <= $endBoundary; $start += 10) {
                $end = $start + $duration;
                $conflictStart = max($startBoundary, $start - $gapMinutes);
                $conflictEnd = min($endBoundary, $end + $gapMinutes);

                if ($this->isBusy($occupancy['kelas'][(int) $day][$offering->kelas_id] ?? [], $conflictStart, $conflictEnd)
                    || $this->isBusy($occupancy['dosen'][(int) $day][$offering->dosen_utama_id] ?? [], $conflictStart, $conflictEnd)) {
                    continue;
                }

                foreach ($rooms as $room) {
                    if ($this->isBusy($occupancy['ruang'][(int) $day][$room->id] ?? [], $conflictStart, $conflictEnd)) {
                        continue;
                    }

                    $attributes = [
                        'penawaran_mata_kuliah_id' => $offering->id,
                        'kelas_id' => $offering->kelas_id,
                        'dosen_id' => $offering->dosen_utama_id,
                        'ruang_id' => $room->id,
                        'hari' => (int) $day,
                        'mulai' => $this->time($start),
                        'selesai' => $this->time($end),
                    ];

                    return $attributes;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, array<int, array<int, list<array{0: int, 1: int}>>>>
     */
    private function occupancy(TahunAkademik $period): array
    {
        $occupancy = ['kelas' => [], 'dosen' => [], 'ruang' => []];

        JadwalMingguan::query()
            ->forAcademicPeriod($period)
            ->get(['kelas_id', 'dosen_id', 'ruang_id', 'hari', 'mulai', 'selesai'])
            ->each(function (JadwalMingguan $schedule) use (&$occupancy): void {
                $this->reserve($occupancy, [
                    'kelas_id' => $schedule->kelas_id,
                    'dosen_id' => $schedule->dosen_id,
                    'ruang_id' => $schedule->ruang_id,
                    'hari' => $schedule->hari,
                    'mulai' => $schedule->mulai,
                    'selesai' => $schedule->selesai,
                ]);
            });

        return $occupancy;
    }

    private function reserve(array &$occupancy, array $schedule): void
    {
        $day = (int) $schedule['hari'];
        $window = [$this->minutes($schedule['mulai']), $this->minutes($schedule['selesai'])];

        foreach (['kelas' => 'kelas_id', 'dosen' => 'dosen_id', 'ruang' => 'ruang_id'] as $resource => $key) {
            $occupancy[$resource][$day][(int) $schedule[$key]][] = $window;
        }
    }

    /** @param list<array{0: int, 1: int}> $windows */
    private function isBusy(array $windows, int $start, int $end): bool
    {
        foreach ($windows as [$occupiedStart, $occupiedEnd]) {
            if ($occupiedStart < $end && $occupiedEnd > $start) {
                return true;
            }
        }

        return false;
    }

    /** @return list<int> */
    private function splitCredits(int $totalCredits, int $maximumCreditsPerSession): array
    {
        $sessionCount = (int) ceil($totalCredits / $maximumCreditsPerSession);
        $baseCredits = intdiv($totalCredits, $sessionCount);
        $remainder = $totalCredits % $sessionCount;

        return collect(range(0, $sessionCount - 1))
            ->map(fn (int $index): int => $baseCredits + ($index < $remainder ? 1 : 0))
            ->all();
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    private function time(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['schedule' => $message]);
    }
}
