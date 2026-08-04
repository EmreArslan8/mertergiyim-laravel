<x-filament-panels::page>
    <div class="merter-tg">
        <section class="merter-tg-head">
            <div>
                <span class="merter-tg-eyebrow">Telegram</span>
                <h1>Ürün Çek</h1>
            </div>
            <a href="{{ \App\Filament\Resources\TelegramChannelProducts\TelegramChannelProductResource::getUrl() }}"
               class="merter-tg-back" wire:navigate>Ürünlere dön</a>
        </section>

        <section class="merter-tg-card">
            <h2>Yeni Tarama</h2>
            <p>Seçili kanallar taranır. İşlem bitince tarama detayında ürünler görünür.</p>

            <div class="merter-tg-channels">
                @foreach ($channels as $channel)
                    <label class="merter-tg-channel">
                        <span>{{ '@'.$channel->username }}</span>
                        <input
                            type="checkbox"
                            value="{{ $channel->username }}"
                            wire:model="selectedChannels"
                        >
                    </label>
                @endforeach

                @if ($channels->isEmpty())
                    <p class="merter-tg-empty">
                        Kayıtlı kanal yok.
                        <a href="{{ \App\Filament\Resources\TelegramChannels\TelegramChannelResource::getUrl() }}" wire:navigate>
                            Kanal ekleyin
                        </a>
                    </p>
                @endif
            </div>

            <label class="merter-tg-field">
                <span>Son kaç mesaj taransın?</span>
                <input type="number" min="1" max="2000" step="1" wire:model="messageLimit">
            </label>

            {{-- Hesap yoksa çekim hesapsız yoldan yapılır: görseller küçük
                 gelir, büyük videolar hiç inmez. Kullanıcı bunu tarama
                 başlamadan bilsin. --}}
            @if ($accounts->isEmpty())
                <p class="merter-tg-note">
                    <strong>Bağlı Telegram hesabı yok.</strong>
                    Çekim önizleme üzerinden yapılacak: fotoğraflar küçük boyutlu gelir,
                    büyük videoların çoğu indirilemez.
                    <a href="{{ \App\Filament\Resources\TelegramAccounts\TelegramAccountResource::getUrl() }}" wire:navigate>
                        Hesap bağla
                    </a>
                </p>
            @elseif ($accounts->count() > 1)
                <label class="merter-tg-field">
                    <span>Hangi hesapla çekilsin?</span>
                    <select wire:model="accountId">
                        @foreach ($accounts as $account)
                            <option value="{{ $account->getKey() }}">{{ $account->label() }}</option>
                        @endforeach
                        <option value="">Hesapsız (önizleme kalitesi)</option>
                    </select>
                </label>
            @endif

            @php($running = $this->runningScan())

            @if ($running)
                {{-- Tarama sürerken: her yenilemede bir kanal işlenir (tick). --}}
                <div class="merter-tg-progress" wire:poll.750ms="tick">
                    {{-- Dolu kısım gerçekten biten kanallar; hareketli kısım
                         şu an işlenen kanal. Kanalın içindeki ilerlemeyi
                         bilmediğimiz için orada yüzde uydurmuyoruz. --}}
                    <div class="merter-tg-progress-bar">
                        <span class="merter-tg-progress-done" style="width: {{ $running->progressPercent() }}%"></span>
                        <span class="merter-tg-progress-active"
                              style="left: {{ $running->progressPercent() }}%; width: {{ $running->activeSlicePercent() }}%"></span>
                    </div>

                    <p class="merter-tg-progress-label">
                        <span class="merter-tg-spinner" aria-hidden="true"></span>
                        {{ $running->progressLabel() }}
                    </p>

                    <p class="merter-tg-progress-counts">
                        <strong>{{ $running->new_count }}</strong> yeni ürün
                        @if ($running->existingCount() > 0)
                            · {{ $running->existingCount() }} daha önce çekilmişti
                        @endif
                    </p>
                </div>
            @else
                <button
                    type="button"
                    class="merter-tg-submit"
                    wire:click="startScan"
                    wire:loading.attr="disabled"
                    wire:target="startScan"
                >
                    <span wire:loading.remove wire:target="startScan">Ürün Çek</span>
                    <span wire:loading wire:target="startScan">Başlatılıyor…</span>
                </button>
            @endif
        </section>

        <section class="merter-tg-card">
            <table class="merter-tg-table">
                <thead>
                    <tr>
                        <th>Tarama</th>
                        <th>Kanallar</th>
                        <th>Durum</th>
                        <th>İşlem</th>
                        <th>Ürün</th>
                        <th>Tarih</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($scans as $scan)
                        <tr>
                            <td class="merter-tg-number">#{{ $scan->number }}</td>
                            <td>{{ $scan->channelsLabel() }}</td>
                            <td>
                                <span class="merter-tg-status merter-tg-status--{{ $scan->status }}">
                                    {{ $scan->statusLabel() }}
                                </span>
                            </td>
                            <td>{{ $scan->message ?: '—' }}</td>
                            <td>
                                {{-- Kullanıcıyı ilgilendiren sayı yeni olanlar;
                                     toplam yalnızca bağlam için yanında duruyor. --}}
                                <strong>{{ $scan->new_count }}</strong> yeni
                                @if ($scan->existingCount() > 0)
                                    <span class="merter-tg-muted">/ {{ $scan->found_count }}</span>
                                @endif
                            </td>
                            <td>{{ optional($scan->finished_at ?? $scan->created_at)->format('d.m.Y H:i') }}</td>
                            <td class="merter-tg-actions">
                                <a href="{{ \App\Filament\Pages\TelegramScanDetail::getUrl(['record' => $scan->getKey()]) }}" wire:navigate>
                                    Detay
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="merter-tg-empty">Henüz tarama yapılmadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-filament-panels::page>
