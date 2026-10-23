---
description: 'Task list for Generador de CV (SPEC-004)'
---

# Tasks: Generador de CV

**Feature**: `004-generador-de-cv`

**Branch**: `004-generador-de-cv`

**Input**: Documentos de diseño de `/specs/004-generador-de-cv/`: [spec.md](./spec.md), [plan.md](./plan.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/http-contracts.md](./contracts/http-contracts.md) y [quickstart.md](./quickstart.md).

**Estado**: Closed — 2026-10-23; 26/26 tareas; implementación y aceptación funcional/visual del MVP por el responsable; T025 con escenarios manuales 1–13 no ejecutados individualmente y documentados; integración en main mediante merge --no-ff (hash en el informe de cierre); sin push.

**Evidencia de esta fase**: T001–T026 completadas; T025 se cierra con aceptación funcional/visual del responsable (2026-10-23) y cobertura manual parcial documentada; T026 consolida la evidencia. Los comandos, resultados y limitaciones se registran en «Validation Evidence». No se ejecutaron operaciones Git en esta actualización; commit local y merge --no-ff autorizados y pendientes de ejecución; sin push.

**Prerequisites**:

- SPEC-004 clarificado (C1–C7); implementación autorizada y ejecutada en la rama `004-generador-de-cv`; la aceptación funcional/manual final sigue pendiente.
- Rama `004-generador-de-cv` coherente con `specs/004-generador-de-cv/` y `.specify/feature.json`; `AGENTS.md`, Constitución y ejecutor disponibles.
- **Gate obligatorio**: `python3 .specify/scripts/falexom-workflow.py check` en verde antes de cada fase y antes de cada escritura. Si falla, hay discrepancia de rama/SPEC, HEAD separado o trabajo ajeno en riesgo, detener la implementación y reportar; no continuar con escrituras.
- T001 acreditó `BASE_READY` y la disponibilidad de la base/rama; el cierre formal de SPEC-001/003 no se reabre ni se modifica en este encargo.
- Base local con el Perfil Maestro de SPEC-001 (puede estar vacío) y, para los escenarios de selección, dos perfiles de CV activos. No se usan datos reales para pruebas destructivas ni se ejecuta `migrate:fresh`, `migrate:refresh` o `db:wipe`.
- No se agregan dependencias de Composer ni de npm, no se crean tablas, migraciones, seeders ni factories, y este documento no autoriza operaciones de Git.

**Tests**: **Obligatorias, no opcionales.** Cada historia escribe primero sus pruebas con Pest y Vitest/React Testing Library, las ejecuta y registra el resultado antes de implementar. Se distingue expresamente la prueba que debe fallar de la prueba de regresión que puede pasar por funcionalidad ya construida; en ese caso no se fuerza un fallo artificial. Las pruebas tienen como objeto el contrato del generador (`GET /cv`, composición, plantilla y estados), no el CRUD, la validación, la unicidad, las cascadas de pivotes ni los presets ya cubiertos por SPEC-003.

**Organization**: tareas agrupadas por fase y por historia de usuario. Hay **un solo Developer secuencial**; la independencia de archivos no autoriza ejecución concurrente ni cambia el orden de dependencias. No se usan marcadores `[P]`: la independencia lógica se documenta en «Parallel Opportunities».

## Format: `[ID] [Fase/Story] Descripción`

- **[ID]**: identificador secuencial `T001…T026`.
- **[Fase/Story]**: `[Setup]`, `[US1]`, `[US2]`, `[US3]`, `[US4]` o `[Polish]`.
- Cada tarea indica **archivos afectados**, **trabajo concreto**, **dependencias relevantes** y **criterio verificable de finalización**.
- Las pruebas se escriben y ejecutan primero; el fallo esperado o la condición de regresión se registra antes de implementar.
- **Gate de escritura**: toda tarea que cree o modifique un archivo comienza con `python3 .specify/scripts/falexom-workflow.py check`; si falla, se detiene y se reporta. T001 establece el gate y las tareas de escritura lo repiten.
- **Sin `[P]`**: el Developer es único y secuencial; la ausencia de conflicto de archivos no implica paralelismo.

## Path Conventions

- **Backend**: `app/`, `routes/`, `tests/Feature/`.
- **Frontend**: `resources/js/`.
- **Especificación**: `specs/004-generador-de-cv/`.
- Aplicación única Laravel + Inertia/React; no se crea una API JSON separada ni un proyecto frontend independiente.
- No se agregan dependencias de Composer ni de npm.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: preparar la ruta, el esqueleto del controlador, los tipos compartidos y la navegación. Esta fase no implementa la composición ni las historias.

**Gate obligatorio de fase**: ejecutar `python3 .specify/scripts/falexom-workflow.py check` antes de la primera tarea y antes de cada escritura. Si falla, detener y reportar.

- [x] T001 [Setup] **Archivos**: ninguno (no escribe). **Trabajo**: ejecutar el gate de preparación con `python3 .specify/scripts/falexom-workflow.py check`; confirmar rama `004-generador-de-cv`, `FEATURE_DIR` (`specs/004-generador-de-cv/`), HEAD/MAIN y `BASE_READY`. Si falla o hay incoherencia, detenerse y reportar. **Dependencias**: ninguna; es el primer gate. **Criterio**: evidencia registrada; sin escrituras.
- [x] T002 [Setup] **Archivos**: `app/Http/Controllers/CvGeneratorController.php` (nuevo), `routes/web.php` (modificado) y `resources/js/layouts/AppLayout.tsx` (modificado). **Trabajo**: crear el controlador con `index()` que responda `abort(501)`; declarar `Route::get('/cv', [CvGeneratorController::class, 'index'])->name('cv.index')`; añadir el ítem «Generador de CV» en el arreglo `navigation` de `AppLayout` después de «Perfiles». Sin lógica de composición. **Dependencias**: T001. **Criterio**: `php artisan route:list --name=cv` muestra solo `GET /cv`; la navegación compila.
- [x] T003 [Setup] **Archivos**: `resources/js/lib/cv-preview.ts` (nuevo). **Trabajo**: definir los tipos del contrato (status, opciones, secciones, datos personales, `source_links` y props de página), reutilizando `CvProfilePersonalData`, `CvProfileLink`, `formatRange`, `formatYearMonth` y `SKILL_CATEGORY_LABELS`; no duplicar esos helpers ni crear un cliente HTTP. Ejecutar el checkpoint de Setup (`check`, `route:list --name=cv`, `npm run types:check`). **Dependencias**: T002. **Criterio**: tipos sin errores; una sola ruta GET; sin lógica de producto.

