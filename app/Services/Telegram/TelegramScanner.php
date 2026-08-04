<?php

namespace App\Services\Telegram;

use App\Models\TelegramAccount;
use App\Models\TelegramChannel;
use App\Models\TelegramChannelProduct;
use App\Models\TelegramChannelProductImage;
use App\Models\TelegramScan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Bir taramayı baştan sona yürütür.
 *
 * Mesajları nereden aldığını bilmez: kaynak ChannelSource arkasında. Hesap
 * seçilmemişse hesapsız önizleme yolu, seçilmişse hesaplı (orijinal medya)
 * kaynak kullanılır; gruplama, ayrıştırma ve kayıt ikisinde de aynıdır.
 *
 * Tarama sırasında görseller İNDİRİLMEZ, yalnızca Telegram CDN adresleri
 * saklanır. İndirme "Hızlı Ekle" anında, tek ürün için yapılır.
 *
 * Yine de işlem uzun sürebiliyor: her kanal için sayfa sayfa geriye yürünüyor
 * ve tek sayfa ~20 mesaj taşıyor. Üç kanal × 100 mesaj, PHP'nin varsayılan 30
 * saniyelik sınırını aşıyordu. Bu yüzden süre sınırı kaldırılıyor, ayrıca
 * TIME_BUDGET dolduğunda tarama kendi isteğiyle temiz biter ve "kısmen
 * tamamlandı" der — kalanı bir sonraki taramada gelir.
 */
class TelegramScanner
{
    /**
     * Taramanın toplam süre bütçesi (saniye).
     *
     * Süre dolunca tarama olduğu yerde temiz biter ve "kısmen tamamlandı" der;
     * yarıda kesilmiş istek yerine elde ne varsa onu kaydeder. Kalanı bir
     * sonraki taramada gelir, tekrar eden kayıt zaten oluşmuyor.
     */
    private const TIME_BUDGET = 110.0;

    private float $deadline = 0.0;

    public function __construct(
        private readonly WebPreviewSource $preview,
        private readonly TelegramProductGrouper $grouper,
        private readonly TelegramProductParser $parser,
    ) {}

    /**
     * Taramanın hangi kaynakla yürüyeceği.
     *
     * Girişi tamamlanmış hesap seçilmişse Telegram'a istemci olarak bağlanan
     * kaynak, aksi halde hesapsız önizleme yolu kullanılır. Hesap kaydı silinmiş
     * ya da oturumu düşmüşse tarama hata vermek yerine önizlemeye düşer:
     * düşük çözünürlüklü sonuç, hiç sonuç olmamasından iyidir.
     */
    private function sourceFor(TelegramScan $scan): ChannelSource
    {
        if (blank($scan->telegram_account_id)) {
            return $this->preview;
        }

        $account = TelegramAccount::query()->find($scan->telegram_account_id);

        if (! $account instanceof TelegramAccount || ! $account->isReady()) {
            return $this->preview;
        }

        return new MtProtoSource($account, app(TelegramClientFactory::class));
    }

    /**
     * Taramanın tamamını tek seferde yürütür.
     *
     * Komut satırı ve testler için. Panel bunu kullanmıyor: orada tarama
     * kanal kanal ilerletiliyor (advance) ki kullanıcı beklerken ne olduğunu
     * görebilsin.
     */
    public function run(TelegramScan $scan): TelegramScan
    {
        $this->begin($scan);

        while ($this->advance($scan)) {
            // advance her çağrıda tek kanal işler ve imleci ilerletir.
        }

        return $scan->refresh();
    }

    /** Taramayı başlatır; henüz hiçbir kanal işlenmez. */
    public function begin(TelegramScan $scan): TelegramScan
    {
        $source = $this->sourceFor($scan);

        $scan->update([
            'status' => 'running',
            'message' => 'Tarama başlıyor...',
            'source' => $source->key(),
            'started_at' => now(),
            'cursor' => 0,
            'found_count' => 0,
            'new_count' => 0,
            'changed_count' => 0,
        ]);

        return $scan;
    }

