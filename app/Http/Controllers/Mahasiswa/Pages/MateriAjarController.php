<?php

namespace App\Http\Controllers\Mahasiswa\Pages;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use App\Models\MateriAjar;
use App\Models\PenawaranMataKuliah;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MateriAjarController extends Controller
{
    public function index(AcademicPeriodContext $context): View
    {
        $student = Auth::guard('mahasiswa')->user();
        $period = $context->published();
        $offerings = $this->accessibleOfferings($student->id, $period?->id)
            ->with([
                'masterMataKuliah',
                'kelas',
                'dosenUtama',
                'materiAjars' => fn ($query) => $query->with('dosen')->latest(),
            ])
            ->orderBy('code')
            ->get();

        return view('mahasiswa.pages.materi-ajar-index', [
            'web' => webSettings::query()->first(),
            'period' => $period,
            'offerings' => $offerings,
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