**Checkpoint**: ruta, controlador esqueleto, tipos y navegación listos; `route:list --name=cv` muestra solo `GET /cv`; `npm run types:check` pasa.

---

## Phase 2: User Story 1 - Generar la vista previa de un perfil activo (Priority: P1) 🎯

**Goal**: permitir seleccionar un perfil de CV activo y obtener una vista previa de solo lectura, compuesta con los datos vigentes del Perfil Maestro y la configuración del perfil, con una única plantilla.

**Why / Independent Test**: es el núcleo demostrable del generador. Con un Perfil Maestro con al menos un elemento de cada clase y un perfil activo con título, resumen y una selección con relevancias distintas, abrir `/cv`, seleccionar el perfil y comprobar que la vista incluye datos personales y de contacto, título y resumen propios, las cinco secciones con solo los elementos incluidos, el orden configurado, una única plantilla y ningún control de edición.

**Gate obligatorio de fase**: `python3 .specify/scripts/falexom-workflow.py check` antes de la primera tarea y antes de cada escritura.

### Tests for User Story 1 (obligatorias) ⚠️

> **Test-first US1**: escribir T004 y T005 y ejecutarlas **antes** de T006–T008. Confirmar el fallo de la composición, la plantilla y el panel; la comprobación de que la ruta existe puede pasar porque T002 ya la declaró. No implementar hasta haber registrado el fallo.

- [x] T004 [US1] **Archivos**: `tests/Feature/CvGeneratorTest.php` (nuevo). **Trabajo**: escribir el test-first backend con el contrato núcleo: ruta `cv.index`; `GET /cv?profile=ID` responde 200 y componente `Cv/Index`; solo perfiles activos ordenados por nombre con `id`, `name` y `professional_title`; sin selección no hay preview; perfil activo → `ready`; composición con datos personales heredados, título/resumen propios, cinco colecciones, solo incluidos, campos FR-011 (`responsibilities`, `achievements`, `results`, `degree_level`, `duration`, `category`, `code`, `validation_url`), orden por relevancia y desempate, ausencia de `relevance`/`master_path`/nombre de perfil/`is_active`/plantilla, sin fallback de título o resumen y sin persistencia. Crear el Maestro explícitamente; no exigir aún `source_links`. Ejecutar y confirmar el fallo antes de implementar. **Dependencias**: T003; debe fallar antes de T006. **Criterio**: test escrito y fallo registrado.
- [x] T005 [US1] **Archivos**: `resources/js/components/__tests__/CvPreview.test.tsx` y `resources/js/components/__tests__/CvGeneratorPanel.test.tsx` (nuevos). **Trabajo**: escribir el test-first frontend con: plantilla única en `article` con `h2`; si falta `full_name`, `h2` estructural «Currículum» visualmente oculto, sin inventar nombre; cabecera, secciones `h3` solo con contenido, campos y fechas FR-011, «actual»/«en curso», omisión de vacíos, orden recibido sin reordenar y sin controles de edición, plantilla, exportación, impresión o descarga; enlaces externos con `target="_blank"`/`rel="noreferrer"` solo cuando corresponda, nunca en `mailto:`/`tel:`; selector nativo accesible, solo perfiles activos, opción vacía «Seleccioná un perfil», sin preselección, formulario `GET /cv`, botón «Ver vista previa» y render de `CvPreview` cuando `status = ready`. Ejecutar y confirmar el fallo. **Dependencias**: T003; debe fallar antes de T007/T008. **Criterio**: tests escritos y fallo registrado.

### Implementation for User Story 1

