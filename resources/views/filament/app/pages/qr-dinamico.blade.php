<x-filament-panels::page>
    <div wire:poll.5s class="grid gap-6 lg:grid-cols-3">
        <section class="fi-section flex flex-col items-center gap-4 bg-white p-6 lg:col-span-2 dark:bg-gray-900">
            @if ($this->estaAbierta())
                @php($qr = $this->qr())
                <img src="{{ $qr['imagen'] }}" alt="Código QR de asistencia" class="aspect-square w-full max-w-md">
                <p
                    class="text-sm text-gray-500 dark:text-gray-400"
                    x-data="{ restante: 0, actualizar() { this.restante = Math.max(0, Math.ceil((new Date('{{ $qr['expira_at'] }}') - new Date()) / 1000)) } }"
                    x-init="actualizar(); setInterval(() => actualizar(), 1000)"
                >
                    El código cambia cada {{ $qr['segundos'] }} segundos · siguiente en <span x-text="restante"></span> s
                </p>
                <p class="text-center text-base text-gray-700 dark:text-gray-300">
                    Escanéalo desde la app <strong>Gestión PNF</strong>, en <em>Registrar asistencia</em>.
                </p>
            @else
                <div class="py-16 text-center">
                    <p class="text-lg font-semibold text-gray-950 dark:text-white">La toma por QR no está abierta</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Se abre a las {{ $this->abre() }} y se cierra al terminar la sesión. Fuera de ese horario usa el registro manual.
                    </p>
                </div>
            @endif
        </section>

        <section class="fi-section bg-white p-6 dark:bg-gray-900">
            @php($registrados = $this->registrados())
            <div class="text-sm text-gray-500 dark:text-gray-400">Registrados</div>
            <div class="text-4xl font-bold text-gray-950 dark:text-white">
                {{ $registrados->count() }} <span class="text-xl font-medium text-gray-400">/ {{ $this->convocados() }}</span>
            </div>

            <ul class="mt-4 divide-y divide-gray-100 dark:divide-white/5">
                @forelse ($registrados->take(12) as $asistencia)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <span class="truncate text-gray-800 dark:text-gray-200">{{ $asistencia->estudiante->nombre_completo }}</span>
                        <x-filament::badge :color="$asistencia->estado->getColor()">{{ $asistencia->estado->getLabel() }}</x-filament::badge>
                    </li>
                @empty
                    <li class="py-2 text-sm text-gray-500 dark:text-gray-400">Aún nadie se ha registrado.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-filament-panels::page>
