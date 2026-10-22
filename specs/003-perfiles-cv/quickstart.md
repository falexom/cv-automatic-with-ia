# Quickstart — Validación de Perfiles de CV

**Feature**: `003-perfiles-cv` | **Fecha**: 2026-10-22
**Referencias**: [plan.md](./plan.md) · [data-model.md](./data-model.md) · [contracts/http-contracts.md](./contracts/http-contracts.md) · [spec.md](./spec.md)

Guía para aplicar la migración, ejecutar las pruebas y validar la capacidad de extremo a extremo una vez implementada. No contiene código de implementación.

## Requisitos previos

- PHP 8.3+ con extensión `pdo_sqlite`
- Composer 2.x
- Node.js 22+ y npm
- Navegador de escritorio
- Base de datos local existente con el Perfil Maestro de SPEC-001 (puede estar vacío)

## Puesta en marcha

```bash
# Dependencias (si no están instaladas)
composer install
npm install

# Configuración local (sin secretos en el repositorio)
cp .env.example .env        # solo si no existe .env
php artisan key:generate    # solo si no existe APP_KEY

# Base de datos local
touch database/database.sqlite   # si no existe
```

## Aplicar la migración

La capacidad agrega una migración aditiva: crea `cv_profiles` y las cinco tablas pivote. No modifica tablas ni filas existentes.

```bash
php artisan migrate
php artisan migrate:status
```

- Verificar que la migración `2026_10_22_000001_create_cv_profiles_and_content_tables` figure como aplicada.
- No usar `php artisan migrate:fresh` ni `migrate:refresh`: destruyen los datos del Perfil Maestro y de SPEC-002.

### Rollback

```bash
php artisan migrate:rollback --step=1
```

- Elimina únicamente los perfiles de CV, sus asociaciones y relevancias.
- No afecta a experiencias, educación, certificaciones, habilidades, proyectos, datos personales ni documentos.
- Volver a aplicar con `php artisan migrate`.

## Ejecutar pruebas

```bash
# Suite completa de backend (Pest; SQLite en memoria)
php artisan test

# Pruebas específicas de esta capacidad
php artisan test tests/Feature/CvProfileTest.php
php artisan test tests/Feature/CvProfileContentTest.php
php artisan test tests/Feature/CvProfileInitialProfilesTest.php

# Frontend (Vitest + React Testing Library)
npm test
npm test -- resources/js/components/__tests__/CvProfileForm.test.tsx
npm test -- resources/js/hooks/__tests__/use-current-url.test.ts

# Verificación de tipos
npm run types:check
```

Si se modificó el frontend, también puede ejecutarse la verificación de formato/lint del proyecto:

```bash
npm run check
composer lint:check
```

## Flujo manual de validación

Cada escenario deriva de los criterios de aceptación del spec y debe pasar antes de dar la capacidad por terminada.

### 1. Estado vacío y perfiles iniciales (FR-017, US4)

1. Partir de una base sin perfiles de CV y abrir **Perfiles** desde el menú principal.
2. Verificar el estado vacío con orientación y las acciones «Crear perfil» y «Crear perfiles iniciales».
3. Ejecutar «Crear perfiles iniciales».
4. Comprobar que aparecen exactamente dos perfiles activos:
    - «Analista» con título «Analista de Sistemas».
    - «Programador» con título «Programador / Desarrollador».
5. Abrir cada uno y verificar que no tienen resumen ni contenido preseleccionado.
6. Volver al listado: la acción de presets ya no se ofrece.
7. Invocar de nuevo la acción por la ruta (`POST /profiles/initial`) y verificar que no crea duplicados ni sobrescribe. Este caso está cubierto por `CvProfileInitialProfilesTest`.

### 2. Creación, nombre y datos propios (FR-005, FR-013, FR-014, US1)

1. Crear un perfil con nombre, título y resumen; guardar.
2. Verificar que aparece activo en el listado y que el detalle muestra el título y el resumen propios.
3. Intentar crear un perfil con el mismo nombre variando mayúsculas y espacios (por ejemplo, « analista ») ⇒ se rechaza e identifica el perfil existente.
4. Intentar guardar un nombre vacío o solo con espacios ⇒ se rechaza con un mensaje claro.
5. Renombrar un perfil a su mismo nombre normalizado ⇒ se permite.
6. Editar el título y el resumen de un perfil ⇒ solo cambia ese perfil; el Perfil Maestro no se modifica.
7. Desactivar un perfil y renombrarlo a un nombre ya usado ⇒ también se rechaza (la unicidad incluye inactivos).

### 3. Selección explícita de contenido (FR-008, FR-009, US2)

