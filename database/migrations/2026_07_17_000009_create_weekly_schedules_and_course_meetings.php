<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ruangs', function (Blueprint $table): void {
            $table->unsignedSmallInteger('kapasitas')->default(40)->after('floor');
        });

        Schema::create('jadwal_mingguans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('penawaran_mata_kuliah_id')->nullable()->constrained('penawaran_mata_kuliahs')->restrictOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->restrictOnDelete();
            $table->foreignId('dosen_id')->constrained('dosens')->restrictOnDelete();
            $table->foreignId('ruang_id')->constrained('ruangs')->restrictOnDelete();
            $table->unsignedTinyInteger('hari');
            $table->time('mulai');
            $table->time('selesai');
            $table->string('code')->unique();
            $table->string('fingerprint', 64)->unique();
            $table->text('alasan_pengecualian')->nullable();
            $table->foreignId('pengecualian_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kelas_id', 'hari', 'mulai', 'selesai'], 'jadwal_mingguan_kelas_waktu_index');
            $table->index(['dosen_id', 'hari', 'mulai', 'selesai'], 'jadwal_mingguan_dosen_waktu_index');
            $table->index(['ruang_id', 'hari', 'mulai', 'selesai'], 'jadwal_mingguan_ruang_waktu_index');
        });

        Schema::create('pertemuan_kuliahs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jadwal_mingguan_id')->constrained('jadwal_mingguans')->cascadeOnDelete();
            $table->foreignId('legacy_jadwal_kuliah_id')->nullable()->unique()->constrained('jadwal_kuliahs')->nullOnDelete();
            $table->foreignId('dosen_id')->constrained('dosens')->restrictOnDelete();
            $table->foreignId('ruang_id')->constrained('ruangs')->restrictOnDelete();
            $table->unsignedTinyInteger('pertemuan_ke');
            $table->date('tanggal');
            $table->time('mulai');
            $table->time('selesai');
            $table->string('metode', 24)->default('tatap_muka');
            $table->text('materi')->nullable();
            $table->string('status', 24)->default('terjadwal');
            $table->string('code')->unique();
            $table->timestamps();

            $table->unique(['jadwal_mingguan_id', 'pertemuan_ke'], 'pertemuan_jadwal_nomor_unique');
            $table->unique(['jadwal_mingguan_id', 'tanggal'], 'pertemuan_jadwal_tanggal_unique');
        });

        Schema::table('absensi_mahasiswas', function (Blueprint $table): void {
            $table->foreignId('pertemuan_kuliah_id')->nullable()->after('id')->constrained('pertemuan_kuliahs')->nullOnDelete();
            $table->foreignId('krs_item_id')->nullable()->after('pertemuan_kuliah_id')->constrained('krs_items')->nullOnDelete();
            $table->index(['pertemuan_kuliah_id', 'author_id'], 'absensi_pertemuan_mahasiswa_index');
        });

        DB::table('jadwal_kuliahs')->orderBy('id')->chunkById(250, function ($schedules): void {
            foreach ($schedules as $schedule) {
                $attributes = [
                    $schedule->penawaran_mata_kuliah_id,
                    $schedule->kelas_id,
                    $schedule->dosen_id,
                    $schedule->ruang_id,
                    $schedule->days_id,
                    $schedule->start,
                    $schedule->ended,
                ];
                $fingerprint = hash('sha256', implode('|', array_map(fn ($value) => (string) $value, $attributes)));
                DB::table('jadwal_mingguans')->insertOrIgnore([
                    'penawaran_mata_kuliah_id' => $schedule->penawaran_mata_kuliah_id,
                    'kelas_id' => $schedule->kelas_id,
                    'dosen_id' => $schedule->dosen_id,
                    'ruang_id' => $schedule->ruang_id,
                    'hari' => $schedule->days_id,
                    'mulai' => $schedule->start,
                    'selesai' => $schedule->ended,
                    'code' => 'WM-'.$schedule->code,
                    'fingerprint' => $fingerprint,
                    'created_at' => $schedule->created_at,
                    'updated_at' => $schedule->updated_at,
                ]);
                $weeklyId = DB::table('jadwal_mingguans')->where('fingerprint', $fingerprint)->value('id');

                DB::table('pertemuan_kuliahs')->insertOrIgnore([
                    'jadwal_mingguan_id' => $weeklyId,
                    'legacy_jadwal_kuliah_id' => $schedule->id,
                    'dosen_id' => $schedule->dosen_id,
                    'ruang_id' => $schedule->ruang_id,
                    'pertemuan_ke' => $schedule->pert_id,
                    'tanggal' => $schedule->date,
                    'mulai' => $schedule->start,
                    'selesai' => $schedule->ended,
                    'metode' => (int) $schedule->meth_id === 1 ? 'daring' : 'tatap_muka',
                    'status' => 'terjadwal',
                    'code' => $schedule->code,
                    'created_at' => $schedule->created_at,
                    'updated_at' => $schedule->updated_at,
                ]);
            }
        });

        DB::table('absensi_mahasiswas')->orderBy('id')->chunkById(250, function ($attendances): void {
            foreach ($attendances as $attendance) {
                $meeting = DB::table('pertemuan_kuliahs')
                    ->join('jadwal_mingguans', 'jadwal_mingguans.id', '=', 'pertemuan_kuliahs.jadwal_mingguan_id')
                    ->where('pertemuan_kuliahs.code', $attendance->jadkul_code)
                    ->select(['pertemuan_kuliahs.id', 'jadwal_mingguans.penawaran_mata_kuliah_id'])
                    ->first();
                if (! $meeting) {
                    continue;
                }

                $krsItemId = DB::table('krs_items')
                    ->join('krs', 'krs.id', '=', 'krs_items.krs_id')
                    ->join('registrasi_mahasiswas', 'registrasi_mahasiswas.id', '=', 'krs.registrasi_mahasiswa_id')
                    ->where('registrasi_mahasiswas.mahasiswa_id', $attendance->author_id)
                    ->where('krs_items.penawaran_mata_kuliah_id', $meeting->penawaran_mata_kuliah_id)
                    ->whereIn('krs.status', ['approved', 'locked'])
                    ->value('krs_items.id');

                DB::table('absensi_mahasiswas')->where('id', $attendance->id)->update([
                    'pertemuan_kuliah_id' => $meeting->id,
                    'krs_item_id' => $krsItemId,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('absensi_mahasiswas', function (Blueprint $table): void {
            $table->dropIndex('absensi_pertemuan_mahasiswa_index');
            $table->dropConstrainedForeignId('krs_item_id');
            $table->dropConstrainedForeignId('pertemuan_kuliah_id');
        });
        Schema::dropIfExists('pertemuan_kuliahs');
        Schema::dropIfExists('jadwal_mingguans');
        Schema::table('ruangs', function (Blueprint $table): void {
            $table->dropColumn('kapasitas');
        });
    }
};
