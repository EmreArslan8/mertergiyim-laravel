<?php

namespace App\Filament\Support;

use App\Filament\Actions\EditRichTextHtmlAction;
use App\Support\Storefront;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

/**
 * jsonb çok dilli alanlar (products.name, hero_slides.title ...) için panel
 * yardımcıları.
 *
 * Panelde yalnızca Türkçe girilir; kalan 9 dil kayıt anında otomatik çevrilir
 * (App\Filament\Concerns\TranslatesJsonFields).
 */
class Multilingual
{
    /**
     * @return array<int, string>
     */
    public static function locales(): array
    {
        return config('storefront.locales');
    }

    public static function localeLabel(string $locale): string
    {
        return [
            'tr' => 'Türkçe',
            'en' => 'İngilizce',
            'ar' => 'Arapça',
            'ru' => 'Rusça',
            'fa' => 'Farsça',
            'uk' => 'Ukraynaca',
            'fr' => 'Fransızca',
            'de' => 'Almanca',
            'es' => 'İspanyolca',
            'it' => 'İtalyanca',
        ][$locale] ?? strtoupper($locale);
    }

    /**
     * Panelde tek Türkçe alan.
     */
    public static function turkish(
        string $field,
        string $label,
        bool $long = false,
        bool $required = true,
        ?string $legacyFallback = null,
        bool $rich = false,
        bool $richInline = false,
    ): TextInput|Textarea|RichEditor {
        // Satır-içi editör de zengin bir editördür; sadece araç seti sadedir.
        $isRich = $rich || $richInline;

        $input = self::input($field.'.tr', $label, $long, $rich, $richInline)
            ->required($required)
            ->columnSpanFull();

        // Not: afterStateHydrated tek closure tutar; zengin editörün eski düz metin
        // dönüşümü de burada yapılır ki iki kanca birbirini ezmesin.
        if ($legacyFallback || $isRich) {
            $input->afterStateHydrated(function ($component, $state, $record) use ($legacyFallback, $isRich): void {
                if ($legacyFallback && blank($state) && filled($record?->{$legacyFallback})) {
                    $state = $record->{$legacyFallback};
                }

                $component->state($isRich ? self::toEditorHtml($state) : $state);
            });
        }

        return $input;
    }

    private static function input(string $name, string $label, bool $long, bool $rich = false, bool $richInline = false): TextInput|Textarea|RichEditor
    {
        if ($rich || $richInline) {
            return self::richEditor($name, $label, inline: $richInline);
        }

        return $long
            ? Textarea::make($name)->label($label)->rows(5)
            : TextInput::make($name)->label($label);
    }

    /**
     * Panel kullanıcısı için güvenli ve kapsamlı içerik editörü. Sayfanın tek
     * H1 başlığı şablondan geldiği için içerikte H2-H4 sunulur.
     */
    public static function richEditor(string $name, string $label, bool $inline = false): RichEditor
    {
        $editor = RichEditor::make($name)
            ->label($label)
            // Editör öncesi kaydedilmiş düz metinler paragraf olarak açılsın.
            ->afterStateHydrated(fn ($component, $state) => $component->state(self::toEditorHtml($state)))
            // Boş editör "<p></p>" üretir; bu dolu metin sayılıp gereksiz çeviri
            // tetiklemesin diye boş kaydedilir.
            ->dehydrateStateUsing(fn ($state) => Storefront::plainText($state, 'tr') === '' ? null : $state)
            ->fileAttachments(false);

        // Hero başlığı gibi tek satırlık alanlar: yalnızca satır-içi biçimlendirme.
        // Başlık/liste/tablo/hizalama gibi blok araçlar kapalı; içerik <h1> içine
        // basıldığı için blok öğeler semantik olarak yanlış olurdu.
        if ($inline) {
            return $editor
                ->toolbarButtons([
                    ['bold', 'italic', 'underline', 'strike', 'link'],
                    ['undo', 'redo', 'clearFormatting'],
                ])
                ->helperText('Başlık, sayfanın ana başlığıdır (H1); bu yüzden yalnızca satır-içi biçimlendirme sunulur. Kalın, italik, altı çizili, üstü çizili ve bağlantı desteklenir.');
        }

        return $editor
            ->tools([
                RichEditorTool::make('editHtml')
                    ->label('HTML kaynağı')
                    ->action(
                        action: 'editRichTextHtml',
                        arguments: '{ html: $getEditor()?.getHTML() ?? \'\' }',
                    )
                    ->icon(Heroicon::CodeBracket),
                RichEditorTool::make('fullscreen')
                    ->label('Tam ekran')
                    ->jsHandler(<<<'JS'
                        (() => {
                            const editor = $el.closest('.fi-fo-rich-editor')
                            if (! editor) return
                            if (document.fullscreenElement) document.exitFullscreen()
                            else editor.requestFullscreen()
                        })()
                        JS)
                    ->icon(Heroicon::ArrowsPointingOut),
            ])
            ->registerActions([
                EditRichTextHtmlAction::make(),
            ])
            ->toolbarButtons([
                ['paragraph', 'h2', 'h3', 'h4'],
                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                ['blockquote', 'bulletList', 'orderedList', 'table', 'horizontalRule'],
                ['undo', 'redo', 'clearFormatting'],
                ['editHtml', 'fullscreen'],
            ])
            ->helperText('Başlık, hizalama, tablo, bağlantı, liste ve HTML kaynağı desteklenir. Güvenli olmayan HTML kayıtta temizlenir.');
    }

    /**
     * Editör öncesi kaydedilmiş düz metinleri paragraflara çevirir; zaten HTML
     * olan içeriğe dokunmaz.
     */
    private static function toEditorHtml(mixed $state): string
    {
        $value = trim((string) $state);

        if ($value === '' || preg_match('/<[a-z][a-z0-9]*\b[^>]*>/i', $value)) {
            return $value;
        }

        $paragraphs = preg_split('/\R{2,}/u', $value) ?: [$value];

        return collect($paragraphs)
            ->map(fn (string $paragraph) => trim($paragraph))
            ->filter()
            ->map(fn (string $paragraph) => '<p>'.nl2br(e($paragraph), false).'</p>')
            ->implode('');
    }

    /**
     * Tablo sütunlarında kullanılacak Türkçe değer.
     */
    public static function tr(mixed $value): string
    {
        return is_array($value) ? (string) ($value['tr'] ?? '') : (string) $value;
    }
}
