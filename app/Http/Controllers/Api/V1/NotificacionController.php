<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Notificaciones de la base de datos (las mismas que la campana del panel).
 */
class NotificacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notificaciones = $request->user()->notifications()->limit(50)->get();

        return response()->json([
            'data' => $notificaciones->map(fn (DatabaseNotification $notificacion): array => [
                'id' => $notificacion->id,
                'titulo' => $notificacion->data['title'] ?? null,
                'cuerpo' => $notificacion->data['body'] ?? null,
                'leida' => $notificacion->read_at !== null,
                'fecha' => $notificacion->created_at->toIso8601String(),
            ]),
            'sin_leer' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function leer(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'Notificaciones marcadas como leídas.']);
    }
}
