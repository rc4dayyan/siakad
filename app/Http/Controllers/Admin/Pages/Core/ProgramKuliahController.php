<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
// SECTION ADDONS SYSTEM
// SECTION ADDONS EXTERNAL
use App\Http\Controllers\Controller;
use App\Models\ProgramKuliah;
// SECTION MODELS
use App\Models\ProgramStudi;
use App\Models\Settings\webSettings;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;

class ProgramKuliahController extends Controller
{
    use roleTrait;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'taka_id' => ['nullable', 'integer', 'exists:tahun_akademiks,id'],
            'pstudi_id' => ['nullable', 'integer', 'exists:program_studis,id'],
            'wave' => ['nullable', 'string', 'max:255'],
        ]);

        $data['web'] = webSettings::where('id', 1)->first();
        $data['prefix'] = $this->setPrefix();
        $data['taka'] = TahunAkademik::query()->latest('year_start')->latest('id')->get();
        $data['pstudi'] = ProgramStudi::query()->orderBy('name')->get();
        $data['waves'] = ProgramKuliah::query()
            ->whereNotNull('wave')
            ->where('wave', '!=', '')
            ->distinct()
            ->orderBy('wave')
            ->pluck('wave');
        $data['proku'] = ProgramKuliah::query()
            ->when($filters['taka_id'] ?? null, fn ($query, $periodId) => $query->where('taka_id', $periodId))
            ->when($filters['pstudi_id'] ?? null, fn ($query, $programId) => $query->where('pstudi_id', $programId))
            ->when($filters['wave'] ?? null, fn ($query, $wave) => $query->where('wave', $wave))
            ->with(['taka', 'pstudi'])
            ->orderByDesc('taka_id')
            ->orderBy('name')
            ->get();
        $data['filters'] = $filters;

        return view('user.admin.master.admin-proku-index', $data);
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'wave' => 'required|string|max:255',
            'wave_start' => 'required|date',
            'wave_ended' => 'required|date',
            'taka_id' => 'required',
            'pstudi_id' => 'required',
        ]);

        $pstudi = new ProgramKuliah;
        $pstudi->name = $request->name;
        $pstudi->code = $request->code;
        $pstudi->wave = $request->wave;
        $pstudi->wave_start = $request->wave_start;
        $pstudi->wave_ended = $request->wave_ended;
        $pstudi->taka_id = $request->taka_id;
        $pstudi->pstudi_id = $request->pstudi_id;
        $pstudi->save();

        Alert::success('success', 'Data telah berhasil disimpan');

        return back();
    }

    public function update(Request $request, $code)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'wave' => 'required|string|max:255',
            'wave_start' => 'required|date',
            'wave_ended' => 'required|date',
            'taka_id' => 'required',
            'pstudi_id' => 'required',
        ]);

        $pstudi = ProgramKuliah::where('code', $code)->first();
        $pstudi->name = $request->name;
        $pstudi->code = $request->code;
        $pstudi->wave = $request->wave;
        $pstudi->wave_start = $request->wave_start;
        $pstudi->wave_ended = $request->wave_ended;
        $pstudi->taka_id = $request->taka_id;
        $pstudi->pstudi_id = $request->pstudi_id;
        $pstudi->save();

        Alert::success('success', 'Data telah berhasil diupdate');

        return back();
    }

    public function destroy(Request $request, $code)
    {

        $pstudi = ProgramKuliah::where('code', $code)->first();
        $pstudi->delete();

        Alert::success('success', 'Data telah berhasil dihapus');

        return back();
    }
}
