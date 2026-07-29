<x-filament-panels::page>
    <div class="merter-dashboard">
        <section class="merter-dashboard-welcome">
            <div>
                <span class="merter-dashboard-eyebrow">
                    <i aria-hidden="true"></i>
                    Yönetim özeti
                </span>
                <h1>Hoş geldiniz, {{ $welcomeName }}</h1>
                <p>{{ $today }} · Mağazanızın güncel durumunu buradan takip edebilirsiniz.</p>
            </div>

            @if ($siteSettingsUrl || $adminSettingsUrl)
                <div class="merter-dashboard-welcome-actions">
                    @if ($siteSettingsUrl)
                        <a href="{{ $siteSettingsUrl }}" wire:navigate>
                            Site ayarları
                        </a>
                    @endif
                    @if ($adminSettingsUrl)
                        <a href="{{ $adminSettingsUrl }}" wire:navigate>
                            Admin ayarları
                        </a>
                    @endif
                </div>
            @endif
        </section>

        <section class="merter-dashboard-stats" aria-label="Mağaza özeti">
            @foreach ($stats as $stat)
                <a class="merter-dashboard-stat" href="{{ $stat['url'] }}" wire:navigate>
                    <span class="merter-dashboard-stat-top">
                        <span>{{ $stat['label'] }}</span>
                        <span class="merter-dashboard-stat-icon">
                            <x-filament::icon :icon="$stat['icon']" />
                        </span>
                    </span>
                    <strong @class(['is-alert' => $stat['alert'] ?? false])>{{ $stat['value'] }}</strong>
                    <small>{{ $stat['detail'] }}</small>
                    <span class="merter-dashboard-stat-link">
                        Yönet
                        <x-filament::icon icon="heroicon-o-arrow-right" />
                    </span>
                </a>
            @endforeach
        </section>

        <div class="merter-dashboard-lower">
            <section class="merter-dashboard-panel">
                <header>
                    <div>
                        <span>Hızlı erişim</span>
                        <h2>Yeni kayıt oluştur</h2>
                    </div>
                    <x-filament::icon icon="heroicon-o-bolt" />
                </header>

                <div class="merter-dashboard-actions">
                    @foreach ($quickActions as $action)
                        <a
                            @class([
                                'merter-dashboard-action',
                                'is-primary' => $action['primary'] ?? false,
                            ])
                            href="{{ $action['url'] }}"
                            wire:navigate
                        >
                            <span class="merter-dashboard-action-icon">
                                <x-filament::icon :icon="$action['icon']" />
                            </span>
                            <span>
                                <strong>{{ $action['label'] }}</strong>
                                <small>{{ $action['description'] }}</small>
                            </span>
                            <x-filament::icon icon="heroicon-o-arrow-up-right" />
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="merter-dashboard-panel merter-dashboard-shortcuts">
                <header>
                    <div>
                        <span>İçerik</span>
                        <h2>Kısayollar</h2>
                    </div>
                    <x-filament::icon icon="heroicon-o-squares-2x2" />
                </header>

                <nav aria-label="Dashboard kısayolları">
                    @foreach ($shortcuts as $shortcut)
                        <a href="{{ $shortcut['url'] }}" wire:navigate>
                            <span>
                                <x-filament::icon :icon="$shortcut['icon']" />
                                {{ $shortcut['label'] }}
                            </span>
                            <x-filament::icon icon="heroicon-o-chevron-right" />
                        </a>
                    @endforeach
                </nav>
            </section>
        </div>
    </div>
</x-filament-panels::page>