- [x] T006 [US1] **Archivos**: `app/Support/CvPreviewContent.php` (nuevo) y `app/Http/Controllers/CvGeneratorController.php` (modificado). **Trabajo**: crear `CvPreviewContent` con el método de instancia `build(CvProfile): array`, no `final`, inyectable; cargar las cinco relaciones; mapear FR-011 completo; calcular `has_data`; no incluir `relevance`, `master_path`, nombre del perfil, `is_active` ni plantilla; no incluir todavía `source_links`; no modificar `CvProfileContent::content()`. Implementar `CvGeneratorController@index` con inyección, perfiles activos, query y estados `no_active_profiles`/`selection_required`/`unavailable`/`ready`; preview solo en `ready`; sin `try/catch` todavía; sin escrituras, caché, sesión ni cookies. **Dependencias**: T003 y T004 (fallo registrado). **Criterio**: los tests backend núcleo avanzan y el contrato de payload es correcto.
- [x] T007 [US1] **Archivos**: `resources/js/components/CvPreview.tsx` (nuevo). **Trabajo**: implementar la única plantilla de una columna y solo lectura; cabecera con nombre/contacto/título/resumen; si falta `full_name`, `h2` estructural «Currículum» visualmente oculto, sin inventar nombre; secciones `h3` solo con contenido en orden fijo; campos FR-011; etiquetas de categoría; fechas; enlaces externos con `target="_blank"`/`rel="noreferrer"` solo cuando corresponda, nunca en `mailto:`/`tel:`; sin formularios, botones, selects, edición, drag-and-drop, guardado, plantilla, exportación, impresión, descarga, PDF ni `@media print`; usar `whitespace-pre-line`/`break-words`, sin `truncate`/`line-clamp`/alturas fijas. **Dependencias**: T005 y T006. **Criterio**: tests de `CvPreview` en verde.
- [x] T008 [US1] **Archivos**: `resources/js/components/CvGeneratorPanel.tsx` y `resources/js/pages/Cv/Index.tsx` (nuevos). **Trabajo**: implementar el selector nativo en formulario `GET /cv`, etiqueta accesible, opción vacía, opciones activas con nombre — título, valor derivado de `selected_profile_id` y botón «Ver vista previa»; renderizar `CvPreview` en `ready` con `has_data`; página con `AppLayout` y `title="Generador de CV"`; sin estado cliente, fetch adicional ni caché. Los estados completos se abordan en US4. **Dependencias**: T005 y T007. **Criterio**: tests de panel núcleo y tipos en verde.
- [x] T009 [US1] **Archivos**: solo correcciones mínimas en los archivos de T006–T008 si una prueba lo exige; evidencia en este documento. **Trabajo**: ejecutar Feature, `CvPreview`, `CvGeneratorPanel` y `npm run types:check`; corregir solo lo necesario; registrar evidencia. **Dependencias**: T006–T008. **Criterio**: núcleo de vista previa, plantilla única, solo lectura y campos principales en verde.

**Checkpoint**: `CvGeneratorTest`, `CvPreview.test.tsx` y `CvGeneratorPanel.test.tsx` en verde; `npm run types:check` sin errores.

---

## Phase 3: User Story 2 - Cambiar de perfil y regenerar la vista (Priority: P2)

**Goal**: cambiar el perfil seleccionado y regenerar la vista con la configuración del nuevo perfil, sin residuos, mezclas ni copias almacenadas.

**Why / Independent Test**: con dos perfiles activos con selecciones, relevancias, títulos o resúmenes distintos, alternar entre ellos y comprobar que la vista se regenera por completo, que el mismo elemento cambia de posición si su relevancia difiere y que volver al primero reconstruye desde las fuentes.

**Gate obligatorio de fase**: `python3 .specify/scripts/falexom-workflow.py check` antes de la primera tarea y antes de cada escritura.

### Tests for User Story 2 (obligatorias) ⚠️

> **Test-first / regresión US2**: escribir T010 y ejecutarlo **antes** de T011. La prueba de dos perfiles puede pasar como **regresión** de la composición por petición ya construida en T006/T008; no se fuerza un fallo artificial. Si el panel conserva residuos o no refleja la nueva selección, el fallo se registra y T011 lo corrige.

- [x] T010 [US2] **Archivos**: `tests/Feature/CvGeneratorTest.php` y `resources/js/components/__tests__/CvGeneratorPanel.test.tsx` (ampliaciones). **Trabajo**: probar dos perfiles activos con títulos, resúmenes, selecciones y relevancias distintas; consultar A, B y A; sin residuos; un mismo elemento con relevancia distinta cambia de posición; `selected_profile_id` correcto; sin copia persistida; el selector refleja el perfil y vuelve a la opción vacía en `unavailable`; el envío es `GET /cv` con `profile`. **Dependencias**: T009. **Criterio**: tests escritos; regresión o fallo registrado; no forzar un fallo artificial.

### Implementation for User Story 2

- [x] T011 [US2] **Archivos**: `resources/js/pages/Cv/Index.tsx` y `resources/js/components/CvGeneratorPanel.tsx` (modificaciones). **Trabajo**: remontar el panel con una `key` basada en `selected_profile_id`/`status`; derivar siempre el valor del selector de `selected_profile_id`; sin estado cliente, `localStorage`, `sessionStorage`, IndexedDB ni caché; si T010 ya pasa, documentar la regresión y no introducir cambios artificiales. **Dependencias**: T010. **Criterio**: cambio de perfil sin residuos y selección derivada.
- [x] T012 [US2] **Archivos**: solo correcciones mínimas en los archivos de T011 si una prueba lo exige; evidencia en este documento. **Trabajo**: ejecutar Feature, panel y `npm run types:check`; corregir solo deficiencias reales; registrar evidencia. **Dependencias**: T011. **Criterio**: regeneración completa y sin residuos.

**Checkpoint**: pruebas de dos perfiles y selector en verde; sin residuos ni almacenamiento de la selección o del CV.

---

## Phase 4: User Story 3 - Mantener el CV como vista derivada y trazable (Priority: P2)

**Goal**: usar siempre los datos vigentes del Maestro y del perfil, reflejar actualizaciones y eliminaciones en la siguiente consulta y ofrecer enlaces identificables a las fuentes de corrección.

**Why / Independent Test**: abrir una vista, modificar datos personales y registros maestros, y el título, el resumen o la relevancia del perfil; volver a consultar y comprobar que todo cambio se refleja; eliminar un registro referenciado y comprobar que desaparece; seguir los enlaces a las fuentes y comprobar que no se creó ningún CV persistido.

**Gate obligatorio de fase**: `python3 .specify/scripts/falexom-workflow.py check` antes de la primera tarea y antes de cada escritura.

### Tests for User Story 3 (obligatorias) ⚠️

> **Test-first / regresión US3**: escribir T013 y ejecutarlo **antes** de T014–T015. Las pruebas de lectura viva y eliminación pueden pasar como **regresión** de la composición derivada; se registran como tales. La prueba de `source_links` debe fallar antes de T014.

