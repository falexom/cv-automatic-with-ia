# Implementation Plan: Generador de CV

**Branch**: `004-generador-de-cv` | **Date**: 2026-10-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/004-generador-de-cv/spec.md`

**Governance**: especificación clarificada el 2026-10-23 (C1–C7, sin pendientes). El encargo autoriza el ciclo completo Plan → Tasks → Implement → Validate, sin merge a `main` ni push. Base declarada por el encargo: rama `004-generador-de-cv`. No se ejecutaron comandos Git ni pruebas en esta fase; este Plan no modifica `spec.md`, código, pruebas ni `tasks.md`. La documentación queda en revisión del usuario; Implement permanece en pausa hasta su visto bueno, mientras que la aceptación funcional/manual final y la integración en `main` siguen pendientes.

## Summary

Implementar el **Generador de CV**: una vista de currículum de solo lectura, derivada y efímera, que se compone en cada consulta a partir del Perfil Profesional Maestro y de un perfil de CV activo seleccionado explícitamente. La vista aplica la selección, la relevancia y el orden ya definidos en SPEC-003, omite estrictamente el contenido no incluido, usa una única plantilla profesional de una columna y no almacena, edita ni exporta nada.

**Enfoque técnico**: monolito Laravel 13 + Inertia 3 + React 19/TypeScript, SQLite local y los componentes existentes. Se agrega un controlador `CvGeneratorController`, una clase de composición `CvPreviewContent`, la ruta `GET /cv` (`cv.index`), la página `Cv/Index` y los componentes presentacionales `CvGeneratorPanel` y `CvPreview`. No se crean tablas, migraciones, seeders, factories ni dependencias. `CvProfileContent::content()` no se modifica: la nueva clase reutiliza `personalData()` y lee las relaciones ya ordenadas de `CvProfile`, incluyendo responsabilidades, logros y resultados que el detalle de SPEC-003 no serializa. Pruebas Feature (Pest) del contrato y Vitest/RTL de la plantilla y los estados.

### Decisiones clave

- **Vista derivada, sin persistencia**: no hay entidad de CV, tabla, migración, seeder, factory, caché, sesión ni archivo. Cada `GET /cv` recompone la vista desde las fuentes.
- **Ruta y selección**: `GET /cv` (`cv.index`) con perfil opcional por query `?profile=ID`. La selección vive en la URL; no hay preselección, estado de servidor ni almacenamiento cliente.
- **Controlador y página dedicados**: `CvGeneratorController@index` renderiza `Cv/Index`. No se extiende `Profiles/Show`, que es configuración, no resultado.
- **Composición en servidor**: `App\Support\CvPreviewContent` arma el payload completo. Reutiliza `CvProfileContent::personalData()` y las cinco relaciones de `CvProfile`, que ya aplican el orden de SPEC-003. No se toca `CvProfileContent::content()`.
- **Datos propios y herencia**: título y resumen propios del perfil, sin fallback al Maestro; datos personales y de contacto heredados en lectura, con enlaces clicables. El nombre del perfil no se imprime en el CV.
- **Fidelidad de campos**: se serializan todos los campos exigidos por FR-011, incluidos `responsibilities`, `achievements` y `results`, que no forman parte del payload de `Profiles/Show`.
- **Orden**: el servidor entrega las cinco colecciones ya ordenadas por relevancia descendente y, en empate, por el orden del Maestro. La UI no reordena. Habilidades se muestran como lista plana con etiqueta de categoría para no romper el orden relevancia → categoría → nombre.
- **Metadatos invisibles**: `relevance` e `is_active` no se incluyen en el payload de la vista ni se muestran como contenido; solo gobiernan orden y disponibilidad.
- **Estados explícitos**: `no_active_profiles`, `selection_required`, `unavailable`, `ready` y `error`. Un perfil inactivo, inexistente o un query inválido produce `unavailable` con alternativas activas, no un 404. Un perfil sin datos produce `ready` con `has_data: false` y estado vacío fuera de la hoja.
- **Una sola plantilla, solo lectura**: `CvPreview` es la única plantilla, de una columna y con jerarquía semántica, sin selector, editor, exportación, impresión, descarga, PDF, CV documentado ni controles de orden. Los enlaces a las fuentes y las orientaciones viven fuera de la hoja.
- **Fallo controlado**: un error de carga o composición se registra con `report()`, no produce vista parcial y devuelve `status: "error"` con reintento; la selección se conserva en la URL.
- **Sin dependencias nuevas**: se usan Pest, Vitest, React Testing Library, componentes shadcn/ui y utilidades existentes.

### Diseño de la plantilla

`CvPreview` se renderiza como un documento de una sola columna y ancho de lectura, sobre la superficie de tarjeta existente, con:

- cabecera: nombre, línea de contacto y enlaces, título profesional y resumen;
- secciones separadas por una regla sutil, con encabezados `h3` y espaciado consistente;
- el título del rol, programa, curso o proyecto como texto principal y los metadatos secundarios;
- listas planas para habilidades y textos largos con saltos de línea preservados;
- sin imágenes, iconos decorativos, columnas laterales, cajas por elemento ni estilos de impresión.

La estética se apoya en los tokens y componentes existentes; no se agregan fuentes, temas ni dependencias. La calidad visual concreta queda sujeta a la revisión de Implement/QA, sin ampliar el alcance funcional.

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json` requiere `^8.3`) con Laravel 13.17; TypeScript 5.7 y React 19.2.

