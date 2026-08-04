<?php

namespace App\Services\Telegram;

/**
 * t.me/s/<kanal> önizleme sayfasının HTML'ini mesajlara çevirir.
 *
 * Sayfa React değil düz HTML; her mesaj `tgme_widget_message` ile başlayan bir
 * blok. Ağ tarafına hiç dokunmaz — böylece gerçek sayfa örnekleriyle test
 * edilebiliyor.
 */
class TelegramPageReader
{
    /**
     * Sayfadaki mesajları id sırasına göre döndürür.
     *
     * @return array<int, array{
     *   id: int, text: string, posted_at: ?string,
     *   photos: array<int, string>,
     *   videos: array<int, array{url: ?string, poster: ?string, duration: ?string}>
     * }>
     */
    public function messages(string $html): array
    {
        $messages = [];

        // İlk parça mesaj değil, sayfa başlığı.
        $blocks = explode('class="tgme_widget_message ', $html);
        array_shift($blocks);

        foreach ($blocks as $block) {
            if (! preg_match('/data-post="[^"\/]+\/(\d+)"/', $block, $m)) {
                continue;
            }

            $id = (int) $m[1];

            $messages[$id] = [
                'id' => $id,
                'text' => $this->text($block),
                'posted_at' => $this->postedAt($block),
                'photos' => $this->photos($block),
                'videos' => $this->videos($block),
            ];
        }

        ksort($messages);

        return array_values($messages);
    }

    /** Sayfadaki en küçük mesaj id'si; sayfalama (?before=) için gerekir. */
    public function oldestMessageId(string $html): ?int
    {
        preg_match_all('/data-post="[^"\/]+\/(\d+)"/', $html, $m);

        return $m[1] === [] ? null : (int) min(array_map('intval', $m[1]));
    }

    private function text(string $block): string
    {
        // Metin bloğu footer'dan önce biter; albümlerde metin hiç olmayabilir.
        if (! preg_match('/<div class="tgme_widget_message_text[^"]*"[^>]*>(.*?)<\/div>\s*<div class="tgme_widget_message_footer/s', $block, $m)
            && ! preg_match('/<div class="tgme_widget_message_text[^"]*"[^>]*>(.*?)<\/div>/s', $block, $m)) {
            return '';
        }

        $text = preg_replace('/<br\s*\/?>/i', "\n", $m[1]) ?? $m[1];
        $text = strip_tags($text);

        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function postedAt(string $block): ?string
    {
        return preg_match('/<time datetime="([^"]+)"/', $block, $m) ? $m[1] : null;
    }

    /**
     * @return array<int, string>
     */
    private function photos(string $block): array
    {
        // Video kapak kareleri de aynı CDN'de duruyor; onları almamak için
        // yalnızca foto sarmalayıcısındaki adresler okunur.
        $photos = [];

        foreach (explode('js-message_photo', $block) as $index => $part) {
            if ($index === 0) {
                continue;
            }

            if (preg_match("/background-image:url\('([^']+)'\)/", substr($part, 0, 1500), $m)) {
                $photos[] = $m[1];
            }
        }

        return array_values(array_unique($photos));
    }

    /**
     * Videolar iki halde gelir:
     *  - mp4 adresi var  → indirilebilir
     *  - "Media is too big" → Telegram dosyayı önizlemede yayınlamıyor,
     *    elimizde yalnızca kapak karesi kalıyor.
     *
     * @return array<int, array{url: ?string, poster: ?string, duration: ?string}>
     */
    private function videos(string $block): array
    {
        $videos = [];

        foreach (explode('js-message_video_player', $block) as $index => $part) {
            if ($index === 0) {
                continue;
            }

            $segment = substr($part, 0, 3000);

            $videos[] = [
                'url' => preg_match('/(https:\/\/[^"\']+\.mp4[^"\']*)/', $segment, $m) ? $m[1] : null,
                'poster' => preg_match("/background-image:url\('([^']+)'\)/", $segment, $m) ? $m[1] : null,
                'duration' => preg_match('/js-message_video_duration">([^<]*)</', $segment, $m) ? trim($m[1]) : null,
            ];
        }

        return $videos;
    }
}
