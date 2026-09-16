<?php

namespace App\Console\Commands;

use App\Models\Wilayah;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportWilayah extends Command
{
    protected $signature = 'wilayah:import';

    protected $description = 'Import official Indonesian administrative region data (Kemendagri, via cahyadsn/wilayah) into the wilayah table';

    public function handle(): int
    {
        $path = resource_path('data/wilayah.sql');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $contents = file_get_contents($path);

        preg_match_all("/\('(\d{2}(?:\.\d{2}){0,2}(?:\.\d{4})?)','((?:[^'\\\\]|\\\\.)*)'\)/", $contents, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            $this->error('No wilayah rows found in the dump.');

            return self::FAILURE;
        }

        $this->info('Found '.count($matches).' rows. Importing...');

        Wilayah::query()->truncate();

        $bar = $this->output->createProgressBar(count($matches));
        $chunk = [];

        foreach ($matches as $match) {
            $code = $match[1];
            $name = str_replace(["\\'", '\\\\'], ["'", '\\'], $match[2]);
            $segments = substr_count($code, '.') + 1;

            $level = match ($segments) {
                1 => 'province',
                2 => 'regency',
                3 => 'district',
                default => 'village',
            };

            $parentCode = $segments > 1 ? substr($code, 0, strrpos($code, '.')) : null;

            $chunk[] = [
                'code' => $code,
                'name' => $name,
                'level' => $level,
                'parent_code' => $parentCode,
            ];

            if (count($chunk) >= 1000) {
                DB::table('wilayah')->insert($chunk);
                $chunk = [];
                $bar->advance(1000);
            }
        }

        if ($chunk !== []) {
            DB::table('wilayah')->insert($chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info('Import selesai: '.Wilayah::query()->count().' baris wilayah.');

        return self::SUCCESS;
    }
}
