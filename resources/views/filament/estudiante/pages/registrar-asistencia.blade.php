<x-filament-panels::page>
    <section class="fi-section mx-auto w-full max-w-lg bg-white p-6 dark:bg-gray-900">
        @include('filament.lector-qr', [
            'metodo' => 'registrar',
            'conUbicacion' => true,
            'indicacion' => 'Apunta la cámara al QR que proyecta el docente. Te pediremos permiso para usar la cámara y tu ubicación, porque debes estar en la sede de la clase.',
        ])
    </section>

    <p class="mx-auto max-w-lg text-center text-sm text-gray-600 dark:text-gray-400">
        El código cambia cada pocos segundos y solo funciona durante la clase. Si no puedes leerlo, muéstrale al docente tu
        <x-filament::link :href="\App\Filament\Estudiante\Pages\MiCarnet::getUrl()">carnet virtual</x-filament::link>.
    </p>
</x-filament-panels::page>
