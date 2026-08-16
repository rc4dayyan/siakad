<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
// SECTION ADDONS SYSTEM
// SECTION ADDONS EXTERNAL
use App\Http\Controllers\Controller;
use App\Models\Dosen;
// SECTION MODELS
use App\Models\Fakultas;
use App\Models\Settings\webSettings;
use Illuminate\Http\Request;

class FakultasController extends Controller
{
    use roleTrait;

    public function index()
    {
        $data['web'] = webSettings::where('id', 1)->first();
        $data['prefix'] = $this->setPrefix();
        $data['fakultas'] = Fakultas::with('head')->get();
        $data['dosen'] = Dosen::where('dsn_stat', 1)->get();

        return view('user.admin.master.admin-fakultas-index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:3',
            'head_id' => 'required|exists:dosens,id',
        ]);

        $fakultas = new Fakultas;
        $fakultas->name = $request->name;
        $fakultas->code = $request->code;
        $fakultas->head_id = $request->head_id;
        $fakultas->save();

        Alert::success('success', 'Data telah berhasil disimpan');

        return back();
    }

    public function update(Request $request, $code)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:3',
            'head_id' => 'required|exists:dosens,id',
        ]);

        $fakultas = Fakultas::where('code', $code)->first();
        $fakultas->name = $request->name;
        $fakultas->code = $request->code;
        $fakultas->head_id = $request->head_id;
        $fakultas->save();

        Alert::success('success', 'Data telah berhasil diupdate');

        return back();
    }

    public function destroy(Request $request, $code)
    {

        $fakultas = Fakultas::where('code', $code)->first();
        $fakultas->delete();

        Alert::success('success', 'Data telah berhasil dihapus');

        return back();
    }
}
