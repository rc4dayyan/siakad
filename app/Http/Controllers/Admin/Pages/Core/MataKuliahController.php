<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
// SECTION ADDONS SYSTEM
// SECTION ADDONS EXTERNAL
use App\Http\Controllers\Controller;
use App\Models\Dosen;
// SECTION MODELS
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MasterMataKuliah;
use App\Models\MataKuliah;
use App\Models\NilaiMahasiswa;
use App\Models\ProgramStudi;
use App\Models\Settings\webSettings;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert as FacadesAlert;

class MataKuliahController extends Controller
{
    use roleTrait;

    public function index()
    {
        $data['web'] = webSettings::where('id', 1)->first();
        $data['prefix'] = $this->setPrefix();
        $data['kuri'] = Kurikulum::all();
        $data['taka'] = TahunAkademik::all();
        $data['pstudi'] = ProgramStudi::all();
        $data['dosen'] = Dosen::all();
        $data['matkul'] = MataKuliah::all();
        $data['masterMatkul'] = $this->masterMataKuliahs();

        return view('user.admin.master.admin-matkul-index', $data);
    }

    public function create()
    {
        $data['web'] = webSettings::where('id', 1)->first();
        $data['prefix'] = $this->setPrefix();
        $data['kuri'] = Kurikulum::all();
        $data['taka'] = TahunAkademik::all();
        $data['pstudi'] = ProgramStudi::all();
        $data['matkul'] = MataKuliah::all();
        $data['dosen'] = Dosen::all();
        $data['masterMatkul'] = $this->masterMataKuliahs();

        return view('user.admin.master.admin-matkul-create', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mid' => 'required|integer|exists:master_mata_kuliahs,id',
            'code' => 'required|string|max:255',
            'bsks' => 'required|string|max:255',
            'desc' => 'required|string',
            'pstudi_id' => 'required',
            'kuri_id' => 'required',
            'taka_id' => 'required',
            'dosen_1' => 'required',
            'dosen_2' => 'nullable',
            'dosen_3' => 'nullable',
            'requ_id' => 'nullable',
        ]);

        $masterMatkul = MasterMataKuliah::findOrFail($request->mid);
        $matkul = new MataKuliah;
        $matkul->mid = $masterMatkul->id;
        $matkul->name = $masterMatkul->name;
        $matkul->code = $request->code;
        $matkul->bsks = $request->bsks;
        $matkul->desc = $request->desc;
        $matkul->pstudi_id = $request->pstudi_id;
        $matkul->kuri_id = $request->kuri_id;
        $matkul->taka_id = $request->taka_id;
        $matkul->requ_id = $request->requ_id;
        $matkul->dosen_1 = $request->dosen_1;
        $matkul->dosen_2 = $request->dosen_2;
        $matkul->dosen_3 = $request->dosen_3;
        $matkul->save();

        Alert::success('success', 'Data telah berhasil disimpan');

        return back();
    }

    public function update(Request $request, $code)
    {
        $request->validate([
            'mid' => 'required|integer|exists:master_mata_kuliahs,id',
            'code' => 'required|string|max:255',
            'bsks' => 'required|string|max:255',
            'desc' => 'required|string',
            'pstudi_id' => 'required',
            'kuri_id' => 'required',
            'taka_id' => 'required',
            'dosen_1' => 'required',
            'dosen_2' => 'nullable',
            'dosen_3' => 'nullable',
            'requ_id' => 'nullable',
        ]);

        $masterMatkul = MasterMataKuliah::findOrFail($request->mid);
        $matkul = MataKuliah::where('code', $code)->firstOrFail();
        $matkul->mid = $masterMatkul->id;
        $matkul->name = $masterMatkul->name;
        $matkul->code = $request->code;
        $matkul->bsks = $request->bsks;
        $matkul->desc = $request->desc;
        $matkul->pstudi_id = $request->pstudi_id;
        $matkul->kuri_id = $request->kuri_id;
        $matkul->taka_id = $request->taka_id;
        $matkul->requ_id = $request->requ_id;
        $matkul->dosen_1 = $request->dosen_1;
        $matkul->dosen_2 = $request->dosen_2;
        $matkul->dosen_3 = $request->dosen_3;
        $matkul->save();

        Alert::success('success', 'Data telah berhasil diupdate');

        return back();
    }

    private function masterMataKuliahs()
    {
        return MasterMataKuliah::query()
            ->orderBy('program_studi')
            ->orderBy('semester')
            ->orderBy('name')
            ->get();
    }

    public function destroy(Request $request, $code)
    {

        $matkul = MataKuliah::where('code', $code)->first();
        $matkul->delete();

        Alert::success('success', 'Data telah berhasil dihapus');

        return back();
    }

    public function nilai($mataKuliahId)
    {
        $data['web'] = webSettings::where('id', 1)->first();
        $data['prefix'] = $this->setPrefix();
        $data['mataKuliah'] = MataKuliah::findOrFail($mataKuliahId);
        $data['mahasiswas'] = Mahasiswa::where('class_id', $data['mataKuliah']->kelas_id)->get();   // atau berdasarkan kelas terkait
        $data['existingNilais'] = \App\Models\NilaiMahasiswa::where('mata_kuliah_id', $mataKuliahId)
            ->where('kelas_id', $mataKuliah->kelas_id ?? 1)
            ->get()
            ->keyBy('mahasiswa_id');

        return view('user.admin.master.admin-matkul-nilai', $data);
    }

    public function storenilai(Request $request)
    {
        $mataKuliahId = $request->mata_kuliah_id;
        $mataKuliah = MataKuliah::findOrFail($mataKuliahId);
        $dosenId = $mataKuliah->dosen_1;
        $kelasId = $mataKuliah->kelas_id ?? 1;

        foreach ($request->nilai as $data) {
            NilaiMahasiswa::updateOrCreate(
                [
                    'mahasiswa_id' => $data['mahasiswa_id'],
                    'mata_kuliah_id' => $mataKuliahId,
                    'kelas_id' => $kelasId,
                ],
                [
                    'dosen_id' => $dosenId,
                    'nilai' => $data['nilai'],
                ]
            );
        }

        $prefix = $this->setPrefix();

        FacadesAlert::success('success', 'Data telah berhasil disimpan untuk matakuliah '.$mataKuliah->name);

        return redirect()->route($prefix.'master.matkul-index');
    }
}
