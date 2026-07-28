<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\TranslateService;
use Tests\TestCase;

class TranslationRepairCommandTest extends TestCase
{
    public function test_fix_retries_when_gemini_omits_a_locale(): void
    {
        Product::query()->each(fn (Product $product) => $product->delete());

        $languages = config('storefront.translation.languages');
        $completeDescription = ['tr' => 'Komut açıklaması'];

        foreach ($languages as $locale) {
            $completeDescription[$locale] = 'description-'.$locale;
        }

        $product = Product::query()->create([
            'code' => 'TRANSLATION-RETRY',
            'slug' => 'translation-retry',
            'name' => ['tr' => 'Komut Ürünü'],
            'description' => $completeDescription,
            'price' => 1,
            'currency' => 'TRY',
            'price_try' => 1,
            'price_usd' => 1,
            'price_eur' => 1,
            'stock_status' => 'in_stock',
            'active' => false,
        ]);

        $firstAttempt = [];

        foreach ($languages as $locale) {
            if ($locale !== 'fa') {
                $firstAttempt[$locale] = 'name-'.$locale;
            }
        }

        $this->mock(TranslateService::class, function ($mock) use ($firstAttempt): void {
            $mock->shouldReceive('configured')->once()->andReturnTrue();
            $mock->shouldReceive('translateFields')
                ->twice()
                ->with(['name' => 'Komut Ürünü'])
                ->andReturn(
                    ['name' => $firstAttempt],
                    ['name' => ['fa' => 'name-fa']],
                );
        });

        $this->artisan('translations:check', [
            '--fix' => true,
            '--model' => 'Product',
        ])->assertExitCode(0);

        $fresh = $product->fresh();

        $this->assertCount(10, $fresh->name);
        $this->assertSame('Komut Ürünü', $fresh->name['tr']);

        foreach ($languages as $locale) {
            $this->assertSame('name-'.$locale, $fresh->name[$locale]);
        }
    }
}
