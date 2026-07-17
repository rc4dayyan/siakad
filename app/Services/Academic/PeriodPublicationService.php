<?php

namespace App\Services\Academic;

use App\Models\Notification;
use App\Models\PeriodPublication;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PeriodPublicationService
{
    public function __construct(
        private readonly PeriodReadinessService $readiness,
        private readonly AcademicAuditService $audit
    ) {}

    public function publish(TahunAkademik $period, User $actor): PeriodPublication
    {
        abort_unless((int) $actor->raw_type === 0, 403);

        if ($period->status !== TahunAkademik::STATUS_ACTIVE || ! $period->is_active) {
            throw ValidationException::withMessages(['publication' => 'Periode harus diaktifkan secara internal sebelum dipublikasikan.']);
        }

        $snapshot = $this->readiness->snapshot($period, $actor, 'publication');
        if ($snapshot->failed_count > 0) {
            throw ValidationException::withMessages(['publication' => 'Publikasi ditolak karena masih ada pemeriksaan wajib yang gagal.']);
        }

        return DB::transaction(function () use ($period, $actor, $snapshot): PeriodPublication {
            $locked = TahunAkademik::query()->lockForUpdate()->findOrFail($period->id);
            if ($locked->is_published) {
                return PeriodPublication::where('taka_id', $locked->id)->firstOrFail();
            }

            $summary = [
                'status' => $snapshot->status,
                'siap' => $snapshot->ready_count,
                'peringatan' => $snapshot->warning_count,
                'gagal' => $snapshot->failed_count,
            ];
            $publication = PeriodPublication::create([
                'taka_id' => $locked->id,
                'readiness_snapshot_id' => $snapshot->id,
                'actor_id' => $actor->id,
                'summary' => $summary,
                'published_at' => now(),
            ]);
            $locked->update(['is_published' => true, 'published_at' => now(), 'published_by' => $actor->id]);

            Notification::create([
                'auth_id' => $actor->id,
                'send_to' => 1,
                'name' => 'Periode akademik dipublikasikan',
                'slug' => '/web-admin/period-opening',
                'type' => 'academic_period',
                'desc' => "Periode {$locked->name} telah dipublikasikan ke portal dosen dan mahasiswa.",
                'code' => 'PUB-'.Str::upper(Str::random(20)),
            ]);
            $this->audit->record('period.published', $locked, $locked->id, $actor,
                ['is_published' => false], ['is_published' => true], ['readiness' => $summary]);

            return $publication;
        });
    }
}
