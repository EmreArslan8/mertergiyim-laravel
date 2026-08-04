<?php

namespace App\Services\Telegram;

use App\Models\TelegramChannel;
use Illuminate\Support\Facades\Http;

/**
 * Hesapsız kaynak: t.me/s/<kanal> önizleme sayfası.
 *
 * Telegram açık kanalların son mesajlarını sunucu tarafında render edilmiş
 * HTML olarak veriyor; giriş, anahtar, numara gerekmiyor. Karşılığında
 * görseller küçültülmüş (600×800 civarı) ve videolar sıkıştırılmış (352×640)
 * geliyor — orijinaller için hesaplı kaynak gerekiyor.
 *
 * Sayfalama `?before=<mesaj id>` ile geriye doğru yürüyor; tek sayfa ~20 mesaj.
 */
class WebPreviewSource implements ChannelSource
{
    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    /** Sonsuz döngüye karşı üst sınır: 60 sayfa ≈ 1200 mesaj. */
    private const MAX_PAGES = 60;

    public function __construct(private readonly TelegramPageReader $reader) {}

    public function key(): string
    {
        return 'preview';
    }

    public function messages(string $username, int $limit, ?callable $shouldStop = null): array
    {
        $username = TelegramChannel::normalizeUsername($username);
        $limit = max(1, $limit);

        $messages = [];
        $before = null;

        for ($page = 0; $page < self::MAX_PAGES && count($messages) < $limit; $page++) {
            if ($shouldStop !== null && $shouldStop()) {
                break;
            }

            $html = $this->fetch($username, $before);

            if ($html === null) {
                break;
            }

            $pageMessages = $this->reader->messages($html);

            if ($pageMessages === []) {
                break;
            }

            foreach ($pageMessages as $message) {
                $messages[$message['id']] = $message;
            }

            $oldest = $this->reader->oldestMessageId($html);

            // Aynı sayfa tekrar geliyorsa arşivin sonundayız.
            if ($oldest === null || $oldest === $before) {
                break;
            }

            $before = $oldest;
        }

        if ($messages === []) {
            return [];
        }

        // En yeni mesajdan geriye doğru limit kadarını al, sonra eskiden yeniye
        // sırala: gruplayıcı albüm/metin eşleştirmesini bu sıraya göre yapıyor.
        krsort($messages);
        $messages = array_slice($messages, 0, $limit, true);
        ksort($messages);

        return array_values($messages);
    }

    private function fetch(string $username, ?int $before): ?string
    {
        $url = 'https://t.me/s/'.$username.($before ? '?before='.$before : '');

        // Kısa zaman aşımı: takılan tek bir sayfa bütün taramayı yemesin.
        $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
            ->connectTimeout(5)
            ->timeout(12)
            ->retry(1, 300, throw: false)
            ->get($url);

        return $response->successful() ? $response->body() : null;
    }
}
