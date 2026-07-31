<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\Multilingual;
use App\Filament\Support\StorageUpload;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AdminOptionService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
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
                                        // searchable() Select'i JS bileşenine çeviriyor ve
                                        // seçenekleri Livewire isteğiyle çekiyor; alan açılınca
                                        // "Yükleniyor…" görünüyordu. Kategori sayısı iki haneli,
                                        // arama gereksiz: native select seçenekleri doğrudan
                                        // sayfayla birlikte basar, bekleme olmaz.
                                        Select::make('category_id')
                                            ->label('Kategori')
                                            ->options(fn () => app(AdminOptionService::class)->categories())
                                            ->required(),
                                        // Paket adedi ayrı bir alan olarak sorulmaz: ekranda iki ayrı
                                        // sayı olması (buradaki hedef ve tablodaki toplam) çelişki
                                        // üretiyordu. Tek doğru kaynak Varyantlar sekmesindeki tablo;
                                        // bu alan yalnızca dağıtım hedefini taşır ve kaydedilirken
                                        // tablonun toplamına eşitlenir.
                                        Hidden::make('pack_size')
                                            ->default(fn (): int => max(1, (int) config('storefront.pack.default_size', 1)))
                                            ->dehydrateStateUsing(fn ($state, Get $get): int => self::packTotal($get) ?: max(1, (int) $state)),

                                        // Bilgiler sekmesinde paket adedi görünür ama düzenlenmez:
                                        // değeri Varyantlar sekmesindeki dağılımın toplamıdır.
                                        Placeholder::make('pack_size_summary')
                                            ->label('Paket adedi')
                                            ->content(function (Get $get): string {
                                                $total = self::packTotal($get);

                                                if ($total <= 1) {
                                                    return 'Tekli ürün';
                                                }

                                                return $total.' adet — '.collect((array) $get('pack_contents'))
                                                    ->map(fn (array $item): string => (app(AdminOptionService::class)->sizes()[$item['size_id'] ?? ''] ?? '?')
                                                        .'×'.max(0, (int) ($item['quantity'] ?? 0)))
                                                    ->implode(' · ');
                                            })
                                            ->helperText('Varyantlar sekmesindeki paket dağılımından hesaplanır.'),
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
                                        Multilingual::turkish('description', 'Açıklama', required: false, rich: true)
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
                                            // Gömülü repeater, ayrı görsel ilişki yöneticisinin
                                            // çeviri kancalarını çalıştırmaz; alt metni burada çevir.
                                            ->mutateRelationshipDataBeforeCreateUsing(
                                                fn (array $data, $livewire): array => $livewire->fillAutomaticTranslationsForFields(
                                                    $data,
                                                    null,
                                                    ['alt' => 'Alternatif metin'],
                                                ),
                                            )
                                            ->mutateRelationshipDataBeforeSaveUsing(
                                                fn (array $data, ProductImage $record, $livewire): array => $livewire->fillAutomaticTranslationsForFields(
                                                    $data,
                                                    $record,
                                                    ['alt' => 'Alternatif metin'],
                                                ),
                                            )
                                            ->required()
                                            ->minItems(1)
                                            ->schema([
                                                // Görsel solda dar bir önizleme; kalan alanlar sağda alt alta.
                                                StorageUpload::image('storage_path', 'products')
                                                    ->label('Görsel')
                                                    ->required()
                                                    // En/boy oranı verilirse FilePond yüksekliği
                                                    // kapsayıcı genişliğinden JS ile hesaplıyor ve
                                                    // ölçüm gelene kadar alan boş kalıyordu. Ölçü
                                                    // sabit: yükseklik CSS'te (merter-admin.css,
                                                    // `.fi-fo-file-upload`) tanımlı.
                                                    ->imagePreviewHeight('150')
                                                    ->helperText('En uzun kenar '.config('storefront.upload.max_size').'px olacak şekilde küçültülüp WebP\'e çevrilir.')
                                                    ->columnSpan(4),
                                                // Kayıtlı görsel, FilePond önizlemesi gelene kadar
                                                // alan boş beklemesin diye doğrudan basılır.
                                                StorageUpload::preview('storage_path', 'products')
                                                    ->columnSpan(4),
                                                Grid::make(6)
                                                    ->columnSpan(8)
                                                    ->schema([
                                                        Multilingual::turkish('alt', 'Alternatif metin', required: false)
                                                            ->helperText('Görsel yüklenemezse görünen ve arama motorlarının okuduğu metin.')
                                                            ->columnSpan(6),
                                                        // Sıra alanı yok: sıralama sürükle-bırak ile yapılır ve
                                                        // sort_order'ı repeater kendisi yazar. Elle girilen sayı
                                                        // ikinci bir doğruluk kaynağı olup çakışma üretiyordu.
                                                        Toggle::make('is_primary')
                                                            ->label('Ana görsel')
                                                            ->helperText('Vitrinde kapak olarak bu görsel kullanılır.')
                                                            ->inline(false)
                                                            ->live()
                                                            // Kapak tam olarak bir tane olmalı: radyo düğmesi gibi
                                                            // davranır. Açınca diğerleri kapanır; tek açık olanı
                                                            // kapatmaya çalışınca geri açılır (kapaksız ürün olamaz).
                                                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                                                // '../' repeater'ın öğe listesi; göreli yollar öğe
                                                                // kabından başlar, bir üstü liste olur.
                                                                $keys = array_keys((array) $get('../'));

                                                                if (! $state) {
                                                                    $hasOther = collect($keys)->contains(
                                                                        fn (string $key): bool => (bool) $get('../'.$key.'.is_primary')
                                                                    );

                                                                    if (! $hasOther) {
                                                                        $set('is_primary', true);

                                                                        Notification::make()
                                                                            ->title('En az bir görsel kapak olmalı.')
                                                                            ->body('Kapağı değiştirmek için başka bir görselin "Ana görsel" anahtarını açın.')
                                                                            ->warning()
                                                                            ->send();
                                                                    }

                                                                    return;
                                                                }

                                                                foreach ($keys as $key) {
                                                                    if ($get('../'.$key.'.is_primary')) {
                                                                        $set('../'.$key.'.is_primary', false);
                                                                    }
                                                                }

                                                                $set('is_primary', true);
                                                            })
                                                            ->columnSpan(6),
                                                    ]),
                                            ])
                                            ->columns(12)
                                            ->defaultItems(0)
                                            ->addActionLabel('Görsel ekle')
                                            ->reorderable()
                                            ->collapsible()
                                            // Sıra numarası sistemden gelir: sürükleyip bıraktıkça
                                            // yeniden numaralanır, elle girilmez. Numara dosya adından
                                            // ayrışsın diye yuvarlak rozet içinde.
                                            ->itemLabel(fn (array $state, ?int $index): Htmlable => new HtmlString(
                                                '<span class="merter-repeater-index">'.(($index ?? 0) + 1).'</span>'
                                                .'<span class="merter-repeater-name">'
                                                .e(isset($state['storage_path']) ? basename((string) $state['storage_path']) : 'Yeni görsel')
                                                .'</span>'
                                                .(($state['is_primary'] ?? false) ? '<span class="merter-repeater-badge">KAPAK</span>' : '')
                                            )),
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
                                            // `columns(4)` yalnızca lg kırılımını ayarlıyor:
                                            // altındaki tüm genişliklerde tek sütun kalıyor,
                                            // beden adları kısacıkken ekran boyu liste
                                            // oluşuyordu. Kırılımlar açıkça verilir.
                                            ->columns(['default' => 3, 'sm' => 4, 'lg' => 4])
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
                                            // Renk satırında renk kutusu + ad var, bedenden
                                            // geniş: mobilde iki sütun.
                                            ->columns(['default' => 2, 'sm' => 3, 'lg' => 4])
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

                                                $sizeIds = self::orderedSizeIds(
                                                    ($record?->variants ?? collect())
                                                        ->pluck('size_id')
                                                        ->filter()
                                                        ->unique()
                                                        ->all()
                                                );

                                                $distributed = self::distributePack((int) $record->pack_size, $sizeIds);

                                                $component->state($distributed !== []
                                                    ? $distributed
                                                    : array_map(fn ($sizeId): array => [
                                                        'size_id' => $sizeId,
                                                        'quantity' => 1,
                                                    ], $sizeIds));
                                            })
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
                                                    // Paket adedi bu satırların toplamı: yazarken
                                                    // güncellensin, alandan çıkmayı beklemesin.
                                                    ->live(debounce: 400),
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->collapsible(false)
                                            // Görünmez: dağılım aşağıdaki tablodan girilir, bu alan
                                            // yalnızca durumu ve kaydı taşır.
                                            ->hidden()
                                            ->dehydratedWhenHidden(),

                                        // Hazır seriler + kalıba döndürme (tablonun üstünde).
                                        Actions::make([
                                            ...array_map(
                                                fn (int $preset): Action => Action::make('packPreset'.$preset)
                                                    ->label($preset."'li seri")
                                                    ->outlined()
                                                    ->action(function (Get $get, Set $set) use ($preset): void {
                                                        $set('pack_size', $preset);
                                                        self::syncPackContents($get, $set);
                                                    }),
                                                array_map('intval', (array) config('storefront.pack.presets', [])),
                                            ),
                                            Action::make('distributePackContents')
                                                ->label('Otomatik dağıt')
                                                ->icon('heroicon-m-arrow-path')
                                                ->link()
                                                ->action(fn (Get $get, Set $set) => self::syncPackContents($get, $set)),
                                        ])->visible(fn (Get $get): bool => filled($get('variant_size_ids'))),

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
        self::syncPackContents($get, $set);
    }

    /**
     * Paket içeriğini seçili bedenlere göre yeniler.
     *
     * Kullanıcı adetlere elle dokunmadıysa dağılım otomatik hesaplanır;
     * dokunduysa girdiği değerler korunur, yalnızca yeni/çıkan bedenler işlenir.
     */
    /**
     * Paket dağılımının toplamı = paket adedi. Elle girilen tek şey dağılım
     * olduğu için paket adedi hep bu toplamdan türer.
     */
    private static function packTotal($get, string $path = 'pack_contents'): int
    {
        return collect((array) $get($path))->sum(
            fn (array $item): int => max(0, (int) ($item['quantity'] ?? 0))
        );
    }

    /**
     * Paket içeriğini seçili bedenlere göre yeniden kurar.
     *
     * Dağılım her çağrıda hedef paket adedinden baştan hesaplanır; ara
     * durumlar (beden listesi tek tek dolarken) böylece kendini düzeltir.
     * Dağıtılamayan durumda (beden sayısı > paket adedi) mevcut adetler
     * korunur, yeni bedene 1 verilir.
     *
     * Tetikleyiciler yalnızca şunlar: beden listesi değişti, paket adedi
     * yazıldı, "Otomatik dağıt"a basıldı. Tablodan elle girilen adetler
     * bunların dışında hiçbir yerde ezilmez.
     */
    private static function syncPackContents($get, $set): void
    {
        $sizes = self::orderedSizeIds((array) $get('variant_size_ids'));

        if ($sizes === []) {
            $set('pack_contents', []);

            return;
        }

        $distributed = self::distributePack((int) $get('pack_size'), $sizes);

        if ($distributed === []) {
            $existing = collect((array) $get('pack_contents'))
                ->keyBy(fn (array $item): string => (string) ($item['size_id'] ?? ''));

            $distributed = array_map(fn ($sizeId): array => [
                'size_id' => $sizeId,
                'quantity' => max(1, (int) ($existing->get((string) $sizeId)['quantity'] ?? 1)),
            ], $sizes);
        }

        $set('pack_contents', $distributed);
        $set('pack_size', array_sum(array_column($distributed, 'quantity')) ?: 1);
    }

    /**
     * Beden id'lerini panel listesindeki sırayla (küçükten büyüğe) döndürür.
     * Ortadan dışa dağıtımın doğru bedene denk gelmesi buna bağlı.
     *
     * @param  array<int, mixed>  $sizeIds
     * @return array<int, string>
     */
    private static function orderedSizeIds(array $sizeIds): array
    {
        $selected = array_map('strval', array_filter($sizeIds));

        if ($selected === []) {
            return [];
        }

        $order = array_keys(app(AdminOptionService::class)->sizes());

        return array_values(array_merge(
            // Bilinen bedenler panel sırasında.
            array_values(array_intersect(array_map('strval', $order), $selected)),
            // Listede olmayan (pasife alınmış) bedenler sona.
            array_values(array_diff($selected, array_map('strval', $order))),
        ));
    }

    /**
     * Paket adedini bedenlere dağıtır.
     *
     * Önce config('storefront.pack.templates') içindeki sabit kalıba bakılır
     * (5'li seride 3 beden -> 2·2·1, 4 beden -> 1·2·1·1). Kalıp yoksa ya da
     * toplamı paket adedini tutmuyorsa hesaplanır: her bedene taban pay,
     * kalan ortadan küçük bedenlere doğru eklenir.
     *
     * 5 adet / [S, M, L] -> S:2 M:2 L:1
     * 10 adet / [S, M, L, XL] -> S:3 M:3 L:2 XL:2
     *
     * Beden sayısı paket adedinden fazlaysa her bedene en az 1 düşmez;
     * bu durumda boş dizi döner ve çağıran taraf dağıtmaz.
     *
     * @param  array<int, string>  $sizeIds
     * @return array<int, array{size_id: string, quantity: int}>
     */
    private static function distributePack(int $packSize, array $sizeIds): array
    {
        $count = count($sizeIds);

        if ($count === 0 || $packSize < $count) {
            return [];
        }

        $quantities = self::packTemplate($packSize, $count) ?? self::computedPack($packSize, $count);

        return array_map(
            fn (string $sizeId, int $quantity): array => [
                'size_id' => $sizeId,
                'quantity' => $quantity,
            ],
            $sizeIds,
            $quantities,
        );
    }

    /**
     * Config'teki sabit kalıp; yoksa veya toplamı tutmuyorsa null.
     *
     * @return array<int, int>|null
     */
    private static function packTemplate(int $packSize, int $sizeCount): ?array
    {
        $template = config('storefront.pack.templates.'.$sizeCount);

        if (! is_array($template) || count($template) !== $sizeCount) {
            return null;
        }

        $quantities = array_map('intval', array_values($template));

        return array_sum($quantities) === $packSize ? $quantities : null;
    }

    /**
     * Taban pay + kalanın ortadan küçük bedenlere doğru dağıtılması.
     *
     * @return array<int, int>
     */
    private static function computedPack(int $packSize, int $sizeCount): array
    {
        $quantities = array_fill(0, $sizeCount, intdiv($packSize, $sizeCount));
        $remainder = $packSize % $sizeCount;

        foreach (self::middleOutOrder($sizeCount) as $index) {
            if ($remainder <= 0) {
                break;
            }

            $quantities[$index]++;
            $remainder--;
        }

        return $quantities;
    }

    /**
     * Ortadan başlayıp küçük bedenlere doğru ilerleyen indeks sırası:
     * 4 beden için [1, 0, 2, 3]. Konfeksiyonda seri küçük-orta bedenlerde
     * yoğunlaşır; sahadaki kalıplar da bu yönde.
     *
     * @return array<int, int>
     */
    private static function middleOutOrder(int $count): array
    {
        $middle = intdiv($count - 1, 2);
        $order = [];

        for ($step = 0; count($order) < $count && $step <= 2 * $count; $step++) {
            $left = $middle - intdiv($step + 1, 2);
            $right = $middle + intdiv($step, 2);
            $candidate = $step === 0 ? $middle : ($step % 2 === 1 ? $left : $right);

            if ($candidate >= 0 && $candidate < $count && ! in_array($candidate, $order, true)) {
                $order[] = $candidate;
            }
        }

        return $order;
    }
}
