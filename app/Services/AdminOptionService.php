<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Color;
use App\Models\Size;
use Illuminate\Support\Facades\Cache;

class AdminOptionService
{
    public const CACHE_KEYS = [
        'admin:options:categories',
        'admin:options:sizes',
        'admin:options:colors',
    ];

    public function categories(): array
    {
        return Cache::rememberForever(self::CACHE_KEYS[0], fn () =>
            Category::query()->orderBy('name')->pluck('name', 'id')->all()
        );
    }

    public function sizes(): array
    {
        return Cache::rememberForever(self::CACHE_KEYS[1], fn () =>
            Size::query()->where('active', true)->orderBy('sort_order')->pluck('name', 'id')->all()
        );
    }

    public function colors(): array
    {
        return Cache::rememberForever(self::CACHE_KEYS[2], fn () =>
            Color::query()->where('active', true)->orderBy('sort_order')->pluck('name', 'id')->all()
        );
    }

    public static function flush(): void
    {
        foreach (self::CACHE_KEYS as $key) {
            Cache::forget($key);
        }
    }
}
