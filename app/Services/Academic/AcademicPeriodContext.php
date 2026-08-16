<?php

namespace App\Services\Academic;

use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AcademicPeriodContext
{
    public const SESSION_KEY = 'selected_taka_id';

    private const HISTORY_ROLES = [0, 1, 3, 4];

    public function active(): ?TahunAkademik
    {
        return TahunAkademik::query()
            ->where('status', TahunAkademik::STATUS_ACTIVE)
            ->where('is_active', true)
            ->orderByDesc('activated_at')
            ->first();
    }

    public function published(): ?TahunAkademik
    {
        $supportsPublication = Schema::hasColumn('tahun_akademiks', 'is_published');

        return TahunAkademik::query()
            ->where('status', TahunAkademik::STATUS_ACTIVE)
            ->where('is_active', true)
            ->when($supportsPublication, fn ($query) => $query->where('is_published', true)->orderByDesc('published_at'))
            ->orderByDesc('activated_at')
            ->first();
    }

    public function current(User $user): ?TahunAkademik
    {
        $selectedId = session(self::SESSION_KEY);

        if ($selectedId) {
            $selected = TahunAkademik::find($selectedId);

            if ($selected && $this->isAvailableTo($selected, $user)) {
                return $selected;
            }

            session()->forget(self::SESSION_KEY);
        }

        $active = $this->active();

        if (! $active && $this->canBrowseHistory($user)) {
            $active = TahunAkademik::query()
                ->where('status', TahunAkademik::STATUS_DRAFT)
                ->orderByDesc('year_start')
                ->orderByDesc('starts_at')
                ->first();
        }

        if ($active) {
            session()->put(self::SESSION_KEY, $active->getKey());
        }

        return $active;
    }

    public function select(TahunAkademik $period, User $user): void
    {
        abort_unless($this->isAvailableTo($period, $user), 403);

        session()->put(self::SESSION_KEY, $period->getKey());
    }

    public function requireCurrent(User $user): TahunAkademik
    {
        $period = $this->current($user);

        if (! $period) {
            throw ValidationException::withMessages([
                'academic_period' => 'Belum ada periode akademik yang dapat digunakan.',
            ]);
        }

        return $period;
    }

    public function requireWritableCurrent(User $user): TahunAkademik
    {
        $period = $this->requireCurrent($user);

        if (! $period->isWritable()) {
            throw ValidationException::withMessages([
                'academic_period' => 'Periode yang ditutup atau diarsipkan hanya dapat dilihat.',
            ]);
        }

        return $period;
    }

    public function availableFor(User $user): Collection
    {
        return TahunAkademik::query()
            ->when(! $this->canBrowseHistory($user), function ($query): void {
                $query->where('status', TahunAkademik::STATUS_ACTIVE)
                    ->where('is_active', true);
            })
            ->orderByDesc('year_start')
            ->orderByDesc('starts_at')
            ->get();
    }

    public function isAvailableTo(TahunAkademik $period, User $user): bool
    {
        return $this->canBrowseHistory($user)
            || ($period->status === TahunAkademik::STATUS_ACTIVE && $period->is_active);
    }

    private function canBrowseHistory(User $user): bool
    {
        return in_array((int) $user->raw_type, self::HISTORY_ROLES, true);
    }
}
