<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\Multilingual;
use App\Models\Category;
use App\Models\Currency;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Çok dilli içerik')
                    ->description('Türkçe alanları doldurup "Çevir" ile kalan 9 dili otomatik üretebilirsiniz.')
                    ->schema([
                        Multilingual::translateAction([
                            'name' => 'Ürün adı',
                            'description' => 'Açıklama',
                        ]),
                        Multilingual::tabs('name', 'Ürün adı'),
                        Multilingual::tabs('description', 'Açıklama', long: true),
                    ]),

                Section::make('Ürün bilgileri')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Ürün kodu')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Örn: MG-1001')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $get, $set) => $set('slug', self::slug($get('name.tr'), $state))),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Ürün URL\'si: /tr/product/<slug>'),
                        Select::make('category_id')
                            ->label('Kategori')
                            ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Select::make('stock_status')
                            ->label('Stok durumu')
                            ->options([
                                'in_stock' => 'Stokta',
                                'low_stock' => 'Son ürünler',
                                'out_of_stock' => 'Tükendi',
                            ])
                            ->default('in_stock')
                            ->required(),
                        TextInput::make('price')->label('Fiyat')->numeric()->step('0.01'),
                        Select::make('currency')
                            ->label('Para birimi')
                            ->options(fn () => Currency::query()->orderBy('sort_order')->pluck('code', 'code'))
                            ->default(fn () => Currency::query()->where('is_default', true)->value('code') ?? 'TRY')
                            ->required(),
                        TextInput::make('video_url')
                            ->label('Video bağlantısı')
                            ->url()
                            ->helperText('YouTube bağlantısı; ürün sayfasında gömülü oynatılır.')
                            ->columnSpanFull(),
                        Toggle::make('active')->label('Yayında')->default(true),
                    ]),
            ]);
    }

    /**
     * Kaynak paneldeki slug üretimiyle aynı: "<ad>-<kod>", Türkçe karakterler
     * sadeleştirilmiş.
     */
    private static function slug(?string $name, ?string $code): string
    {
        return Str::slug(trim(($name ?? '').' '.($code ?? '')));
    }
}
