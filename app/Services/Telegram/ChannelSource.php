<?php

namespace App\Services\Telegram;

/**
 * Bir kanalın mesajlarını getiren kaynak.
 *
 * İki uygulaması olacak:
 *   - WebPreviewSource : hesapsız, t.me/s/ önizlemesi (600×800, sıkıştırılmış video)
 *   - MtProtoSource    : hesapla, orijinal foto + video
 *
 * Gruplama, ayrıştırma ve kayıt katmanı ikisinde de ortak; bu yüzden mesaj
 * biçimi TelegramPageReader'ın ürettiği yapıyla aynı kalmak zorunda.
 */
interface ChannelSource
{
    /**
     * Kanalın son mesajlarını eskiden yeniye sıralı döndürür.
     *
     * @param  string  $username  '@' ve adres öneki olmadan kanal adı
     * @param  int  $limit  En yeniden geriye kaç mesaj alınacağı
     * @param  (callable(): bool)|null  $shouldStop  Süre bütçesi dolduğunda true döner;
     *                                               kaynak elindekini kaybetmeden çıkar
     * @return array<int, array{
     *   id: int, text: string, posted_at: ?string,
     *   photos: array<int, string>,
     *   videos: array<int, array{url: ?string, poster: ?string, duration: ?string}>
     * }>
     */
    public function messages(string $username, int $limit, ?callable $shouldStop = null): array;

    /** telegram_scans.source sütununa yazılan kaynak anahtarı: preview | mtproto */
    public function key(): string;
}
