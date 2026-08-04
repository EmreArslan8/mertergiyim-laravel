<?php

namespace App\Services\Telegram;

/**
 * Mesajları ürünlere gruplar.
 *
 * Kanallar ürünü tek mesajda paylaşmıyor. İki düzen var ve ikisi de gerçek:
 *
 *   @asprinntrendy — önce açıklama metni, sonra fotoğraf albümleri:
 *       48320  METİN "Krınkıl Keten... Natur Haki Beyaz Renkleriyle"
 *       48321  4 foto   ← Natur
 *       48325  3 foto   ← Haki
 *       48328  3 foto   ← Beyaz
 *     Albüm sayısı metinde sayılan renk sayısına denk geliyor, bu yüzden her
 *     albüm ayrı `album_index` ile saklanıyor.
 *
 *   @rosearyaa / @naturallover — metin doğrudan albümün altyazısı.
 *
 * Bu yüzden gruplama yönü ayarlanabilir: metinden sonra gelen medya ürüne mi
 * ait, yoksa metinden önce gelen mi.
 */
class TelegramProductGrouper
{
    public const MEDIA_AFTER_TEXT = 'after';

    public const MEDIA_BEFORE_TEXT = 'before';

    /**
     * Duyuru/reklam metni işaretleri. İçinde fiyat geçse bile bunları taşıyan
     * metin ürün açmaz; araya giren duyurunun sonraki ürünün albümlerini
     * kapmasını önler.
     */
    private const ANNOUNCEMENT = '/(kampanya|indirim|kupon|sipariş|siparis|iletişim|iletisim|whatsapp|wa\.me|t\.me\/|https?:|(?<![a-zçğıöşü])dm(?![a-zçğıöşü])|takip\s*et|günayd|gunayd|hayırlı|hayirli|müjde|mujde|tebrik)/iu';

