<?php

namespace App\Services\Finance;

use App\Models\ProgramKuliah;
use App\Models\RegistrasiMahasiswa;
use App\Models\TemplateTagihan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BillingTargetService
{
    public const TARGET_TYPES = ['mahasiswa', 'prodi', 'proku', 'kelompok'];

    public function validate(TemplateTagihan $template): void
    {
        if (! in_array($template->target_type, self::TARGET_TYPES, true)) {
            $this->fail('Jenis target tagihan tidak valid.');
        }

        $targets = array_filter([
            'mahasiswa' => $template->target_mahasiswa_id,
            'prodi' => $template->target_prodi_id,
            'proku' => $template->target_proku_id,
            'kelompok' => $template->kelompok_target,
        ], fn ($value) => filled($value));

        if (count($targets) !== 1 || ! array_key_exists($template->target_type, $targets)) {
            $this->fail('Template harus memiliki tepat satu target yang sesuai dengan jenis target.');
        }

        $belongsToPeriod = match ($template->target_type) {
            'mahasiswa' => RegistrasiMahasiswa::forAcademicPeriod($template->taka_id)
                ->where('mahasiswa_id', $template->target_mahasiswa_id)->exists(),
            'prodi' => RegistrasiMahasiswa::forAcademicPeriod($template->taka_id)
                ->whereHas('kelas', fn (Builder $query) => $query->where('pstudi_id', $template->target_prodi_id))->exists(),
            'proku' => ProgramKuliah::query()->whereKey($template->target_proku_id)
                ->where('taka_id', $template->taka_id)->exists(),
            'kelompok' => $template->kelompok_target === 'semua'
                || in_array($template->kelompok_target, RegistrasiMahasiswa::academicStatuses(), true),
        };

        if (! $belongsToPeriod) {
            $this->fail('Target tidak terdaftar pada periode akademik template.');
        }
    }

    public function candidateQuery(TemplateTagihan $template): Builder
    {
        $this->validate($template);

        return RegistrasiMahasiswa::query()
            ->forAcademicPeriod($template->taka_id)
            ->with(['mahasiswa', 'kelas.pstudi', 'kelas.proku'])
            ->whereNotNull('mahasiswa_id')
            ->when($template->target_type === 'mahasiswa', fn (Builder $query) => $query
                ->where('mahasiswa_id', $template->target_mahasiswa_id))
            ->when($template->target_type === 'prodi', fn (Builder $query) => $query
                ->whereHas('kelas', fn (Builder $class) => $class->where('pstudi_id', $template->target_prodi_id)))
            ->when($template->target_type === 'proku', fn (Builder $query) => $query
                ->whereHas('kelas', fn (Builder $class) => $class->where('proku_id', $template->target_proku_id)))
            ->when(
                $template->target_type === 'kelompok' && $template->kelompok_target !== 'semua',
                fn (Builder $query) => $query->where('status_akademik', $template->kelompok_target)
            )
            ->orderBy('id');
    }

    public function candidates(TemplateTagihan $template): Collection
    {
        return $this->candidateQuery($template)->get()->unique('mahasiswa_id')->values();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['target' => $message]);
    }
}
