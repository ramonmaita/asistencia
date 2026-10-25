{{-- Historial de veredictos de un integrante, del más reciente al más antiguo. --}}
<ol class="relative space-y-5 border-s border-gray-200 ps-5 dark:border-white/10">
    @forelse ($historial as $cambio)
        <li class="relative">
            <span class="absolute -start-[1.6rem] top-1 size-2.5 rounded-full {{ $cambio->veredicto === \App\Enums\VeredictoProyecto::Aprobado ? 'bg-success-500' : 'bg-danger-500' }}"></span>
            <div class="text-sm font-semibold text-gray-950 dark:text-white">
                @if ($cambio->veredicto_anterior)
                    {{ $cambio->veredicto_anterior->getLabel() }} → {{ $cambio->veredicto->getLabel() }}
                @else
                    {{ $cambio->veredicto->getLabel() }}
                @endif
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ $cambio->created_at->format('d/m/Y H:i') }} · {{ $cambio->autor?->name ?? 'Usuario eliminado' }}
            </div>
            @if (filled($cambio->observacion))
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $cambio->observacion }}</p>
            @endif
        </li>
    @empty
        <li class="text-sm text-gray-500">Aún no hay veredictos registrados.</li>
    @endforelse
</ol>
