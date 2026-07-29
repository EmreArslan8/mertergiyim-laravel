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
        'admin:options:colors-with-swatches',
        'admin:options:color-details',
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

    public function colorsWithSwatches(): array
    {
        return Cache::rememberForever(self::CACHE_KEYS[3], fn () =>
            Color::query()
                ->where('active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'hex'])
                ->mapWithKeys(function (Color $color): array {
                    $hex = preg_match('/^#[0-9a-f]{6}$/i', (string) $color->hex)
                        ? $color->hex
                        : '#ffffff';

                    return [$color->id => sprintf(
                        '<span class="merter-color-option"><span class="merter-color-option__swatch" style="--merter-option-color:%s"></span><span>%s</span></span>',
                        e($hex),
                        e($color->name),
                    )];
                })
                ->all()
        );
    }

    /**
     * @return array<string, array{name: string, hex: string}>
     */
    public function colorDetails(): array
    {
        return Cache::rememberForever(self::CACHE_KEYS[4], fn () =>
            Color::query()
                ->where('active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'hex'])
                ->mapWithKeys(fn (Color $color): array => [$color->id => [
                    'name' => $color->name,
                    'hex' => preg_match('/^#[0-9a-f]{6}$/i', (string) $color->hex)
                        ? $color->hex
                        : '#ffffff',
                ]])
                ->all()
        );
    }

    public static function flush(): void
    {
        foreach (self::CACHE_KEYS as $key) {
            Cache::forget($key);
        }
    }
}
