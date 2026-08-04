<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Color;
use App\Models\Size;
use App\Models\TelegramChannelProduct;
use App\Models\TelegramChannelProductImage;
use App\Models\TelegramScan;
use App\Services\ExchangeRateService;
use App\Services\Telegram\TelegramNameGenerator;
use App\Services\Telegram\TelegramProductImporter;
use App\Services\TranslateService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Throwable;

/**
 * Tek bir taramanın detayı: özet kutuları + bulunan ürün kartları.
 *
 * Kartta hangi alanların eksik olduğu kırmızı uyarı satırında yazıyor;
 * "Hızlı Ekle" o eksikleri doldurup ürünü kataloğa aktarıyor.
 */
class TelegramScanDetail extends Page
{
    protected string $view = 'filament.pages.telegram-scan-detail';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'telegram-scans/{record}';

    /**
     * Rota parametresi. Livewire genel özellikleri istek parametreleriyle
     * doldurduğu için burada modelin kendisi değil kimliği tutuluyor.
     */
    public string $record = '';

    /** Ürün adı / tedarikçi kodu / mesaj metninde arama. */
    public string $search = '';

    /** Kanala göre daralt; boşsa tüm kanallar. */
    public string $channelFilter = '';

    /**
     * Kataloğa eklenme durumuna göre daralt.
     * '' hepsi · 'no' henüz eklenmemiş (iş bekleyen) · 'yes' kataloğa aktarılmış.
     * İşimiz için 5'li ham durum yerine tek soru yeter: eklendi mi?
     */
    public string $addedFilter = '';

    /**
     * Kapsam: '' tümü (taramada görülen hepsi) · 'new' yalnızca bu taramada
     * ilk kez çıkanlar.
     */
    public string $scopeFilter = '';

    /** Sıralama: 'newest' · 'oldest' · 'price_asc' · 'price_desc'. */
    public string $sort = 'newest';

    private ?TelegramScan $scan = null;

    public static function canAccess(): bool
    {
        $user = filament()->auth()->user();

        return (bool) $user?->is_active && $user->isSuperAdmin();
    }

    public function mount(string $record): void
    {
        $this->record = $record;

        // Var olmayan tarama için hemen 404.
        $this->scan();
    }

    public function scan(): TelegramScan
    {
        return $this->scan ??= TelegramScan::query()->findOrFail($this->record);
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Tarama #'.$this->scan()->number;
    }

    /**
     * Kartın üstünde gösterilen eksik alan uyarıları.
     *
     * @return array<int, string>
     */
    public static function missingFields(TelegramChannelProduct $product): array
    {
        $missing = [];

        if (blank($product->name)) {
            $missing[] = 'Ürün adı eksik';
        }

        if ($product->price === null) {
            $missing[] = 'Fiyat eksik';
        }

        if (blank($product->sizes)) {
            $missing[] = 'Paket bedenleri eksik';
        }

        if (blank($product->colors)) {
            $missing[] = 'Renk adı çekilemedi';
        }

        if (blank($product->category)) {
            $missing[] = 'Kategori eksik';
        }

        return $missing;
    }

    /** Hızlı Ekle açık olan ürünün kimliği; null ise pencere kapalı. */
    public ?string $quickAddId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    /**
     * Modal içi anlık bildirim: ['type' => success|warning|danger, 'message' => ...].
     *
     * Filament toast'ları sayfa seviyesinde çıkıp modalın arkasında kaldığı için
     * Hızlı Ekle'deki geri bildirim modalın içinde gösteriliyor.
     *
     * @var array<string, string>
     */
    public array $quickAddFlash = [];

    private function flash(string $message, string $type = 'warning'): void
    {
        $this->quickAddFlash = ['type' => $type, 'message' => $message];
    }

