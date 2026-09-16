<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->text('hero_title')->nullable();
            $table->string('hero_image_path')->nullable();
        });
        DB::table('site_settings')->where('about', 'Ruang bagi warga untuk bertumbuh bersama. Melalui koperasi, potensi lokal, usaha masyarakat, dan semangat gotong royong dapat saling terhubung untuk membangun ekonomi desa.')
            ->update(['about' => config('portal.about')]);
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['hero_title', 'hero_image_path']));
    }
};
