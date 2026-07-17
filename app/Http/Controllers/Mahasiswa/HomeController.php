<?php

namespace App\Http\Controllers\Mahasiswa;

use Alert;
use App\Http\Controllers\Controller;
// SECTION ADDONS SYSTEM
use App\Models\AbsensiMahasiswa;
use App\Models\Balance;
use App\Models\Dosen;
use App\Models\FeedBack\FBPerkuliahan;
use App\Models\HistoryTagihan;
use App\Models\JadwalKuliah;
use App\Models\Kurikulum;
// SECTION ADDONS EXTERNAL
use App\Models\MataKuliah;
use App\Models\Notification;
use App\Models\ProgramStudi;
// SECTION MODELS
use App\Models\Ruang;
use App\Models\Settings\webSettings;
use App\Models\TagihanKuliah;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Academic\AttendanceEligibilityService;
use App\Services\Academic\StudentAcademicContext;
use Auth;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use PDF;
use Str;

class HomeController extends Controller
{
    public function index(Request $request, AcademicPeriodContext $context, StudentAcademicContext $studentContext)
    {
        $user = Auth::guard('mahasiswa')->user();
        $period = $context->published();
        $academicClass = $studentContext->classFor($user, $period);
        $data['web'] = webSettings::where('id', 1)->first();
        $data['tagihan'] = TagihanKuliah::query()
            ->where(function ($query) use ($user, $academicClass): void {
                $query->where('users_id', $user->id);

                if ($academicClass) {
                    $query->orWhere('proku_id', $academicClass->proku_id)
                        ->orWhere('prodi_id', $academicClass->pstudi_id);
                }
            })
            ->sum('price');
        $data['history'] = HistoryTagihan::where('users_id', $user->id)->where('stat', 1)->whereHas('tagihan', function ($query) {
            $query->select('price');
        })->with('tagihan')->get()->sum(function ($history) {
            return $history->tagihan->price;
        });
        $data['sisatagihan'] = $data['tagihan'] - $data['history'];
        $data['jadkul'] = $academicClass
            ? JadwalKuliah::query()->forAcademicPeriod($period)->forStudentClass($academicClass->id)->count()
            : 0;
        $data['habsen'] = AbsensiMahasiswa::query()->forAcademicPeriod($period)->where('author_id', $user->id)->where('absen_type', 'H')->count();
        $data['notify'] = Notification::whereIn('send_to', [0, 3])->latest()->paginate(5);

        // dd($data['notif']);
        // dd($data['history']);

        return view('mahasiswa.home-index', $data);

    }

    public function profile(AcademicPeriodContext $context, StudentAcademicContext $studentContext)
    {

        $student = Auth::guard('mahasiswa')->user();
        $data['academicRegistration'] = $studentContext->profileRegistration($student, $context->published());
        $data['academicClass'] = $data['academicRegistration']?->kelas
            ?? $studentContext->classFor($student, $context->published());
        $data['web'] = webSettings::where('id', 1)->first();

        return view('mahasiswa.home-profile', $data);

    }

    public function jadkulIndex(AcademicPeriodContext $context, StudentAcademicContext $studentContext)
    {
        $period = $context->published();
        $student = Auth::guard('mahasiswa')->user();
        $academicClass = $studentContext->classFor($student, $period);
        $data['kuri'] = Kurikulum::all();
        $data['taka'] = $period ? collect([$period]) : collect();
        // $data['dosen'] = MataKuliah::where('dosen');
        $data['pstudi'] = ProgramStudi::all();
        $data['matkul'] = MataKuliah::query()->forAcademicPeriod($period)->get();
        $data['jadkul'] = JadwalKuliah::query()
            ->forAcademicPeriod($period)
            ->when(
                Schema::hasColumn('jadwal_kuliahs', 'penawaran_mata_kuliah_id'),
                fn ($query) => $query->forApprovedStudent($student->id),
                fn ($query) => $query->when($academicClass, fn ($query) => $query->forStudentClass($academicClass->id))
            )
            ->when(! $academicClass, fn ($query) => $query->whereRaw('1 = 0'))
            ->with(['matkul', 'kelas', 'dosen', 'ruang.gedung'])
            ->get();
        $data['ruang'] = Ruang::all();
        $data['kelas'] = $academicClass ? collect([$academicClass]) : collect();
        $data['web'] = webSettings::where('id', 1)->first();

        return view('mahasiswa.pages.mhs-jadkul-index', $data);
    }