    public function openQuickAdd(string $productId): void
    {
        $product = TelegramChannelProduct::query()->with('images')->findOrFail($productId);

        $this->quickAddId = $productId;
        $this->quickAddFlash = [];

        // Telegram'dan gelenler forma önden yazılır; kullanıcı üzerinde
        // değişiklik yapar. Renk gelmiyor (kanallar yazmıyor), o yüzden boş.
        $this->form = [
            'name' => (string) ($product->name ?? ''),
            'name_source' => $product->name_source,
            // Adı boş gelen üründe görselden otomatik üretim başlar. Bu bayrak
            // "Görselden üretiliyor…" yer tutucusunu ve suggester bileşenini
            // yönetir; üretim (başarı ya da hata) bitince kapanır, böylece hata
            // olsa da alan "üretiliyor"da takılı kalmaz.
            'name_pending' => blank($product->name) && $this->nameGeneratorReady(),
            'description' => '',
            // Katalog USD tabanlı; kanal fiyatı TRY yazmışsa güncel kurla
            // USD'ye çevrilir. USD/belirsizse olduğu gibi kullanılır.
            'price_usd' => $this->priceUsdFor($product),
            // telegram_channel_products.category serbest metin (kanaldan
            // tahmin); katalog kategorisiyle eşleşmediği için elle seçilir.
            'category_id' => null,
            // Telegram'daki kod (ör. "Kod 8017") tedarikçinin kendi kodu;
            // kataloğa taşınmıyor. Kayıtta sistemin sıradaki kodu atanır.
            'code' => '',
            'pack' => $this->packFromSeries($product),
            // Kanal rengi yazmışsa ve sistemde karşılığı varsa önden seçili
            // gelir; olmayanlar için ekranda tek tıklık "ekle" düğmesi çıkar.
            'color_ids' => collect($this->channelColors($product))
                ->pluck('color')
                ->filter()
                ->map(fn (Color $color): string => $color->getKey())
                ->values()
                ->all(),
            'image_ids' => $this->uniqueImages($product)->pluck('id')->all(),
            // Görsellerin gösterim/katalog sırası. Sürükle-bırak bunu değiştirir;
            // numaralar ve kapak bu sırayı izler. Başlangıçta albüm sırası.
            'image_order' => $this->uniqueImages($product)->pluck('id')->all(),
            // Yeni ürün varsayılan olarak ana sayfada görünür; istenmiyorsa
            // kaydetmeden önce kapatılır.
            'show_on_home' => true,
        ];
    }

    /**
     * Sürükle-bırak sonrası yeni görsel sırasını forma yazar.
     *
     * Gelen id listesi ürünün kendi görselleriyle sınırlanır (dışarıdan başka
     * kimlik enjekte edilemesin) ve listede olmayan varsa sona eklenir.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function reorderImages(array $orderedIds): void
    {
        $known = (array) ($this->form['image_order'] ?? []);

        $clean = collect($orderedIds)
            ->map(fn ($id): string => (string) $id)
            ->filter(fn (string $id): bool => in_array($id, $known, true))
            ->unique()
            ->values();

        // JS'in göndermediği (ör. gizli) kareler kaybolmasın: sona eklenir.
        $missing = array_values(array_diff($known, $clean->all()));

        $this->form['image_order'] = [...$clean->all(), ...$missing];
    }

    public function closeQuickAdd(): void
    {
        $this->quickAddId = null;
        $this->form = [];
        $this->quickAddFlash = [];
    }

    /**
     * Adı olmayan ürün için görselden ad üretir.
     *
     * Üretilen ad forma yazılır, doğrudan kaydedilmez: kullanıcı görüp
     * düzeltebilsin. Kaydedilirse kaynağı "ai" olarak işaretlenir.
     */
    public function generateName(bool $notify = true): void
    {
        $product = TelegramChannelProduct::query()->with('images')->findOrFail((string) $this->quickAddId);

        try {
            $name = app(TelegramNameGenerator::class)->generate($product);
        } catch (Throwable $e) {
            if ($notify) {
                $this->flash('Ad üretilemedi: '.$e->getMessage(), 'danger');
            }

            // Otomatik denemede sessizce geç: kullanıcı adı elle yazabilir,
            // dilerse butonla tekrar dener.
            $this->form['name_source'] = $this->form['name_source'] ?? null;

            return;
        }

        $this->form['name'] = $name;
        $this->form['name_source'] = 'ai';

        // Aday kayda hemen yazılıyor: pencere kapatılsa bile aynı ürün için
        // ikinci kez üretim yapılmasın ve listede adıyla görünsün.
        $product->forceFill(['name' => $name, 'name_source' => 'ai'])->save();

        if ($notify) {
            $this->flash('Ad önerildi: '.$name, 'success');
        }
    }

