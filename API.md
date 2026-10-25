# API de Gestión PNF · v1

Referencia de la API que consume la app móvil (Flutter) de docentes y estudiantes. Todas las rutas empiezan por `/api/v1`. Los ejemplos de respuesta salen de una instalación con los datos de prueba (`php artisan migrate:fresh --seed`).

## Índice

1. [Convenciones](#1-convenciones)
2. [Autenticación](#2-autenticación)
3. [Rutas comunes](#3-rutas-comunes)
4. [Rutas del docente](#4-rutas-del-docente)
5. [Rutas del estudiante](#5-rutas-del-estudiante)
6. [Flujos de registro por QR](#6-flujos-de-registro-por-qr)
7. [Errores](#7-errores)
8. [Configuración del servidor](#8-configuración-del-servidor)

---

## 1. Convenciones

| Aspecto | Regla |
| --- | --- |
| Cabeceras | `Accept: application/json` en todas las peticiones; `Authorization: Bearer <token>` en las autenticadas. |
| Cuerpo | JSON (`Content-Type: application/json`), salvo la solicitud de justificación, que es `multipart/form-data` porque lleva un archivo. |
| Fechas | `fecha` en formato `AAAA-MM-DD`; fechas con hora en ISO 8601 con la zona del servidor (`America/Caracas`, `-04:00`). Las horas de las sesiones van como `HH:MM` (24 h). |
| Enumerados | Siempre como objeto `{"valor": "...", "etiqueta": "..."}`. La app compara con `valor` y muestra `etiqueta` (ya viene en español). |
| Listas | Envueltas en `{"data": [...]}`. Los recursos sueltos también van en `data`, excepto las respuestas de inicio de sesión, del QR dinámico, del escaneo y del carnet, que van sin envoltorio (ver cada ruta). |
| Identificar una sesión | Las sesiones de clase y las tutorías se tratan igual. Cada una se identifica con `tipo` (`clase` o `tutoria`) más `id`, y así aparecen en las rutas: `/sesiones/{tipo}/{id}`. |

### Valores de los enumerados

| Enumerado | Valores |
| --- | --- |
| Estado de asistencia | `presente`, `ausente`, `tardanza`, `justificado` |
| Método de registro | `manual`, `qr_docente` (el docente escaneó el carnet), `qr_dinamico` (el estudiante escaneó el QR de la sesión) |
| Estado de la sesión | `programada`, `realizada`, `suspendida` |
| Nivel de inasistencia | `normal`, `en_riesgo` (≥ 80 % del límite), `superado` (por encima del límite) |
| Estado de la justificación | `pendiente`, `aprobada`, `rechazada` |
| Veredicto del proyecto | `aprobado`, `reprobado` (o `null` si aún no hay) |
| Rol en el PNF | `coordinador`, `docente` |

---

## 2. Autenticación

La API usa tokens de Laravel Sanctum. Cada inicio de sesión crea un token para un dispositivo; la app debe guardarlo de forma segura (por ejemplo, con `flutter_secure_storage`) y enviarlo en la cabecera `Authorization`. Los tokens no caducan; se revocan al cerrar sesión.

Un token de docente no sirve en las rutas del estudiante, ni al revés (responde `403`).

Ambas rutas de inicio de sesión admiten **5 intentos por minuto** por cuenta e IP; después responden `429`.

### `POST /docente/login`

| Campo | Tipo | Notas |
| --- | --- | --- |
| `email` | texto | Obligatorio. |
| `password` | texto | Obligatorio. |
| `dispositivo` | texto | Obligatorio, máx. 100. Nombre visible del teléfono (p. ej. "Pixel 7"). |

Respuesta `201`:

```json
{
  "token": "1|ghhPmin34iPak7v9NhQ3ZRPCj2XfPKOBAmfTAIEQ82fda6db",
  "usuario": {
    "tipo": "docente",
    "id": 3,
    "nombre": "Docente Compartido",
    "cedula": "16711507",
    "email": "docente@gestionpnf.test"
  }
}
```

Errores `422`:

| Campo | Mensaje |
| --- | --- |
| `email` | `Las credenciales no son correctas.` |
| `email` | `Tu cuenta no tiene acceso a ningún PNF.` (cuenta inactiva o sin PNF asignado) |

### `POST /estudiante/login`

| Campo | Tipo | Notas |
| --- | --- | --- |
| `cedula` | texto | Obligatorio. Se aceptan prefijos y puntos: `V-30.111.222` equivale a `30111222`. |
| `password` | texto | Obligatorio. La contraseña inicial es la cédula. |
| `dispositivo` | texto | Obligatorio, máx. 100. |

Respuesta `201`:

```json
{
  "token": "2|tV2RYVHGEkf5Y3BIKFYjmiqWzCS3FjSaUsKrTDMNf7757868",
  "usuario": {
    "tipo": "estudiante",
    "id": 1,
    "cedula": "30111222",
    "nombres": "María",
    "apellidos": "Pérez",
    "email": "batz.eliza@example.org",
    "pnf": { "nombre": "PNF en Informática", "codigo": "PNFI" },
    "debe_cambiar_password": false
  }
}
```

Errores `422` en `cedula`: `Las credenciales no son correctas.` o `Tu cuenta de estudiante está inactiva.`

> **Contraseña inicial.** Si `debe_cambiar_password` es `true`, el estudiante solo puede llamar a `PUT /estudiante/contrasena`. Cualquier otra ruta del estudiante responde `403` con `Debes cambiar tu contraseña inicial antes de continuar.` La app debe llevarlo directo a la pantalla de cambio de contraseña.

### `POST /logout`

Revoca el token con el que se hace la petición. Respuesta `200`: `{"message": "Sesión cerrada."}`

---

## 3. Rutas comunes

Sirven para docentes y estudiantes.

### `GET /yo`

Perfil del usuario del token, con el mismo formato de `usuario` del inicio de sesión, dentro de `data`.

```json
{ "data": { "tipo": "estudiante", "id": 1, "cedula": "30111222", "nombres": "María", "...": "..." } }
```

### `GET /notificaciones`

Las 50 notificaciones más recientes, las mismas de la campana del panel web: avisos de inasistencia, revisión de justificaciones, etc.

```json
{
  "data": [
    {
      "id": "9d3c…",
      "titulo": "Tu justificación fue aprobada",
      "cuerpo": "Matemática · Martes 06/10/2026 08:00–10:15",
      "leida": false,
      "fecha": "2026-10-25T14:30:00-04:00"
    }
  ],
  "sin_leer": 1
}
```

### `POST /notificaciones/leer`

Marca todas como leídas. Respuesta `200`.

---

## 4. Rutas del docente

Requieren un token de docente. Salvo `GET /docente/pnfs`, todas llevan el PNF en la URL: `/docente/pnfs/{slug}/…`. Un docente puede trabajar en varios PNF; si el `slug` no es de uno suyo, la respuesta es `404`.

El docente ve y gestiona:

- las **clases** de los cursos que dicta;
- las **tutorías** de los proyectos en los que es tutor académico o técnico.

Una sesión que no le corresponde responde `404` (no se revela que existe). El coordinador del PNF puede gestionar todas las del PNF.

### `GET /docente/pnfs`

```json
{
  "data": [
    { "slug": "pnfa", "nombre": "PNF en Administración", "codigo": "PNFA", "rol": { "valor": "docente", "etiqueta": "Docente" } },
    { "slug": "pnfi", "nombre": "PNF en Informática", "codigo": "PNFI", "rol": { "valor": "docente", "etiqueta": "Docente" } }
  ]
}
```

### `GET /docente/pnfs/{slug}/cursos`

Cursos que dicta en el periodo activo.

```json
{
  "data": [
    {
      "id": 1,
      "unidad_curricular": { "codigo": "PNFI-ALG", "nombre": "Algorítmica y Programación", "es_proyecto": false },
      "seccion": "Sección 01",
      "periodo": "2026-II",
      "docentes": ["Docente Compartido"],
      "umbral_inasistencia": 25
    }
  ]
}
```

### `GET /docente/pnfs/{slug}/cursos/{curso}/sesiones`

Sesiones de clase del curso, por fecha. Parámetros opcionales: `desde` y `hasta` (`AAAA-MM-DD`). Cada elemento tiene el formato de [sesión](#formato-de-una-sesión).

### `GET /docente/pnfs/{slug}/sesiones/hoy`

Clases y tutorías de hoy del docente, ordenadas por hora de inicio. Es la pantalla principal de la app.

#### Formato de una sesión

```json
{
  "data": [
    {
      "tipo": "clase",
      "id": 109,
      "fecha": "2026-10-25",
      "hora_inicio": "14:19",
      "hora_fin": "15:24",
      "estado": { "valor": "programada", "etiqueta": "Programada" },
      "titulo": "Algorítmica y Programación",
      "detalle": "Sección 01 · PNFI-ALG",
      "curso_id": 1,
      "convocados": 21,
      "registrados": 0,
      "asistencia_abierta": true,
      "puede_registrar": true
    }
  ]
}
```

| Campo | Significado |
| --- | --- |
| `titulo` | Nombre de la unidad curricular (clase) o tema de la tutoría. |
| `detalle` | Sección y código de la UC (clase), o `Tutoría · <título del proyecto>`. |
| `convocados` | Estudiantes inscritos en el curso (clase) o integrantes del proyecto (tutoría). |
| `registrados` | Cuántos ya tienen asistencia registrada. |
| `asistencia_abierta` | `true` si ahora se acepta el registro por QR (ver la [ventana horaria](#6-flujos-de-registro-por-qr)). |
| `puede_registrar` | `false` si la sesión es futura o está suspendida: no se puede tomar asistencia. |

### `GET /docente/pnfs/{slug}/sesiones/{tipo}/{id}`

La sesión con la lista de convocados y su estado actual (`null` si aún no tiene registro).

```json
{
  "data": {
    "tipo": "clase",
    "id": 109,
    "...": "mismos campos de la sesión",
    "participantes": [
      { "id": 16, "cedula": "26248318", "nombre": "Bechtelar, Chelsea", "estado": null, "metodo": null },
      { "id": 8, "cedula": "34888246", "nombre": "Becker, Eloy", "estado": { "valor": "presente", "etiqueta": "Presente" }, "metodo": { "valor": "qr_dinamico", "etiqueta": "QR dinámico" } }
    ]
  }
}
```

### `GET /docente/pnfs/{slug}/sesiones/{tipo}/{id}/qr-dinamico`

Código vigente del QR dinámico. La app dibuja un QR con el texto de `codigo` y pide uno nuevo al llegar a `expira_at`; conviene pedirlo uno o dos segundos antes.

```json
{
  "codigo": "GPNFD:sesion_clase:109:59765089:ad1f0f2c018e9ebd0fb5",
  "expira_at": "2026-10-25T14:25:00-04:00",
  "segundos": 30
}
```

Responde `403` si la sesión es futura o está suspendida, y `422` en el campo `sesion` si la toma por QR no está abierta (antes de la ventana o después del fin).

### `POST /docente/pnfs/{slug}/sesiones/{tipo}/{id}/escanear`

El docente escanea el QR del carnet de un estudiante (impreso o en la app del estudiante).

| Campo | Tipo | Notas |
| --- | --- | --- |
| `qr` | texto | Contenido leído del carnet, con la forma `GPNF:<uuid>`. |

Respuesta `201` si se registró, `200` si ya estaba registrado (presente, tardanza o justificado):

```json
{
  "message": "Asistencia registrada.",
  "estudiante": { "id": 2, "cedula": "34682446", "nombre": "Prosacco, Chet" },
  "estado": { "valor": "presente", "etiqueta": "Presente" }
}
```

Errores `422` en el campo `qr`:

| Mensaje | Causa |
| --- | --- |
| `El carnet no es válido o no pertenece a este PNF.` | El QR no es de un carnet o es de otro PNF. |
| `<Nombre> no está convocado a esta sesión.` | No está inscrito en el curso o no es integrante del proyecto. |
| `La asistencia de esta sesión aún no está abierta (abre a las 07:45 am).` | Antes de la ventana. |
| `La sesión ya terminó; pide a tu docente que registre la asistencia manualmente.` | Después del fin. |

### `POST /docente/pnfs/{slug}/sesiones/{tipo}/{id}/asistencias`

Registro manual de uno o varios estudiantes; sirve también para corregir. Solo se actualizan los que cambian de estado. Marca la sesión como realizada.

```json
{ "estados": { "16": "presente", "8": "ausente", "23": "tardanza" } }
```

Las claves son ids de estudiante y los valores, estados de asistencia. Respuesta `200`: `{"message": "Asistencia guardada."}`

Errores: `422` en `estados.<id>` si el estado no existe, `422` en `estados` si algún estudiante no está convocado, y `403` si la sesión es futura o está suspendida.

### `POST /docente/pnfs/{slug}/sesiones/{tipo}/{id}/cerrar`

Termina la toma por QR: los convocados que no se registraron quedan **ausentes** y la sesión, realizada.

```json
{ "message": "Asistencia cerrada. Ausentes: 3.", "ausentes": 3 }
```

---

## 5. Rutas del estudiante

Requieren un token de estudiante. Siempre devuelven solo los datos propios del estudiante; pedir un curso, proyecto o asistencia ajenos responde `404`.

### `PUT /estudiante/contrasena`

| Campo | Notas |
| --- | --- |
| `contrasena_actual` | Obligatoria. |
| `contrasena` | Mínimo 8 caracteres, con letras y números, distinta de la actual y de la cédula. |
| `contrasena_confirmation` | Igual a `contrasena`. |

Respuesta `200`: `{"message": "Contraseña actualizada."}`. Errores `422`: `La contraseña actual no es correcta.`, `La nueva contraseña no puede ser tu cédula.`, entre otros.

### `GET /estudiante/carnet`

Carnet digital. La app dibuja un QR con el valor de `qr`, que es el mismo del carnet impreso y el que escanea el docente.

```json
{
  "qr": "GPNF:b870c855-b757-442e-988f-ffdc250a6ab6",
  "cedula": "30111222",
  "nombres": "María",
  "apellidos": "Pérez",
  "pnf": "PNF en Informática",
  "secciones": ["Sección 01"],
  "periodo": "2026-II"
}
```

### `GET /estudiante/cursos`

Unidades curriculares en las que está inscrito, con su asistencia a clases.

```json
{
  "data": [
    {
      "id": 2,
      "unidad_curricular": { "codigo": "PNFI-MAT", "nombre": "Matemática", "es_proyecto": false },
      "seccion": "Sección 01",
      "periodo": "2026-II",
      "docentes": ["Docente Compartido"],
      "umbral_inasistencia": 25,
      "resumen": {
        "sesiones": 6,
        "asistencias": 4,
        "tardanzas": 0,
        "inasistencias": 2,
        "justificadas": 0,
        "porcentaje": 33.33,
        "nivel": { "valor": "superado", "etiqueta": "Umbral superado" }
      }
    }
  ]
}
```

El porcentaje es `inasistencias / sesiones × 100` y considera solo las clases realizadas con asistencia registrada. `asistencias` incluye las tardanzas; las justificadas no cuentan como inasistencia.

### `GET /estudiante/cursos/{curso}/asistencias`

Historial de asistencias del curso, de la más reciente a la más antigua.

```json
{
  "data": [
    {
      "id": 160,
      "estado": { "valor": "ausente", "etiqueta": "Ausente" },
      "metodo": { "valor": "manual", "etiqueta": "Manual" },
      "registrado_at": "2026-10-25T14:24:39-04:00",
      "sesion": { "tipo": "clase", "fecha": "2026-10-06", "horario": "08:00–10:15", "tema": null },
      "justificacion": { "valor": "pendiente", "etiqueta": "Pendiente" },
      "puede_justificarse": false
    }
  ]
}
```

`justificacion` es el estado de la última solicitud (`null` si no hay). `puede_justificarse` es `true` solo para una ausencia sin solicitud pendiente ni aprobada.

### `GET /estudiante/proyectos`

Proyectos de los que es integrante, con los tres tutores, su asistencia a tutorías y su veredicto. El límite de tutorías es el del trayecto y se controla aparte del de clases.

```json
{
  "data": [
    {
      "id": 1,
      "titulo": "Sistema de control de inventario para el consejo comunal",
      "descripcion": null,
      "comunidad": "Consejo comunal Dietrich",
      "unidad_curricular": "Proyecto Socio Tecnológico I",
      "seccion": "Sección 01",
      "periodo": "2026-II",
      "tutores": {
        "academicos": ["Docente Compartido"],
        "tecnico": "Docente Técnico",
        "institucional": { "nombre": "Sister Collins", "contacto": "0414-2188388" }
      },
      "integrantes": ["Becker, Eloy", "Jakubowski, Charlie", "Pérez, María", "Roob, Elwin", "Ryan, Bradford"],
      "umbral_inasistencia_tutorias": 25,
      "resumen": {
        "sesiones": 6,
        "asistencias": 5,
        "tardanzas": 0,
        "inasistencias": 1,
        "justificadas": 0,
        "porcentaje": 16.67,
        "nivel": { "valor": "normal", "etiqueta": "Normal" }
      },
      "veredicto": { "valor": "aprobado", "etiqueta": "Aprobado" }
    }
  ]
}
```

`tutores.tecnico` y `tutores.institucional` pueden ser `null`.

### `GET /estudiante/proyectos/{proyecto}/tutorias`

Asistencias a las tutorías del proyecto, con el mismo formato que las de clase; aquí `sesion.tipo` es `tutoria` y `sesion.tema` trae el tema.

### `GET /estudiante/justificaciones`

```json
{
  "data": [
    {
      "id": 1,
      "sesion": "Matemática · Martes 06/10/2026 08:00–10:15",
      "motivo": "Cita médica en el ambulatorio.",
      "soporte": "constancia.pdf",
      "estado": { "valor": "pendiente", "etiqueta": "Pendiente" },
      "observacion_revision": null,
      "solicitada_at": "2026-10-25T14:24:39-04:00",
      "revisada_at": null
    }
  ]
}
```

`observacion_revision` es el comentario del docente al aprobar o rechazar.

### `POST /estudiante/asistencias/{asistencia}/justificaciones`

Solicita la justificación de una inasistencia propia (de clase o de tutoría). Se envía como `multipart/form-data`.

| Campo | Notas |
| --- | --- |
| `motivo` | Obligatorio, máx. 1000 caracteres. |
| `soporte` | Archivo obligatorio: PDF, JPG o PNG de hasta 5 MB. |

Respuesta `201` con la justificación en `data`, en estado `pendiente`. Los docentes a cargo reciben una notificación.

| Error | Causa |
| --- | --- |
| `404` | La asistencia no es del estudiante. |
| `422` en `motivo` | `Esta inasistencia no se puede justificar.` (no es una ausencia o ya tiene una solicitud pendiente o aprobada). |
| `422` en `soporte` | `El soporte debe ser un PDF o una imagen JPG o PNG.` / `El soporte no puede pesar más de 5 MB.` |

### `POST /estudiante/asistencias/qr`

Registra la asistencia escaneando el QR dinámico que muestra el docente. Ver el flujo en la [sección 6](#6-flujos-de-registro-por-qr).

| Campo | Notas |
| --- | --- |
| `codigo` | Obligatorio. Texto leído del QR (`GPNFD:…`). |
| `latitud`, `longitud` | Ubicación actual del teléfono. Obligatorias si la sede de la clase tiene coordenadas; conviene enviarlas siempre. |

Respuesta `201` si se registró, `200` si ya estaba registrado:

```json
{
  "message": "Asistencia registrada.",
  "sesion": "Algorítmica y Programación · Domingo 25/10/2026 14:19–15:24",
  "estado": { "valor": "presente", "etiqueta": "Presente" }
}
```

Errores `422`:

| Campo | Mensaje |
| --- | --- |
| `codigo` | `El código no es un QR de asistencia válido.` |
| `codigo` | `El código QR caducó. Escanea el que se muestra ahora.` |
| `codigo` | `La sesión del código no existe.` (también si es de otro PNF) |
| `codigo` | `<Nombre> no está convocado a esta sesión.` |
| `codigo` | Mensajes de la ventana horaria (aún no abierta / ya terminó). |
| `latitud` | `Activa la ubicación del teléfono para registrar tu asistencia.` |
| `latitud` | `Debes estar en la sede <nombre> para registrar tu asistencia.` |

---

## 6. Flujos de registro por QR

### Ventana horaria

Para ambas formas de QR, la asistencia solo se acepta **el día de la sesión**:

- desde **15 minutos antes** de la hora de inicio;
- hasta la **hora de fin**.

Quien se registra en los primeros **15 minutos** después del inicio queda como `presente`; después, como `tardanza`. Fuera de la ventana, el docente usa el registro manual.

### Carnet escaneado por el docente (`qr_docente`)

1. El docente abre la sesión de hoy (`GET …/sesiones/hoy`).
2. Escanea el carnet de cada estudiante (`POST …/escanear` con `qr`).
3. Al terminar, cierra la toma (`POST …/cerrar`) para que los que faltan queden ausentes.

No se valida la ubicación: el docente está presente.

### QR dinámico escaneado por el estudiante (`qr_dinamico`)

1. El docente muestra el código vigente. Puede hacerlo de dos maneras:
   - **En la app:** pide `GET …/qr-dinamico` y lo vuelve a pedir al llegar a `expira_at` (cada 30 segundos).
   - **En el panel web:** proyecta la página **QR** de la sesión en el aula. Está en *Cursos → sesión → QR* o en el widget *Clases de hoy*, y se refresca sola.
2. El estudiante escanea con la app y envía `POST /estudiante/asistencias/qr` con `codigo`, `latitud` y `longitud`.
3. El docente cierra la toma (`POST …/cerrar` o el botón **Cerrar asistencia** del panel).

Controles antifraude:

- **Código firmado que caduca.** Cada código vale para su intervalo de 30 s y el anterior (unos 60 s en total). Una captura reenviada deja de servir enseguida, y sin la clave del servidor no se puede fabricar uno válido.
- **Ventana horaria**, descrita arriba.
- **Ubicación.** Si la sede de la sección tiene coordenadas, el teléfono debe estar dentro del radio de la sede (200 m si la sede no define otro). Las tutorías no tienen sede, así que en ellas no se valida la ubicación.

> **Para la app:** pide permiso de ubicación antes de abrir el escáner y envía la posición actual, no la última conocida. Si la precisión es mala dentro de edificios, el administrador puede ampliar el radio de la sede en *Administración → Sedes*.

### Estados existentes

Un estudiante que ya está `presente`, con `tardanza` o `justificado` no cambia al volver a escanear (la respuesta es `200`). Uno marcado `ausente` sí pasa a `presente` o `tardanza`.

---

## 7. Errores

| Código | Cuándo | Cuerpo |
| --- | --- | --- |
| `401` | Falta el token o no es válido. | `{"message": "Unauthenticated."}` |
| `403` | Token del tipo equivocado, contraseña inicial sin cambiar, o sesión futura o suspendida. | `{"message": "<explicación>"}` |
| `404` | El recurso no existe o no es del usuario (PNF, curso, sesión, proyecto, asistencia ajenos). | `{"message": "..."}` |
| `422` | Datos inválidos o regla de negocio incumplida. | `{"message": "...", "errors": {"campo": ["mensaje"]}}` |
| `429` | Demasiados intentos de inicio de sesión. | `{"message": "Too Many Attempts."}` |

Los mensajes propios del sistema (los de las tablas anteriores) están en español. Los de las validaciones genéricas de Laravel (campo obligatorio, formato de correo, etc.) salen en inglés mientras la aplicación use `APP_LOCALE=en`. La app puede mostrar `errors.<campo>[0]` directamente o usar textos propios para esos casos.

---

## 8. Configuración del servidor

Valores en `config/asistencia.php`; se ajustan con variables en `.env` sin tocar el código:

| Variable | Por defecto | Uso |
| --- | --- | --- |
| `ASISTENCIA_QR_SEGUNDOS` | `30` | Cada cuántos segundos cambia el QR dinámico. |
| `ASISTENCIA_QR_VENTANAS` | `2` | Cuántos códigos seguidos se aceptan (el vigente y los anteriores). |
| `ASISTENCIA_MINUTOS_ANTES` | `15` | Minutos antes del inicio en que se abre la toma por QR. |
| `ASISTENCIA_TOLERANCIA_TARDANZA` | `15` | Minutos después del inicio a partir de los cuales es tardanza. |
| `ASISTENCIA_RADIO_METROS` | `200` | Radio por defecto alrededor de la sede. |

La ubicación de cada sede (latitud, longitud y radio propio) se carga en el panel de administración, en *Sedes*. Si una sede no tiene coordenadas, no se valida la ubicación en sus clases.

### Cuentas de prueba (seeder)

| Usuario | Acceso |
| --- | --- |
| `docente@gestionpnf.test` / `password` | Docente de Informática y Administración. |
| `tecnico@gestionpnf.test` / `password` | Tutor técnico de los proyectos de Informática. |
| `coordinador@gestionpnf.test` / `password` | Coordinador de Informática. |
| Cédula `30111222` / `password` | Estudiante María Pérez (Informática). |
