{{-- Logotipo provisional: se reemplaza por el logo oficial de la institución. --}}
<div class="flex items-center gap-2.5">
    <svg viewBox="0 0 32 32" class="h-9 w-9 shrink-0" aria-hidden="true">
        <path d="M16 2 4 7v8.5C4 23 9.2 28.6 16 30c6.8-1.4 12-7 12-14.5V7L16 2Z" fill="var(--gpnf-marino-claro, #2c5288)" stroke="var(--gpnf-ambar, #f2a900)" stroke-width="1.6"/>
        <path d="M9.5 11.5 16 9l6.5 2.5L16 14l-6.5-2.5Z" fill="var(--gpnf-ambar, #f2a900)"/>
        <path d="M11.5 13.3v4.2c0 1.6 2 2.8 4.5 2.8s4.5-1.2 4.5-2.8v-4.2L16 15.1l-4.5-1.8Z" fill="#fff"/>
        <path d="M22.5 11.5v5" stroke="var(--gpnf-ambar, #f2a900)" stroke-width="1.2" stroke-linecap="round"/>
    </svg>
    <div class="leading-tight">
        <div class="text-[1.05rem] font-bold tracking-tight">Gestión PNF</div>
        @if (filled($subtitulo ?? null))
            <div class="text-[0.7rem] font-medium tracking-wide uppercase opacity-70">{{ $subtitulo }}</div>
        @endif
    </div>
</div>
