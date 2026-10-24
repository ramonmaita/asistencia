<?php

namespace App\Notifications;

use App\Enums\NivelInasistencia;
use App\Filament\App\Resources\Cursos\CursoResource;
use App\Filament\Estudiante\Resources\Cursos\CursoResource as CursoDelEstudianteResource;
use App\Models\Curso;
use App\Models\Estudiante;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de que un estudiante llegó a "en riesgo" o superó el umbral de
 * inasistencia en un curso. Lo reciben el estudiante, los docentes del
 * curso y los coordinadores del PNF, con un texto distinto para cada uno.
 */
class NivelInasistenciaAlcanzado extends Notification
{
    use Queueable;

    public function __construct(
        public Curso $curso,
        public Estudiante $estudiante,
        public NivelInasistencia $nivel,
        public float $porcentaje,
    ) {}

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
        $situacion = $this->nivel === NivelInasistencia::Superado
            ? 'superó el límite de inasistencias'
            : 'está en riesgo por inasistencias';

        return $notifiable instanceof Estudiante
            ? ($this->nivel === NivelInasistencia::Superado ? 'Superaste el límite de inasistencias' : 'Estás en riesgo por inasistencias')
            : "{$this->estudiante->getFilamentName()} {$situacion}";
    }

    private function mensaje(object $notifiable): string
    {
        $porcentaje = number_format($this->porcentaje, 2, ',', '.').' %';
        $umbral = number_format((float) $this->curso->pnf->umbral_inasistencia_clases, 2, ',', '.').' %';
        $curso = "{$this->curso->unidadCurricular->nombre} (sección {$this->curso->seccion->codigo})";

        return $notifiable instanceof Estudiante
            ? "Tienes {$porcentaje} de inasistencias en {$curso}. El límite del PNF es {$umbral}."
            : "{$this->estudiante->getFilamentName()} (C.I. {$this->estudiante->cedula}) tiene {$porcentaje} de inasistencias en {$curso}. El límite del PNF es {$umbral}.";
    }

    private function url(object $notifiable): string
    {
        return $notifiable instanceof Estudiante
            ? CursoDelEstudianteResource::getUrl('view', ['record' => $this->curso], panel: 'estudiante')
            : CursoResource::getUrl('view', ['record' => $this->curso], panel: 'app', tenant: $this->curso->pnf);
    }
}
