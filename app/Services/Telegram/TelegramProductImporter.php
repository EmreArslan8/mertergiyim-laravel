<?php

namespace App\Services\Telegram;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\TelegramChannelProduct;
use App\Models\TelegramChannelProductImage;
use App\Services\ImageUploader;
use App\Services\TranslateService;
use App\Support\ProductName;
use App\Support\UploadTarget;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * "Hızlı Ekle": Telegram'dan çekilmiş bir ürün adayını kataloğa aktarır.
 *
 * Medya tarama sırasında indirilmiyor; yalnızca Telegram adresleri
 * saklanıyor. Kullanıcı hangi fotoğraf ve videoyu istediğini seçtiği anda
 * seçilenler indirilip depoya yazılır. Böylece 2000+ görselin tamamı yerine
 * gerçekten kullanılanlar diske iniyor.
 */
class TelegramProductImporter
{
    public function __construct(private readonly ImageUploader $uploader) {}

    /**
     * @param  array{
     *   name: string,
     *   description?: ?string,
     *   price_usd: float|string|null,
     *   category_id?: ?string,
     *   code?: ?string,
     *   pack?: array<string, int>,
     *   color_ids?: array<int, string>,
     *   image_ids?: array<int, string>,
     *   show_on_home?: bool,
     * }  $data
     */
    public function import(TelegramChannelProduct $record, array $data): Product
    {
        // Çoklu görsel indirme PHP max_execution_time'ı (30 sn) aşabilir;
        // fatal yerine görsel başına timeout'la (aşağıda 15 sn) zarifçe atlansın.
        @set_time_limit(120);

        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Ürün adı zorunlu.');
        }

        // Katalog adı yalnızca Türkçe gelir; ana sayfa, kategori ve detay
        // sayfaları diğer dilleri de gösterdiği için ad ve açıklama kayıt
        // anında çevrilir. Çeviri çökerse Türkçe ile devam edilir (kategori
        // eklemedeki davranışla aynı).
        $nameI18n = rescue(
            fn (): array => ['tr' => $name] + app(TranslateService::class)->translateText($name),
            ['tr' => $name],
            report: false,
        );

        $description = trim((string) ($data['description'] ?? ''));
        $descriptionI18n = $description === ''
            ? ['tr' => '']
            : rescue(
                fn (): array => ['tr' => $description] + app(TranslateService::class)->translateText($description),
                ['tr' => $description],
                report: false,
            );

        // Yalnızca adedi girilmiş bedenler pakete girer.
        $pack = collect($data['pack'] ?? [])
            ->map(fn ($quantity): int => max(0, (int) $quantity))
            ->filter(fn (int $quantity): bool => $quantity > 0);

        $colorIds = array_values(array_filter((array) ($data['color_ids'] ?? [])));

        // Seçilen medya, ürüne bağlı olanlarla sınırlı: form dışarıdan başka
        // bir ürünün görselini gönderemez. Sıra, gelen image_ids dizisinin
        // sırasıdır (kullanıcı Hızlı Ekle'de sürükleyip dizdi); album_index
        // değil, çünkü kapak ve galeri sırası kullanıcının seçtiği sıradır.
        $imageIds = array_values(array_filter((array) ($data['image_ids'] ?? [])));
        $position = array_flip($imageIds);

        $selected = $record->images()
            ->whereIn('id', $imageIds)
            ->get()
            ->sortBy(fn ($image): int => $position[$image->getKey()] ?? PHP_INT_MAX)
            ->values();

