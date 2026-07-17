<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahun_akademiks', function (Blueprint $table) {
            $table->year('year_end')->nullable()->after('year_start');
            $table->string('term', 16)->nullable()->after('year_end');
            $table->date('starts_at')->nullable()->after('term');
            $table->date('ends_at')->nullable()->after('starts_at');
            $table->string('status', 16)->default('draft')->after('is_active');
            $table->timestamp('activated_at')->nullable()->after('status');
            $table->unsignedBigInteger('activated_by')->nullable()->after('activated_at');

            $table->index('term');
            $table->index('status');
        });

        DB::table('tahun_akademiks')
            ->where('is_active', 1)
            ->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('tahun_akademiks', function (Blueprint $table) {
            $table->dropIndex(['term']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'year_end',
                'term',
                'starts_at',
                'ends_at',
                'status',
                'activated_at',
                'activated_by',
            ]);
        });
    }
};
