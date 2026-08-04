<?php

namespace Tests\Feature;

use App\Filament\Support\Multilingual;
use App\Support\Storefront;
use Tests\TestCase;

class RichTextDescriptionTest extends TestCase
{
    public function test_editor_html_is_kept_for_allowed_tags(): void
    {
        $html = Storefront::richText([
            'tr' => '<h2>Özellikler</h2><p>Pamuklu <strong>oversize</strong> <u>tişört</u></p>'
                .'<blockquote>Rahat kalıp</blockquote><ul><li>%100 pamuk</li><li><s>Eski</s> Yeni</li></ul><hr>',
        ], 'tr');

        $this->assertSame(
            '<h2>Özellikler</h2><p>Pamuklu <strong>oversize</strong> <u>tişört</u></p>'
                .'<blockquote>Rahat kalıp</blockquote><ul><li>%100 pamuk</li><li><s>Eski</s> Yeni</li></ul><hr>',
            $html
        );
    }

    public function test_safe_link_attributes_are_kept(): void
    {
        $html = Storefront::richText([
            'tr' => '<p><a href="https://example.com/urun?a=1&amp;b=2" target="_blank" class="x" onclick="x()">Ürün</a></p>',
        ], 'tr');

        $this->assertSame(
            '<p><a href="https://example.com/urun?a=1&amp;b=2" target="_blank" rel="noopener noreferrer">Ürün</a></p>',
            $html
        );
    }

    public function test_editor_exposes_advanced_content_and_html_tools(): void
    {
        $editor = Multilingual::richEditor('content', 'İçerik');
        $toolbarProperty = new \ReflectionProperty($editor, 'toolbarButtons');
        $toolbar = collect($toolbarProperty->getValue($editor))
            ->flatten()
            ->all();

        $this->assertContains('h4', $toolbar);
        $this->assertContains('table', $toolbar);
        $this->assertContains('alignJustify', $toolbar);
        $this->assertContains('clearFormatting', $toolbar);
        $this->assertContains('editHtml', $toolbar);
        $this->assertContains('fullscreen', $toolbar);
    }

    public function test_tables_and_safe_alignment_survive_html_sanitizing(): void
    {
        $html = Storefront::richText([
            'tr' => '<h4 style="text-align: center; color: red" onclick="x()">Ölçüler</h4>'
                .'<table class="x"><thead><tr><th colspan="2">Beden</th></tr></thead>'
                .'<tbody><tr><td rowspan="2">M</td><td>38</td></tr></tbody></table>',
        ], 'tr');

        $this->assertSame(
            '<h4 style="text-align: center">Ölçüler</h4>'
                .'<table><thead><tr><th colspan="2">Beden</th></tr></thead>'
                .'<tbody><tr><td rowspan="2">M</td><td>38</td></tr></tbody></table>',
            $html,
        );
    }

    public function test_unsafe_tags_and_attributes_are_stripped(): void
    {
        $html = Storefront::richText([
            'tr' => '<p style="font-size:80px" onclick="alert(1)">Metin</p>'
                .'<script>alert(1)</script>'
                .'<h1>Başlık</h1>'
                .'<a href="javascript:alert(1)" target="_blank">link</a>',
        ], 'tr');

        $this->assertSame('<p>Metin</p>Başlık<a>link</a>', $html);
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
