<?php

namespace Tests\Unit;

use App\Support\Storefront;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StorefrontTitleCaseTest extends TestCase
{
    #[DataProvider('names')]
    public function test_it_capitalizes_product_name_initials_without_changing_the_rest(
        string $name,
        string $locale,
        string $expected,
    ): void {
        $this->assertSame($expected, Storefront::titleCase($name, $locale));
    }

    public static function names(): array
    {
        return [
            'turkish dotted i' => ['ince askılı elbise', 'tr', 'İnce Askılı Elbise'],
            'turkish dotless i' => ['ışıl işlemeli tişört', 'tr', 'Işıl İşlemeli Tişört'],
            'hyphen and slash' => ['gömlek-etek / ikili takım', 'tr', 'Gömlek-Etek / İkili Takım'],
            'existing acronym' => ['oversize KOD model', 'tr', 'Oversize KOD Model'],
            'english' => ['linen summer dress', 'en', 'Linen Summer Dress'],
        ];
    }
}