        $product = DB::transaction(function () use ($record, $data, $name, $nameI18n, $descriptionI18n, $pack, $colorIds): Product {
            // Aynı ürünü ikinci kez aktarma: kayıt zaten bir ürüne bağlıysa ya
            // da aynı adla ürün varsa (name_key/slug benzersiz) yeni kayıt
            // yerine mevcut ürün güncellenir. Kod MG-1002 → MG-1008 gibi
            // değişebildiği için eşleşme koda değil isme dayanır.
            $product = $record->product ?? ProductName::duplicate($name);

            if ($product) {
                // Mevcut kod korunur: kod sistemi atar, katalog kodu yalnızca
                // boşsa yazılır. Üzerine yazmak kod ikizleri üretebilirdi.
                $attributes = [
                    'name' => $nameI18n,
                    'description' => $descriptionI18n,
                    'category_id' => $data['category_id'] ?? null,
                    'price_usd' => $data['price_usd'] !== null ? (float) $data['price_usd'] : 0,
                    'pack_size' => max(1, (int) $pack->sum()),
                    'pack_contents' => $pack
                        ->map(fn (int $quantity, string $sizeId): array => ['size_id' => $sizeId, 'quantity' => $quantity])
                        ->values()
                        ->all(),
                    'active' => true,
                    'show_on_home' => (bool) ($data['show_on_home'] ?? true),
                ];

                if (blank($product->code) && filled($data['code'] ?? null)) {
                    $attributes['code'] = trim((string) $data['code']);
                }

                $product->fill($attributes)->save();

                // Beden × renk paketi değişmiş olabilir; varyantlar yeniden kurulur.
                $product->variants()->delete();
                $this->createVariants($product, $pack->keys()->all(), $colorIds);
            } else {
                $product = Product::create([
                    'name' => $nameI18n,
                    // description NOT NULL: boş bırakılsa da dizi yazılmalı.
                    'description' => $descriptionI18n,
                    'category_id' => $data['category_id'] ?? null,
                    // Boş bırakılırsa Product modeli sıradaki kodu kendi atıyor.
                    'code' => filled($data['code'] ?? null) ? trim((string) $data['code']) : null,
                    'price_usd' => $data['price_usd'] !== null ? (float) $data['price_usd'] : 0,
                    'pack_size' => max(1, (int) $pack->sum()),
                    'pack_contents' => $pack
                        ->map(fn (int $quantity, string $sizeId): array => ['size_id' => $sizeId, 'quantity' => $quantity])
                        ->values()
                        ->all(),
                    'stock_status' => 'in_stock',
                    'active' => true,
                    'show_on_home' => (bool) ($data['show_on_home'] ?? true),
                ]);

                $this->createVariants($product, $pack->keys()->all(), $colorIds);
            }

            $record->forceFill([
                'status' => 'imported',
                'product_id' => $product->getKey(),
            ])->save();

            return $product;
        });

        // İndirme işlem dışında: ağ yavaşsa veritabanı kilidi tutulmasın.
        $this->attachMedia($product, $selected);

