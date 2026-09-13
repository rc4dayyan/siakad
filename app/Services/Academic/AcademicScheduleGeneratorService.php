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
        $lecturerLoads = $requiredOfferings->countBy('dosen_utama_id');
        $classLoads = $requiredOfferings->countBy('kelas_id');
        $unscheduled = $requiredOfferings
            ->where('jadwal_mingguans_count', 0)
            ->sortBy([
                fn (PenawaranMataKuliah $a, PenawaranMataKuliah $b): int => ($lecturerLoads[$b->dosen_utama_id] ?? 0) <=> ($lecturerLoads[$a->dosen_utama_id] ?? 0),
                fn (PenawaranMataKuliah $a, PenawaranMataKuliah $b): int => ($classLoads[$b->kelas_id] ?? 0) <=> ($classLoads[$a->kelas_id] ?? 0),
                fn (PenawaranMataKuliah $a, PenawaranMataKuliah $b): int => $b->kapasitas <=> $a->kapasitas,
                fn (PenawaranMataKuliah $a, PenawaranMataKuliah $b): int => $a->id <=> $b->id,
            ])->values();

        if ($offerings->isEmpty()) {
            $this->reject('Belum ada penawaran mata kuliah pada periode yang dipilih.');
        }

        // The reference timetable defines fixed slots, independent of SKS,
        // operating-window inputs and additional gaps between sessions.
        $gapMinutes = 0;

        $occupancy = $this->occupancy($period);

        DB::beginTransaction();

        try {
            $created = 0;
            $candidates = [];

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

                $candidates[$offering->id] = $this->availableSchedules(
                    $offering,
                    $eligibleRooms,
                    $days,
                    $gapMinutes,
                    $occupancy
                );
                if (! $candidates[$offering->id]) {
                    $this->reject("Tidak ada slot jadwal tetap untuk {$offering->masterMataKuliah?->name} — {$offering->kelas->name} pada hari dan jam yang dipilih.");
                }
            }

            $attempts = 0;
            $plan = $this->planSchedules($candidates, $occupancy, $gapMinutes, $attempts);
            if ($plan === null) {
                $reason = $attempts >= 5000 ? 'Batas pencarian susunan jadwal tercapai.' : 'Tidak ditemukan susunan jadwal tanpa bentrok.';
                $this->reject($reason.' Periksa jumlah mata kuliah per kelas dan dosen, hari kuliah, serta jeda antarjadwal. Tambah hari atau kurangi jeda jika memungkinkan.');
            }
            foreach ($unscheduled as $offering) {
                $schedule = $plan[$offering->id];
                $this->conflicts->validate($offering, $schedule);
                JadwalMingguan::create([
                    ...$schedule,
                    'sks' => $offering->sks,
                    'code' => 'JMG-AUTO-'.Str::upper(Str::random(12)),
                    'fingerprint' => JadwalMingguan::fingerprint($schedule),
                ]);
                $this->reserve($occupancy, $schedule);
                $created++;
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

    private function availableSchedules(
        PenawaranMataKuliah $offering,
        $rooms,
        array $days,
        int $gapMinutes,
        array $occupancy
    ): array {
        $schedules = [];

        foreach ($days as $day) {
            foreach (AcademicScheduleBreak::TEACHING_SLOTS as [$startTime, $endTime]) {
                $start = $this->minutes($startTime);
                $end = $this->minutes($endTime);
                $conflictStart = $start;
                $conflictEnd = $end;

                if ($this->isBusy($occupancy['kelas'][(int) $day][$offering->kelas_id] ?? [], $conflictStart, $conflictEnd)
                    || $this->isBusy($occupancy['dosen'][(int) $day][$offering->dosen_utama_id] ?? [], $conflictStart, $conflictEnd)) {
                    continue;
                }

                $roomIds = $rooms->filter(fn ($room) => ! $this->isBusy(
                    $occupancy['ruang'][(int) $day][$room->id] ?? [], $conflictStart, $conflictEnd
                ))->pluck('id')->all();
                if ($roomIds) {
                    $schedules[] = [
                        'penawaran_mata_kuliah_id' => $offering->id,
                        'kelas_id' => $offering->kelas_id,
                        'dosen_id' => $offering->dosen_utama_id,
                        'ruang_id' => $roomIds[0],
                        'room_choices' => $roomIds,
                        'hari' => (int) $day,
                        'mulai' => $this->time($start),
                        'selesai' => $this->time($end),
                    ];
                }
            }
        }

        return $schedules;
    }

    /** @return array<int, array>|null */
    private function planSchedules(array $domains, array $occupancy, int $gapMinutes, int &$attempts): ?array
    {
        if (! $domains) {
            return [];
        }
        if (++$attempts >= 5000) {
            return null;
        }
        $selectedId = null;
        $selectedOptions = [];
        $filtered = [];
        foreach ($domains as $offeringId => $options) {
            $available = [];
            foreach ($options as $option) {
                $start = $this->minutes($option['mulai']) - $gapMinutes;
                $end = $this->minutes($option['selesai']) + $gapMinutes;
                foreach (['kelas' => 'kelas_id', 'dosen' => 'dosen_id'] as $resource => $key) {
                    if ($this->isBusy($occupancy[$resource][$option['hari']][$option[$key]] ?? [], $start, $end)) {
                        continue 2;
                    }
                }
                foreach ($option['room_choices'] as $roomId) {
                    if (! $this->isBusy($occupancy['ruang'][$option['hari']][$roomId] ?? [], $start, $end)) {
                        $option['ruang_id'] = $roomId;
                        $available[] = $option;
                        break;
                    }
                }
            }
            if (! $available) {
                return null;
            }
            $filtered[$offeringId] = $available;
            if ($selectedId === null || count($available) < count($selectedOptions)) {
                $selectedId = $offeringId;
                $selectedOptions = $available;
            }
        }
        unset($filtered[$selectedId]);
        foreach ($selectedOptions as $schedule) {
            $nextOccupancy = $occupancy;
            $this->reserve($nextOccupancy, $schedule);
            $rest = $this->planSchedules($filtered, $nextOccupancy, $gapMinutes, $attempts);
            if ($rest !== null) {
                unset($schedule['room_choices']);

                return [$selectedId => $schedule] + $rest;
            }
            if ($attempts >= 5000) {
                break;
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
