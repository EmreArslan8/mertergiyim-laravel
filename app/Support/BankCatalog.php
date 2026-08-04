<?php

namespace App\Support;

final class BankCatalog
{
    /**
     * Statik logo dosyaları public/images/banks/<slug>.(svg|png|webp|jpg) yoluna
     * konur; dosya varsa otomatik üretilen SVG yerine o kullanılır.
     *
     * @var array<string, array{label: string, mark: string, color: string, slug: string}>
     */
    private const BANKS = [
        'Akbank' => ['label' => 'Akbank', 'mark' => 'AK', 'color' => '#E30613', 'slug' => 'akbank'],
        'Albaraka Türk' => ['label' => 'Albaraka Türk', 'mark' => 'AT', 'color' => '#009A44', 'slug' => 'albaraka-turk'],
        'Anadolubank' => ['label' => 'Anadolubank', 'mark' => 'AB', 'color' => '#00529B', 'slug' => 'anadolubank'],
        'Burgan Bank' => ['label' => 'Burgan Bank', 'mark' => 'BB', 'color' => '#6B2C91', 'slug' => 'burgan-bank'],
        'DenizBank' => ['label' => 'DenizBank', 'mark' => 'DB', 'color' => '#005BAA', 'slug' => 'denizbank'],
        'Fibabanka' => ['label' => 'Fibabanka', 'mark' => 'FB', 'color' => '#F58220', 'slug' => 'fibabanka'],
        'Garanti BBVA' => ['label' => 'Garanti BBVA', 'mark' => 'G', 'color' => '#008A45', 'slug' => 'garanti-bbva'],
        'Halkbank' => ['label' => 'Halkbank', 'mark' => 'HB', 'color' => '#0072CE', 'slug' => 'halkbank'],
        'HSBC Türkiye' => ['label' => 'HSBC Türkiye', 'mark' => 'HSBC', 'color' => '#DB0011', 'slug' => 'hsbc-turkiye'],
        'ING Türkiye' => ['label' => 'ING Türkiye', 'mark' => 'ING', 'color' => '#FF6200', 'slug' => 'ing-turkiye'],
        'İş Bankası' => ['label' => 'Türkiye İş Bankası', 'mark' => 'İŞ', 'color' => '#005EB8', 'slug' => 'is-bankasi'],
        'Kuveyt Türk' => ['label' => 'Kuveyt Türk', 'mark' => 'KT', 'color' => '#007A53', 'slug' => 'kuveyt-turk'],
        'Odea Bank' => ['label' => 'Odea Bank', 'mark' => 'OB', 'color' => '#7A2582', 'slug' => 'odea-bank'],
        'QNB Türkiye' => ['label' => 'QNB Türkiye', 'mark' => 'QNB', 'color' => '#6D2077', 'slug' => 'qnb-turkiye'],
        'Şekerbank' => ['label' => 'Şekerbank', 'mark' => 'ŞB', 'color' => '#00843D', 'slug' => 'sekerbank'],
        'TEB' => ['label' => 'TEB', 'mark' => 'TEB', 'color' => '#00A651', 'slug' => 'teb'],
        'Türkiye Finans' => ['label' => 'Türkiye Finans', 'mark' => 'TF', 'color' => '#7B1FA2', 'slug' => 'turkiye-finans'],
        'VakıfBank' => ['label' => 'VakıfBank', 'mark' => 'VB', 'color' => '#F5A800', 'slug' => 'vakifbank'],
        'Yapı Kredi' => ['label' => 'Yapı Kredi', 'mark' => 'YK', 'color' => '#004B93', 'slug' => 'yapi-kredi'],
        'Ziraat Bankası' => ['label' => 'Ziraat Bankası', 'mark' => 'ZB', 'color' => '#E0001B', 'slug' => 'ziraat-bankasi'],
    ];

    /** Statik logo dosyası aranan uzantılar (öncelik sırasıyla). */
    private const LOGO_EXTENSIONS = ['svg', 'png', 'webp', 'jpg'];

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_map(static fn (array $bank): string => $bank['label'], self::BANKS);
    }

    public static function logoDataUri(?string $bankName): ?string
    {
        if (! $bankName || ! isset(self::BANKS[$bankName])) {
            return null;
        }

        $bank = self::BANKS[$bankName];

        // Statik logo dosyası varsa (public/images/banks/<slug>.<ext>) onu kullan;
        // yoksa aşağıdaki otomatik SVG'ye düş.
        foreach (self::LOGO_EXTENSIONS as $ext) {
            $relative = 'images/banks/'.$bank['slug'].'.'.$ext;
            if (is_file(public_path($relative))) {
                return asset($relative);
            }
        }

        $mark = htmlspecialchars($bank['mark'], ENT_QUOTES | ENT_XML1, 'UTF-8');
        $label = htmlspecialchars($bank['label'], ENT_QUOTES | ENT_XML1, 'UTF-8');
        $markSize = strlen($bank['mark']) > 3 ? 19 : 24;

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="240" height="72" viewBox="0 0 240 72">
          <rect width="240" height="72" rx="14" fill="#ffffff"/>
          <rect x="1" y="1" width="238" height="70" rx="13" fill="none" stroke="#e5e7eb"/>
          <rect x="10" y="10" width="52" height="52" rx="12" fill="{$bank['color']}"/>
          <text x="36" y="44" fill="#ffffff" font-family="Arial, sans-serif" font-size="{$markSize}" font-weight="700" text-anchor="middle">{$mark}</text>
          <text x="76" y="42" fill="#1f2937" font-family="Arial, sans-serif" font-size="17" font-weight="700">{$label}</text>
        </svg>
        SVG;

        return 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg);
    }
}