- [x] T013 [US3] **Archivos**: `tests/Feature/CvGeneratorTest.php` y `resources/js/components/__tests__/CvGeneratorPanel.test.tsx` (ampliaciones). **Trabajo**: probar vigencia y trazabilidad: actualizar datos personales y un registro de cada clase; actualizar título, resumen, selección y relevancia del perfil; eliminar registros maestros y datos personales/enlaces; la siguiente consulta refleja el cambio; `source_links` exactos (`/profiles/{id}/edit`, `/profile`, `/experiences`, `/education`, `/certifications`, `/skills`, `/projects`); bloque «Corregir en las fuentes» fuera del `article`; sin copia obsoleta, historial ni persistencia. Los casos de lectura viva/eliminación pueden ser regresión; `source_links` debe fallar antes de T014. **Dependencias**: T012. **Criterio**: tests escritos; fallo de `source_links` registrado.
- [x] T014 [US3] **Archivos**: `app/Support/CvPreviewContent.php` y `resources/js/components/CvGeneratorPanel.tsx` (modificaciones). **Trabajo**: implementar `source_links` en `CvPreviewContent` y el bloque «Corregir en las fuentes» en el panel, fuera del `article`, con los siete enlaces y texto identificable; sin edición dentro de la hoja. **Dependencias**: T013. **Criterio**: tests de `source_links` en verde.
- [x] T015 [US3] **Archivos**: `app/Support/CvPreviewContent.php` y/o `app/Http/Controllers/CvGeneratorController.php` solo si T013 muestra una deficiencia real; ninguno si no. **Trabajo**: verificar que composición y `personalData()` se leen en cada petición, sin caché, sesión, cookie, `localStorage` ni payload reutilizado; ajustar solo si T013 muestra una deficiencia real; no modificar `CvProfileContent::content()` ni SPEC-003. **Dependencias**: T014. **Criterio**: vigencia, eliminación y no persistencia en verde o regresión documentada.
- [x] T016 [US3] **Archivos**: solo correcciones mínimas si una verificación lo exige; evidencia en este documento. **Trabajo**: ejecutar Feature, panel y `npm run types:check`; registrar evidencia. **Dependencias**: T015. **Criterio**: vigencia, eliminación, no persistencia y enlaces a fuentes coherentes.

**Checkpoint**: actualizaciones y eliminaciones reflejadas; `source_links` exactos y fuera de la hoja; sin copia persistida.

---

## Phase 5: User Story 4 - Entender la disponibilidad, los estados vacíos y los errores (Priority: P3)

**Goal**: explicar qué hacer cuando no hay un perfil disponible, falta selección, el perfil está inactivo o el query es inválido, no hay datos o falla la carga; sin inventar datos, sin vistas parciales y con accesibilidad básica.

**Why / Independent Test**: comprobar el generador sin perfiles activos, con todos inactivos, sin selección, con un perfil activo sin contenido, con un Perfil Maestro vacío, con un perfil sin ningún dato, con un query inválido y ante un fallo de carga; verificar que cada caso muestra orientación o error fuera del CV, sin datos inventados y sin presentar una vista parcial como válida.

**Gate obligatorio de fase**: `python3 .specify/scripts/falexom-workflow.py check` antes de la primera tarea y antes de cada escritura.

### Tests for User Story 4 (obligatorias) ⚠️

> **Test-first / regresión US4**: escribir T017 y T018 y ejecutarlas **antes** de T019–T020. Los estados `no_active_profiles`, `selection_required` y `unavailable` pueden pasar como **regresión** si T006 ya los resolvió; no se fuerza su fallo. Deben fallar las pruebas de error controlado, el estado vacío de UI con `has_data: false` y la accesibilidad no implementada; los cálculos de `has_data` pueden pasar como regresión de T006.

- [x] T017 [US4] **Archivos**: `tests/Feature/CvGeneratorTest.php` (ampliación). **Trabajo**: probar sin perfiles activos; sin selección; perfil inactivo/inexistente/query inválido; perfil desactivado tras consulta; perfil sin contenido con Maestro con datos; Maestro vacío con título/resumen propio; perfil sin ningún dato; error de composición con doble de `CvPreviewContent`; acreditar `status = error`, `preview = null` y ausencia de vista parcial; si se usa `Log::spy` o equivalente, confirmar que intercepta el reporte, sin exigir una implementación específica del logger; textos largos y acentos. Marcar como regresión lo que ya pase T006; error y vacío deben fallar. **Dependencias**: T016. **Criterio**: tests escritos; fallos esperados registrados; error acreditado con `status`, `preview` y ausencia de vista parcial.
- [x] T018 [US4] **Archivos**: `resources/js/components/__tests__/CvGeneratorPanel.test.tsx` y `resources/js/components/__tests__/CvPreview.test.tsx` (ampliaciones). **Trabajo**: probar `no_active_profiles` (`EmptyState` + enlace `/profiles`), `selection_required`, `unavailable`, `ready` con `has_data = false` (estado vacío fuera del `article`), error con alerta y reintento `router.reload()` conservando query, accesibilidad (`article` con nombre, `h2`→`h3`, si falta `full_name` un `h2` estructural «Currículum» visualmente oculto, sin inventar nombre, `label`, foco, enlaces, `target="_blank"`/`rel="noreferrer"` solo en enlaces externos y nunca en `mailto:`/`tel:`, sin dependencia del color) y textos largos/acentos sin truncar. **Dependencias**: T017 (orden de fase). **Criterio**: tests escritos; fallos de estados y accesibilidad registrados.

### Implementation for User Story 4

