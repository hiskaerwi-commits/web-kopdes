<?php

namespace App\Services;

use App\Models\Wilayah;
use Illuminate\Support\Facades\Storage;

class SimkopdesData
{
    public static function provinces(): array
    {
        $file = resource_path('data/provinces.json');

        return is_file($file) ? (json_decode(file_get_contents($file), true)['data'] ?? []) : [];
    }

    public function summary(?string $code): array
    {
        $snapshot = $this->snapshot();
        $row = $code ? (str_contains($code, '.') ? ($snapshot['regions'][$code] ?? null) : collect($snapshot['provinces'] ?? [])->firstWhere('code', $code)) : null;
        $metrics = $code ? ($row['metrics'] ?? null) : ($snapshot['national'] ?? null);

        return [
            'code' => $code,
            'name' => $code ? ($row['name'] ?? Wilayah::find($code)?->name ?? $code) : 'Nasional',
            'metrics' => $metrics,
            'fetched_at' => $metrics ? ($row['fetched_at'] ?? $snapshot['fetched_at'] ?? null) : null,
            'source' => $row['source'] ?? $snapshot['source'] ?? 'https://api.simkopdes.go.id/api/statistics/national/phase-2',
            'rows' => $code ? [] : ($snapshot['provinces'] ?? []),
        ];
    }

    public function cards(array $summary): array
    {
        $labels = [
            'cooperatives' => 'Koperasi tercatat', 'accounts' => 'Akun koperasi',
            'active_outlets' => 'Gerai aktif', 'members' => 'Anggota tercatat',
            'members_male' => 'Anggota laki-laki', 'members_female' => 'Anggota perempuan',
            'managements' => 'Pengurus tercatat', 'partnerships' => 'Kemitraan tercatat',
            'npwp' => 'Koperasi memiliki NPWP', 'nib' => 'Koperasi memiliki NIB',
            'rat' => 'RAT tercatat', 'transaction_value' => 'Nilai transaksi',
        ];
        $cards = [];
        foreach ($labels as $key => $label) {
            $value = $summary['metrics'][$key] ?? null;
            if (! is_numeric($value)) {
                continue;
            }
            $cards[] = ['label' => $label, 'value' => ($key === 'transaction_value' ? 'Rp ' : '').number_format($value, 0, ',', '.')];
        }

        return $cards;
    }

    public function provinceDetail(string $provinceCode): ?array
    {
        return $this->snapshot()['province_details'][$provinceCode] ?? null;
    }

    private function snapshot(): array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists('simkopdes/statistics.json')) {
            return [];
        }
        $snapshot = json_decode($disk->get('simkopdes/statistics.json'), true);

        return is_array($snapshot) && ($snapshot['schema_version'] ?? null) === 1 ? $snapshot : [];
    }
}
