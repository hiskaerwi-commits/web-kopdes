<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperative_networks', fn (Blueprint $table) => $table->string('region_code', 13)->nullable()->index());
    }

    public function down(): void
    {
        Schema::table('cooperative_networks', fn (Blueprint $table) => $table->dropColumn('region_code'));
    }
};
