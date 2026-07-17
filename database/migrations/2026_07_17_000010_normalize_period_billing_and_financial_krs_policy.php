<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_tagihans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('taka_id')->constrained('tahun_akademiks')->restrictOnDelete();
            $table->string('name');
            $table->string('jenis', 40);
            $table->unsignedBigInteger('nominal');
            $table->date('tanggal_terbit');
            $table->date('jatuh_tempo');
            $table->boolean('wajib_lunas_krs')->default(false);
            $table->string('target_type', 24);
            $table->foreignId('target_mahasiswa_id')->nullable()->constrained('mahasiswas')->restrictOnDelete();
            $table->foreignId('target_prodi_id')->nullable()->constrained('program_studis')->restrictOnDelete();
            $table->foreignId('target_proku_id')->nullable()->constrained('program_kuliahs')->restrictOnDelete();
            $table->string('kelompok_target', 40)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['taka_id', 'jenis', 'name'], 'template_tagihan_periode_jenis_name_unique');
            $table->index(['taka_id', 'target_type'], 'template_tagihan_target_index');
        });

        Schema::table('tagihan_kuliahs', function (Blueprint $table): void {
            $table->foreignId('taka_id')->nullable()->after('id')->constrained('tahun_akademiks')->restrictOnDelete();
            $table->foreignId('template_tagihan_id')->nullable()->after('taka_id')->constrained('template_tagihans')->nullOnDelete();
            $table->unsignedBigInteger('nominal')->nullable()->after('price');
            $table->date('tanggal_terbit')->nullable()->after('nominal');
            $table->date('jatuh_tempo')->nullable()->after('tanggal_terbit');
            $table->string('status', 24)->default('draft')->after('jatuh_tempo');
            $table->string('jenis', 40)->default('lainnya')->after('status');
            $table->boolean('wajib_lunas_krs')->default(false)->after('jenis');
            $table->string('target_type', 24)->nullable()->after('wajib_lunas_krs');
            $table->foreignId('target_mahasiswa_id')->nullable()->after('target_type')->constrained('mahasiswas')->restrictOnDelete();
            $table->foreignId('target_prodi_id')->nullable()->after('target_mahasiswa_id')->constrained('program_studis')->restrictOnDelete();
            $table->foreignId('target_proku_id')->nullable()->after('target_prodi_id')->constrained('program_kuliahs')->restrictOnDelete();
            $table->string('kelompok_target', 40)->nullable()->after('target_proku_id');

            $table->index(['taka_id', 'status', 'jatuh_tempo'], 'tagihan_periode_status_jatuh_tempo_index');
            $table->unique(['taka_id', 'jenis', 'target_mahasiswa_id'], 'tagihan_mahasiswa_periode_jenis_unique');
        });

        Schema::create('penerbitan_tagihan_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_tagihan_id')->constrained('template_tagihans')->restrictOnDelete();
            $table->foreignId('taka_id')->constrained('tahun_akademiks')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('calon')->default(0);
            $table->unsignedInteger('berhasil')->default(0);
            $table->unsignedInteger('dilewati')->default(0);
            $table->unsignedInteger('gagal')->default(0);
            $table->json('konfigurasi')->nullable();
            $table->text('ringkasan')->nullable();
            $table->timestamps();
        });

        Schema::table('history_tagihans', function (Blueprint $table): void {
            $table->foreignId('tagihan_kuliah_id')->nullable()->after('id')->constrained('tagihan_kuliahs')->nullOnDelete();
            $table->foreignId('taka_id')->nullable()->after('tagihan_kuliah_id')->constrained('tahun_akademiks')->restrictOnDelete();
            $table->unsignedBigInteger('nominal')->nullable()->after('stat');
            $table->string('status', 24)->default('pending')->after('nominal');
            $table->timestamp('dibayar_at')->nullable()->after('status');
            $table->index(['taka_id', 'status', 'dibayar_at'], 'pembayaran_periode_status_index');
        });

        Schema::create('override_keuangan_krs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registrasi_mahasiswa_id')->constrained('registrasi_mahasiswas')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('alasan');
            $table->timestamp('berlaku_sampai')->nullable();
            $table->timestamps();
            $table->index(['registrasi_mahasiswa_id', 'berlaku_sampai'], 'override_keuangan_registrasi_index');
        });

        DB::table('tagihan_kuliahs')->orderBy('id')->chunkById(250, function ($bills): void {
            foreach ($bills as $bill) {
                $studentId = (int) $bill->users_id > 0 && DB::table('mahasiswas')->where('id', (int) $bill->users_id)->exists()
                    ? (int) $bill->users_id : null;
                $programId = (int) $bill->prodi_id > 0 && DB::table('program_studis')->where('id', (int) $bill->prodi_id)->exists()
                    ? (int) $bill->prodi_id : null;
                $studyProgramId = (int) $bill->proku_id > 0 && DB::table('program_kuliahs')->where('id', (int) $bill->proku_id)->exists()
                    ? (int) $bill->proku_id : null;
                $periodId = $studyProgramId ? DB::table('program_kuliahs')->where('id', $studyProgramId)->value('taka_id') : null;

                if (! $periodId && $studentId) {
                    $matchingPeriods = DB::table('registrasi_mahasiswas')
                        ->join('tahun_akademiks', 'tahun_akademiks.id', '=', 'registrasi_mahasiswas.taka_id')
                        ->where('registrasi_mahasiswas.mahasiswa_id', $studentId)
                        ->whereDate('tahun_akademiks.starts_at', '<=', $bill->created_at)
                        ->whereDate('tahun_akademiks.ends_at', '>=', $bill->created_at)
                        ->pluck('registrasi_mahasiswas.taka_id');
                    $periodId = $matchingPeriods->count() === 1 ? $matchingPeriods->first() : null;
                }

                $targetCandidates = array_filter([
                    'mahasiswa' => $studentId,
                    'prodi' => $programId,
                    'proku' => $studyProgramId,
                ]);
                $targetType = count($targetCandidates) === 1 ? array_key_first($targetCandidates) : null;
                $nominal = (int) preg_replace('/[^0-9]/', '', (string) $bill->price);

                // Beberapa data legacy dapat berisi lebih dari satu tagihan tanpa jenis yang jelas.
                // Jangan menebak bahwa semuanya adalah jenis "lainnya" untuk penerima yang sama.
                if ($targetType === 'mahasiswa' && $periodId && DB::table('tagihan_kuliahs')
                    ->where('id', '!=', $bill->id)
                    ->where('taka_id', $periodId)
                    ->where('jenis', 'lainnya')
                    ->where('target_mahasiswa_id', $studentId)
                    ->exists()) {
                    $targetType = null;
                }

                DB::table('tagihan_kuliahs')->where('id', $bill->id)->update([
                    'taka_id' => $periodId,
                    'nominal' => $nominal > 0 ? $nominal : null,
                    'tanggal_terbit' => substr((string) $bill->created_at, 0, 10),
                    'status' => 'terbit',
                    'target_type' => $targetType,
                    'target_mahasiswa_id' => $targetType === 'mahasiswa' ? $studentId : null,
                    'target_prodi_id' => $targetType === 'prodi' ? $programId : null,
                    'target_proku_id' => $targetType === 'proku' ? $studyProgramId : null,
                ]);
            }
        });

        DB::table('history_tagihans')->orderBy('id')->chunkById(250, function ($payments): void {
            foreach ($payments as $payment) {
                $bill = DB::table('tagihan_kuliahs')->where('code', $payment->tagihan_code)->first();
                if (! $bill) {
                    continue;
                }
                DB::table('history_tagihans')->where('id', $payment->id)->update([
                    'tagihan_kuliah_id' => $bill->id,
                    'taka_id' => $bill->taka_id,
                    'nominal' => $bill->nominal,
                    'status' => (int) $payment->stat === 1 ? 'lunas' : 'pending',
                    'dibayar_at' => (int) $payment->stat === 1 ? $payment->updated_at : null,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('override_keuangan_krs');
        Schema::table('history_tagihans', function (Blueprint $table): void {
            $table->dropIndex('pembayaran_periode_status_index');
            $table->dropConstrainedForeignId('taka_id');
            $table->dropConstrainedForeignId('tagihan_kuliah_id');
            $table->dropColumn(['nominal', 'status', 'dibayar_at']);
        });
        Schema::dropIfExists('penerbitan_tagihan_batches');
        Schema::table('tagihan_kuliahs', function (Blueprint $table): void {
            $table->dropUnique('tagihan_mahasiswa_periode_jenis_unique');
            $table->dropIndex('tagihan_periode_status_jatuh_tempo_index');
            $table->dropConstrainedForeignId('target_proku_id');
            $table->dropConstrainedForeignId('target_prodi_id');
            $table->dropConstrainedForeignId('target_mahasiswa_id');
            $table->dropConstrainedForeignId('template_tagihan_id');
            $table->dropConstrainedForeignId('taka_id');
            $table->dropColumn(['nominal', 'tanggal_terbit', 'jatuh_tempo', 'status', 'jenis', 'wajib_lunas_krs', 'target_type', 'kelompok_target']);
        });
        Schema::dropIfExists('template_tagihans');
    }
};
