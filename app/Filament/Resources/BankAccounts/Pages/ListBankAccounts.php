<?php

namespace App\Filament\Resources\BankAccounts\Pages;

use App\Filament\Resources\BankAccounts\BankAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListBankAccounts extends ListRecords
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Banka hesabı ekle')
                ->modalDescription('Müşterilere gösterilecek banka ve hesap bilgilerini ekleyin.')
                ->modalWidth(Width::SixExtraLarge)
                ->stickyModalHeader()
                ->stickyModalFooter()
                ->extraModalWindowAttributes(['class' => 'merter-bank-account-modal']),
        ];
    }
}
