<?php

namespace App\Http\Controllers\Dosen;

use Alert;
use App\Http\Controllers\Controller;
use App\Models\FeedBack\FBPerkuliahan;
use App\Models\JadwalMingguan;
use App\Models\Krs;
use App\Models\Notification;
use App\Models\Settings\webSettings;
use App\Models\studentTask;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class HomeController extends Controller
{
    public function index(AcademicPeriodContext $context)
    {
        $lecturer = Auth::guard('dosen')->user();
        $period = $context->published();
        $scheduleQuery = JadwalMingguan::query()
            ->forAcademicPeriod($period)
            ->where('dosen_id', $lecturer->id);
        $feedbackQuery = FBPerkuliahan::query()
            ->whereHas('jadkul', fn ($query) => $query
                ->forAcademicPeriod($period)
                ->forLecturer($lecturer->id));
        $feedbackByScore = $feedbackQuery
            ->selectRaw('fb_score, COUNT(*) as total')
            ->groupBy('fb_score')
            ->pluck('total', 'fb_score');

        return view('dosen.home-index', [
            'web' => webSettings::where('id', 1)->first(),
            'lecturer' => $lecturer,
            'period' => $period,
            'scheduleCount' => (clone $scheduleQuery)->count(),
            'taskCount' => studentTask::query()
                ->forAcademicPeriod($period)
                ->forLecturer($lecturer->id)
                ->count(),
            'feedbackCount' => $feedbackByScore->sum(),
            'pendingKrsCount' => Krs::query()
                ->where('status', Krs::STATUS_SUBMITTED)
                ->whereHas('registrasiMahasiswa', fn ($query) => $query
                    ->where('dosen_wali_id', $lecturer->id)
                    ->when($period, fn ($query) => $query->where('taka_id', $period->id)))
                ->when(! $period, fn ($query) => $query->whereRaw('1 = 0'))
                ->count(),
            'feedbackChart' => [
                (int) $feedbackByScore->get('Tidak Puas', 0),
                (int) $feedbackByScore->get('Cukup Puas', 0),
                (int) $feedbackByScore->get('Sangat Puas', 0),
            ],
            'notify' => Notification::query()
                ->where(function ($query) use ($lecturer): void {
                    $query->where('lecture_id', $lecturer->id)
                        ->orWhere(function ($global): void {
                            $global->whereIn('send_to', [0, 2])->whereNull('lecture_id');
                        });
                })
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function profile()
    {

        $data['web'] = webSettings::where('id', 1)->first();

        return view('dosen.home-profile', $data);

    }

    public function saveImageProfile(Request $request)
    {
        $request->validate([
            'dsn_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8192',
        ]);

        $user = Auth::guard('dosen')->user();

        if ($request->hasFile('dsn_image')) {
            $image = $request->file('dsn_image');
            $name = 'profile-'.$user->dsn_code.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/images/profile/dosen');
            $destinationPaths = storage_path('app/public/images');

            // Compress image
            $manager = new ImageManager(new Driver);
            $image = $manager->read($image->getRealPath());
            // $image->resize(width: 250);
            $image->scaleDown(height: 300);
            $image->toPng()->save($destinationPath.'/'.$name);

            if ($user->dsn_image != 'default/default-profile.jpg') {
                File::delete($destinationPaths.'/'.$user->dsn_image); // hapus gambar lama
            }
            $user->dsn_image = 'profile/dosen/'.$name;
            $user->save();

            Alert::success('Success', 'Data berhasil diupdate');

            return redirect()->route('dosen.home-profile');
        }
    }

    public function saveDataProfile(Request $request)
    {

        $request->validate([
            'dsn_name' => 'required|string|max:255',
            'dsn_nidn' => 'required|string|max:255|unique:users,user,'.Auth::guard('dosen')->user()->id,
            'dsn_birthplace' => 'required|string|max:255', // New field
            'dsn_birthdate' => 'required|date', // New field
            'dsn_gend' => 'required|string|max:1', // New field
        ]);
        $user = Auth::guard('dosen')->user();

        $user->dsn_name = $request->dsn_name;
        $user->dsn_nidn = $request->dsn_nidn;
        $user->dsn_birthplace = $request->dsn_birthplace; // New field
        $user->dsn_birthdate = $request->dsn_birthdate; // New field
        $user->dsn_gend = $request->dsn_gend; // New field

        $user->save();

        Alert::success('Success', 'Data berhasil diupdate');

        return back();
    }

    public function saveDataKontak(Request $request)
    {

        $request->validate([
            'dsn_phone' => 'required|numeric|unique:users,phone,'.Auth::guard('dosen')->user()->id,
            'dsn_mail' => 'required|email|max:255|unique:users,email,'.Auth::guard('dosen')->user()->id,
        ]);
        $user = Auth::guard('dosen')->user();

        $user->dsn_phone = $request->dsn_phone;
        $user->dsn_mail = $request->dsn_mail;

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

        $user = Auth::guard('dosen')->user();

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
}
