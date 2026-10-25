<?php

use App\Http\Controllers\Api\V1\AutenticacionController;
use App\Http\Controllers\Api\V1\Docente\CursoController as CursoDelDocenteController;
use App\Http\Controllers\Api\V1\Docente\PnfController;
use App\Http\Controllers\Api\V1\Docente\SesionController;
use App\Http\Controllers\Api\V1\Estudiante\AsistenciaQrController;
use App\Http\Controllers\Api\V1\Estudiante\CarnetController;
use App\Http\Controllers\Api\V1\Estudiante\ContrasenaController;
use App\Http\Controllers\Api\V1\Estudiante\CursoController as CursoDelEstudianteController;
use App\Http\Controllers\Api\V1\Estudiante\JustificacionController;
use App\Http\Controllers\Api\V1\Estudiante\ProyectoController;
use App\Http\Controllers\Api\V1\NotificacionController;
use App\Http\Middleware\Api\EstablecerPnfDelDocente;
use App\Http\Middleware\Api\SoloDocentes;
use App\Http\Middleware\Api\SoloEstudiantes;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API para la app móvil (Flutter)
|--------------------------------------------------------------------------
|
| Autenticación con tokens de Sanctum. Los docentes entran con correo y los
| estudiantes con cédula; cada token lleva la habilidad "docente" o
| "estudiante". Las rutas del docente llevan el PNF en la URL (puede
| trabajar en varios); las del estudiante usan siempre su propio PNF.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('throttle:login-api')->group(function () {
        Route::post('docente/login', [AutenticacionController::class, 'docente'])->name('docente.login');
        Route::post('estudiante/login', [AutenticacionController::class, 'estudiante'])->name('estudiante.login');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('yo', [AutenticacionController::class, 'yo'])->name('yo');
        Route::post('logout', [AutenticacionController::class, 'logout'])->name('logout');
        Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
        Route::post('notificaciones/leer', [NotificacionController::class, 'leer'])->name('notificaciones.leer');

        Route::middleware(SoloDocentes::class)->prefix('docente')->name('docente.')->group(function () {
            Route::get('pnfs', [PnfController::class, 'index'])->name('pnfs.index');

            Route::middleware(EstablecerPnfDelDocente::class)->prefix('pnfs/{pnf:slug}')->group(function () {
                Route::get('cursos', [CursoDelDocenteController::class, 'index'])->name('cursos.index');
                Route::get('cursos/{curso}/sesiones', [CursoDelDocenteController::class, 'sesiones'])->name('cursos.sesiones');

                Route::get('sesiones/hoy', [SesionController::class, 'hoy'])->name('sesiones.hoy');

                Route::prefix('sesiones/{tipo}/{sesion}')->whereIn('tipo', ['clase', 'tutoria'])->whereNumber('sesion')->name('sesiones.')->group(function () {
                    Route::get('/', [SesionController::class, 'show'])->name('show');
                    Route::get('qr-dinamico', [SesionController::class, 'qrDinamico'])->name('qr');
                    Route::post('escanear', [SesionController::class, 'escanear'])->name('escanear');
                    Route::post('asistencias', [SesionController::class, 'registrar'])->name('registrar');
                    Route::post('cerrar', [SesionController::class, 'cerrar'])->name('cerrar');
                });
            });
        });

        Route::middleware(SoloEstudiantes::class)->prefix('estudiante')->name('estudiante.')->group(function () {
            Route::put('contrasena', [ContrasenaController::class, 'update'])->name('contrasena');
            Route::get('carnet', [CarnetController::class, 'show'])->name('carnet');
            Route::get('cursos', [CursoDelEstudianteController::class, 'index'])->name('cursos.index');
            Route::get('cursos/{curso}/asistencias', [CursoDelEstudianteController::class, 'asistencias'])->name('cursos.asistencias');
            Route::get('proyectos', [ProyectoController::class, 'index'])->name('proyectos.index');
            Route::get('proyectos/{proyecto}/tutorias', [ProyectoController::class, 'tutorias'])->name('proyectos.tutorias');
            Route::get('justificaciones', [JustificacionController::class, 'index'])->name('justificaciones.index');
            Route::post('asistencias/{asistencia}/justificaciones', [JustificacionController::class, 'store'])->name('justificaciones.store');
            Route::post('asistencias/qr', [AsistenciaQrController::class, 'store'])->name('asistencias.qr');
        });
    });
});
