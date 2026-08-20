<?php

namespace App\Http\Controllers\Admin;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\MasterMataKuliah;
use App\Models\ProgramStudi;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Rap2hpoutre\FastExcel\FastExcel;

class KrsListController extends Controller
{
    use roleTrait;

    private const STATUSES = [
        Krs::STATUS_DRAFT,
        Krs::STATUS_SUBMITTED,
        Krs::STATUS_APPROVED,
        Krs::STATUS_REJECTED,
        Krs::STATUS_LOCKED,
    ];

    private const GRADES = ['A', 'B', 'C', 'D', 'E', 'empty'];

    public function index(Request $request, AcademicPeriodContext $periods): View
    {
        $this->authorizeRole($request);
        $period = $periods->requireCurrent($request->user());
        $filters = $this->filters($request);
        $query = $this->filteredQuery($period->id, $filters);
        $items = (clone $query)->paginate(30)->withQueryString();

        return view('user.admin.krs-list-index', [
            'web' => webSettings::find(1),
            'prefix' => $this->setPrefix(),
            'period' => $period,
            'items' => $items,
            'filters' => $filters,
            'programs' => ProgramStudi::query()
                ->whereHas('penawarans', fn ($query) => $query->where('taka_id', $period->id))
                ->orderBy('name')
                ->get(),
            'classes' => Kelas::query()
                ->forAcademicPeriod($period)
                ->with('pstudi')
                ->orderBy('name')
                ->get(),
            'courses' => MasterMataKuliah::query()
                ->whereHas('penawarans', fn ($query) => $query->where('taka_id', $period->id))
                ->orderBy('name')
                ->get(),
            'statusLabels' => $this->statusLabels(),
            'summary' => [
                'rows' => (clone $query)->count(),
                'students' => (clone $query)->distinct()->count('mahasiswas.id'),
                'credits' => (int) (clone $query)->sum('krs_items.sks'),
            ],
        ]);
    }

