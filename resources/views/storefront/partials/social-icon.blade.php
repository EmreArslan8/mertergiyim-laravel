{{-- Panelde seçilen platforma göre ikon; bilinmeyen platformda genel bağlantı ikonu. --}}
@switch($platform)
    @case('instagram')
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <rect x="3.5" y="3.5" width="17" height="17" rx="5.2" stroke="currentColor" stroke-width="1.8"/>
            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
            <circle cx="17.3" cy="6.9" r="1.1" fill="currentColor"/>
        </svg>
        @break
    @case('facebook')
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M14.2 8.2V6.9c0-.7.5-.9.9-.9h2.1V2.6L14.3 2.5c-3.2 0-4 2.4-4 3.9v1.8H7.8V12h2.5v9.5h3.9V12h2.9l.4-3.8h-3.3Z"/>
        </svg>
        @break
    @case('tiktok')
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M16.2 2.5h-3v13.1a2.6 2.6 0 1 1-2.2-2.6v-3a5.6 5.6 0 1 0 5.2 5.6V9.1a6.6 6.6 0 0 0 3.6 1.1V7.3a3.7 3.7 0 0 1-3.6-3.8Z"/>
        </svg>
        @break
    @case('youtube')
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8ZM10 15V9l5.2 3-5.2 3Z"/>
        </svg>
        @break
    @case('linkedin')
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M6.9 8.6H3.6V21h3.3V8.6ZM5.3 3a1.9 1.9 0 1 0 0 3.8 1.9 1.9 0 0 0 0-3.8ZM20.4 13.6c0-3.3-1.8-4.9-4.2-4.9a3.6 3.6 0 0 0-3.3 1.8V8.6H9.6V21h3.3v-6.5c0-1.4.7-2.3 1.9-2.3s1.9.8 1.9 2.3V21h3.3v-7.4Z"/>
        </svg>
        @break
    @case('x')
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M17.5 3h3l-6.6 7.5L21.8 21h-6l-4.7-6.1L5.7 21h-3l7-8L2.5 3h6.2l4.2 5.6L17.5 3Zm-1 16h1.6L7.6 4.7H5.9L16.5 19Z"/>
        </svg>
        @break
    @case('pinterest')
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 2.5a9.5 9.5 0 0 0-3.5 18.3c-.1-.8-.2-2 0-2.9l1.2-5s-.3-.6-.3-1.5c0-1.4.8-2.4 1.8-2.4.9 0 1.3.6 1.3 1.4 0 .9-.6 2.2-.9 3.4-.2 1 .5 1.9 1.5 1.9 1.9 0 3.2-2.4 3.2-5.2 0-2.2-1.5-3.8-4.1-3.8a4.7 4.7 0 0 0-4.9 4.7c0 .9.3 1.5.7 2 .2.2.2.3.1.5l-.2.8c0 .3-.2.4-.5.2-1.3-.5-1.9-2-1.9-3.6 0-2.7 2.3-5.9 6.8-5.9 3.6 0 6 2.6 6 5.4 0 3.7-2 6.5-5 6.5-1 0-2-.6-2.3-1.2l-.6 2.4c-.2.8-.7 1.7-1.1 2.3A9.5 9.5 0 1 0 12 2.5Z"/>
        </svg>
        @break
    @case('whatsapp')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M3.8 20.2 5 16.5a7.7 7.7 0 1 1 2.9 2.8l-4.1.9Z"/>
            <path d="M9.2 8.6c.4 2.4 2.2 4.2 4.6 4.6l1-1.3 1.8.8v1.5c-2.9.6-6.7-2.6-7.4-5.6h1.4l.6 1.6-1 .9"/>
        </svg>
        @break
    @case('telegram')
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M21.6 4.3 18.7 19c-.2 1-.8 1.2-1.6.8l-4.4-3.3-2.1 2c-.2.3-.4.5-.9.5l.3-4.5 8.2-7.4c.4-.3-.1-.5-.6-.2L7.4 13 3 11.6c-1-.3-1-1 .2-1.4l17.2-6.6c.8-.3 1.5.2 1.2 1.7Z"/>
        </svg>
        @break
    @default
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M10 13.5a3.5 3.5 0 0 0 5 0l3-3a3.5 3.5 0 0 0-5-5l-1.2 1.2"/>
            <path d="M14 10.5a3.5 3.5 0 0 0-5 0l-3 3a3.5 3.5 0 0 0 5 5l1.2-1.2"/>
        </svg>
@endswitch
