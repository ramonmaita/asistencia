<?php

namespace App\Notifications;

use App\Enums\NivelInasistencia;
use App\Filament\App\Resources\Cursos\CursoResource;
use App\Filament\App\Resources\Proyectos\ProyectoResource;
use App\Filament\Estudiante\Resources\Cursos\CursoResource as CursoDelEstudianteResource;
use App\Filament\Estudiante\Resources\Proyectos\ProyectoResource as ProyectoDelEstudianteResource;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Proyecto;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de que un estudiante llegó a "en riesgo" o superó el umbral de
 * inasistencia en un curso: a sus clases o, si se indica el proyecto, a sus
 * tutorías. Lo reciben el estudiante, los docentes del curso y los
 * coordinadores del PNF, con un texto distinto para cada uno.
 */
class NivelInasistenciaAlcanzado extends Notification
{
    use Queueable;

    public function __construct(
        public Curso $curso,
        public Estudiante $estudiante,
        public NivelInasistencia $nivel,
        public float $porcentaje,
        public ?Proyecto $proyecto = null,
    ) {}

    public function esDeTutorias(): bool
    {
        return $this->proyecto !== null;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->titulo($notifiable))
            ->greeting('Hola, '.($notifiable instanceof Estudiante ? $notifiable->nombres : $notifiable->name).'.')
            ->line($this->mensaje($notifiable))
            ->when(
                $notifiable instanceof Estudiante,
                fn (MailMessage $correo) => $correo->line($this->nivel === NivelInasistencia::Superado
                    ? 'Comunícate con tu docente o con la coordinación para conocer tu situación.'
                    : 'Si tienes inasistencias justificables, puedes solicitar su justificación desde el panel del estudiante.'),
            )
            ->action('Ver asistencias', $this->url($notifiable))
            ->salutation("Coordinación del {$this->curso->pnf->nombre}");
    }

    /**
     * Formato de las notificaciones de base de datos de Filament.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->titulo($notifiable))
            ->body($this->mensaje($notifiable))
            ->icon(Heroicon::OutlinedExclamationTriangle)
            ->color($this->nivel->getColor())
            ->actions([
                Action::make('ver')
                    ->label('Ver asistencias')
                    ->url($this->url($notifiable)),
            ])
            ->getDatabaseMessage();
    }

    private function titulo(object $notifiable): string
    {
        $a = $this->esDeTutorias() ? ' a tutorías' : '';
        $situacion = $this->nivel === NivelInasistencia::Superado
            ? "superó el límite de inasistencias{$a}"
            : "está en riesgo por inasistencias{$a}";

        return $notifiable instanceof Estudiante
            ? ($this->nivel === NivelInasistencia::Superado ? "Superaste el límite de inasistencias{$a}" : "Estás en riesgo por inasistencias{$a}")
            : "{$this->estudiante->getFilamentName()} {$situacion}";
    }

    private function mensaje(object $notifiable): string
    {
        $porcentaje = number_format($this->porcentaje, 2, ',', '.').' %';
        $umbral = number_format((float) ($this->esDeTutorias()
            ? $this->curso->seccion->trayecto->umbral_inasistencia_tutorias
            : $this->curso->pnf->umbral_inasistencia_clases), 2, ',', '.').' %';
        $donde = $this->esDeTutorias()
            ? "las tutorías del proyecto «{$this->proyecto->titulo}» ({$this->curso->unidadCurricular->nombre}, sección {$this->curso->seccion->codigo})"
            : "{$this->curso->unidadCurricular->nombre} (sección {$this->curso->seccion->codigo})";
        $limite = $this->esDeTutorias() ? 'El límite del trayecto' : 'El límite del PNF';

        return $notifiable instanceof Estudiante
            ? "Tienes {$porcentaje} de inasistencias en {$donde}. {$limite} es {$umbral}."
            : "{$this->estudiante->getFilamentName()} (C.I. {$this->estudiante->cedula}) tiene {$porcentaje} de inasistencias en {$donde}. {$limite} es {$umbral}.";
    }

    private function url(object $notifiable): string
    {
        if ($this->esDeTutorias()) {
            return $notifiable instanceof Estudiante
                ? ProyectoDelEstudianteResource::getUrl('view', ['record' => $this->proyecto], panel: 'estudiante')
                : ProyectoResource::getUrl('view', ['record' => $this->proyecto], panel: 'app', tenant: $this->curso->pnf);
        }

        return $notifiable instanceof Estudiante
            ? CursoDelEstudianteResource::getUrl('view', ['record' => $this->curso], panel: 'estudiante')
            : CursoResource::getUrl('view', ['record' => $this->curso], panel: 'app', tenant: $this->curso->pnf);
    }
}