**Primary Dependencies**: Laravel 13, Inertia 3 (`inertiajs/inertia-laravel` + `@inertiajs/react`), React 19, Tailwind CSS 4, shadcn/ui sobre Radix UI, Vite 8 y Wayfinder. Desarrollo: Pest 4.7, Vitest (vía `vp test`) y React Testing Library. **No se agregan dependencias.**

**Storage**: SQLite local en `database/database.sqlite`; sin tablas nuevas. La vista es efímera y no se persiste.

**Testing**: Pest 4 (Feature con SQLite en memoria, `RefreshDatabase`) mediante `php artisan test`; Vitest + React Testing Library mediante `npm test`; verificación de tipos con `npm run types:check` y formato/lint con `npm run check` y `composer lint:check`.

**Target Platform**: aplicación web local (localhost) para navegador de escritorio, uso sin conexión, un solo usuario y un único Perfil Maestro.

**Project Type**: aplicación web única (monolito Laravel + SPA Inertia/React en el mismo repositorio); no se crea una API JSON separada.

**Performance Goals**: sin objetivos de rendimiento medibles. La aplicación es local y monousuario; una petición carga un perfil y sus cinco relaciones. No se introducen benchmarks, caché ni pruebas de carga.

**Constraints**: monousuario y sin autenticación; el Perfil Maestro es la única fuente de verdad; interfaz en español con acentos y caracteres especiales; sin generación de PDF, CV documentado, plantillas múltiples, exportación, impresión, IA, ATS, ofertas, postulaciones ni asociación documental; sin almacenamiento ni historial del CV; sin orden manual; sin migraciones ni dependencias nuevas.

