<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ManagedResource;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactMessageResource extends ManagedResource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'İletişim Mesajları';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'iletişim mesajı';

    protected static ?string $pluralModelLabel = 'iletişim mesajları';

    public static function canCreate(): bool
    {
        return false;
    }


    /**
     * Okunmamış mesaj sayısı: sipariş rozetiyle aynı amaç, bekleyen işi
     * menüde göstermek.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->where('read', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Gönderen')->columns(2)->schema([
                TextInput::make('name')->label('Ad soyad')->disabled(),
                TextInput::make('phone')->label('Telefon')->disabled(),
                TextInput::make('email')->label('E-posta')->disabled(),
                TextInput::make('subject')->label('Konu')->disabled(),
                Textarea::make('message')->label('Mesaj')->rows(8)->disabled()->columnSpanFull(),
                Toggle::make('read')->label('Okundu'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Gönderen')->searchable()
                    ->description(fn ($record) => $record->phone),
                TextColumn::make('subject')->label('Konu')->placeholder('Genel'),
                TextColumn::make('message')->label('Mesaj')->limit(55)->wrap(),
                TextColumn::make('locale')->label('Dil')->badge(),
                IconColumn::make('read')->label('Okundu')->boolean(),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i'),
            ])
            ->recordActions([
                EditAction::make()->label('Görüntüle'),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListContactMessages::route('/')];
    }
}
