<x-filament-panels::page>
    <div wire:poll.5s class="grid gap-6 lg:grid-cols-3">
        <section x-data="{ modo: 'qr' }" class="fi-section flex flex-col items-center gap-4 bg-white p-6 lg:col-span-2 dark:bg-gray-900">
            @if ($this->estaAbierta())
                <x-filament::tabs>
                    <x-filament::tabs.item alpine-active="modo === 'qr'" x-on:click="modo = 'qr'" icon="heroicon-o-qr-code">
                        Mostrar QR dinámico
                    </x-filament::tabs.item>
                    <x-filament::tabs.item alpine-active="modo === 'carnets'" x-on:click="modo = 'carnets'" icon="heroicon-o-camera">
                        Escanear carnets
                    </x-filament::tabs.item>
                </x-filament::tabs>

                <div x-show="modo === 'qr'" class="flex w-full flex-col items-center gap-4">
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
                        Los estudiantes lo escanean desde la app <strong>Gestión PNF</strong> o desde su panel web, en <em>Registrar asistencia</em>.
                    </p>
                </div>

                <div x-show="modo === 'carnets'" x-cloak class="w-full">
                    @include('filament.lector-qr', [
                        'metodo' => 'registrarCarnet',
                        'continuo' => true,
                        'indicacion' => 'Apunta la cámara al QR del carnet impreso o del carnet virtual que el estudiante muestra en su teléfono. Puedes leer varios seguidos.',
                    ])
                </div>
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
