<?php

namespace App\Http\Controllers\Admin\Pages\Core;

use Alert;
use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\Settings\webSettings;
use App\Models\TahunAkademik;
use App\Models\TahunAkademikInduk;
use App\Services\Academic\AcademicAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TahunAkademikController extends Controller
{
    use roleTrait;

    public function index(): View
    {
        $data['web'] = webSettings::where('id', 1)->first();
        $data['prefix'] = $this->setPrefix();
        $data['taka'] = TahunAkademik::query()
            ->with('tahunAkademik')
            ->orderByDesc('year_start')
            ->orderByDesc('starts_at')
            ->get();
        $data['academicYears'] = TahunAkademikInduk::query()
            ->withCount('periodeAkademiks')
            ->orderByDesc('year_start')
            ->get();
        $data['terms'] = TahunAkademik::terms();

        return view('user.admin.master.admin-taka-index', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePeriod($request);

        DB::transaction(function () use ($validated): void {
            TahunAkademik::create([
                ...$validated,
                'semester' => $this->legacySemesterValue($validated['term']),
                'is_active' => false,
                'status' => TahunAkademik::STATUS_DRAFT,
            ]);
        });

        Alert::success('Berhasil', 'Periode akademik berhasil disimpan sebagai draft.');

        return back();
    }

    public function update(Request $request, string $code): RedirectResponse
    {
        $taka = TahunAkademik::where('code', $code)->firstOrFail();

        if ($taka->status !== TahunAkademik::STATUS_DRAFT || $taka->is_active) {
            Alert::error('Tidak dapat diubah', 'Hanya periode berstatus draft yang dapat diubah.');

            return back();
        }

        $validated = $this->validatePeriod($request, $taka);

        DB::transaction(function () use ($taka, $validated): void {
            $taka->update([
                ...$validated,
                'semester' => $this->legacySemesterValue($validated['term']),
            ]);
        });

        Alert::success('Berhasil', 'Periode akademik berhasil diperbarui.');

        return back();
    }

    public function activate(string $code, AcademicAuditService $audit): RedirectResponse
    {
        $taka = TahunAkademik::where('code', $code)->firstOrFail();

        if ($taka->status === TahunAkademik::STATUS_ACTIVE) {
            Alert::info('Informasi', 'Periode akademik tersebut sudah aktif.');

            return back();
        }

        if ($taka->status !== TahunAkademik::STATUS_DRAFT) {
            Alert::error('Tidak dapat diaktifkan', 'Hanya periode berstatus draft yang dapat diaktifkan.');

            return back();
        }

        $missingAcademicYear = Schema::hasColumn('tahun_akademiks', 'tid') && ! $taka->tid;

        if ($missingAcademicYear || ! $taka->term || ! $taka->year_end || ! $taka->starts_at || ! $taka->ends_at) {
            Alert::error('Data belum lengkap', 'Lengkapi jenis dan rentang periode sebelum aktivasi.');

            return back();
        }

        DB::transaction(function () use ($taka, $audit): void {
            TahunAkademik::query()->lockForUpdate()->get();

            $closedAttributes = [
                'is_active' => false,
                'status' => TahunAkademik::STATUS_CLOSED,
            ];
            if (Schema::hasColumn('tahun_akademiks', 'is_published')) {
                $closedAttributes['is_published'] = false;
            }

            TahunAkademik::query()
                ->whereKeyNot($taka->getKey())
                ->where(function ($query): void {
                    $query->where('is_active', true)
                        ->orWhere('status', TahunAkademik::STATUS_ACTIVE);
                })
                ->update($closedAttributes);

            $taka->update([
                'is_active' => true,
                'status' => TahunAkademik::STATUS_ACTIVE,
                'activated_at' => now(),
                'activated_by' => auth()->id(),
            ]);

            $audit->record('period.activated', $taka, $taka->id, auth()->user(),
                ['status' => TahunAkademik::STATUS_DRAFT, 'is_active' => false],
                ['status' => TahunAkademik::STATUS_ACTIVE, 'is_active' => true]);
        });

        Alert::success('Berhasil', 'Periode akademik berhasil diaktifkan.');

        return back();
    }

    public function destroy(string $code): RedirectResponse
    {
        $taka = TahunAkademik::where('code', $code)->firstOrFail();

        if ($taka->status !== TahunAkademik::STATUS_DRAFT || $taka->is_active) {
            Alert::error('Tidak dapat dihapus', 'Hanya periode draft yang tidak aktif yang dapat dihapus.');

            return back();
        }

        if ($taka->hasAcademicData()) {
            Alert::error('Tidak dapat dihapus', 'Periode sudah digunakan oleh data akademik.');

            return back();
        }

        $taka->delete();

        Alert::success('Berhasil', 'Periode akademik berhasil dihapus.');

        return back();
    }

    private function validatePeriod(Request $request, ?TahunAkademik $taka = null): array
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'tid' => ['required', 'integer', Rule::exists('tahun_akademik', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('tahun_akademiks', 'code')->ignore($taka?->id),
            ],
            'term' => [
                'required',
                Rule::in(TahunAkademik::terms()),
                Rule::unique('tahun_akademiks', 'term')
                    ->where(fn ($query) => $query->where('tid', $request->integer('tid')))
                    ->ignore($taka?->id),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ], [
            'tid.required' => 'Tahun akademik wajib dipilih.',
            'tid.exists' => 'Tahun akademik yang dipilih tidak tersedia.',
            'code.regex' => 'Kode periode hanya boleh berisi huruf, angka, dan tanda hubung.',
            'code.unique' => 'Kode periode akademik sudah digunakan.',
            'term.in' => 'Jenis periode akademik tidak valid.',
            'term.unique' => 'Jenis periode tersebut sudah tersedia pada tahun akademik yang dipilih.',
            'ends_at.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        $academicYear = TahunAkademikInduk::findOrFail($validated['tid']);
        $validated['year_start'] = $academicYear->year_start;
        $validated['year_end'] = $academicYear->year_end;

        return $validated;
    }

    private function legacySemesterValue(string $term): int
    {
        return match ($term) {
            TahunAkademik::TERM_GANJIL => 1,
            TahunAkademik::TERM_GENAP => 2,
            TahunAkademik::TERM_PENDEK => 0,
        };
    }
}