    public function export(Request $request, AcademicPeriodContext $periods)
    {
        $this->authorizeRole($request);
        $period = $periods->requireCurrent($request->user());
        $query = $this->filteredQuery($period->id, $this->filters($request));
        $rows = (function () use ($query, $period) {
            foreach ($query->cursor() as $item) {
                yield [
                    'NIM' => (string) $item->nim,
                    'Nama' => $item->nama_mahasiswa,
                    'Semester' => (string) $period->code,
                    'Kode Mata Kuliah' => $item->kode_mata_kuliah,
                    'Nama Mata Kuliah' => $item->nama_mata_kuliah,
                    'Nama Kelas' => $item->nama_kelas,
                    'Kode Prodi' => $item->kode_prodi,
                    'Nama Prodi' => $item->nama_prodi,
                    'Nilai Huruf' => $item->nilai_huruf,
                    'Nilai Indeks' => $this->gradeIndex($item->nilai_huruf),
                    'Nilai Angka' => '',
                ];
            }
        })();

        return (new FastExcel($rows))->download(
            'list-krs-'.Str::slug((string) $period->code).'-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    /** @return array<string, string|int|null> */
    private function filters(Request $request): array
    {
        $status = $request->string('status')->value();
        $grade = strtoupper($request->string('nilai')->value());
        $grade = $grade === 'EMPTY' ? 'empty' : $grade;

        return [
            'q' => trim($request->string('q')->value()),
            'pstudi_id' => $request->integer('pstudi_id') ?: null,
            'kelas_id' => $request->integer('kelas_id') ?: null,
            'mata_kuliah_id' => $request->integer('mata_kuliah_id') ?: null,
            'status' => in_array($status, self::STATUSES, true) ? $status : '',
            'nilai' => in_array($grade, self::GRADES, true) ? $grade : '',
        ];
    }

    /** @param array<string, string|int|null> $filters */
    private function filteredQuery(int $periodId, array $filters): Builder
    {
        return DB::table('krs_items')
            ->join('krs', 'krs.id', '=', 'krs_items.krs_id')
            ->join('registrasi_mahasiswas', 'registrasi_mahasiswas.id', '=', 'krs.registrasi_mahasiswa_id')
            ->join('mahasiswas', 'mahasiswas.id', '=', 'registrasi_mahasiswas.mahasiswa_id')
            ->join('penawaran_mata_kuliahs', 'penawaran_mata_kuliahs.id', '=', 'krs_items.penawaran_mata_kuliah_id')
            ->join('master_mata_kuliahs', 'master_mata_kuliahs.id', '=', 'penawaran_mata_kuliahs.master_mata_kuliah_id')
            ->join('kelas', 'kelas.id', '=', 'penawaran_mata_kuliahs.kelas_id')
            ->join('program_studis', 'program_studis.id', '=', 'penawaran_mata_kuliahs.pstudi_id')
            ->leftJoin('nilai_mahasiswas', function ($join): void {
                $join->on('nilai_mahasiswas.mahasiswa_id', '=', 'mahasiswas.id')
                    ->on('nilai_mahasiswas.penawaran_mata_kuliah_id', '=', 'penawaran_mata_kuliahs.id');
            })
            ->where('registrasi_mahasiswas.taka_id', $periodId)
            ->where('penawaran_mata_kuliahs.taka_id', $periodId)
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $keyword = '%'.$filters['q'].'%';
                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('mahasiswas.mhs_nim', 'like', $keyword)
                        ->orWhere('mahasiswas.mhs_name', 'like', $keyword)
                        ->orWhere('penawaran_mata_kuliahs.code', 'like', $keyword)
                        ->orWhere('master_mata_kuliahs.code', 'like', $keyword)
                        ->orWhere('master_mata_kuliahs.name', 'like', $keyword);
                });
            })
            ->when($filters['pstudi_id'], fn (Builder $query, $id) => $query->where('program_studis.id', $id))
            ->when($filters['kelas_id'], fn (Builder $query, $id) => $query->where('kelas.id', $id))
            ->when($filters['mata_kuliah_id'], fn (Builder $query, $id) => $query->where('master_mata_kuliahs.id', $id))
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('krs.status', $filters['status']))
            ->when($filters['nilai'] === 'empty', fn (Builder $query) => $query->whereNull('nilai_mahasiswas.nilai'))
            ->when($filters['nilai'] !== '' && $filters['nilai'] !== 'empty', fn (Builder $query) => $query->where('nilai_mahasiswas.nilai', $filters['nilai']))
            ->select([
                'krs_items.id',
                'mahasiswas.mhs_nim as nim',
                'mahasiswas.mhs_name as nama_mahasiswa',
                'registrasi_mahasiswas.semester_mahasiswa',
                DB::raw('COALESCE(master_mata_kuliahs.code, penawaran_mata_kuliahs.code) as kode_mata_kuliah'),
                'master_mata_kuliahs.name as nama_mata_kuliah',
                'kelas.name as nama_kelas',
                'kelas.code as kode_kelas',
                'program_studis.code as kode_prodi',
                'program_studis.name as nama_prodi',
                'nilai_mahasiswas.nilai as nilai_huruf',
                'krs.status',
                'krs_items.sks',
            ])
            ->orderBy('mahasiswas.mhs_nim')
            ->orderBy('master_mata_kuliahs.name')
            ->orderBy('krs_items.id');
    }

    /** @return array<string, string> */
    private function statusLabels(): array
    {
        return [
            Krs::STATUS_DRAFT => 'Draft',
            Krs::STATUS_SUBMITTED => 'Diajukan',
            Krs::STATUS_APPROVED => 'Disetujui',
            Krs::STATUS_REJECTED => 'Ditolak',
            Krs::STATUS_LOCKED => 'Dikunci',
        ];
    }

    private function gradeIndex(?string $grade): string
    {
        return match ($grade) {
            'A' => '4.00',
            'B' => '3.00',
            'C' => '2.00',
            'D' => '1.00',
            'E' => '0.00',
            default => '',
        };
    }

    private function authorizeRole(Request $request): void
    {
        abort_unless(in_array((int) $request->user()->raw_type, [0, 3], true), 403);
    }
}
