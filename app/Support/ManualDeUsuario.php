<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Manual de usuario por rol. Cada guía es un archivo Markdown en
 * resources/ayuda; sus títulos "##" son las secciones que muestran las
 * páginas de ayuda y el PDF descargable.
 */
class ManualDeUsuario
{
    /**
     * @var array<string, string>
     */
    public const GUIAS = [
        'administrador' => 'Administrador del sistema',
        'coordinador' => 'Coordinador',
        'docente' => 'Docente',
        'administrativo' => 'Administrativo',
        'estudiante' => 'Estudiante',
    ];

    /**
     * @param  list<string>  $guias
     * @return array<string, array{titulo: string, secciones: list<array{id: string, titulo: string, html: string, texto: string}>}>
     */
    public function guias(array $guias): array
    {
        return collect($guias)
            ->mapWithKeys(fn (string $guia): array => [$guia => [
                'titulo' => self::GUIAS[$guia],
                'secciones' => $this->secciones($guia),
            ]])
            ->all();
    }

    /**
     * @return list<array{id: string, titulo: string, html: string, texto: string}>
     */
    public function secciones(string $guia): array
    {
        $markdown = File::get(resource_path("ayuda/{$guia}.md"));

        return collect(preg_split('/^## /m', $markdown, -1, PREG_SPLIT_NO_EMPTY))
            ->map(function (string $bloque) use ($guia): array {
                [$titulo, $cuerpo] = explode("\n", $bloque, 2) + [1 => ''];
                $html = Str::markdown($cuerpo, ['html_input' => 'escape', 'allow_unsafe_links' => false]);

                return [
                    'id' => $guia.'-'.Str::slug($titulo),
                    'titulo' => trim($titulo),
                    'html' => $html,
                    'texto' => Str::lower(trim($titulo).' '.strip_tags($html)),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $guias
     */
    public function pdf(array $guias): string
    {
        return Pdf::loadView('pdf.manual', ['guias' => $this->guias($guias)])
            ->setPaper('a4')
            ->setOption('isFontSubsettingEnabled', true)
            ->output();
    }
}
