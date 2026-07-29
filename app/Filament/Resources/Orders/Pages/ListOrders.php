<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Yeni sipariş')
                ->icon(Heroicon::Plus)
                ->url(fn (): string => OrderResource::getUrl('create')),
        ];
    }
}
