<?php

namespace App\Http\Controllers\Admin\Pages\Finance;

use Alert;
use App\Helper\roleTrait;
// SECTION ADDONS SYSTEM
use App\Http\Controllers\Controller;
use App\Models\HistoryTagihan;
// SECTION ADDONS EXTERNAL
use App\Models\Mahasiswa;
use App\Models\ProgramKuliah;
// SECTION MODELS
use App\Models\ProgramStudi;
use App\Models\Settings\webSettings;
use App\Models\TagihanKuliah;
use App\Models\TemplateTagihan;
use App\Services\Academic\AcademicAuditService;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Finance\BillingTargetService;
use Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Str;

class GenerateTagihanController extends Controller
{
    use roleTrait;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'mahasiswa_id' => ['nullable', 'integer', 'exists:mahasiswas,id'],
            'target' => ['nullable', 'in:mahasiswa,prodi,proku,kelompok'],
            'status' => ['nullable', 'in:draft,terbit,dibatalkan'],
        ]);
        $filters = array_merge([
            'q' => null,
            'mahasiswa_id' => null,
            'target' => null,
            'status' => null,
        ], $filters);
        $filters['q'] = filled($filters['q']) ? trim($filters['q']) : null;

        $data['income'] = HistoryTagihan::where('stat', 1)->whereHas('tagihan', function ($query) {
            $query->select('price');
        })->with('tagihan')->get()->sum(function ($history) {
            return $history->tagihan->price;
        });
        $data['web'] = webSettings::where('id', 1)->first();
        $data['tagihan'] = TagihanKuliah::query()
            ->with(['mahasiswa', 'targetMahasiswa', 'prodi', 'targetProdi', 'prokuu', 'targetProku'])
            ->when($filters['q'], function ($query, $search): void {
                $keyword = '%'.trim($search).'%';

                $query->where(function ($query) use ($keyword): void {
                    $query->where('code', 'like', $keyword)
                        ->orWhere('name', 'like', $keyword)
                        ->orWhereHas('mahasiswa', fn ($student) => $student
                            ->where('mhs_name', 'like', $keyword)
                            ->orWhere('mhs_nim', 'like', $keyword))
                        ->orWhereHas('targetMahasiswa', fn ($student) => $student
                            ->where('mhs_name', 'like', $keyword)
                            ->orWhere('mhs_nim', 'like', $keyword));
                });
            })
            ->when($filters['mahasiswa_id'], fn ($query, $studentId) => $query
                ->where(fn ($target) => $target
                    ->where('target_mahasiswa_id', $studentId)
                    ->orWhere('users_id', $studentId)))
            ->when($filters['target'], function ($query, $target): void {
                $query->where(function ($query) use ($target): void {
                    $query->where('target_type', $target);

                    if ($target === 'mahasiswa') {
                        $query->orWhere(fn ($legacy) => $legacy->whereNull('target_type')->where('users_id', '>', 0));
                    } elseif ($target === 'prodi') {
                        $query->orWhere(fn ($legacy) => $legacy->whereNull('target_type')->where('prodi_id', '>', 0));
                    } elseif ($target === 'proku') {
                        $query->orWhere(fn ($legacy) => $legacy->whereNull('target_type')->where('proku_id', '>', 0));
                    }
                });
            })
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $data['mahasiswa'] = Mahasiswa::query()->orderBy('mhs_name')->get(['id', 'mhs_nim', 'mhs_name']);
        $data['prodi'] = ProgramStudi::query()->orderBy('name')->get();
        $data['proku'] = ProgramKuliah::query()->orderBy('name')->get();
        $data['filters'] = $filters;
        $data['totalTagihan'] = TagihanKuliah::count();
        $data['totalPembayaran'] = HistoryTagihan::where('stat', 1)->count();
        $data['prefix'] = $this->setPrefix();

        return view('user.finance.pages.tagihan-index', $data);
    }

    public function create(Request $request)
    {
        $data['income'] = HistoryTagihan::where('stat', 1)->whereHas('tagihan', function ($query) {
            $query->select('price');
        })->with('tagihan')->get()->sum(function ($history) {
            return $history->tagihan->price;
        });
        $data['tagihan'] = TagihanKuliah::latest()->paginate(3);
        $data['history'] = HistoryTagihan::all();
        $data['mahasiswa'] = Mahasiswa::all();
        $data['prodi'] = ProgramStudi::all();
        $data['proku'] = ProgramKuliah::all();
        $data['prefix'] = $this->setPrefix();
        $data['web'] = webSettings::where('id', 1)->first();

        return view('user.finance.pages.tagihan-create', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'string|max:255',
            'price' => 'string|max:255',
            'prodi_id' => 'required_without_all:users_id,proku_id|nullable|integer|min:0',
            'proku_id' => 'required_without_all:users_id,prodi_id|nullable|integer|min:0',
            'users_id' => 'required_without_all:proku_id,prodi_id|nullable|integer|min:0',
        ]);

        // Menghitung jumlah nilai yang valid
        $count = count(array_filter([$request->prodi_id, $request->proku_id, $request->users_id], function ($value) {
            return $value > 0;
        }));

        // Validasi jika hanya satu nilai yang valid
        if ($count != 1) {
            Alert::error('error', 'Hanya boleh memilih salah satu.');

            return back()->withInput();
        }

        $tagihan = new TagihanKuliah;
        $tagihan->name = $request->name;
        $tagihan->price = $request->price;
        $tagihan->prodi_id = $request->prodi_id;
        $tagihan->proku_id = $request->proku_id;
        $tagihan->users_id = $request->users_id;
        $tagihan->author_id = Auth::user()->id;
        $tagihan->code = 'UKT-'.Str::random(8);

        $tagihan->save();

        Alert::success('success', 'Data berhasil ditambahkan');

        return back();
    }

    public function update(Request $request, $code)
    {
        $request->validate([
            'name' => 'string|max:255',
            'price' => 'string|max:255',
            'prodi_id' => 'required_without_all:users_id,proku_id|nullable|integer|min:0',
            'proku_id' => 'required_without_all:users_id,prodi_id|nullable|integer|min:0',
            'users_id' => 'required_without_all:proku_id,prodi_id|nullable|integer|min:0',
        ]);

        // Menghitung jumlah nilai yang valid
        $count = count(array_filter([$request->prodi_id, $request->proku_id, $request->users_id], function ($value) {
            return $value > 0;
        }));

        // Validasi jika hanya satu nilai yang valid
        if ($count != 1) {
            Alert::error('error', 'Hanya boleh memilih salah satu.');

            return back()->withInput();
        }

        $tagihan = TagihanKuliah::where('code', $code)->first();
        $tagihan->name = $request->name;
        $tagihan->price = $request->price;
        $tagihan->prodi_id = $request->prodi_id;
        $tagihan->proku_id = $request->proku_id;
        $tagihan->users_id = $request->users_id;
        $tagihan->author_id = Auth::user()->id;
        // $tagihan->code = 'UKT-'.Str::random(8);

        $tagihan->save();

        Alert::success('success', 'Data berhasil diupdate');

        return back();
    }

    public function prepare(
        Request $request,
        string $code,
        AcademicPeriodContext $periods,
        BillingTargetService $targets,
        AcademicAuditService $audit
    ): RedirectResponse {
        $period = $periods->requireWritableCurrent($request->user());
        $data = $request->validate([
            '_form' => ['required', 'string'],
            'jenis' => ['required', Rule::in(array_keys(TemplateTagihan::JENIS_LABELS))],
            'nominal' => ['required', 'integer', 'min:1'],
            'tanggal_terbit' => ['required', 'date'],
            'jatuh_tempo' => ['required', 'date', 'after_or_equal:tanggal_terbit'],
            'wajib_lunas_krs' => ['nullable', 'boolean'],
        ]);

        $template = DB::transaction(function () use ($request, $code, $period, $targets, $audit, $data): TemplateTagihan {
            $draft = TagihanKuliah::query()->where('code', $code)->lockForUpdate()->firstOrFail();

            if ($draft->status !== TagihanKuliah::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya tagihan berstatus draft yang dapat diproses untuk penerbitan.',
                ]);
            }

            $target = $this->resolveDraftTarget($draft);
            $template = TemplateTagihan::create($target + [
                'taka_id' => $period->id,
                'name' => $draft->name,
                'jenis' => $data['jenis'],
                'nominal' => $data['nominal'],
                'tanggal_terbit' => $data['tanggal_terbit'],
                'jatuh_tempo' => $data['jatuh_tempo'],
                'wajib_lunas_krs' => $request->boolean('wajib_lunas_krs'),
                'created_by' => $request->user()->id,
            ]);

            $targets->validate($template);

            if (! $targets->candidateQuery($template)->exists()) {
                throw ValidationException::withMessages([
                    'target' => 'Tidak ada mahasiswa terdaftar yang sesuai dengan target pada periode terpilih.',
                ]);
            }

            $before = ['status' => $draft->status];
            $draft->update(['status' => TagihanKuliah::STATUS_DIBATALKAN]);
            $audit->record(
                'billing.draft_prepared',
                $draft,
                $period->id,
                $request->user(),
                $before,
                ['status' => TagihanKuliah::STATUS_DIBATALKAN],
                ['template_id' => $template->id, 'source_code' => $draft->code]
            );

            return $template;
        });

        return redirect()
            ->route($this->setPrefix().'billing-period.preview', $template)
            ->with('success', 'Draft berhasil disiapkan. Periksa pratinjau sebelum menerbitkan tagihan.');
    }

    public function destroy(Request $request, $code, AcademicAuditService $audit)
    {
        $tagihan = TagihanKuliah::where('code', $code)->firstOrFail();
        if ($tagihan->taka_id) {
            $before = ['status' => $tagihan->status];
            $tagihan->update(['status' => TagihanKuliah::STATUS_DIBATALKAN]);
            $audit->record('billing.cancelled', $tagihan, $tagihan->taka_id, $request->user(), $before,
                ['status' => TagihanKuliah::STATUS_DIBATALKAN]);
        } else {
            $tagihan->delete();
        }

        Alert::success('success', 'Data telah berhasil dihapus');

        return back();
    }

    private function resolveDraftTarget(TagihanKuliah $draft): array
    {
        $candidates = array_filter([
            'mahasiswa' => (int) ($draft->target_mahasiswa_id ?: $draft->users_id) ?: null,
            'prodi' => (int) ($draft->target_prodi_id ?: $draft->prodi_id) ?: null,
            'proku' => (int) ($draft->target_proku_id ?: $draft->proku_id) ?: null,
            'kelompok' => $draft->kelompok_target ?: null,
        ], fn ($value) => filled($value));

        if ($draft->target_type && array_key_exists($draft->target_type, $candidates)) {
            $candidates = [$draft->target_type => $candidates[$draft->target_type]];
        }

        if (count($candidates) !== 1) {
            throw ValidationException::withMessages([
                'target' => 'Draft harus memiliki tepat satu target mahasiswa, program studi, program kuliah, atau kelompok.',
            ]);
        }

        $targetType = array_key_first($candidates);

        return [
            'target_type' => $targetType,
            'target_mahasiswa_id' => $targetType === 'mahasiswa' ? $candidates[$targetType] : null,
            'target_prodi_id' => $targetType === 'prodi' ? $candidates[$targetType] : null,
            'target_proku_id' => $targetType === 'proku' ? $candidates[$targetType] : null,
            'kelompok_target' => $targetType === 'kelompok' ? $candidates[$targetType] : null,
        ];
    }
}
