<?php

namespace App\Http\Controllers\Admin;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
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

class DosenPengajarListController extends Controller
{
    private const ROLES = ['utama', 'pendamping_1', 'pendamping_2'];

    use roleTrait;

    public function index(Request $request, AcademicPeriodContext $periods): View
    {
        $this->authorizeRole($request);
        $period = $periods->requireCurrent($request->user());
        $filters = $this->filters($request);
        $query = $this->filteredQuery($period->id, $filters);
        $items = (clone $query)->paginate(30)->withQueryString();

        return view('user.admin.dosen-pengajar-list-index', [
            'web' => webSettings::find(1),
            'prefix' => $this->setPrefix(),
            'period' => $period,
            'items' => $items,
            'filters' => $filters,
            'lecturers' => Dosen::query()
                ->where(function ($query) use ($period): void {
                    $query->whereIn('id', DB::table('penawaran_mata_kuliahs')
                        ->where('taka_id', $period->id)
                        ->select('dosen_utama_id'))
                        ->orWhereIn('id', DB::table('penawaran_mata_kuliahs')
                            ->where('taka_id', $period->id)
                            ->whereNotNull('dosen_pendamping_1_id')
                            ->select('dosen_pendamping_1_id'))
                        ->orWhereIn('id', DB::table('penawaran_mata_kuliahs')
                            ->where('taka_id', $period->id)
                            ->whereNotNull('dosen_pendamping_2_id')
                            ->select('dosen_pendamping_2_id'));
                })
                ->orderBy('dsn_name')
                ->get(),
            'programs' => ProgramStudi::query()
                ->whereHas('penawarans', fn ($query) => $query->where('taka_id', $period->id))
                ->orderBy('name')
                ->get(),
            'classes' => Kelas::query()
                ->forAcademicPeriod($period)
                ->orderBy('name')
                ->get(),
            'courses' => MasterMataKuliah::query()
                ->whereHas('penawarans', fn ($query) => $query->where('taka_id', $period->id))
                ->orderBy('name')
                ->get(),
            'roleLabels' => $this->roleLabels(),
            'summary' => [
                'assignments' => (clone $query)->count(),
                'lecturers' => (clone $query)->distinct()->count('dosens.id'),
                'credits' => (int) (clone $query)->sum('penawaran_mata_kuliahs.sks'),
            ],
        ]);
    }

