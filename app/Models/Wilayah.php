<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    protected $table = 'wilayah';

    public $incrementing = false;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['code', 'name', 'level', 'parent_code'];

    public static function children(?string $parentCode): \Illuminate\Support\Collection
    {
        return static::query()
            ->where('parent_code', $parentCode)
            ->orderBy('name')
            ->pluck('name', 'code');
    }

    public static function provinces(): \Illuminate\Support\Collection
    {
        return static::query()
            ->where('level', 'province')
            ->orderBy('name')
            ->pluck('name', 'code');
    }
}
