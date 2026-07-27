@php
    $user = filament()->auth()->user();
@endphp

@if ($user)
    <div class="merter-topbar-user">
        <span>{{ $user->email }}</span>

        <form action="{{ filament()->getLogoutUrl() }}" method="post">
            @csrf
            <button type="submit">Çıkış</button>
        </form>
    </div>
@endif
