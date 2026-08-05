<?php

namespace App\Livewire;

use App\Filament\Pages\TelegramScanDetail;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;

/**
 * Hızlı Ekle açıklaması: panelin geri kalanıyla aynı Filament zengin
 * editörü (TipTap). Ana sayfa çıplak Blade modalı olduğu için editör ayrı
 * bileşende yaşar; her değişiklikte değer olayla ana panele iletilir.
 *
 * Bileşen yalnızca modal açıkken DOM'da olduğundan (blade'de @if ile
 * kapatılıyor) her açılışta temiz başlar; reset olayı gerekmez.
 */
class QuickAddDescriptionEditor extends Component implements HasForms
{
    use InteractsWithForms;

    public string $description = '';

    public function mount(string $description = ''): void
    {
        $this->description = $description;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                RichEditor::make('description')
                    ->label('')
                    // Storefront'un izinli etiket listesiyle uyumlu araçlar.
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike'],
                        ['h2', 'h3'],
                        ['bulletList', 'orderedList', 'blockquote', 'link'],
                        ['undo', 'redo', 'clearFormatting'],
                    ])
                    ->fileAttachments(false),
            ])
            ->statePath('description');
    }

    public function updatedDescription(): void
    {
        // Sebebi ana panele ilet: kaydetme form.description'ı okuyor.
        $this->dispatch('quick-add-description-updated', description: $this->description)
            ->to(TelegramScanDetail::class);
    }

    public function render()
    {
        return view('livewire.quick-add-description-editor');
    }
}