        return $product->refresh();
    }

    /**
     * Beden × renk kombinasyonları. Renk seçilmemişse varyant üretilmez;
     * panelin ürün formu da aynı kuralı uyguluyor.
     *
     * @param  array<int, string>  $sizeIds
     * @param  array<int, string>  $colorIds
     */
    private function createVariants(Product $product, array $sizeIds, array $colorIds): void
    {
        if ($sizeIds === [] || $colorIds === []) {
            return;
        }

        foreach ($sizeIds as $sizeId) {
            foreach ($colorIds as $colorId) {
                ProductVariant::create([
                    'product_id' => $product->getKey(),
                    'size_id' => $sizeId,
                    'color_id' => $colorId,
                    'stock_quantity' => 0,
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, TelegramChannelProductImage>  $selected
     */
    private function attachMedia(Product $product, $selected): void
    {
        $sort = 0;

        // Aynı kaynak görsel ürüne yeniden bağlanmaz (tekrar aktarımda
        // çoğaltmaya karşı); videoda da tek alan olduğu için zaten bağlı
        // video yeniden indirilmez.
        $attachedSourceIds = $product->images()
            ->whereNotNull('telegram_image_id')
            ->pluck('telegram_image_id')
            ->map(fn (string $id): string => (string) $id)
            ->all();

        foreach ($selected as $image) {
            if ($image->type === 'video') {
                // products tablosunda tek video alanı var; ilk seçilen kazanır.
                if (blank($product->video_url)) {
                    $url = $this->storeVideo($product, $image);

                    if ($url !== null) {
                        $product->forceFill(['video_url' => $url])->save();
                    }
                }

                continue;
            }

            if (in_array((string) $image->getKey(), $attachedSourceIds, true)) {
                continue;
            }

            $path = $this->storePhoto($product, $image);

            if ($path === null) {
                continue;
            }

            ProductImage::create([
                'product_id' => $product->getKey(),
                'storage_path' => $path,
                'telegram_image_id' => $image->getKey(),
                // alt NOT NULL; çok dilli alan olduğu için dizi yazılır.
                // Metin girilmiyor: alt yazısı panelden düzenlenebiliyor.
                'alt' => ['tr' => ''],
                'sort_order' => $sort,
                'is_primary' => $sort === 0,
            ]);

            $sort++;
        }
    }

    /** @return string|null Bucket'a göreli yol */
    private function storePhoto(Product $product, TelegramChannelProductImage $image): ?string
    {
        $temp = $this->download($image->url());

        if ($temp === null) {
            return null;
        }

        try {
            // ImageUploader küçültme ve WebP çevrimini yapıyor; panelden
            // yüklenen görsellerle aynı işlemden geçsinler.
            return $this->uploader->store(
                new UploadedFile($temp, Str::slug($product->code ?: 'urun').'.jpg', 'image/jpeg', test: true),
                'products',
                (string) $product->getKey(),
            );
        } finally {
            @unlink($temp);
        }
    }

    private function storeVideo(Product $product, TelegramChannelProductImage $image): ?string
    {
        if (! $image->downloadable) {
            return null;
        }

        $temp = $this->download($image->url());

        if ($temp === null) {
            return null;
        }

        // Video ImageUploader'dan geçemez (WebP'e çevirmeye çalışır);
        // olduğu gibi ürün klasörüne yazılır.
        $path = $product->getKey().'/'.now()->getTimestampMs().'-'.Str::random(6).'.mp4';

        try {
            Storage::disk(UploadTarget::disk('products'))->put(
                UploadTarget::pathPrefix('products').$path,
                file_get_contents($temp),
                ['visibility' => 'public'],
            );
        } finally {
            @unlink($temp);
        }

        return $path;
    }

    /** @return string|null Geçici dosya yolu */
    private function download(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        // Yerel dosya (tarama sırasında indirilmiş MTProto medyası, adres
        // "/storage/..."): ağdan çekmek yerine depodaki dosyayı geçici konuma
        // kopyala ki katalog klasörüne aynı işlemden geçirilebilsin.
        if (! str_starts_with($url, 'http')) {
            return $this->copyLocal($url);
        }

        try {
            $response = Http::timeout(15)->connectTimeout(10)->get($url);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $temp = tempnam(sys_get_temp_dir(), 'tg-');

        file_put_contents($temp, $response->body());

        return $temp;
    }

    /**
     * Yerel depodaki dosyayı geçici konuma kopyalar.
     *
     * Adres göreli ("/storage/telegram-images/..") ya da mutlak olabilir; her
     * durumda "storage/" sonrası bucket'a göreli yola çevrilip public_media
     * diskinden okunur.
     *
     * @return string|null Geçici dosya yolu
     */
    private function copyLocal(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $relative = preg_replace('#^/?storage/#', '', ltrim($path, '/'));

        $disk = Storage::disk('public_media');

        if ($relative === '' || ! $disk->exists($relative)) {
            return null;
        }

        $temp = tempnam(sys_get_temp_dir(), 'tg-');

        file_put_contents($temp, $disk->get($relative));

        return $temp;
    }
}
