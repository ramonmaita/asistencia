<?php

namespace App\Filament\Estudiante\Resources\Concerns;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Los recursos del panel del estudiante son de solo lectura y su consulta ya
 * está limitada a sus propios datos (lo ajeno da 404). Las políticas de los
 * modelos son para el personal (User), así que aquí no se usan.
 */
trait SoloLectura
{
    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        return in_array($action, ['viewAny', 'view'], true) ? Response::allow() : Response::deny();
    }
}