1. Con al menos un registro maestro de cada clase, abrir **Editar** en un perfil.
2. Verificar que ninguna casilla está marcada inicialmente y que se invita a seleccionar contenido.
3. Marcar una experiencia, una entrada de educación, un curso/certificación, una habilidad y un proyecto.
4. Verificar que al marcar cada elemento aparece su relevancia con «Media» por defecto.
5. Guardar y comprobar que los cinco elementos aparecen agrupados en sus secciones.
6. Verificar que los elementos no marcados no aparecen en el detalle.
7. Intentar incluir un elemento ya incluido ⇒ no se duplica; se actualiza la asociación existente.
8. Verificar que no hay ningún botón para crear un registro maestro desde el perfil; los enlaces llevan a las secciones del Maestro.

### 4. Relevancia y orden (FR-010, FR-011, FR-012, US2)

1. Incluir en una misma sección varios elementos con relevancia Alta, Media y Baja.
2. Verificar el orden descendente: Alta, Media, Baja.
3. Para experiencia, educación, certificaciones y proyectos, incluir elementos con la misma relevancia y fechas distintas, incluido uno sin fecha.
4. Verificar que se ordenan de más reciente a más antiguo y que el registro sin fecha queda al final.
5. Para habilidades, incluir varias con la misma relevancia y comprobar el orden por categoría (técnica, funcional, metodológica, herramienta, tecnología) y luego por nombre.
6. Verificar que no existe ningún control de arrastre ni de reordenamiento manual.

### 5. Reutilización y aislamiento (FR-003, SC-002, US2)

1. Incluir el mismo elemento maestro en dos perfiles con relevancias distintas (por ejemplo, Alta en «Analista» y Baja en «Programador»).
2. Cambiar la relevancia en uno de los perfiles ⇒ el otro conserva la suya.
3. Desasociar el elemento en uno de los perfiles ⇒ deja de aparecer solo en ese perfil; el otro lo conserva y el registro maestro permanece intacto.
4. Editar el título o el resumen de un perfil ⇒ el otro perfil y el Maestro no cambian.

### 6. Herencia de datos personales y título propio (FR-014, FR-015, US3)

1. Completar en el Perfil Maestro nombre, teléfono, correo, ubicación y enlaces.
2. Abrir el detalle de un perfil y verificar que esos datos aparecen en modo lectura, sin campos editables.
3. Verificar que **no** se muestra el título profesional del Maestro; se muestra el título propio del perfil.
4. Dejar el título del perfil vacío ⇒ no se muestra ningún título (no se usa el del Maestro como reemplazo).
5. Cambiar el correo o la ubicación en el Perfil Maestro y volver a abrir el perfil ⇒ se ve el dato actualizado sin sincronización manual.
6. Cambiar el título del perfil ⇒ el Perfil Maestro conserva su título original.
7. Abrir un perfil cuando el Maestro no tiene datos personales ⇒ se muestra una orientación, sin inventar información.

### 7. Actualización y eliminación de registros maestros (FR-002, US3)

1. Incluir una experiencia y una habilidad en un perfil.
2. Editar la experiencia en **Experiencia** (por ejemplo, cambiar la empresa) ⇒ el detalle del perfil muestra el dato actualizado.
3. Eliminar la experiencia desde **Experiencia** ⇒ desaparece del perfil; el perfil y sus demás asociaciones permanecen.
4. Repetir con educación, certificación, habilidad y proyecto ⇒ en todos los casos solo se elimina la asociación.
5. Verificar que no queda ninguna copia obsoleta ni un mensaje de error en el perfil.

### 8. Activación y desactivación (FR-016, FR-018, US1, US3)

1. Abrir un perfil con contenido y desactivarlo.
2. Verificar que pasa a inactivo, que sigue visible y diferenciado en el listado, y que conserva nombre, título, resumen, asociaciones y relevancias.
3. Abrir su detalle y su edición ⇒ sigue siendo consultable y editable; se muestra el aviso de inactivo.
4. Reactivarlo ⇒ vuelve a estar activo y disponible para la futura generación.
5. Desactivar todos los perfiles ⇒ la gestión sigue disponible y no hay errores.
6. Buscar una acción de eliminación definitiva ⇒ no existe. Una petición `DELETE /profiles/{id}` responde 405 y `profiles.destroy` no está registrada.

### 9. Persistencia y texto (FR-019, SC-006)

1. Crear o editar un perfil con nombre, título y resumen que incluyan acentos y caracteres especiales (por ejemplo, «Análisis y diseño — ñandú»).
2. Guardar, detener `composer dev` y volver a iniciarlo.
3. Recargar **Perfiles** ⇒ se conservan perfiles, estados, datos propios, asociaciones y relevancias; los textos se muestran correctamente.
4. Verificar que no hay llamadas a servicios externos ni dependencia de red.

