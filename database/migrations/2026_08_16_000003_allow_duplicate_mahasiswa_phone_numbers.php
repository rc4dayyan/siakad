<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table): void {
            $table->dropUnique(['mhs_phone']);
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table): void {
            $table->unique('mhs_phone');
        });
    }
};
