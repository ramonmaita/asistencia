{{--
    Lector de QR con la cámara (resources/js/lector-qr.js).
    Parámetros: $metodo (método Livewire que recibe lo leído), $conUbicacion,
    $continuo (sigue leyendo tras cada código) y $indicacion.
--}}
<div
    wire:ignore
    x-data="lectorQr({ metodo: @js($metodo), conUbicacion: @js($conUbicacion ?? false), continuo: @js($continuo ?? false) })"
    class="flex w-full flex-col items-center gap-4"
>
    <div class="relative aspect-square w-full max-w-sm overflow-hidden rounded-xl bg-gray-950" x-show="activo" x-cloak>
        <video x-ref="video" class="h-full w-full object-cover" muted playsinline></video>
        <div class="pointer-events-none absolute inset-8 rounded-lg border-4 border-white/70"></div>
        <div x-show="procesando" class="absolute inset-x-0 bottom-0 bg-black/60 py-2 text-center text-sm text-white">Verificando…</div>
    </div>
    <canvas x-ref="lienzo" class="hidden"></canvas>

    <p x-show="! activo" class="max-w-sm text-center text-sm text-gray-600 dark:text-gray-400">{{ $indicacion }}</p>

    <div class="flex gap-3">
        <x-filament::button x-show="! activo" x-on:click="iniciar()" icon="heroicon-o-camera">Abrir cámara</x-filament::button>
        <x-filament::button x-show="activo" x-cloak x-on:click="detener()" color="gray" icon="heroicon-o-stop">Detener</x-filament::button>
    </div>

    <p x-show="error" x-cloak x-text="error" class="max-w-sm text-center text-sm font-medium text-danger-600 dark:text-danger-400"></p>

    @if ($conUbicacion ?? false)
        <p x-show="activo && ! ubicacion" x-cloak class="max-w-sm text-center text-xs text-gray-500 dark:text-gray-400">
            Esperando tu ubicación… si el navegador la pide, permítela.
        </p>
    @endif

    @if ($continuo ?? false)
        <ul x-show="lecturas.length" x-cloak class="w-full max-w-sm divide-y divide-gray-100 text-sm dark:divide-white/5">
            <template x-for="lectura in lecturas" :key="lectura.id">
                <li class="flex items-center gap-2 py-2">
                    <span x-text="lectura.exito ? '✓' : '✕'" :class="lectura.exito ? 'text-success-600' : 'text-danger-600'" class="font-bold"></span>
                    <span x-text="lectura.mensaje" class="text-gray-800 dark:text-gray-200"></span>
                </li>
            </template>
        </ul>
    @else
        <div
            x-show="resultado"
            x-cloak
            :class="resultado?.exito ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400'"
            class="w-full max-w-sm rounded-xl px-4 py-3 text-center font-medium"
            x-text="resultado?.mensaje"
        ></div>
    @endif
</div>
