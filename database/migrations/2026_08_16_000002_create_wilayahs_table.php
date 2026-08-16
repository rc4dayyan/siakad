<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayahs', function (Blueprint $table) {
            $table->id();
            $table->char('code', 6)->unique();
            $table->string('kecamatan');
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->timestamps();

            $table->index(['provinsi', 'kabupaten']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayahs');
    }
};
