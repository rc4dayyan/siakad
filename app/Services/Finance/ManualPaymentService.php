<?php

namespace App\Services\Finance;

use App\Models\Balance;
use App\Models\HistoryTagihan;
use App\Models\Mahasiswa;
use App\Models\TagihanKuliah;
use App\Models\User;
use App\Services\Academic\AcademicAuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ManualPaymentService
{
    public function __construct(private readonly AcademicAuditService $audit) {}

    public function submit(TagihanKuliah $bill, Mahasiswa $student, UploadedFile $proof, array $data): HistoryTagihan
    {
        $this->assertSubmittable($bill, $student);

        $path = $proof->store('payment-proofs', 'local');
        if (! $path) {
            $this->reject('bukti_pembayaran', 'Bukti pembayaran gagal disimpan. Silakan coba kembali.');
        }

        try {
            $payment = DB::transaction(function () use ($bill, $student, $path, $data): HistoryTagihan {
                TagihanKuliah::query()->whereKey($bill->id)->lockForUpdate()->firstOrFail();
                $this->assertSubmittable($bill, $student);

                return HistoryTagihan::create([
                    'users_id' => $student->id,
                    'stat' => 0,
                    'tagihan_code' => $bill->code,
                    'desc' => $data['note'] ?? 'Konfirmasi pembayaran manual '.$bill->code,
                    'code' => 'BYR-'.Str::upper(Str::random(16)),
                    'tagihan_kuliah_id' => $bill->id,
                    'taka_id' => $bill->taka_id,
                    'nominal' => $bill->nominal,
                    'status' => HistoryTagihan::STATUS_PENDING,
                    'bukti_path' => $path,
                    'tanggal_transfer' => $data['tanggal_transfer'],
                    'nama_pengirim' => $data['nama_pengirim'],
                    'diajukan_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        $this->audit->record('payment.manual_submitted', $payment, $bill->taka_id, $student, null,
            ['status' => HistoryTagihan::STATUS_PENDING], ['bill_id' => $bill->id]);

        return $payment;
    }

    public function decide(HistoryTagihan $payment, User $actor, string $decision, ?string $note): HistoryTagihan
    {
        abort_unless(in_array((int) $actor->raw_type, [0, 1], true), 403);

        if (! in_array($decision, [HistoryTagihan::STATUS_PAID, HistoryTagihan::STATUS_REJECTED], true)) {
            $this->reject('status', 'Keputusan pembayaran tidak valid.');
        }
        if ($decision === HistoryTagihan::STATUS_REJECTED && blank($note)) {
            $this->reject('catatan_verifikasi', 'Catatan wajib diisi ketika pembayaran ditolak.');
        }

        $before = $payment->status;
        $payment = DB::transaction(function () use ($payment, $actor, $decision, $note): HistoryTagihan {
            $locked = HistoryTagihan::query()->lockForUpdate()->findOrFail($payment->id);
            if ($locked->status !== HistoryTagihan::STATUS_PENDING) {
                $this->reject('payment', 'Hanya pembayaran yang masih menunggu yang dapat diputuskan.');
            }

            $locked->update([
                'status' => $decision,
                'stat' => $decision === HistoryTagihan::STATUS_PAID ? 1 : 0,
                'dibayar_at' => $decision === HistoryTagihan::STATUS_PAID ? now() : null,
                'ditinjau_at' => now(),
                'ditinjau_oleh' => $actor->id,
                'catatan_verifikasi' => $note,
            ]);

            if ($decision === HistoryTagihan::STATUS_PAID) {
                Balance::firstOrCreate(
                    ['desc' => 'Reff pembayaran mahasiswa #'.$locked->code],
                    ['value' => $locked->nominal, 'type' => 1, 'code' => (string) Str::uuid()]
                );
            }

            return $locked->refresh();
        });

        $this->audit->record('payment.manual_decided', $payment, $payment->taka_id, $actor,
            ['status' => $before], ['status' => $decision], ['has_note' => filled($note)]);

        return $payment;
    }

    private function reject(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }

    private function assertSubmittable(TagihanKuliah $bill, Mahasiswa $student): void
    {
        if ($bill->target_type !== 'mahasiswa' || (int) $bill->target_mahasiswa_id !== (int) $student->id) {
            $this->reject('payment', 'Tagihan tidak terdaftar untuk mahasiswa ini.');
        }

        $payments = HistoryTagihan::query()
            ->where('tagihan_kuliah_id', $bill->id)
            ->where('users_id', $student->id);

        if ((clone $payments)->where(fn ($query) => $query->where('status', HistoryTagihan::STATUS_PAID)->orWhere('stat', 1))->exists()) {
            $this->reject('payment', 'Tagihan ini sudah dinyatakan lunas.');
        }

        if ((clone $payments)->where('status', HistoryTagihan::STATUS_PENDING)->exists()) {
            $this->reject('payment', 'Konfirmasi pembayaran masih menunggu pemeriksaan petugas.');
        }
    }
}
