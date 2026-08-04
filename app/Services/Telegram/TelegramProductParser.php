<?php

namespace App\Services\Telegram;

/**
 * Telegram mesaj metninden ürün alanlarını çıkarır.
 *
 * Üç kanal üç ayrı düzende yazıyor ve hiçbiri tutarlı değil:
 *
 *   @asprinntrendy  "Krınkıl Keten Takım / Seri 5 li 2s 2m 1l MODEL 7963 / Fiyat 22$ / Natur Haki Beyaz Renkleriyle"
 *
 *   @naturallover   "Code:5836 / Standart / Pack:5 / Price:5$"      (ürün adı yok)
 *
 *   @rosearyaa      "Keten maxi Elbise / S2m2L1"                    (fiyat, kod yok)
 *
 * Bu yüzden kanala göre ayrı parser yerine tek toleranslı parser var: her alan
 * kendi kalıbını metnin tamamında arar, bulamazsa null döner. Bulunamayan alan
 * panelde "eksik" olarak işaretlenir; uydurma yapılmaz.
 */
class TelegramProductParser
{
    /** Büyükten küçüğe sıralı: XXL, XL'den önce denenmeli. */
    private const SIZE_TOKENS = ['XXXL', 'XXL', 'XL', 'XS', 'S', 'M', 'L'];

    /** Ürün adı sayılmayacak satırlar. */
    private const NOISE = '/^(standart|standar size|standard|beden|size|pack|paket|fiyat|price|kod|code|model|seri|whatsapp|http|adet)\b/iu';

    /**
     * @return array{
     *   name: ?string, product_code: ?string, price: ?float, currency: ?string,
     *   pack_size: ?int, size_series: ?string, sizes: ?array<string,int>, colors: ?array<int,string>
     * }
     */
    public function parse(?string $text): array
    {
        $text = $this->clean((string) $text);

        $sizeSeries = $this->sizeSeries($text);

        return [
            'name' => $this->name($text),
            'product_code' => $this->productCode($text),
            'price' => $this->price($text),
            'currency' => $this->currency($text),
            'pack_size' => $this->packSize($text),
            'size_series' => $sizeSeries,
            'sizes' => $sizeSeries ? $this->sizes($sizeSeries) : null,
            'colors' => $this->colors($text),
        ];
    }

    /** Görünmez boşluklar ve süs karakterleri metni bozuyor. */
    private function clean(string $text): string
    {
        $text = str_replace(["\xC2\xA0", "\xE2\x80\x8B"], ' ', $text);

        return trim($text);
    }

    /**
     * @return array<int, string>
     */
    private function lines(string $text): array
    {
        return array_values(array_filter(
            array_map(fn (string $line): string => trim($this->stripDecoration($line)), explode("\n", $text)),
            fn (string $line): bool => $line !== '',
        ));
    }

    /** Emoji, onay işareti ve madde imlerini atar. */
    private function stripDecoration(string $line): string
    {
        // Emoji blokları + ✓ ✔ ❤ gibi işaretler.
        $line = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{2B00}-\x{2BFF}]/u', ' ', $line) ?? $line;

