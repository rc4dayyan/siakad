<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\MasterMataKuliah;
use App\Models\Settings\webSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Rap2hpoutre\FastExcel\FastExcel;

class MasterMataKuliahController extends Controller
{
    use roleTrait;

    public function index()
    {
        return view('user.admin.master.admin-master-matkul-index', [
            'web' => webSettings::find(1),
            'prefix' => $this->setPrefix(),
            'masterMatkul' => MasterMataKuliah::query()
                ->orderBy('program_studi')
                ->orderBy('semester')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        MasterMataKuliah::create($data);

        Alert::success('Sukses', 'Master mata kuliah berhasil ditambahkan.');

        return back();
    }

    public function update(Request $request, MasterMataKuliah $masterMataKuliah)
    {
        $data = $this->validateData($request, $masterMataKuliah);
        $masterMataKuliah->update($data);

        Alert::success('Sukses', 'Master mata kuliah berhasil diperbarui.');

        return back();
    }

    public function import(Request $request)
    {
        $request->validate([
            'import' => ['required', 'file', 'mimes:xlsx,csv', 'max:2048'],
        ], [
            'import.required' => 'File harus diunggah.',
            'import.mimes' => 'File harus dalam format xlsx atau csv.',
            'import.max' => 'Ukuran file tidak boleh melebihi 2MB.',
        ]);

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(storage_path('app/'.$path));

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['import' => 'File import tidak berisi data.']);
            }

            $requiredHeaders = ['Program Studi', 'Kode', 'Nama Mata Kuliah', 'SKS', 'Semester'];
            $missingHeaders = array_diff($requiredHeaders, array_keys($rows->first()));

            if ($missingHeaders !== []) {
                throw ValidationException::withMessages([
                    'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
                ]);
            }

            $data = [];
            $codes = [];
            $naturalKeys = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $programStudi = strtoupper(trim((string) $row['Program Studi']));
                $code = strtoupper(trim((string) $row['Kode']));
                $name = trim((string) $row['Nama Mata Kuliah']);
                $sks = filter_var($row['SKS'], FILTER_VALIDATE_INT);
                $semester = $this->parseSemester($row['Semester']);

                if ($programStudi === '' || mb_strlen($programStudi) > 10 || $code === '' || mb_strlen($code) > 50 || $semester === null || $name === '' || mb_strlen($name) > 255 || $sks === false || $sks < 1 || $sks > 24) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: data program studi, kode, nama mata kuliah, SKS, atau semester tidak valid.",
                    ]);
                }

                $naturalKey = $programStudi.'|'.$semester.'|'.mb_strtolower($name);

                if (isset($codes[$code])) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: kode {$code} juga digunakan pada baris {$codes[$code]}.",
                    ]);
                }

                if (isset($naturalKeys[$naturalKey])) {
                    throw ValidationException::withMessages([
                        'import' => "Baris {$rowNumber}: mata kuliah yang sama juga terdapat pada baris {$naturalKeys[$naturalKey]}.",
                    ]);
                }

                $codes[$code] = $rowNumber;
                $naturalKeys[$naturalKey] = $rowNumber;
                $data[] = [
                    'program_studi' => $programStudi,
                    'code' => $code,
                    'semester' => $semester,
                    'name' => $name,
                    'sks' => $sks,
                    'row_number' => $rowNumber,
                ];
            }

            DB::transaction(function () use ($data) {
                foreach ($data as $attributes) {
                    $rowNumber = $attributes['row_number'];
                    unset($attributes['row_number']);

                    $byCode = MasterMataKuliah::where('code', $attributes['code'])->first();
                    $byNaturalKey = MasterMataKuliah::query()
                        ->where('program_studi', $attributes['program_studi'])
                        ->where('semester', $attributes['semester'])
                        ->where('name', $attributes['name'])
                        ->first();

                    if ($byCode && $byNaturalKey && ! $byCode->is($byNaturalKey)) {
                        throw ValidationException::withMessages([
                            'import' => "Baris {$rowNumber}: kode {$attributes['code']} dan mata kuliah {$attributes['name']} mengarah ke dua data yang berbeda.",
                        ]);
                    }

                    ($byCode ?? $byNaturalKey ?? new MasterMataKuliah)->fill($attributes)->save();
                }
            });
        } finally {
            unlink(storage_path('app/'.$path));
        }

        Alert::success('Sukses', count($data).' master mata kuliah berhasil diimport.');

        return back();
    }

    public function export()
    {
        $items = MasterMataKuliah::query()
            ->orderBy('program_studi')
            ->orderBy('semester')
            ->orderBy('name')
            ->get();

        return (new FastExcel($items))->download('master-mata-kuliah-'.now()->format('Ymd-His').'.xlsx', function (MasterMataKuliah $item) {
            return [
                'Program Studi' => $item->program_studi,
                'Kode' => $item->code,
                'Nama Mata Kuliah' => $item->name,
                'SKS' => $item->sks,
                'Semester' => $this->semesterToRoman($item->semester),
            ];
        });
    }

    private function validateData(Request $request, ?MasterMataKuliah $masterMataKuliah = null): array
    {
        $request->merge([
            'program_studi' => strtoupper(trim((string) $request->input('program_studi'))),
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $data = $request->validate([
            'program_studi' => ['required', 'string', 'max:10'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('master_mata_kuliahs', 'code')->ignore($masterMataKuliah),
            ],
            'semester' => ['required', 'integer', 'between:1,14'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_mata_kuliahs')->where(fn ($query) => $query
                    ->where('program_studi', strtoupper($request->string('program_studi')->trim()->value()))
                    ->where('semester', $request->integer('semester')))
                    ->ignore($masterMataKuliah),
            ],
            'sks' => ['required', 'integer', 'between:1,24'],
        ], [
            'code.required' => 'Kode mata kuliah wajib diisi.',
            'code.unique' => 'Kode mata kuliah sudah digunakan.',
            'name.unique' => 'Mata kuliah tersebut sudah ada pada program studi dan semester yang dipilih.',
        ]);

        return $data;
    }

    private function parseSemester(mixed $value): ?int
    {
        $semester = strtoupper(trim((string) $value));

        if (ctype_digit($semester)) {
            $number = (int) $semester;

            return $number >= 1 && $number <= 14 ? $number : null;
        }

        $romanSemesters = array_flip([
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII',
            8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII', 13 => 'XIII', 14 => 'XIV',
        ]);

        return $romanSemesters[$semester] ?? null;
    }

    private function semesterToRoman(int $semester): string
    {
        return [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII',
            8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII', 13 => 'XIII', 14 => 'XIV',
        ][$semester] ?? (string) $semester;
    }
}
