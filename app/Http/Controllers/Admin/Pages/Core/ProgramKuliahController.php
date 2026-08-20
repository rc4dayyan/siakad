<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
// SECTION ADDONS SYSTEM
// SECTION ADDONS EXTERNAL
use App\Http\Controllers\Controller;
use App\Models\ProgramKuliah;
// SECTION MODELS
use App\Models\ProgramStudi;
use App\Models\Settings\webSettings;
use App\Models\TahunAkademik;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Rap2hpoutre\FastExcel\FastExcel;

class ProgramKuliahController extends Controller
{
    use roleTrait;

    public function index(Request $request)
    {
        $filters = $this->validateFilters($request);

        $data['web'] = webSettings::where('id', 1)->first();
        $data['prefix'] = $this->setPrefix();
        $data['taka'] = TahunAkademik::query()->latest('year_start')->latest('id')->get();
        $data['pstudi'] = ProgramStudi::query()->orderBy('name')->get();
        $data['waves'] = ProgramKuliah::query()
            ->whereNotNull('wave')
            ->where('wave', '!=', '')
            ->distinct()
            ->orderBy('wave')
            ->pluck('wave');
        $data['proku'] = $this->filteredQuery($filters)
            ->with(['taka', 'pstudi'])
            ->orderByDesc('taka_id')
            ->orderBy('name')
            ->get();
        $data['filters'] = $filters;

        return view('user.admin.master.admin-proku-index', $data);
    }

    public function export(Request $request)
    {
        $programs = $this->filteredQuery($this->validateFilters($request))
            ->with(['taka', 'pstudi'])
            ->orderByDesc('taka_id')
            ->orderBy('name')
            ->get();

        $response = (new FastExcel($programs))->download(
            'program-kuliah-'.now()->format('YmdHis').'.xlsx',
            fn (ProgramKuliah $program): array => [
                'Kode Program Kuliah' => $program->code,
                'Nama Program Kuliah' => $program->name,
                'Kode Tahun Akademik' => $program->taka?->code,
                'Kode Program Studi' => $program->pstudi?->code,
                'Gelombang' => $program->wave,
                'Tanggal Mulai Pendaftaran' => $this->formatDate($program->wave_start),
                'Tanggal Akhir Pendaftaran' => $this->formatDate($program->wave_ended),
            ]
        );

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return $response;
    }

    public function import(Request $request)
    {
        $request->validate([
            '_form' => ['nullable', 'in:import-proku'],
            'import' => ['required', 'file', 'mimes:xlsx,csv', 'max:2048'],
        ], [
            'import.required' => 'File harus diunggah.',
            'import.mimes' => 'File harus dalam format xlsx atau csv.',
            'import.max' => 'Ukuran file tidak boleh melebihi 2MB.',
        ]);

        $path = $request->file('import')->store('excel-files', 'local');

        try {
            $rows = (new FastExcel)->import(storage_path('app/'.$path));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'import' => 'File tidak dapat dibaca. Pastikan format xlsx atau csv valid.',
            ]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $requiredHeaders = [
            'Kode Program Kuliah',
            'Nama Program Kuliah',
            'Kode Tahun Akademik',
            'Kode Program Studi',
            'Gelombang',
            'Tanggal Mulai Pendaftaran',
            'Tanggal Akhir Pendaftaran',
        ];

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages(['import' => 'File import tidak berisi data program kuliah.']);
        }