- [x] T019 [US4] **Archivos**: `app/Http/Controllers/CvGeneratorController.php` (modificación). **Trabajo**: implementar el error controlado con `try/catch (Throwable)` alrededor de carga, resolución y composición; `report($e)`; `status = error`, `preview = null`, perfiles/ID best effort; `Inertia::render` fuera del bloque; sin vista parcial. **Dependencias**: T017. **Criterio**: test de error en verde.
- [x] T020 [US4] **Archivos**: `resources/js/components/CvGeneratorPanel.tsx`, `resources/js/pages/Cv/Index.tsx` y `resources/js/components/CvPreview.tsx` (modificaciones). **Trabajo**: completar los estados `no_active_profiles`, `selection_required`, `unavailable`, `ready` con `has_data = false` y `error`/reintento; `article` accesible, `h2`/`h3`, si falta `full_name` un `h2` estructural «Currículum» visualmente oculto, sin inventar nombre, `label`, foco, enlaces, `target="_blank"`/`rel="noreferrer"` solo en enlaces externos y nunca en `mailto:`/`tel:`, sin dependencia del color, `whitespace-pre-line`/`break-words`; sin `truncate`/`line-clamp`/alturas fijas/scroll interno/print. **Dependencias**: T018 y T019. **Criterio**: tests de estados y accesibilidad en verde.
- [x] T021 [US4] **Archivos**: solo correcciones mínimas si una verificación lo exige; evidencia en este documento. **Trabajo**: ejecutar Feature, panel, preview y `npm run types:check`; corregir solo lo necesario; registrar evidencia. **Dependencias**: T019 y T020. **Criterio**: estados, error, reintento y accesibilidad en verde.

**Checkpoint**: estados vacíos/error y accesibilidad en verde; ninguna vista parcial se presenta como válida; sin datos inventados.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: auditoría final de alcance, suites completas, tipos, lint/formato, verificación de migraciones existentes y validación manual del quickstart.

**Gate obligatorio de fase**: `python3 .specify/scripts/falexom-workflow.py check` antes de la primera tarea y antes de cada escritura.

- [x] T022 [Polish] **Archivos**: `tests/Feature/CvGeneratorTest.php` (ampliación); sin cambios de producto. **Trabajo**: añadir la prueba negativa de alcance: `cv.index` existe; no existen `cv.export`/`cv.print`/`cv.download`/`cv.pdf`/`cv.template`/`cv.store`/`cv.update`/`cv.destroy`/`cv.regenerate`/`cv.reorder`; `POST`/`PUT`/`PATCH`/`DELETE` a `/cv` responden 405; la única ruta cuyo path exacto es `/cv` es `GET /cv`. Auditar `php artisan route:list --name=cv`, que debe mostrar solo `cv.index`; revisar `routes/web.php` para confirmar que la única ruta con path exacto `/cv` es `GET /cv`; si se usa `--path=cv`, filtrar por segmento exacto y no confundir `/profiles/{cvProfile}`. Revisar también controlador, composición (`app/Support/CvPreviewContent.php`), componentes, página y tipos para ausencia de exportación, impresión, PDF, CV documentado, plantilla múltiple, edición, ATS, IA y postulaciones. **Dependencias**: T021. **Criterio**: prueba verde y auditoría sin hallazgos.
- [x] T023 [Polish] **Archivos**: ninguno; auditoría de repositorio. **Trabajo**: comprobar que `git status --short -- database/migrations database/seeders database/factories` está vacío; que no hay migración de SPEC-004; que `CvProfileContent::content()` permanece intacto; que SPEC-001/003, modelos/controladores/pruebas existentes y `Profile.php` no cambian; que `composer.json`, `package.json` y lockfiles no cambian; y que `php artisan migrate:status` no muestra una migración nueva. Distinguir cambios ajenos preexistentes y registrar limitaciones si Git se deniega. **Dependencias**: T022. **Criterio**: auditoría registrada; sin cambios fuera de alcance.
- [x] T024 [Polish] **Archivos**: solo correcciones en archivos de SPEC-004 si una verificación falla. **Trabajo**: ejecutar `php artisan test`, `npm test`, `npm run types:check`, `npm run check` y `composer lint:check`; corregir solo archivos de SPEC-004; no formatear archivos ajenos. **Dependencias**: T022 y T023. **Criterio**: verificaciones de SPEC-004 en verde; el gate global queda rojo solo por `docs/roadmap.md` preexistente y fuera de alcance. **Observación**: `npm run check -- --no-fmt` pasa con 0 warnings/errores en 81 archivos; `npm run check -- --no-lint` solo reporta `docs/roadmap.md` preexistente/fuera de alcance; `docs/roadmap.md` no se modificó. PHPStan completo mantiene 16 errores preexistentes de SPEC-003; los archivos nuevos pasan con 0 errores.
- [x] T025 [Polish] **Archivos**: ninguno; evidencia en «Validation Evidence». **Trabajo**: ejecutar la validación manual de [quickstart.md](./quickstart.md), escenarios 1–13, en la aplicación local; registrar fecha, entorno y resultado; el escenario 14 se acredita con T017/T024. Si requiere navegador o confirmación humana y no se puede ejecutar, registrar `PENDIENTE_DE_CONFIRMACIÓN`; no declarar aprobado. **Dependencias**: T024. **Criterio**: evidencia 1–13 registrada o pendiente explícita. **Estado de cierre**: aceptación funcional y visual del MVP por el responsable (2026-10-23); los escenarios manuales 1–13 no se ejecutaron individualmente y no se declaran aprobados; el escenario 14 se acreditó por prueba automatizada; cobertura manual parcial documentada y aceptada para el cierre.
- [x] T026 [Polish] **Archivos**: `specs/004-generador-de-cv/tasks.md` (sección «Validation Evidence»). **Trabajo**: consolidar «Validation Evidence» con comandos, resultados, limitaciones, aceptación del responsable y estado de cierre; verificar 26 tareas, FR-001–FR-022, SC-001–SC-008 y ausencia de IDs antiguos incompatibles; dejar constancia de que el merge --no-ff está autorizado y pendiente de ejecución; no ejecutar push. **Dependencias**: T024 y T025. **Criterio**: evidencia consolidada, aceptación registrada y consistencia documental.