    /**
     * Adı boş gelen ürün için ayrı bileşen (TelegramNameSuggester) görselden ad
     * üretip bu olayı gönderir; ad forma yazılır. Ayrı bileşende koştuğu için
     * üretim süresince panel kilitlenmez, kullanıcı bu arada kategori/renk
     * seçebilir.
     */
    #[On('telegram-name-suggested')]
    public function applySuggestedName(string $name): void
    {
        // Üretim bitti: "üretiliyor" yer tutucusu kalksın.
        $this->form['name_pending'] = false;

        // Kullanıcı bu arada adı elle yazmışsa üzerine yazma.
        if (filled($this->form['name'] ?? null)) {
            return;
        }

        $this->form['name'] = $name;
        $this->form['name_source'] = 'ai';
    }

    /**
     * Otomatik ad üretimi başarısız olduğunda suggester bileşeninin gönderdiği
     * olay: yer tutucu "üretiliyor"da takılı kalmasın, sebep modalda görünsün.
     *
     * (Model/servis düzelince üretim yine otomatik denenecek; şimdilik hata
     * kullanıcıya dönüyor, adı elle yazabilir ya da "Yeniden üret"e basabilir.)
     */
    #[On('telegram-name-failed')]
    public function applyNameFailure(string $message): void
    {
        $this->form['name_pending'] = false;

        // Kullanıcı bu arada adı elle yazdıysa hatayla rahatsız etme.
        if (filled($this->form['name'] ?? null)) {
            return;
        }

        $this->flash('Ad üretilemedi: '.$message.' — adı elle yazabilir ya da "Yeniden üret"e basabilirsiniz.', 'danger');
    }

    public function nameGeneratorReady(): bool
    {
        return app(TelegramNameGenerator::class)->configured();
    }

    /** Kategori tek seçim: aynı kutuya tekrar basmak seçimi kaldırır. */
    public function selectCategory(string $categoryId): void
    {
        $this->form['category_id'] = ($this->form['category_id'] ?? null) === $categoryId
            ? null
            : $categoryId;
    }

    public function toggleColor(string $colorId): void
    {
        $selected = (array) ($this->form['color_ids'] ?? []);

        $this->form['color_ids'] = in_array($colorId, $selected, true)
            ? array_values(array_diff($selected, [$colorId]))
            : [...$selected, $colorId];
    }

    public function toggleShowOnHome(): void
    {
        $this->form['show_on_home'] = ! ($this->form['show_on_home'] ?? true);
    }

    public function toggleImage(string $imageId): void
    {
        $selected = (array) ($this->form['image_ids'] ?? []);

        $this->form['image_ids'] = in_array($imageId, $selected, true)
            ? array_values(array_diff($selected, [$imageId]))
            : [...$selected, $imageId];
    }

    /**
     * Kanalın yazdığı renk adları için tahmini renk kodu.
     *
     * Kanallar rengi nadiren yazıyor ama yazdığında ("haki", "natur") sistemde
     * karşılığı olmayabiliyor. Tek tıkla eklenebilsin diye yaygın toptan
     * tekstil tonlarının kodu burada duruyor; bilinmeyen ad siyahla eklenir ve
     * Renkler ekranından düzeltilebilir.
     */
    private const KNOWN_HEX = [
        'haki' => '#78866B',
        'natur' => '#E8DCC8',
        'ekru' => '#F0EAD6',
        'bej' => '#D9C6A5',
        'antrasit' => '#3A3A3C',
        'vizon' => '#A08679',
        'gri' => '#9CA3AF',
        'lacivert' => '#1E3A8A',
        'bordo' => '#7B1E23',
        'hardal' => '#D4A017',
        'fuşya' => '#D6246E',
        'turuncu' => '#F97316',
        'pudra' => '#E7C6C2',
        'zeytin' => '#6B7A3A',
    ];

    /** Renk sayılmayacak, ayrıştırıcının yanlışlıkla yakaladığı sözcükler. */
    private const NOT_A_COLOR = ['yeni', 'renk', 'renkler', 'seri', 'kod', 'adet', 'stok'];

