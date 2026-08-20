<?php

namespace App\Services\Academic;

use App\Models\JadwalMingguan;
use App\Models\Kelas;
use App\Models\PenawaranMataKuliah;
use App\Models\PeriodReadinessSnapshot;
use App\Models\RegistrasiMahasiswa;
use App\Models\TagihanKuliah;
use App\Models\TahunAkademik;
use App\Models\TemplateTagihan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PeriodReadinessService
{
    public const READY = 'siap';

    public const WARNING = 'peringatan';

    public const FAILED = 'gagal';

    public function check(TahunAkademik $period): array
    {
        $checks = collect([
            $this->identityCheck($period),
            $this->curriculumCheck($period),
            $this->registrationCheck($period),
            $this->offeringCheck($period),
            $this->scheduleCheck($period),
            $this->billingCheck($period),
        ]);
        $counts = [
            'siap' => $checks->where('status', self::READY)->count(),
            'peringatan' => $checks->where('status', self::WARNING)->count(),
            'gagal' => $checks->where('status', self::FAILED)->count(),
        ];

        return [
            'status' => $counts['gagal'] > 0 ? self::FAILED : ($counts['peringatan'] > 0 ? self::WARNING : self::READY),
            'counts' => $counts,
            'checks' => $checks->all(),
            'progress' => (int) round(($counts['siap'] / max(1, $checks->count())) * 100),
        ];
    }

    public function snapshot(TahunAkademik $period, User $actor, string $purpose = 'manual'): PeriodReadinessSnapshot
    {
        $result = $this->check($period);

        return PeriodReadinessSnapshot::create([
            'taka_id' => $period->id,
            'actor_id' => $actor->id,
            'purpose' => $purpose,
            'status' => $result['status'],
            'ready_count' => $result['counts']['siap'],
            'warning_count' => $result['counts']['peringatan'],
            'failed_count' => $result['counts']['gagal'],
            'checks' => $result['checks'],
            'checked_at' => now(),
        ]);
    }

    private function identityCheck(TahunAkademik $period): array
    {
        $hasAcademicYear = ! Schema::hasColumn('tahun_akademiks', 'tid') || filled($period->tid);
        $complete = filled($period->name) && filled($period->code) && filled($period->term)
            && $period->year_start && $period->year_end && $period->starts_at && $period->ends_at
            && $period->year_end > $period->year_start && $period->ends_at->gte($period->starts_at)
            && $hasAcademicYear;

        return $this->result('identitas', 'Identitas dan tanggal periode', $complete ? self::READY : self::FAILED,
            $complete ? 'Tahun akademik, identitas, serta rentang tanggal periode lengkap.' : 'Hubungkan tahun akademik dan lengkapi identitas, jenis semester, serta rentang tanggal yang valid.',
            '/web-admin/master/data-taka');
    }

    private function curriculumCheck(TahunAkademik $period): array
    {
        $classes = Kelas::forAcademicPeriod($period)->count();
        $offerings = Schema::hasTable('penawaran_mata_kuliahs')
            ? PenawaranMataKuliah::forAcademicPeriod($period)->count() : 0;
        $invalid = Schema::hasTable('penawaran_mata_kuliahs')
            ? PenawaranMataKuliah::forAcademicPeriod($period)
                ->where(fn ($query) => $query->whereNull('pstudi_id')->orWhereNull('kuri_id'))->count() : 0;
        $status = $invalid > 0 ? self::FAILED : (($classes + $offerings) > 0 ? self::READY : self::WARNING);

        return $this->result('kurikulum_prodi', 'Kurikulum dan program studi', $status,
            $invalid > 0 ? "Ada {$invalid} penawaran tanpa kurikulum/program studi yang valid."
                : "{$classes} kelas dan {$offerings} penawaran memiliki konteks program studi.",
            '/web-admin/master/data-matkul');
    }

    private function registrationCheck(TahunAkademik $period): array
    {
        $classes = Kelas::forAcademicPeriod($period)->count();
        $registrations = RegistrasiMahasiswa::forAcademicPeriod($period)->count();
        $invalid = RegistrasiMahasiswa::forAcademicPeriod($period)
            ->where(fn ($query) => $query->whereNull('kelas_id')->orWhereNull('dosen_wali_id'))->count();
        $status = ($classes === 0 || $registrations === 0 || $invalid > 0) ? self::FAILED : self::READY;

        return $this->result('registrasi_kelas', 'Registrasi mahasiswa dan kelas', $status,
            "{$registrations} registrasi, {$classes} kelas, {$invalid} registrasi belum memiliki kelas/dosen wali.",
            '/web-admin/workers/data-student');
    }

    private function offeringCheck(TahunAkademik $period): array
    {
        if (! Schema::hasTable('penawaran_mata_kuliahs')) {
            return $this->result('penawaran_dosen', 'Penawaran dan dosen', self::FAILED, 'Struktur penawaran belum tersedia.', '/web-admin/master/penawaran-mata-kuliah');
        }

        $offerings = PenawaranMataKuliah::forAcademicPeriod($period)->count();
        $invalid = PenawaranMataKuliah::forAcademicPeriod($period)
            ->where(fn ($query) => $query->whereNull('dosen_utama_id')->orWhereNull('kelas_id'))->count();
        $inactive = PenawaranMataKuliah::forAcademicPeriod($period)
            ->whereHas('dosenUtama', fn ($query) => $query->where('dsn_stat', '!=', 1))->count();
        $status = ($offerings === 0 || $invalid > 0 || $inactive > 0) ? self::FAILED : self::READY;

        return $this->result('penawaran_dosen', 'Penawaran mata kuliah dan dosen', $status,
            "{$offerings} penawaran; {$invalid} referensi belum lengkap; {$inactive} dosen utama tidak aktif.",
            '/web-admin/master/penawaran-mata-kuliah');
    }

    private function scheduleCheck(TahunAkademik $period): array
    {
        if (! Schema::hasTable('jadwal_mingguans')) {
            return $this->result('jadwal', 'Jadwal dan bentrok', self::FAILED, 'Struktur jadwal mingguan belum tersedia.', '/web-admin/master/jadwal-mingguan');
        }

        $schedules = JadwalMingguan::forAcademicPeriod($period)->get();
        $offeringCount = PenawaranMataKuliah::forAcademicPeriod($period)->count();
        $scheduledOfferings = $schedules->pluck('penawaran_mata_kuliah_id')->filter()->unique()->count();
        $conflicts = $this->scheduleConflictCount($schedules);
        $missing = max(0, $offeringCount - $scheduledOfferings);
        $status = ($schedules->isEmpty() || $missing > 0 || $conflicts > 0) ? self::FAILED
            : ($schedules->whereNotNull('alasan_pengecualian')->isNotEmpty() ? self::WARNING : self::READY);

        return $this->result('jadwal', 'Jadwal dan bentrok', $status,
            "{$schedules->count()} jadwal; {$missing} penawaran belum dijadwalkan; {$conflicts} bentrok ditemukan.",
            '/web-admin/master/jadwal-mingguan');
    }

    private function billingCheck(TahunAkademik $period): array
    {
        if (! Schema::hasTable('template_tagihans')) {
            return $this->result('tagihan', 'Tagihan wajib', self::WARNING, 'Modul tagihan periode belum tersedia.', '/web-admin/finance/billing-period');
        }

        $templates = TemplateTagihan::forAcademicPeriod($period)->where('wajib_lunas_krs', true)->count();
        if ($templates === 0) {
            return $this->result('tagihan', 'Tagihan wajib', self::WARNING, 'Belum ada template tagihan wajib KRS; pastikan ini sesuai kebijakan.', '/web-admin/finance/billing-period');
        }

        $activeStudents = RegistrasiMahasiswa::forAcademicPeriod($period)->where('status_akademik', RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF)->pluck('mahasiswa_id');
        $issuedStudents = TagihanKuliah::forAcademicPeriod($period)->where('wajib_lunas_krs', true)->where('status', TagihanKuliah::STATUS_TERBIT)->pluck('target_mahasiswa_id')->unique();
        $missing = $activeStudents->diff($issuedStudents)->count();

        return $this->result('tagihan', 'Tagihan wajib', $missing > 0 ? self::FAILED : self::READY,
            "{$templates} template wajib; {$missing} mahasiswa aktif belum menerima tagihan wajib.",
            '/web-admin/finance/billing-period');
    }

    private function scheduleConflictCount(Collection $schedules): int
    {
        $conflicts = 0;
        foreach ($schedules->groupBy('hari') as $daily) {
            $items = $daily->values();
            for ($left = 0; $left < $items->count(); $left++) {
                for ($right = $left + 1; $right < $items->count(); $right++) {
                    $a = $items[$left];
                    $b = $items[$right];
                    $overlaps = $a->mulai < $b->selesai && $a->selesai > $b->mulai;
                    $sameResource = $a->dosen_id === $b->dosen_id || $a->kelas_id === $b->kelas_id || $a->ruang_id === $b->ruang_id;
                    $conflicts += $overlaps && $sameResource ? 1 : 0;
                }
            }
        }

        return $conflicts;
    }

    private function result(string $key, string $label, string $status, string $message, string $fixUrl): array
    {
        return compact('key', 'label', 'status', 'message') + ['group' => $key, 'fix_url' => $fixUrl];
    }
}