---

## Validation Evidence

> Evidencia consolidada por Project Manager el 2026-10-23 con resultados aportados por Implement/QA. No se declaran pruebas no ejecutadas; se registra la aceptación funcional/visual del responsable y la cobertura manual parcial.

**Fecha y entorno**: 2026-10-23; entorno local. Backend: PHP/Laravel con SQLite en memoria para tests. Frontend: Node/Vitest/jsdom.

| Ámbito                | Tareas / evidencia                                                            | Resultado                                                                                                                                                                                                                                                                                                                  | Fecha y entorno   | Limitaciones                                                                                                                                                                   |
| --------------------- | ----------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Setup                 | T001–T003: `falexom-workflow.py check`, `route:list --name=cv`, `types:check` | `check` exit 0; `BASE_READY=true`; HEAD `03af72a`; MAIN `63ab15b`; `route:list --name=cv` solo `GET`/`HEAD` `/cv`; `TSC_EXIT:0`                                                                                                                                                                                            | 2026-10-23; local | —                                                                                                                                                                              |
| US1                   | T004–T009: test-first backend/frontend, composición, plantilla y panel        | Fallo inicial esperado: 11 tests con 501 y frontend con imports ausentes; final Feature 11/11, 351 aserciones; Vitest 9/9; `TSC_EXIT:0`                                                                                                                                                                                    | 2026-10-23; local | Los fallos iniciales fueron los esperados por test-first; no son defectos residuales                                                                                           |
| US2                   | T010–T012: dos perfiles, regeneración y selector                              | Feature regresión 13/13, 456 aserciones; fallo real del panel en 2 tests por selector que conservaba el valor, corregido con `key`; panel final 5/5; `TSC_EXIT:0`                                                                                                                                                          | 2026-10-23; local | —                                                                                                                                                                              |
| US3                   | T013–T016: vigencia, eliminación, no persistencia y `source_links`            | Fallo inicial: `preview.source_links` inexistente; final Feature 18/18, 642 aserciones; panel 6/6; `TSC_EXIT:0`                                                                                                                                                                                                            | 2026-10-23; local | —                                                                                                                                                                              |
| US4                   | T017–T021: estados, error, reintento y accesibilidad                          | Fallo inicial: error 500 en vez de 200 controlado; final Feature 24/24, 763 aserciones; frontend 18/18; `TSC_EXIT:0`                                                                                                                                                                                                       | 2026-10-23; local | —                                                                                                                                                                              |
| Polish                | T022–T024: alcance, suite completa, tipos, lint y auditoría                   | T022 Feature 25/25, 785 aserciones; `php artisan test` 168/168, 2040 aserciones; `npm test` 15 archivos/51 tests; `types:check` 0 errores/561 archivos; `composer lint:check` Pint passed; `npm run check -- --no-fmt` 0 warnings/errores en 81 archivos; `npm run check -- --no-lint` solo `docs/roadmap.md` preexistente | 2026-10-23; local | Gate global rojo solo por `docs/roadmap.md` preexistente/fuera de alcance, no modificado; PHPStan completo 16 errores preexistentes de SPEC-003, archivos nuevos con 0 errores |
| Repositorio / alcance | T023: auditoría de repositorio                                                | Los seis documentos SPEC-004 formateados; modificados `routes/web.php` y `AppLayout.tsx`; sin cambios en SPEC-001/002/003, `CvProfileContent::content()`, modelos/controladores existentes, `composer.json`, `package.json`, lockfiles, migraciones, seeders ni factories; sin stage/commit/merge/push ejecutados           | 2026-10-23; local | `docs/roadmap.md` preexistente y fuera de alcance, no modificado                                                                                                               |
| QA independiente      | Revisión final de QA                                                          | PASS CON OBSERVACIONES; sin defectos funcionales residuales                                                                                                                                                                                                                                                                | 2026-10-23; local | Observaciones no bloqueantes; no sustituye la aceptación humana                                                                                                                |
| Quickstart / cierre   | T025: escenarios 1–13 y aceptación                                            | Aceptación funcional y visual del MVP por el responsable (2026-10-23); escenarios manuales 1–13 no ejecutados individualmente y documentados; escenario 14 acreditado por prueba automatizada (T017/T024); cobertura manual parcial aceptada para el cierre                                                                                                                                                                                                  | 2026-10-23; local | Escenarios 1–13 no ejecutados individualmente; no se declaran aprobados                                                                                                        |
| Consolidación         | T026: evidencia y consistencia documental                                     | 26 tareas `T001–T026`; 26 completadas; FR-001–FR-022 y SC-001–SC-008 conservados; aceptación del responsable y estado de cierre registrados                                                                                                                                                                                                        | 2026-10-23; local | SPEC cerrado documentalmente; merge --no-ff autorizado y pendiente de ejecución; sin push                                                                                       |
| Cierre formal | Aceptación del responsable y estado | Aceptación funcional/visual del MVP por el responsable (2026-10-23); 26/26 tareas; SPEC cerrado documentalmente; merge --no-ff autorizado y pendiente de ejecución; sin push | 2026-10-23; local | El hash del merge se registrará en el informe de cierre; no ejecutado aún |
| Observaciones preexistentes | `docs/roadmap.md` y PHPStan | `docs/roadmap.md` (formato) y 16 errores PHPStan de SPEC-003, no corregidos en este SPEC | 2026-10-23; local | Preexistentes y fuera del alcance de SPEC-004 |

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (T001–T003)**: comienza inmediatamente. T002 depende de T001; T003 depende de T002 y cierra el checkpoint de Setup.
- **US1 (T004–T009)**: depende de Setup. T004 y T005 son las pruebas y van antes de T006–T008; T009 es el checkpoint.
- **US2 (T010–T012)**: depende de US1 (T009). T010 va antes de T011; T012 es el checkpoint.
- **US3 (T013–T016)**: depende de US2 (T012) y de la composición de US1 porque comparte panel y página. T013 va antes de T014–T015; T016 es el checkpoint.
- **US4 (T017–T021)**: depende de US1 (estados del servidor) y de US2/US3 (panel, página y enlaces). T017 y T018 van antes de T019–T020; T021 es el checkpoint.
- **Polish (T022–T026)**: depende de todas las historias. T022 y T023 preceden a T024; T025 va después de T024; T026 cierra la evidencia.

