<?php

namespace Tests\Unit;

use App\Models\HasilStudi;
use App\Models\HistoryTagihan;
use App\Models\KalenderAkademik;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\NilaiMahasiswa;
use App\Models\PenawaranMataKuliah;
use App\Models\PenerbitanTagihanBatch;
use App\Models\PeriodeAkademik;
use App\Models\ProgramKuliah;
use App\Models\RegistrasiMahasiswa;
use App\Models\TagihanKuliah;
use App\Models\TahunAkademik;
use App\Models\TahunAkademikInduk;
use App\Models\TemplateTagihan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class PeriodeAkademikTerminologyTest extends TestCase
{
    public function test_periode_akademik_uses_the_legacy_table(): void
    {
        $this->assertSame('tahun_akademiks', (new PeriodeAkademik)->getTable());
        $this->assertInstanceOf(PeriodeAkademik::class, new TahunAkademik);
        $this->assertInstanceOf(PeriodeAkademik::class, PeriodeAkademik::factory()->make());
    }

    public function test_period_owned_models_expose_the_domain_relation_through_taka_id(): void
    {
        $models = [
            new TemplateTagihan,
            new PenerbitanTagihanBatch,
            new ProgramKuliah,
            new Mahasiswa,
            new KalenderAkademik,
            new HistoryTagihan,
            new NilaiMahasiswa,
            new MataKuliah,
            new Kelas,
            new PenawaranMataKuliah,
            new TagihanKuliah,
            new RegistrasiMahasiswa,
            new HasilStudi,
        ];

        foreach ($models as $model) {
            $relation = $model->periodeAkademik();

            $this->assertInstanceOf(BelongsTo::class, $relation);
            $this->assertInstanceOf(PeriodeAkademik::class, $relation->getRelated());
            $this->assertSame('taka_id', $relation->getForeignKeyName());
        }
    }

    public function test_academic_period_scopes_accept_the_canonical_model(): void
    {
        $period = new PeriodeAkademik;
        $period->setAttribute('id', 42);

        $query = Kelas::query()->forAcademicPeriod($period)->getQuery();

        $this->assertSame([42], $query->getBindings());
    }

    public function test_period_uses_tid_for_its_academic_year_parent(): void
    {
        $relation = (new PeriodeAkademik)->tahunAkademik();

        $this->assertInstanceOf(TahunAkademikInduk::class, $relation->getRelated());
        $this->assertSame('tid', $relation->getForeignKeyName());
    }
}