        $missingHeaders = array_diff($requiredHeaders, array_keys($rows->first()));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.',
            ]);
        }

        $result = DB::transaction(function () use ($rows): array {
            $created = 0;
            $skipped = 0;

            foreach ($rows as $index => $line) {
                $rowNumber = $index + 2;
                $code = trim((string) $line['Kode Program Kuliah']);
                $name = trim((string) $line['Nama Program Kuliah']);
                $periodCode = trim((string) $line['Kode Tahun Akademik']);
                $studyProgramCode = trim((string) $line['Kode Program Studi']);
                $wave = trim((string) $line['Gelombang']);

                if ($code === '' || $name === '' || $periodCode === '' || $studyProgramCode === '' || $wave === '') {
                    $this->rejectImportRow($rowNumber, 'semua kolom wajib diisi.');
                }

                if (mb_strlen($code) > 255 || mb_strlen($name) > 255 || mb_strlen($wave) > 255) {
                    $this->rejectImportRow($rowNumber, 'kode, nama, dan gelombang maksimal 255 karakter.');
                }

                if (ProgramKuliah::query()->where('code', $code)->exists()) {
                    $skipped++;

                    continue;
                }

                $period = TahunAkademik::query()->where('code', $periodCode)->first();
                $studyProgram = ProgramStudi::query()->where('code', $studyProgramCode)->first();

                if (! $period) {
                    $this->rejectImportRow($rowNumber, "kode tahun akademik {$periodCode} tidak ditemukan.");
                }

                if (! $studyProgram) {
                    $this->rejectImportRow($rowNumber, "kode program studi {$studyProgramCode} tidak ditemukan.");
                }

                $start = $this->parseImportDate($line['Tanggal Mulai Pendaftaran'], $rowNumber, 'Tanggal Mulai Pendaftaran');
                $end = $this->parseImportDate($line['Tanggal Akhir Pendaftaran'], $rowNumber, 'Tanggal Akhir Pendaftaran');

                if ($end->lt($start)) {
                    $this->rejectImportRow($rowNumber, 'tanggal akhir pendaftaran harus sama dengan atau setelah tanggal mulai.');
                }

                ProgramKuliah::create([
                    'taka_id' => $period->id,
                    'pstudi_id' => $studyProgram->id,
                    'name' => $name,
                    'code' => $code,
                    'wave' => $wave,
                    'wave_start' => $start->toDateString(),
                    'wave_ended' => $end->toDateString(),
                ]);
                $created++;
            }

            return compact('created', 'skipped');
        });

        Alert::success('Sukses', "Import selesai: {$result['created']} program kuliah dibuat dan {$result['skipped']} kode duplikat dilewati.");

        return back();
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'wave' => 'required|string|max:255',
            'wave_start' => 'required|date',
            'wave_ended' => 'required|date',
            'taka_id' => 'required',
            'pstudi_id' => 'required',
        ]);

        $pstudi = new ProgramKuliah;
        $pstudi->name = $request->name;
        $pstudi->code = $request->code;
        $pstudi->wave = $request->wave;
        $pstudi->wave_start = $request->wave_start;
        $pstudi->wave_ended = $request->wave_ended;
        $pstudi->taka_id = $request->taka_id;
        $pstudi->pstudi_id = $request->pstudi_id;
        $pstudi->save();

        Alert::success('success', 'Data telah berhasil disimpan');

        return back();
    }

    public function update(Request $request, $code)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'wave' => 'required|string|max:255',
            'wave_start' => 'required|date',
            'wave_ended' => 'required|date',
            'taka_id' => 'required',
            'pstudi_id' => 'required',
        ]);

        $pstudi = ProgramKuliah::where('code', $code)->first();
        $pstudi->name = $request->name;
        $pstudi->code = $request->code;
        $pstudi->wave = $request->wave;
        $pstudi->wave_start = $request->wave_start;
        $pstudi->wave_ended = $request->wave_ended;
        $pstudi->taka_id = $request->taka_id;
        $pstudi->pstudi_id = $request->pstudi_id;
        $pstudi->save();

        Alert::success('success', 'Data telah berhasil diupdate');

        return back();
    }

    public function destroy(Request $request, $code)
    {

        $pstudi = ProgramKuliah::where('code', $code)->first();
        $pstudi->delete();

        Alert::success('success', 'Data telah berhasil dihapus');

        return back();
    }

    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'taka_id' => ['nullable', 'integer', 'exists:tahun_akademiks,id'],
            'pstudi_id' => ['nullable', 'integer', 'exists:program_studis,id'],
            'wave' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function filteredQuery(array $filters): Builder
    {
        return ProgramKuliah::query()
            ->when($filters['taka_id'] ?? null, fn (Builder $query, $periodId) => $query->where('taka_id', $periodId))
            ->when($filters['pstudi_id'] ?? null, fn (Builder $query, $programId) => $query->where('pstudi_id', $programId))
            ->when($filters['wave'] ?? null, fn (Builder $query, $wave) => $query->where('wave', $wave));
    }

    private function parseImportDate(mixed $value, int $rowNumber, string $column): Carbon
    {
        try {
            if ($value instanceof DateTimeInterface) {
                return Carbon::instance($value)->startOfDay();
            }

            $date = Carbon::createFromFormat('!Y-m-d', trim((string) $value));

            if ($date === false || $date->format('Y-m-d') !== trim((string) $value)) {
                throw new \RuntimeException;
            }

            return $date;
        } catch (\Throwable) {
            $this->rejectImportRow($rowNumber, "{$column} harus menggunakan format YYYY-MM-DD.");
        }
    }

    private function formatDate(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->toDateString() : null;
    }

    private function rejectImportRow(int $rowNumber, string $message): never
    {
        throw ValidationException::withMessages([
            'import' => "Baris {$rowNumber}: {$message}",
        ]);
    }
}
