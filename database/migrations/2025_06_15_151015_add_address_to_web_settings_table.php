<?php

use App\Models\Settings\webSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('web_settings', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->after('school_phone');
            $table->string('address')->nullable()->after('whatsapp');
        });
        webSettings::first()->update([
            'whatsapp' => '081221330033',
            'address' => 'Jl. Raya Suma No. 478 Majalengka'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('web_settings', function (Blueprint $table) {
            $table->dropColumn('whatsapp');
            $table->dropColumn('address');
        });
    }
};
