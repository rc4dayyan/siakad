<?php

namespace App\Services\Academic;

use App\Models\AcademicWorkflowAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AcademicAuditService
{
    private const SENSITIVE_KEYS = ['password', 'token', 'snap_token', 'server_key', 'client_key', 'credential'];

    public function record(
        string $event,
        ?Model $subject,
        ?int $periodId,
        Model|int|null $actor,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null
    ): ?AcademicWorkflowAudit {
        if (! Schema::hasTable('academic_workflow_audits')) {
            return null;
        }

        return AcademicWorkflowAudit::create([
            'taka_id' => $periodId,
            'actor_id' => $actor instanceof User ? $actor->id : (is_int($actor) ? $actor : null),
            'actor_type' => $actor instanceof Model ? $actor::class : null,
            'actor_reference_id' => $actor instanceof Model ? $actor->getKey() : null,
            'event' => $event,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'before' => $this->sanitize($before),
            'after' => $this->sanitize($after),
            'metadata' => $this->sanitize($metadata),
        ]);
    }

    private function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                unset($values[$key]);
            } elseif (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            }
        }

        return $values;
    }
}
