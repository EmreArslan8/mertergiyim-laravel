<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\AdminUsers\AdminUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdminUser extends EditRecord
{
    use HasBackToListAction;

    protected static string $resource = AdminUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function backToListLabel(): string
    {
        return 'Kullanıcılara dön';
    }
}
