@props(['step'])
<span class="process-icon" aria-hidden="true">
    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" focusable="false">
        @switch(strtolower(trim($step)))
            @case('discover')
                <circle cx="10.5" cy="10.5" r="5.5"/><path d="m15 15 5 5"/>
                @break
            @case('define')
                <rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M9 9h6m-6 4h6m-6 4h4"/>
                @break
            @case('design')
                <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 8h18M9 8v12m3-8h5m-5 4h3"/>
                @break
            @case('build')
                <path d="m8 7-5 5 5 5m8-10 5 5-5 5m-2-13-4 16"/>
                @break
            @case('validate')
                <path d="M12 22s7-3.5 7-10V5l-7-3-7 3v7c0 6.5 7 10 7 10Z"/><path d="m9 12 2 2 4-4"/>
                @break
            @case('improve')
                <path d="M4 19h16M6 15l5-5 3 3 5-7m-5 0h5v5"/>
                @break
            @default
                <circle cx="12" cy="12" r="8"/><path d="M8 12h8m-3-3 3 3-3 3"/>
        @endswitch
    </svg>
</span>