### User Story Dependencies

- **US1**: no depende de otras historias; entrega el núcleo del generador (selección de un perfil activo y vista).
- **US2**: depende de US1; no cambia el contrato del servidor, sino que verifica y robustece el cambio de perfil sin residuos.
- **US3**: depende de US1 y US2 (T012) en este plan; consume la composición ya construida para demostrar vigencia, eliminación y enlaces a fuentes.
- **US4**: depende de US1 y de la UI de US2/US3; completa los estados de disponibilidad, vacío, error y accesibilidad.

### Critical Path

```text
T001 → T002 → T003 → T004/T005 → T006 → T007 → T008 → T009
     → T010 → T011 → T012
     → T013 → T014 → T015 → T016
     → T017 → T018 → T019 → T020 → T021
     → T022 → T023 → T024 → T025 → T026
```

- T005 se ejecuta después de T003 y antes de T006–T008 (rama de pruebas frontend); T006 depende de T004 y T007/T008 dependen también de T005; no se omite.
- No iniciar Polish hasta que las historias autorizadas tengan sus checkpoints en verde.
- No se avanza a la siguiente historia si el checkpoint anterior no está en verde o si el `check` de fase falla.

### Within Each User Story

- Las pruebas se escriben y se ejecutan primero; se registra el fallo esperado o la condición de regresión.
- Backend antes que la integración frontend: composición y controlador antes de la plantilla y la página.
- Cada historia termina en su checkpoint con los comandos indicados.
- Toda tarea de escritura comienza con `python3 .specify/scripts/falexom-workflow.py check`; si falla, se detiene.

### Parallel Opportunities

- **No hay paralelismo real**: el Developer es único y secuencial. La independencia es solo de archivos.
- T004 y T005 escriben archivos distintos y pueden prepararse en paralelo lógico; se ejecutan secuencialmente.
- T010 amplía dos archivos de prueba; T013 también; T017 y T018 escriben archivos distintos.
- T022 y T023 son verificaciones independientes en archivos, pero se ejecutan secuencialmente.
- T024 agrupa comandos de verificación; no se ejecutan en paralelo ni sustituyen el orden de dependencias.

---

## Implementation Strategy

### MVP First (P1)

1. Completar Setup (T001–T003).
2. Completar US1 (T004–T009) y validar su checkpoint: un perfil activo produce una vista derivada, solo lectura, con una única plantilla.
3. **Primer corte demostrable**: US1. Es el núcleo del generador; US2 y US3 amplían la confianza en el cambio de perfil y la vigencia, y US4 completa la robustez.

### Incremental Delivery

1. Setup → ruta, tipos y navegación.
2. US1 → composición y vista previa.
3. US2 → cambio de perfil sin residuos.
4. US3 → vigencia, eliminación y enlaces a fuentes.
5. US4 → estados vacíos, error, reintento y accesibilidad.
6. Polish → auditoría de alcance, suites completas, tipos, lint, migraciones y quickstart.

Cada incremento debe dejar las pruebas de las historias anteriores en verde y no modificar el Perfil Maestro, los perfiles de CV, sus asociaciones ni los documentos existentes.

### Checkpoints

- **Tras T003**: `route:list --name=cv` con una sola ruta `GET /cv`; `types:check` en verde.
- **Tras T009**: `CvGeneratorTest`, `CvPreview.test.tsx` y `CvGeneratorPanel.test.tsx` en verde; tipos sin errores.
- **Tras T012**: pruebas de dos perfiles y selector en verde; sin residuos.
- **Tras T016**: vigencia, eliminación y `source_links` en verde; sin persistencia.
- **Tras T021**: estados vacíos/error y accesibilidad en verde.
- **Tras T026**: evidencia automatizada y manual registrada; lo manual no confirmado queda pendiente, no aprobado.

---

## FR/SC Traceability

### Cobertura por historia

| Historia | Tareas    | Requisitos principales                                        |
| -------- | --------- | ------------------------------------------------------------- |
| US1      | T004–T009 | FR-001, FR-002, FR-003, FR-006–FR-013, FR-015, FR-020, FR-021 |
| US2      | T010–T012 | FR-004, FR-008, FR-015, SC-003, SC-008                        |
| US3      | T013–T016 | FR-009, FR-010, FR-014–FR-016                                 |
| US4      | T017–T021 | FR-005, FR-017–FR-022                                         |
| Polish   | T022–T026 | FR-012, FR-013, FR-015, FR-022, SC-005, SC-006, SC-007        |

### Requisitos funcionales