    /**
     * Kanal metninden çıkan renk adları; sistemdeki karşılığıyla birlikte.
     *
     * @return array<int, array{name: string, color: ?Color}>
     */
    public function channelColors(TelegramChannelProduct $product): array
    {
        $system = Color::query()->get()->keyBy(fn (Color $color): string => mb_strtolower($color->name));

        return collect((array) $product->colors)
            ->map(fn ($name): string => trim((string) $name))
            ->filter(fn (string $name): bool => mb_strlen($name) > 2
                && ! is_numeric($name)
                && ! in_array(mb_strtolower($name), self::NOT_A_COLOR, true))
            ->unique(fn (string $name): string => mb_strtolower($name))
            ->map(fn (string $name): array => [
                'name' => $name,
                'color' => $system->get(mb_strtolower($name)),
            ])
            ->values()
            ->all();
    }

    /** Kanalın yazdığı ama sistemde olmayan rengi tek tıkla ekler. */
    public function addChannelColor(string $name): void
    {
        $this->newColorName = $name;
        $this->newColorHex = self::KNOWN_HEX[mb_strtolower($name)] ?? '#000000';

        $this->addColor();
    }

    /** Yeni renk kutusundaki ad ve renk kodu. */
    public string $newColorName = '';

    public string $newColorHex = '#000000';

    /**
     * Sistemde olmayan rengi ekleyip seçer.
     *
     * Kanallar sürekli yeni ton çıkarıyor ("adaçayı yeşili", "kırık beyaz").
     * Kullanıcıyı Renkler ekranına gönderip Hızlı Ekle'yi baştan açtırmak
     * yerine renk buradan tanımlanıyor; katalogda da kalıcı oluyor.
     */
    public function addColor(): void
    {
        $name = trim($this->newColorName);

        // İsim zorunlu: boşken sessizce dönmek "eklenmiyor ama hata da yok" gibi
        // görünüyordu; artık nedenini söylüyor.
        if ($name === '') {
            $this->flash('Renk adı girin');

            return;
        }

        // Aynı renk ikinci kez oluşmasın: varsa onu seç.
        $existing = Color::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing instanceof Color) {
            if (! in_array($existing->getKey(), (array) ($this->form['color_ids'] ?? []), true)) {
                $this->toggleColor($existing->getKey());
            }

            $this->resetNewColor();

            $this->flash('Bu renk zaten vardı, seçildi.', 'success');

            return;
        }

        // Hex elle yazılıyor (#78866B ya da 78866B). Geçersizse siyaha düşüp
        // yanlış renk kaydetmek yerine uyarıp durur; kullanıcı düzeltir.
        $hex = ltrim(trim($this->newColorHex), '#');

        if (preg_match('/^[0-9A-Fa-f]{6}$/', $hex) !== 1) {
            $this->flash('Renk kodu geçersiz. Örnek: #78866B');

            return;
        }

        $hex = '#'.strtoupper($hex);

        $color = Color::create([
            'name' => $name,
            // Çeviri servisi çalışmazsa da renk kaydedilsin; Türkçesi yeter.
            'name_i18n' => rescue(
                fn (): array => ['tr' => $name] + app(TranslateService::class)->translateText($name),
                ['tr' => $name],
                report: false,
            ),
            'hex' => $hex,
            'active' => true,
            'sort_order' => ((int) Color::query()->max('sort_order')) + 1,
        ]);

        $this->toggleColor($color->getKey());
        $this->resetNewColor();

