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
    private const RICH_TEXT_TAGS = [
        'p', 'br', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u', 's', 'del',
        'sub', 'sup', 'mark', 'pre', 'code', 'blockquote', 'ul', 'ol', 'li',
        'a', 'hr', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
    ];

    /**
     * Bilgilendirme sayfaları varsayılan dilde /{slug}, diğer dillerde
     * /{locale}/{slug} altında yaşar. Bu segmentler sabit rotalara aittir.
     */
    public const RESERVED_SLUGS = [
        'sepet', 'siparis-takibi', 'siparisler', 'siparis-basarili',
        'multimedya', 'iletisim', 'blog', 'product', 'kategori',
        'banka-hesaplarimiz', 'sitemap.xml', 'robots.txt',
    ];

    /**
     * Panelde seçilebilen sosyal medya platformları: anahtar => görünen ad.
     * Anahtar, eski tekil ayar alanlarının ön eki ile aynı (instagramUrl gibi).
     */
    public const SOCIAL_PLATFORMS = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'x' => 'X',
        'pinterest' => 'Pinterest',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
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
     * Ürün adlarında yalnızca kelimelerin ilk harflerini büyütür.
     *
     * CSS `text-transform: capitalize` Türkçedeki i/İ dönüşümünü güvenilir
     * yapmadığı için vitrinde dile duyarlı dönüşüm sunucu tarafında yapılır.
     * Kelimelerin kalanına dokunulmaz; mevcut kısaltmalar korunur.
     */
    public static function titleCase(string $value, string $locale): string
    {
        return preg_replace_callback(
            '/(^|[\s\p{Pd}\/&(\[])(\p{L})/u',
            static function (array $match) use ($locale): string {
                $letter = $match[2];
                $upper = $locale === 'tr'
                    ? match ($letter) {
                        'i' => 'İ',
                        'ı' => 'I',
                        default => mb_strtoupper($letter, 'UTF-8'),
                    }
                : mb_strtoupper($letter, 'UTF-8');

                return $match[1].$upper;
            },
            $value,
        ) ?? $value;
    }

    /**
     * Panelde zengin editörle girilen alanlar (ürün açıklaması) için güvenli HTML.
     *
     * - Editör öncesi düz metin kayıtları satır sonlarıyla korunur.
     * - Yalnızca izin verilen etiketler kalır. Bağlantılarda güvenli href,
     *   target ve rel dışında tüm attribute'lar atılır.
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
        $html = preg_replace_callback(
            '#<\s*(/?)\s*([a-z0-9]+)\b([^>]*)>#i',
            static function (array $matches): string {
                $closing = $matches[1] === '/';
                $tag = strtolower($matches[2]);

                if ($closing) {
                    return '</'.$tag.'>';
                }

                if ($tag === 'a') {
                    return self::sanitizedAnchor($matches[3]);
                }

                return self::sanitizedRichTextTag($tag, $matches[3]);
            },
            $html
        ) ?? '';

        return trim($html);
    }

    private static function sanitizedRichTextTag(string $tag, string $attributes): string
    {
        $safeAttributes = [];

        if (in_array($tag, ['p', 'h2', 'h3', 'h4', 'blockquote', 'li', 'th', 'td'], true)
            && preg_match('/\bstyle\s*=\s*(["\'])(.*?)\1/is', $attributes, $style)
            && preg_match('/(?:^|;)\s*text-align\s*:\s*(left|center|right|justify|start|end)\s*(?:;|$)/i', $style[2], $alignment)) {
            $safeAttributes[] = 'style="text-align: '.strtolower($alignment[1]).'"';
        }

        if (in_array($tag, ['th', 'td'], true)) {
            foreach (['colspan', 'rowspan'] as $attribute) {
                if (preg_match('/\b'.$attribute.'\s*=\s*(["\']?)(\d{1,2})\1/i', $attributes, $match)) {
                    $safeAttributes[] = $attribute.'="'.max(1, min(20, (int) $match[2])).'"';
                }
            }
        }

        return '<'.$tag.($safeAttributes === [] ? '' : ' '.implode(' ', $safeAttributes)).'>';
    }

    private static function sanitizedAnchor(string $attributes): string
    {
        if (! preg_match('/\bhref\s*=\s*(["\'])(.*?)\1/is', $attributes, $hrefMatch)) {
            return '<a>';
        }

        $href = html_entity_decode(trim($hrefMatch[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (! self::isSafeRichTextHref($href)) {
            return '<a>';
        }

        $anchor = '<a href="'.e($href).'"';

        if (preg_match('/\btarget\s*=\s*(["\'])_blank\1/i', $attributes)) {
            $anchor .= ' target="_blank" rel="noopener noreferrer"';
        }

        return $anchor.'>';
    }

    private static function isSafeRichTextHref(string $href): bool
    {
        if ($href === '' || preg_match('/[\x00-\x1F\x7F]/u', $href)) {
            return false;
        }

        if (str_starts_with($href, '#')) {
            return true;
        }

        if (str_starts_with($href, '/') && ! str_starts_with($href, '//')) {
            return true;
        }

        return (bool) preg_match('#^(https?://|mailto:|tel:)#i', $href);
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

        $raw = preg_replace(
            '#<(?:br|hr|/p|/h2|/h3|/h4|/blockquote|/li|/ul|/ol|/th|/td|/tr|/table)\s*/?>#i',
            ' ',
            $raw,
        ) ?? $raw;
        $text = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * Dil ön eki. Varsayılan dil kökte yaşar (/iletisim), diğerleri ön ekli
     * (/en/iletisim). Varsayılan dil panelden değiştirilebilir.
     */
    public static function prefix(string $locale): string
    {
        return $locale === BrandSettings::defaultLocale() ? '' : '/'.$locale;
    }

    /**
     * Dil ön eki + yol: localePath('en', '/iletisim') → "/en/iletisim".
     */
    public static function localePath(string $locale, string $path = ''): string
    {
        $path = $path === '/' ? '' : $path;

        return self::prefix($locale).$path ?: '/';
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

        return self::localePath($locale, $path).$suffix;
    }

    public static function productHref(string $locale, string $slug): string
    {
        return self::localePath($locale, '/product/'.rawurlencode($slug));
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
     * Yerel bir /storage adresinin gösterdiği dosyanın ham içeriğini döndürür.
     *
     * Görsellerin url()'i yerelde göreli bir /storage yolu üretir; bunu HTTP ile
     * çekmek gereksiz (sunucu kendine istek atar) ve alwaysdata'da çalışmaz.
     * Diskten okumak isteyen (ör. Gemini'ye görsel gönderen ad üretici) burayı
     * kullanır: adresin /storage önekini atıp public_media diskinden okur. Uzak
     * (http) adresler ve diskte olmayan dosyalar için null döner.
     */
    public static function localStorageContents(?string $url): ?string
    {
        if (! $url || str_starts_with($url, 'http')) {
            return null;
        }

        // public_media diskinin herkese açık öneki (varsayılan /storage).
        $prefix = trim((string) config('filesystems.disks.public_media.url', '/storage'), '/');
        $relative = ltrim($url, '/');

        if ($prefix !== '' && str_starts_with($relative, $prefix.'/')) {
            $relative = substr($relative, strlen($prefix) + 1);
        }

        foreach (['public_media', 'local_supabase_stub'] as $diskName) {
            $disk = Storage::disk($diskName);

            if ($disk->exists($relative)) {
                return $disk->get($relative);
            }
        }

        return null;
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

    /**
     * Panelde sıralanan sosyal medya bağlantıları (value.general.socialLinks).
     *
     * @return array<int, array{platform: string, label: string, url: string}>
     */
    public static function socialLinks(mixed $settings): array
    {
        $links = [];

        foreach ((array) (((array) $settings)['socialLinks'] ?? []) as $item) {
            $item = (array) $item;
            $url = trim((string) ($item['url'] ?? ''));
            $platform = (string) ($item['platform'] ?? '');

            if ($url === '') {
                continue;
            }

            $links[] = [
                'platform' => $platform,
                'label' => trim((string) ($item['label'] ?? '')) ?: (self::SOCIAL_PLATFORMS[$platform] ?? $platform),
                'url' => $url,
            ];
        }

        return $links;
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
            'cart', 'sepet' => self::localePath($locale, '/sepet'),
            'tracking', 'siparis-takibi' => self::localePath($locale, '/siparis-takibi'),
            'categories', 'kategori' => self::localePath($locale, '/kategori'),
            'media', 'multimedia', 'multimedya' => self::localePath($locale, '/multimedya'),
            'contact', 'iletisim' => self::localePath($locale, '/iletisim'),
            'blog' => self::localePath($locale, '/blog'),
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

    /**
     * IBAN'ı okunur biçime getirir: boşlukları temizler, büyük harfe çevirir,
     * 4'erli gruplar hâlinde ayırır. "TR760004..." → "TR76 0004 0060 ...".
     */
    public static function formatIban(?string $iban): string
    {
        $clean = strtoupper(preg_replace('/\s+/', '', (string) $iban));

        return trim(chunk_split($clean, 4, ' '));
    }
}
