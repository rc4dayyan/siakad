<?php

namespace App\Http\Controllers\Admin;

use Alert;
use App\Helper\roleTrait;
// UNTUK PLUGIN TAMBAHAN
use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\Dosen;
use App\Models\HistoryTagihan;
use App\Models\Mahasiswa;
use App\Models\PenerbitanTagihanBatch;
use App\Models\Settings\webSettings;
use App\Models\TagihanKuliah;
use App\Models\TemplateTagihan;
use App\Models\uAttendance;
use App\Models\User;
use App\Services\Academic\AcademicDashboardService;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Finance\FinancialReportService;
use Auth;
use Carbon\Carbon;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Str;

class HomeController extends Controller
{
    use roleTrait;

    public function index(AcademicPeriodContext $periodContext, FinancialReportService $financialReports)
    {

        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['balIncome'] = 0;
        $data['balExpense'] = 0;
        $data['balPending'] = 0;
        $data['balSekarang'] = 0;

        if (in_array((int) auth()->user()->raw_type, [0, 1], true)) {
            $data['balIncome'] = Balance::where('type', 1)->sum('value');
            $data['balExpense'] = Balance::where('type', 2)->sum('value');
            $data['balPending'] = Balance::where('type', 0)->sum('value');
            $data['balSekarang'] = $data['balIncome'] - $data['balExpense'];
        }

        if ((int) auth()->user()->raw_type === 1) {
            $period = $periodContext->current(auth()->user());
            $summary = [
                'jumlah_tagihan' => 0,
                'total_tagihan' => 0,
                'jumlah_pembayaran' => 0,
                'total_pembayaran' => 0,
                'total_tunggakan' => 0,
            ];

            if ($period) {
                $summary = $financialReports->forPeriod($period)['ringkasan'];
            }

            $data['financeDashboard'] = [
                'period' => $period,
                'summary' => $summary,
                'pendingPayments' => $period
                    ? HistoryTagihan::query()->where('taka_id', $period->id)->where('status', HistoryTagihan::STATUS_PENDING)->count()
                    : 0,
                'templates' => $period ? TemplateTagihan::forAcademicPeriod($period)->count() : 0,
                'batches' => $period
                    ? PenerbitanTagihanBatch::query()->where('taka_id', $period->id)->latest()->limit(4)->with('template')->get()
                    : collect(),
                'recentBills' => $period
                    ? TagihanKuliah::forAcademicPeriod($period)->with('targetMahasiswa')->latest()->limit(5)->get()
                    : collect(),
            ];
        }

        if ((int) auth()->user()->raw_type === 0) {
            $academic = app(AcademicDashboardService::class)->forUser(auth()->user());
            $period = $academic['period'];
            $financialSummary = [
                'jumlah_tagihan' => 0,
                'total_tagihan' => 0,
                'jumlah_pembayaran' => 0,
                'total_pembayaran' => 0,
                'total_tunggakan' => 0,
            ];

            if ($period) {
                $financialSummary = $financialReports->forPeriod($period)['ringkasan'];
            }

            $data['webAdminDashboard'] = [
                'academic' => $academic,
                'financial' => $financialSummary,
                'students' => Mahasiswa::query()->count(),
                'lecturers' => Dosen::query()->count(),
                'activeLecturers' => Dosen::query()->where('dsn_stat', 1)->count(),
                'staff' => User::query()->whereIn('type', [1, 2, 3, 4, 5])->count(),
                'activeStaff' => User::query()->whereIn('type', [1, 2, 3, 4, 5])->where('status', 1)->count(),
                'pendingPayments' => $period
                    ? HistoryTagihan::query()->where('taka_id', $period->id)->where('status', HistoryTagihan::STATUS_PENDING)->count()
                    : 0,
            ];
        }

        if ((int) auth()->user()->raw_type === 3) {
            $data['academicDashboard'] = app(AcademicDashboardService::class)
                ->forUser(auth()->user());
        }

        return view('user.home-index', $data);
    }

    // KHUSUS PROFILE AREA
    public function profile()
    {

        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();

        return view('user.home-profile', $data);
    }

    public function getMhsGender()
    {

        $maleCount = Mahasiswa::where('mhs_gend', 'L')->count();
        $femaleCount = Mahasiswa::where('mhs_gend', 'P')->count();
        $dmaleCount = Dosen::where('dsn_gend', 'L')->count();
        $dfemaleCount = Dosen::where('dsn_gend', 'P')->count();
        $umaleCount = User::where('gend', 'L')->count();
        $ufemaleCount = User::where('gend', 'P')->count();

        return response()->json([
            'male' => $maleCount,
            'dmale' => $dmaleCount,
            'umale' => $umaleCount,
            'female' => $femaleCount,
            'dfemale' => $dfemaleCount,
            'ufemale' => $ufemaleCount,
        ]);
    }

