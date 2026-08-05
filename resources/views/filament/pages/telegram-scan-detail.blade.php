<x-filament-panels::page>
    <div class="merter-tg">
        <section class="merter-tg-head">
            <div>
                <span class="merter-tg-eyebrow">Telegram Channel Products</span>
                <h1>Tarama #{{ $scan->number }}</h1>
                <p>
                    {{ $scan->channelsLabel() }}
                    · Son {{ $scan->message_limit }} mesaj
                    · {{ $scan->message ?: $scan->statusLabel() }}
                </p>
            </div>
            <a href="{{ $backUrl }}" class="merter-tg-back" wire:navigate>Modüle dön</a>
        </section>

        <section class="merter-tg-stats">
            <div class="merter-tg-stat">
                <span>Yeni ürün</span>
                <strong>{{ $scan->new_count }}</strong>
            </div>
            <div class="merter-tg-stat">
                <span>Daha önce çekilmişti</span>
                <strong>{{ $scan->existingCount() }}</strong>
            </div>
            <div class="merter-tg-stat">
                <span>Kanal güncellemiş</span>
                <strong>{{ $scan->changed_count }}</strong>
            </div>
            <div class="merter-tg-stat">
                <span>Bitiş</span>
                <strong>{{ optional($scan->finished_at)->format('H:i') ?: '—' }}</strong>
            </div>
        </section>

        {{-- Tek liste: taramada görülen tüm ürünler birlikte gösteriliyor.
             Yeni/eski ayrımı kartlardaki rozetten ("Bu taramada çıktı" /
             "Daha önce çekilmişti") okunuyor. Arama + filtre çubuğu listeyi
             daraltır; yazarken canlı süzüldüğü için ayrı "ara" düğmesi yok. --}}
        <section class="merter-tg-filters">
            <input
                type="search"
                class="merter-tg-filter-search"
                placeholder="Ürün adı, kod veya mesaj metninde ara…"
                wire:model.live.debounce.400ms="search"
            >

            <select class="merter-tg-filter-select" wire:model.live="channelFilter">
                <option value="">Tüm kanallar</option>
                @foreach ($channelOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <select class="merter-tg-filter-select" wire:model.live="addedFilter">
                <option value="">Eklenen + eklenmeyen</option>
                <option value="no">Kataloğa eklenmemiş</option>
                <option value="yes">Kataloğa eklenmiş</option>
            </select>

            <select class="merter-tg-filter-select" wire:model.live="scopeFilter">
                <option value="">Taramada görülen tümü</option>
                <option value="new">Sadece bu taramada yeni</option>
            </select>

            <select class="merter-tg-filter-select" wire:model.live="sort">
                <option value="newest">En yeni</option>
                <option value="oldest">En eski</option>
                <option value="price_asc">Fiyat: düşük → yüksek</option>
                <option value="price_desc">Fiyat: yüksek → düşük</option>
            </select>

            @if ($hasActiveFilters)
                <span class="merter-tg-filter-count">{{ $products->count() }} sonuç</span>
                <button type="button" class="merter-tg-link" wire:click="clearFilters">Temizle</button>
            @endif
        </section>

        @forelse ($products as $product)
            @php
                $missing = \App\Filament\Pages\TelegramScanDetail::missingFields($product);
                $photos = $product->images->where('type', 'photo');
                $videos = $product->images->where('type', 'video');
            @endphp

            @php
                // Foto ve videolar tek listede: büyütücü ikisi arasında da gezinsin.
                $media = $photos
                    ->map(fn ($image) => [
                        'type' => 'photo',
                        'src' => $image->url(),
                        'thumb' => $image->thumbnailUrl(),
                        'duration' => null,
                        'ok' => true,
                    ])
                    ->concat($videos->map(fn ($image) => [
                        'type' => 'video',
                        'src' => $image->url(),
                        // 20 MB üstü videolarda Telegram dosyayı vermiyor;
                        // elimizde yalnızca kapak karesi kalıyor.
                        'thumb' => $image->thumbnailUrl(),
                        'duration' => $image->duration,
                        'ok' => (bool) $image->downloadable && filled($image->source_url),
                    ]))
                    // Ne dosyası ne kapağı olan kayıt gösterilemez.
                    ->filter(fn (array $item): bool => filled($item['thumb']))
                    ->values();
            @endphp

            <article
                class="merter-tg-product"
                x-data="{
                    open: false,
                    i: 0,
                    items: @js($media),
                    postUrl: @js($product->post_url),
                    show(index) { this.i = index; this.open = true; },
                    next() { this.i = (this.i + 1) % this.items.length; },
                    prev() { this.i = (this.i - 1 + this.items.length) % this.items.length; },
                }"
                @keydown.escape.window="open = false"
                @keydown.arrow-right.window="open && next()"
                @keydown.arrow-left.window="open && prev()"
            >
                <div class="merter-tg-gallery">
                    @foreach ($media->take(4) as $index => $item)
                        <button type="button" class="merter-tg-thumb" @click="show({{ $index }})" title="Büyüt">
                            <img src="{{ $item['thumb'] }}" alt="" loading="lazy">

                            @if ($item['type'] === 'video')
                                <span class="merter-tg-thumb-play" aria-hidden="true"></span>
                                @if ($item['duration'])
                                    <span class="merter-tg-thumb-duration">{{ $item['duration'] }}</span>
                                @endif
                            @endif

                            @if ($loop->last && $media->count() > 4)
                                <span class="merter-tg-thumb-more">+{{ $media->count() - 4 }}</span>
                            @endif
                        </button>
                    @endforeach

                    @if ($media->isEmpty())
                        <div class="merter-tg-gallery-empty">Görsel yok</div>
                    @endif
                </div>

                {{-- Büyütücü: yalnızca sıradaki öğe basılıyor, hepsi birden
                     yüklenip videolar arka planda çalışmasın diye. --}}
                <div class="merter-tg-lightbox" x-show="open" x-cloak @click.self="open = false">
                    <button type="button" class="merter-tg-lb-close" @click="open = false" aria-label="Kapat">&times;</button>

                    <button type="button" class="merter-tg-lb-nav merter-tg-lb-prev"
                            x-show="items.length > 1" @click="prev()" aria-label="Önceki">&lsaquo;</button>

                    <div class="merter-tg-lb-stage">
                        <template x-if="open && items[i].type === 'photo'">
                            <img :src="items[i].src" alt="">
                        </template>

                        <template x-if="open && items[i].type === 'video' && items[i].ok">
                            <video :src="items[i].src" controls autoplay playsinline></video>
                        </template>

                        <template x-if="open && items[i].type === 'video' && ! items[i].ok">
                            <div class="merter-tg-lb-unavailable">
                                <img :src="items[i].thumb" alt="">
                                <p>
                                    Bu video Telegram önizlemesinden indirilemiyor (dosya çok büyük).
                                    <a :href="postUrl" target="_blank" rel="noopener">Telegram'da izle</a>
                                </p>
                            </div>
                        </template>
                    </div>

                    <button type="button" class="merter-tg-lb-nav merter-tg-lb-next"
                            x-show="items.length > 1" @click="next()" aria-label="Sonraki">&rsaquo;</button>

                    <p class="merter-tg-lb-counter" x-text="(i + 1) + ' / ' + items.length"></p>
                </div>

                <div class="merter-tg-product-body">
                    <div class="merter-tg-product-head">
                        <h3>{{ $product->name ?: ($product->product_code ? 'Code:'.$product->product_code : 'İsimsiz ürün') }}</h3>
                        <span class="merter-tg-status merter-tg-status--{{ $product->status }}">
                            {{ \App\Models\TelegramChannelProduct::STATUSES[$product->status] ?? $product->status }}
                        </span>
                    </div>

                    <div class="merter-tg-badges">
                        {{-- Ürünün geldiği Telegram grubu; kart üzerinde görünsün
                             diye modalda değil burada. Gruba tıklayınca açılır. --}}
                        <a class="merter-tg-source" href="{{ 'https://t.me/'.$product->channel }}" target="_blank" rel="noopener">@ {{ $product->channel }}</a>
                        {{-- Durum rozetindeki "Yeni" kaydın işlenme durumunu
                             anlatır; ürünün bu taramada mı çıktığı ayrı rozetle
                             gösteriliyor ki tek listede ikisi karışmasın. --}}
                        @if ($product->first_telegram_scan_id === $scan->id)
                            <span class="merter-tg-origin merter-tg-origin--fresh">Bu taramada çıktı</span>
                        @else
                            <span class="merter-tg-origin">Daha önce çekilmişti</span>
                        @endif
                        @if ($product->price !== null)
                            <span>{{ rtrim(rtrim(number_format((float) $product->price, 2, ',', '.'), '0'), ',') }} {{ $product->currency }}</span>
                        @endif
                        <span>{{ $photos->count() }} foto</span>
                        @if ($videos->isNotEmpty())
                            <span>{{ $videos->count() }} video</span>
                        @endif
                        @if ($product->size_series)
                            <span>{{ $product->size_series }}</span>
                        @endif
                    </div>

                    @if ($missing)
                        <p class="merter-tg-missing">{{ implode(' · ', $missing) }}</p>
                    @endif

                    {{-- Kataloğa aktarıldıktan sonra kaynak post değişmişse
                         (fiyat/metin) katalogdaki bilgi eskimiş olabilir; kullanıcı
                         ürünü açıp güncelleyebilsin diye uyarı + bağlantı. --}}
                    @if ($product->status === 'imported' && $product->source_changed_at)
                        <p class="merter-tg-stale">
                            Kaynak {{ $product->source_changed_at->diffForHumans() }} değişti · katalog bilgisi eskimiş olabilir
                            @if ($product->product_id)
                                · <a href="{{ \App\Filament\Resources\Products\ProductResource::getUrl('edit', ['record' => $product->product_id]) }}">Ürünü aç</a>
                            @endif
                        </p>
                    @endif

                    @if ($product->raw_text)
                        <p class="merter-tg-raw">{{ \Illuminate\Support\Str::limit($product->raw_text, 160) }}</p>
                    @endif

                    <div class="merter-tg-product-actions">
                        @if ($product->status === 'imported')
                            <button type="button" class="merter-tg-quick merter-tg-quick--done" disabled>
                                <span class="merter-tg-check" aria-hidden="true">✓</span> Kataloğa eklendi
                            </button>
                        @else
                            <button type="button" class="merter-tg-quick" wire:click="openQuickAdd('{{ $product->getKey() }}')">
                                Hızlı Ekle
                            </button>
                        @endif

                        @if ($product->post_url)
                            <a class="merter-tg-btn" href="{{ $product->post_url }}" target="_blank" rel="noopener">Mesaja git</a>
                        @endif
                        <a class="merter-tg-btn" href="{{ 'https://t.me/'.$product->channel }}" target="_blank" rel="noopener">Telegram konuşması</a>
                    </div>
                </div>
            </article>
        @empty
            <section class="merter-tg-card">
                @if ($hasActiveFilters)
                    <p class="merter-tg-empty">
                        Arama ve filtrelerle eşleşen ürün yok.
                        <button type="button" wire:click="clearFilters" class="merter-tg-link">Filtreleri temizle</button>
                    </p>
                @else
                    <p class="merter-tg-empty">Bu taramada ürün bulunamadı.</p>
                @endif
            </section>
        @endforelse

        {{-- HIZLI EKLE --}}
        @if ($quickAddProduct)
            @php
                // Sürükle-bırakla belirlenen sıra; numaralar ve kapak bunu izler.
                $qaMedia = $this->orderedMedia($quickAddProduct);
            @endphp

            <div class="merter-qa-backdrop" wire:key="qa-{{ $quickAddProduct->getKey() }}">
                <div class="merter-qa">
                    <header class="merter-qa-head">
                        <div>
                            <span class="merter-qa-eyebrow">Hızlı Ekle</span>
                            <h2>{{ $quickAddProduct->name ?: 'İsimsiz ürün' }}</h2>
                        </div>
                        <button type="button" class="merter-qa-close" wire:click="closeQuickAdd"
                                aria-label="Kapat" title="Kapat">
                            {{-- lucide: x --}}
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 6 6 18"/>
                                <path d="m6 6 12 12"/>
                            </svg>
                        </button>
                    </header>

                    <div class="merter-qa-body">
                        {{-- Modal içi bildirim: Filament toast'ları modalın
                             arkasında kaldığı için geri bildirim burada, üstte
                             yapışık gösteriliyor. --}}
                        @if (($quickAddFlash['message'] ?? '') !== '')
                            <div class="merter-qa-flash is-{{ $quickAddFlash['type'] ?? 'warning' }}"
                                 wire:key="qa-flash">
                                <span>{{ $quickAddFlash['message'] }}</span>
                                <button type="button" wire:click="$set('quickAddFlash', [])"
                                        aria-label="Kapat" title="Kapat">&times;</button>
                            </div>
                        @endif

                        {{-- Vitrin kararı en üstte: ürün eklenmeden önce
                             görülsün, formun dibinde kaybolmasın. --}}
                        <button type="button"
                                class="merter-qa-choice merter-qa-home @if ($form['show_on_home'] ?? true) is-selected @endif"
                                wire:click="toggleShowOnHome">
                            <span class="merter-qa-box" aria-hidden="true"></span>
                            Ana sayfada göster
                            <small>İşaretli değilse ürün yayında kalır, ana sayfadaki listede çıkmaz.</small>
                        </button>

                        <div class="merter-qa-grid">
                            <label class="merter-qa-field">
                                <span>Ürün adı</span>
                                {{-- Adı boş gelen ürünlerde üretim pencere
                                     açılır açılmaz kendiliğinden başlar; alan
                                     dolu gelsin ama düzenlenebilir kalsın.
                                     name_pending bitince (başarı/hata) bileşen
                                     kalkar, yer tutucu "üretiliyor"da takılmaz. --}}
                                @if ($form['name_pending'] ?? false)
                                    {{-- Üretim ayrı bileşende koşar; panel bu
                                         süre boyunca kilitlenmez. --}}
                                    @livewire('telegram-name-suggester',
                                        ['productId' => (string) $quickAddId],
                                        key('qa-name-'.$quickAddId))
                                @endif

                                <div class="merter-qa-inline">
                                    <input type="text" wire:model="form.name"
                                           placeholder="{{ ($form['name_pending'] ?? false) ? 'Görselden üretiliyor…' : 'Ürün adı' }}">
                                    {{-- Kanalların bir kısmı ad paylaşmıyor; ad
                                         yalnızca fotoğrafın içinde oluyor. --}}
                                    @if ($this->nameGeneratorReady())
                                        <button type="button"
                                                class="merter-qa-ai"
                                                wire:click="generateName"
                                                wire:loading.attr="disabled"
                                                wire:target="generateName"
                                                title="Fotoğraftan ürün adı üret">
                                            <span wire:loading.remove wire:target="generateName">Yeniden üret</span>
                                            <span wire:loading wire:target="generateName">Üretiliyor…</span>
                                        </button>
                                    @endif
                                </div>
                                @if (($form['name_source'] ?? null) === 'ai')
                                    <small>Ad görselden üretildi — kontrol edip düzeltebilirsiniz.</small>
                                @endif
                            </label>

                            <div class="merter-qa-field">
                                <span>Kategori</span>
                                {{-- Açılır liste yerine kutular: seçenekler az,
                                     hepsi bir bakışta görünsün. --}}
                                <div class="merter-qa-choices">
                                    @foreach ($categories as $category)
                                        @php
                                            $active = ($form['category_id'] ?? null) === $category->getKey();
                                        @endphp
                                        <button type="button"
                                                class="merter-qa-choice @if ($active) is-selected @endif"
                                                wire:click="selectCategory('{{ $category->getKey() }}')">
                                            <span class="merter-qa-box" aria-hidden="true"></span>
                                            {{ \App\Filament\Support\Multilingual::tr($category->name_i18n ?? null) ?: $category->name }}
                                        </button>
                                    @endforeach
                                </div>
                                {{-- Kategoriler de renkler gibi: listede yoksa
                                     buraya yazılıp tanımlanır ve seçili gelir. --}}
                                <div class="merter-qa-newcat">
                                    <button type="button" wire:click="addCategory">Kategori ekle ve seç</button>
                                    <input type="text"
                                           wire:model.blur="newCategoryName"
                                           wire:keydown.enter="addCategory"
                                           placeholder="Yeni kategori adı (ör. Kaban)">
                                </div>
                            </div>

                            <label class="merter-qa-field">
                                <span>Fiyat USD</span>
                                <input type="number" step="0.01" min="0" wire:model="form.price_usd" placeholder="0">
                            </label>

                            {{-- Tedarikçinin kodu kataloğa taşınmıyor; ürün
                                 kaydedilirken sistemin sıradaki kodu atanıyor.
                                 Telegram kaydında referans olarak duruyor. --}}
                            <div class="merter-qa-field">
                                <span>Ürün kodu</span>
                                <p class="merter-qa-note">
                                    Kaydederken sistemdeki sıradaki boş kod otomatik atanacak.
                                    @if ($quickAddProduct->product_code)
                                        <br>Tedarikçi kodu: <strong>{{ $quickAddProduct->product_code }}</strong>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <label class="merter-qa-field">
                            <span>Açıklama</span>
                            {{-- Panelin kendi zengin editörü (TipTap) ayrı
                                 bileşende koşar; değer her değişiklikte
                                 form.description'a olayla iletilir. --}}
                            @livewire('quick-add-description-editor',
                                ['description' => (string) ($form['description'] ?? '')],
                                key('qa-desc-'.$quickAddId))
                        </label>

                        <div class="merter-qa-cols">
                            <section>
                                <h3>
                                    Paket bedenleri
                                    {{-- Kanal "Seri 5 li" diyor; girilen adetler
                                         tutmuyorsa kullanıcı hemen görsün. --}}
                                    <small>
                                        toplam <strong>{{ $this->packTotal() }}</strong> parça
                                        @if ($quickAddProduct->pack_size && $quickAddProduct->pack_size !== $this->packTotal())
                                            · kanal {{ $quickAddProduct->pack_size }}'li diyor
                                        @endif
                                    </small>
                                </h3>
                                <div class="merter-qa-sizes">
                                    @foreach ($sizes as $size)
                                        <label>
                                            <span>{{ $size->name }}</span>
                                            <input type="number" min="0" step="1"
                                                   wire:model.live.debounce.400ms="form.pack.{{ $size->getKey() }}">
                                        </label>
                                    @endforeach
                                </div>
                            </section>

                            <section>
                                <h3>Renkler</h3>

                                @php
                                    $kanalRenkleri = $this->channelColors($quickAddProduct);
                                    $eksikRenkler = array_filter($kanalRenkleri, fn (array $r): bool => $r['color'] === null);
                                @endphp

                                @if ($kanalRenkleri !== [])
                                    {{-- Kanal rengi yazmışsa tekrar yazdırmıyoruz:
                                         sistemde olanlar seçili geliyor, olmayanlar
                                         tek tıkla ekleniyor. --}}
                                    <p class="merter-qa-hint">
                                        Kanal şunları yazmış:
                                        @foreach ($kanalRenkleri as $renk)
                                            @if ($renk['color'])
                                                <span class="merter-qa-tag is-known">{{ $renk['name'] }} ✓</span>
                                            @else
                                                <button type="button" class="merter-qa-tag"
                                                        wire:click="addChannelColor(@js($renk['name']))"
                                                        title="Sistemde yok — ekle ve seç">
                                                    + {{ $renk['name'] }}
                                                </button>
                                            @endif
                                        @endforeach
                                        @if ($eksikRenkler !== [])
                                            <small>Sistemde olmayanlara tıklayarak ekleyebilirsiniz.</small>
                                        @endif
                                    </p>
                                @endif
                                {{-- Kanallar renk adını yazmıyor, yalnızca kaç renk
                                     olduğunu söylüyor; seçim elle yapılır. --}}
                                <div class="merter-qa-colors">
                                    @foreach ($colors as $color)
                                        @php
                                            $checked = in_array($color->getKey(), (array) ($form['color_ids'] ?? []), true);
                                        @endphp
                                        <button type="button"
                                                class="merter-qa-color @if ($checked) is-selected @endif"
                                                wire:click="toggleColor('{{ $color->getKey() }}')">
                                            <span class="merter-qa-box" aria-hidden="true"></span>
                                            <span class="merter-qa-dot" style="background: {{ $color->hex }}"></span>
                                            {{ \App\Filament\Support\Multilingual::tr($color->name_i18n ?? null) ?: $color->name }}
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Kanallar sürekli yeni ton çıkarıyor;
                                     renk burada tanımlanıp hemen seçiliyor. --}}
                                <div class="merter-qa-newcolor">
                                    <input type="text"
                                           wire:model.blur="newColorName"
                                           wire:keydown.enter="addColor"
                                           placeholder="Yeni renk adı (ör. Adaçayı)">
                                    {{-- Tek seçici: tıklayınca renk tayfı açılır,
                                         değeri hep hex (#rrggbb) tutulur. --}}
                                    <input type="color"
                                           wire:model.blur="newColorHex"
                                           aria-label="Renk kodu">
                                    <button type="button" wire:click="addColor">Renk ekle ve seç</button>
                                </div>
                            </section>
                        </div>

                        @php
                            $order = $this->selectionOrder($quickAddProduct);
                            $coverId = $this->coverImageId($quickAddProduct);
                        @endphp

                        <section>
                            <h3>
                                Fotoğraflar ve videolar
                                <small>
                                    <strong>{{ count($order) }}</strong> / {{ $qaMedia->count() }} seçili ·
                                    sürükleyerek sırala, numaralar kataloğa girecek sırayı gösterir
                                </small>
                            </h3>

                            <div class="merter-qa-media" data-merter-sortable>
                                @foreach ($qaMedia as $image)
                                    @php
                                        $no = $order[$image->getKey()] ?? null;
                                    @endphp
                                    <button type="button"
                                            wire:key="qa-media-{{ $image->getKey() }}"
                                            data-image-id="{{ $image->getKey() }}"
                                            class="merter-qa-pick @if ($no) is-picked @endif"
                                            wire:click="toggleImage('{{ $image->getKey() }}')"
                                            aria-pressed="{{ $no ? 'true' : 'false' }}"
                                            title="{{ $no ? $no.'. sırada — sürükleyip sırala, çıkarmak için tıkla' : 'Eklemek için tıkla, sürükleyerek sırala' }}">
                                        <img src="{{ $image->thumbnailUrl() }}" alt="" loading="lazy">

                                        {{-- Sadece renk/solukluk yetmiyor: seçili
                                             olanlar numarayla, seçilmeyenler boş
                                             halkayla işaretleniyor. --}}
                                        <span class="merter-qa-badge">{{ $no ?? '' }}</span>

                                        @if ($image->getKey() === $coverId)
                                            <span class="merter-qa-cover">Kapak</span>
                                        @endif

                                        @if ($image->type === 'video')
                                            <span class="merter-tg-thumb-play" aria-hidden="true"></span>
                                        @endif

                                        @if (! $image->downloadable)
                                            <span class="merter-qa-warn">indirilemez</span>
                                        @endif
                                    </button>
                                @endforeach

                                @if ($qaMedia->isEmpty())
                                    <p class="merter-tg-empty">Bu üründe görsel yok.</p>
                                @endif
                            </div>

                            @if ($qaMedia->isNotEmpty())
                                <button type="button" class="merter-tg-link" wire:click="toggleAllImages">
                                    {{ count($order) === $qaMedia->count() ? 'Seçimi temizle' : 'Hepsini seç' }}
                                </button>
                            @endif
                        </section>
                    </div>

                    <footer class="merter-qa-foot">
                        @php
                            $qaErrors = $this->quickAddErrors();
                        @endphp

                        @if ($qaErrors !== [])
                            <span class="merter-qa-missing">Eksik: {{ implode(' · ', $qaErrors) }}</span>
                        @endif

                        <button type="button"
                                class="merter-qa-submit"
                                wire:click="saveQuickAdd"
                                wire:loading.attr="disabled"
                                wire:target="saveQuickAdd">
                            Ürünü ekle
                        </button>
                    </footer>

                    {{-- Kaydetme sırasında seçilen medya indiriliyor; bu birkaç
                         saniye sürüyor ve pencere donmuş gibi duruyordu. --}}
                    <div class="merter-qa-busy" wire:loading.flex wire:target="saveQuickAdd">
                        <span class="merter-qa-busy-spinner" aria-hidden="true"></span>
                        <p>
                            Ürün ekleniyor…
                            <small>Seçilen fotoğraf ve videolar indiriliyor, pencereyi kapatmayın.</small>
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
