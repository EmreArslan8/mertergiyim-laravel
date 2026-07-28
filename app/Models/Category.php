<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasUuidKey;

    protected $guarded = [];

    protected $casts = ['active' => 'boolean', 'name_i18n' => 'array'];

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            $category->name = $category->name_i18n['tr'] ?? $category->name;

            if (blank($category->slug)) {
                $category->slug = Str::slug((string) $category->name);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
