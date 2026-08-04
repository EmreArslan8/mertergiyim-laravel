<?php

namespace App\Filament\Pages;

use App\Models\TelegramAccount;
use App\Models\TelegramChannel;
use App\Models\TelegramScan;
use App\Services\Telegram\TelegramScanner;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * "Ürün Çek" ekranı: kanal seçimi + kaç mesaj taranacağı + tarama geçmişi.
 *
 * Tarama senkron çalışıyor. Görseller tarama sırasında indirilmediği için
 * 100 mesajlık tarama ~10 HTTP isteğine denk geliyor ve saniyeler sürüyor;
 * bu yüzden kuyruk kurmaya gerek kalmadı.
 */
class TelegramScans extends Page
{
    protected string $view = 'filament.pages.telegram-scans';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    /**
     * Kenar çubuğunda tek kalem var: Telegram > Ürünler. Bu ekran oradan
     * açılıyor; menüde ayrıca yer kaplamıyor.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Telegram Ürün Çek';

    /** @var array<int, string> */
    public array $selectedChannels = [];

    public int $messageLimit = 100;

    /**
     * Çekimin hangi hesapla yapılacağı; boşsa hesapsız önizleme yolu.
     *
     * Hesapla çekimde fotoğraf ve videolar orijinal çözünürlükte iniyor;
     * hesapsız yolda büyük videoların çoğu hiç gelmiyor.
     */
    public ?string $accountId = null;

    public static function canAccess(): bool
    {
        $user = filament()->auth()->user();

        return (bool) $user?->is_active && $user->isSuperAdmin();
    }

    public function mount(): void
    {
        // Varsayılan: tüm aktif kanallar seçili gelsin.
        $this->selectedChannels = TelegramChannel::query()->active()->orderBy('sort_order')->pluck('username')->all();

        // Tarama sırasında sayfa kapatılmış olabilir; kayıt "çalışıyor"da
        // kalıyor ve kimse ilerletmiyordu. Sayfa açılınca kaldığı yerden
        // devam eder — imleç veritabanında, hiçbir kanal tekrar taranmaz.
        $this->runningScanId = TelegramScan::query()
            ->whereIn('status', ['queued', 'running'])
            ->latest('number')
            ->value('id');

        // Bağlı hesap varsa varsayılan olarak o kullanılır: iyi sonuç için
        // kullanıcının ayrıca bir şey seçmesi gerekmesin.
        $this->accountId = TelegramAccount::query()->ready()->orderBy('sort_order')->value('id');
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * Süren taramanın kimliği.
     *
     * Doluyken ekranda ilerleme çubuğu görünür ve Livewire kendini yenileyerek
     * taramayı kanal kanal ilerletir.
     */
    public ?string $runningScanId = null;

    public function startScan(): void
    {
        $channels = array_values(array_filter($this->selectedChannels));

        if ($channels === []) {
            Notification::make()->title('En az bir kanal seçin.')->warning()->send();

            return;
        }

        $limit = max(1, min(2000, $this->messageLimit));

        $scan = TelegramScan::create([
            'channels' => $channels,
            'message_limit' => $limit,
            'status' => 'queued',
            'telegram_account_id' => $this->accountId,
        ]);

        // Burada tarama yapılmıyor: kayıt açılıyor ve istek hemen bitiyor.
        // Böylece kullanıcı boş ekrana bakmıyor, ilerleme çubuğu anında çıkıyor.
        app(TelegramScanner::class)->begin($scan);

        $this->runningScanId = $scan->getKey();
    }

    /**
     * Ekran her yenilendiğinde bir kanal işler.
     *
     * Kuyruk/worker kurmadan ilerleme göstermenin yolu bu: her istek tek kanal
     * tarıyor ve dönüyor, uzun tarama tek isteğin süresine sıkışmıyor.
     */
    public function tick(): void
    {
        if ($this->runningScanId === null) {
            return;
        }

        $scan = TelegramScan::query()->find($this->runningScanId);

        if (! $scan instanceof TelegramScan) {
            $this->runningScanId = null;

            return;
        }

        if ($scan->isRunning()) {
            app(TelegramScanner::class)->advance($scan);

            $scan->refresh();
        }

        if ($scan->isRunning()) {
            return;
        }

        $this->runningScanId = null;

        if ($scan->status === 'failed') {
            Notification::make()
                ->title('Tarama başarısız')
                ->body($scan->error ?: 'Bilinmeyen hata.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title($scan->new_count > 0 ? $scan->new_count.' yeni ürün bulundu' : 'Yeni ürün yok')
            ->body($scan->new_count > 0
                ? $scan->existingCount().' ürün daha önce çekilmişti.'
                : 'Kanallar son taramadan beri yeni paylaşım yapmamış.')
            ->success()
            ->send();

        $this->redirect(TelegramScanDetail::getUrl(['record' => $scan->getKey()]));
    }

    public function runningScan(): ?TelegramScan
    {
        return $this->runningScanId === null
            ? null
            : TelegramScan::query()->find($this->runningScanId);
    }

    protected function getViewData(): array
    {
        return [
            'channels' => TelegramChannel::query()->active()->orderBy('sort_order')->get(),
            'scans' => TelegramScan::query()->latest('number')->limit(25)->get(),
            'accounts' => TelegramAccount::query()->ready()->orderBy('sort_order')->get(),
        ];
    }
}
