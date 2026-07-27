<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * lib/storefront/utils.ts + lib/currency.ts + lib/format-price.ts karşılığı.
 */
class Storefront
{
    private const EXTERNAL_URL_PATTERN = '#^(https?://|mailto:|tel:|\#)#i';

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
     * Supabase Storage public URL üretir. Zaten tam URL olan path'ler korunur.
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

        // S3 anahtarları yokken yüklenen dosyalar lokal yedek diskte durur.
        if (! UploadTarget::usesSupabase()) {
            $local = Storage::disk('local_supabase_stub');

            if ($local->exists($bucket.'/'.ltrim($path, '/'))) {
                return $local->url($bucket.'/'.ltrim($path, '/'));
            }
        }

        $base = rtrim((string) config('storefront.storage_url'), '/');

        return $base.'/'.$bucket.'/'.ltrim($path, '/');
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
