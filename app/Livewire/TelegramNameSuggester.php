<?php

namespace App\Livewire;

use App\Filament\Pages\TelegramScanDetail;
use App\Models\TelegramChannelProduct;
use App\Services\Telegram\TelegramNameGenerator;
use Livewire\Component;
use Throwable;

/**
 * Hızlı Ekle panelinde adı boş gelen ürün için görselden ad üretir.
 *
 * Neden ayrı bileşen: üretim, Gemini'ye giden birkaç saniyelik senkron bir
 * istek. Ana sayfanın (TelegramScanDetail) bir metodunda çalışsaydı Livewire o
 * bileşene giden bütün istekleri sıraya alır, panel ad gelene kadar kilitli
 * kalırdı (kategori/renk seçilemez, "açılmıyor" gibi görünür). Ayrı bileşende
 * çalışınca istek kendi bağlantısında koşar; panel açık ve kullanılabilir
 * kalır, ad hazır olunca olayla ana panele iletilir.
 */
class TelegramNameSuggester extends Component
{
    public string $productId;

    public function suggest(): void
    {
        try {
            $product = TelegramChannelProduct::query()->with('images')->findOrFail($this->productId);
            $name = app(TelegramNameGenerator::class)->generate($product);
        } catch (Throwable $e) {
            // Sebebi ana panele ilet: yer tutucu "üretiliyor"da takılı kalmasın,
            // kullanıcı hatayı görüp adı elle yazsın ya da "Yeniden üret"e bassın.
            $this->dispatch('telegram-name-failed', message: $e->getMessage())->to(TelegramScanDetail::class);

            return;
        }

        // Aday kayda hemen yazılıyor: pencere kapatılsa bile aynı ürün için
        // ikinci kez üretim yapılmasın ve listede adıyla görünsün.
        $product->forceFill(['name' => $name, 'name_source' => 'ai'])->save();

        $this->dispatch('telegram-name-suggested', name: $name)->to(TelegramScanDetail::class);
    }

    public function render()
    {
        return view('livewire.telegram-name-suggester');
    }
}
