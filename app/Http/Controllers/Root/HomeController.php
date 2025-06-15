<?php

namespace App\Http\Controllers\Root;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// SECTION ADDONS SYSTEM
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use Auth;
use Hash;
use Str;
// SECTION ADDONS EXTERNAL
use Alert;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
// SECTION MODELS
use App\Models\Fakultas;
use App\Models\newsPost;
use App\Models\newsCategory;
use App\Models\KotakSaran;
use App\Models\ProgramStudi;
use App\Models\Settings\webSettings;
use App\Models\Notification;
use App\Models\GalleryAlbum;
use App\Models\docsResource;
use App\Models\ProgramKuliah;

class HomeController extends Controller
{
    private function setPrefix()
    {
        if(Auth::user()){

            $rawType = Auth::user()->raw_type;
            switch ($rawType) {
                case 1:
                    return 'finance.';
                case 2:
                    return 'officer.';
                case 3:
                    return 'academic.';
                case 4:
                    return 'admin.';
                case 5:
                    return 'support.';
                default:
                    return 'web-admin.';
            }
        }
    }


    public function index()
    {
        $data['fakultas'] = Fakultas::all();
        $data['proku'] = ProgramKuliah::all();
        $data['album'] = GalleryAlbum::where('isPublish', 1)->latest()->paginate(3);
        $data['web'] = webSettings::where('id', 1)->first();
        $data['posts'] = newsPost::latest()->paginate(7);
        $data['notify'] = Notification::whereIn('send_to', [0,3])->get();
        $data['prefix'] = $this->setPrefix();
        $data['title'] = " - Siakad";
        $data['menu'] = "Halaman Utama";
        return view('root.root-index', $data);
    }

    public function galleryIndex()
    {
        $data['fakultas'] = Fakultas::all();
        $data['proku'] = ProgramKuliah::all();
        $data['notify'] = Notification::whereIn('send_to', [0,3])->get();
        $data['web'] = webSettings::where('id', 1)->first();
        // $data['album'] = GalleryAlbum::where('slug', $slug)->first();
        $data['albums'] = GalleryAlbum::latest()->paginate(24);
        $data['prefix'] = $this->setPrefix();
        $data['title'] = " - Siakad";
        $data['menu'] = "Daftar Album Foto ";
        return view('root.pages.gallery-index', $data);
    }
    public function gallerySearch(Request $request)
    {
        $data['fakultas'] = Fakultas::all();
        $data['proku'] = ProgramKuliah::all();
        $data['notify'] = Notification::whereIn('send_to', [0,3])->get();
        $data['web'] = webSettings::where('id', 1)->first();
        // $data['album'] = GalleryAlbum::where('slug', $slug)->first();
        $search = $request->input('search');
        $albums = GalleryAlbum::where('name', 'like', "%$search%")->paginate(24);
        $data['prefix'] = $this->setPrefix();
        $data['title'] = " - Siakad";
        $data['menu'] = "Daftar Album Foto ";
        return view('root.pages.gallery-index', compact('albums'), $data);
    }
    public function galleryShow($slug)
    {
        $data['fakultas'] = Fakultas::all();
        $data['proku'] = ProgramKuliah::all();
        $data['notify'] = Notification::whereIn('send_to', [0,3])->get();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['album'] = GalleryAlbum::where('slug', $slug)->first();
        $data['albums'] = GalleryAlbum::latest()->paginate(7);
        $data['prefix'] = $this->setPrefix();
        $data['title'] = " - Siakad";
        $data['menu'] = "Lihat Album " . $data['album']->name;
        return view('root.pages.gallery-view', $data);
    }

    public function postView($slug)
    {
        $data['fakultas'] = Fakultas::all();
        $data['proku'] = ProgramKuliah::all();
        $data['notify'] = Notification::whereIn('send_to', [0,3])->get();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['post'] = newsPost::where('slug', $slug)->first();
        $data['posts'] = newsPost::latest()->paginate(7);
        $data['prefix'] = $this->setPrefix();
        $data['title'] = " - Siakad";
        $data['menu'] = "Lihat Postingan " . $data['post']->name;
        return view('root.pages.news-view', $data);
    }


    public function downloadIndex()
    {
        $data['fakultas'] = Fakultas::all();
        $data['proku'] = ProgramKuliah::all();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['title'] = " - Siakad";
        $data['menu'] = "Download";
        $data['prefix'] = $this->setPrefix();
        $data['docs'] = docsResource::orderBy('created_at', 'desc')->get();

        return view('root.pages.document-index', $data);
    }
    public function adviceIndex()
    {
        $data['fakultas'] = Fakultas::all();
        $data['proku'] = ProgramKuliah::all();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['title'] = " - Siakad";
        $data['menu'] = "Kotak Saran";
        $data['prefix'] = $this->setPrefix();
        return view('root.pages.advice-index', $data);
    }
    public function prodiIndex($slug)
    {
        $data['fakultas'] = Fakultas::all();
        $data['proku'] = ProgramKuliah::all();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['pstudi'] = ProgramStudi::where('slug', $slug)->first();
        $data['title'] = " - Siakad";
        $data['menu'] = "Program Studi ". $data['pstudi']->name;
        $data['prefix'] = $this->setPrefix();
        return view('root.pages.prodi-index', $data);
    }
    public function prokuIndex($code)
    {
        $data['fakultas'] = Fakultas::all();
        $data['proku'] = ProgramKuliah::all();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['pstudi'] = ProgramKuliah::where('code', $code)->first();
        $data['title'] = " - Siakad";
        $data['menu'] = "Program Kuliah ". $data['pstudi']->name;
        $data['prefix'] = $this->setPrefix();

        return view('root.pages.prodi-index', $data);
    }
    public function adviceStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'desc' => 'required',
        ]);

        $saran = new KotakSaran;
        $saran->name = $request->name;
        $saran->email = $request->email;
        $saran->subject = $request->subject;
        $saran->desc = $request->desc;
        if($saran->save()){
            Mail::send('base.resource.mail-kotak-saran-admin', ['saran' => $saran], function($message) use ($saran) {
                $message->to([
                    'koacime@gmail.com',
                    'daeytea@gmail.com'
                ]);
                $message->subject('[ SARAN ] - Siakad - ' . $saran->subject);
                $message->from('admin@staipuimajalengka.ac.id', config('app.name'));
            });

            Alert::success('Sukses', 'Terima kasih telah mengirimkan Saran ^_^');
            return back();
        } else {
            Alert::error('Error', 'Email tidak berhasil dikirim.');
            return back();
        }

    }

    public function downloadMahasiswaExample()
    {
        $path = public_path('files/file_import/siakad-import-mahasiswa-contoh.xlsx');
        
        if (!file_exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }

    public function downloadMatakuliahExample()
    {
        $path = public_path('files/file_import/siakad-import-matakuliah-contoh.xlsx');

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }
}
