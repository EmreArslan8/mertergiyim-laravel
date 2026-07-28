<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\Multilingual;
use App\Filament\Support\StorageUpload;
use App\Models\Product;
use App\Services\AdminOptionService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
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
            ->columns(2)
            ->components([
                Section::make('Ürün bilgileri')
                    ->extraAttributes(['class' => 'merter-product-fields'])
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Multilingual::turkish('name', 'Ürün Adı')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $get, $set) => $set('slug', self::slug($state, $get('code'))))
                            ->columnSpanFull(),
                        TextInput::make('code')
                            ->label('Ürün kodu')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Örn: MG-1001')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $get, $set) => $set('slug', self::slug($get('name.tr'), $state))),
                        Select::make('category_id')
                            ->label('Kategori')
                            ->options(fn () => app(AdminOptionService::class)->categories())
                            ->searchable()
                            ->preload(),
                        Hidden::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn ($state, $get) => $state ?: self::slug($get('name.tr'), $get('code'))),
                        TextInput::make('price_try')
                            ->label('Fiyat')
                            ->numeric()
                            ->step('0.01')
                            ->prefix('₺')
                            ->required()
                            ->helperText('USD ve EUR güncel kurdan otomatik hesaplanır.'),
                        Select::make('stock_status')
                            ->label('Stok durumu')
                            ->options([
                                'in_stock' => 'Stokta',
                                'low_stock' => 'Son ürünler',
                                'out_of_stock' => 'Tükendi',
                            ])
                            ->default('in_stock')
                            ->required(),
                        TextInput::make('video_url')
                            ->label('Video bağlantısı')
                            ->url()
                            ->columnSpanFull(),
                        Multilingual::turkish('description', 'Açıklama', long: true)
                            ->columnSpanFull(),
                        Toggle::make('active')->label('Yayında')->default(true),
                    ]),

                Section::make('Görseller ve varyantlar')
                    ->extraAttributes(['class' => 'merter-product-media'])
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('images')
                            ->label('Ürün Görselleri')
                            ->relationship('images')
                            ->orderColumn('sort_order')
                            ->schema([
                                StorageUpload::image('storage_path', 'products')
                                    ->label('Görsel')
                                    ->required()
                                    ->columnSpanFull(),
                                Toggle::make('is_primary')
                                    ->label('Ana görsel'),
                                TextInput::make('sort_order')
                                    ->label('Sıra')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Görsel ekle')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => isset($state['storage_path']) ? basename((string) $state['storage_path']) : 'Yeni görsel'),

                        Select::make('variant_size_ids')
                            ->label('Bedenleri seçin')
                            ->options(fn () => app(AdminOptionService::class)->sizes())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn (Select $component, ?Product $record) => $component->state(
                                ($record?->variants ?? collect())
                                    ->pluck('size_id')
                                    ->filter()
                                    ->unique()
                                    ->values()
                                    ->all() ?? []
                            ))
                            ->afterStateUpdated(fn ($get, $set) => self::generateVariants($get, $set)),
                        Select::make('variant_color_ids')
                            ->label('Renkleri seçin')
                            ->options(fn () => app(AdminOptionService::class)->colors())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn (Select $component, ?Product $record) => $component->state(
                                ($record?->variants ?? collect())
                                    ->pluck('color_id')
                                    ->filter()
                                    ->unique()
                                    ->values()
                                    ->all() ?? []
                            ))
                            ->afterStateUpdated(fn ($get, $set) => self::generateVariants($get, $set)),

                        Repeater::make('variants')
                            ->label('Oluşan beden × renk kombinasyonları')
                            ->relationship('variants')
                            ->schema([
                                Select::make('size_id')
                                    ->label('Beden')
                                    ->options(fn () => app(AdminOptionService::class)->sizes())
                                    ->searchable()
                                    ->preload()
                                    ->wrapOptionLabels(false),
                                Select::make('color_id')
                                    ->label('Renk')
                                    ->options(fn () => app(AdminOptionService::class)->colors())
                                    ->searchable()
                                    ->preload()
                                    ->wrapOptionLabels(false),
                                TextInput::make('stock_quantity')
                                    ->label('Stok')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addable(false)
                            ->collapsible(),
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

    private static function generateVariants($get, $set): void
    {
        $sizes = array_values(array_filter((array) $get('variant_size_ids')));
        $colors = array_values(array_filter((array) $get('variant_color_ids')));

        if ($sizes === [] || $colors === []) {
            $set('variants', []);

            return;
        }

        $existing = collect((array) $get('variants'))
            ->keyBy(fn (array $variant): string => ($variant['size_id'] ?? '').'|'.($variant['color_id'] ?? ''));
        $variants = [];

        foreach ($sizes as $sizeId) {
            foreach ($colors as $colorId) {
                $key = $sizeId.'|'.$colorId;
                $variants[] = [
                    'size_id' => $sizeId,
                    'color_id' => $colorId,
                    'stock_quantity' => (int) ($existing->get($key)['stock_quantity'] ?? 0),
                ];
            }
        }

        $set('variants', $variants);
    }
}
