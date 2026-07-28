<?php

namespace Tests\Feature;

use App\Services\TranslateService;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Panelde artık manuel "Çevir" butonu yok; çeviri kayıt anında otomatik yapılıyor
 * (bkz. AutoTranslateOnSaveTest). Bu test Gemini bağlantısının gerçekten
 * çalıştığını doğrular.
 *
 * Gerçek Gemini çağrısı yapar; hiçbir kayıt oluşturulmaz/değiştirilmez.
 */
#[Group('external')]
class TranslateServiceTest extends TestCase
{
    public function test_it_translates_multiple_fields_in_a_single_request(): void
    {
        $result = app(TranslateService::class)->translateFields([
            'label' => 'Ürünler',
            'title' => 'Yeni Sezon',
        ]);

        foreach (['label', 'title'] as $field) {
            foreach (config('storefront.translation.languages') as $language) {
                $this->assertNotEmpty($result[$field][$language] ?? null, "{$field}.{$language} boş kaldı");
            }
        }
    }
}
