<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\ManagedResource;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderResource extends ManagedResource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Siparişler';

    protected static ?string $modelLabel = 'sipariş';

    protected static ?string $pluralModelLabel = 'siparişler';


    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    /**
     * Kalemler detay sayfasında salt okunur bir kart olarak gösteriliyor,
     * düzenleme o kartın başlığındaki aksiyondan yapılıyor: aynı veriyi
     * altta ikinci bir tabloda tekrar göstermenin anlamı yok.
     */
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
