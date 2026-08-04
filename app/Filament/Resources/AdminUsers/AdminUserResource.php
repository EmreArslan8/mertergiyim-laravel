<?php

namespace App\Filament\Resources\AdminUsers;

use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use App\Filament\Resources\AdminUsers\Pages\ListAdminUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdminUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Admin Ayarları';

    protected static ?int $navigationSort = 160;

    protected static ?string $modelLabel = 'Yönetici';

    protected static ?string $pluralModelLabel = 'Yöneticiler';

    public static function canAccess(): bool
    {
        return filament()->auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Yönetici hesabı')->schema([
                TextInput::make('name')->label('Ad soyad')->required(),
                TextInput::make('email')->label('E-posta')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Şifre')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state)),
                Select::make('role')->label('Yetki')->options([
                    'super_admin' => 'Süper Yönetici',
                    'editor' => 'İçerik Editörü',
                    'order_manager' => 'Satış Elemanı',
                ])->default('editor')->required(),
                Toggle::make('is_active')->label('Hesap aktif')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->stackedOnMobile()->columns([
            TextColumn::make('name')->label('Yönetici')->searchable(),
            TextColumn::make('email')->label('E-posta')->searchable(),
            TextColumn::make('role')->label('Yetki')->badge()->formatStateUsing(fn ($state) => match ($state) {
                'super_admin' => 'Süper Yönetici',
                'order_manager' => 'Satış Elemanı',
                default => 'İçerik Editörü',
            }),
            IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])->recordActions([
            EditAction::make()
                ->url(fn ($record): string => self::getUrl('edit', ['record' => $record])),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminUsers::route('/'),
            'create' => CreateAdminUser::route('/create'),
            'edit' => EditAdminUser::route('/{record}/edit'),
        ];
    }
}
