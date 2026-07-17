<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kalender_akademiks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('taka_id')->constrained('tahun_akademiks')->cascadeOnDelete();
            $table->string('kategori', 40);
            $table->string('nama');
            $table->dateTime('mulai_at');
            $table->dateTime('selesai_at');
            $table->boolean('dipublikasikan')->default(false);
            $table->timestamps();

            $table->index(['taka_id', 'kategori', 'dipublikasikan'], 'kalender_taka_kategori_index');
        });

        Schema::create('penawaran_mata_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('master_mata_kuliah_id')->constrained('master_mata_kuliahs')->restrictOnDelete();
            $table->foreignId('taka_id')->constrained('tahun_akademiks')->restrictOnDelete();
            $table->foreignId('pstudi_id')->constrained('program_studis')->restrictOnDelete();
            $table->foreignId('kuri_id')->constrained('kurikulums')->restrictOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->restrictOnDelete();
            $table->foreignId('dosen_utama_id')->constrained('dosens')->restrictOnDelete();
            $table->foreignId('dosen_pendamping_1_id')->nullable()->constrained('dosens')->nullOnDelete();
            $table->foreignId('dosen_pendamping_2_id')->nullable()->constrained('dosens')->nullOnDelete();
            $table->foreignId('prasyarat_master_id')->nullable()->constrained('master_mata_kuliahs')->nullOnDelete();
            $table->foreignId('legacy_mata_kuliah_id')->nullable()->unique()->constrained('mata_kuliahs')->nullOnDelete();
            $table->string('code')->unique();
            $table->unsignedTinyInteger('sks');
            $table->unsignedSmallInteger('kapasitas')->default(40);
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            $table->unique(
                ['master_mata_kuliah_id', 'taka_id', 'pstudi_id', 'kuri_id', 'kelas_id'],
                'penawaran_matkul_kombinasi_unique'
            );
            $table->index(['taka_id', 'pstudi_id', 'kuri_id'], 'penawaran_filter_index');
        });

        DB::table('mata_kuliahs')
            ->whereNotNull('mid')
            ->whereNotNull('kelas_id')
            ->orderBy('id')
            ->chunkById(250, function ($mataKuliahs): void {
                foreach ($mataKuliahs as $mataKuliah) {
                    DB::table('penawaran_mata_kuliahs')->insertOrIgnore([
                        'master_mata_kuliah_id' => $mataKuliah->mid,
                        'taka_id' => $mataKuliah->taka_id,
                        'pstudi_id' => $mataKuliah->pstudi_id,
                        'kuri_id' => $mataKuliah->kuri_id,
                        'kelas_id' => $mataKuliah->kelas_id,
                        'dosen_utama_id' => $mataKuliah->dosen_1,
                        'dosen_pendamping_1_id' => $mataKuliah->dosen_2,
                        'dosen_pendamping_2_id' => $mataKuliah->dosen_3,
                        'prasyarat_master_id' => DB::table('mata_kuliahs')->where('id', $mataKuliah->requ_id)->value('mid'),
                        'legacy_mata_kuliah_id' => $mataKuliah->id,
                        'code' => $mataKuliah->code,
                        'sks' => (int) $mataKuliah->bsks,
                        'kapasitas' => 40,
                        'deskripsi' => $mataKuliah->desc,
                        'created_at' => $mataKuliah->created_at,
                        'updated_at' => $mataKuliah->updated_at,
                    ]);
                }
            });

        Schema::create('krs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registrasi_mahasiswa_id')->unique()->constrained('registrasi_mahasiswas')->cascadeOnDelete();
            $table->string('status', 20)->default('draft');
            $table->unsignedTinyInteger('total_sks')->default(0);
            $table->text('catatan_mahasiswa')->nullable();
            $table->text('catatan_keputusan')->nullable();
            $table->dateTime('diajukan_at')->nullable();
            $table->dateTime('diputuskan_at')->nullable();
            $table->foreignId('diputuskan_oleh')->nullable()->constrained('dosens')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'diajukan_at']);
        });

        Schema::create('krs_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('krs_id')->constrained('krs')->cascadeOnDelete();
            $table->foreignId('penawaran_mata_kuliah_id')->constrained('penawaran_mata_kuliahs')->restrictOnDelete();
            $table->unsignedTinyInteger('sks');
            $table->timestamps();

            $table->unique(['krs_id', 'penawaran_mata_kuliah_id'], 'krs_item_penawaran_unique');
        });

        Schema::table('jadwal_kuliahs', function (Blueprint $table): void {
            $table->foreignId('penawaran_mata_kuliah_id')
                ->nullable()
                ->after('makul_id')
                ->constrained('penawaran_mata_kuliahs')
                ->nullOnDelete();
        });

        Schema::table('nilai_mahasiswas', function (Blueprint $table): void {
            $table->foreignId('penawaran_mata_kuliah_id')
                ->nullable()
                ->after('mata_kuliah_id')
                ->constrained('penawaran_mata_kuliahs')
                ->nullOnDelete();
        });

        DB::table('penawaran_mata_kuliahs')
            ->whereNotNull('legacy_mata_kuliah_id')
            ->orderBy('id')
            ->each(function ($penawaran): void {
                DB::table('jadwal_kuliahs')
                    ->where('makul_id', $penawaran->legacy_mata_kuliah_id)
                    ->update(['penawaran_mata_kuliah_id' => $penawaran->id]);
                DB::table('nilai_mahasiswas')
                    ->where('mata_kuliah_id', $penawaran->legacy_mata_kuliah_id)
                    ->update(['penawaran_mata_kuliah_id' => $penawaran->id]);
            });
    }

    public function down(): void
    {
        Schema::table('nilai_mahasiswas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('penawaran_mata_kuliah_id');
        });
        Schema::table('jadwal_kuliahs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('penawaran_mata_kuliah_id');
        });
        Schema::dropIfExists('krs_items');
        Schema::dropIfExists('krs');
        Schema::dropIfExists('penawaran_mata_kuliahs');
        Schema::dropIfExists('kalender_akademiks');
    }
};
