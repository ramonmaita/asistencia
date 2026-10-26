## Qué hace el administrador

El administrador (superadministrador) configura el sistema para toda la universidad desde el **panel de administración** (`/admin`). También puede entrar al panel académico de cualquier PNF (`/app`) con los mismos permisos que un coordinador.

En el panel de administración se gestionan:

- Los **PNF** y su personal.
- Las **cuentas** del personal (coordinadores, docentes y administrativos) y de los estudiantes.
- Los **periodos académicos** y los **feriados**.
- Las **sedes** y su ubicación para el control por GPS.

## Crear y configurar un PNF

1. Entra a **PNF** y pulsa **Nuevo PNF**.
2. Escribe el nombre y el código (por ejemplo, `PNFI`). El **slug** es la parte de la dirección del panel (`/app/pnfi`); si lo dejas vacío se genera a partir del código.
3. Define el **umbral de inasistencia en clases** (en %). Al superarlo, el estudiante y sus docentes reciben un aviso.
4. Sube el logo si lo tienes y deja el PNF **activo**.
5. Guarda. En la pestaña **Docentes y coordinadores** asigna al personal con su rol.

Un PNF inactivo deja de aparecer en el menú de PNF del panel académico.

## Gestionar al personal

En **Usuarios › Personal** están todas las cuentas del personal. Cada fila muestra en qué PNF trabaja la persona y con qué rol.

- **Nueva cuenta:** nombre, cédula, correo y contraseña inicial. Luego, en la pestaña **PNF y roles**, asígnala a uno o varios PNF.
- **Roles por PNF:** una misma persona puede ser coordinador en un PNF y docente en otro.
  - **Coordinador:** gestiona todo el PNF.
  - **Docente:** dicta sus cursos y tutora proyectos.
  - **Administrativo:** gestiona estudiantes, personal, inscripciones y reportes, pero no registra asistencia.
- **Cambiar contraseña** y **Desactivar / Activar** están en el menú de acciones de cada fila. Una cuenta desactivada no entra a ningún panel ni a la app móvil.
- **Superadministrador:** actívalo solo para quien deba administrar todo el sistema.

Filtra la lista por rol, por PNF o por estado para encontrar a alguien rápido.

## Gestionar estudiantes de todos los PNF

En **Usuarios › Estudiantes** ves a los estudiantes de todos los PNF. Desde aquí puedes:

- Buscar por cédula, nombre o correo, y filtrar por PNF.
- Cambiar a un estudiante de PNF o corregir sus datos.
- **Restablecer la contraseña:** vuelve a ser la cédula y el estudiante debe cambiarla al entrar.
- **Regenerar el QR** del carnet cuando se pierde: el carnet anterior deja de funcionar.
- Desactivar la cuenta.

La carga masiva (importar desde Excel o CSV) se hace dentro de cada PNF, en el panel académico.

## Periodos y feriados

- **Periodos:** solo puede haber un periodo **activo**. Al activar uno, el anterior se desactiva solo. Las secciones, los cursos y los reportes trabajan con el periodo activo.
- **Feriados:** las fechas registradas se saltan al generar las sesiones de clase. Si un feriado se decreta después de generar las sesiones, el docente o el coordinador puede marcar como suspendida la sesión de ese día.

## Sedes y control de ubicación

En **Sedes** registra cada sede con su **latitud**, **longitud** y **radio en metros**. Cuando un estudiante registra su asistencia con el QR dinámico, el sistema verifica que esté dentro de ese radio.

- Si una sede no tiene coordenadas, no se valida la ubicación.
- Si el radio queda vacío se usan 200 metros.
- Dentro de los edificios el GPS puede fallar por 30 a 80 metros: ajusta el radio después de probarlo en el aula.

## Preguntas frecuentes

### Un docente dice que no ve un PNF en el menú

Revisa en **Personal** que esté asignado a ese PNF y que el PNF esté activo.

### ¿Cómo creo otra cuenta de administrador?

Crea la cuenta en **Personal** y activa **Superadministrador**.

### ¿Puedo eliminar a un docente que ya registró asistencias?

Es mejor **desactivarlo**: conserva su historial y deja de tener acceso. Para sacarlo de un PNF basta con quitarlo en la pestaña **PNF y roles**.

### Un estudiante aparece en el PNF equivocado

Ábrelo en **Usuarios › Estudiantes**, cambia el PNF y guarda. Sus inscripciones en cursos del PNF anterior deben retirarse desde esos cursos.
