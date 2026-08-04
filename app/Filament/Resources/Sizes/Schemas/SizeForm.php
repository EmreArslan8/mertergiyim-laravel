<?php

namespace App\Filament\Resources\Sizes\Schemas;

use App\Filament\Support\Multilingual;
use App\Models\Size;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SizeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Multilingual::turkish('name_i18n', 'Beden', legacyFallback: 'name')
                    ->unique(table: Size::class, column: 'name', ignoreRecord: true),
                // Sıra otomatik: yeni beden sona eklenir, tabloda "Manuel sırayı
                // düzenle" ile sürükle-bırakla değiştirilir. Elle input gerekmiyor.
                Toggle::make('active')->label('Aktif')->default(true),
            ]);
    }
}
