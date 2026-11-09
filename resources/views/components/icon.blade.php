@props(['name'])

{{-- Outline icon set drawn for this project (24px grid, 1.75 stroke). --}}
<svg {{ $attributes->merge(['class' => 'size-5 shrink-0', 'aria-hidden' => 'true']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    @switch($name)
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break
        @case('close')
            <path d="M6 6l12 12M18 6 6 18" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="6.5" />
            <path d="m20 20-4.2-4.2" />
            @break
        @case('bag')
            <path d="M5 8h14l-1.2 12H6.2z" />
            <path d="M9 8V6.5a3 3 0 0 1 6 0V8" />
            @break
        @case('heart')
            <path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7a4.3 4.3 0 0 1 7.5 2.8C19.5 15.4 12 20 12 20z" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="3.5" />
            <path d="M5 20a7 7 0 0 1 14 0" />
            @break
        @case('globe')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M3.5 12h17M12 3.5c2.4 2.5 3.5 5.3 3.5 8.5s-1.1 6-3.5 8.5c-2.4-2.5-3.5-5.3-3.5-8.5s1.1-6 3.5-8.5z" />
            @break
        @case('phone')
            <path d="M6.5 4h2.8l1.4 3.7-1.9 1.4a10.5 10.5 0 0 0 6.1 6.1l1.4-1.9 3.7 1.4v2.8a1.5 1.5 0 0 1-1.6 1.5A14.5 14.5 0 0 1 5 5.6 1.5 1.5 0 0 1 6.5 4z" />
            @break
        @case('mail')
            <rect x="3.5" y="5.5" width="17" height="13" rx="2" />
            <path d="m4 7.5 8 5.5 8-5.5" />
            @break
        @case('truck')
            <path d="M3 6.5h11v9.5H3zM14 9.5h3.8l3.2 3.3V16H14" />
            <circle cx="7" cy="17.5" r="1.75" />
            <circle cx="17.5" cy="17.5" r="1.75" />
            @break
        @case('cash')
            <rect x="3" y="6.5" width="18" height="11" rx="2" />
            <circle cx="12" cy="12" r="2.5" />
            <path d="M6.5 9.5v.01M17.5 14.5v.01" />
            @break
        @case('whatsapp')
            <path d="M4 20l1.2-3.6A8.5 8.5 0 1 1 8.3 19z" />
            <path d="M9.3 8.4c.3-.4.7-.5 1-.4l.9 1.9-.7 1a6 6 0 0 0 2.6 2.6l1-.7 1.9.9c.1.3 0 .7-.4 1-1 .7-2.6.4-4.2-1s-2.6-3.3-2.1-5.3z" />
            @break
        @case('instagram')
            <rect x="3.5" y="3.5" width="17" height="17" rx="5" />
            <circle cx="12" cy="12" r="4" />
            <circle cx="17.25" cy="6.75" r="0.9" fill="currentColor" stroke="none" />
            @break
        @case('tiktok')
            <path d="M14.5 3.5v11a3.75 3.75 0 1 1-3.75-3.75" />
            <path d="M14.5 3.5a4.75 4.75 0 0 0 4.75 4.75" />
            @break
        @case('facebook')
            <path d="M14 20.5v-7h2.6l.4-3H14V8.8c0-.9.3-1.5 1.6-1.5H17V4.6a19 19 0 0 0-2.3-.1c-2.3 0-3.9 1.4-3.9 4v2H8.5v3h2.3v7" />
            @break
        @case('snapchat')
            <path d="M12 3.5c-2.9 0-4.8 2.1-4.8 4.8v2l-1.5.5c-.5.2-.5.8 0 1l1.3.6c-.6 1.6-1.8 2.8-3.3 3.4.3.8 1.3 1.1 2.4 1.3l.4 1.3c1-.2 2-.1 3 .4.8.4 1.5.9 2.5.9s1.7-.5 2.5-.9c1-.5 2-.6 3-.4l.4-1.3c1.1-.2 2.1-.5 2.4-1.3-1.5-.6-2.7-1.8-3.3-3.4l1.3-.6c.5-.2.5-.8 0-1l-1.5-.5v-2c0-2.7-1.9-4.8-4.8-4.8z" />
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6" />
            @break
        @case('chevron-end')
            <path class="rtl:hidden" d="m9 6 6 6-6 6" />
            <path class="ltr:hidden" d="m15 6-6 6 6 6" />
            @break
        @case('shield')
            <path d="M12 3.5 5 6v5.5c0 4.2 3 7.6 7 9 4-1.4 7-4.8 7-9V6z" />
            <path d="m9 12 2 2 4-4" />
            @break
        @case('grid')
            <rect x="4" y="4" width="6.5" height="6.5" rx="1.5" />
            <rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5" />
            <rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5" />
            <rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5" />
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3" />
            <path d="M12 3v2.5M12 18.5V21M3 12h2.5M18.5 12H21M5.6 5.6l1.8 1.8M16.6 16.6l1.8 1.8M5.6 18.4l1.8-1.8M16.6 7.4l1.8-1.8" />
            @break
        @case('users')
            <circle cx="9" cy="8" r="3" />
            <path d="M3.5 19a5.5 5.5 0 0 1 11 0" />
            <path d="M16 5.5a3 3 0 0 1 0 5.5M17.5 14a5 5 0 0 1 3 5" />
            @break
        @case('logout')
            <path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4" />
            <path d="M10 16l-4-4 4-4M6 12h10" />
            @break
        @case('external')
            <path d="M14 4h6v6M20 4l-9 9M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4" />
            @break
        @case('check')
            <path d="m5 12.5 4.5 4.5L19 7.5" />
            @break
        @case('circle')
            <circle cx="12" cy="12" r="8" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('pencil')
            <path d="M4 20h4L19 9l-4-4L4 16z" />
            <path d="m13.5 6.5 4 4" />
            @break
        @case('trash')
            <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" />
            @break
        @case('image')
            <rect x="3.5" y="4.5" width="17" height="15" rx="2" />
            <circle cx="9" cy="10" r="1.75" />
            <path d="m4 17.5 4.5-4.5 3.5 3.5 2.5-2.5 5.5 5" />
            @break
        @case('alert')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 7.5v5.5M12 16.5v.01" />
            @break
        @case('tag')
            <path d="M3.5 12.2V4.5a1 1 0 0 1 1-1h7.7l8.3 8.3a1.5 1.5 0 0 1 0 2.1l-6.4 6.4a1.5 1.5 0 0 1-2.1 0z" />
            <circle cx="8" cy="8" r="1.5" />
            @break
        @case('layers')
            <path d="m12 3.5 8.5 4.5-8.5 4.5L3.5 8z" />
            <path d="m3.5 12 8.5 4.5 8.5-4.5M3.5 16l8.5 4.5 8.5-4.5" />
            @break
        @case('palette')
            <path d="M12 3.5a8.5 8.5 0 0 0 0 17c1.1 0 1.8-.8 1.8-1.7 0-.5-.2-.9-.5-1.2-.3-.3-.5-.7-.5-1.2 0-.9.8-1.7 1.7-1.7h2a4 4 0 0 0 4-4c0-4.1-3.8-7.2-8.5-7.2z" />
            <circle cx="7.5" cy="11.5" r="1" fill="currentColor" stroke="none" />
            <circle cx="10" cy="7.5" r="1" fill="currentColor" stroke="none" />
            <circle cx="14.5" cy="7.5" r="1" fill="currentColor" stroke="none" />
            @break
        @case('ruler')
            <path d="M4 15.5 15.5 4l4.5 4.5L8.5 20z" />
            <path d="m8 11.5 2 2M10.5 9l1.5 1.5M13 6.5l2 2" />
            @break
        @case('table')
            <rect x="3.5" y="4.5" width="17" height="15" rx="2" />
            <path d="M3.5 9.5h17M3.5 14.5h17M9.5 9.5v10" />
            @break
    @endswitch
</svg>
