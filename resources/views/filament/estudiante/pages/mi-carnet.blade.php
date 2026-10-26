<x-filament-panels::page>
    @php($estudiante = $this->estudiante())
    <div class="mx-auto w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="bg-(--gpnf-marino) bg-[linear-gradient(100deg,var(--gpnf-transicion-2)_0%,var(--gpnf-transicion-5)_65%,var(--gpnf-transicion-7)_100%)] px-5 py-4 text-white">
            <div class="text-sm font-semibold">{{ $estudiante->pnf->nombre }}</div>
            <div class="text-xs opacity-85">Carnet de estudiante{{ $this->periodo() ? ' · Periodo '.$this->periodo() : '' }}</div>
        </div>

        <div class="flex flex-col items-center gap-4 p-6">
            <img src="{{ $this->qr() }}" alt="QR de mi carnet" class="aspect-square w-full max-w-64 rounded-lg bg-white p-2">

            <div class="text-center">
                <div class="text-xl font-bold text-gray-950 dark:text-white">{{ $estudiante->nombres }} {{ $estudiante->apellidos }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">C.I. {{ number_format((int) $estudiante->cedula, 0, ',', '.') }}</div>
                @if ($this->seccion())
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $this->seccion() }}</div>
                @endif
            </div>
        </div>
    </div>

    <p class="mx-auto max-w-sm text-center text-sm text-gray-600 dark:text-gray-400">
        Muéstrale este código al docente para que registre tu asistencia. Sube el brillo de la pantalla si no lo lee.
        Si alguien más usa tu carnet, pide en la coordinación que lo regeneren.
    </p>
</x-filament-panels::page>
