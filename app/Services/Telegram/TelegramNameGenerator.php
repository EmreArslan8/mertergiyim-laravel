<?php

namespace App\Services\Telegram;

use App\Models\TelegramChannelProduct;
use App\Services\TranslateService;
use App\Support\Storefront;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Adı olmayan ürünler için görselden ürün adı üretir.
 *
 * Kanalların bir kısmı (özellikle @naturallover) ürün adı paylaşmıyor: metinde
 * yalnızca kod, fiyat ve seri var. Ad bilgisi sadece fotoğrafın içinde; bu
 * yüzden ad, metinden değil görselden üretiliyor.
 *
 * Çeviri servisiyle aynı Gemini kurulumunu kullanır (GEMINI_API_KEY, aynı
 * model); ayrı bir sağlayıcı eklenmiyor.
 */
class TelegramNameGenerator
{
    /** Gemini'ye gönderilecek en büyük görsel; büyüğü boşuna token yakar. */
    private const MAX_IMAGE_BYTES = 4 * 1024 * 1024;

    public function __construct(private readonly TranslateService $translator) {}

    public function configured(): bool
    {
        return $this->translator->configured();
    }

    /**
     * @return string Türkçe ürün adı
     */
    public function generate(TelegramChannelProduct $product): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('GEMINI_API_KEY tanımlı değil.');
        }

        $image = $this->firstPhoto($product);

        if ($image === null) {
            throw new RuntimeException('Üründe ad üretilebilecek fotoğraf yok.');
        }

        $response = Http::connectTimeout(4)
            ->timeout(12)
            ->withHeaders(['x-goog-api-key' => (string) config('storefront.translation.api_key')])
            ->post($this->endpoint(), [
                'contents' => [[
                    'parts' => [
                        ['text' => $this->prompt($product)],
                        ['inlineData' => ['mimeType' => $image['mime'], 'data' => base64_encode($image['bytes'])]],
                    ],
                ]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    // Ad kısa; uzun cevap üretmesine gerek yok.
                    'maxOutputTokens' => 120,
                    'temperature' => 0.2,
                ],
            ]);

        $payload = $response->json() ?? [];

        if (! $response->successful()) {
            throw new RuntimeException($payload['error']['message'] ?? 'Gemini isteği başarısız.');
        }

        $parsed = json_decode($payload['candidates'][0]['content']['parts'][0]['text'] ?? '{}', true);
        $name = trim((string) ($parsed['name'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Gemini ad üretemedi.');
        }

        return $name;
    }

    private function endpoint(): string
    {
        $base = trim((string) config('storefront.translation.base_url', ''));
        $prefix = $base !== ''
            ? $base.'/v1beta/models/'
            : 'https://generativelanguage.googleapis.com/v1beta/models/';

        return $prefix.$this->translator->model().':generateContent';
    }

    private function prompt(TelegramChannelProduct $product): string
    {
        // Ham metinde ad yok ama kumaş/model ipucu olabiliyor ("Keten Kumas");
        // görselle birlikte verilince ad isabetli çıkıyor.
        $context = trim((string) $product->raw_text);

        return 'Fotoğraftaki toptan kadın giyim ürünü için Türkçe bir ürün adı üret.'
            .' Kurallar: 2-5 kelime, yalnızca ürün türü ve belirgin özellikleri'
            .' (kumaş, desen, kesim, yaka gibi); marka adı uydurma;'
            .' fiyat, beden, renk sayısı, kod yazma; büyük harfle bağırma;'
            .' emoji ve noktalama ekleme.'
            .' Örnek biçim: "Keten Kruvaze Elbise", "Saten Gömlek".'
            .' SADECE şu JSON: {"name":"..."}'
            .($context !== '' ? ' Kanalın paylaştığı metin (ad içermiyor olabilir): '.mb_substr($context, 0, 400) : '');
    }

    /**
     * @return array{bytes: string, mime: string}|null
     */
    private function firstPhoto(TelegramChannelProduct $product): ?array
    {
        $image = $product->images
            ->filter(fn ($item): bool => $item->type === 'photo')
            ->first();

        if ($image === null) {
            return null;
        }

        $url = $image->url();

        // İndirilmiş görsel yerel diskte durur; url() bunun için göreli bir
        // /storage yolu döndürür ve HTTP ile çekilemez. Byte'ları doğrudan
        // diskten oku. Yerelde yoksa (Supabase kurulumu ya da henüz indirilmemiş
        // CDN görseli) aşağıda uzak adresten indirilir.
        if (blank($url)) {
            return null;
        }

        if (! str_starts_with($url, 'http')) {
            $local = Storefront::localStorageContents($url);

            return $local !== null && strlen($local) <= self::MAX_IMAGE_BYTES
                ? ['bytes' => $local, 'mime' => $this->mimeFromPath($url)]
                : null;
        }

        try {
            $response = Http::connectTimeout(4)->timeout(8)->get($url);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful() || strlen($response->body()) > self::MAX_IMAGE_BYTES) {
            return null;
        }

        return [
            'bytes' => $response->body(),
            'mime' => $response->header('Content-Type') ?: 'image/jpeg',
        ];
    }

    /** Dosya uzantısından Gemini'ye verilecek MIME türü. */
    private function mimeFromPath(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }
}
