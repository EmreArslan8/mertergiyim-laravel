<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\TranslationUsage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StorefrontStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $usage = TranslationUsage::query()
            ->selectRaw('count(*) as requests, coalesce(sum(total_tokens), 0) as tokens')
            ->first();

        return [
            Stat::make('Yayındaki ürün', (string) Product::query()->where('active', true)->count())
                ->description('Toplam '.Product::query()->count().' ürün')
                ->color('success'),

            Stat::make('Yeni sipariş', (string) Order::query()->where('status', 'new')->count())
                ->description('Toplam '.Order::query()->count().' sipariş')
                ->color('info'),

            Stat::make('Çeviri token', number_format((int) ($usage->tokens ?? 0), 0, ',', '.'))
                ->description(($usage->requests ?? 0).' Gemini isteği')
                ->color('gray'),
        ];
    }
}