**Scale/Scope**: 1 usuario; 1 Perfil Maestro; N perfiles de CV, de los cuales solo los activos se ofrecen; 1 ruta nueva; 1 controlador; 1 clase de soporte; 1 página + 2 componentes presentacionales + 1 archivo de tipos; 3 archivos de prueba nuevos; 0 tablas nuevas; FR-001 a FR-022.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio                                         | Estado | Evidencia del diseño                                                                                                                                                                                                                                        |
| ------------------------------------------------- | ------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| I. Fuente única de verdad                         | PASA   | La vista no copia ni persiste datos; lee el Maestro y las relaciones del perfil en cada consulta.                                                                                                                                                           |
| II. El CV es un resultado                         | PASA   | Vista derivada, de solo lectura, sin edición directa; toda corrección se hace en el perfil o en el Maestro mediante enlaces.                                                                                                                                |
| III. Separación datos/reglas/presentación         | PASA   | Datos en el Maestro; reglas de selección/relevancia en los pivotes de SPEC-003; composición en servidor; presentación en `CvPreview`; sin mezclar responsabilidades.                                                                                        |
| IV. MVP estrictamente acotado                     | PASA   | Alcance idéntico al spec: sin PDF, CV documentado, plantillas múltiples, IA, ATS ni postulaciones.                                                                                                                                                          |
| V. Perfil Profesional Maestro                     | PASA   | No se modifican sus entidades, campos, validaciones ni datos; solo se leen.                                                                                                                                                                                 |
| VI. Perfiles profesionales como configuración     | PASA   | Dos perfiles activos producen vistas distintas según su selección, relevancia, título y resumen; no se duplican datos.                                                                                                                                      |
| VII–XI. Documentos, evidencias y CV documentado   | N/A    | Fuera de alcance; no se asocian documentos ni evidencias al CV.                                                                                                                                                                                             |
| XII. Arquitectura simple para aplicación local    | PASA   | Laravel + Inertia/React + SQLite + HTML/CSS; sin servicios externos ni infraestructura adicional.                                                                                                                                                           |
| XIII. Evitar complejidad prematura                | PASA   | Una ruta, un controlador, una clase de composición y dos componentes; sin colas, caché, API separada ni patrones adicionales.                                                                                                                               |
| XIV. Datos relacionales, no contenido duplicado   | PASA   | Se reutilizan las relaciones y pivotes existentes; no se copian textos ni registros maestros.                                                                                                                                                               |
| XV. Administrador orientado a tareas              | PASA   | Ítem «Generador de CV» en la navegación y página con selector, vista y enlaces de corrección.                                                                                                                                                               |
| XVI. Vista previa antes de exportar               | PASA   | Esta capacidad entrega exactamente la vista previa; no genera PDF ni exporta.                                                                                                                                                                               |
| XVII. Impeccable como herramienta de UI/UX        | PASA   | Se usan los componentes y tokens existentes; no se introduce tooling ni dependencia de diseño.                                                                                                                                                              |
| XVIII. Una plantilla excelente antes que muchas   | PASA   | Una única plantilla `CvPreview`, sin selector ni variantes por perfil.                                                                                                                                                                                      |
| XIX. Cada Spec entrega una capacidad verificable  | PASA   | FR-001 a FR-022 y SC-001 a SC-008 con pruebas automatizadas y validación manual en `quickstart.md`.                                                                                                                                                         |
| XX. Las tareas no sustituyen a los Specs          | PASA   | El trabajo se descompone en Tasks dentro de este Spec; no se abren tareas funcionales independientes.                                                                                                                                                       |
| XXI. Pruebas proporcionales al riesgo             | PASA   | Feature tests de composición, exclusividad, orden, vigencia, disponibilidad, no persistencia y error; Vitest de plantilla, selector y estados.                                                                                                              |
| XXII. No romper datos existentes                  | PASA   | No hay migraciones ni escrituras de esquema; las lecturas no modifican datos existentes (el `firstOrCreate` de `Profile::current()` es comportamiento heredado de SPEC-001). `CvProfileContent::content()` y el comportamiento de SPEC-003 no se modifican. |
| XXIII. Privacidad local por defecto               | PASA   | Todos los datos permanecen en SQLite local; no hay llamadas externas ni envío de información.                                                                                                                                                               |
| XXIV. No almacenar secretos en código fuente      | PASA   | No se agregan credenciales, claves ni configuración sensible.                                                                                                                                                                                               |
| XXV. No expandir el MVP durante la implementación | PASA   | Sin exportación, impresión, PDF, CV documentado, historial, versionado, plantillas múltiples ni edición.                                                                                                                                                    |
| XXVI. Cambios importantes requieren decisión      | PASA   | No se cambia el stack, SQLite, autenticación, modelo del Maestro, edición del CV ni el número de plantillas.                                                                                                                                                |

