<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Settings\webSettings;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Rap2hpoutre\FastExcel\FastExcel;
use Throwable;

class WilayahController extends Controller
{
    use roleTrait;

    public function index(Request $request)
    {
        $search = trim($request->string('q')->value());
        $province = trim($request->string('provinsi')->value());

        $wilayahs = Wilayah::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('kecamatan', 'like', "%{$search}%")
                        ->orWhere('kabupaten', 'like', "%{$search}%")
                        ->orWhere('provinsi', 'like', "%{$search}%");
                });
            })
            ->when($province !== '', fn ($query) => $query->where('provinsi', $province))
            ->orderBy('provinsi')
            ->orderBy('kabupaten')
            ->orderBy('kecamatan')
            ->paginate(25)
            ->withQueryString();

        return view('user.admin.master.admin-wilayah-index', [
            'web' => webSettings::find(1),
            'prefix' => $this->setPrefix(),
            'wilayahs' => $wilayahs,
            'provinces' => Wilayah::query()
                ->whereNotNull('provinsi')
                ->where('provinsi', '!=', '')
                ->distinct()
                ->orderBy('provinsi')
                ->pluck('provinsi'),
            'search' => $search,
            'selectedProvince' => $province,
        ]);
    }

    public function store(Request $request)
    {
        Wilayah::create($this->validateData($request));

        Alert::success('Sukses', 'Data wilayah berhasil ditambahkan.');

        return back();
    }

    public function update(Request $request, Wilayah $wilayah)
    {
        $wilayah->update($this->validateData($request, $wilayah));

        Alert::success('Sukses', 'Data wilayah berhasil diperbarui.');

        return back();
    }

    public function destroy(Wilayah $wilayah)
    {
        $wilayah->delete();

        Alert::success('Sukses', 'Data wilayah berhasil dihapus.');

        return back();
    }

    public function import(Request $request)
    {
        $request->validate([
            'import' => [
                'required',
                'file',
                'extensions:xlsx,csv',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/x-zip-compressed,text/csv,text/plain,application/csv',
                'max:5120',
            ],
        ], [
            'import.required' => 'File wilayah harus diunggah.',
            'import.extensions' => 'Ekstensi file harus xlsx atau csv.',
            'import.mimetypes' => 'File harus dalam format xlsx atau csv yang valid.',
            'import.max' => 'Ukuran file tidak boleh melebihi 5 MB.',
        ]);

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            try {
                $rows = (new FastExcel)->import(Storage::disk('local')->path($path));
            } catch (Throwable) {
                throw ValidationException::withMessages([
                    'import' => 'File tidak dapat dibaca. Pastikan format xlsx atau csv valid.',
                ]);
            }

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['import' => 'File import tidak berisi data wilayah.']);
            }

            $requiredHeaders = ['id_wil', 'kecamatan', 'kabupaten', 'provinsi'];
            $missingHeaders = array_diff($requiredHeaders, array_keys($rows->first()));

            if ($missingHeaders !== []) {
                throw ValidationException::withMessages([
                    'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
                ]);
            }

            $now = now();
            $data = [];
            $codes = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $code = trim((string) $row['id_wil']);
                $kecamatan = trim((string) $row['kecamatan']);
                $kabupaten = trim((string) $row['kabupaten']);
                $provinsi = trim((string) $row['provinsi']);

                if (! preg_match('/^\d{6}$/', $code)) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: id_wil wajib terdiri dari 6 digit.",
                    ]);
                }

                if ($kecamatan === '' || mb_strlen($kecamatan) > 255 || mb_strlen($kabupaten) > 255 || mb_strlen($provinsi) > 255) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: kecamatan wajib diisi dan nama wilayah maksimal 255 karakter.",
                    ]);
                }

                if (isset($codes[$code])) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: id_wil {$code} duplikat di dalam file.",
                    ]);
                }

                $codes[$code] = true;
                $data[] = [
                    'code' => $code,
                    'kecamatan' => $kecamatan,
                    'kabupaten' => $kabupaten !== '' ? $kabupaten : null,
                    'provinsi' => $provinsi !== '' ? $provinsi : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::transaction(function () use ($data) {
                foreach (array_chunk($data, 500) as $chunk) {
                    Wilayah::upsert(
                        $chunk,
                        ['code'],
                        ['kecamatan', 'kabupaten', 'provinsi', 'updated_at'],
                    );
                }
            });
        } finally {
            Storage::disk('local')->delete($path);
        }

        Alert::success('Sukses', count($data).' data wilayah berhasil diimport.');

        return back();
    }

    private function validateData(Request $request, ?Wilayah $wilayah = null): array
    {
        $data = $request->validate([
            'code' => [
                'required',
                'digits:6',
                Rule::unique('wilayahs', 'code')->ignore($wilayah),
            ],
            'kecamatan' => ['required', 'string', 'max:255'],
            'kabupaten' => ['nullable', 'string', 'max:255'],
            'provinsi' => ['nullable', 'string', 'max:255'],
        ], [
            'code.digits' => 'Kode wilayah wajib terdiri dari 6 digit.',
            'code.unique' => 'Kode wilayah sudah digunakan.',
            'kecamatan.required' => 'Nama kecamatan wajib diisi.',
        ]);

        $data['code'] = trim($data['code']);
        $data['kecamatan'] = trim($data['kecamatan']);
        $data['kabupaten'] = filled($data['kabupaten'] ?? null) ? trim($data['kabupaten']) : null;
        $data['provinsi'] = filled($data['provinsi'] ?? null) ? trim($data['provinsi']) : null;

        return $data;
    }
}
