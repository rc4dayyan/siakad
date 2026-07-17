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

            $requiredHeaders = ['Program Studi', 'Semester', 'Nama Mata Kuliah', 'SKS'];
            $missingHeaders = array_diff($requiredHeaders, array_keys($rows->first()));

            if ($missingHeaders !== []) {
                throw ValidationException::withMessages([
                    'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
                ]);
            }

            $now = now();
            $data = [];

            foreach ($rows as $index => $row) {
                $programStudi = strtoupper(trim((string) $row['Program Studi']));
                $semester = filter_var($row['Semester'], FILTER_VALIDATE_INT);
                $name = trim((string) $row['Nama Mata Kuliah']);
                $sks = filter_var($row['SKS'], FILTER_VALIDATE_INT);

                if ($programStudi === '' || strlen($programStudi) > 10 || $semester === false || $semester < 1 || $semester > 14 || $name === '' || $sks === false || $sks < 1 || $sks > 24) {
                    throw ValidationException::withMessages([
                        'import' => 'Baris '.($index + 2).': data program studi, semester, nama, atau SKS tidak valid.',
                    ]);
                }

                $data[] = [
                    'program_studi' => $programStudi,
                    'semester' => $semester,
                    'name' => $name,
                    'sks' => $sks,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::transaction(fn () => MasterMataKuliah::upsert(
                $data,
                ['program_studi', 'semester', 'name'],
                ['sks', 'updated_at'],
            ));
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
                'Semester' => $item->semester,
                'Nama Mata Kuliah' => $item->name,
                'SKS' => $item->sks,
            ];
        });
    }

    private function validateData(Request $request, ?MasterMataKuliah $masterMataKuliah = null): array
    {
        $data = $request->validate([
            'program_studi' => ['required', 'string', 'max:10'],
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
            'name.unique' => 'Mata kuliah tersebut sudah ada pada program studi dan semester yang dipilih.',
        ]);

        $data['program_studi'] = strtoupper(trim($data['program_studi']));

        return $data;
    }
}
