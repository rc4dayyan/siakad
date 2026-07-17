<?php

namespace App\Http\Controllers\Admin\Pages\Finance;

use Alert;
use App\Helper\roleTrait;
// SECTION ADDONS SYSTEM
use App\Http\Controllers\Controller;
use App\Models\HistoryTagihan;
// SECTION ADDONS EXTERNAL
use App\Models\Mahasiswa;
use App\Models\ProgramKuliah;
// SECTION MODELS
use App\Models\ProgramStudi;
use App\Models\Settings\webSettings;
use App\Models\TagihanKuliah;
use App\Services\Academic\AcademicAuditService;
use Auth;
use Illuminate\Http\Request;
use Str;

class GenerateTagihanController extends Controller
{
    use roleTrait;

    public function index(Request $request)
    {
        $data['income'] = HistoryTagihan::where('stat', 1)->whereHas('tagihan', function ($query) {
            $query->select('price');
        })->with('tagihan')->get()->sum(function ($history) {
            return $history->tagihan->price;
        });
        $data['web'] = webSettings::where('id', 1)->first();
        $data['tagihan'] = TagihanKuliah::all();
        $data['history'] = HistoryTagihan::all();
        $data['mahasiswa'] = Mahasiswa::all();
        $data['prodi'] = ProgramStudi::all();
        $data['proku'] = ProgramKuliah::all();
        $data['prefix'] = $this->setPrefix();

        // dd($data);

        return view('user.finance.pages.tagihan-index', $data);
    }

    public function create(Request $request)
    {
        $data['income'] = HistoryTagihan::where('stat', 1)->whereHas('tagihan', function ($query) {
            $query->select('price');
        })->with('tagihan')->get()->sum(function ($history) {
            return $history->tagihan->price;
        });
        $data['tagihan'] = TagihanKuliah::latest()->paginate(3);
        $data['history'] = HistoryTagihan::all();
        $data['mahasiswa'] = Mahasiswa::all();
        $data['prodi'] = ProgramStudi::all();
        $data['proku'] = ProgramKuliah::all();
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();

        return view('user.finance.pages.tagihan-create', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'string|max:255',
            'price' => 'string|max:255',
            'prodi_id' => 'required_without_all:users_id,proku_id|nullable|integer|min:0',
            'proku_id' => 'required_without_all:users_id,prodi_id|nullable|integer|min:0',
            'users_id' => 'required_without_all:proku_id,prodi_id|nullable|integer|min:0',
        ]);

        // Menghitung jumlah nilai yang valid
        $count = count(array_filter([$request->prodi_id, $request->proku_id, $request->users_id], function ($value) {
            return $value > 0;
        }));

        // Validasi jika hanya satu nilai yang valid
        if ($count != 1) {
            Alert::error('error', 'Hanya boleh memilih salah satu.');

            return back()->withInput();
        }

        $tagihan = new TagihanKuliah;
        $tagihan->name = $request->name;
        $tagihan->price = $request->price;
        $tagihan->prodi_id = $request->prodi_id;
        $tagihan->proku_id = $request->proku_id;
        $tagihan->users_id = $request->users_id;
        $tagihan->author_id = Auth::user()->id;
        $tagihan->code = 'UKT-'.Str::random(8);

        $tagihan->save();

        Alert::success('success', 'Data berhasil ditambahkan');

        return back();
    }

    public function update(Request $request, $code)
    {
        $request->validate([
            'name' => 'string|max:255',
            'price' => 'string|max:255',
            'prodi_id' => 'required_without_all:users_id,proku_id|nullable|integer|min:0',
            'proku_id' => 'required_without_all:users_id,prodi_id|nullable|integer|min:0',
            'users_id' => 'required_without_all:proku_id,prodi_id|nullable|integer|min:0',
        ]);

        // Menghitung jumlah nilai yang valid
        $count = count(array_filter([$request->prodi_id, $request->proku_id, $request->users_id], function ($value) {
            return $value > 0;
        }));

        // Validasi jika hanya satu nilai yang valid
        if ($count != 1) {
            Alert::error('error', 'Hanya boleh memilih salah satu.');

            return back()->withInput();
        }

        $tagihan = TagihanKuliah::where('code', $code)->first();
        $tagihan->name = $request->name;
        $tagihan->price = $request->price;
        $tagihan->prodi_id = $request->prodi_id;
        $tagihan->proku_id = $request->proku_id;
        $tagihan->users_id = $request->users_id;
        $tagihan->author_id = Auth::user()->id;
        // $tagihan->code = 'UKT-'.Str::random(8);

        $tagihan->save();

        Alert::success('success', 'Data berhasil diupdate');

        return back();
    }

    public function destroy(Request $request, $code, AcademicAuditService $audit)
    {
        $tagihan = TagihanKuliah::where('code', $code)->firstOrFail();
        if ($tagihan->taka_id) {
            $before = ['status' => $tagihan->status];
            $tagihan->update(['status' => TagihanKuliah::STATUS_DIBATALKAN]);
            $audit->record('billing.cancelled', $tagihan, $tagihan->taka_id, $request->user(), $before,
                ['status' => TagihanKuliah::STATUS_DIBATALKAN]);
        } else {
            $tagihan->delete();
        }

        Alert::success('success', 'Data telah berhasil dihapus');

        return back();
    }
}
