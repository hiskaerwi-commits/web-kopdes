<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CooperativeNetwork extends Model
{
    protected $fillable = [
        'name',
        'region',
        'region_code',
        'url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
