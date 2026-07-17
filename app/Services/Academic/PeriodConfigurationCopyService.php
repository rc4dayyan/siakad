<?php

namespace App\Services\Academic;

use App\Models\JadwalMingguan;
use App\Models\Kelas;
use App\Models\PenawaranMataKuliah;
use App\Models\PeriodCopyRun;
use App\Models\ProgramKuliah;
use App\Models\TahunAkademik;
use App\Models\TemplateTagihan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PeriodConfigurationCopyService
{
    public const SELECTIONS = ['kelas', 'penawaran', 'dosen', 'jadwal', 'template_tagihan'];

    public function __construct(
        private readonly ScheduleConflictService $scheduleConflicts,
        private readonly AcademicAuditService $audit
    ) {}

    public function preview(TahunAkademik $source, TahunAkademik $target, array $selections): array
    {
        $this->validateConfiguration($source, $target, $selections);
        $conflicts = [];

        foreach (Kelas::forAcademicPeriod($source)->with('proku')->get() as $class) {
            if (in_array('kelas', $selections, true) && $class->proku_id && ! $this->targetProgram($class->proku, $target)) {
                $conflicts[] = "Kelas {$class->name}: program kuliah tujuan belum tersedia.";
            }
        }

        $counts = [
            'kelas' => in_array('kelas', $selections, true) ? Kelas::forAcademicPeriod($source)->count() : 0,
            'penawaran' => in_array('penawaran', $selections, true) ? PenawaranMataKuliah::forAcademicPeriod($source)->count() : 0,
            'dosen' => in_array('dosen', $selections, true) ? PenawaranMataKuliah::forAcademicPeriod($source)->distinct('dosen_utama_id')->count('dosen_utama_id') : 0,
            'jadwal' => in_array('jadwal', $selections, true) ? JadwalMingguan::forAcademicPeriod($source)->count() : 0,
            'template_tagihan' => in_array('template_tagihan', $selections, true) ? TemplateTagihan::forAcademicPeriod($source)->count() : 0,
        ];

        return ['counts' => $counts, 'total' => array_sum($counts), 'conflicts' => $conflicts,
            'excluded' => ['KRS', 'nilai', 'pembayaran', 'presensi']];
    }

    public function execute(TahunAkademik $source, TahunAkademik $target, array $selections, User $actor): PeriodCopyRun
    {
        $preview = $this->preview($source, $target, $selections);

        return DB::transaction(function () use ($source, $target, $selections, $actor, $preview): PeriodCopyRun {
            $run = PeriodCopyRun::create([
                'source_period_id' => $source->id,
                'target_period_id' => $target->id,
                'actor_id' => $actor->id,
                'selections' => array_values($selections),
                'status' => 'running',
            ]);
            $result = ['created' => 0, 'skipped' => 0, 'conflicts' => $preview['conflicts'], 'items' => []];
            $classMap = [];
            $offeringMap = [];

            if (in_array('kelas', $selections, true)) {
                foreach (Kelas::forAcademicPeriod($source)->with('proku')->get() as $class) {
                    $targetProgram = $class->proku_id ? $this->targetProgram($class->proku, $target) : null;
                    if ($class->proku_id && ! $targetProgram) {
                        continue;
                    }
                    $existing = Kelas::forAcademicPeriod($target)->where('pstudi_id', $class->pstudi_id)->where('name', $class->name)->first();
                    if ($existing) {
                        $classMap[$class->id] = $existing;
                        $result['skipped']++;

                        continue;
                    }
                    $created = Kelas::create([
                        'taka_id' => $target->id, 'pstudi_id' => $class->pstudi_id, 'proku_id' => $targetProgram?->id,
                        'dosen_id' => in_array('dosen', $selections, true) ? $class->dosen_id : null,
                        'capacity' => $class->capacity, 'name' => $class->name,
                        'code' => $this->code($class->code, $target),
                    ]);
                    $classMap[$class->id] = $created;
                    $result['created']++;
                }
            }

            if (in_array('penawaran', $selections, true)) {
                foreach (PenawaranMataKuliah::forAcademicPeriod($source)->get() as $offering) {
                    $targetClass = $classMap[$offering->kelas_id]
                        ?? Kelas::forAcademicPeriod($target)->where('pstudi_id', $offering->pstudi_id)
                            ->where('name', $offering->kelas?->name)->first();
                    if (! $targetClass) {
                        $result['conflicts'][] = "Penawaran {$offering->code}: kelas tujuan tidak ditemukan.";

                        continue;
                    }
                    $existing = PenawaranMataKuliah::forAcademicPeriod($target)
                        ->where('master_mata_kuliah_id', $offering->master_mata_kuliah_id)
                        ->where('kelas_id', $targetClass->id)->first();
                    if ($existing) {
                        $offeringMap[$offering->id] = $existing;
                        $result['skipped']++;

                        continue;
                    }
                    $created = PenawaranMataKuliah::create([
                        'master_mata_kuliah_id' => $offering->master_mata_kuliah_id, 'taka_id' => $target->id,
                        'pstudi_id' => $offering->pstudi_id, 'kuri_id' => $offering->kuri_id, 'kelas_id' => $targetClass->id,
                        'dosen_utama_id' => $offering->dosen_utama_id, 'dosen_pendamping_1_id' => $offering->dosen_pendamping_1_id,
                        'dosen_pendamping_2_id' => $offering->dosen_pendamping_2_id, 'prasyarat_master_id' => $offering->prasyarat_master_id,
                        'legacy_mata_kuliah_id' => null, 'code' => $this->code($offering->code, $target),
                        'sks' => $offering->sks, 'kapasitas' => $offering->kapasitas, 'deskripsi' => $offering->deskripsi,
                    ]);
                    $offeringMap[$offering->id] = $created;
                    $result['created']++;
                }
            }

            if (in_array('jadwal', $selections, true)) {
                foreach (JadwalMingguan::forAcademicPeriod($source)->get() as $schedule) {
                    $targetOffering = $offeringMap[$schedule->penawaran_mata_kuliah_id] ?? null;
                    $targetClass = $targetOffering?->kelas;
                    if (! $targetOffering || ! $targetClass) {
                        $result['conflicts'][] = "Jadwal {$schedule->code}: penawaran tujuan tidak ditemukan.";

                        continue;
                    }
                    $attributes = [
                        'penawaran_mata_kuliah_id' => $targetOffering->id, 'kelas_id' => $targetClass->id,
                        'dosen_id' => $schedule->dosen_id, 'ruang_id' => $schedule->ruang_id,
                        'hari' => $schedule->hari, 'mulai' => $schedule->mulai, 'selesai' => $schedule->selesai,
                    ];
                    $fingerprint = JadwalMingguan::fingerprint($attributes);
                    if (JadwalMingguan::where('fingerprint', $fingerprint)->exists()) {
                        $result['skipped']++;

                        continue;
                    }
                    try {
                        $override = $this->scheduleConflicts->validate($targetOffering, $attributes);
                        JadwalMingguan::create($attributes + $override + [
                            'code' => $this->code($schedule->code, $target), 'fingerprint' => $fingerprint,
                        ]);
                        $result['created']++;
                    } catch (ValidationException $exception) {
                        $result['conflicts'][] = "Jadwal {$schedule->code}: ".collect($exception->errors())->flatten()->first();
                    }
                }
            }

            if (in_array('template_tagihan', $selections, true)) {
                foreach (TemplateTagihan::forAcademicPeriod($source)->get() as $template) {
                    $targetProku = $template->target_proku_id ? $this->targetProgram($template->targetProku, $target) : null;
                    $targetStudent = $template->target_mahasiswa_id && DB::table('registrasi_mahasiswas')
                        ->where('taka_id', $target->id)->where('mahasiswa_id', $template->target_mahasiswa_id)->exists()
                        ? $template->target_mahasiswa_id : null;
                    if (($template->target_proku_id && ! $targetProku) || ($template->target_mahasiswa_id && ! $targetStudent)) {
                        $result['conflicts'][] = "Template {$template->name}: target tidak tersedia pada periode tujuan.";

                        continue;
                    }
                    if (TemplateTagihan::forAcademicPeriod($target)->where('jenis', $template->jenis)->where('name', $template->name)->exists()) {
                        $result['skipped']++;

                        continue;
                    }
                    TemplateTagihan::create([
                        'taka_id' => $target->id, 'name' => $template->name, 'jenis' => $template->jenis,
                        'nominal' => $template->nominal, 'tanggal_terbit' => $target->starts_at,
                        'jatuh_tempo' => $target->starts_at->copy()->addMonth(), 'wajib_lunas_krs' => $template->wajib_lunas_krs,
                        'target_type' => $template->target_type, 'target_mahasiswa_id' => $targetStudent,
                        'target_prodi_id' => $template->target_prodi_id, 'target_proku_id' => $targetProku?->id,
                        'kelompok_target' => $template->kelompok_target, 'created_by' => $actor->id,
                    ]);
                    $result['created']++;
                }
            }

            $run->update([
                'status' => $result['conflicts'] === [] ? 'selesai' : 'selesai_dengan_konflik',
                'created_count' => $result['created'], 'skipped_count' => $result['skipped'],
                'conflict_count' => count($result['conflicts']), 'details' => $result,
            ]);
            $this->audit->record('period.configuration_copied', $run, $target->id, $actor, null,
                ['created' => $result['created'], 'skipped' => $result['skipped'], 'conflicts' => count($result['conflicts'])],
                ['source_period_id' => $source->id, 'selections' => $selections]);

            return $run->fresh();
        });
    }

    private function validateConfiguration(TahunAkademik $source, TahunAkademik $target, array $selections): void
    {
        if ($source->is($target) || $target->status !== TahunAkademik::STATUS_DRAFT || $target->is_active) {
            throw ValidationException::withMessages(['target_period_id' => 'Periode tujuan harus berbeda dan masih berstatus draft.']);
        }
        if (array_diff($selections, self::SELECTIONS) !== []) {
            throw ValidationException::withMessages(['selections' => 'Pilihan data salin tidak valid.']);
        }
        if (in_array('penawaran', $selections, true) && (! in_array('kelas', $selections, true) || ! in_array('dosen', $selections, true))) {
            throw ValidationException::withMessages(['selections' => 'Penawaran membutuhkan pilihan kelas dan dosen.']);
        }
        if (in_array('jadwal', $selections, true) && ! in_array('penawaran', $selections, true)) {
            throw ValidationException::withMessages(['selections' => 'Jadwal membutuhkan penawaran, kelas, dan dosen.']);
        }
    }

    private function targetProgram(?ProgramKuliah $sourceProgram, TahunAkademik $target): ?ProgramKuliah
    {
        return $sourceProgram ? ProgramKuliah::query()->where('taka_id', $target->id)
            ->where('pstudi_id', $sourceProgram->pstudi_id)->where('name', $sourceProgram->name)->first() : null;
    }

    private function code(string $sourceCode, TahunAkademik $target): string
    {
        return Str::limit($sourceCode.'-'.$target->code, 250, '').'-'.Str::upper(Str::random(4));
    }
}
