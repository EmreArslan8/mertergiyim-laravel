<?php

namespace Tests\Unit;

use App\Services\Telegram\TelegramProductParser;
use PHPUnit\Framework\TestCase;

/**
 * Parser, üç kanaldan alınmış gerçek mesaj metinlerine karşı doğrulanır.
 * Buradaki metinler t.me önizlemesinden birebir kopyalandı.
 */
class TelegramProductParserTest extends TestCase
{
    private TelegramProductParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new TelegramProductParser;
    }

    public function test_asprinntrendy_seri_bicimi(): void
    {
        $result = $this->parser->parse(
            "Krınkıl Keten Kumas İkili Takım\nSeri 5 li 2s 2m 1l MODEL 7963\nFiyat 22$\n\nNatur Haki Beyaz Renkleriyle✔️✔️✔️"
        );

        $this->assertSame('Krınkıl Keten Kumas İkili Takım', $result['name']);
        $this->assertSame('7963', $result['product_code']);
        $this->assertSame(22.0, $result['price']);
        $this->assertSame('USD', $result['currency']);
        $this->assertSame(5, $result['pack_size']);
        $this->assertSame(['S' => 2, 'M' => 2, 'L' => 1], $result['sizes']);
        $this->assertSame(['Natur', 'Haki', 'Beyaz'], $result['colors']);
    }

    public function test_asprinntrendy_beden_etiketli_bicim(): void
    {
        // "SS MM L" tekrar eden harflerle adet belirtiyor: 2 S, 2 M, 1 L.
        $result = $this->parser->parse(
            "🏷 Envalop Yaka Pullu Elbise\n👗 Beden: SS MM L\n🏷 Kod: 7596\nFiyat 22$"
        );

        $this->assertSame('Envalop Yaka Pullu Elbise', $result['name']);
        $this->assertSame('7596', $result['product_code']);
        $this->assertSame(22.0, $result['price']);
        $this->assertSame(['S' => 2, 'M' => 2, 'L' => 1], $result['sizes']);
    }

    public function test_asprinntrendy_nokta_ile_yazilmis_fiyat(): void
    {
        $result = $this->parser->parse(
            "🏷 Deniz Kabuklu Bağlamalı Crop Bluz Etek Krinkıl Takım \n👗 Beden: 2s 1m 1l 1xl\n🏷 Kod: 7116 \nFiyat. 23$\n✓✓✓✓✓"
        );

        $this->assertSame('Deniz Kabuklu Bağlamalı Crop Bluz Etek Krinkıl Takım', $result['name']);
        $this->assertSame('7116', $result['product_code']);
        $this->assertSame(23.0, $result['price']);
        $this->assertSame(['S' => 2, 'M' => 1, 'L' => 1, 'XL' => 1], $result['sizes']);
    }

    public function test_naturallover_isimsiz_bicim(): void
    {
        $result = $this->parser->parse("Code:5836\nStandart \nPack:5\nPrice:5$");

        // Bu kanal ürün adı paylaşmıyor; uydurulmamalı.
        $this->assertNull($result['name']);
        $this->assertSame('5836', $result['product_code']);
        $this->assertSame(5.0, $result['price']);
        $this->assertSame('USD', $result['currency']);
        $this->assertSame(5, $result['pack_size']);
        $this->assertNull($result['sizes']);
    }

    public function test_naturallover_ondalikli_fiyat_ve_beden_listesi(): void
    {
        $result = $this->parser->parse("Code:1112\nS/M/L/XL\nPack:4\nPrice:7,5$");

        $this->assertSame('1112', $result['product_code']);
        $this->assertSame(7.5, $result['price']);
        $this->assertSame(4, $result['pack_size']);
        $this->assertSame(['S' => 1, 'M' => 1, 'L' => 1, 'XL' => 1], $result['sizes']);
    }

    public function test_naturallover_tek_satirda_toplanmis_kayit(): void
    {
        $result = $this->parser->parse('Saten Gömlek Size:S-M-L-XL Pack:4 Price:7,5$');

        $this->assertSame('Saten Gömlek', $result['name']);
        $this->assertSame(7.5, $result['price']);
        $this->assertSame(4, $result['pack_size']);
        $this->assertSame(['S' => 1, 'M' => 1, 'L' => 1, 'XL' => 1], $result['sizes']);
    }

    public function test_rosearyaa_fiyatsiz_bicim(): void
    {
        $result = $this->parser->parse("Keten (Linen) maxi Elbise\n\nS2m2L1");

        $this->assertSame('Keten (Linen) maxi Elbise', $result['name']);
        // Bu kanal fiyat paylaşmıyor.
        $this->assertNull($result['price']);
        $this->assertNull($result['product_code']);
        $this->assertSame(['S' => 2, 'M' => 2, 'L' => 1], $result['sizes']);
    }

    public function test_rosearyaa_kumas_satiri_urun_adi_sayilmaz(): void
    {
        $result = $this->parser->parse("İnterlok kumaş kol dantel detay Tshirt\n\n%100 cotton \n\nS2m2L2");

        $this->assertSame('İnterlok kumaş kol dantel detay Tshirt', $result['name']);
        $this->assertSame(['S' => 2, 'M' => 2, 'L' => 2], $result['sizes']);
    }

    public function test_sadece_beden_iceren_mesajda_ad_uretilmez(): void
    {
        $result = $this->parser->parse('S2m2L1XL1');

        $this->assertNull($result['name']);
        $this->assertSame(['S' => 2, 'M' => 2, 'L' => 1, 'XL' => 1], $result['sizes']);
    }

    public function test_bos_mesaj_butun_alanlari_null_dondurur(): void
    {
        $result = $this->parser->parse('');

        foreach ($result as $field => $value) {
            $this->assertNull($value, "[$field] boş mesajda null olmalı");
        }
    }

    public function test_renk_anahtar_kelime_olmadan_sozlukten_cikar(): void
    {
        // "Renkler" gibi bir anahtar yok; renkler sözlükten yakalanmalı.
        $result = $this->parser->parse("Keten Takım\nAntrasit Vizon Bej\nFiyat 20$");

        $this->assertSame(['Antrasit', 'Vizon', 'Bej'], $result['colors']);
    }

    public function test_urun_adindaki_renk_disi_kelimeler_renk_sayilmaz(): void
    {
        // "keten", "gömlek" renk değil; yalnızca "beyaz" çıkmalı.
        $result = $this->parser->parse("Beyaz Keten Gömlek\nSize:S-M-L\nPrice:8$");

        $this->assertSame(['Beyaz'], $result['colors']);
    }

    public function test_cok_kelimeli_ton_tek_renk_olarak_alinir(): void
    {
        // "Kırık Beyaz" ayrı bir renktir; "Beyaz"a indirgenmemeli.
        $result = $this->parser->parse("Poplin Gömlek\nKırık Beyaz Haki\nFiyat 12$");

        $this->assertSame(['Kırık Beyaz', 'Haki'], $result['colors']);
    }

    public function test_modifier_ile_ton_birlesir(): void
    {
        $result = $this->parser->parse("Örme Kazak\nAçık Mavi Koyu Yeşil\nFiyat 15$");

        $this->assertSame(['Açık Mavi', 'Koyu Yeşil'], $result['colors']);
    }

    public function test_renk_yoksa_null_doner(): void
    {
        $result = $this->parser->parse("Keten Elbise\nS2m2L1");

        $this->assertNull($result['colors']);
    }

    public function test_tekrar_eden_renk_bir_kez_yazilir(): void
    {
        $result = $this->parser->parse("Beyaz Gömlek\nBeyaz Haki Renkleriyle");

        $this->assertSame(['Beyaz', 'Haki'], $result['colors']);
    }
}