    public function export(Request $request, AcademicPeriodContext $periods)
    {
        $this->authorizeRole($request);
        $period = $periods->requireCurrent($request->user());
        $query = $this->filteredQuery($period->id, $this->filters($request));
        $rows = (function () use ($query, $period) {
            $hasRows = false;

            foreach ($query->cursor() as $item) {
                $hasRows = true;

                yield [
                    'Semester' => (string) $period->code,
                    'NIDN' => (string) $item->nidn,
                    'NUPTK' => '',
                    'Nama Dosen' => $item->nama_dosen,
                    'Kode Matakuliah' => $item->kode_mata_kuliah,
                    'Nama Matakuliah' => $item->nama_mata_kuliah,
                    'Nama Kelas' => $item->nama_kelas,
                    'Tatap Muka' => (int) $item->tatap_muka,
                    'Tatap Muka Realisasi' => (int) $item->tatap_muka_realisasi,
                    'Kode Prodi' => $item->kode_prodi,
                    'Nama Prodi' => $item->nama_prodi,
                    'Sks Ajar' => (int) $item->sks_ajar,
                    'Jenis Evaluasi' => 1,
                ];
            }

            if (! $hasRows) {
                yield [
                    'Semester' => (string) $period->code,
                    'NIDN' => '',
                    'NUPTK' => '',
                    'Nama Dosen' => '',
                    'Kode Matakuliah' => '',
                    'Nama Matakuliah' => '',
                    'Nama Kelas' => '',
                    'Tatap Muka' => '',
                    'Tatap Muka Realisasi' => '',
                    'Kode Prodi' => '',
                    'Nama Prodi' => '',
                    'Sks Ajar' => '',
                    'Jenis Evaluasi' => 1,
                ];
            }
        })();

        return (new FastExcel($rows))->download(
            'list-dosen-pengajar-'.Str::slug((string) $period->code).'-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    /** @return array<string, string|int|null> */
    private function filters(Request $request): array
    {
        $role = $request->string('peran')->value();

        return [
            'q' => trim($request->string('q')->value()),
            'dosen_id' => $request->integer('dosen_id') ?: null,
            'pstudi_id' => $request->integer('pstudi_id') ?: null,
            'kelas_id' => $request->integer('kelas_id') ?: null,
            'mata_kuliah_id' => $request->integer('mata_kuliah_id') ?: null,
            'peran' => in_array($role, self::ROLES, true) ? $role : '',
        ];
    }

    /** @param array<string, string|int|null> $filters */
    private function filteredQuery(int $periodId, array $filters): Builder
    {
        $assignments = DB::table('penawaran_mata_kuliahs')
            ->selectRaw("id as penawaran_id, dosen_utama_id as dosen_id, 'utama' as peran")
            ->where('taka_id', $periodId)
            ->unionAll(
                DB::table('penawaran_mata_kuliahs')
                    ->selectRaw("id as penawaran_id, dosen_pendamping_1_id as dosen_id, 'pendamping_1' as peran")
                    ->where('taka_id', $periodId)
                    ->whereNotNull('dosen_pendamping_1_id')
            )
            ->unionAll(
                DB::table('penawaran_mata_kuliahs')
                    ->selectRaw("id as penawaran_id, dosen_pendamping_2_id as dosen_id, 'pendamping_2' as peran")
                    ->where('taka_id', $periodId)
                    ->whereNotNull('dosen_pendamping_2_id')
            );

        $meetings = DB::table('jadwal_mingguans')
            ->join('pertemuan_kuliahs', 'pertemuan_kuliahs.jadwal_mingguan_id', '=', 'jadwal_mingguans.id')
            ->selectRaw('jadwal_mingguans.penawaran_mata_kuliah_id, pertemuan_kuliahs.dosen_id, COUNT(pertemuan_kuliahs.id) as tatap_muka')
            ->selectRaw("SUM(CASE WHEN pertemuan_kuliahs.status = 'selesai' THEN 1 ELSE 0 END) as tatap_muka_realisasi")
            ->groupBy('jadwal_mingguans.penawaran_mata_kuliah_id', 'pertemuan_kuliahs.dosen_id');

        return DB::query()
            ->fromSub($assignments, 'penugasan_dosen')
            ->join('penawaran_mata_kuliahs', 'penawaran_mata_kuliahs.id', '=', 'penugasan_dosen.penawaran_id')
            ->join('dosens', 'dosens.id', '=', 'penugasan_dosen.dosen_id')
            ->join('master_mata_kuliahs', 'master_mata_kuliahs.id', '=', 'penawaran_mata_kuliahs.master_mata_kuliah_id')
            ->join('kelas', 'kelas.id', '=', 'penawaran_mata_kuliahs.kelas_id')
            ->join('program_studis', 'program_studis.id', '=', 'penawaran_mata_kuliahs.pstudi_id')
            ->leftJoinSub($meetings, 'rekap_pertemuan', function ($join): void {
                $join->on('rekap_pertemuan.penawaran_mata_kuliah_id', '=', 'penawaran_mata_kuliahs.id')
                    ->on('rekap_pertemuan.dosen_id', '=', 'dosens.id');
            })
            ->where('penawaran_mata_kuliahs.taka_id', $periodId)
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $keyword = '%'.$filters['q'].'%';
                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('dosens.dsn_nidn', 'like', $keyword)
                        ->orWhere('dosens.dsn_name', 'like', $keyword)
                        ->orWhere('penawaran_mata_kuliahs.code', 'like', $keyword)
                        ->orWhere('master_mata_kuliahs.code', 'like', $keyword)
                        ->orWhere('master_mata_kuliahs.name', 'like', $keyword);
                });
            })
            ->when($filters['dosen_id'], fn (Builder $query, $id) => $query->where('dosens.id', $id))
            ->when($filters['pstudi_id'], fn (Builder $query, $id) => $query->where('program_studis.id', $id))
            ->when($filters['kelas_id'], fn (Builder $query, $id) => $query->where('kelas.id', $id))
            ->when($filters['mata_kuliah_id'], fn (Builder $query, $id) => $query->where('master_mata_kuliahs.id', $id))
            ->when($filters['peran'] !== '', fn (Builder $query) => $query->where('penugasan_dosen.peran', $filters['peran']))
            ->select([
                'penawaran_mata_kuliahs.id as penawaran_id',
                'dosens.id as dosen_id',
                'dosens.dsn_nidn as nidn',
                'dosens.dsn_name as nama_dosen',
                'penugasan_dosen.peran',
                DB::raw('COALESCE(master_mata_kuliahs.code, penawaran_mata_kuliahs.code) as kode_mata_kuliah'),
                'master_mata_kuliahs.name as nama_mata_kuliah',
                'kelas.name as nama_kelas',
                'kelas.code as kode_kelas',
                'program_studis.code as kode_prodi',
                'program_studis.name as nama_prodi',
                'penawaran_mata_kuliahs.sks as sks_ajar',
                DB::raw('COALESCE(rekap_pertemuan.tatap_muka, 0) as tatap_muka'),
                DB::raw('COALESCE(rekap_pertemuan.tatap_muka_realisasi, 0) as tatap_muka_realisasi'),
            ])
            ->orderBy('dosens.dsn_name')
            ->orderBy('master_mata_kuliahs.name')
            ->orderBy('kelas.name');
    }

    /** @return array<string, string> */
    private function roleLabels(): array
    {
        return [
            'utama' => 'Dosen Utama',
            'pendamping_1' => 'Pendamping 1',
            'pendamping_2' => 'Pendamping 2',
        ];
    }

    private function authorizeRole(Request $request): void
    {
        abort_unless(in_array((int) $request->user()->raw_type, [0, 3], true), 403);
    }
}
