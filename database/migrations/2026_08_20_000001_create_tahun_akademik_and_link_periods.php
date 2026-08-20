<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_akademik', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 32)->unique();
            $table->year('year_start');
            $table->year('year_end');
            $table->timestamps();

            $table->unique(['year_start', 'year_end'], 'tahun_akademik_year_unique');
        });

        Schema::table('tahun_akademiks', function (Blueprint $table): void {
            $table->foreignId('tid')
                ->nullable()
                ->after('id')
                ->constrained('tahun_akademik')
                ->restrictOnDelete();
        });

        $now = now();
        $academicYears = DB::table('tahun_akademiks')
            ->whereNotNull('year_start')
            ->select(['year_start', 'year_end'])
            ->get()
            ->map(fn (object $period): array => [
                'year_start' => (int) $period->year_start,
                'year_end' => (int) ($period->year_end ?: ((int) $period->year_start + 1)),
            ])
            ->unique(fn (array $year): string => $year['year_start'].'-'.$year['year_end']);

        foreach ($academicYears as $academicYear) {
            $code = $academicYear['year_start'].'-'.$academicYear['year_end'];

            DB::table('tahun_akademik')->updateOrInsert(
                [
                    'year_start' => $academicYear['year_start'],
                    'year_end' => $academicYear['year_end'],
                ],
                [
                    'name' => 'Tahun Akademik '.$academicYear['year_start'].'/'.$academicYear['year_end'],
                    'code' => $code,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $academicYearId = DB::table('tahun_akademik')
                ->where('year_start', $academicYear['year_start'])
                ->where('year_end', $academicYear['year_end'])
                ->value('id');

            DB::table('tahun_akademiks')
                ->where('year_start', $academicYear['year_start'])
                ->where(function ($query) use ($academicYear): void {
                    $query->where('year_end', $academicYear['year_end'])
                        ->orWhereNull('year_end');
                })
                ->update(['tid' => $academicYearId]);
        }
    }

    public function down(): void
    {
        Schema::table('tahun_akademiks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tid');
        });

        Schema::dropIfExists('tahun_akademik');
    }
};
