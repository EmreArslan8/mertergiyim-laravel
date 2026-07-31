<?php

namespace Tests\Feature;

use App\Support\Storefront;
use Tests\TestCase;

class RichTextDescriptionTest extends TestCase
{
    public function test_editor_html_is_kept_for_allowed_tags(): void
    {
        $html = Storefront::richText([
            'tr' => '<p>Pamuklu <strong>oversize</strong> tişört</p><ul><li>%100 pamuk</li><li>Unisex</li></ul>',
        ], 'tr');

        $this->assertSame(
            '<p>Pamuklu <strong>oversize</strong> tişört</p><ul><li>%100 pamuk</li><li>Unisex</li></ul>',
            $html
        );
    }

    public function test_unsafe_tags_and_attributes_are_stripped(): void
    {
        $html = Storefront::richText([
            'tr' => '<p style="font-size:80px" onclick="alert(1)">Metin</p>'
                .'<script>alert(1)</script>'
                .'<h1>Başlık</h1>'
                .'<a href="https://spam.example">link</a>',
        ], 'tr');

        $this->assertSame('<p>Metin</p>Başlıklink', $html);
    }

    public function test_legacy_plain_text_keeps_line_breaks(): void
    {
        $html = Storefront::richText(['tr' => "Birinci satır\nİkinci satır"], 'tr');

        $this->assertSame("Birinci satır<br />\nİkinci satır", $html);
    }

    public function test_plain_text_strips_markup_for_meta_description(): void
    {
        $text = Storefront::plainText([
            'tr' => '<p>Pamuklu <strong>tişört</strong></p><ul><li>%100 pamuk</li><li>Unisex</li></ul>',
        ], 'tr');

        $this->assertSame('Pamuklu tişört %100 pamuk Unisex', $text);
    }

    public function test_locale_falls_back_to_turkish(): void
    {
        $html = Storefront::richText(['tr' => '<p>Türkçe</p>'], 'de');

        $this->assertSame('<p>Türkçe</p>', $html);
    }
}