    public function jadkulAbsen(string $code, AcademicPeriodContext $context, StudentAcademicContext $studentContext)
    {
        $student = Auth::guard('mahasiswa')->user();
        $academicClass = $studentContext->classFor($student, $context->published());
        $jadwal = $this->studentActiveSchedule($code, $academicClass?->id, $context);
        $date = \Carbon\Carbon::now()->format('Y-m-d');
        $checkAbsen = AbsensiMahasiswa::where('jadkul_code', $jadwal->code)->where('author_id', $student->id)->exists();

        if (! $checkAbsen) {
            if ($jadwal->date === $date) {
                $data['web'] = webSettings::where('id', 1)->first();
                $data['kuri'] = Kurikulum::all();
                $data['taka'] = collect([$context->published()]);
                // $data['dosen'] = MataKuliah::where('dosen');
                $data['pstudi'] = ProgramStudi::all();
                $data['matkul'] = collect([$jadwal->matkul]);
                $data['jadkul'] = $jadwal;
                $data['ruang'] = Ruang::all();
                $data['kelas'] = collect([$jadwal->kelas]);
                $data['academicClass'] = $academicClass;

                // dd($data['jadkul']);

                return view('mahasiswa.pages.mhs-jadkul-absen', $data);
            } else {

                Alert::error('Error', 'Kamu belum bisa absen pada saat ini.');

                return back();
            }
        } else {
            Alert::error('Error', 'Kamu sudah absen untuk matakuliah ini.');

            return back();
        }
    }

    public function jadkulAbsenStore(Request $request, AcademicPeriodContext $context, StudentAcademicContext $studentContext, AttendanceEligibilityService $attendanceEligibility)
    {
        $request->validate([
            'jadkul_code' => ['required', 'string'],
            'absen_proof' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:8192'],
            'absen_type' => ['required', 'in:H,I,S'],
        ]);

        $student = Auth::guard('mahasiswa')->user();
        $classId = $studentContext->classIdFor($student, $context->published());
        $jadwal = $this->studentActiveSchedule((string) $request->string('jadkul_code'), $classId, $context);
        $meeting = Schema::hasTable('pertemuan_kuliahs') ? $jadwal->pertemuanKuliah : null;
        $krsItem = null;
        if ($meeting && $jadwal->penawaran_mata_kuliah_id) {
            $krsItem = $attendanceEligibility->eligibleKrsItem($meeting, $student);
        }
        $now = now();

        if ($jadwal->date !== $now->toDateString() || $now->format('H:i:s') < $jadwal->start || $now->format('H:i:s') >= $jadwal->ended) {
            Alert::error('Error', 'Absensi hanya dapat dilakukan selama jadwal perkuliahan berlangsung.');

            return back();
        }

        if (AbsensiMahasiswa::where('jadkul_code', $jadwal->code)->where('author_id', $student->id)->exists()) {
            Alert::error('Error', 'Kamu sudah absen untuk mata kuliah ini.');

            return back();
        }

        $image = $request->file('absen_proof');
        $name = 'profile-'.$jadwal->code.'-'.$now->toDateString().'-'.$student->id.'-'.uniqid().'.png';
        $destinationPath = storage_path('app/public/images/profile/absen');
        File::ensureDirectoryExists($destinationPath);
        (new ImageManager(new Driver))->read($image->getRealPath())->scaleDown(height: 300)->toPng()->save($destinationPath.'/'.$name);

        AbsensiMahasiswa::create([
            ...(Schema::hasColumn('absensi_mahasiswas', 'pertemuan_kuliah_id') ? [
                'pertemuan_kuliah_id' => $meeting?->id,
                'krs_item_id' => $krsItem?->id,
            ] : []),
            'absen_proof' => 'profile/absen/'.$name,
            'author_id' => $student->id,
            'jadkul_code' => $jadwal->code,
            'absen_date' => $now->toDateString(),
            'absen_time' => $now->format('H:i:s'),
            'absen_type' => $request->input('absen_type'),
            'code' => uniqid(),
        ]);

        Alert::success('Success', 'Kamu telah berhasil melakukan absensi.');

        return redirect()->route('mahasiswa.home-jadkul-index');

    }

