<?php

namespace App\Services\Finance;

use App\Models\PenerbitanTagihanBatch;
use App\Models\TagihanKuliah;
use App\Models\TemplateTagihan;
use App\Models\User;
use App\Services\Academic\AcademicAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class BulkBillingService
{
    public function __construct(
        private readonly BillingTargetService $targets,
        private readonly AcademicAuditService $audit
    ) {}

    public function preview(TemplateTagihan $template): array
    {
        $candidateIds = $this->targets->candidateQuery($template)
            ->pluck('mahasiswa_id')
            ->filter()
            ->unique()
            ->values();
        $existingStudentIds = $candidateIds->isEmpty()
            ? collect()
            : TagihanKuliah::forAcademicPeriod($template->taka_id)
                ->where('target_type', 'mahasiswa')
                ->where('jenis', $template->jenis)
                ->whereIn('target_mahasiswa_id', $candidateIds)
                ->pluck('target_mahasiswa_id')
                ->unique()
                ->values();
        $readyCount = $candidateIds->diff($existingStudentIds)->count();

        return [
            'calon' => $candidateIds->count(),
            'siap' => $readyCount,
            'dilewati' => $existingStudentIds->count(),
            'total_nominal' => $readyCount * $template->nominal,
            'mahasiswa_ids_tertagih' => $existingStudentIds->all(),
        ];
    }

    public function issue(TemplateTagihan $template, User $actor): PenerbitanTagihanBatch
    {
        $this->targets->validate($template);

        return DB::transaction(function () use ($template, $actor): PenerbitanTagihanBatch {
            $candidateCount = (clone $this->targets->candidateQuery($template))->count();
            $batch = PenerbitanTagihanBatch::create([
                'template_tagihan_id' => $template->id,
                'taka_id' => $template->taka_id,
                'actor_id' => $actor->id,
                'calon' => $candidateCount,
                'konfigurasi' => ['target_type' => $template->target_type],
            ]);
            $result = ['berhasil' => 0, 'dilewati' => 0, 'gagal' => 0];

            $this->targets->candidateQuery($template)->chunkById(200, function ($registrations) use ($template, $actor, &$result): void {
                foreach ($registrations->unique('mahasiswa_id') as $registration) {
                    if (TagihanKuliah::forAcademicPeriod($template->taka_id)
                        ->forStudent($registration->mahasiswa_id)
                        ->where('jenis', $template->jenis)->exists()) {
                        $result['dilewati']++;

                        continue;
                    }

                    try {
                        TagihanKuliah::create([
                            'author_id' => (string) $actor->id,
                            'proku_id' => '0',
                            'prodi_id' => '0',
                            'users_id' => (string) $registration->mahasiswa_id,
                            'name' => $template->name,
                            'code' => 'TGH-'.strtoupper(Str::random(16)),
                            'price' => (string) $template->nominal,
                            'taka_id' => $template->taka_id,
                            'template_tagihan_id' => $template->id,
                            'nominal' => $template->nominal,
                            'tanggal_terbit' => $template->tanggal_terbit,
                            'jatuh_tempo' => $template->jatuh_tempo,
                            'status' => TagihanKuliah::STATUS_TERBIT,
                            'jenis' => $template->jenis,
                            'wajib_lunas_krs' => $template->wajib_lunas_krs,
                            'target_type' => 'mahasiswa',
                            'target_mahasiswa_id' => $registration->mahasiswa_id,
                        ]);
                        $result['berhasil']++;
                    } catch (Throwable) {
                        $result['gagal']++;
                    }
                }
            });

            $batch->update($result + [
                'ringkasan' => "{$result['berhasil']} berhasil, {$result['dilewati']} dilewati, {$result['gagal']} gagal.",
            ]);
            $this->audit->record('billing.batch_issued', $batch, $template->taka_id, $actor, null,
                $result, ['template_id' => $template->id, 'candidate_count' => $candidateCount]);

            return $batch->refresh();
        });
    }
}
