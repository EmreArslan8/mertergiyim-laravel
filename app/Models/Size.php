<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = ['active' => 'boolean', 'sort_order' => 'integer', 'name_i18n' => 'array'];

    protected static function booted(): void
    {
        static::saving(function (Size $size): void {
            $size->name = $size->name_i18n['tr'] ?? $size->name;
        });
    }
}