    public function saveImageProfile(Request $request)
    {
        $request->validate([
            'mhs_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8192',
        ]);

        $user = Auth::guard('mahasiswa')->user();

        if ($request->hasFile('mhs_image')) {
            $image = $request->file('mhs_image');
            $name = 'profile-'.$user->mhs_code.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/images/profile');
            $destinationPaths = storage_path('app/public/images');

            // Compress image
            $manager = new ImageManager(new Driver);
            $image = $manager->read($image->getRealPath());
            // $image->resize(width: 250);
            $image->scaleDown(height: 300);
            $image->toPng()->save($destinationPath.'/'.$name);

            if ($user->mhs_image != 'default/default-profile.jpg') {
                File::delete($destinationPaths.'/'.$user->mhs_image); // hapus gambar lama
            }
            $user->mhs_image = 'profile/'.$name;
            $user->save();

            Alert::success('Success', 'Data berhasil diupdate');

            return redirect()->route('mahasiswa.home-profile');
        }
    }

    public function saveDataProfile(Request $request)
    {

        $request->validate([
            'mhs_name' => 'required|string|max:255',
            'mhs_nim' => 'required|string|max:255|unique:users,user,'.Auth::guard('mahasiswa')->user()->id,
            'mhs_birthplace' => 'required|string|max:255', // New field
            'mhs_birthdate' => 'required|date', // New field
        ]);
        $user = Auth::guard('mahasiswa')->user();

        $user->mhs_name = $request->mhs_name;
        $user->mhs_nim = $request->mhs_nim;
        $user->mhs_reli = $request->mhs_reli;
        $user->mhs_gend = $request->mhs_gend;
        $user->mhs_birthplace = $request->mhs_birthplace; // New field
        $user->mhs_birthdate = $request->mhs_birthdate; // New field

        $user->save();

        Alert::success('Success', 'Data berhasil diupdate');

        return back();
    }

