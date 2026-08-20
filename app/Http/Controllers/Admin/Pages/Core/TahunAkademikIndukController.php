<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\PeriodeAkademik;
use App\Models\Settings\webSettings;
use App\Models\TahunAkademikInduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TahunAkademikIndukController extends Controller
{
    use roleTrait;

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'year_start' => ['nullable', 'integer', 'between:2000,2100'],
            'usage' => ['nullable', Rule::in(['used', 'unused'])],
        ]);
        $keyword = trim((string) ($filters['q'] ?? ''));

        $academicYears = TahunAkademikInduk::query()
            ->withCount('periodeAkademiks')
            ->with(['periodeAkademiks' => fn ($query) => $query
                ->select(['id', 'tid', 'name', 'code', 'term', 'status', 'is_active'])
                ->orderBy('starts_at')])
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($search) use ($keyword): void {
                    $search->where('name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%")
                        ->orWhere('year_start', 'like', "%{$keyword}%")
                        ->orWhere('year_end', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['year_start'] ?? null, fn ($query, $year) => $query->where('year_start', $year))
            ->when(($filters['usage'] ?? null) === 'used', fn ($query) => $query->has('periodeAkademiks'))
            ->when(($filters['usage'] ?? null) === 'unused', fn ($query) => $query->doesntHave('periodeAkademiks'))
            ->orderByDesc('year_start')
            ->paginate(12)
            ->withQueryString();

        return view('user.admin.master.admin-tahun-akademik-index', [
            'web' => webSettings::where('id', 1)->first(),
            'prefix' => $this->setPrefix(),
            'academicYears' => $academicYears,
            'yearOptions' => TahunAkademikInduk::query()->distinct()->orderByDesc('year_start')->pluck('year_start'),
            'filters' => $filters,
            'summary' => [
                'total' => TahunAkademikInduk::count(),
                'used' => TahunAkademikInduk::has('periodeAkademiks')->count(),
                'periods' => PeriodeAkademik::whereNotNull('tid')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAcademicYear($request);

        TahunAkademikInduk::create($validated);

        Alert::success('Berhasil', 'Tahun akademik berhasil ditambahkan.');

        return back();
    }

    public function update(Request $request, string $code): RedirectResponse
    {
        $academicYear = TahunAkademikInduk::where('code', $code)->firstOrFail();
        $validated = $this->validateAcademicYear($request, $academicYear);
        $yearsChanged = (int) $academicYear->year_start !== (int) $validated['year_start']
            || (int) $academicYear->year_end !== (int) $validated['year_end'];

        if ($yearsChanged && $academicYear->periodeAkademiks()->exists()) {
            throw ValidationException::withMessages([
                'year_start' => 'Rentang tahun tidak dapat diubah karena sudah digunakan oleh periode akademik.',
            ]);
        }

        $academicYear->update($validated);

        Alert::success('Berhasil', 'Tahun akademik berhasil diperbarui.');

        return back();
    }

    public function destroy(string $code): RedirectResponse
    {
        $academicYear = TahunAkademikInduk::where('code', $code)->firstOrFail();

        if ($academicYear->periodeAkademiks()->exists()) {
            Alert::error('Tidak dapat dihapus', 'Tahun akademik masih digunakan oleh satu atau beberapa periode akademik.');

            return back();
        }

        $academicYear->delete();

        Alert::success('Berhasil', 'Tahun akademik berhasil dihapus.');

        return back();
    }

    private function validateAcademicYear(Request $request, ?TahunAkademikInduk $academicYear = null): array
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('tahun_akademik', 'code')->ignore($academicYear?->id),
            ],
            'year_start' => [
                'required',
                'integer',
                'between:2000,2100',
                Rule::unique('tahun_akademik', 'year_start')
                    ->where(fn ($query) => $query->where('year_end', $request->input('year_end')))
                    ->ignore($academicYear?->id),
            ],
            'year_end' => ['required', 'integer', 'between:2001,2101', 'gt:year_start'],
        ], [
            'code.regex' => 'Kode hanya boleh berisi huruf, angka, dan tanda hubung.',
            'code.unique' => 'Kode tahun akademik sudah digunakan.',
            'year_start.unique' => 'Rentang tahun akademik tersebut sudah tersedia.',
            'year_end.gt' => 'Tahun selesai harus setelah tahun mulai.',
        ]);

        return $validated;
    }
}
