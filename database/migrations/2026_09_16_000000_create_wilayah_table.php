<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            $table->string('code', 13)->primary();
            $table->string('name', 100);
            $table->enum('level', ['province', 'regency', 'district', 'village']);
            $table->string('parent_code', 13)->nullable();
            $table->index('parent_code');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
