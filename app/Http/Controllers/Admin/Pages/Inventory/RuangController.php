<?php

namespace App\Http\Controllers\Admin\Pages\Inventory;

use Alert;
use App\Helper\roleTrait;
// SECTION ADDONS SYSTEM
// SECTION ADDONS EXTERNAL
use App\Http\Controllers\Controller;
use App\Models\Gedung;
// SECTION MODELS
use App\Models\Ruang;
use App\Models\Settings\webSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RuangController extends Controller
{
    use roleTrait;

    public function index(Request $request)
    {
        $data['web'] = webSettings::where('id', 1)->first();
        $data['prefix'] = $this->setPrefix();
        $data['gedung'] = Gedung::all();
        $data['ruang'] = Ruang::all();
        $data['openCreateModal'] = $request->boolean('buat');

        return view('user.admin.master-inventory.admin-ruang-index', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gedu_id' => 'required|integer|exists:gedungs,id',
            'type' => 'required|integer|in:0,1,2,3,4',
            'floor' => 'required|integer',
            'kapasitas' => 'required|integer|min:1|max:1000',
            'name' => 'required|string|max:255',
            'code' => 'required|alpha_num|max:5',
            'jumlah' => 'nullable|integer|min:1|max:100',
        ], [
            'jumlah.min' => 'Jumlah record minimal 1.',
            'jumlah.max' => 'Jumlah record maksimal 100.',
        ]);

        $quantity = (int) ($validated['jumlah'] ?? 1);
        $baseCode = strtoupper($validated['code']);
        $sequenceLength = max(2, strlen((string) $quantity));
        $records = collect(range(1, $quantity))->map(function (int $sequence) use ($validated, $quantity, $baseCode, $sequenceLength): array {
            $suffix = $quantity > 1 ? str_pad((string) $sequence, $sequenceLength, '0', STR_PAD_LEFT) : '';

            return [
                'gedu_id' => $validated['gedu_id'],
                'type' => $validated['type'],
                'floor' => $validated['floor'],
                'kapasitas' => $validated['kapasitas'],
                'name' => trim($validated['name']).($suffix !== '' ? ' '.$suffix : ''),
                'code' => $baseCode.$suffix,
            ];
        });

        if ($records->contains(fn (array $record): bool => strlen($record['code']) > 5)) {
            throw ValidationException::withMessages([
                'code' => "Kode dasar terlalu panjang untuk {$quantity} record. Panjang kode akhir maksimal 5 karakter.",
            ]);
        }

        if ($records->contains(fn (array $record): bool => strlen($record['name']) > 255)) {
            throw ValidationException::withMessages([
                'name' => 'Nama ruang terlalu panjang setelah ditambahkan nomor urut.',
            ]);
        }

        $existingCodes = Ruang::query()
            ->whereIn('code', $records->pluck('code'))
            ->pluck('code');

        if ($existingCodes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'code' => 'Kode ruang berikut sudah digunakan: '.$existingCodes->implode(', ').'.',
            ]);
        }

        DB::transaction(function () use ($records): void {
            Ruang::query()->insert($records->map(fn (array $record): array => [
                ...$record,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
        });

        Alert::success('success', "{$quantity} data ruang berhasil disimpan");

        return redirect()->route($this->setPrefix().'inventory.ruang-index');
    }

    public function update(Request $request, $code)
    {
        $request->validate([
            'gedu_id' => 'required|integer',
            'type' => 'required|integer',
            'floor' => 'required|integer',
            'kapasitas' => 'required|integer|min:1|max:1000',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:5',
        ]);

        $ruang = Ruang::where('code', $code)->first();
        $ruang->gedu_id = $request->gedu_id;
        $ruang->type = $request->type;
        $ruang->floor = $request->floor;
        $ruang->kapasitas = $request->kapasitas;
        $ruang->name = $request->name;
        $ruang->code = $request->code;
        $ruang->save();

        Alert::success('success', 'Data telah berhasil diupdate');

        return back();
    }

    public function destroy(Request $request, $code)
    {

        $ruang = Ruang::where('code', $code)->first();
        $ruang->delete();

        Alert::success('success', 'Data telah berhasil dihapus');

        return back();
    }
}
