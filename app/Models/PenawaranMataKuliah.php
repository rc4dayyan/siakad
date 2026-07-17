<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PenawaranMataKuliah extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['sks' => 'integer', 'kapasitas' => 'integer'];
    }

    public function scopeForAcademicPeriod(Builder $query, TahunAkademik|int|null $period): Builder
    {
        $periodId = $period instanceof TahunAkademik ? $period->id : $period;

        return $periodId ? $query->where('taka_id', $periodId) : $query->whereRaw('1 = 0');
    }

    public function masterMataKuliah()
    {
        return $this->belongsTo(MasterMataKuliah::class);
    }

    public function taka()
    {
        return $this->belongsTo(TahunAkademik::class, 'taka_id');
    }

    public function pstudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'pstudi_id');
    }

    public function kurikulum()
    {
        return $this->belongsTo(Kurikulum::class, 'kuri_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function dosenUtama()
    {
        return $this->belongsTo(Dosen::class, 'dosen_utama_id');
    }

    public function dosenPendamping1()
    {
        return $this->belongsTo(Dosen::class, 'dosen_pendamping_1_id');
    }

    public function dosenPendamping2()
    {
        return $this->belongsTo(Dosen::class, 'dosen_pendamping_2_id');
    }

    public function prasyaratMaster()
    {
        return $this->belongsTo(MasterMataKuliah::class, 'prasyarat_master_id');
    }

    public function legacyMataKuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'legacy_mata_kuliah_id');
    }

    public function krsItems()
    {
        return $this->hasMany(KrsItem::class);
    }

    public function pesertaDisetujui()
    {
        return Mahasiswa::query()->whereHas('registrasiAkademik.krs.items', fn (Builder $query) => $query
            ->where('penawaran_mata_kuliah_id', $this->id)
            ->whereHas('krs', fn (Builder $krs) => $krs->whereIn('status', [Krs::STATUS_APPROVED, Krs::STATUS_LOCKED])));
    }

    public function hasParticipants(): bool
    {
        return $this->krsItems()->exists();
    }
}
