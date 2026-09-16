<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['province', 'village']);
            $table->string('province_code', 13)->nullable()->after('logo_path');
            $table->string('regency_code', 13)->nullable()->after('province_code');
            $table->string('district_code', 13)->nullable()->after('regency_code');
            $table->string('village_code', 13)->nullable()->after('district_code');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['province_code', 'regency_code', 'district_code', 'village_code']);
            $table->string('village')->nullable();
            $table->string('province')->nullable();
        });
    }
};