**Resultado**: sin violaciones. No se requiere justificación en Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/004-generador-de-cv/
├── plan.md                  # Este archivo
├── research.md              # Fase 0: decisiones técnicas y alternativas descartadas
├── data-model.md            # Fase 1: vista derivada, fuentes, mapeo y estados
├── contracts/
│   └── http-contracts.md    # Fase 1: ruta, query, props Inertia, orden y garantías
├── quickstart.md            # Fase 1: pruebas y validación manual de extremo a extremo
├── spec.md                  # Entrada clarificada; no se modifica
└── tasks.md                 # Fase 2: lo crea Project Manager; no lo crea Plan
```

### Source Code (repository root)

```text
app/
├── Http/
│   └── Controllers/
│       └── CvGeneratorController.php      # nuevo: resuelve la selección y renderiza Cv/Index
└── Support/
    ├── CvProfileContent.php               # reutilizado; no se modifica
    └── CvPreviewContent.php               # nuevo: composición de la vista derivada

routes/
└── web.php                                # + GET /cv (cv.index)

resources/js/
├── components/
│   ├── CvGeneratorPanel.tsx               # nuevo: selector, estados y enlaces a fuentes
│   ├── CvPreview.tsx                      # nuevo: única plantilla de CV, solo lectura
│   └── __tests__/
│       ├── CvGeneratorPanel.test.tsx      # nuevo: selector y estados vacíos/error
│       └── CvPreview.test.tsx             # nuevo: jerarquía, campos, orden y ausencia de controles
├── layouts/
│   └── AppLayout.tsx                      # + ítem «Generador de CV»
├── lib/
│   └── cv-preview.ts                      # nuevo: tipos del payload y de la página
└── pages/
    └── Cv/
        └── Index.tsx                      # nuevo: página Inertia Cv/Index

tests/Feature/
└── CvGeneratorTest.php                    # nuevo: contrato del generador