### 10. Fallo de guardado y reintento (FR-020, SC-005)

1. Provocar un error de validación (por ejemplo, nombre duplicado o ID de un elemento eliminado mientras el formulario estaba abierto).
2. Verificar que se informa el problema, que el formulario conserva lo ingresado y que se puede corregir y reintentar.
3. Confirmar que no se guardó un estado parcial (por ejemplo, no quedaron asociaciones nuevas sin el perfil o viceversa).

### 11. Límites de alcance (FR-018, Principios XXV–XXVI)

1. Verificar que no hay botones ni rutas para generar CV, vista previa, PDF, plantillas, exportación, IA, ofertas, postulaciones ni asociación de documentos a perfiles.
2. Verificar que no hay eliminación definitiva de perfiles ni historial/versionado.
3. Verificar que no se modificaron datos existentes del Perfil Maestro ni de SPEC-002 durante la migración.

## Registro de validación manual del responsable — 2026-10-22

**Fuente**: aceptación del responsable de SPEC-003 registrada en la conversación de cierre. **Fecha**: 2026-10-22. **Carácter**: validación manual del responsable; **no** es una prueba automatizada ni una ejecución de QA. No se reejecutaron pruebas ni comandos para este registro.

El siguiente mapeo indica los escenarios y pasos del quickstart donde cada evidencia tiene correspondencia. No implica que todas las aserciones de esos pasos hayan sido verificadas: los huecos se detallan en la sección siguiente.

### Evidencias aceptadas y mapeo a quickstart

| #   | Evidencia declarada                                                                                                 | Escenarios/pasos de quickstart que cubre                                                                                                                                                                                                                                                |
| --- | ------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Creación, edición, consulta, activación y desactivación de perfiles.                                                | Escenario 2, pasos 1–2 y 6 (creación, consulta y edición de datos propios); escenario 8, pasos 1–4 (activación, desactivación y reactivación).                                                                                                                                          |
| 2   | Selección, guardado y consulta de registros del Perfil Maestro con relevancia Alta/Media/Baja.                      | Escenario 3, pasos 1–5 (selección, guardado y consulta con relevancia); escenario 4, paso 1 (registros con Alta/Media/Baja).                                                                                                                                                            |
| 3   | Reutilización de elementos entre perfiles con relevancias independientes.                                           | Escenario 5, pasos 1–2.                                                                                                                                                                                                                                                                 |
| 4   | Actualizaciones del Perfil Maestro reflejadas en los perfiles asociados.                                            | Escenario 6, paso 5; escenario 7, paso 2.                                                                                                                                                                                                                                               |
| 5   | Eliminación de registros maestros sin afectar a los perfiles restantes.                                             | Escenario 7, pasos 3–4 (eliminación y conservación de perfiles; sin detalle clase por clase).                                                                                                                                                                                           |
| 6   | Conservación del contenido al desactivar y reactivar.                                                               | Escenario 8, pasos 1–4 (conservación de contenido; no así visibilidad, edición ni «todos inactivos»).                                                                                                                                                                                   |
| 7   | Persistencia de perfiles y asociaciones después de reiniciar.                                                       | Escenario 9, pasos 2–3 (persistencia de perfiles y asociaciones; no estados, datos propios, relevancias ni acentos).                                                                                                                                                                    |
| 8   | Validaciones de nombres duplicados y vacíos.                                                                        | Escenario 2, pasos 3–4, en cuanto a rechazo de nombres duplicados y de nombres vacíos; no se detalla la variante de mayúsculas/espacios, el nombre compuesto solo por espacios, la identificación del perfil existente, el renombrado al mismo normalizado ni la unicidad en inactivos. |
| 9   | Recuperación ante fallos de guardado y reintento.                                                                   | Escenario 10, pasos 1–2 (fallo de guardado y reintento; no el paso 3 de ausencia de estado parcial).                                                                                                                                                                                    |
| 10  | Exclusiones de alcance verificadas: sin generación de CV, PDF, exportaciones ni eliminación definitiva de perfiles. | Escenario 11, pasos 1–2, únicamente para CV, PDF, exportaciones y ausencia de eliminación definitiva; no vista previa, plantillas, IA, ofertas, postulaciones, documentos, historial/versionado ni paso 11.3.                                                                           |

### Subescenarios no confirmados explícitamente

Los siguientes puntos **no** se declaran aprobados por la lista de evidencias anterior:

