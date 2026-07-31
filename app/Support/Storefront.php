<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * lib/storefront/utils.ts + lib/currency.ts + lib/format-price.ts karşılığı.
 */
class Storefront
{
    private const EXTERNAL_URL_PATTERN = '#^(https?://|mailto:|tel:|\#)#i';

    /**
     * Zengin editör alanlarında vitrine geçmesine izin verilen etiketler.
     */
    private const RICH_TEXT_TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li'];

    /**
     * Bilgilendirme sayfaları /{locale}/{slug} altında yaşıyor. Bu segmentler
     * routes/web.php'de sabit rotalara ait, sayfa slug'ı olarak kullanılamaz.
     */
    public const RESERVED_SLUGS = [
        'sepet', 'siparis-takibi', 'siparisler', 'siparis-basarili',
        'multimedya', 'iletisim', 'blog', 'product', 'kategori',
        'sitemap.xml', 'robots.txt',
    ];

    public static function isReservedSlug(?string $slug): bool
    {
        $slug = mb_strtolower(trim((string) $slug), 'UTF-8');

        return in_array($slug, self::RESERVED_SLUGS, true) || self::hasLocale($slug);
    }

    public static function locales(): array
    {
        return config('storefront.locales');
    }

    public static function hasLocale(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::locales(), true);
    }

    public static function isRtl(string $locale): bool
    {
        return in_array($locale, config('storefront.rtl_locales'), true);
    }

    /**
     * jsonb çok dilli alandan aktif dildeki metni seçer, yoksa tr'ye düşer.
     */
    public static function text(mixed $value, string $locale): string
    {
        if (! is_array($value)) {
            return is_string($value) ? $value : '';
        }

        return (string) ($value[$locale] ?? $value['tr'] ?? '');
    }

    /**
     * Panelde zengin editörle girilen alanlar (ürün açıklaması) için güvenli HTML.
     *
     * - Editör öncesi düz metin kayıtları satır sonlarıyla korunur.
     * - Yalnızca izin verilen etiketler kalır, tüm attribute'lar (style, class,
     *   onclick ...) atılır; böylece vitrin tasarımı ve güvenliği bozulmaz.
     */
    public static function richText(mixed $value, string $locale): string
    {
        $raw = trim(self::text($value, $locale));

        if ($raw === '') {
            return '';
        }

        if (! preg_match('/<[a-z][a-z0-9]*\b[^>]*>/i', $raw)) {
            return nl2br(e($raw));
        }

        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', '', $raw) ?? '';
        $html = strip_tags($html, '<'.implode('><', self::RICH_TEXT_TAGS).'>');
        // Açılış etiketlerinde attribute bırakma.
        $html = preg_replace('#<\s*([a-z0-9]+)\b[^>]*?(/?)>#i', '<$1$2>', $html) ?? '';

        return trim($html);
    }

    /**
     * Zengin metinden meta description / bildirim gibi düz metin alanlar için sade karşılık.
     */
    public static function plainText(mixed $value, string $locale): string
    {
        $raw = self::text($value, $locale);

        if ($raw === '') {
            return '';
        }

        $raw = preg_replace('#<(?:br|/p|/li|/ul|/ol)\s*/?>#i', ' ', $raw) ?? $raw;
        $text = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * Site linklerindeki URL'yi aktif dile taşır; dış linkleri olduğu gibi bırakır.
     */
    public static function href(?string $url, string $locale): string
    {
        $url = (string) $url;

        if ($url === '' || preg_match(self::EXTERNAL_URL_PATTERN, $url)) {
            return $url;
        }

        // Kaynak localizedHref, "/tr#urunler" gibi değerlerde dil ön ekini
        // tanıyamayıp "/tr/tr#urunler" üretiyordu; hash/query'yi ayırıp düzeltiyoruz.
        $suffix = '';
        if (preg_match('/[#?]/', $url, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];
            $suffix = substr($url, $offset);
            $url = substr($url, 0, $offset);
        }

        $segments = explode('/', str_starts_with($url, '/') ? $url : '/'.$url);

        if (self::hasLocale($segments[1] ?? '')) {
            array_splice($segments, 1, 1);
        }

        $path = implode('/', $segments) ?: '/';

        return '/'.$locale.($path === '/' ? '' : $path).$suffix;
    }

    public static function productHref(string $locale, string $slug): string
    {
        return '/'.$locale.'/product/'.rawurlencode($slug);
    }

    /**
     * Yerel veya Supabase Storage public URL'sini üretir.
     */
    public static function storageUrl(string $bucketKey, ?string $path): string
    {
        if (! $path) {
            return (string) config('storefront.placeholder_image');
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        $bucket = config('storefront.buckets.'.$bucketKey, $bucketKey);

        // Alwaysdata: görseller storage/app/public altında kalıcı tutulur.
        if (! UploadTarget::usesSupabase()) {
            $local = Storage::disk('public_media');

            if ($local->exists($bucket.'/'.ltrim($path, '/'))) {
                return $local->url($bucket.'/'.ltrim($path, '/'));
            }

            // Önceki lokal geliştirme dizinindeki dosyaları kırmadan göster.
            $legacy = Storage::disk('local_supabase_stub');
            if ($legacy->exists($bucket.'/'.ltrim($path, '/'))) {
                return $legacy->url($bucket.'/'.ltrim($path, '/'));
            }
        }

        $base = rtrim((string) config('storefront.storage_url'), '/');

        return $base !== ''
            ? $base.'/'.$bucket.'/'.ltrim($path, '/')
            : '';
    }

    /**
     * storageUrl'ün üstüne Supabase image transformation ekler.
     *
     * object/public yerine render/image/public uçlarını kullanır: görsel
     * istenen genişliğe küçültülür ve tarayıcı Accept başlığına göre webp
     * döner. Transform ucu ayrıca cache-control: max-age=3600 gönderir,
     * ham object/public ucu ise no-cache gönderiyor.
     */
    public static function imageUrl(string $bucketKey, ?string $path, int $width, int $quality = 75): string
    {
        $url = self::storageUrl($bucketKey, $path);

        if (! $url || ! str_contains($url, '/storage/v1/object/public/')) {
            return $url;
        }

        $url = str_replace('/storage/v1/object/public/', '/storage/v1/render/image/public/', $url);

        return $url.'?'.http_build_query([
            'width' => $width,
            'quality' => $quality,
            // Supabase'in varsayılan "cover" modu yalnızca width
            // verildiğinde görseli yataydan kırpar. Kaynak oranını koru;
            // gerekli kadrajı vitrindeki object-fit: cover yapsın.
            'resize' => 'contain',
        ]);
    }

    /**
     * Birincil görsel önce, sonra sort_order.
     *
     * @param  iterable<int, mixed>  $images
     * @return array<int, mixed>
     */
    public static function sortedImages(mixed $images): array
    {
        $list = collect($images ?? [])->all();

        usort($list, function ($first, $second) {
            $primary = ((int) ($second->is_primary ?? 0)) <=> ((int) ($first->is_primary ?? 0));

            return $primary !== 0 ? $primary : (($first->sort_order ?? 0) <=> ($second->sort_order ?? 0));
        });

        return $list;
    }

    public static function youtubeEmbedUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $pattern = '#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})#';

        return preg_match($pattern, $url, $matches) ? 'https://www.youtube.com/embed/'.$matches[1] : null;
    }

    public static function isCartLink(mixed $link): bool
    {
        $key = $link->link_key ?? '';
        $trLabel = is_array($link->label ?? null) ? ($link->label['tr'] ?? '') : '';

        return $key === 'cart' || $key === 'sepet' || mb_strtolower($trLabel, 'UTF-8') === 'sepet';
    }

    public static function navigationHref(mixed $link, string $locale): string
    {
        $key = mb_strtolower((string) ($link->link_key ?? ''), 'UTF-8');

        return match ($key) {
            'cart', 'sepet' => '/'.$locale.'/sepet',
            'tracking', 'siparis-takibi' => '/'.$locale.'/siparis-takibi',
            'categories', 'kategori' => '/'.$locale.'/kategori',
            'media', 'multimedia', 'multimedya' => '/'.$locale.'/multimedya',
            'contact', 'iletisim' => '/'.$locale.'/iletisim',
            'blog' => '/'.$locale.'/blog',
            default => self::href($link->url ?? '', $locale),
        };
    }

    /**
     * currencies satırından gösterim bilgisi (sembol + konum).
     */
    public static function currencyDisplay(mixed $row): array
    {
        $symbol = $row->symbol ?? null;

        if (! $symbol) {
            return ['symbol' => 'TL', 'position' => 'suffix'];
        }

        return [
            'symbol' => $symbol,
            'position' => ($row->position ?? null) === 'prefix' ? 'prefix' : 'suffix',
        ];
    }

    /**
     * Para birimi kodunu gösterime çevirir; bulunamazsa varsayılana düşer.
     */
    public static function resolveCurrency(array $resolver, ?string $code): array
    {
        return ($code && isset($resolver['byCode'][$code]))
            ? $resolver['byCode'][$code]
            : $resolver['fallback'];
    }

    public static function currencyCodeForLocale(string $locale): string
    {
        return match ($locale) {
            'tr' => 'TRY',
            'ar', 'fa' => 'USD',
            default => 'EUR',
        };
    }

    /**
     * lib/format-price.ts: tr-TR biçimi, iki ondalık, sembol önde/arkada.
     */
    public static function formatPrice(mixed $value, ?array $currency = null): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $currency ??= ['symbol' => 'TL', 'position' => 'suffix'];
        $amount = number_format((float) $value, 2, ',', '.');

        return $currency['position'] === 'prefix'
            ? $currency['symbol'].$amount
            : $amount.' '.$currency['symbol'];
    }
}