        $this->flash('Renk eklendi: '.$name, 'success');
    }

    private function resetNewColor(): void
    {
        $this->newColorName = '';
        $this->newColorHex = '#000000';
    }

    /** Tümünü seç / hiçbirini seçme. */
    public function toggleAllImages(): void
    {
        $product = TelegramChannelProduct::query()->with('images')->findOrFail((string) $this->quickAddId);
        $all = $this->uniqueImages($product)->pluck('id')->all();

        $this->form['image_ids'] = count((array) ($this->form['image_ids'] ?? [])) === count($all)
            ? []
            : $all;
    }

    /**
     * Ürünün tekil görselleri, kullanıcının sürükle-bırakla belirlediği sırada.
     *
     * form.image_order sıralamayı tutar; listede olmayan (yeni) kareler sona
     * eklenir. İçe aktarma da bu sırayı izler, numara gerçek sonucu gösterir.
     *
     * @return Collection<int, TelegramChannelProductImage>
     */
    public function orderedMedia(TelegramChannelProduct $product)
    {
        $order = (array) ($this->form['image_order'] ?? []);
        $position = array_flip($order);

        return $this->uniqueImages($product)
            ->sortBy(fn ($image): int => $position[$image->getKey()] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * Seçili medyanın kataloğa girecek sırası.
     *
     * Kullanıcının sürükleyip dizdiği sıra (orderedMedia) esas alınıyor; içe
     * aktarma da aynı sırayla yazıyor, böylece numaralar gerçek sonucu gösterir.
     *
     * @return array<string, int> image_id => sıra (1'den başlar)
     */
    public function selectionOrder(TelegramChannelProduct $product): array
    {
        $selected = (array) ($this->form['image_ids'] ?? []);

        return $this->orderedMedia($product)
            ->filter(fn ($image): bool => in_array($image->getKey(), $selected, true))
            ->values()
            ->mapWithKeys(fn ($image, int $index): array => [$image->getKey() => $index + 1])
            ->all();
    }

    /** Kapak olacak görsel: sıradaki ilk seçili fotoğraf (video kapak olamaz). */
    public function coverImageId(TelegramChannelProduct $product): ?string
    {
        $selected = (array) ($this->form['image_ids'] ?? []);

        return $this->orderedMedia($product)
            ->first(fn ($image): bool => $image->type === 'photo' && in_array($image->getKey(), $selected, true))
            ?->getKey();
    }

    /** Girilen beden adetlerinin toplamı: paket kaç parça. */
    public function packTotal(): int
    {
        return (int) collect($this->form['pack'] ?? [])->sum(fn ($quantity): int => max(0, (int) $quantity));
    }

    /**
     * Kataloğa aktarmadan önce dolu olması gereken alanlar.
     *
     * Tedarikçi kodu bilerek listede yok: kataloğa taşınmıyor.
     *
     * @return array<int, string>
     */
    public function quickAddErrors(): array
    {
        $errors = [];

        if (blank($this->form['name'] ?? null)) {
            $errors[] = 'Ürün adı';
        }

        if (blank($this->form['price_usd'] ?? null) || (float) $this->form['price_usd'] <= 0) {
            $errors[] = 'Fiyat';
        }

        if (blank($this->form['category_id'] ?? null)) {
            $errors[] = 'Kategori';
        }

        if ($this->packTotal() < 1) {
            $errors[] = 'Paket bedenleri';
        }

        if ((array) ($this->form['color_ids'] ?? []) === []) {
            $errors[] = 'En az bir renk';
        }

        return $errors;
    }

    public function saveQuickAdd(): void
    {
        $missing = $this->quickAddErrors();

        if ($missing !== []) {
            $this->flash('Eksik alan var: '.implode(' · ', $missing));

            return;
        }

        $product = TelegramChannelProduct::query()->with('images')->findOrFail((string) $this->quickAddId);

        // AI ile üretilen ad aday kayda da işlenir: liste bunu rozetle
        // gösteriyor ve aynı ürün için tekrar üretim gerekmiyor.
        if (($this->form['name_source'] ?? null) === 'ai' && filled($this->form['name'] ?? null)) {
            $product->forceFill([
                'name' => (string) $this->form['name'],
                'name_source' => 'ai',
            ])->save();
        }

        try {
            $created = app(TelegramProductImporter::class)->import($product, [
                'name' => (string) ($this->form['name'] ?? ''),
                'description' => $this->form['description'] ?? null,
                'price_usd' => $this->form['price_usd'] ?? null,
                'category_id' => $this->form['category_id'] ?? null,
                // Katalog kodunu Product modeli kendi atıyor.
                'code' => null,
                'pack' => (array) ($this->form['pack'] ?? []),
                'color_ids' => (array) ($this->form['color_ids'] ?? []),
                // Seçili görseller kullanıcının sürükleyip dizdiği sırayla
                // gönderilir; importer bu diziyi olduğu gibi uygular.
                'image_ids' => $this->orderedMedia($product)
                    ->pluck('id')
                    ->filter(fn (string $id): bool => in_array($id, (array) ($this->form['image_ids'] ?? []), true))
                    ->values()
                    ->all(),
                'show_on_home' => (bool) ($this->form['show_on_home'] ?? true),
            ]);
        } catch (Throwable $e) {
            $this->flash('Ürün eklenemedi: '.$e->getMessage(), 'danger');

            return;
        }

        $this->closeQuickAdd();

        Notification::make()
            ->title('Ürün kataloğa eklendi')
            ->body('Kod: '.$created->code)
            ->success()
            ->send();
    }

    /**
     * Adayın fiyatını katalog tabanı olan USD'ye çevirir.
     *
     * Kanallar çoğunlukla USD ("22$") yazıyor ama biri TRY ("22 TL") ya da EUR
     * ("22€") yazarsa o tutar güncel TCMB kuruyla USD'ye çevrilir; yoksa
     * yanlışlıkla "22 USD" olarak kataloğa girerdi. USD ya da para birimi
     * belirsizse olduğu gibi kullanılır. Kur alınamazsa ham değere düşülür —
     * kullanıcı formda görüp düzeltebilir.
     */
    private function priceUsdFor(TelegramChannelProduct $product): string
    {
        if ($product->price === null) {
            return '';
        }

        $price = (float) $product->price;
        $currency = mb_strtoupper((string) $product->currency);

        if ($currency === 'TRY' || $currency === 'EUR') {
            // rates['USD'] ve rates['EUR']: 1 TRY karşılığı USD / EUR.
            //   TRY → USD: try × rates['USD']
            //   EUR → USD: eur × (rates['USD'] / rates['EUR'])
            $usd = rescue(function () use ($price, $currency): ?float {
                $rates = app(ExchangeRateService::class)->ratesFromTry();

                if ($currency === 'TRY') {
                    return $price * (float) $rates['USD'];
                }

                return (float) $rates['EUR'] > 0
                    ? $price * ((float) $rates['USD'] / (float) $rates['EUR'])
                    : null;
            }, null, report: false);

            if ($usd !== null && $usd > 0) {
                return (string) round($usd, 2);
            }
        }

        return (string) $price;
    }

    /**
     * Yaygın beden yazım farklarının kanonik karşılığı.
     *
     * Parser hep kanonik token üretir (S/M/L/XL/XS/XXL/XXXL) ama admin bedeni
     * "Small", "X-Large", "2XL" gibi yazmış olabilir; eşleştirme öncesi ikisi
     * de bu tabloyla aynı forma indirgeniyor.
     */
    private const SIZE_ALIASES = [
        'SMALL' => 'S',
        'MEDIUM' => 'M',
        'LARGE' => 'L',
        'XSMALL' => 'XS',
        'EXTRASMALL' => 'XS',
        'XLARGE' => 'XL',
        'EXTRALARGE' => 'XL',
        '2XL' => 'XXL',
        '2XLARGE' => 'XXL',
        'XXLARGE' => 'XXL',
        '3XL' => 'XXXL',
        '3XLARGE' => 'XXXL',
        'XXXLARGE' => 'XXXL',
    ];

    /**
     * Beden adını/etiketini kanonik token'a indirger: "Small" → "S",
     * "X-Large" → "XL", "2XL" → "XXL". Harf/rakam dışı (boşluk, tire) atılır.
     */
    private static function canonicalSize(string $value): string
    {
        $key = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper(trim($value))) ?? '';

        return self::SIZE_ALIASES[$key] ?? $key;
    }

    /**
     * Telegram'daki beden serisini ("1s 2m 1l 1xl") sistemdeki beden
     * kayıtlarına bağlar.
     *
     * Etiketler parser'dan kanonik gelse de iki taraf da canonicalSize ile
     * normalize ediliyor; böylece beden "Small"/"X-Large"/"2XL" gibi kayıtlıysa
     * da adet doğru yerleşir, paket boş kalmaz.
     *
     * @return array<string, int> size_id => adet
     */
    private function packFromSeries(TelegramChannelProduct $product): array
    {
        $series = collect((array) $product->sizes)
            ->mapWithKeys(fn ($quantity, $label): array => [self::canonicalSize((string) $label) => (int) $quantity])
            ->all();

        return Size::query()
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (Size $size): array => [
                $size->getKey() => (int) ($series[self::canonicalSize((string) $size->name)] ?? 0),
            ])
            ->all();
    }

    /**
     * Aynı görselin tekrarlarını eler.
     *
     * Kanallar aynı fotoğrafı birden çok albümde paylaşabiliyor; ürün
     * kartında ve Hızlı Ekle'de aynı kare iki kez çıkmasın diye kaynak
     * adresine göre tekilleştiriliyor.
     *
     * @return Collection<int, TelegramChannelProductImage>
     */
    public function uniqueImages(TelegramChannelProduct $product)
    {
        return $product->images
            ->unique(fn ($image): string => $image->file_path ?: ($image->source_url ?: $image->poster_url ?: $image->getKey()))
            ->values();
    }

    /** Arama + kanal/durum/kapsam filtrelerini ve sıralamayı başa döndür. */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->channelFilter = '';
        $this->addedFilter = '';
        $this->scopeFilter = '';
        $this->sort = 'newest';
    }

    /** Herhangi bir arama/filtre etkin mi (boş sonuç mesajını ayarlamak için). */
    public function hasActiveFilters(): bool
    {
        return filled($this->search)
            || filled($this->channelFilter)
            || filled($this->addedFilter)
            || filled($this->scopeFilter);
    }

    protected function getViewData(): array
    {
        $scan = $this->scan();

        // Tek liste: taramada görülen tüm ürünler. Yeni/eski ayrımı kartta
        // rozetle gösteriliyor; arama ve filtre çubuğu listeyi daraltır.
        $query = $scan->products()->with('images');

        // Arama: ürün adı, tedarikçi kodu ve ham mesaj metninde geçiyor mu.
        if (filled($this->search)) {
            $term = '%'.trim($this->search).'%';

            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('product_code', 'like', $term)
                    ->orWhere('raw_text', 'like', $term);
            });
        }

        if (filled($this->channelFilter)) {
            $query->where('channel', $this->channelFilter);
        }

        // Kataloğa eklendi mi: 5'li ham durum yerine tek soru.
        if ($this->addedFilter === 'yes') {
            $query->where('status', 'imported');
        } elseif ($this->addedFilter === 'no') {
            $query->where('status', '!=', 'imported');
        }

        // Kapsam: yalnızca bu taramada ilk kez çıkanlar.
        if ($this->scopeFilter === 'new') {
            $query->where('first_telegram_scan_id', $scan->id);
        }

        // Sıralama. Fiyat sıralamasında fiyatsız kayıtlar sona düşsün diye
        // önce "fiyat var mı" ölçütüyle diziliyor.
        match ($this->sort) {
            'oldest' => $query->orderBy('message_id'),
            'price_asc' => $query->orderByRaw('price is null')->orderBy('price'),
            'price_desc' => $query->orderByRaw('price is null')->orderByDesc('price'),
            default => $query->orderByDesc('message_id'),
        };

        $products = $query->get();

        return [
            'scan' => $scan,
            'products' => $products,
            'hasActiveFilters' => $this->hasActiveFilters(),
            // Filtre çubuğu için kanal listesi modelde tanımlı.
            'channelOptions' => TelegramChannelProduct::CHANNELS,
            'backUrl' => TelegramScans::getUrl(),
            // Hızlı Ekle penceresinin seçenekleri.
            'sizes' => Size::query()->orderBy('sort_order')->get(),
            'colors' => Color::query()->where('active', true)->orderBy('sort_order')->get(),
            // categories tablosunda sort_order yok; ada göre sıralanıyor.
            'categories' => Category::query()->where('active', true)->orderBy('name')->get(),
            'quickAddProduct' => $this->quickAddId
                ? TelegramChannelProduct::query()->with('images')->find($this->quickAddId)
                : null,
        ];
    }
}
