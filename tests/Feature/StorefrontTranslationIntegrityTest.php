<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Support\Arr;
use Tests\TestCase;

class StorefrontTranslationIntegrityTest extends TestCase
{
    public function test_all_storefront_dictionaries_have_identical_non_empty_keys_and_placeholders(): void
    {
        $source = $this->dictionary('tr');

        foreach (config('storefront.locales') as $locale) {
            $dictionary = $this->dictionary($locale);

            $this->assertSame(
                array_keys($source),
                array_keys($dictionary),
                $locale.' sözlük anahtarları Türkçe kaynakla eşleşmiyor.',
            );

            foreach ($source as $key => $sourceValue) {
                $this->assertNotSame('', trim((string) $dictionary[$key]), $locale.'.'.$key.' boş.');
                $this->assertSame(
                    $this->placeholders((string) $sourceValue),
                    $this->placeholders((string) $dictionary[$key]),
                    $locale.'.'.$key.' placeholder değerleri eşleşmiyor.',
                );
            }
        }
    }

    public function test_english_storefront_does_not_render_known_hardcoded_turkish_ui_copy(): void
    {
        $product = Product::query()->where('active', true)->firstOrFail();
        $paths = [
            '/en',
            '/en/sepet',
            '/en/multimedya',
            '/en/product/'.$product->slug,
        ];
        $forbidden = [
            'SİPARİŞ',
            'Paket fiyatı',
            'Paket içeriği',
            'ADET FİYATI',
            'ÜRÜN AÇIKLAMASI',
            'SİZİN İÇİN SEÇTİK',
            'Diğer kategorileri göster',
            'ürün detayını aç',
            '. görsel',
        ];

        foreach ($paths as $path) {
            $response = $this->get($path)->assertOk();

            foreach ($forbidden as $text) {
                $response->assertDontSee($text, false);
            }
        }
    }

    public function test_product_gallery_uses_localized_image_alt_text(): void
    {
        // Sıralama tie-breaker'sız bırakılırsa hangi ürünün/görselin geldiği
        // sqlite sürümüne göre değişiyor ve test yalnızca bazı ortamlarda
        // patlıyordu. Ürün id ile sabitlenir, alt metin de galeride hangi
        // görsel öne çıkarsa çıksın eşleşsin diye tüm görsellere yazılır.
        $product = Product::query()
            ->where('active', true)
            ->whereHas('images')
            ->orderBy('id')
            ->firstOrFail();

        $product->images()->get()->each(fn ($image) => $image->update([
            'alt' => [
                'tr' => 'Ürünün önden görünümü',
                'de' => 'Vorderansicht des Produkts',
            ],
        ]));

        $this->get('/de/product/'.$product->slug)
            ->assertOk()
            ->assertSee('alt="Vorderansicht des Produkts"', false)
            ->assertSee('data-thumb-alt="Vorderansicht des Produkts"', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function dictionary(string $locale): array
    {
        $values = Arr::dot(json_decode(
            (string) file_get_contents(lang_path('storefront/'.$locale.'.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        ));
        ksort($values);

        return $values;
    }

    /**
     * @return array<int, string>
     */
    private function placeholders(string $value): array
    {
        preg_match_all('/\{[a-zA-Z0-9_]+\}/', $value, $matches);
        $placeholders = array_values(array_unique($matches[0]));
        sort($placeholders);

        return $placeholders;
    }
}
