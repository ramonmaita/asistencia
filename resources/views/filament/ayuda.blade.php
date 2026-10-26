<x-filament-panels::page>
    @php($manual = $this->manual())
    <div x-data="{ guia: @js(array_key_first($manual)), busqueda: '' }" class="flex flex-col gap-6">
        <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
            <x-filament::input type="search" x-model.debounce.200ms="busqueda" placeholder="Buscar en la ayuda (por ejemplo: carnet, contraseña, QR)…" />
        </x-filament::input.wrapper>

        @if (count($manual) > 1)
            <x-filament::tabs x-show="! busqueda">
                @foreach ($manual as $clave => $guia)
                    <x-filament::tabs.item :alpine-active="'guia === '.\Illuminate\Support\Js::from($clave)" x-on:click="guia = {{ \Illuminate\Support\Js::from($clave) }}">
                        {{ $guia['titulo'] }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        @endif

        @foreach ($manual as $clave => $guia)
            <div class="flex flex-col gap-3" x-show="busqueda || guia === @js($clave)">
                @if (count($manual) > 1)
                    <h2 x-show="busqueda" class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $guia['titulo'] }}</h2>
                @endif

                @foreach ($guia['secciones'] as $seccion)
                    <details
                        id="{{ $seccion['id'] }}"
                        x-show="! busqueda || @js($seccion['texto']).includes(busqueda.toLowerCase())"
                        x-effect="if (busqueda) $el.open = true"
                        @if ($loop->first) open @endif
                        class="fi-section group bg-white dark:bg-gray-900"
                    >
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-6 py-4 text-base font-semibold text-gray-950 dark:text-white">
                            {{ $seccion['titulo'] }}
                            <x-filament::icon icon="heroicon-m-chevron-down" class="h-5 w-5 text-gray-400 transition group-open:rotate-180" />
                        </summary>
                        <div class="fi-prose border-t border-gray-100 px-6 py-4 dark:border-white/10">
                            {!! $seccion['html'] !!}
                        </div>
                    </details>
                @endforeach
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
