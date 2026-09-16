<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cooperative_networks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region');
            $table->string('url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $existing = SiteSetting::query()->value('networks');

        if ($existing) {
            $networks = json_decode($existing, true) ?: [];

            foreach ($networks as $index => $network) {
                if (empty($network['name']) || empty($network['region'])) {
                    continue;
                }

                DB::table('cooperative_networks')->insert([
                    'name' => $network['name'],
                    'region' => $network['region'],
                    'url' => $network['url'] ?? null,
                    'sort_order' => $index,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('networks');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('networks')->nullable();
        });

        Schema::dropIfExists('cooperative_networks');
    }
};