    /**
     * Parser, metnin ürün sinyali (fiyat/kod/beden) taşıyıp taşımadığını
     * anlamak için kullanılıyor. Opsiyonel enjekte: birim testleri argümansız
     * kurabilsin, uygulama konteynerden geçirsin.
     */
    public function __construct(
        private readonly TelegramProductParser $parser = new TelegramProductParser,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $messages  TelegramPageReader çıktısı
     * @param  ?string  $direction  Elle zorlanmazsa örnekten otomatik seçilir.
     * @return array<int, array<string, mixed>>
     */
    public function group(array $messages, ?string $direction = null): array
    {
        usort($messages, fn (array $a, array $b): int => $a['id'] <=> $b['id']);

        $direction ??= $this->detectDirection($messages);

        if ($direction === self::MEDIA_BEFORE_TEXT) {
            return $this->groupMediaBeforeText($messages);
        }

        // Metin-önce düzende, pencere başında metni önceki pencerede kalmış
        // öksüz albümleri at (adı/fiyatı olmayan hayalet ürün oluşmasın).
        return $this->groupMediaAfterText($this->dropOrphanLeadingMedia($messages));
    }

    /**
     * Kanalın düzenini örnekten çıkarır.
     *
     * Bazı kanallar albümü önce, açıklama metnini ayrı bir mesajla sonra atıyor
     * (media-before-text); çoğu ise metni/altyazıyı önce koyuyor. Sınır kuralı:
     * pencere medya-only bir mesajla BAŞLIYOR ve metinli bir mesajla BİTİYORSA
     * albüm-önce düzendir; aksi halde varsayılan metin-önce. Belirsizde (ör.
     * altyazılı kanallar) metin-önce kalır.
     *
     * @param  array<int, array<string, mixed>>  $messages  id'ye göre sıralı
     */
    private function detectDirection(array $messages): string
    {
        $mediaOnly = static fn (array $m): bool => $m['text'] === ''
            && ($m['photos'] !== [] || $m['videos'] !== []);

        $first = $messages[0] ?? null;
        $last = $messages !== [] ? $messages[array_key_last($messages)] : null;

        if ($first !== null && $last !== null && $mediaOnly($first) && ! $mediaOnly($last)) {
            return self::MEDIA_BEFORE_TEXT;
        }

        return self::MEDIA_AFTER_TEXT;
    }

    /**
     * Metin-önce düzende, pencerenin en eskisinde kalan metinsiz albümleri atar:
     * açıklaması pencerenin dışında kalmış, adı/fiyatı olmayan hayalet ürünler.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private function dropOrphanLeadingMedia(array $messages): array
    {
        while ($messages !== [] && $messages[0]['text'] === '') {
            array_shift($messages);
        }

        return $messages;
    }

    /**
     * Varsayılan: metin ürünü açar, peşinden gelen medya mesajları ona bağlanır.
     *
     * Ürün-sinyali kapısı: altyazılı medya her zaman ürün açar; medyasız metin
     * ancak ürün sinyali taşıyorsa (fiyat/kod/beden, duyuru işareti yok) açar.
     * Sinyalsiz metin (duyuru/reklam) ürün açmaz ve ayırıcı olur; böylece
     * sonraki ürünün albümlerini kapmaz. Açık ürün yokken gelen medya sessizce
     * kaybolmaz, öksüz aday olur (panelde eksik alan uyarısıyla görünür).
     */
    private function groupMediaAfterText(array $messages): array
    {
        $products = [];
        $openIndex = null;

        foreach ($messages as $message) {
            $hasText = $message['text'] !== '';
            $hasMedia = $message['photos'] !== [] || $message['videos'] !== [];

            if (! $hasText && ! $hasMedia) {
                continue;
            }

            if ($hasText) {
                if ($hasMedia || $this->opensProduct($message['text'])) {
                    $products[] = $this->start($message);
                    $openIndex = count($products) - 1;

                    if ($hasMedia) {
                        $this->attach($products[$openIndex], $message);
                    }
                } else {
                    // Gürültü/duyuru: ürün açmaz, ayırıcı olur.
                    $openIndex = null;
                }

                continue;
            }

            // Medya-only mesaj: açık ürüne bağlanır; açık ürün yoksa (metni
            // pencere dışında kalmış ya da araya duyuru girmiş) öksüz aday olur.
            if ($openIndex === null) {
                $products[] = $this->start($message);
                $openIndex = count($products) - 1;
            }

            $this->attach($products[$openIndex], $message);
        }

        return array_map($this->finalize(...), $products);
    }

    /**
     * Medyasız bir metnin ürün açıp açmayacağı: fiyat, ürün kodu ya da beden
     * serisi taşıyorsa ürün; duyuru işareti taşıyorsa (fiyat geçse bile) değil.
     */
    private function opensProduct(string $text): bool
    {
        if (trim($text) === '' || preg_match(self::ANNOUNCEMENT, $text)) {
            return false;
        }

        $parsed = $this->parser->parse($text);

        return $parsed['price'] !== null
            || $parsed['product_code'] !== null
            || $parsed['size_series'] !== null;
    }

    /**
     * Ters düzen: medya birikir, metin gelince o ürüne bağlanır.
     */
    private function groupMediaBeforeText(array $messages): array
    {
        $products = [];
        $pending = [];

        foreach ($messages as $message) {
            $hasText = $message['text'] !== '';
            $hasMedia = $message['photos'] !== [] || $message['videos'] !== [];

            if (! $hasText) {
                if ($hasMedia) {
                    $pending[] = $message;
                }

                continue;
            }

            $product = $this->start($message);

            foreach ($pending as $media) {
                $this->attach($product, $media);
            }

            if ($hasMedia) {
                $this->attach($product, $message);
            }

            $pending = [];
            $products[] = $product;
        }

        // Metni hiç gelmemiş medya da kaybolmasın.
        foreach ($pending as $media) {
            $product = $this->start($media);
            $this->attach($product, $media);
            $products[] = $product;
        }

        return array_map($this->finalize(...), $products);
    }

    /**
     * @return array<string, mixed>
     */
    private function start(array $message): array
    {
        return [
            'message_id' => $message['id'],
            'text' => $message['text'],
            'posted_at' => $message['posted_at'],
            'media' => [],
            'album_count' => 0,
        ];
    }

    /** Bir mesajdaki tüm medyayı ürüne, kendi albüm sırasıyla ekler. */
    private function attach(array &$product, array $message): void
    {
        $albumIndex = $product['album_count'];
        $sort = 0;

        foreach ($message['photos'] as $url) {
            $product['media'][] = [
                'type' => 'photo',
                'message_id' => $message['id'],
                'album_index' => $albumIndex,
                'sort_order' => $sort++,
                'source_url' => $url,
                'poster_url' => null,
                'duration' => null,
                'downloadable' => true,
            ];
        }

        foreach ($message['videos'] as $video) {
            $product['media'][] = [
                'type' => 'video',
                'message_id' => $message['id'],
                'album_index' => $albumIndex,
                'sort_order' => $sort++,
                'source_url' => $video['url'],
                'poster_url' => $video['poster'],
                'duration' => $video['duration'],
                // mp4 adresi yoksa Telegram dosyayı vermiyor demektir.
                'downloadable' => $video['url'] !== null,
            ];
        }

        $product['album_count']++;
    }

    /**
     * @return array<string, mixed>
     */
    private function finalize(array $product): array
    {
        unset($product['album_count']);

        $product['photo_count'] = count(array_filter($product['media'], fn (array $m): bool => $m['type'] === 'photo'));
        $product['video_count'] = count(array_filter($product['media'], fn (array $m): bool => $m['type'] === 'video'));

        return $product;
    }
}