        return trim($line, " \t\n\r.-–—•*");
    }

    private function name(string $text): ?string
    {
        foreach ($this->lines($text) as $line) {
            // "Saten Gömlek Size:S-M-L-XL Pack:4 Price:7,5$" gibi tek satırda
            // toplanmış kayıtlarda ad, ilk etiketten önceki kısımdır.
            $line = trim(preg_split('/\b(size|pack|price|fiyat|kod|code|model|beden|seri)\b\s*[:.]/iu', $line)[0] ?? $line);

            if ($line === '' || preg_match(self::NOISE, $line)) {
                continue;
            }

            // Sadece beden serisi ya da kumaş oranı olan satır ad değildir.
            if ($this->looksLikeSizeSeries($line) || preg_match('/^%\s*\d+/u', $line)) {
                continue;
            }

            // En az bir anlamlı kelime içermeli; "S2m2L1" elenir.
            if (! preg_match('/\p{L}{3,}/u', $line)) {
                continue;
            }

            return $line;
        }

        return null;
    }

    private function productCode(string $text): ?string
    {
        if (preg_match('/\b(?:kod|code|model)\b\s*[:.]?\s*(\d{2,8})/iu', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    private function price(string $text): ?float
    {
        // Önce etiketli biçim ("Fiyat 22$", "Price:7,5$"), sonra çıplak "22$".
        if (preg_match('/\b(?:fiyat|price)\b\s*[:.]?\s*(\d+(?:[.,]\d+)?)/iu', $text, $m)
            || preg_match('/(\d+(?:[.,]\d+)?)\s*(?:\$|usd|€|eur|₺|tl\b)/iu', $text, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        return null;
    }

    private function currency(string $text): ?string
    {
        if (preg_match('/\$|\busd\b/iu', $text)) {
            return 'USD';
        }

        if (preg_match('/€|\beur\b/iu', $text)) {
            return 'EUR';
        }

        if (preg_match('/₺|\btl\b|\btry\b/iu', $text)) {
            return 'TRY';
        }

        return null;
    }

    private function packSize(string $text): ?int
    {
        // "Pack:5" ya da "Seri 5 li".
        if (preg_match('/\bpack\b\s*[:.]?\s*(\d+)/iu', $text, $m)
            || preg_match('/\bseri\b\s*[:.]?\s*(\d+)\s*li\b/iu', $text, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /** Beden serisinin ham metni; doğrulama için olduğu gibi saklanır. */
    private function sizeSeries(string $text): ?string
    {
        // Etiketli: "Beden: SS MM L", "Size:S-M-L-XL"
        if (preg_match('/\b(?:beden|size)\b\s*[:.]?\s*([^\n]+)/iu', $text, $m)) {
            $value = $this->stripDecoration($m[1]);

            // "Size" etiketinden sonra başka etiket gelebiliyor: "Size:S-M-L-XL Pack:4"
            $value = trim(preg_split('/\b(pack|price|fiyat|kod|code|model)\b\s*[:.]?/iu', $value)[0] ?? $value);

            if ($value !== '' && ! preg_match('/^standar/iu', $value)) {
                return $value;
            }
        }

        // "Seri 5 li 2s 2m 1l MODEL 7963" → serinin dağılımı "li"den sonra.
        if (preg_match('/\bseri\b\s*\d*\s*li\b\s*([^\n]+)/iu', $text, $m)) {
            $value = trim(preg_split('/\b(model|kod|code|fiyat|price|pack)\b/iu', $this->stripDecoration($m[1]))[0] ?? '');

            if ($value !== '' && $this->looksLikeSizeSeries($value)) {
                return $value;
            }
        }

        // Kendi başına duran beden satırı: "S2m2L1XL1", "2s 2m 1l"
        foreach ($this->lines($text) as $line) {
            if ($this->looksLikeSizeSeries($line)) {
                return $line;
            }
        }

        return null;
    }

    private function looksLikeSizeSeries(string $line): bool
    {
        $line = trim($line);

        if ($line === '' || mb_strlen($line) > 40) {
            return false;
        }

        // Yalnızca beden harfleri, rakamlar ve ayraçlardan oluşmalı.
        return (bool) preg_match('/^[\s\d\/,\-–xsmlXSML]+$/u', $line)
            && (bool) preg_match('/[smlxSMLX]/u', $line);
    }

    /**
     * Ham seriyi bedene göre adede çevirir: "2s 2m 1l" → ['S' => 2, 'M' => 2, 'L' => 1]
     *
     * Üç yazım var ve hangisi olduğu ilk karakterden anlaşılıyor:
     *   rakam önce  → "2s 2m 1l"      (@asprinntrendy)
     *   rakam sonra → "S2m2L1XL1"     (@rosearyaa)
     *   sadece harf → "S/M/L/XL", "SS MM L"
     *
     * Son biçimde tekrar eden harf adet demek: "SS MM L" = 2 S, 2 M, 1 L.
     *
     * @return array<string, int>|null
     */
    public function sizes(string $series): ?array
    {
        $normalized = mb_strtoupper(trim($series));

        if ($normalized === '') {
            return null;
        }

        $pattern = implode('|', self::SIZE_TOKENS);

        // Rakamla başlıyorsa "2S 2M 1L" biçimi, değilse "S2M2L1" / "SS MM L".
        $result = ctype_digit($normalized[0])
            ? $this->countsBefore($normalized, $pattern)
            : $this->countsAfter($normalized, $pattern);

        return $result === [] ? null : $result;
    }

    /** "2S 2M 1L" biçimi. */
    private function countsBefore(string $text, string $pattern): array
    {
        preg_match_all('/(\d+)\s*('.$pattern.')(?![A-Z])/u', $text, $matches, PREG_SET_ORDER);

        $sizes = [];

        foreach ($matches as $match) {
            $sizes[$match[2]] = ($sizes[$match[2]] ?? 0) + (int) $match[1];
        }

        return $sizes;
    }

    /** "S2M2L1XL1" ya da harfle biten "S/M/L/XL", "SS MM L" biçimleri. */
    private function countsAfter(string $text, string $pattern): array
    {
        preg_match_all('/('.$pattern.')\s*(\d*)/u', $text, $matches, PREG_SET_ORDER);

        $sizes = [];

        // Rakam varsa adet odur; yoksa tekrar eden harfler adedi verir
        // ("SS MM L" → S:2, M:2, L:1).
        foreach ($matches as $match) {
            $count = $match[2] === '' ? 1 : (int) $match[2];
            $sizes[$match[1]] = ($sizes[$match[1]] ?? 0) + $count;
        }

        return $sizes;
    }

    /**
     * En fazla kaç kelimelik renk tonu denensin ("gül kurusu" = 2).
     */
    private const COLOR_PHRASE_MAX = 3;

    /**
     * "açık/koyu mavi" gibi ton üreten önekler. Ardından bilinen bir renk
     * gelirse tek renk olarak birleştirilir ("Açık Mavi").
     */
    private const COLOR_MODIFIERS = ['açık', 'acik', 'koyu', 'kırık', 'kirik', 'antik', 'pastel', 'neon'];

    /**
     * Bilinen Türkçe toptan tekstil renkleri (küçük harf, normalize).
     *
     * "renkler" gibi bir anahtar kelimeye bağlı kalmadan renk çıkarabilmek için
     * sözlük yaklaşımı: yalnızca burada geçen kelimeler renk sayılır, böylece
     * ürün adındaki "keten", "gömlek" gibi kelimeler yanlışlıkla renk olmaz.
     * Çok kelimeli tonlar ("kırık beyaz", "gül kurusu") ayrı renktir ve en-uzun
     * eşleşmeyle tek parça alınır. Listede olmayan renk panelden elle eklenir.
     *
     * @var array<string, true>
     */
    private const COLOR_LEXICON = [
        'siyah' => true, 'beyaz' => true, 'gri' => true, 'kırmızı' => true, 'kirmizi' => true,
        'mavi' => true, 'yeşil' => true, 'yesil' => true, 'sarı' => true, 'sari' => true,
        'turuncu' => true, 'mor' => true, 'pembe' => true, 'kahverengi' => true, 'kahve' => true,
        'lacivert' => true, 'bordo' => true, 'bej' => true, 'krem' => true, 'ekru' => true,
        'haki' => true, 'hardal' => true, 'vizon' => true, 'antrasit' => true, 'natur' => true,
        'zeytin' => true, 'füme' => true, 'fume' => true, 'taba' => true, 'camel' => true,
        'gümüş' => true, 'gumus' => true, 'altın' => true, 'altin' => true, 'somon' => true,
        'mint' => true, 'turkuaz' => true, 'petrol' => true, 'indigo' => true, 'fuşya' => true,
        'fusya' => true, 'pudra' => true, 'vişne' => true, 'visne' => true, 'mürdüm' => true,
        'murdum' => true, 'tütün' => true, 'tutun' => true, 'kiremit' => true, 'somon' => true,
        // Çok kelimeli tonlar (ayrı hex'li renkler).
        'kırık beyaz' => true, 'kirik beyaz' => true, 'gül kurusu' => true, 'gul kurusu' => true,
        'buz mavisi' => true, 'deniz mavisi' => true, 'bebe mavisi' => true, 'saks mavisi' => true,
        'yavru ağzı' => true, 'yavru agzi' => true,
    ];

    /**
     * Metindeki bilinen renkleri geçtikleri sırayla çıkarır.
     *
     * @return array<int, string>|null
     */
    private function colors(string $text): ?array
    {
        $words = array_values(array_filter(
            array_map(fn (string $w): string => trim($this->stripDecoration($w)), preg_split('/[\s,\/]+/u', $text) ?: []),
            fn (string $w): bool => $w !== '',
        ));

        $lower = array_map(fn (string $w): string => mb_strtolower($w, 'UTF-8'), $words);

        $found = [];
        $count = count($words);
        $i = 0;

        while ($i < $count) {
            $take = 0;

            // Uzun tonları önce dene: "gül kurusu", "açık mavi", "beyaz".
            for ($len = min(self::COLOR_PHRASE_MAX, $count - $i); $len >= 1; $len--) {
                $phrase = implode(' ', array_slice($lower, $i, $len));

                $modifierCombo = $len >= 2
                    && in_array($lower[$i], self::COLOR_MODIFIERS, true)
                    && isset(self::COLOR_LEXICON[implode(' ', array_slice($lower, $i + 1, $len - 1))]);

                if (isset(self::COLOR_LEXICON[$phrase]) || $modifierCombo) {
                    $take = $len;
                    break;
                }
            }

            if ($take === 0) {
                $i++;

                continue;
            }

            $found[] = mb_convert_case(implode(' ', array_slice($words, $i, $take)), MB_CASE_TITLE, 'UTF-8');
            $i += $take;
        }

        // Aynı renk iki kez geçse tek yaz; sırayı koru.
        $unique = [];
        foreach ($found as $color) {
            $unique[mb_strtolower($color, 'UTF-8')] = $color;
        }

        return $unique === [] ? null : array_values($unique);
    }
}
