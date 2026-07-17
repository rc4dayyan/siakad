<?php

namespace App\Http\Controllers\Admin;

use Alert;
use App\Http\Controllers\Controller;
use App\Models\TahunAkademik;
use App\Services\Academic\AcademicPeriodContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcademicPeriodContextController extends Controller
{
    public function update(Request $request, AcademicPeriodContext $context): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'exists:tahun_akademiks,code'],
        ], [
            'code.required' => 'Pilih periode akademik terlebih dahulu.',
            'code.exists' => 'Periode akademik yang dipilih tidak ditemukan.',
        ]);

        $period = TahunAkademik::where('code', $validated['code'])->firstOrFail();
        $context->select($period, $request->user());

        Alert::success('Periode dipilih', 'Konteks data diubah ke '.$period->name.'.');

        return back();
    }
}
