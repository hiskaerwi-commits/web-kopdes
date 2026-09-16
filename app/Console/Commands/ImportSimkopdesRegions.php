<?php

namespace App\Console\Commands;

use App\Models\Wilayah;
use App\Services\SimkopdesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportSimkopdesRegions extends Command
{
    protected $signature = 'simkopdes:import-regions';

    protected $description = 'Import metadata wilayah hasil sinkronisasi API Simkopdes tanpa menghapus wilayah lain';

    public function handle(): int
    {
        $provinces = SimkopdesData::provinces();
        if (! count($provinces)) {
            $this->error('Jalankan npm run sync:simkopdes terlebih dahulu.');

            return self::FAILURE;
        }
        foreach ($provinces as $province) {
            Wilayah::updateOrCreate(['code' => $province['code']], ['name' => $province['name'], 'level' => 'province', 'parent_code' => null]);
        }
        $disk = Storage::disk('local');
        $rows = $disk->exists('simkopdes/regions.json') ? (json_decode($disk->get('simkopdes/regions.json'), true) ?? []) : [];
        foreach ($rows as $row) {
            Wilayah::updateOrCreate(['code' => $row['code']], ['name' => $row['name'], 'level' => $row['level'], 'parent_code' => $row['parent_code']]);
        }
        $this->info(count($provinces).' provinsi dan '.count($rows).' wilayah turunan diimpor.');

        return self::SUCCESS;
    }
}
