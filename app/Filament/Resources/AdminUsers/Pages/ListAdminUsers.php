<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\AdminUserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListAdminUsers extends ListRecords
{
    protected static string $resource = AdminUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Yeni yönetici')
                ->icon(Heroicon::Plus)
                ->url(fn (): string => AdminUserResource::getUrl('create')),
        ];
    }
}