database/
└── (sin cambios: no hay migraciones, seeders ni factories nuevos)
```

**Structure Decision**: se mantiene la aplicación única Laravel + Inertia/React de la raíz del repositorio. La vista se separa en una página contenedora (`Cv/Index`) y dos componentes presentacionales: `CvGeneratorPanel` concentra selector, estados y enlaces a fuentes; `CvPreview` contiene únicamente la plantilla del CV. Esta separación permite probar la plantilla y los estados sin montar Inertia y evita mezclar la configuración del perfil con el resultado. No se crea una API JSON ni un proyecto frontend separado (Principios XII–XIII). No se modifica `CvProfile`, `CvProfileContent::content()` ni el comportamiento de SPEC-003.

## Phase 0/1 Outputs

- **[research.md](./research.md)**: decisiones sobre derivación sin persistencia, ruta/query, controlador, clase de composición, orden, payload, metadatos invisibles, enlaces, estados, error, selector, plantilla, fechas, accesibilidad y pruebas. Sin `NEEDS CLARIFICATION`; incluye alternativas descartadas y riesgos.
- **[data-model.md](./data-model.md)**: definición de la vista derivada, fuentes, estructura del payload, mapeo campo a campo de las cinco clases, datos personales, datos propios, reglas de orden, matriz de estados, `has_data`, composición paso a paso y garantía de no persistencia.
- **[contracts/http-contracts.md](./contracts/http-contracts.md)**: ruta, query, props Inertia exactas, payload de cada estado, orden, garantías de no mutación, errores y límites de alcance.
- **[quickstart.md](./quickstart.md)**: comandos de prueba, advertencia de no usar `migrate:fresh` y flujo manual de validación de extremo a extremo.
- **tasks.md**: no se crea en esta fase; lo generará Project Manager a partir de este Plan y del spec clarificado.

## Requirement Coverage

| Requisito | Diseño                                                                                                                                                                                                                     | Prueba prevista                                                                                                |
| --------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| FR-001    | Ítem «Generador de CV» en `AppLayout`; `GET /cv` (`cv.index`); página `Cv/Index` con selector y vista en el mismo lugar.                                                                                                   | `Route::has('cv.index')`; prueba de página/panel; validación manual de navegación.                             |
| FR-002    | El servidor consulta solo `is_active = true`, ordena por `name` y envía `id`, `name` y `professional_title`; inactivos nunca se envían.                                                                                    | Feature: opciones solo activas, ordenadas, sin inactivas; Vitest: selector con las opciones activas recibidas. |
| FR-003    | Sin query o con query vacío: `status = selection_required`, `preview = null`; el selector no preselecciona.                                                                                                                | Feature: sin selección no hay preview; Vitest: placeholder seleccionado y ausencia de hoja.                    |
| FR-004    | Cada cambio de perfil navega a `/cv?profile=ID`; el servidor recompone desde cero; no hay caché ni estado cliente.                                                                                                         | Feature: dos perfiles con configuraciones distintas y cambio entre ambos; Vitest: re-render sin residuos.      |
| FR-005    | Query a perfil inactivo, inexistente o inválido: `status = unavailable`, `preview = null`, alternativas activas.                                                                                                           | Feature: perfil desactivado, ID inexistente y query inválido; Vitest: aviso y selector de alternativas.        |
| FR-006    | Composición exclusiva con Maestro y perfil seleccionado; orden fijo: datos/contacto, título, resumen, experiencia, educación, cursos, habilidades, proyectos.                                                              | Feature: payload completo y orden de secciones; Vitest: orden de encabezados.                                  |
| FR-007    | Se serializan solo las cinco relaciones incluidas; arreglos vacíos no generan sección; no se envían elementos no incluidos.                                                                                                | Feature: exclusión y secciones vacías; Vitest: no se renderizan encabezados vacíos.                            |
| FR-008    | Se reutilizan las relaciones ordenadas de `CvProfile` (relevancia descendente y desempate del Maestro); la UI no ordena; `relevance` no se envía.                                                                          | Feature: orden por clase, empates y estabilidad; `missing('relevance')`; Vitest: orden recibido sin reordenar. |
| FR-009    | `professional_title` y `summary` se leen solo de `cv_profiles`; vacíos se normalizan a `null`; no se consulta el título/resumen del Maestro.                                                                               | Feature: perfil sin título/resumen y Maestro con ambos; payload sin fallback.                                  |
| FR-010    | `CvProfileContent::personalData()` se reutiliza; campos vacíos se omiten en la plantilla; email/teléfono y enlaces clicables.                                                                                              | Feature: datos heredados y enlaces; Vitest: enlaces y omisión de campos vacíos.                                |
| FR-011    | Mapeo completo: experiencia con responsabilidades y logros; educación con grado y descripción; certificaciones con duración, categoría, código y validación; habilidades con categoría; proyectos con resultados y enlace. | Feature: payload con todos los campos; Vitest: render de cada campo y omisión de opcionales.                   |
| FR-012    | Una única plantilla `CvPreview`; no hay prop, selector ni variante de plantilla.                                                                                                                                           | Vitest: no existe selector de plantilla; validación manual.                                                    |
| FR-013    | `CvPreview` no contiene formularios, botones, campos ni acciones de guardado; es solo lectura.                                                                                                                             | Vitest: `article` sin `button`, `textbox`, `combobox` ni `form`.                                               |
| FR-014    | `source_links` apunta al perfil (`/profiles/{id}/edit`), al Maestro (`/profile`) y a las cinco secciones; se renderizan fuera de la hoja.                                                                                  | Feature: rutas exactas; Vitest: enlaces fuera del `article` y sin edición dentro.                              |
| FR-015    | Composición en cada `GET`; sin tabla, caché, sesión, archivo, historial ni servicio externo.                                                                                                                               | Feature: conteos y `updated_at` sin cambios; validación manual sin red.                                        |
| FR-016    | Las relaciones y `personalData()` se leen en vivo; una actualización o eliminación se refleja en la siguiente consulta.                                                                                                    | Feature: actualizar Maestro/perfil y eliminar un registro; la siguiente respuesta refleja el cambio.           |
| FR-017    | Sin perfiles activos: `status = no_active_profiles`, `profiles = []`, `preview = null`; estado vacío con enlace a `/profiles`.                                                                                             | Feature y Vitest: estado vacío y acción de gestión.                                                            |
| FR-018    | Perfil sin contenido o Maestro vacío: vista válida con lo disponible; `has_data = false` solo si no hay nada; orientación fuera de la hoja.                                                                                | Feature: perfil sin contenido, Maestro vacío y perfil sin datos; Vitest: estado vacío sin hoja.                |
| FR-019    | Error de carga o composición: `report()`, `status = error`, `preview = null`, alternativas best-effort y reintento con `router.reload()`.                                                                                  | Feature: composer falso que lanza excepción; Vitest: alerta, reintento y ausencia de vista parcial.            |
| FR-020    | Textos completos con `whitespace-pre-line` y `break-words`; sin `truncate`, `line-clamp` ni alturas fijas; desplazamiento natural.                                                                                         | Vitest: texto largo, acentos y caracteres especiales completos; validación manual de scroll.                   |
| FR-021    | Jerarquía h1 página → h2 CV → h3 secciones; selector nativo con etiqueta; enlaces con texto; foco visible; sin dependencia del color.                                                                                      | Vitest: roles y encabezados; validación manual con teclado y foco.                                             |
| FR-022    | Sin botones, enlaces ni rutas de exportación, impresión, descarga, PDF, CV documentado, plantilla, ATS, IA u ofertas.                                                                                                      | Vitest: ausencia de controles; Feature: solo `GET /cv`; validación manual.                                     |

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

Sin violaciones. No se requiere justificación de complejidad. La capacidad agrega una ruta, un controlador, una clase de composición, una página y dos componentes presentacionales; no agrega tablas, migraciones, dependencias, caché ni infraestructura.

## Post-Design Constitution Re-Check (Phase 1)

Re-evaluado tras generar `research.md`, `data-model.md`, `contracts/http-contracts.md` y `quickstart.md`:

- **I / II / III (fuente única, CV como resultado, separación)**: la vista se compone en memoria desde el Maestro y el perfil; no se copia, guarda ni edita; datos, reglas y presentación quedan separados.
- **V / VI / XIV (Maestro, perfiles, relacional)**: no se modifican entidades ni datos; se reutilizan las relaciones y pivotes de SPEC-003; no se duplica contenido.
- **XII / XIII (simplicidad)**: monolito Inertia, una ruta y dos componentes; cero dependencias nuevas y cero tablas.
- **XVI / XVIII (vista previa y plantilla)**: la entrega es la vista previa; una sola plantilla sin selector ni exportación.
- **XXI (pruebas)**: composición, exclusividad, orden, vigencia, no persistencia, disponibilidad, error y accesibilidad tienen cobertura Feature y/o Vitest.
- **XXII (no romper datos)**: no hay migraciones ni escrituras de esquema; `CvProfileContent::content()` y el comportamiento de SPEC-003 permanecen intactos.
- **XXV / XXVI (alcance y decisiones)**: no se agregan capacidades fuera del spec ni se cambian stack, autenticación, modelo del Maestro, edición del CV o número de plantillas.

Sin violaciones nuevas introducidas por el diseño.