    /**
     * Sıradaki kanalı işler.
     *
     * @return bool İşlenecek kanal kaldıysa true. Panel bunu her istekte bir
     *              kez çağırıp ilerleme çubuğunu güncelliyor; böylece uzun
     *              tarama tek bir isteğin süresine sıkışmıyor.
     */
    public function advance(TelegramScan $scan): bool
    {
        if ($scan->status !== 'running') {
            return false;
        }

        $channels = array_values($scan->channels ?? []);
        $index = (int) $scan->cursor;

        if ($index >= count($channels)) {
            $this->finish($scan);

            return false;
        }

        // Tek kanal da uzun sürebiliyor (sayfa sayfa geriye yürünüyor);
        // PHP'nin varsayılan 30 saniyelik sınırı yetmiyordu.
        @set_time_limit(0);

        // Süre bütçesi çağrı başına: panelde her istek bir kanal işlediği için
        // toplam tarama süresi tek isteğe sıkışmıyor.
        $this->deadline = microtime(true) + self::TIME_BUDGET;

        $username = TelegramChannel::normalizeUsername((string) $channels[$index]);

        try {
            $tally = $this->scanChannel($scan, $this->sourceFor($scan), $username);
        } catch (Throwable $e) {
            Log::error('Telegram taraması başarısız', ['scan' => $scan->number, 'kanal' => $username, 'error' => $e->getMessage()]);

            $scan->update([
                'status' => 'failed',
                'message' => 'Tarama başarısız.',
                'error' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            return false;
        }

        $done = $index + 1;

        $scan->update([
            'cursor' => $done,
            'found_count' => $scan->found_count + $tally['total'],
            'new_count' => $scan->new_count + $tally['new'],
            'changed_count' => $scan->changed_count + $tally['changed'],
            'message' => $done.'/'.count($channels).' kanal tarandı',
        ]);

        if ($done >= count($channels)) {
            $this->finish($scan);

            return false;
        }

        return true;
    }

    private function finish(TelegramScan $scan): void
    {
        $scan->refresh();

        $scan->update([
            'status' => 'completed',
            'message' => $scan->new_count > 0
                ? $scan->new_count.' yeni ürün bulundu.'
                : 'Yeni ürün yok.',
            'finished_at' => now(),
        ]);
    }

    /**
     * @return array{total: int, new: int, changed: int}
     */
    private function scanChannel(TelegramScan $scan, ChannelSource $source, string $username): array
    {
        $limit = max(1, (int) $scan->message_limit);

        // Süre bütçesi kaynağa da geçiyor: sayfa sayfa yürürken bütçe dolarsa
        // eldeki mesajları kaybetmeden çıkıyor.
        $messages = $source->messages($username, $limit, fn (): bool => $this->outOfTime());

        $tally = ['total' => 0, 'new' => 0, 'changed' => 0];

        if ($messages === []) {
            return $tally;
        }

        // Yön tespiti ve öksüz medya elemesi grouper'ın içinde: gruplayıcı, öndeki
        // albümü silmeden önce kanalın "albüm-önce mi metin-önce mi" olduğunu
        // örnekten anlıyor.
        $products = $this->grouper->group($messages);

        foreach ($products as $product) {
            $outcome = $this->store($scan, $username, $product);

            $tally['total']++;

            if ($outcome === 'new') {
                $tally['new']++;
            } elseif ($outcome === 'changed') {
                $tally['changed']++;
            }
        }

        TelegramChannel::query()
            ->where('username', $username)
            ->update([
                'last_scanned_message_id' => max(array_column($messages, 'id')),
                'last_scanned_at' => now(),
            ]);

        return $tally;
    }


    private function outOfTime(): bool
    {
        return $this->deadline > 0.0 && microtime(true) >= $this->deadline;
    }

    /**
     * Aynı mesaj tekrar tarandığında kayıt çoğalmamalı; kanal + mesaj id
     * benzersiz. Kullanıcının elle düzelttiği alanları ezmemek için yalnızca
     * ham veri ve henüz dokunulmamış kayıtlar güncellenir.
     *
     * @param  array<string, mixed>  $product
     */
    private function store(TelegramScan $scan, string $username, array $product): string
    {
        $record = TelegramChannelProduct::query()->firstOrNew([
            'channel' => $username,
            'message_id' => $product['message_id'],
        ]);

        // Kanal postu düzenlemiş mi? Telegram'ın "edited" işaretine bakmıyoruz
        // (önizlemede güvenilir değil); sakladığımız ham metinle karşılaştırmak
        // hem hesaplı hem hesapsız yolda aynı sonucu veriyor.
        $changed = $record->exists && $record->raw_text !== $product['text'];

        // Kataloğa aktarılmış ya da elle düzenlenmiş kaydı bozma. Yine de
        // değişikliği bildiriyoruz: sitedeki fiyat eskimiş olabilir.
        if ($record->exists && in_array($record->status, ['approved', 'imported'], true)) {
            // İçeriğe dokunulmuyor ama "en son bu taramada görüldü" bilgisi
            // yazılıyor: yoksa tarama detayındaki "Hepsi" listesi bu kayıtları
            // atlıyor ve sayaçla listelenen ürün sayısı tutmuyordu.
            $record->forceFill(array_filter([
                'telegram_scan_id' => $scan->id,
                'scraped_at' => now(),
                'source_changed_at' => $changed ? now() : null,
            ]))->save();

            return $changed ? 'changed' : 'existing';
        }

        // Ucuz geçiş: kayıt var ve ham metin değişmemişse parse ile medya
        // senkronunu atla; yalnızca "en son bu taramada görüldü" defterini yaz.
        // Tarama kanalın son N mesajını her seferinde baştan okuduğu için
        // çoğu mesaj bu yoldan geçer; yüz değişmemiş mesaj için gereksiz parse
        // ve medya sorgusu yapılmaz. Metin aynı olduğundan ürün alanları ve
        // görseller de değişmemiştir.
        if ($record->exists && ! $changed) {
            $record->forceFill([
                'telegram_scan_id' => $scan->id,
                'scraped_at' => now(),
            ])->save();

            // Kayıt daha önce medyasız kaydedilmişse (ör. eski indirme hatası)
            // ve mesajda medya varsa, ucuz geçişte bile bir kez geri doldur.
            // Normal (medyası zaten olan) kayıtlar bu sorguya girmez.
            if ($product['media'] !== [] && $record->images()->doesntExist()) {
                $this->syncMedia($record, $product['media']);
            }

            return 'existing';
        }

        // Buradan sonrası yalnızca yeni ya da metni değişmiş kayıt için çalışır.
        $parsed = $this->parser->parse($product['text']);

        $isNew = ! $record->exists;

        if ($changed) {
            $record->source_changed_at = now();
        }

        // İlk görüldüğü tarama hiç değişmiyor; detay sayfası "bu taramada
        // ortaya çıkanlar"ı buradan buluyor. telegram_scan_id ise her
        // taramada güncelleniyor (en son nerede görüldü).
        if ($isNew) {
            $record->first_telegram_scan_id = $scan->id;
        }

        $record->fill([
            'telegram_scan_id' => $scan->id,
            'post_url' => 'https://t.me/'.$username.'/'.$product['message_id'],
            'posted_at' => $product['posted_at'],
            'raw_text' => $product['text'],
            'scraped_at' => now(),
        ]);

        // Elle girilmiş ad korunur; parser'ın bulduğu yalnızca boşluğu doldurur.
        if ($record->name_source !== 'manual') {
            $record->name = $parsed['name'] ?? $record->name;
            $record->name_source = $parsed['name'] ? 'channel' : $record->name_source;
        }

        foreach (['product_code', 'price', 'currency', 'pack_size', 'size_series', 'sizes', 'colors'] as $field) {
            if ($parsed[$field] !== null) {
                $record->{$field} = $parsed[$field];
            }
        }

        $record->status ??= 'new';
        $record->save();

        $this->syncMedia($record, $product['media']);

        // Değişmemiş kayıt yukarıda ele alındı; buraya yalnızca yeni ya da
        // metni değişmiş kayıt ulaşır.
        return $isNew ? 'new' : 'changed';
    }

    /**
     * @param  array<int, array<string, mixed>>  $media
     */
    private function syncMedia(TelegramChannelProduct $record, array $media): void
    {
        if ($media === []) {
            return;
        }

        // Mevcut medya tek sorguda okunur. Önceden her görsel için ayrı ayrı
        // sorgu atılıyordu; 100 mesajlık taramada bu binlerce sorgu demekti ve
        // isteğin süresi dolup tarama ölüyordu.
        $existingByKey = $record->images()
            ->get()
            ->keyBy(fn ($image): string => $image->message_id.':'.$image->album_index.':'.$image->sort_order);

        $now = now();
        $insert = [];

        foreach ($media as $item) {
            $key = $item['message_id'].':'.$item['album_index'].':'.$item['sort_order'];
            $existing = $existingByKey->get($key);

            // İndirilmiş dosyanın adresini yeniden yazma; CDN bağlantıları
            // zamanla değişiyor ama indirdiğimiz dosya kalıcı.
            if ($existing && $existing->file_path) {
                continue;
            }

            $attributes = [
                'type' => $item['type'],
                'source_url' => $item['source_url'],
                'poster_url' => $item['poster_url'],
                'duration' => $item['duration'],
                'downloadable' => $item['downloadable'],
            ];

            if ($existing) {
                $existing->fill($attributes)->save();

                continue;
            }

            $insert[] = $attributes + [
                'id' => (string) Str::uuid(),
                'telegram_channel_product_id' => $record->getKey(),
                'message_id' => $item['message_id'],
                'album_index' => $item['album_index'],
                'sort_order' => $item['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Yeni kayıtlar tek insert ile yazılır.
        if ($insert !== []) {
            TelegramChannelProductImage::query()->insert($insert);
        }
    }
}
