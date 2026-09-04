<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('f_b_perkuliahans', function (Blueprint $table) {
            $table->json('fb_answers')->nullable()->after('fb_reason');
            $table->decimal('fb_average_score', 3, 2)->nullable()->after('fb_answers');
        });
    }

    public function down(): void
    {
        Schema::table('f_b_perkuliahans', function (Blueprint $table) {
            $table->dropColumn(['fb_answers', 'fb_average_score']);
        });
    }
};
