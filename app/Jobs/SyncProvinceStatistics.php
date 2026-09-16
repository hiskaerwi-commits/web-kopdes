<?php

namespace App\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class SyncProvinceStatistics
{
    use Dispatchable;

    public function __construct(public string $provinceCode) {}

    public function handle(): void
    {
        $provinceCode = $this->provinceCode;

        // Started detached so this never holds the web server's request worker hostage:
        // the Node/Puppeteer sync can take up to a minute, but the HTTP response is already sent.
        Process::path(base_path())->timeout(120)
            ->start(['node', 'scripts/sync-simkopdes.mjs', '--region='.$provinceCode], function (string $type, string $output) use ($provinceCode) {
                if ($type === \Symfony\Component\Process\Process::ERR) {
                    Log::warning('Simkopdes province statistics sync failed', [
                        'province_code' => $provinceCode,
                        'output' => trim($output),
                    ]);
                }
            });
    }
}