| Requisito | Tareas de implementación | Tareas de prueba / verificación |
| --------- | ------------------------ | ------------------------------- |
| FR-001    | T002, T008               | T005, T025                      |
| FR-002    | T006, T008               | T004, T005, T017                |
| FR-003    | T006, T008               | T004, T005, T017, T018          |
| FR-004    | T006, T011               | T010, T012                      |
| FR-005    | T006, T020               | T017, T018                      |
| FR-006    | T006, T007               | T004, T005                      |
| FR-007    | T006, T007               | T004, T005                      |
| FR-008    | T006, T007               | T004, T005, T010                |
| FR-009    | T006, T007               | T004, T005                      |
| FR-010    | T006, T007               | T004, T005                      |
| FR-011    | T006, T007               | T004, T005                      |
| FR-012    | T007                     | T005, T022                      |
| FR-013    | T007, T008               | T005, T022                      |
| FR-014    | T014, T020               | T013, T018                      |
| FR-015    | T006, T015               | T004, T010, T013                |
| FR-016    | T006, T015               | T013, T016                      |
| FR-017    | T006, T020               | T017, T018                      |
| FR-018    | T006, T020               | T017, T018                      |
| FR-019    | T019, T020               | T017, T018                      |
| FR-020    | T007, T020               | T005, T018                      |
| FR-021    | T007, T008, T020         | T005, T018                      |
| FR-022    | T002, T007, T008         | T005, T022, T023                |

### Criterios de éxito

| Criterio | Tareas de implementación | Tareas de prueba / verificación |
| -------- | ------------------------ | ------------------------------- |
| SC-001   | T006, T007               | T004, T005                      |
| SC-002   | T006, T007               | T004, T005, T010                |
| SC-003   | T006, T011               | T010, T012                      |
| SC-004   | T006, T015               | T013, T016                      |
| SC-005   | T007, T008, T015         | T005, T022, T023                |
| SC-006   | T006, T020               | T017, T018                      |
| SC-007   | T007, T008, T020         | T005, T018                      |
| SC-008   | T006, T008               | T004, T005, T010, T025          |

---

## Checkpoint final

**SPEC-004 cerrado documentalmente el 2026-10-23.** La aceptación funcional y visual del MVP fue otorgada por el responsable; T025 se cierra con cobertura manual parcial documentada: los escenarios 1–13 no se ejecutaron individualmente y no se declaran aprobados; el escenario 14 se acreditó por prueba automatizada.

- **Automatizado**: evidencia de T022–T024 en «Validation Evidence»: `php artisan test` 168/168, `npm test` 15 archivos/51 tests, `npm run types:check` 0 errores/561 archivos, `composer lint:check` Pint passed, `npm run check -- --no-fmt` 0 warnings/errores en 81 archivos y `npm run check -- --no-lint` solo `docs/roadmap.md` preexistente.
- **Auditoría**: solo `GET /cv`; sin exportación, impresión, descarga, PDF, CV documentado, plantilla múltiple, edición, ATS, IA ni postulaciones; sin tablas, migraciones, seeders ni factories nuevas; `CvProfileContent::content()` intacto; SPEC-001/002/003 sin cambios; sin dependencias nuevas.
- **Migraciones**: estado existente verificado, sin migración de SPEC-004 y sin operaciones destructivas.
- **Manual**: T025 cerrada por aceptación funcional/visual del responsable (2026-10-23) con cobertura manual parcial documentada; escenarios 1–13 no ejecutados individualmente y no declarados aprobados; escenario 14 acreditado por T017/T024.
- **Consolidación**: T026 completada; 26/26 tareas; aceptación del responsable y estado de cierre registrados.
- **Integración**: merge --no-ff a `main` autorizado y pendiente de ejecución; el hash se registrará en el informe de cierre; sin push.

La aceptación funcional/visual del MVP queda registrada. No se declaran ejecutados los escenarios manuales 1–13 ni se autoriza push.

---

## Notes

- **Pruebas obligatorias**: ninguna historia se considera terminada sin la evidencia de sus pruebas. Se diferencia prueba ejecutada, prueba pendiente y prueba bloqueada; no se inventan resultados.
- **Test-first con regresiones**: las pruebas que pueden pasar por funcionalidad ya construida se identifican como regresión y no se fuerza un fallo artificial. Las pruebas nuevas que deben fallar se ejecutan antes de la implementación correspondiente.
- **[P] sin paralelismo**: no se usan marcadores `[P]`; la independencia de archivos es solo lógica y no autoriza ejecución concurrente ni cambia el orden secuencial de un único Developer.
- **Integración Git**: el commit local y el merge --no-ff a `main` están autorizados y pendientes de ejecución; el hash del merge se registrará en el informe de cierre. No se autoriza push ni se ejecutaron operaciones Git en esta actualización documental.
- **Sin migraciones nuevas**: no se crean tablas, columnas, migraciones, seeders ni factories; no usar `migrate:fresh`, `migrate:refresh` ni `db:wipe`.
- **Sin dependencias nuevas**: no modificar `composer.json`, `package.json` ni lockfiles.
- **Sin cambios en SPEC-001/002/003**: no se reabren sus especificaciones, entidades, datos, rutas ni pruebas. `CvProfileContent::content()` permanece intacto; el generador usa una clase de composición nueva.
- **Alcance cerrado**: solo `GET /cv`; sin exportación, impresión, descarga, PDF, CV documentado, concatenación o asociación de documentos, múltiples plantillas, edición, drag-and-drop, guardado, historial, versionado, ATS, IA, ofertas ni postulaciones.
- **Cobertura manual parcial**: los escenarios 1–13 no se ejecutaron individualmente; el cierre se apoya en la aceptación funcional/visual del responsable y en el escenario 14 automatizado. No se declaran aprobados.
