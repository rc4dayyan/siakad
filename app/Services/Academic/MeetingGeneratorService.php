<?php

namespace App\Services\Academic;

use App\Models\JadwalKuliah;
use App\Models\JadwalMingguan;
use App\Models\KalenderAkademik;
use App\Models\MataKuliah;
use App\Models\PertemuanKuliah;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MeetingGeneratorService
{
    public function preview(JadwalMingguan $schedule, string $start, string $end, int $count): Collection
    {
        $startDate = CarbonImmutable::parse($start)->startOfDay();
        $endDate = CarbonImmutable::parse($end)->startOfDay();
        $holidays = KalenderAkademik::query()
            ->where('taka_id', $schedule->penawaranMataKuliah->taka_id)
            ->where('kategori', 'libur')
            ->where('dipublikasikan', true)
            ->get(['mulai_at', 'selesai_at']);
        $schedule->loadMissing('pertemuans');
        $existingDates = $schedule->pertemuans
            ->pluck('tanggal')
            ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString());
        $dates = collect();

        for ($date = $startDate; $date->lte($endDate) && $dates->count() < $count; $date = $date->addDay()) {
            if ($date->dayOfWeek !== $schedule->hari) {
                continue;
            }
            $holiday = $holidays->first(fn ($event) => $date->betweenIncluded(
                CarbonImmutable::parse($event->mulai_at)->startOfDay(),
                CarbonImmutable::parse($event->selesai_at)->startOfDay()
            ));
            if ($holiday) {
                continue;
            }
            $dates->push([
                'pertemuan_ke' => $dates->count() + 1,
                'tanggal' => $date->toDateString(),
                'sudah_ada' => $existingDates->contains($date->toDateString()),
            ]);
        }

        return $dates;
    }

    public function generate(JadwalMingguan $schedule, string $start, string $end, int $count): array
    {
        $schedule->unsetRelation('pertemuans')->load('pertemuans');
        $preview = $this->preview($schedule, $start, $end, $count);
        $result = ['created' => 0, 'skipped' => 0];
        $existingMeetingNumbers = $schedule->pertemuans->pluck('pertemuan_ke')->map(fn ($number) => (int) $number);

        DB::transaction(function () use ($schedule, $preview, $existingMeetingNumbers, &$result): void {
            $legacyCourse = $this->ensureLegacyCourse($schedule);
            foreach ($preview as $candidate) {
                if ($candidate['sudah_ada'] || $existingMeetingNumbers->contains($candidate['pertemuan_ke'])) {
                    $result['skipped']++;

                    continue;
                }

                $code = 'PTM-'.Str::upper(Str::random(12));
                $legacy = JadwalKuliah::create([
                    'makul_id' => $legacyCourse->id,
                    'penawaran_mata_kuliah_id' => $schedule->penawaran_mata_kuliah_id,
                    'kelas_id' => $schedule->kelas_id,
                    'dosen_id' => $schedule->dosen_id,
                    'ruang_id' => $schedule->ruang_id,
                    'pert_id' => $candidate['pertemuan_ke'],
                    'meth_id' => 0,
                    'days_id' => $schedule->hari,
                    'bsks' => min(8, $schedule->sks ?? $schedule->penawaranMataKuliah->sks),
                    'date' => $candidate['tanggal'],
                    'start' => $schedule->mulai,
                    'ended' => $schedule->selesai,
                    'code' => $code,
                ]);
                PertemuanKuliah::create([
                    'jadwal_mingguan_id' => $schedule->id,
                    'legacy_jadwal_kuliah_id' => $legacy->id,
                    'dosen_id' => $schedule->dosen_id,
                    'ruang_id' => $schedule->ruang_id,
                    'pertemuan_ke' => $candidate['pertemuan_ke'],
                    'tanggal' => $candidate['tanggal'],
                    'mulai' => $schedule->mulai,
                    'selesai' => $schedule->selesai,
                    'metode' => 'tatap_muka',
                    'status' => PertemuanKuliah::STATUS_SCHEDULED,
                    'code' => $code,
                ]);
                $result['created']++;
            }
        });

        return $result;
    }

    private function ensureLegacyCourse(JadwalMingguan $schedule): MataKuliah
    {
        $offering = $schedule->penawaranMataKuliah->loadMissing('masterMataKuliah');
        if ($offering->legacy_mata_kuliah_id) {
            return MataKuliah::findOrFail($offering->legacy_mata_kuliah_id);
        }

        $legacy = MataKuliah::create([
            'mid' => $offering->master_mata_kuliah_id,
            'kuri_id' => $offering->kuri_id,
            'taka_id' => $offering->taka_id,
            'pstudi_id' => $offering->pstudi_id,
            'kelas_id' => $offering->kelas_id,
            'dosen_1' => $offering->dosen_utama_id,
            'dosen_2' => $offering->dosen_pendamping_1_id,
            'dosen_3' => $offering->dosen_pendamping_2_id,
            'name' => $offering->masterMataKuliah->name,
            'code' => 'LEGACY-'.$offering->code,
            'bsks' => $offering->sks,
            'desc' => $offering->deskripsi ?: 'Record kompatibilitas penawaran mata kuliah.',
        ]);
        $offering->update(['legacy_mata_kuliah_id' => $legacy->id]);
        $offering->setRelation('legacyMataKuliah', $legacy);

        return $legacy;
    }
}
