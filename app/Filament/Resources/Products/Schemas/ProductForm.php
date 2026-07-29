<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\Multilingual;
use App\Filament\Support\StorageUpload;
use App\Models\Product;
use App\Services\AdminOptionService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Ürün formu')
                    ->id('product-form-tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Bilgiler')
                            ->schema([
                                Section::make('Ürün bilgileri')
                                    ->extraAttributes(['class' => 'merter-product-fields'])
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
                                            ->preload()
                                            ->required(),
                                        TextInput::make('pack_size')
                                            ->label('Paket adedi')
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(1)
                                            ->required()
                                            ->placeholder('Örn: 5')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, $set): void {
                                                if ((int) $state <= 1) {
                                                    $set('pack_contents', []);
                                                }
                                            })
                                            ->helperText('Her renk aynı beden dağılımını kullanır. Örn. 5 girildiğinde beden adetlerinin toplamı da 5 olmalıdır.'),
                                        Hidden::make('slug')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->dehydrateStateUsing(fn ($state, $get) => $state ?: self::slug($get('name.tr'), $get('code'))),
                                        TextInput::make('price_try')
                                            ->label('Fiyat')
                                            ->numeric()
                                            ->minValue(0)
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
                                        Multilingual::turkish('description', 'Açıklama', long: true, required: false)
                                            ->columnSpanFull(),
                                        Toggle::make('active')
                                            ->label('Yayında')
                                            ->default(true)
                                            ->visibleOn('edit'),
                                    ]),
                            ]),

                        Tab::make('Görseller')
                            ->schema([
                                Section::make('Ürün görselleri')
                                    ->description('Görselleri ekleyin, sürükleyerek sıralayın ve kapak görselini seçin.')
                                    ->extraAttributes(['class' => 'merter-product-media'])
                                    ->schema([
                                        Repeater::make('images')
                                            ->hiddenLabel()
                                            ->relationship('images')
                                            ->orderColumn('sort_order')
                                            ->required()
                                            ->minItems(1)
                                            ->schema([
                                                StorageUpload::image('storage_path', 'products')
                                                    ->label('Görsel')
                                                    ->required()
                                                    ->columnSpanFull(),
                                                Multilingual::turkish('alt', 'Alternatif metin', required: false),
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
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => isset($state['storage_path']) ? basename((string) $state['storage_path']) : 'Yeni görsel'),
                                    ]),
                            ]),

                        Tab::make('Varyantlar')
                            ->schema([
                                Section::make('Beden ve renk seçenekleri')
                                    ->description('Beden ve renkleri seçtiğinizde stok satırları otomatik oluşturulur.')
                                    ->extraAttributes(['class' => 'merter-product-media'])
                                    ->schema([
                                        CheckboxList::make('variant_size_ids')
                                            ->label('Bedenleri seçin')
                                            ->options(fn () => app(AdminOptionService::class)->sizes())
                                            ->required()
                                            ->minItems(1)
                                            ->columns(4)
                                            ->gridDirection('row')
                                            ->bulkToggleable()
                                            ->live()
                                            ->dehydrated(false)
                                            ->afterStateHydrated(fn (CheckboxList $component, ?Product $record) => $component->state(
                                                ($record?->variants ?? collect())
                                                    ->pluck('size_id')
                                                    ->filter()
                                                    ->unique()
                                                    ->values()
                                                    ->all() ?? []
                                            ))
                                            ->afterStateUpdated(fn ($get, $set) => self::handleSizesUpdated($get, $set)),
                                        CheckboxList::make('variant_color_ids')
                                            ->label('Renkleri seçin')
                                            ->options(fn () => app(AdminOptionService::class)->colorsWithSwatches())
                                            ->allowHtml()
                                            ->required()
                                            ->minItems(1)
                                            ->columns(4)
                                            ->gridDirection('row')
                                            ->bulkToggleable()
                                            ->live()
                                            ->dehydrated(false)
                                            ->afterStateHydrated(fn (CheckboxList $component, ?Product $record) => $component->state(
                                                ($record?->variants ?? collect())
                                                    ->pluck('color_id')
                                                    ->filter()
                                                    ->unique()
                                                    ->values()
                                                    ->all() ?? []
                                            ))
                                            ->afterStateUpdated(fn ($get, $set) => self::generateVariants($get, $set)),

                                        Repeater::make('pack_contents')
                                            ->label('Paket içeriği')
                                            ->afterStateHydrated(function (Repeater $component, mixed $state, ?Product $record): void {
                                                if (filled($state) || (int) ($record?->pack_size ?? 1) <= 1) {
                                                    return;
                                                }

                                                $component->state(
                                                    ($record?->variants ?? collect())
                                                        ->pluck('size_id')
                                                        ->filter()
                                                        ->unique()
                                                        ->values()
                                                        ->map(fn ($sizeId): array => [
                                                            'size_id' => $sizeId,
                                                            'quantity' => 1,
                                                        ])
                                                        ->all()
                                                );
                                            })
                                            ->helperText(fn (Get $get): string => self::packageTotalMessage(
                                                (array) $get('pack_contents'),
                                                (int) $get('pack_size'),
                                            ))
                                            ->schema([
                                                Select::make('size_id')
                                                    ->label('Beden')
                                                    ->options(fn () => app(AdminOptionService::class)->sizes())
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->required(),
                                                TextInput::make('quantity')
                                                    ->label('Paket içindeki adet')
                                                    ->numeric()
                                                    ->integer()
                                                    ->minValue(1)
                                                    ->required()
                                                    ->live(onBlur: true),
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->collapsible(false)
                                            ->visible(fn (Get $get): bool => (int) $get('pack_size') > 1)
                                            ->dehydrated(fn (Get $get): bool => (int) $get('pack_size') > 1)
                                            ->rule(fn (Get $get) => function (string $attribute, mixed $value, callable $fail) use ($get): void {
                                                $packSize = (int) $get('pack_size');

                                                if ($packSize <= 1) {
                                                    return;
                                                }

                                                $total = collect((array) $value)->sum(
                                                    fn (array $item): int => max(0, (int) ($item['quantity'] ?? 0))
                                                );

                                                if ($total !== $packSize) {
                                                    $fail("Paket içeriği toplamı {$packSize} adet olmalıdır. Şu an {$total} adet.");
                                                }
                                            }),

                                        View::make('filament.forms.product-stock-matrix'),

                                        Repeater::make('variants')
                                            ->relationship('variants')
                                            ->schema([
                                                Hidden::make('size_id'),
                                                Hidden::make('color_id'),
                                                TextInput::make('stock_quantity')
                                                    ->label('Beden stoğu (adet)')
                                                    ->numeric()
                                                    ->integer()
                                                    ->minValue(0)
                                                    ->default(0),
                                            ])
                                            ->defaultItems(0)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->hidden()
                                            ->dehydratedWhenHidden()
                                            ->saveRelationshipsWhenHidden(),
                                    ]),
                            ]),
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

    private static function handleSizesUpdated($get, $set): void
    {
        self::generateVariants($get, $set);

        $sizes = array_values(array_filter((array) $get('variant_size_ids')));
        $existing = collect((array) $get('pack_contents'))
            ->keyBy(fn (array $item): string => (string) ($item['size_id'] ?? ''));

        $set('pack_contents', array_map(fn ($sizeId): array => [
            'size_id' => $sizeId,
            'quantity' => max(1, (int) ($existing->get((string) $sizeId)['quantity'] ?? 1)),
        ], $sizes));
    }

    /**
     * @param  array<int, array<string, mixed>>  $contents
     */
    private static function packageTotalMessage(array $contents, int $packSize): string
    {
        $total = collect($contents)->sum(
            fn (array $item): int => max(0, (int) ($item['quantity'] ?? 0))
        );

        return "Paket toplamı: {$total} / ".max(1, $packSize).' adet';
    }
}
