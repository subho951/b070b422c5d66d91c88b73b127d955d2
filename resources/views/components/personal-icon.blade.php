@props(['name'])
<svg {{ $attributes->class('personal-icon') }} viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('web')
            <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 8h18m-12 4-2 2 2 2m6-4 2 2-2 2m-2-5-2 6"/>
            @break
        @case('python')
            <path d="M12 3H8a2 2 0 0 0-2 2v3h6m0-5h2a3 3 0 0 1 3 3v4a2 2 0 0 1-2 2H9a2 2 0 0 0-2 2v4a3 3 0 0 0 3 3h6a2 2 0 0 0 2-2v-3h-6m-6-8H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m11 0h1a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2M9 5h.01M15 19h.01"/>
            @break
        @case('database')
            <ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.66 3.58 3 8 3s8-1.34 8-3V5M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3"/>
            @break
        @case('ai')
            <path d="M9 4 5 6.5v5L9 14l4-2.5v-5L9 4Zm4 7.5 4-2.5 4 2.5v5L17 19l-4-2.5v-5ZM9 14v5l-4 2.5-4-2.5v-5l4-2.5"/>
            @break
        @case('cloud')
            <path d="M7 18a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 17 8.5h.6a4.75 4.75 0 0 1 0 9.5H7Z"/>
            @break
        @case('products')
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            @break
        @case('globe')
            <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18m0-18a15 15 0 0 0 0 18"/>
            @break
    @endswitch
</svg>
