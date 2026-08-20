<?php

namespace App\Http\Controllers\Admin\Pages;

use Alert;
use App\Helper\roleTrait;
// SECTION ADDONS SYSTEM
use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
// SECTION ADDONS EXTERNAL
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\RegistrasiMahasiswa;
use App\Models\Settings\webSettings;
// SECTION MODELS
use App\Models\User;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Imports\DosenOpenFeederImportService;
use App\Services\Imports\MahasiswaOpenFeederImportService;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Rap2hpoutre\FastExcel\FastExcel;
use Str;

class WorkersController extends Controller
{
    use roleTrait;

    // KHUSUS KELOLA DATA ROLE ADMIN
    public function indexAdmin()
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['admin'] = User::where('type', 0)->get();

        return view('user.admin.pages.workers-admin-index', $data);

    }

    public function createAdmin()
    {
        $data['admin'] = User::where('type', 0)->get();
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();

        return view('user.admin.pages.workers-admin-create', $data);

    }

    public function editAdmin(Request $request, $code)
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['admin'] = User::where('type', 0)->where('code', $code)->first();

        return view('user.admin.pages.workers-admin-edit', $data);

    }

    public function storeAdmin(Request $request)
    {
        $user = new User;

        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8196',
            'name' => 'required|string|max:255',
            'user' => 'required|string|max:255|unique:users,user,'.$user->id,
            'birth_place' => 'required|string|max:255', // New field
            'birth_date' => 'required|date', // New field
            'phone' => 'required|numeric|unique:users,phone,'.$user->id,
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'gend' => 'nullable|string',
            'status' => 'nullable|string',
            'password' => 'nullable|string',
            'password_confirm' => 'nullable|string|same:password',
        ]);

        $user->name = $request->name;
        $user->user = $request->user;
        $user->status = $request->status;
        $user->birth_place = $request->birth_place; // New field
        $user->birth_date = $request->birth_date; // New field
        $user->gend = $request->gend;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->status = $request->status;
        $user->reli = $request->reli;
        $user->contact_name_1 = $request->contact_name_1;
        $user->contact_name_2 = $request->contact_name_2;
        $user->contact_phone_1 = $request->contact_phone_1;
        $user->contact_phone_2 = $request->contact_phone_2;
        $user->type = $request->type;
        $user->code = Str::random(6);

        $user->password = Hash::make($request->password);
        $user->save();
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = 'profile-'.$user->code.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/images/profile');
            $destinationPaths = storage_path('app/public/images');

            // Compress image
            $manager = new ImageManager(new Driver);
            $image = $manager->read($image->getRealPath());
            $image->scaleDown(height: 300);
            $image->toPng()->save($destinationPath.'/'.$name);

            if ($user->image != 'default/default-profile.jpg') {
                File::delete($destinationPaths.'/'.$user->image); // hapus gambar lama
            }
            $user->image = 'profile/'.$name;
            $user->save();

            Alert::success('Success', 'Data berhasil ditambahkan');

            return back();
        }
        Alert::success('Success', 'Data berhasil ditambahkan');

        return back();
    }

    public function updateAdmin(Request $request, $code)
    {
        $user = User::where('type', 0)->where('code', $code)->first();

        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8196',
            'name' => 'required|string|max:255',
            'user' => 'required|string|max:255|unique:users,user,'.$user->id,
            // 'birth_place' => 'string|max:255', // New field
            // 'birth_date' => 'date', // New field
            'phone' => 'required|numeric|unique:users,phone,'.$user->id,
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'gend' => 'nullable|string',
            'status' => 'nullable|string',
            'password' => 'nullable|string',
            'password_confirm' => 'nullable|string|same:password',
        ]);

        $user->name = $request->name;
        $user->user = $request->user;
        $user->status = $request->status;
        $user->birth_place = $request->birth_place; // New field
        $user->birth_date = $request->birth_date; // New field
        $user->gend = $request->gend;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->status = $request->status;
        $user->reli = $request->reli;
        $user->contact_name_1 = $request->contact_name_1;
        $user->contact_name_2 = $request->contact_name_2;
        $user->contact_phone_1 = $request->contact_phone_1;
        $user->contact_phone_2 = $request->contact_phone_2;
        $user->type = $request->type;

        $user->password = Hash::make($request->password);
        $user->save();
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = 'profile-'.$user->code.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/images/profile');
            $destinationPaths = storage_path('app/public/images');

            // Compress image
            $manager = new ImageManager(new Driver);
            $image = $manager->read($image->getRealPath());
            $image->scaleDown(height: 300);
            $image->toPng()->save($destinationPath.'/'.$name);

            if ($user->image != 'default/default-profile.jpg') {
                File::delete($destinationPaths.'/'.$user->image); // hapus gambar lama
            }
            $user->image = 'profile/'.$name;
            $user->save();

            Alert::success('Success', 'Data berhasil diupdate');

            return back();
        }
        Alert::success('Success', 'Data berhasil diupdate');

        return back();
    }

    public function destroyAdmin(Request $request, $code)
    {
        $destinationPaths = storage_path('app/public/images');

        $admin = User::where('code', $code)->first();
        if ($admin->image != 'default/default-profile.jpg') {
            File::delete($destinationPaths.'/'.$admin->image); // hapus gambar lama
        }

        $admin->delete();
        Alert::success('Success', 'Pengguna berhasil dihapus.');

        return back();
    }

    // KHUSUS KELOLA DATA ROLE WORKER
    public function indexWorkers()
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['admin'] = User::whereIn('type', [1, 2, 3, 4, 5])->get();
        // dd($data['admin']->count());

        return view('user.admin.pages.workers-staff-index', $data);

    }

    public function createWorkers()
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['admin'] = User::whereIn('type', [1, 2, 3, 4, 5])->get();

        return view('user.admin.pages.workers-staff-create', $data);

    }

    public function editWorkers(Request $request, $code)
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['admin'] = User::whereIn('type', [1, 2, 3, 4, 5])->where('code', $code)->first();

        return view('user.admin.pages.workers-staff-edit', $data);

    }

    public function storeWorkers(Request $request)
    {
        $user = new User;

        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8196',
            'name' => 'required|string|max:255',
            'user' => 'required|string|max:255|unique:users,user,'.$user->id,
            'birth_place' => 'required|string|max:255', // New field
            'birth_date' => 'required|date', // New field
            'phone' => 'required|numeric|unique:users,phone,'.$user->id,
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'gend' => 'nullable|string',
            'status' => 'nullable|string',
            'password' => 'nullable|string',
            'password_confirm' => 'nullable|string|same:password',
        ]);

        $user->name = $request->name;
        $user->user = $request->user;
        $user->status = $request->status;
        $user->birth_place = $request->birth_place; // New field
        $user->birth_date = $request->birth_date; // New field
        $user->gend = $request->gend;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->status = $request->status;
        $user->reli = $request->reli;
        $user->contact_name_1 = $request->contact_name_1;
        $user->contact_name_2 = $request->contact_name_2;
        $user->contact_phone_1 = $request->contact_phone_1;
        $user->contact_phone_2 = $request->contact_phone_2;
        $user->type = $request->type;
        $user->code = Str::random(6);

        $user->password = Hash::make($request->password);
        $user->save();
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = 'profile-'.$user->code.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/images/profile');
            $destinationPaths = storage_path('app/public/images');

            // Compress image
            $manager = new ImageManager(new Driver);
            $image = $manager->read($image->getRealPath());
            $image->scaleDown(height: 300);
            $image->toPng()->save($destinationPath.'/'.$name);

            if ($user->image != 'default/default-profile.jpg') {
                File::delete($destinationPaths.'/'.$user->image); // hapus gambar lama
            }
            $user->image = 'profile/'.$name;
            $user->save();

            Alert::success('Success', 'Data berhasil ditambahkan');

            return back();
        }
        Alert::success('Success', 'Data berhasil ditambahkan');

        return back();
    }

    public function updateWorkers(Request $request, $code)
    {
        $user = User::whereIn('type', [1, 2, 3, 4, 5])->where('code', $code)->first();

        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8196',
            'name' => 'required|string|max:255',
            'user' => 'required|string|max:255|unique:users,user,'.$user->id,
            // 'birth_place' => 'string|max:255', // New field
            // 'birth_date' => 'date', // New field
            'phone' => 'required|numeric|unique:users,phone,'.$user->id,
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'gend' => 'nullable|string',
            'status' => 'nullable|string',
            'password' => 'nullable|string',
            'password_confirm' => 'nullable|string|same:password',
        ]);

        $user->name = $request->name;
        $user->user = $request->user;
        $user->status = $request->status;
        $user->birth_place = $request->birth_place; // New field
        $user->birth_date = $request->birth_date; // New field
        $user->gend = $request->gend;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->status = $request->status;
        $user->reli = $request->reli;
        $user->contact_name_1 = $request->contact_name_1;
        $user->contact_name_2 = $request->contact_name_2;
        $user->contact_phone_1 = $request->contact_phone_1;
        $user->contact_phone_2 = $request->contact_phone_2;
        $user->type = $request->type;

        $user->password = Hash::make($request->password);
        $user->save();
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = 'profile-'.$user->code.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/images/profile');
            $destinationPaths = storage_path('app/public/images');

            // Compress image
            $manager = new ImageManager(new Driver);
            $image = $manager->read($image->getRealPath());
            $image->scaleDown(height: 300);
            $image->toPng()->save($destinationPath.'/'.$name);

            if ($user->image != 'default/default-profile.jpg') {
                File::delete($destinationPaths.'/'.$user->image); // hapus gambar lama
            }
            $user->image = 'profile/'.$name;
            $user->save();

            Alert::success('Success', 'Data berhasil diupdate');

            return back();
        }
        Alert::success('Success', 'Data berhasil diupdate');

        return back();
    }

    public function destroyWorkers(Request $request, $code)
    {
        $destinationPaths = storage_path('app/public/images');

        $admin = User::where('code', $code)->first();
        if ($admin->image != 'default/default-profile.jpg') {
            File::delete($destinationPaths.'/'.$admin->image); // hapus gambar lama
        }

        $admin->delete();
        Alert::success('Success', 'Pengguna berhasil dihapus.');

        return back();
    }

    // KHUSUS KELOLA DATA ROLE DOSEN
    public function indexLecture()
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['dosen'] = Dosen::all();

        return view('user.admin.pages.workers-lecture-index', $data);

    }

    public function createLecture()
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['dosen'] = Dosen::all();

        return view('user.admin.pages.workers-lecture-create', $data);

    }

    public function editLecture(Request $request, $code)
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['dosen'] = Dosen::where('dsn_code', $code)->first();

        return view('user.admin.pages.workers-lecture-edit', $data);

    }

    public function storeLecture(Request $request)
    {
        $user = new Dosen;

        $request->validate([
            'dsn_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8196',
            'dsn_name' => 'required|string|max:255',
            'dsn_user' => 'required|string|max:255|unique:users,user,'.$user->id,
            'dsn_birthplace' => 'required|string|max:255', // New field
            'dsn_birthdate' => 'required|date', // New field
            'dsn_phone' => 'required|numeric|unique:users,phone,'.$user->id,
            'dsn_mail' => 'required|email|max:255|unique:users,email,'.$user->id,
            'dsn_gend' => 'nullable|string',
            'dsn_stat' => 'nullable|string',
            'password' => 'nullable|string',
            'password_confirm' => 'nullable|string|same:password',
        ]);

        $user->dsn_name = $request->dsn_name;
        $user->dsn_user = $request->dsn_user;
        $user->dsn_nidn = $request->dsn_nidn;
        $user->dsn_stat = $request->dsn_stat;
        $user->dsn_birthplace = $request->dsn_birthplace; // New field
        $user->dsn_birthdate = $request->dsn_birthdate; // New field
        $user->dsn_gend = $request->dsn_gend;
        $user->dsn_phone = $request->dsn_phone;
        $user->dsn_mail = $request->dsn_mail;
        // $user->type = $request->type;
        $user->dsn_code = Str::random(6);

        $user->password = Hash::make($request->password);
        $user->save();
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

            // dd($user->image);

            Alert::success('Success', 'Data berhasil ditambahkan');

            return back();
        }
        Alert::success('Success', 'Data berhasil ditambahkan');

        return back();
    }

    public function importLecture(Request $request, DosenOpenFeederImportService $importer)
    {
        $request->validate([
            'import' => ['required', 'file', 'mimes:xlsx,csv', 'max:2048'],
        ], [
            'import.required' => 'File dosen wajib diunggah.',
            'import.file' => 'Berkas import dosen tidak valid.',
            'import.mimes' => 'File harus berformat XLSX atau CSV.',
            'import.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(Storage::disk('local')->path($path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Gunakan file XLSX OpenFeeder atau CSV yang valid.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $result = $importer->import($rows);
        $message = "Import selesai: {$result['imported']} dosen baru dibuat";
        if ($result['skipped'] > 0) {
            $message .= " dan {$result['skipped']} dosen dilewati karena NIDN sudah terdaftar";
        }
        $message .= '. Username dan password awal dosen baru adalah NIDN.';

        Alert::success('Sukses', $message);

        return back()->with('success', $message);
    }

    public function updateLecture(Request $request, $code)
    {
        $user = Dosen::where('dsn_code', $code)->first();

        $request->validate([
            'dsn_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8196',
            'dsn_name' => 'required|string|max:255',
            'dsn_user' => 'required|string|max:255|unique:users,user,'.$user->id,
            // 'dsn_birthplace' => 'string|max:255', // New field
            // 'dsn_birthdate' => 'date', // New field
            'dsn_phone' => 'required|numeric|unique:users,phone,'.$user->id,
            'dsn_mail' => 'required|email|max:255|unique:users,email,'.$user->id,
            'dsn_gend' => 'nullable|string',
            'dsn_stat' => 'nullable|string',
            'password' => 'nullable|string',
            'password_confirm' => 'nullable|string|same:password',
        ]);

        $user->dsn_name = $request->dsn_name;
        $user->dsn_user = $request->dsn_user;
        $user->dsn_stat = $request->dsn_stat;
        $user->dsn_birthplace = $request->dsn_birthplace; // New field
        $user->dsn_birthdate = $request->dsn_birthdate; // New field
        $user->dsn_gend = $request->dsn_gend;
        $user->dsn_phone = $request->dsn_phone;
        $user->dsn_mail = $request->dsn_mail;
        // $user->type = $request->type;
        // $user->code = Str::random(6);

        $user->password = Hash::make($request->password);
        $user->save();
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

            // dd($user->image);

            Alert::success('Success', 'Data berhasil diupdate');

            return back();
        }
        Alert::success('Success', 'Data berhasil diupdate');

        return back();
    }

    public function destroyLecture(Request $request, $code)
    {
        $destinationPaths = storage_path('app/public/images');

        $dosen = Dosen::where('dsn_code', $code)->first();
        if ($dosen->image != 'default/default-profile.jpg') {
            File::delete($destinationPaths.'/'.$dosen->image); // hapus gambar lama
        }

        $dosen->delete();
        Alert::success('Success', 'Pengguna berhasil dihapus.');

        return back();
    }

    // KHUSUS KELOLA DATA ROLE MAHASISWA
    public function importStudent(
        Request $request,
        AcademicPeriodContext $periodContext,
        MahasiswaOpenFeederImportService $importer
    ) {
        $period = $periodContext->requireWritableCurrent($request->user());

        $request->validate([
            'import' => [
                'required',
                'file',
                'extensions:xlsx,csv',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/x-zip-compressed,text/csv,text/plain,application/csv',
                'max:5120',
            ],
            'class_id' => ['required', 'integer', 'exists:kelas,id'],
            'dry_run' => ['nullable', 'boolean'],
        ], [
            'import.required' => 'File mahasiswa OpenFeeder wajib diunggah.',
            'import.file' => 'Berkas import mahasiswa tidak valid.',
            'import.extensions' => 'Ekstensi file harus XLSX atau CSV.',
            'import.mimetypes' => 'Tipe file harus berupa dokumen XLSX atau CSV.',
            'import.max' => 'Ukuran file maksimal 5 MB.',
            'class_id.required' => 'Kelas tujuan wajib dipilih.',
        ]);

        $class = Kelas::query()
            ->forAcademicPeriod($period)
            ->with(['pstudi', 'taka', 'dosen'])
            ->find($request->integer('class_id'));

        if (! $class) {
            throw ValidationException::withMessages([
                'class_id' => 'Kelas harus berasal dari periode akademik yang sedang dipilih.',
            ]);
        }

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(Storage::disk('local')->path($path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Gunakan file XLSX OpenFeeder atau CSV yang valid.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $result = $importer->import($rows, $class, $request->boolean('dry_run'));
        $message = $request->boolean('dry_run')
            ? "Dry-run berhasil: {$result['imported']} mahasiswa baru valid dan {$result['skipped']} NIM sudah terdaftar. Tidak ada data disimpan."
            : "Import selesai: {$result['imported']} mahasiswa baru dibuat dan {$result['skipped']} NIM yang sudah terdaftar dilewati. Username dan password awal mahasiswa baru adalah NIM.";

        Alert::success('Sukses', $message);

        return back()->with('success', $message);
    }

    public function indexStudent(Request $request, AcademicPeriodContext $periodContext)
    {
        $filters = $request->validate([
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'prodi_id' => ['nullable', 'integer', 'exists:program_studis,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
        ]);

        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['academicPeriod'] = $periodContext->current(auth()->user());
        $studentAngkatan = Mahasiswa::query()
            ->with('registrasiAwal.taka')
            ->get(['id', 'years_id'])
            ->mapWithKeys(fn (Mahasiswa $student) => [
                $student->id => $student->registrasiAwal?->taka?->year_start
                    ?: ((int) $student->years_id ?: null),
            ]);

        $data['student'] = Mahasiswa::query()
            ->when($filters['angkatan'] ?? null, fn ($query, $angkatan) => $query
                ->whereKey($studentAngkatan->filter(fn ($tahun) => $tahun === (int) $angkatan)->keys()))
            ->when($filters['kelas_id'] ?? null, function ($query, $kelasId) use ($data): void {
                if ($data['academicPeriod']) {
                    $query->forAcademicClass($data['academicPeriod'], (int) $kelasId);

                    return;
                }

                $query->where('class_id', $kelasId);
            })
            ->when($filters['prodi_id'] ?? null, function ($query, $programId) use ($data): void {
                $query->where(function ($query) use ($data, $programId): void {
                    if ($data['academicPeriod']) {
                        $query->whereHas('registrasiAkademik', fn ($registration) => $registration
                            ->where('taka_id', $data['academicPeriod']->id)
                            ->whereHas('kelas', fn ($class) => $class->where('pstudi_id', $programId)))
                            ->orWhere(function ($legacy) use ($data, $programId): void {
                                $legacy->where('taka_id', $data['academicPeriod']->id)
                                    ->whereHas('kelas', fn ($class) => $class->where('pstudi_id', $programId))
                                    ->whereDoesntHave('registrasiAkademik', fn ($registration) => $registration
                                        ->where('taka_id', $data['academicPeriod']->id));
                            });

                        return;
                    }

                    $query->whereHas('kelas', fn ($class) => $class->where('pstudi_id', $programId));
                });
            })
            ->with([
                'kelas.pstudi',
                'registrasiAkademik' => fn ($query) => $query
                    ->where('taka_id', $data['academicPeriod']?->id)
                    ->with('kelas.pstudi'),
            ])
            ->get();
        $data['kelas'] = Kelas::query()->orderBy('name')->get();
        $data['filterKelas'] = $data['academicPeriod']
            ? Kelas::query()->forAcademicPeriod($data['academicPeriod'])->orderBy('name')->get()
            : $data['kelas'];
        $data['programStudi'] = ProgramStudi::query()->orderBy('name')->get();
        $data['angkatan'] = $studentAngkatan->filter()->unique()->sortDesc()->values();
        $data['filters'] = $filters;

        return view('user.admin.pages.workers-student-index', $data);

    }

    public function createStudent(Request $request, AcademicPeriodContext $periodContext)
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['academicPeriod'] = $periodContext->current($request->user());
        $data['kelas'] = $data['academicPeriod']
            ? Kelas::query()->forAcademicPeriod($data['academicPeriod'])->orderBy('name')->get()
            : collect();
        $data['student'] = Mahasiswa::all();

        return view('user.admin.pages.workers-student-create', $data);

    }

    public function editStudent(Request $request, $code, AcademicPeriodContext $periodContext)
    {
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();
        $data['kelas'] = Kelas::all();
        $data['student'] = Mahasiswa::where('mhs_code', $code)->firstOrFail();
        $data['academicPeriod'] = $periodContext->current($request->user());
        $data['registration'] = $data['academicPeriod']
            ? RegistrasiMahasiswa::query()
                ->where('mahasiswa_id', $data['student']->id)
                ->where('taka_id', $data['academicPeriod']->id)
                ->with(['riwayatStatus.changedBy'])
                ->first()
            : null;
        $data['academicStatusTransitions'] = $data['registration']
            ? $data['registration']->allowedAcademicStatusTransitions()
            : [];
        $data['academicPeriodClasses'] = $data['academicPeriod']
            ? Kelas::query()->forAcademicPeriod($data['academicPeriod'])->orderBy('name')->get()
            : collect();
        $data['academicAdvisors'] = Dosen::query()
            ->where('dsn_stat', 1)
            ->orderBy('dsn_name')
            ->get();
        $data['registrationHistory'] = RegistrasiMahasiswa::query()
            ->where('mahasiswa_id', $data['student']->id)
            ->with(['taka', 'kelas', 'dosenWali'])
            ->get()
            ->sortByDesc(fn (RegistrasiMahasiswa $registration) => [
                $registration->taka?->starts_at?->timestamp ?? 0,
                $registration->id,
            ]);
        $data['latestRegistration'] = $data['registrationHistory']->first();
        $data['suggestedSemester'] = min(
            14,
            max(1, ((int) ($data['latestRegistration']?->semester_mahasiswa ?? 0)) + 1)
        );

        return view('user.admin.pages.workers-student-edit', $data);

    }

    public function storeStudent(Request $request, AcademicPeriodContext $periodContext)
    {
        $user = new Mahasiswa;
        $academicPeriod = $periodContext->requireCurrent($request->user());

        $request->validate([
            'mhs_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8196',
            'class_id' => [
                'required',
                'integer',
                Rule::exists('kelas', 'id')->where(fn ($query) => $query->where('taka_id', $academicPeriod->id)),
            ],
            'mhs_name' => 'required|string|max:255',
            'mhs_user' => 'required|string|max:255|unique:users,user,'.$user->id,
            'mhs_birthplace' => 'nullable|string|max:255', // New field
            'mhs_birthdate' => 'nullable|date', // New field
            'mhs_gend' => 'nullable|string',
            'mhs_phone' => 'required|numeric|unique:users,phone,'.$user->id,
            'mhs_mail' => 'required|email|max:255|unique:users,email,'.$user->id,
            'mhs_stat' => 'nullable|string',
            'password' => 'nullable|string',
            'password_confirm' => 'nullable|string|same:password',
        ], [
            'class_id.required' => 'Kelas mahasiswa wajib dipilih.',
            'class_id.integer' => 'Kelas mahasiswa tidak valid.',
            'class_id.exists' => 'Kelas mahasiswa tidak tersedia pada periode akademik yang dipilih.',
        ]);

        $user->class_id = $request->class_id;
        $user->taka_id = $academicPeriod->id;
        $user->mhs_name = $request->mhs_name;
        $user->mhs_user = $request->mhs_user;
        $user->mhs_nim = $request->mhs_nim;
        $user->mhs_gend = $request->mhs_gend;
        $user->mhs_birthplace = $request->mhs_birthplace;
        $user->mhs_birthdate = $request->mhs_birthdate;
        $user->mhs_birthdate = $request->mhs_birthdate;
        $user->mhs_reli = $request->mhs_reli;
        $user->mhs_phone = $request->mhs_phone;
        $user->mhs_mail = $request->mhs_mail;
        $user->mhs_parent_mother = $request->mhs_parent_mother;
        $user->mhs_parent_mother_phone = $request->mhs_parent_mother_phone;
        $user->mhs_parent_father = $request->mhs_parent_father;
        $user->mhs_parent_father_phone = $request->mhs_parent_father_phone;
        $user->mhs_wali_name = $request->mhs_wali_name;
        $user->mhs_wali_phone = $request->mhs_wali_phone;
        $user->mhs_addr_domisili = $request->mhs_addr_domisili;
        $user->mhs_addr_kelurahan = $request->mhs_addr_kelurahan;
        $user->mhs_addr_kecamatan = $request->mhs_addr_kecamatan;
        $user->mhs_addr_kota = $request->mhs_addr_kota;
        $user->mhs_addr_provinsi = $request->mhs_addr_provinsi;
        $user->mhs_stat = $request->mhs_stat;
        $user->mhs_code = Str::random(6);

        $user->password = Hash::make($request->password);
        $user->save();
        if ($request->hasFile('mhs_image')) {
            $image = $request->file('mhs_image');
            $name = 'profile-'.$user->mhs_code.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/images/profile/dosen');
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
            $user->mhs_image = 'profile/mahasiswa/'.$name;
            $user->save();

            Alert::success('Success', 'Data berhasil ditambahkan');

            return back();
        }
        Alert::success('Success', 'Data berhasil ditambahkan');

        return back();
    }

    public function updateStudent(Request $request, $code)
    {
        $user = Mahasiswa::where('mhs_code', $code)->first();

        $request->validate([
            'mhs_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:8196',
            'mhs_name' => 'required|string|max:255',
            'mhs_user' => 'string|max:255|unique:users,user,'.$user->id,
            'mhs_birthplace' => 'nullable|string|max:255', // New field
            'mhs_birthdate' => 'nullable|date', // New field
            'mhs_gend' => 'nullable|string',
            'mhs_phone' => 'required|numeric|unique:users,phone,'.$user->id,
            'mhs_mail' => 'required|email|max:255|unique:users,email,'.$user->id,
            'mhs_stat' => 'nullable|string',
            'password' => 'nullable|string',
            'password_confirm' => 'nullable|string|same:password',
        ]);

        $user->class_id = $request->class_id;
        $user->mhs_name = $request->mhs_name;
        // $user->mhs_user = $request->mhs_user;
        $user->mhs_nim = $request->mhs_nim;
        $user->mhs_gend = $request->mhs_gend;
        $user->mhs_birthplace = $request->mhs_birthplace;
        $user->mhs_birthdate = $request->mhs_birthdate;
        $user->mhs_birthdate = $request->mhs_birthdate;
        $user->mhs_reli = $request->mhs_reli;
        $user->mhs_phone = $request->mhs_phone;
        $user->mhs_mail = $request->mhs_mail;
        $user->mhs_parent_mother = $request->mhs_parent_mother;
        $user->mhs_parent_mother_phone = $request->mhs_parent_mother_phone;
        $user->mhs_parent_father = $request->mhs_parent_father;
        $user->mhs_parent_father_phone = $request->mhs_parent_father_phone;
        $user->mhs_wali_name = $request->mhs_wali_name;
        $user->mhs_wali_phone = $request->mhs_wali_phone;
        $user->mhs_addr_domisili = $request->mhs_addr_domisili;
        $user->mhs_addr_kelurahan = $request->mhs_addr_kelurahan;
        $user->mhs_addr_kecamatan = $request->mhs_addr_kecamatan;
        $user->mhs_addr_kota = $request->mhs_addr_kota;
        $user->mhs_addr_provinsi = $request->mhs_addr_provinsi;
        $user->mhs_stat = $request->mhs_stat;

        $user->password = Hash::make($request->password);
        $user->save();
        if ($request->hasFile('mhs_image')) {
            $image = $request->file('mhs_image');
            $name = 'profile-'.$user->mhs_code.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/images/profile/dosen');
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
            $user->mhs_image = 'profile/dosen/'.$name;
            $user->save();

            Alert::success('Success', 'Data berhasil diupdate');

            return back();
        }
        Alert::success('Success', 'Data berhasil diupdate');

        return back();
    }

    public function destroyStudent(Request $request, $code)
    {
        $destinationPaths = storage_path('app/public/images');

        $student = Mahasiswa::where('mhs_code', $code)->first();
        if ($student->image != 'default/default-profile.jpg') {
            File::delete($destinationPaths.'/'.$student->image); // hapus gambar lama
        }

        $student->delete();
        Alert::success('Success', 'Pengguna berhasil dihapus.');

        return back();
    }
}