    public function saveDataKontak(Request $request)
    {

        $request->validate([
            'mhs_phone' => 'required|numeric|unique:users,phone,'.Auth::guard('mahasiswa')->user()->id,
            'mhs_mail' => 'required|email|max:255|unique:users,email,'.Auth::guard('mahasiswa')->user()->id,
            'mhs_parent_father' => 'nullable|string|max:255',
            'mhs_parent_mother' => 'nullable|string|max:255',
            'mhs_parent_father_phone' => 'nullable|string|max:14',
            'mhs_parent_mother_phone' => 'nullable|string|max:14',
            'mhs_parent_wali_name' => 'string|max:14',
            'mhs_parent_wali_phone' => 'string|max:14',
            'mhs_addr_domisili' => 'nullable|string|max:4192',
            'mhs_addr_kelurahan' => 'nullable|string|max:255',
            'mhs_addr_kecamatan' => 'nullable|string|max:255',
            'mhs_addr_kota' => 'nullable|string|max:255',
            'mhs_addr_provinsi' => 'nullable|string|max:255',
        ]);
        $user = Auth::guard('mahasiswa')->user();

        $user->mhs_phone = $request->mhs_phone;
        $user->mhs_mail = $request->mhs_mail;
        $user->mhs_parent_father = $request->mhs_parent_father;
        $user->mhs_parent_father_phone = $request->mhs_parent_father_phone;
        $user->mhs_parent_mother = $request->mhs_parent_mother;
        $user->mhs_parent_mother_phone = $request->mhs_parent_mother_phone;
        $user->mhs_wali_name = $request->mhs_wali_name;
        $user->mhs_wali_phone = $request->mhs_wali_phone;
        $user->mhs_addr_domisili = $request->mhs_addr_domisili;
        $user->mhs_addr_kelurahan = $request->mhs_addr_kelurahan;
        $user->mhs_addr_kecamatan = $request->mhs_addr_kecamatan;
        $user->mhs_addr_kota = $request->mhs_addr_kota;
        $user->mhs_addr_provinsi = $request->mhs_addr_provinsi;

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

        $user = Auth::guard('mahasiswa')->user();

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

    public function tagihanIndexAjax(AcademicPeriodContext $context)
    {
        $user = Auth::guard('mahasiswa')->user();
        $period = $context->published();

        $data['tagihan'] = TagihanKuliah::query()->forAcademicPeriod($period)->forStudent($user)->latest()->get();
        $data['history'] = HistoryTagihan::query()->where('users_id', $user->id)->where('taka_id', $period?->id)->where('stat', 1)->latest()->get();

        return response()->json($data);

    }

    public function tagihanIndex(AcademicPeriodContext $context)
    {
        $user = Auth::guard('mahasiswa')->user();
        $period = $context->published();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['period'] = $period;
        $data['tagihan'] = TagihanKuliah::query()->forAcademicPeriod($period)->forStudent($user)->latest()->get();
        $data['history'] = HistoryTagihan::query()->where('users_id', $user->id)->where('taka_id', $period?->id)->where('stat', 1)->latest()->get();

        return view('mahasiswa.pages.mhs-tagihan-index', $data);

    }

    public function tagihanView($code, AcademicPeriodContext $context)
    {
        // Mencari tagihan berdasarkan `users_id`
        $user = Auth::guard('mahasiswa')->user();
        $data['web'] = webSettings::where('id', 1)->first();
        $checkData = HistoryTagihan::where('tagihan_code', $code)->where('users_id', $user->id)->where('stat', 1)->first();
        if ($checkData !== null) {

            Alert::error('error', 'Kamu sudah membayar tagihan ini');

            return back();
        } else {
            $data['tagihan'] = TagihanKuliah::query()
                ->forAcademicPeriod($context->published())
                ->forStudent($user)
                ->where('status', TagihanKuliah::STATUS_TERBIT)
                ->where('code', $code)
                ->firstOrFail();

            return view('mahasiswa.pages.mhs-tagihan-view', $data);

        }
    }

    public function tagihanPayment(Request $request, $code, AcademicPeriodContext $context)
    {
        $user = Auth::guard('mahasiswa')->user();
        $tagihan = TagihanKuliah::query()
            ->forAcademicPeriod($context->published())
            ->forStudent($user)
            ->where('status', TagihanKuliah::STATUS_TERBIT)
            ->where('code', $code)
            ->firstOrFail();
        $request->validate(['note' => ['nullable', 'string', 'max:255']]);

        \Midtrans\Config::$serverKey = config('services.midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('services.midtrans.isProduction');
        \Midtrans\Config::$isSanitized = config('services.midtrans.isSanitized');
        \Midtrans\Config::$is3ds = config('services.midtrans.is3ds');

        DB::transaction(function () use ($request, $tagihan, $user) {
            $donation = \App\Models\HistoryTagihan::create([
                'users_id' => $user->id,
                'tagihan_code' => $tagihan->code,
                'tagihan_kuliah_id' => $tagihan->id,
                'taka_id' => $tagihan->taka_id,
                'nominal' => $tagihan->nominal,
                'status' => 'pending',
                'code' => Str::random(9),
                'desc' => $request->note ?: 'Pembayaran Tagihan Kuliah '.$tagihan->code,
            ]);

            $payload = [
                'transaction_details' => [
                    'order_id' => $donation->code,
                    'gross_amount' => $tagihan->nominal,
                ],
                'customer_details' => [
                    'first_name' => $user->mhs_name,
                    'email' => $user->mhs_mail,
                ],
                'item_details' => [
                    [
                        'id' => $tagihan->code,
                        'price' => $tagihan->nominal,
                        'quantity' => 1,
                        'name' => $tagihan->name,
                        'brand' => 'Tagihan Kuliah',
                        'category' => 'Tagihan Kuliah',
                        'merchant_name' => config('app.name'),
                    ],
                ],
            ];
            // dd($payload);

            $snapToken = \Midtrans\Snap::getSnapToken($payload);
            $donation->snap_token = $snapToken;
            $donation->save();

            $this->response['code_uniq'] = $donation->code;
            $this->response['snap_token'] = $snapToken;
        });

        return response()->json([
            'status' => 'success',
            'snap_token' => $this->response['snap_token'],
            'code_uniq' => $this->response['code_uniq'],
        ]);
    }

    public function tagihanSuccess(Request $request, $code)
    {
        $user = Auth::guard('mahasiswa')->user();
        $tagihan = HistoryTagihan::query()
            ->where('code', $code)
            ->where('users_id', $user->id)
            ->with('tagihan')
            ->firstOrFail();

        if ((int) $tagihan->stat === 1) {
            return redirect()->route('mahasiswa.home-tagihan-index');
        }

        \Midtrans\Config::$serverKey = config('services.midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('services.midtrans.isProduction');

        try {
            $transaction = \Midtrans\Transaction::status($tagihan->code);
        } catch (\Throwable) {
            Alert::error('Error', 'Status pembayaran belum dapat diverifikasi. Silakan coba kembali.');

            return redirect()->route('mahasiswa.home-tagihan-index');
        }

        $accepted = in_array($transaction->transaction_status ?? null, ['capture', 'settlement'], true)
            && (($transaction->transaction_status ?? null) !== 'capture' || ($transaction->fraud_status ?? 'accept') === 'accept');
        $expectedAmount = (int) ($tagihan->nominal ?? $tagihan->tagihan?->nominal ?? $tagihan->tagihan?->price);

        if (! $accepted || (int) ($transaction->gross_amount ?? 0) !== $expectedAmount) {
            Alert::error('Error', 'Pembayaran belum berhasil atau nominal transaksi tidak sesuai.');

            return redirect()->route('mahasiswa.home-tagihan-index');
        }

        DB::transaction(function () use ($tagihan, $expectedAmount, $user): void {
            $payment = HistoryTagihan::query()
                ->whereKey($tagihan->id)
                ->where('users_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $payment->stat === 1) {
                return;
            }

            $payment->update(['stat' => 1, 'status' => 'lunas', 'dibayar_at' => now(), 'nominal' => $expectedAmount]);

            Balance::create([
                'value' => $expectedAmount,
                'type' => 1,
                'desc' => 'Reff pembayaran mahasiswa #'.$payment->code,
                'code' => uniqid(),
            ]);
        });

        Alert::success('Success', 'Tagihan telah dibayar');

        return redirect()->route('mahasiswa.home-tagihan-index');

    }

    public function tagihanInvoice(Request $request, $code)
    {
        $data['history'] = HistoryTagihan::query()
            ->where('code', $code)
            ->where('users_id', Auth::guard('mahasiswa')->id())
            ->where(fn ($query) => $query->where('status', 'lunas')->orWhere('stat', 1))
            ->firstOrFail();

        // Load view into a variable

        // return view('mahasiswa.pages.mhs-tagihan-invoice', $data);
        $view = view('mahasiswa.pages.mhs-tagihan-invoice', $data);

        // Load the HTML content of the view
        $html = $view->render();

        // Load HTML content into DOMPDF
        $pdf = PDF::loadHtml($html)->setPaper('a4');

        // Save the PDF file to storage
        $pdf->save(storage_path('app/public/invoices/Invoice-Pembayaran-'.$data['history']->tagihan->name.'-'.$data['history']->tagihan_code.'.pdf'));

        // Or you can return the PDF to be downloaded
        return $pdf->download('Invoice-Pembayaran-'.$data['history']->tagihan->name.'-'.$data['history']->tagihan_code.'.pdf');
    }

    public function storeFBPerkuliahan(
        Request $request,
        string $code,
        AcademicPeriodContext $context,
        StudentAcademicContext $studentContext
    ) {
        $user = Auth::guard('mahasiswa')->user();
        $classId = $studentContext->classIdFor($user, $context->published());
        $jadwal = $this->studentActiveSchedule($code, $classId, $context);

        $checkData = FBPerkuliahan::where('fb_jakul_code', $jadwal->code)->where('fb_users_code', $user->mhs_code)->first();

        $request->validate([
            'fb_score' => 'required|in:Tidak Puas,Cukup Puas,Sangat Puas',
            'fb_reason' => 'required',
        ], [
            'fb_score.required' => 'Skor feedback harus diisi.',
            'fb_score.in' => 'Skor feedback harus salah satu dari: Tidak Puas, Cukup Puas, Sangat Puas.',
            'fb_reason.required' => 'Alasan feedback harus diisi.',
        ]);

        if ($checkData !== null) {
            Alert::error('Error', 'Kamu sudah memberikan FeedBack pada perkuliahan ini.');

            return back();
        } else {
            $fb = new FBPerkuliahan;

            $fb->fb_users_code = $user->mhs_code;
            $fb->fb_jakul_code = $jadwal->code;
            $fb->fb_code = uniqid(8);
            $fb->fb_score = $request->fb_score;
            $fb->fb_reason = $request->fb_reason;

            $fb->save();

            Alert::success('Sukses', 'Terima kasih telah memberi FeedBack ^_^');

            return back();

        }

    }

    private function studentActiveSchedule(string $code, ?int $classId, AcademicPeriodContext $context): JadwalKuliah
    {
        return JadwalKuliah::query()
            ->forAcademicPeriod($context->published())
            ->when(
                Schema::hasColumn('jadwal_kuliahs', 'penawaran_mata_kuliah_id'),
                fn ($query) => $query->forApprovedStudent(Auth::guard('mahasiswa')->id()),
                fn ($query) => $query->when($classId, fn ($query) => $query->forStudentClass($classId))
            )
            ->when(! $classId, fn ($query) => $query->whereRaw('1 = 0'))
            ->with(['matkul', 'kelas'])
            ->where('code', $code)
            ->firstOrFail();
    }
}
