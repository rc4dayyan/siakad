<?php

namespace App\Http\Controllers\Admin\Pages\Finance;

use App\Helper\roleTrait;
use App\Http\Controllers\Controller;
use App\Models\HistoryTagihan;
use App\Models\Settings\webSettings;
use App\Services\Academic\AcademicPeriodContext;
use App\Services\Finance\ManualPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PembayaranController extends Controller
{
    use roleTrait;

    public function index(Request $request, AcademicPeriodContext $periods): View
    {
        $period = $periods->requireCurrent($request->user());
        $query = HistoryTagihan::query()
            ->where('taka_id', $period->id)
            ->with(['users', 'tagihanKuliah', 'ditinjauOleh'])
            ->latest('diajukan_at');

        return view('user.finance.pages.pembayaran-index', [
            'web' => webSettings::find(1),
            'prefix' => $this->setPrefix(),
            'period' => $period,
            'pendingPayments' => (clone $query)->where('status', HistoryTagihan::STATUS_PENDING)->get(),
            'reviewedPayments' => (clone $query)->whereIn('status', [HistoryTagihan::STATUS_PAID, HistoryTagihan::STATUS_REJECTED])->get(),
            'income' => (clone $query)
                ->where(fn ($status) => $status->where('status', HistoryTagihan::STATUS_PAID)->orWhere('stat', 1))
                ->sum('nominal'),
        ]);
    }

    public function decision(
        Request $request,
        HistoryTagihan $payment,
        ManualPaymentService $payments,
        AcademicPeriodContext $periods
    ): RedirectResponse {
        $period = $periods->requireCurrent($request->user());
        abort_unless((int) $payment->taka_id === (int) $period->id, 404);

        $data = $request->validate([
            'status' => ['required', 'in:'.HistoryTagihan::STATUS_PAID.','.HistoryTagihan::STATUS_REJECTED],
            'catatan_verifikasi' => ['nullable', 'required_if:status,'.HistoryTagihan::STATUS_REJECTED, 'string', 'max:2000'],
        ]);

        $payments->decide($payment, $request->user(), $data['status'], $data['catatan_verifikasi'] ?? null);

        return back()->with('success', $data['status'] === HistoryTagihan::STATUS_PAID
            ? 'Pembayaran berhasil dikonfirmasi lunas.'
            : 'Konfirmasi pembayaran ditolak dan dapat diajukan ulang oleh mahasiswa.');
    }

    public function proof(Request $request, HistoryTagihan $payment, AcademicPeriodContext $periods)
    {
        $period = $periods->requireCurrent($request->user());
        abort_unless((int) $payment->taka_id === (int) $period->id, 404);
        abort_unless($payment->bukti_path && Storage::disk('local')->exists($payment->bukti_path), 404);

        return Storage::disk('local')->download($payment->bukti_path);
    }
}
