<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\Multilingual;
use App\Filament\Support\StorageUpload;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AdminOptionService;
use App\Services\ExchangeRateService;
use App\Services\ProductCodeService;
use App\Support\ProductName;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Ürün bilgileri')
                    ->extraAttributes(['class' => 'merter-product-fields'])
                    ->columns(2)
                    ->schema([
                        // Vitrin kararı en üstte: ürünü kaydetmeden önce
                        // görülsün, formun dibinde kaybolmasın.
                        Checkbox::make('show_on_home')
                            ->label('Ana sayfada göster')
                            ->default(true)
                            ->helperText('İşaretli değilse ürün yayında kalır ama ana sayfadaki listede çıkmaz.')
                            ->columnSpanFull(),
                        // Aynı ürünün ikinci kez girilmesini engelleyen kontrol.
                        // Kimlik koddur; ad kontrolü mükerrer kaydı önler.
                        Multilingual::turkish('name', 'Ürün Adı')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $get, $set) => $set('slug', self::slug($state)))
                            ->rule(fn (?Product $record) => function (string $attribute, $value, callable $fail) use ($record): void {
                                $duplicate = ProductName::duplicate($value, $record?->getKey());

                                if ($duplicate) {
                                    $fail(self::duplicateMessage($duplicate));
                                }
                            })
                            ->helperText(fn (?string $state, ?Product $record): string|Htmlable => self::duplicateProductHint($state, $record))
                            ->columnSpanFull(),
                        // Kod sistem tarafından sırayla atanır; input yerine
                        // belirgin, salt okunur bilgi olarak gösterilir.
                        Placeholder::make('code_display')
                            ->label('Ürün kodu')
                            ->content(fn (?Product $record): Htmlable => new HtmlString(
                                '<span class="merter-product-code">'.e($record?->code ?? app(ProductCodeService::class)->peek()).'</span>'
                            )),
                        Hidden::make('currency')
                            ->default('USD')
                            ->dehydrateStateUsing(fn (): string => 'USD'),
                        TextInput::make('price_usd')
                            ->label('Fiyat (USD)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->prefix('$')
                            ->required()
                            ->afterStateHydrated(function (TextInput $component, mixed $state, ?Product $record): void {
                                if (filled($state) || ! $record || blank($record->price_try)) {
                                    return;
                                }

                                try {
                                    $rates = app(ExchangeRateService::class)->ratesFromTry();
                                    $component->state(round((float) $record->price_try * (float) $rates['USD'], 2));
                                } catch (\Throwable) {
                                    // Kur alınamazsa yanlış USD değeri göstermek yerine alan boş kalır.
                                }
                            })
                            ->helperText('Ana fiyat dolardır. Türkçede TL, diğer Avrupa dillerinde Euro olarak güncel kurdan gösterilir.'),
                        // Veritabanında ürün başına tek kategori tutulur. Gizli
                        // alan gerçek değeri taşır; kutucuklu alan panelde daha
                        // hızlı ve görünür bir seçim deneyimi sağlar.
                        Hidden::make('category_id')
                            ->required(),
                        CheckboxList::make('category_selection')
                            ->label('Kategori')
                            ->options(fn () => app(AdminOptionService::class)->categories())
                            ->columns(['default' => 2, 'sm' => 3, 'lg' => 4])
                            ->gridDirection('row')
                            ->minItems(fn (Get $get): int => blank($get('category_id')) ? 1 : 0)
                            ->maxItems(1)
                            ->required(fn (Get $get): bool => blank($get('category_id')))
                            ->live()
                            ->afterStateHydrated(fn (CheckboxList $component, ?Product $record) => $component->state(
                                filled($record?->category_id) ? [(string) $record->category_id] : []
                            ))
                            ->afterStateUpdated(fn ($state, Set $set) => $set(
                                'category_id',
                                collect((array) $state)->filter()->first()
                            ))
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->helperText('Ürün için bir kategori seçin.'),
                        // Paket adedi ayrı bir alan olarak sorulmaz: ekranda iki ayrı
                        // sayı olması (buradaki hedef ve tablodaki toplam) çelişki
                        // üretiyordu. Tek doğru kaynak aşağıdaki varyant tablosu;
                        // bu alan yalnızca dağıtım hedefini taşır ve kaydedilirken
                        // tablonun toplamına eşitlenir.
                        Hidden::make('pack_size')
                            ->default(fn (): int => max(1, (int) config('storefront.pack.default_size', 1)))
                            ->dehydrateStateUsing(fn ($state, Get $get): int => self::packTotal($get) ?: max(1, (int) $state)),

                        // Ürün bilgilerinde paket adedi görünür ama düzenlenmez:
                        // değeri aşağıdaki varyant dağılımının toplamıdır.
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
                            ->helperText('Aşağıdaki beden ve renk bölümündeki paket dağılımından hesaplanır.'),
                        // Bağlantı ürün adından üretilir; ad değişince yenilenir.
                        Hidden::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn ($state, $get) => self::slug($get('name.tr')) ?: $state),
                        // İki durum yeterli: "Son ürünler" hiç kullanılmamıştı.
                        // Kolon metin olarak kaldığı için eski kayıtlardaki
                        // low_stock değeri işaretli (stokta) gelir.
                        // Kolon metin tutmaya devam ediyor; kutucuk yalnızca
                        // arayüz tarafında. Alan tek olduğu için çapraz okuma
                        // ya da canlı olay gerekmiyor: aynı alan yüklenirken
                        // bool'a, kaydedilirken metne çevriliyor.
                        Checkbox::make('stock_status')
                            ->label('Stokta')
                            ->helperText('İşaretli değilse ürün "Tükendi" olarak gösterilir.')
                            ->default(true)
                            ->afterStateHydrated(fn (Checkbox $component, ?Product $record) => $component->state(
                                ($record?->stock_status ?? 'in_stock') !== 'out_of_stock',
                            ))
                            ->dehydrateStateUsing(fn ($state): string => $state ? 'in_stock' : 'out_of_stock'),
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

                Section::make('Ürün görselleri')
                    ->description('Görselleri ekleyin, sürükleyerek sıralayın ve kapak görselini seçin.')
                    ->extraAttributes(['class' => 'merter-product-media'])
                    ->schema([
                        Repeater::make('images')
                            ->hiddenLabel()
                            ->relationship('images')
                            ->orderColumn('sort_order')
                            // Gömülü repeater, ayrı görsel ilişki yöneticisinin
                            // çeviri kancalarını çalıştırmaz; tüm alt metinleri
                            // tek API isteğinde burada çevir.
                            ->mutateRelationshipDataBeforeCreateUsing(
                                fn (array $data, $livewire): array => $livewire->fillAutomaticImageAltTranslations(
                                    $data,
                                    null,
                                ),
                            )
                            ->mutateRelationshipDataBeforeSaveUsing(
                                fn (array $data, ProductImage $record, $livewire): array => $livewire->fillAutomaticImageAltTranslations(
                                    $data,
                                    $record,
                                ),
                            )
                            ->required()
                            ->minItems(1)
                            // hiddenLabel olduğu için doğrulama mesajı ham "images"
                            // adını kullanıyordu ("images zorunludur"); Türkçe ad ver.
                            ->validationAttribute('Görsel')
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
                            ->addActionLabel('Yeni görsel ekle')
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

                Section::make('Beden ve renk seçenekleri')
                    ->description('Beden ve renkleri seçtiğinizde stok satırları otomatik oluşturulur.')
                    ->extraAttributes(['class' => 'merter-product-media'])
                    ->schema([
                        CheckboxList::make('variant_size_ids')
                            ->label('Bedenleri seçin')
                            ->helperText('Ürünün satılacağı bedenlerin tamamını işaretleyin. Bu alan zorunludur.')
                            ->extraFieldWrapperAttributes(['class' => 'merter-variant-choice merter-variant-choice--sizes'])
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
                            ->helperText('Ürünün mevcut olduğu renklerin tamamını işaretleyin. Bu alan zorunludur.')
                            ->extraFieldWrapperAttributes(['class' => 'merter-variant-choice merter-variant-choice--colors'])
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
            ]);
    }

    /**
     * Kaynak paneldeki slug üretimiyle aynı: "<ad>-<kod>", Türkçe karakterler
     * sadeleştirilmiş.
     */
    private static function slug(?string $name): string
    {
        return ProductName::key($name);
    }

    /**
     * Mükerrer ad hatası: kullanıcı yeni kayıt açmak yerine mevcut ürünü
     * düzenlemeye yönlendirilir.
     */
    private static function duplicateMessage(Product $duplicate): string
    {
        $name = Multilingual::tr($duplicate->name);
        $state = $duplicate->active ? '' : ' Bu ürün şu an yayında değil.';

        return 'Bu adla bir ürün zaten var: '.$name.' (kod: '.$duplicate->code.').'.$state
            .' Aşağıdaki bağlantıdan mevcut ürünü açın.';
    }

    /**
     * Birebir mükerreri veya muhtemel yazım hatasını tek satırda gösterir.
     * Düz URL yerine düzenleme sayfası yeni sekmede açılır.
     */
    private static function duplicateProductHint(?string $state, ?Product $record): string|Htmlable
    {
        $duplicate = ProductName::duplicate($state, $record?->getKey());
        $suggestion = $duplicate ?: ProductName::closestTypo($state, $record?->getKey());

        if (! $suggestion) {
            return 'Aynı adla ikinci ürün girilemez; mevcut ürünü düzenleyin.';
        }

        $name = Multilingual::tr($suggestion->name);
        $url = ProductResource::getUrl('edit', ['record' => $suggestion]);
        $status = $suggestion->active
            ? ''
            : '<span class="merter-duplicate-status">Yayında değil</span>';
        $title = $duplicate ? 'Bu ürün zaten kayıtlı' : 'Bunu mu demek istediniz?';
        $cardClass = $duplicate ? 'merter-duplicate-card' : 'merter-duplicate-card merter-typo-card';
        $role = $duplicate ? 'alert' : 'status';

        return new HtmlString(
            '<span class="'.$cardClass.'" role="'.$role.'">'
            .'<span class="merter-duplicate-icon" aria-hidden="true">'
            .'<svg viewBox="0 0 24 24" fill="none"><path d="M12 8v5m0 3.25v.01M10.28 4.67 3.36 16.5A2 2 0 0 0 5.09 19.5h13.82a2 2 0 0 0 1.73-3L13.72 4.67a2 2 0 0 0-3.44 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            .'</span>'
            .'<span class="merter-duplicate-content">'
            .'<span class="merter-duplicate-title">'.$title.'</span>'
            .'<span class="merter-duplicate-product"><strong>'.e($name).'</strong><span aria-hidden="true"> · </span>Kod '.e($suggestion->code).$status.'</span>'
            .'<a class="merter-duplicate-link" href="'.e($url).'" target="_blank" rel="noopener noreferrer">'
            .'<span>Mevcut ürünü aç</span><span aria-hidden="true">↗</span></a>'
            .'</span></span>'
        );
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