    public function saveImageProfile(Request $request)
    {
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8196',
        ]);

        $user = Auth::user();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = 'profile-'.$user->code.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/images/profile');
            $destinationPaths = storage_path('app/public/images');

            // Compress image
            $manager = new ImageManager(new Driver);
            $image = $manager->read($image->getRealPath());
            // $image->resize(width: 250);
            $image->scaleDown(height: 300);
            $image->toPng()->save($destinationPath.'/'.$name);

            if ($user->image != 'default/default-profile.jpg') {
                File::delete($destinationPaths.'/'.$user->image); // hapus gambar lama
            }
            $user->image = 'profile/'.$name;
            $user->save();

            // dd($user->image);

            Alert::success('Success', 'Data berhasil diupdate');

            return back();
        }

    }

    public function saveDataProfile(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'user' => 'required|string|max:255|unique:users,user,'.Auth::user()->id,
            'birth_place' => 'required|string|max:255', // New field
            'birth_date' => 'required|date', // New field
            'gend' => 'required|string|max:1', // New field
        ]);
        $user = Auth::user();

        $user->name = $request->name;
        $user->user = $request->user;
        $user->birth_place = $request->birth_place; // New field
        $user->birth_date = $request->birth_date; // New
        $user->gend = $request->gend; // New field

        $user->update();

        // dd($user);

        Alert::success('Success', 'Data berhasil diupdate');

        return back();
    }

    public function saveDataKontak(Request $request)
    {

        $request->validate([
            'phone' => 'required|numeric|unique:users,phone,'.Auth::user()->id,
            'email' => 'required|email|max:255|unique:users,email,'.Auth::user()->id,
        ]);
        $user = Auth::user();

        $user->phone = $request->phone;
        $user->email = $request->email;

        $user->save();

        Alert::success('Success', 'Data berhasil diupdate');

        return back();
    }

    public function saveDataPassword(Request $request)
    {
        // Validate the request...
        $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'same:new_password_confirmed'],
        ]);

        $user = Auth::user();

        // Check if the old password is correct
        if (! Hash::check($request->old_password, $user->password)) {
            Alert::error('Error', 'Password lama yang diberikan tidak cocok dengan catatan kami.');

            return back();
        }

        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();

        Alert::success('Success', 'Password berhasil diubah!');

        return back();
    }

    // KHUSUS PRESENSI AREA
    public function presensi(Request $request)
    {

        $user = Auth::user();
        $data['prefix'] = $this->setPrefix();

        $data['hadir'] = uAttendance::where('absen_user_id', $user->id)->whereIn('absen_type', [0, 1, 4, 5])->get();
        $data['izin'] = uAttendance::where('absen_user_id', $user->id)->whereIn('absen_type', [2, 3, 6, 7])->get();
        $data['sakit'] = uAttendance::where('absen_user_id', $user->id)->whereIn('absen_type', [2])->get();
        // Filter data untuk terlambat (waktu masuk lebih dari jam 8 pagi)
        $data['terlambat'] = uAttendance::where('absen_user_id', $user->id)
            ->whereIn('absen_type', [0, 1, 5])
            ->whereTime('absen_time_in', '>', '08:00:00')
            ->get();
        $data['web'] = webSettings::where('id', 1)->first();

        // dd($data['prefix']);
        return view('user.home-presensi', $data);
    }

    public function presensiGet(Request $request)
    {
        $user = Auth::user();
        $selectedDate = $request->input('absen_date'); // Ambil tanggal yang dipilih dari permintaan
        // Gunakan tanggal yang dipilih untuk mengambil data presensi
        $data = uAttendance::where('absen_user_id', $user->id)
            ->whereDate('absen_date', $selectedDate)
            ->first(); // Menggunakan first() karena Anda mengharapkan satu hasil

        if ($data) {
            return response()->json(['data' => $data]); // Kirimkan data jika tersedia
        } else {
            return response()->json(['error' => 'Data not available'], 404); // Kirimkan respons error jika tidak ada data
        }
    }

    public function presensiHadir(Request $request)
    {

        $user = Auth::user();
        $data['prefix'] = $this->setPrefix();

        $data['absen'] = uAttendance::where('absen_user_id', $user->id)->whereIn('absen_type', [0, 1, 4, 5])->get();
        $data['hadir'] = uAttendance::where('absen_user_id', $user->id)->whereIn('absen_type', [0, 1, 4, 5])->get();
        $data['izin'] = uAttendance::where('absen_user_id', $user->id)->whereIn('absen_type', [2, 3, 6, 7])->get();
        $data['sakit'] = uAttendance::where('absen_user_id', $user->id)->whereIn('absen_type', [2])->get();
        // Filter data untuk terlambat (waktu masuk lebih dari jam 8 pagi)
        $data['terlambat'] = uAttendance::where('absen_user_id', $user->id)
            ->whereIn('absen_type', [0, 1, 5])
            ->whereTime('absen_time_in', '>', '08:00:00')
            ->get();

        // dd($data['prefix']);

        return view('user.home-presensi-view', $data);
    }

    public function saveAbsen(Request $request)
    {
        $request->validate([
            'absen_type' => 'required|integer',
            'absen_date' => 'required|date',
            'absen_time_in' => 'required',
            'absen_time_out' => 'nullable',
            'absen_proof' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8196',
        ]);

        $user = Auth::user();
        $absenDate = Carbon::parse($request->absen_date)->toDateString();

        // Periksa apakah sudah ada presensi untuk tanggal yang sama
        $existingAbsen = uAttendance::where('absen_user_id', $user->id)
            ->whereDate('absen_date', $absenDate)
            ->where('absen_type', $request->absen_type)
            ->first();

        if ($existingAbsen) {
            Alert::error('Error', 'Kamu sudah absen pada tanggal ini.');

            return back();
        }

        $absen = new uAttendance;
        $absen->absen_user_id = $user->id;
        $absen->absen_type = $request->absen_type;
        $absen->absen_date = $request->absen_date;
        $absen->absen_code = Str::random(7);
        $absen->absen_time_in = $request->absen_time_in;
        $absen->absen_time_out = $request->absen_time_out;

        if ($request->hasFile('absen_proof')) {
            $image = $request->file('absen_proof');
            $name = 'presensi-'.$user->code.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/images/presensi');

            // Membuat direktori jika belum ada
            if (! File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }

            // Mengompres gambar dan menyimpannya
            $manager = new ImageManager(new Driver);
            $image = $manager->read($image->getRealPath());

            $image->scaleDown(height: 300)->save($destinationPath.'/'.$name);

            // Menyimpan nama file gambar ke database
            $absen->absen_proof = 'presensi/'.$name;
        }

        $absen->save();

        Alert::success('Success', 'Data berhasil disimpan');

        return back();
    }

    public function saveIzinCuti(Request $request)
    {
        $request->validate([
            'absen_type' => 'required|integer',
            'absen_date' => 'required|date',
            'absen_time_out' => 'nullable',
            'absen_desc' => 'required',
            'absen_proof' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8196',
        ]);

        $user = Auth::user();
        $absenDate = Carbon::parse($request->absen_date)->toDateString();

        // Periksa apakah sudah ada presensi untuk tanggal yang sama
        $existingAbsen = uAttendance::where('absen_user_id', $user->id)
            ->whereDate('absen_date', $absenDate)
            ->where('absen_type', $request->absen_type)
            ->first();

        if ($existingAbsen) {
            Alert::error('Error', 'Kamu sudah absen pada tanggal ini.');

            return back();
        }

        $absen = new uAttendance;
        $absen->absen_user_id = $user->id;
        $absen->absen_type = $request->absen_type;
        $absen->absen_date = $request->absen_date;
        $absen->absen_code = Str::random(7);
        $absen->absen_time_in = Carbon::now()->format('H:i');
        $absen->absen_approve = 1;
        $absen->absen_desc = $request->absen_desc;

        if ($request->hasFile('absen_proof')) {
            $image = $request->file('absen_proof');
            $name = 'presensi-'.$user->code.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/images/presensi');

            // Membuat direktori jika belum ada
            if (! File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }

            // Mengompres gambar dan menyimpannya
            $manager = new ImageManager(new Driver);
            $image = $manager->read($image->getRealPath());

            $image->scaleDown(height: 300)->save($destinationPath.'/'.$name);

            // Menyimpan nama file gambar ke database
            $absen->absen_proof = 'presensi/'.$name;
        }

        $absen->save();

        Alert::success('Success', 'Data berhasil disimpan');

        return back();
    }
}
