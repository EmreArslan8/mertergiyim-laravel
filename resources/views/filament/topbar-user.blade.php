@php
    $user = filament()->auth()->user();
@endphp

@if ($user)
    <div class="merter-topbar-user">
        <span class="merter-user-identity">
            <b>{{ mb_strtoupper(mb_substr($user->email, 0, 1)) }}</b>
            <strong>{{ $user->email }}</strong>
        </span>

        <form action="{{ filament()->getLogoutUrl() }}" method="post">
            @csrf
            <button type="submit" class="merter-topbar-logout" aria-label="Çıkış yap">
                <x-filament::icon icon="heroicon-o-arrow-right-start-on-rectangle" />
            </button>
        </form>
    </div>
@endif
