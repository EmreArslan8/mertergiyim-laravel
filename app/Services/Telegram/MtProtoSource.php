<?php

namespace App\Services\Telegram;

use App\Models\TelegramAccount;
use App\Models\TelegramChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Hesaplı kaynak: Telegram'a istemci olarak bağlanıp mesajları okur.
 *
 * Önizleme yolundan iki temel farkı var:
 *
 *  - Medyanın adresi yok. Telegram dosyayı indirtiyor, bağlantı vermiyor;
 *    bu yüzden fotoğraf ve videolar tarama sırasında diske yazılıyor ve
 *    kayıtlara yerel yol düşüyor. Önizlemede süreli video bağlantılarının
 *    ölmesi sorunu da böylece ortadan kalkıyor.
 *  - Albümler `grouped_id` ile kesin biliniyor; sıraya bakıp tahmin etmeye
 *    gerek kalmıyor.
 *
 * Ölçü farkı (ölçülen değerler): fotoğraf 600×800 yerine 960×1280, video
 * 352×640 yerine 1080×1920 ve önizlemede hiç indirilemeyen büyük videolar
 * burada sorunsuz iniyor.
 */
class MtProtoSource implements ChannelSource
{
    /** Telegram tek istekte en fazla bu kadar mesaj veriyor. */
    private const PAGE = 100;

    public function __construct(
        private readonly TelegramAccount $account,
        private readonly TelegramClientFactory $factory,
    ) {}

    public function key(): string
    {
        return 'mtproto';
    }

    public function messages(string $username, int $limit, ?callable $shouldStop = null): array
    {
        $username = TelegramChannel::normalizeUsername($username);
        $limit = max(1, $limit);
        $peer = '@'.$username;

        $api = $this->factory->make($this->account);

        $collected = [];
        $offsetId = 0;

        while (count($collected) < $limit) {
            if ($shouldStop !== null && $shouldStop()) {
                break;
            }

            $page = $api->messages->getHistory(
                peer: $peer,
                limit: min(self::PAGE, $limit - count($collected)),
                offset_id: $offsetId,
            );

            $rows = $page['messages'] ?? [];

            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $collected[$row['id']] = $row;
            }

            // getHistory offset_id'den ESKİye doğru gidiyor.
            $offsetId = min(array_column($rows, 'id'));
        }

        if ($collected === []) {
            return [];
        }

        $this->account->forceFill(['last_used_at' => now()])->save();

        krsort($collected);
        $collected = array_slice($collected, 0, $limit, true);
        ksort($collected);

        return $this->toMessages($username, $collected, $api);
    }

    /**
     * Ham MTProto mesajlarını okuyucu biçimine çevirir.
     *
     * Albüm üyeleri (aynı grouped_id) tek mesajda birleştiriliyor: medyaları
     * toplanıyor, açıklaması hangisinde varsa o alınıyor. Böylece gruplayıcı
     * sıraya bakıp tahmin yürütmek zorunda kalmıyor.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function toMessages(string $username, array $rows, mixed $api): array
    {
        $messages = [];

        foreach ($rows as $row) {
            if (($row['_'] ?? '') !== 'message') {
                continue;
            }

            // Albümün ilk üyesinin kimliği ortak anahtar olur.
            $key = isset($row['grouped_id']) ? 'g'.$row['grouped_id'] : 'm'.$row['id'];

            $messages[$key] ??= [
                'id' => (int) $row['id'],
                'text' => '',
                'posted_at' => isset($row['date'])
                    ? date('c', (int) $row['date'])
                    : null,
                'photos' => [],
                'videos' => [],
            ];

            // Açıklama albümün herhangi bir üyesinde olabiliyor.
            $text = trim((string) ($row['message'] ?? ''));

            if ($text !== '' && $messages[$key]['text'] === '') {
                $messages[$key]['text'] = $text;
            }

            $media = $this->download($username, $row, $api);

            if ($media === null) {
                continue;
            }

            if ($media['type'] === 'photo') {
                $messages[$key]['photos'][] = $media['url'];

                continue;
            }

            $messages[$key]['videos'][] = [
                'url' => $media['url'],
                'poster' => null,
                'duration' => $media['duration'],
            ];
        }

        return array_values($messages);
    }

    /**
     * Mesajın medyasını diske indirir.
     *
     * @return array{type: string, url: string, duration: ?string}|null
     */
    private function download(string $username, array $row, mixed $api): ?array
    {
        $kind = $row['media']['_'] ?? null;

        if (! in_array($kind, ['messageMediaPhoto', 'messageMediaDocument'], true)) {
            return null;
        }

        $isPhoto = $kind === 'messageMediaPhoto';
        $duration = null;

        if (! $isPhoto) {
            $document = $row['media']['document'] ?? [];

            // Yalnızca video; belge/ses/gif ürün medyası değil.
            if (! str_starts_with((string) ($document['mime_type'] ?? ''), 'video/')) {
                return null;
            }

            foreach ($document['attributes'] ?? [] as $attribute) {
                if (($attribute['_'] ?? '') === 'documentAttributeVideo') {
                    $seconds = (int) round((float) ($attribute['duration'] ?? 0));
                    $duration = sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
                }
            }
        }

        $bucket = (string) config('storefront.buckets.telegram', 'telegram-images');
        $relative = $username.'/'.$row['id'].'.'.($isPhoto ? 'jpg' : 'mp4');
        $disk = Storage::disk('public_media');

        // Aynı mesaj tekrar tarandığında dosyayı yeniden indirme.
        if (! $disk->exists($bucket.'/'.$relative)) {
            // downloadToFile hedef dosyayı touch ediyor ama üst klasörü
            // oluşturmuyor; kanal klasörü yoksa indirme "No such file or
            // directory" ile patlıyordu. Klasörü önden garantiliyoruz.
            $disk->makeDirectory($bucket.'/'.$username);

            try {
                $api->downloadToFile($row, $disk->path($bucket.'/'.$relative));
            } catch (Throwable $e) {
                Log::warning('Telegram medyası indirilemedi', [
                    'kanal' => $username,
                    'mesaj' => $row['id'],
                    'hata' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return [
            'type' => $isPhoto ? 'photo' : 'video',
            'url' => $disk->url($bucket.'/'.$relative),
            'duration' => $duration,
        ];
    }
}
