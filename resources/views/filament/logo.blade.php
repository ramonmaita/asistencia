{{-- Logotipo: insignia de la UPT Estado Bolívar más el nombre del sistema. --}}
<div class="gpnf-logo">
    <span class="gpnf-logo-insignia">
        <img src="{{ asset('images/logo-upt.png') }}" alt="UPT Estado Bolívar">
    </span>
    <span class="gpnf-logo-texto">
        <span class="gpnf-logo-nombre">Gestión PNF</span>
        @if (filled($subtitulo ?? null))
            <span class="gpnf-logo-subtitulo">{{ $subtitulo }}</span>
        @endif
    </span>
</div>
