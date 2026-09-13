<?php

namespace App\Http\Controllers\Admin;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\TahunAkademik;
use App\Services\Imports\NilaiOpenFeederImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Rap2hpoutre\FastExcel\FastExcel;

class NilaiImportController extends Controller
{
    use roleTrait;

    public function index(Request $request): View
    {
        abort_unless((int) $request->user()->raw_type === 0, 403);

        return view('user.admin.nilai-import', [
            'prefix' => $this->setPrefix(),
            'periods' => TahunAkademik::query()->whereIn('status', ['draft', 'active'])->orderByDesc('year_start')->get(),
        ]);
    }

    public function store(Request $request, NilaiOpenFeederImportService $service): RedirectResponse
    {
        abort_unless((int) $request->user()->raw_type === 0, 403);
        $data = $request->validate([
            'periode' => ['required', 'string', 'exists:tahun_akademiks,code'],
            'import' => ['required', 'file', 'extensions:xlsx', 'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/x-zip-compressed', 'max:5120'],
        ], [
            'periode.required' => 'Pilih periode akademik tujuan.',
            'periode.exists' => 'Periode akademik tidak ditemukan.',
            'import.required' => 'Pilih file nilai yang akan diimpor.',
            'import.extensions' => 'File nilai harus berformat XLSX.',
            'import.mimetypes' => 'Isi file harus berupa XLSX yang valid.',
            'import.max' => 'Ukuran file maksimal 5 MB.',
        ]);
        $period = TahunAkademik::where('code', $data['periode'])->firstOrFail();
        $path = $request->file('import')->store('excel-files', 'local');
        try {
            try {
                $rows = (new FastExcel)->import(Storage::disk('local')->path($path));
            } catch (\Throwable) {
                throw ValidationException::withMessages(['import' => 'File Excel tidak dapat dibaca. Gunakan format template import nilai OpenFeeder.']);
            }
            $count = $service->import($rows, $period);
        } finally {
            Storage::disk('local')->delete($path);
        }

        return to_route('web-admin.nilai-import.index')->with('success', "{$count} nilai mahasiswa berhasil diimpor ke periode {$period->name}.");
    }
}
