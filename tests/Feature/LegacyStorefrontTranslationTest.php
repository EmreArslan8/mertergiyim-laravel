<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\TranslationStatus;
use Tests\TestCase;

class LegacyStorefrontTranslationTest extends TestCase
{
    public function test_first_five_products_have_translated_names_and_descriptions(): void
    {
        $products = Product::query()->whereIn('code', ['01', '02', '03', '04', '05'])->get();

        $this->assertCount(5, $products);

        foreach ($products as $product) {
            $this->assertSame([], TranslationStatus::missingLocales($product->name));
            $this->assertSame([], TranslationStatus::missingLocales($product->description));
        }
    }

    public function test_footer_information_heading_is_localized(): void
    {
        $value = SiteSetting::query()->whereKey('storefront')->firstOrFail()->value;

        $this->assertSame('Bilgilendirmeler', $value['tr']['footerInfoTitle']);
        $this->assertSame('Information', $value['en']['footerInfoTitle']);
        $this->assertSame('معلومات', $value['ar']['footerInfoTitle']);
        $this->assertSame('Informationen', $value['de']['footerInfoTitle']);
        $this->assertSame('Informazioni', $value['it']['footerInfoTitle']);
    }
}
