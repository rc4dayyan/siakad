<?php

namespace App\Http\Controllers\Mahasiswa\Pages;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use App\Models\MateriAjar;
use App\Models\PenawaranMataKuliah;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MateriAjarController extends Controller
{
    public function index(AcademicPeriodContext $context, ?Request $request = null): View
    {
        $request ??= request();
        $student = Auth::guard('mahasiswa')->user();
        $period = $context->published();
        $filters = [
            'q' => trim((string) $request->query('q')),
            'availability' => in_array($request->query('availability'), ['tersedia', 'belum_tersedia'], true)
                ? $request->query('availability')
                : null,
            'attachment' => in_array($request->query('attachment'), ['dengan_file', 'tanpa_file'], true)
                ? $request->query('attachment')
                : null,
        ];
        $offerings = $this->accessibleOfferings($student->id, $period?->id)
            ->with([
                'masterMataKuliah',
                'kelas',
                'dosenUtama',
                'materiAjars' => fn ($query) => $query->with('dosen')->latest(),
            ])
            ->orderBy('code')
            ->get();
        $allMaterials = $offerings->flatMap->materiAjars;
        $summary = [
            'courses' => $offerings->count(),
            'materials' => $allMaterials->count(),
            'attachments' => $allMaterials->whereNotNull('file_path')->count(),
            'latest' => $allMaterials->max('created_at'),
        ];
        $offerings = $offerings
            ->when($filters['q'], function ($items, $search) {
                $needle = mb_strtolower($search);

                return $items->filter(function (PenawaranMataKuliah $offering) use ($needle): bool {
                    $courseMatches = str_contains(mb_strtolower(implode(' ', [
                        $offering->masterMataKuliah?->code,
                        $offering->masterMataKuliah?->name,
                        $offering->code,
                        $offering->kelas?->name,
                        $offering->dosenUtama?->dsn_name,
                    ])), $needle);
                    $materialMatches = $offering->materiAjars->contains(fn (MateriAjar $material) => str_contains(
                        mb_strtolower(implode(' ', [
                            $material->judul,
                            $material->deskripsi,
                            $material->file_name,
                            $material->dosen?->dsn_name,
                        ])),
                        $needle
                    ));

                    return $courseMatches || $materialMatches;
                });
            })
            ->when($filters['availability'] === 'tersedia', fn ($items) => $items->filter(fn (PenawaranMataKuliah $offering) => $offering->materiAjars->isNotEmpty()))
            ->when($filters['availability'] === 'belum_tersedia', fn ($items) => $items->filter(fn (PenawaranMataKuliah $offering) => $offering->materiAjars->isEmpty()))
            ->when($filters['attachment'] === 'dengan_file', fn ($items) => $items->filter(fn (PenawaranMataKuliah $offering) => $offering->materiAjars->contains(fn (MateriAjar $material) => filled($material->file_path))))
            ->when($filters['attachment'] === 'tanpa_file', fn ($items) => $items->filter(fn (PenawaranMataKuliah $offering) => $offering->materiAjars->contains(fn (MateriAjar $material) => blank($material->file_path))))
            ->values();

        return view('mahasiswa.pages.materi-ajar-index', [
            'web' => webSettings::query()->first(),
            'period' => $period,
            'offerings' => $offerings,
            'filters' => $filters,
            'summary' => $summary,
        ]);
    }

    public function download(MateriAjar $materi, AcademicPeriodContext $context)
    {
        $student = Auth::guard('mahasiswa')->user();
        $period = $context->published();
        abort_unless(
            $this->accessibleOfferings($student->id, $period?->id)
                ->whereKey($materi->penawaran_mata_kuliah_id)
                ->exists(),
            404
        );
        abort_unless($materi->file_path && Storage::disk('local')->exists($materi->file_path), 404);

        return Storage::disk('local')->download(
            $materi->file_path,
            $materi->file_name,
            ['Content-Type' => $materi->mime_type ?: 'application/octet-stream']
        );
    }

    private function accessibleOfferings(int $studentId, ?int $periodId): Builder
    {
        return PenawaranMataKuliah::query()
            ->when($periodId, fn (Builder $query) => $query->where('taka_id', $periodId))
            ->when(! $periodId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->whereHas('krsItems', fn (Builder $items) => $items->whereHas(
                'krs',
                fn (Builder $krs) => $krs
                    ->whereIn('status', [Krs::STATUS_APPROVED, Krs::STATUS_LOCKED])
                    ->whereHas('registrasiMahasiswa', fn (Builder $registration) => $registration
                        ->where('mahasiswa_id', $studentId)
                        ->where('taka_id', $periodId))
            ));
    }
}
