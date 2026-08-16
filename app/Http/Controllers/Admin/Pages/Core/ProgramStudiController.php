<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
// SECTION ADDONS SYSTEM
use App\Http\Controllers\Controller;
// SECTION ADDONS EXTERNAL
use App\Models\Dosen;
use App\Models\Fakultas;
// SECTION MODELS
use App\Models\ProgramStudi;
use App\Models\Settings\webSettings;
use Illuminate\Http\Request;
use Str;

class ProgramStudiController extends Controller
{
    use roleTrait;

    public function index()
    {
        $data['web'] = webSettings::where('id', 1)->first();
        $data['prefix'] = $this->setPrefix();
        $data['fakultas'] = Fakultas::all();
        $data['pstudi'] = ProgramStudi::with(['fakultas', 'head'])->get();
        $data['dosen'] = Dosen::where('dsn_stat', 1)->get();

        return view('user.admin.master.admin-pstudi-index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'cnim' => 'required|string|max:99999|numeric',
            'head_id' => 'required|exists:dosens,id',
            'faku_id' => 'required|exists:fakultas,id',
            'title' => 'required',
            'level' => 'required',
        ]);

        $pstudi = new ProgramStudi;
        $pstudi->name = $request->name;
        $pstudi->code = $request->code;
        $pstudi->cnim = $request->cnim;
        $pstudi->slug = Str::slug($request->name);
        $pstudi->level = $request->level;
        $pstudi->title = $request->title;
        $pstudi->head_id = $request->head_id;
        $pstudi->faku_id = $request->faku_id;
        $pstudi->save();

        Alert::success('success', 'Data telah berhasil disimpan');

        return back();
    }

    public function update(Request $request, $code)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'cnim' => 'required|string|max:99999|numeric',
            'head_id' => 'required|exists:dosens,id',
            'faku_id' => 'required|exists:fakultas,id',
            'title' => 'required',
            'level' => 'required',
        ]);

        $pstudi = ProgramStudi::where('code', $code)->first();
        $pstudi->name = $request->name;
        $pstudi->code = $request->code;
        $pstudi->cnim = $request->cnim;
        $pstudi->slug = Str::slug($request->name);
        $pstudi->level = $request->level;
        $pstudi->title = $request->title;
        $pstudi->head_id = $request->head_id;
        $pstudi->faku_id = $request->faku_id;
        $pstudi->save();

        Alert::success('success', 'Data telah berhasil diupdate');

        return back();
    }

    public function destroy(Request $request, $code)
    {

        $pstudi = ProgramStudi::where('code', $code)->first();
        $pstudi->delete();

        Alert::success('success', 'Data telah berhasil dihapus');

        return back();
    }
}
