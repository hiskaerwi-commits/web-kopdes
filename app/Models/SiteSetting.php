<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['name', 'logo_path', 'favicon_path', 'hero_title', 'hero_image_path', 'province_code', 'regency_code', 'district_code', 'village_code', 'tagline', 'about', 'address', 'email', 'phone', 'hours', 'services', 'meta_title', 'meta_description', 'meta_keywords', 'og_image_path'];

    protected function casts(): array
    {
        return ['services' => 'array'];
    }

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'name' => 'Koperasi Desa Merah Putih',
            'tagline' => 'Dari desa, untuk kesejahteraan bersama.',
            'about' => config('portal.about'),
            'services' => [],
        ]);
    }

    public function provinceName(): ?string
    {
        return $this->province_code ? Wilayah::query()->find($this->province_code)?->name : null;
    }

    public function scopeCode(): ?string
    {
        return $this->village_code ?: $this->district_code ?: $this->regency_code ?: $this->province_code;
    }

    public function regencyName(): ?string
    {
        return $this->regency_code ? Wilayah::query()->find($this->regency_code)?->name : null;
    }

    public function districtName(): ?string
    {
        return $this->district_code ? Wilayah::query()->find($this->district_code)?->name : null;
    }

    public function villageName(): ?string
    {
        return $this->village_code ? Wilayah::query()->find($this->village_code)?->name : null;
    }

    public function regionBreadcrumb(): ?string
    {
        return collect([$this->villageName(), $this->districtName(), $this->regencyName(), $this->provinceName()])
            ->filter()
            ->implode(' · ') ?: null;
    }
}