| Escenario                                            | Subescenarios no confirmados                                                                                                                                                                                                                                                                                                                                                                                 |
| ---------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1. Estado vacío y perfiles iniciales                 | 1.1–1.7 completos: estado vacío y acciones; creación de los presets «Analista» y «Programador» con títulos exactos, activos y sin resumen/contenido; ocultamiento de la acción tras crearlos; no duplicación ni sobrescritura al reinvocar (este último punto tiene prueba automatizada, no evidencia manual).                                                                                               |
| 2. Creación, nombre y datos propios                  | 2.3 duplicado con diferencias de mayúsculas/espacios e identificación explícita del perfil existente; 2.4 nombre compuesto solo por espacios; 2.5 renombrar un perfil a su mismo nombre normalizado; 2.6 que editar título/resumen no altere el Perfil Maestro ni otros perfiles; 2.7 unicidad en perfiles inactivos.                                                                                        |
| 3. Selección explícita de contenido                  | 3.2 ausencia de preselección e invitación a seleccionar; 3.3 cobertura simultánea de las cinco clases; 3.4 valor inicial «Media»; 3.6 elementos no incluidos ausentes del detalle; 3.7 reinclusión sin duplicado y actualización de la asociación; 3.8 ausencia de creación de registros maestros desde el perfil y enlaces a sus secciones.                                                                 |
| 4. Relevancia y orden                                | 4.2 orden descendente Alta → Media → Baja; 4.3–4.4 empates por fecha y registros sin fecha al final; 4.5 orden de habilidades por categoría y nombre; 4.6 ausencia de arrastre o reordenamiento manual.                                                                                                                                                                                                      |
| 5. Reutilización y aislamiento                       | 5.3 desasociación aislada, con el otro perfil y el Maestro intactos; 5.4 edición de título/resumen sin afectar al otro perfil ni al Maestro.                                                                                                                                                                                                                                                                 |
| 6. Herencia de datos personales y título propio      | 6.1–6.2 datos personales en modo lectura, sin campos editables; 6.3 título del Maestro no mostrado; 6.4 ausencia de fallback al título del Maestro; 6.6 edición del título propio sin modificar el Maestro; 6.7 Perfil Maestro sin datos personales con orientación.                                                                                                                                         |
| 7. Actualización y eliminación de registros maestros | 7.4 repetición de la eliminación en educación, certificación, habilidad y proyecto (cascada en las cinco clases); 7.5 ausencia de copia obsoleta o mensaje de error tras la eliminación.                                                                                                                                                                                                                     |
| 8. Activación y desactivación                        | 8.2 visibilidad y diferenciación del inactivo en el listado; 8.3 acceso a detalle y edición con aviso de inactivo; 8.4 indicación de disponibilidad para la futura generación (no hay generador en esta capacidad); 8.5 desactivación de todos los perfiles; 8.6 comprobación técnica de 405 y ausencia de `profiles.destroy` (la exclusión funcional de eliminación definitiva sí está en la evidencia 10). |
| 9. Persistencia y texto                              | 9.1 acentos y caracteres especiales; 9.3 conservación de estados, datos propios y relevancias tras reiniciar (la evidencia 7 solo declara perfiles y asociaciones); 9.4 ausencia de llamadas externas o dependencia de red.                                                                                                                                                                                  |
| 10. Fallo de guardado y reintento                    | 10.3 ausencia de estado parcial tras el fallo.                                                                                                                                                                                                                                                                                                                                                               |
| 11. Límites de alcance                               | 11.1 vista previa, plantillas, IA, ofertas, postulaciones y asociación de documentos (la evidencia solo cubre CV, PDF y exportaciones); 11.2 historial/versionado; 11.3 ausencia de cambios a datos existentes del Perfil Maestro o de SPEC-002 durante la migración.                                                                                                                                        |

**Alcance de este registro**: no se declara la ejecución de la migración, del rollback ni de los comandos de prueba; se limita a las 10 evidencias manuales enumeradas. El «Resultado esperado» de este quickstart sigue siendo el objetivo de validación completa y no se declara alcanzado por este registro.

**Estado de T050**: cobertura manual parcial documentada. El responsable autorizó el cierre de SPEC-003 con las 10 evidencias aportadas; los subescenarios no confirmados arriba no se declaran aprobados y este registro no sustituye pruebas automatizadas ni QA.

## Resultado esperado

- `php artisan test` en verde, incluidos `CvProfileTest`, `CvProfileContentTest` y `CvProfileInitialProfilesTest`.
- `npm test` en verde, incluidos `CvProfileForm` y `use-current-url`.
- `npm run types:check` sin errores.
- Los 11 escenarios manuales pasan.
- La migración es aditiva y reversible; el Perfil Maestro y los documentos existentes permanecen intactos.
- La configuración queda lista para que SPEC-004 consuma los perfiles activos y su contenido ordenado, sin implementar la generación en esta capacidad.
