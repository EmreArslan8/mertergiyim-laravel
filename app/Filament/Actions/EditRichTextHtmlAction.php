<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\Width;

class EditRichTextHtmlAction
{
    public static function make(): Action
    {
        return Action::make('editRichTextHtml')
            ->label('HTML kaynağını düzenle')
            ->modalHeading('HTML kaynağını düzenle')
            ->modalDescription('Yalnızca güvenli içerik etiketleri vitrinde korunur; script ve tehlikeli özellikler otomatik temizlenir.')
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitActionLabel('HTML’yi uygula')
            ->fillForm(fn (array $arguments): array => [
                'html' => (string) ($arguments['html'] ?? ''),
            ])
            ->schema([
                Textarea::make('html')
                    ->hiddenLabel()
                    ->rows(24)
                    ->extraInputAttributes([
                        'class' => 'merter-html-source-input',
                        'spellcheck' => 'false',
                    ]),
            ])
            ->action(function (array $data, RichEditor $component): void {
                $component->runCommands([
                    EditorCommand::make('setContent', arguments: [(string) ($data['html'] ?? '')]),
                ]);
            });
    }
}
