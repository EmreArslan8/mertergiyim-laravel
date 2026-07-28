<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StorefrontStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $stats = DB::selectOne('
            select
                (select count(*) from products) as products,
                (select count(*) from products where active = true) as active_products,
                (select count(*) from orders) as orders,
                (select count(*) from orders where status = ?) as new_orders,
                (select count(*) from categories) as categories,
                (select count(*) from hero_slides where active = true) as slides,
                (select count(*) from content_pages where active = true) as pages,
                (select count(*) from blog_posts where active = true) as posts,
                (select count(*) from translation_usage) as translation_requests,
                (select coalesce(sum(total_tokens), 0) from translation_usage) as translation_tokens
        ', ['new']);

        return [
            Stat::make('Ürünler', (string) $stats->active_products)
                ->description('Toplam '.$stats->products.' ürün')
                ->color('success'),

            Stat::make('Siparişler', (string) $stats->new_orders)
                ->description('Toplam '.$stats->orders.' sipariş')
                ->color('info'),

            Stat::make('Kategoriler', (string) $stats->categories)
                ->description($stats->slides.' aktif slider')
                ->color('primary'),

            Stat::make('İçerikler', (string) ((int) $stats->pages + (int) $stats->posts))
                ->description($stats->pages.' sayfa · '.$stats->posts.' blog')
                ->color('warning'),

            Stat::make('Çeviri token', number_format((int) $stats->translation_tokens, 0, ',', '.'))
                ->description($stats->translation_requests.' Gemini isteği')
                ->color('gray'),
        ];
    }
}
