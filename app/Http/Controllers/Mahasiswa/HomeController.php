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
use App\Models\Kelas;
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
use App\Services\Finance\ManualPaymentService;
use Auth;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use PDF;

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

    public function jadkulIndex(Request $request, AcademicPeriodContext $context, StudentAcademicContext $studentContext)
    {
        $period = $context->published();
        $student = Auth::guard('mahasiswa')->user();
        $academicClass = $studentContext->classFor($student, $period);
        $baseQuery = JadwalKuliah::query()
            ->forAcademicPeriod($period)
            ->when(
                Schema::hasColumn('jadwal_kuliahs', 'penawaran_mata_kuliah_id'),
                fn ($query) => $query->forApprovedStudent($student->id),
                fn ($query) => $query->when($academicClass, fn ($query) => $query->forStudentClass($academicClass->id))
            )
            ->when(! $academicClass, fn ($query) => $query->whereRaw('1 = 0'));
        $classIds = (clone $baseQuery)->distinct()->pluck('kelas_id');
        $lecturerIds = (clone $baseQuery)->distinct()->pluck('dosen_id');
        $roomIds = (clone $baseQuery)->distinct()->pluck('ruang_id');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kelas_id' => ['nullable', 'integer', Rule::in($classIds->all())],
            'dosen_id' => ['nullable', 'integer', Rule::in($lecturerIds->all())],
            'ruang_id' => ['nullable', 'integer', Rule::in($roomIds->all())],
            'meth_id' => ['nullable', 'integer', 'in:0,1'],
            'days_id' => ['nullable', 'integer', 'between:0,6'],
            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                Rule::when($request->filled('date_from'), ['after_or_equal:date_from']),
            ],
        ]);
        $data['kuri'] = Kurikulum::all();
        $data['taka'] = $period ? collect([$period]) : collect();
        $data['pstudi'] = ProgramStudi::all();
        $data['matkul'] = MataKuliah::query()->forAcademicPeriod($period)->get();
        $data['jadkul'] = (clone $baseQuery)
            ->with(['matkul', 'kelas', 'dosen', 'ruang.gedung'])
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('code', 'like', "%{$keyword}%")
                        ->orWhereHas('matkul', fn ($course) => $course
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('code', 'like', "%{$keyword}%"))
                        ->orWhereHas('kelas', fn ($class) => $class
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('code', 'like', "%{$keyword}%"))
                        ->orWhereHas('dosen', fn ($lecturer) => $lecturer->where('dsn_name', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['kelas_id'] ?? null, fn ($query, $classId) => $query->where('kelas_id', $classId))
            ->when($filters['dosen_id'] ?? null, fn ($query, $lecturerId) => $query->where('dosen_id', $lecturerId))
            ->when($filters['ruang_id'] ?? null, fn ($query, $roomId) => $query->where('ruang_id', $roomId))
            ->when(isset($filters['meth_id']) && $filters['meth_id'] !== null, fn ($query) => $query->where('meth_id', $filters['meth_id']))
            ->when(isset($filters['days_id']) && $filters['days_id'] !== null, fn ($query) => $query->where('days_id', $filters['days_id']))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('date', '<=', $date))
            ->orderBy('date')
            ->orderBy('start')
            ->get();
        $data['filters'] = $filters;
        $data['filterClasses'] = Kelas::query()->whereIn('id', $classIds)->orderBy('name')->get();
        $data['filterLecturers'] = Dosen::query()->whereIn('id', $lecturerIds)->orderBy('dsn_name')->get();
        $data['filterRooms'] = Ruang::query()->whereIn('id', $roomIds)->orderBy('name')->get();
        $data['selectedPeriod'] = $period;
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
        $data['payments'] = HistoryTagihan::query()->where('users_id', $user->id)->where('taka_id', $period?->id)->latest()->get();

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
        $data['payments'] = HistoryTagihan::query()->where('users_id', $user->id)->where('taka_id', $period?->id)->latest()->get();

        return view('mahasiswa.pages.mhs-tagihan-index', $data);

    }

    public function tagihanView($code, AcademicPeriodContext $context)
    {
        $user = Auth::guard('mahasiswa')->user();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['tagihan'] = TagihanKuliah::query()
            ->forAcademicPeriod($context->published())
            ->forStudent($user)
            ->where('status', TagihanKuliah::STATUS_TERBIT)
            ->where('code', $code)
            ->firstOrFail();
        $data['manualPayment'] = HistoryTagihan::query()
            ->where('tagihan_kuliah_id', $data['tagihan']->id)
            ->where('users_id', $user->id)
            ->latest()
            ->first();

        return view('mahasiswa.pages.mhs-tagihan-view', $data);
    }

    public function tagihanPayment(
        Request $request,
        $code,
        AcademicPeriodContext $context,
        ManualPaymentService $payments
    ) {
        $user = Auth::guard('mahasiswa')->user();
        $tagihan = TagihanKuliah::query()
            ->forAcademicPeriod($context->published())
            ->forStudent($user)
            ->where('status', TagihanKuliah::STATUS_TERBIT)
            ->where('code', $code)
            ->firstOrFail();
        $data = $request->validate([
            'tanggal_transfer' => ['required', 'date', 'before_or_equal:today'],
            'nama_pengirim' => ['required', 'string', 'max:255'],
            'bukti_pembayaran' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $payments->submit($tagihan, $user, $request->file('bukti_pembayaran'), $data);

        return redirect()->route('mahasiswa.home-tagihan-index')
            ->with('success', 'Konfirmasi pembayaran berhasil dikirim dan menunggu pemeriksaan petugas.');
    }

    public function paymentProof(HistoryTagihan $payment)
    {
        abort_unless((int) $payment->users_id === (int) Auth::guard('mahasiswa')->id(), 403);
        abort_unless($payment->bukti_path && Storage::disk('local')->exists($payment->bukti_path), 404);

        return Storage::disk('local')->download($payment->bukti_path);
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
            ->with(['tagihan', 'tagihanKuliah', 'users', 'ditinjauOleh'])
            ->firstOrFail();
        $billName = $data['history']->tagihanKuliah?->name ?? $data['history']->tagihan?->name ?? 'Tagihan';
        $safeBillName = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $billName), '-');

        return PDF::loadView('mahasiswa.pages.mhs-tagihan-invoice', $data)
            ->setPaper('a4')
            ->download('Invoice-Pembayaran-'.$safeBillName.'-'.$data['history']->tagihan_code.'.pdf');
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
