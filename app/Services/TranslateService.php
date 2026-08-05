<?php

namespace App\Services;

use App\Models\TranslationUsage;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Kaynak projedeki app/api/translate/route.ts karşılığı.
 *
 * Prompt metni, dil listesi ve JSON çıktı biçimi kaynakla birebir aynıdır ki
 * iki panel de aynı çeviri kalitesini üretsin.
 */
class TranslateService
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * Gemini bazı barındırma IP'lerini "desteklenmeyen bölge" sayıp isteği
     * reddediyor (User location is not supported). Sunucu böyle bir ağdaysa
     * GEMINI_BASE_URL ile araya kendi vekil adresin konabilir; boşken doğrudan
     * Google'a gidilir.
     */
    private function endpoint(): string
    {
        $base = rtrim((string) config('storefront.translation.base_url', ''), '/');

        return $base !== '' ? $base.'/v1beta/models/' : self::ENDPOINT;
    }

    /**
     * @return array<int, string>
     */
    public function languages(): array
    {
        return config('storefront.translation.languages');
    }

    public function model(): string
    {
        return (string) config('storefront.translation.model', 'gemini-3.5-flash-lite');
    }

    public function configured(): bool
    {
        return (string) config('storefront.translation.api_key') !== '';
    }

    /**
     * Birden çok Türkçe alanı tek istekte 9 dile çevirir.
     *
     * @param  array<string, string>  $texts  ['name' => 'Oversize Tişört', ...]
     * @return array<string, array<string, string>> ['name' => ['en' => '...', ...]]
     */
    public function translateFields(array $texts): array
    {
        // Uzun içerikler (blog yazısı, ürün açıklaması) 9 dile çevrilirken tek
        // istek 20 sn'yi aşıp cURL 28 veriyordu; süre önce 45 sn'ye, sonra
        // 120 sn'ye çıkarıldı (ölçüm: ~336 token/sn → 1000 kelimelik yazı
        // ≈70 sn). Retry kasıtlı yok: 120 sn'lik üretim zaman aşımından sonra
        // yeniden deneme aynı uzun üretimi sıfırdan başlatır. Zaman aşımı bu
        // çağrıda Throwable olarak yönetimli yakalanır; kayıt engellenir,
        // sistem çökmez. Aşılamayacak bütçeyi TranslatesJsonFields belirler.
        set_time_limit(150);

        $source = array_filter(array_map(fn ($value) => trim((string) $value), $texts), fn ($value) => $value !== '');

        if ($source === []) {
            throw new RuntimeException('Çevrilecek metin boş.');
        }

        if (! $this->configured()) {
            throw new RuntimeException('GEMINI_API_KEY eksik.');
        }

        $prompt = 'Translate every Turkish ecommerce text value below into these language codes: '
            .implode(', ', $this->languages())
            .'. Return ONLY valid JSON with this shape: {"field": {"en":"...", "ar":"..."}}.'
            .' Keep the same field keys and do not add markdown.'
            .' Write professional, native-sounding fashion ecommerce copy while preserving the exact meaning.'
            .' Translate every descriptive term fully; do not leave Turkish or English fragments in another language.'
            .' Keep only the actual brand name and product codes unchanged; translate every surrounding product descriptor.'
            .' Never copy an English descriptor into Russian, Ukrainian, German, Arabic, Persian, French, Spanish, or Italian.'
            .' If a possible brand and generic words are adjacent, preserve only the brand token and translate the generic words.'
            .' Clothing terms must be natural and precise: "kısa kollu" means short-sleeved,'
            .' "hafif doku/kumaş" means lightweight texture/fabric, never a food-related adjective,'
            .' and the Turkish geographic adjective "Ege" must be translated as Aegean'
            .' (or the natural equivalent in the target language), not kept as an untranslated brand term.'
            .' A value may contain simple HTML markup (p, br, strong, b, em, i, u, ul, ol, li).'
            .' In that case translate only the visible text and keep the tag structure byte-identical:'
            .' same tags, same order, same nesting, no extra or missing tags, no added attributes.'
            .' Turkish texts: '.json_encode($source, JSON_UNESCAPED_UNICODE);

        $response = Http::connectTimeout(5)
            ->timeout(120)
            ->withHeaders(['x-goog-api-key' => (string) config('storefront.translation.api_key')])
            ->post($this->endpoint().$this->model().':generateContent', [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['responseMimeType' => 'application/json'],
            ]);

        $payload = $response->json() ?? [];
        $usage = $payload['usageMetadata'] ?? [];

        if (! $response->successful()) {
            $message = $payload['error']['message'] ?? 'Gemini çeviri hatası.';
            $this->recordUsage(0, 0, 0, false, $message);

            throw new RuntimeException($message);
        }

        $raw = $payload['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        $parsed = json_decode($raw, true);

        if (! is_array($parsed)) {
            $this->recordUsage(
                $usage['promptTokenCount'] ?? 0,
                $usage['candidatesTokenCount'] ?? 0,
                $usage['totalTokenCount'] ?? 0,
                false,
                'Gemini geçerli JSON döndürmedi.'
            );

            throw new RuntimeException('Gemini geçerli JSON döndürmedi.');
        }

        $this->recordUsage(
            $usage['promptTokenCount'] ?? 0,
            $usage['candidatesTokenCount'] ?? 0,
            $usage['totalTokenCount'] ?? 0,
            true
        );

        return $this->normalize($parsed, array_keys($source));
    }

    /**
     * Tek metni 9 dile çevirir.
     *
     * @return array<string, string>
     */
    public function translateText(string $text): array
    {
        return $this->translateFields(['text' => $text])['text'] ?? [];
    }

    /**
     * Bağlantı testi (kaynak route'un GET karşılığı).
     *
     * @return array{connected: bool, model: string, error?: string}
     */
    public function ping(): array
    {
        if (! $this->configured()) {
            return ['connected' => false, 'model' => $this->model(), 'error' => 'GEMINI_API_KEY eksik.'];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['x-goog-api-key' => (string) config('storefront.translation.api_key')])
                ->post($this->endpoint().$this->model().':generateContent', [
                    'contents' => [['parts' => [['text' => 'ping']]]],
                    'generationConfig' => ['maxOutputTokens' => 1],
                ]);

            return $response->successful()
                ? ['connected' => true, 'model' => $this->model()]
                : ['connected' => false, 'model' => $this->model(), 'error' => $response->json('error.message') ?? 'Bağlantı hatası.'];
        } catch (\Throwable $exception) {
            return ['connected' => false, 'model' => $this->model(), 'error' => 'Gemini API\'ye ulaşılamadı.'];
        }
    }

    /**
     * Model yalnızca istenen dilleri, string değerlerle döndürsün.
     *
     * @param  array<string, mixed>  $parsed
     * @param  array<int, string>  $fields
     * @return array<string, array<string, string>>
     */
    private function normalize(array $parsed, array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            $values = $parsed[$field] ?? [];

            foreach ($this->languages() as $language) {
                if (isset($values[$language]) && is_string($values[$language])) {
                    $result[$field][$language] = $values[$language];
                }
            }
        }

        return $result;
    }

    /**
     * Kullanım kaydı başarısız olsa bile çeviri akışını bozma.
     */
    private function recordUsage(int $prompt, int $output, int $total, bool $ok, ?string $error = null): void
    {
        rescue(fn () => TranslationUsage::create([
            'model' => $this->model(),
            'prompt_tokens' => $prompt,
            'output_tokens' => $output,
            'total_tokens' => $total,
            'ok' => $ok,
            'error' => $error,
        ]), report: false);
    }
}
