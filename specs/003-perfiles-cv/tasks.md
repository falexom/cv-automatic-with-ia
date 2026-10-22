---
description: 'Task list for Perfiles de CV (SPEC-003)'
---

# Tasks: Perfiles de CV

**Feature**: `003-perfiles-cv`

**Branch**: `003-perfiles-cv`

**Input**: Documentos de diseño de `/specs/003-perfiles-cv/`: [spec.md](./spec.md), [plan.md](./plan.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/http-contracts.md](./contracts/http-contracts.md) y [quickstart.md](./quickstart.md).

**Prerequisites**:

- SPEC-003 aprobado y clarificado (D1–D3); Plan, research, data-model, contracts y quickstart completos.
- Rama `003-perfiles-cv` coherente con `specs/003-perfiles-cv/` y `.specify/feature.json`; `AGENTS.md`, Constitución y ejecutor disponibles.
- `python3 .specify/scripts/falexom-workflow.py check` en verde antes de cada fase y antes de cada escritura. Si falla, detener la implementación y reportar.
- Base local con el Perfil Maestro de SPEC-001 (puede estar vacío). No se reabre SPEC-001 ni SPEC-002.

**Tests**: **Obligatorias, no opcionales.** La especificación las exige en «Pruebas Requeridas» y en SC-001 a SC-006. En cada historia, las pruebas se escriben y se ejecutan primero para confirmar que fallan antes de implementar. No se marca una tarea o historia como terminada sin la evidencia de la prueba correspondiente.

**Organization**: tareas agrupadas por fase y por historia de usuario para permitir implementación y validación incrementales. Las dos historias P1 (US1 y US2) forman el MVP funcional; US3 y US4 son incrementos P2.

## Format: `[ID] [P?] [Story] Descripción`

- **[ID]**: identificador secuencial `T001…`.
- **[P]**: la tarea puede ejecutarse sin conflicto de archivos con otras tareas marcadas `[P]`. No implica ejecución concurrente: en esta capacidad hay **un solo Developer secuencial**.
- **[Story]**: `[US1]`, `[US2]`, `[US3]` o `[US4]`; `[Setup]` y `[Foundational]` identifican tareas compartidas.
- Cada tarea incluye la ruta exacta del archivo que crea o modifica y, cuando aplica, el comando de verificación.

## Path Conventions

- **Backend**: `app/`, `routes/`, `database/`, `tests/Feature/`.
- **Frontend**: `resources/js/`.
- **Especificación**: `specs/003-perfiles-cv/`.
- Aplicación única Laravel + Inertia/React; no se crea una API JSON separada ni un proyecto frontend independiente.
- No se agregan dependencias de Composer ni de npm.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: crear el esquema, el modelo, la factory, los tipos compartidos, las rutas explícitas y la navegación. Esta fase no implementa todavía la lógica de las historias.

- [x] T001 [Setup] Crear la migración aditiva `database/migrations/2026_10_22_000001_create_cv_profiles_and_content_tables.php`: tabla `cv_profiles` (`id`, `name` string(100), `normalized_name` string(100) único, `professional_title` string(255) nullable, `summary` text nullable, `is_active` boolean default `true`, timestamps); cinco pivotes `cv_profile_work_experience`, `cv_profile_education_entry`, `cv_profile_certification`, `cv_profile_skill` y `cv_profile_project` con PK compuesta `(cv_profile_id, <elemento>_id)`, FKs `cascadeOnDelete` hacia `cv_profiles` y hacia la tabla maestra correspondiente, `relevance` string(10) default `media`, sin `id`, sin timestamps y sin columnas de contenido copiado. `down()` elimina primero las pivotes y después `cv_profiles` solo como rollback de migración; la aplicación no expone eliminación de perfiles. No alterar tablas existentes, no insertar datos y no usar seeders. Verificar con `php artisan migrate --pretend`.
- [x] T002 [Setup] Crear `app/Models/CvProfile.php`: modelo `App\Models\CvProfile`, `$table = 'cv_profiles'`, `$guarded = []`, cast `is_active` a boolean, constantes canónicas `RELEVANCES = ['alta', 'media', 'baja']` y pesos de orden Alta=3, Media=2, Baja=1, y hook `saving` que calcule `normalized_name` con `App\Support\NameNormalizer::normalize()` conservando `name` para mostrar. No agregar relaciones todavía (T010) ni columnas/campos de datos maestros, documentos o CV.
- [x] T003 [Setup] Crear `database/factories/CvProfileFactory.php` para `CvProfile`: `name` único, `professional_title`, `summary` y `is_active`, con estados `active()` e `inactive()`; el `normalized_name` se calcula por el hook del modelo. La factory no crea pivotes ni registros maestros.
- [x] T004 [Setup] Crear `resources/js/lib/cv-profiles.ts` con los tipos y etiquetas compartidos de la capacidad: `Relevance = 'alta' | 'media' | 'baja'`, `RELEVANCE_LABELS`, `RELEVANCE_OPTIONS`, `CvProfileOwnData`, `CvProfileSummary`, `CvProfileOption`, `CvProfileOptions`, `CvProfileSelection`, `CvProfileContentItem`, `CvProfileShowProps` y `CvProfileFormProps`. Reutilizar `SKILL_CATEGORY_LABELS` de `resources/js/lib/skill-categories.ts` y `formatRange`/`formatYearMonth` de `resources/js/lib/dates.ts`; no crear un cliente HTTP ni duplicar etiquetas existentes.
- [x] T005 [P] [Setup] Escribir primero `resources/js/hooks/__tests__/use-current-url.test.ts` con los casos de límite de segmento: `/profile` es actual/padre de `/profile` pero no de `/profiles`; `/profiles` es padre de `/profiles/1/edit` y de `/profiles/create`; `/experiences` sigue siendo padre de `/experiences/1/edit`; una ruta absoluta se compara por su `pathname`. Ejecutar `npm test -- resources/js/hooks/__tests__/use-current-url.test.ts` y confirmar que falla antes de T006.
- [x] T006 [Setup] Corregir `resources/js/hooks/use-current-url.ts`: considerar una ruta como padre solo si coincide exactamente o si el siguiente carácter es `/`, sin cambiar la comparación exacta de `isCurrentUrl` ni el comportamiento de las secciones existentes. Ejecutar de nuevo T005 y dejar el test en verde.
- [x] T007 [Setup] Declarar rutas explícitas en `routes/web.php` y crear el esqueleto de `app/Http/Controllers/CvProfileController.php`: `GET /profiles` (`profiles.index`), `GET /profiles/create` (`profiles.create`), `POST /profiles` (`profiles.store`), `POST /profiles/initial` (`profiles.initial`), `GET /profiles/{cvProfile}` (`profiles.show`), `GET /profiles/{cvProfile}/edit` (`profiles.edit`), `PUT /profiles/{cvProfile}` (`profiles.update`), `POST /profiles/{cvProfile}/activate` (`profiles.activate`) y `POST /profiles/{cvProfile}/deactivate` (`profiles.deactivate`). Declarar `/profiles/create` y `/profiles/initial` antes de `/profiles/{cvProfile}`. **No usar `Route::resource`, no declarar `DELETE` y no crear `profiles.destroy` ni un método `destroy`**; el esqueleto del controlador solo contiene las nueve acciones con `abort(501)` y sin lógica de negocio. Verificar con `php artisan route:list --name=profiles`.
- [x] T008 [Setup] Agregar el ítem «Perfiles» en `resources/js/layouts/AppLayout.tsx`, después de «Documentos», apuntando a `/profiles`; no cambiar el resto de la navegación ni el cálculo de ítem activo.

**Checkpoint**: esquema, modelo, factory, tipos, rutas y navegación listos. `php artisan route:list --name=profiles` muestra las nueve rutas y ninguna `DELETE`; el test de `use-current-url` está en verde.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: relaciones, orden, validación compartida, props Inertia y sincronización transaccional. Es la base común que evita duplicar lógica entre historias.

**⚠️ CRITICAL**: ninguna historia comienza hasta completar T001–T013. T001–T008 preparan esquema, modelo, tipos y rutas; T009–T013 completan la base compartida de orden, validación, props y sincronización, y T011 se reutiliza ya en US1. El checkpoint de la fase exige toda la base para evitar retrabajo y asegurar una sola semántica de orden y persistencia.

- [x] T009 [P] [Foundational] Agregar a `app/Models/Skill.php` el scope aditivo `scopeMasterOrder(Builder $query): Builder`: ordenar por categoría con `CASE` en el orden `tecnica`, `funcional`, `metodologica`, `herramienta`, `tecnologia` y luego por `name` ascendente. Reutilizar `Skill::CATEGORIES`; no modificar `SkillController@index` ni el comportamiento de SPEC-001. Verificar que `php artisan test tests/Feature/SkillTest.php` sigue en verde.
- [x] T010 [Foundational] Agregar a `app/Models/CvProfile.php` las cinco relaciones `belongsToMany`: `workExperiences()` con pivote `cv_profile_work_experience`, `educationEntries()` con `cv_profile_education_entry`, `certifications()` con `cv_profile_certification`, `skills()` con `cv_profile_skill` y `projects()` con `cv_profile_project`. Todas con `withPivot('relevance')`, sin timestamps, ordenadas por relevancia descendente (`CASE` Alta=3, Media=2, Baja=1 sobre la tabla pivote) y, en empate, por el orden del Maestro: `chronological()` en experiencia, educación, certificaciones y proyectos; `masterOrder()` en habilidades. No agregar FK `profile_id` ni columnas de orden manual.
- [x] T011 [P] [Foundational] Crear `app/Http/Requests/Concerns/ValidatesCvProfile.php` con la validación compartida: reglas de `name` (`required`, `string`, `max:100`), `professional_title` (`nullable`, `string`, `max:255`) y `summary` (`nullable`, `string`); reglas de contenido para las cinco clases (`sometimes`, `array`, `*.id` `required|integer|distinct` y `exists` restringido al `profile_id` del Perfil Maestro actual, `*.relevance` `sometimes|in:alta,media,baja`); y mensajes en español, incluido el de pertenencia al Maestro. La trait debe poder usarse desde `StoreCvProfileRequest` y `UpdateCvProfileRequest` sin duplicar reglas.
- [x] T012 [Foundational] Crear `app/Support/CvProfileContent.php` con la base de props Inertia: `options()` devuelve las cinco claves `work_experiences`, `education_entries`, `certifications`, `skills` y `projects` con los campos de etiqueta del contrato, el orden del Maestro y solo registros de `Profile::current()`; `selections(CvProfile $profile)` devuelve `id` y `relevance` por clase; `content(CvProfile $profile)` devuelve las cinco colecciones ordenadas con campos de presentación y `relevance`; el `master_path` de procedencia se añade en T036 (US3); `personalData()` devuelve solo los datos personales y enlaces vigentes del Maestro, con `has_data`, **excluyendo `professional_title` y `summary` del Maestro**. No escribir en el Maestro ni almacenar copias.
- [x] T013 [Foundational] Completar `app/Support/CvProfileContent.php` con la escritura transaccional: `create(array $data): CvProfile` y `update(CvProfile $profile, array $data): void` que ejecutan `DB::transaction`, actualizan solo los datos propios del perfil y sincronizan las cinco clases con `sync`; `relevance` omitida se guarda como `media`; un arreglo omitido equivale a vacío, reincluir actualiza la relevancia existente sin duplicar y un elemento ausente se desasocia solo de ese perfil. No crear, editar ni eliminar registros maestros y no eliminar perfiles.

**Checkpoint**: relaciones, orden, validación, props y sincronización listos. `php artisan test tests/Feature/SkillTest.php` sigue en verde y no hay cambios en datos maestros.

---

## Phase 3: User Story 1 - Crear y mantener perfiles de CV (Priority: P1) 🎯

**Goal**: permitir crear, consultar, editar, activar y desactivar perfiles con nombre, título y resumen propios, sin eliminación definitiva y sin modificar el Perfil Maestro.

**Independent Test**: crear dos perfiles, editar sus datos propios, comprobar el rechazo de un nombre duplicado normalizado, desactivar y reactivar uno, verificar que el contenido se conserva y que el Perfil Maestro y el otro perfil no cambian.

### Tests for User Story 1 (obligatorias) ⚠️

> **Escribir primero T014 y ejecutarla. Debe fallar antes de implementar T015–T023.**

- [x] T014 [US1] Escribir `tests/Feature/CvProfileTest.php` con pruebas de: índice vacío, con perfiles activos/inactivos y con todos inactivos; creación con `name`, `professional_title` y `summary`, activa por defecto; nombre obligatorio, vacío y solo espacios rechazados; duplicado normalizado por mayúsculas/espacios, incluido un perfil inactivo; renombrar al mismo nombre normalizado permitido; actualización a un nombre duplicado rechazada; título y resumen propios sin modificar el Perfil Maestro; perfil sin título/resumen válido; `show` de un perfil y 404 de uno inexistente; desactivación que conserva datos y asociaciones, perfil inactivo visible y editable; reactivación reversible; persistencia entre dos peticiones y conservación de acentos/caracteres especiales. Ejecutar `php artisan test tests/Feature/CvProfileTest.php` y confirmar el fallo.
- [x] T015 [US1] Crear `app/Http/Requests/StoreCvProfileRequest.php` y `app/Http/Requests/UpdateCvProfileRequest.php` usando la trait de T011 solo para las reglas de datos propios; normalizar `professional_title` y `summary` vacíos a `null`; la unicidad se resuelve en el controlador. No incluir todavía las reglas de contenido (T027).
- [x] T016 [US1] Implementar en `app/Http/Controllers/CvProfileController.php` las acciones `index()` y `create()`: `index` devuelve todos los perfiles (activos e inactivos) ordenados por `name`, con `id`, `name`, `professional_title`, `is_active` y `can_create_initial_profiles` calculado por conteo; `create` renderiza `Profiles/Create`. Sin datos maestros ni contadores de contenido.
- [x] T017 [US1] Implementar en `app/Http/Controllers/CvProfileController.php` la acción `store()`: validar con `StoreCvProfileRequest`, comprobar unicidad de `normalized_name` incluyendo inactivos y devolver el error de nombre correspondiente, crear el perfil activo y solo con datos propios, redirigir a `profiles.show` con toast de éxito. No procesar selecciones de contenido en esta historia.
- [x] T018 [US1] Implementar en `app/Http/Controllers/CvProfileController.php` la acción `show()`: devolver `profile` con `id`, `name`, `professional_title`, `summary` e `is_active`; no incluir todavía datos heredados ni contenido (US2/US3). El implicit binding debe producir 404 para un perfil inexistente.
- [x] T019 [US1] Implementar en `app/Http/Controllers/CvProfileController.php` las acciones `edit()` y `update()`: `edit` devuelve los datos propios del perfil; `update` valida con `UpdateCvProfileRequest`, revalida la unicidad excluyendo el propio perfil, actualiza solo `name`, `professional_title` y `summary`, y redirige a `profiles.show` con toast. No cambia `is_active` ni procesa contenido.
- [x] T020 [US1] Implementar en `app/Http/Controllers/CvProfileController.php` las acciones `activate()` y `deactivate()`: cambiar solo `is_active` a `true`/`false`, ser idempotentes, conservar datos y asociaciones, redirigir a `profiles.index` con toast y no ofrecer ninguna acción de eliminación.
- [x] T021 [US1] Crear `resources/js/pages/Profiles/Index.tsx`: listar todos los perfiles con nombre, título propio (o «Sin título propio»), `Badge` de estado y acciones Ver/Editar/Activar o Desactivar; estado vacío con orientación y «Crear perfil»; enlace a `/profiles/create`. No incluir botón, icono ni acción de eliminación.
- [x] T022 [US1] Crear `resources/js/components/forms/CvProfileForm.tsx` con los campos propios `name`, `professional_title` y `summary`, errores por campo y conservación de valores; crear `resources/js/pages/Profiles/Create.tsx` y `resources/js/pages/Profiles/Edit.tsx` usando el formulario compartido y `useForm` para `POST /profiles` y `PUT /profiles/{id}`. No incluir campos de datos personales ni de registros maestros.
- [x] T023 [US1] Crear `resources/js/pages/Profiles/Show.tsx` con los datos propios, el estado activo/inactivo y enlaces a Editar y Volver; sin datos heredados, contenido, vista previa de CV ni acción de eliminación. US2 añade las props de contenido en el controlador y US3 amplía esta página con la interfaz de herencia y contenido.
- [x] T024 [US1] Ejecutar `php artisan test tests/Feature/CvProfileTest.php` y `npm run types:check`; corregir solo lo necesario para dejar US1 en verde. **Checkpoint US1**: creación, edición, consulta, activación/desactivación y unicidad funcionan de forma independiente.

---

## Phase 4: User Story 2 - Seleccionar, priorizar y reutilizar contenido (Priority: P1) 🎯

**Goal**: permitir seleccionar explícitamente elementos de las cinco clases, asignarles relevancia, ordenar por relevancia y orden del Maestro, y reutilizar el mismo elemento en varios perfiles con configuración independiente.

**Independent Test**: con al menos un elemento maestro de cada clase, crear dos perfiles, comprobar que no incluyen nada por defecto, incluir elementos de las cinco clases, asignar relevancia, verificar agrupamiento y orden, desasociar en un perfil sin alterar el Maestro ni el otro perfil, y rechazar IDs ajenos o duplicados.

### Tests for User Story 2 (obligatorias) ⚠️

> **Escribir primero T025 y T026; ejecutarlas y confirmar el fallo antes de implementar T027–T032.**

- [x] T025 [P] [US2] Escribir `tests/Feature/CvProfileContentTest.php` con pruebas de: perfil nuevo sin pivotes y con `selections` vacías; solo los elementos incluidos aparecen y los no incluidos no se muestran; inclusión de un elemento de cada una de las cinco clases; relevancia `media` por defecto y valores `alta`/`baja`; rechazo de relevancia inválida; orden por relevancia descendente; empate por orden del Maestro (más reciente primero, sin fecha al final) en experiencia, educación, certificaciones y proyectos; habilidades por categoría (`tecnica`, `funcional`, `metodologica`, `herramienta`, `tecnologia`) y luego nombre; reutilización del mismo elemento en dos perfiles con relevancia distinta; cambio y desasociación en un perfil sin afectar al otro ni al Maestro; rechazo de ID inexistente, ID de otra clase, ID de otro Perfil Maestro y ID duplicado en el mismo payload; reinclusión que actualiza la asociación sin duplicar; actualización por reemplazo completo de una clase; un arreglo omitido se interpreta como vacío; el payload no crea ni modifica registros maestros; esquema de pivotes con solo IDs y `relevance`, PK compuesta y sin columnas de contenido u orden; y error de validación que no deja asociaciones parciales. Ejecutar `php artisan test tests/Feature/CvProfileContentTest.php` y confirmar el fallo.
- [x] T026 [P] [US2] Escribir `resources/js/components/__tests__/CvProfileForm.test.tsx` con pruebas de: las cinco secciones se renderizan; ningún elemento está preseleccionado en un perfil nuevo; al marcar un elemento aparece el selector de relevancia con «Media» por defecto; cambiar a «Alta» y desmarcar lo excluye; una sección sin opciones muestra orientación y enlace a la sección maestra; los errores recibidos se muestran junto al campo o elemento y los valores escritos se conservan; no existen controles de arrastre, reordenamiento ni eliminación. Ejecutar `npm test -- resources/js/components/__tests__/CvProfileForm.test.tsx` y confirmar el fallo.

### Implementation for User Story 2

- [x] T027 [US2] Extender `app/Http/Requests/StoreCvProfileRequest.php` y `app/Http/Requests/UpdateCvProfileRequest.php` para combinar las reglas de contenido de `ValidatesCvProfile` (cinco arreglos, `*.id` con `exists` restringido al Maestro actual, `distinct`, `*.relevance` con `in:alta,media,baja`) y sus mensajes. No permitir campos de datos personales, `normalized_name`, `is_active` ni datos maestros en el payload.
- [x] T028 [US2] Actualizar `app/Http/Controllers/CvProfileController.php` (`store()` y `update()`) para pasar los arreglos validados a `CvProfileContent::create()`/`update()` y guardar perfil y asociaciones en una sola transacción; un fallo de validación o de persistencia no debe dejar perfil ni pivotes parciales. Conservar la comprobación de unicidad y las redirecciones de US1.
- [x] T029 [US2] Actualizar `app/Http/Controllers/CvProfileController.php` (`create()` y `edit()`) para enviar `options` desde `CvProfileContent::options()` y, en `edit`, `selections` desde `CvProfileContent::selections()`. `create` no debe enviar ninguna selección preseleccionada.
- [x] T030 [US2] Actualizar `app/Http/Controllers/CvProfileController.php` (`show()`) para incluir `content` con las cinco colecciones ordenadas, su `relevance` y los campos de presentación necesarios; no incluir todavía `personal_data` ni `master_path` (US3). La UI no debe reordenar.
- [x] T031 [US2] Ampliar `resources/js/components/forms/CvProfileForm.tsx` con las cinco secciones de selección: checkbox por opción, selector de relevancia Alta/Media/Baja con «Media» por defecto, etiqueta y período de cada opción, estados vacíos con enlace a la sección maestra y errores anidados; enviar los arreglos completos por clase. No permitir editar registros maestros, ordenar manualmente ni eliminar perfiles.
- [x] T032 [US2] Actualizar `resources/js/pages/Profiles/Create.tsx` y `resources/js/pages/Profiles/Edit.tsx` para recibir y pasar `options` y `selections` al formulario, con selecciones vacías en creación y las existentes en edición; conservar el envío de datos propios y la redirección del servidor.
- [x] T033 [US2] Ejecutar `php artisan test tests/Feature/CvProfileContentTest.php`, `npm test -- resources/js/components/__tests__/CvProfileForm.test.tsx` y `npm run types:check`; corregir solo lo necesario. **Checkpoint US2**: selección, relevancia, orden, reutilización y aislamiento funcionan de forma independiente.

---

## Phase 5: User Story 3 - Consultar el perfil con datos heredados y contenido agrupado (Priority: P2)

**Goal**: consultar un perfil con su título y resumen propios, los datos personales heredados del Maestro en modo lectura y el contenido agrupado, ordenado, con relevancia y procedencia.

**Independent Test**: abrir un perfil con contenido, comprobar que los datos personales heredados son los del Maestro y no editables, salvo el título propio; actualizar un dato personal y un registro profesional en el Maestro y verificar que el perfil refleja el cambio; revisar agrupamiento, relevancia, orden, procedencia y estados vacíos.

### Tests for User Story 3 (obligatorias) ⚠️

> **Escribir primero T034 y T035; ejecutarlas y confirmar el fallo antes de implementar T036–T038.**

- [x] T034 [P] [US3] Ampliar `tests/Feature/CvProfileTest.php` con pruebas de: `personal_data` igual a los datos personales y enlaces vigentes del Maestro; ausencia de `professional_title` y `summary` del Maestro en `personal_data`; `has_data = false` con Maestro vacío; título propio usado en el perfil; título propio vacío sin fallback al Maestro; cambio del título del Maestro que no se muestra en el perfil; actualización de un dato personal del Maestro reflejada sin sincronización; un dato personal eliminado en el Maestro deja de mostrarse; perfil inactivo consultable; y ausencia de campos editables de datos heredados en `edit`. Ejecutar `php artisan test tests/Feature/CvProfileTest.php` y confirmar el fallo.
- [x] T035 [P] [US3] Ampliar `tests/Feature/CvProfileContentTest.php` con pruebas de: `show` devuelve las cinco secciones agrupadas y ordenadas con `relevance` y `master_path` correcto por clase; perfil sin contenido devuelve cinco arreglos vacíos y sigue siendo consultable; perfil inactivo conserva su contenido; actualización de un registro maestro reflejada sin sincronización; eliminación de un registro maestro de cada clase que elimina solo su asociación, conserva el perfil y las demás asociaciones, y no devuelve copias obsoletas. Ejecutar `php artisan test tests/Feature/CvProfileContentTest.php`; las pruebas de `master_path` deben fallar antes de T036 y las de cascadas/estados vacíos actúan como regresión de lo ya construido.
- [x] T036 [US3] Actualizar `app/Support/CvProfileContent.php` y `app/Http/Controllers/CvProfileController.php` (`show()`) para agregar `master_path` a cada elemento de `content` e incluir `personal_data` desde `CvProfileContent::personalData()`, manteniendo `profile` y el resto de `content`; asegurar que el payload de `edit` no expone campos editables de datos personales ni el título/resumen del Maestro.
- [x] T037 [US3] Actualizar `resources/js/pages/Profiles/Show.tsx` para mostrar título y resumen propios, estado activo/inactivo y datos personales heredados en modo lectura; usar `has_data` para mostrar orientación cuando el Maestro esté vacío; no mostrar el título del Maestro como fallback ni ofrecer edición de datos heredados.
- [x] T038 [US3] Completar `resources/js/pages/Profiles/Show.tsx` con las cinco secciones de contenido agrupadas, `Badge` de relevancia, período/description cuando corresponda, enlace de procedencia `master_path` y estados vacíos con orientación; renderizar el orden recibido sin ordenar, agrupar por relevancia ni reordenar secciones. Mostrar aviso y acción Reactivar para perfiles inactivos; no incluir vista previa de CV, PDF, exportación ni eliminación.
- [x] T039 [US3] Ejecutar `php artisan test tests/Feature/CvProfileTest.php`, `php artisan test tests/Feature/CvProfileContentTest.php` y `npm run types:check`; corregir solo lo necesario. **Checkpoint US3**: herencia, título propio, procedencia, agrupamiento, orden y estados vacíos funcionan de forma independiente.

---

## Phase 6: User Story 4 - Disponer de los perfiles iniciales Analista y Programador (Priority: P2)

**Goal**: crear los perfiles iniciales «Analista» y «Programador» mediante una acción explícita, activos, sin resumen ni contenido, sin recreación ni sobrescritura.

**Independent Test**: sin perfiles existentes, ejecutar la acción explícita y comprobar los dos perfiles activos con sus títulos, sin resumen ni contenido; ejecutar de nuevo y comprobar que no se duplican ni sobrescriben; renombrar y desactivar uno y comprobar que no reaparece automáticamente.

### Tests for User Story 4 (obligatorias) ⚠️

> **Escribir primero T040 y ejecutarla. Debe fallar antes de implementar T041–T042.**

- [x] T040 [US4] Escribir `tests/Feature/CvProfileInitialProfilesTest.php` con pruebas de: la migración y el índice no crean perfiles automáticamente; `POST /profiles/initial` crea exactamente «Analista» con título «Analista de Sistemas» y «Programador» con título «Programador / Desarrollador», ambos activos, con `summary = null` y sin pivotes; redirección a `profiles.index` con toast; segunda ejecución sin duplicados ni sobrescritura; renombrar o desactivar un preset y volver a ejecutar sin recrearlo; un perfil personalizado existente bloquea la acción; `can_create_initial_profiles` es `true` solo cuando no hay ningún perfil. Ejecutar `php artisan test tests/Feature/CvProfileInitialProfilesTest.php` y confirmar el fallo.
- [x] T041 [US4] Implementar en `app/Http/Controllers/CvProfileController.php` la acción `storeInitial()`: comprobar que no existe ningún perfil; si existe, no escribir y redirigir con toast informativo; si no existe, crear en una transacción los dos presets con los valores exactos, activos, sin resumen ni contenido, y redirigir a `profiles.index` con toast de éxito. No recrear, sobrescribir ni duplicar; no crear automáticamente en migraciones, seeders ni al abrir la aplicación.
- [x] T042 [US4] Ajustar en `app/Http/Controllers/CvProfileController.php` la acción `index()` para que `can_create_initial_profiles` sea exactamente `CvProfile::query()->doesntExist()` y actualizar `resources/js/pages/Profiles/Index.tsx` para mostrar «Crear perfiles iniciales» solo cuando ese prop sea `true`, enviando `POST /profiles/initial`; el estado vacío debe ofrecer «Crear perfil» y la acción de presets. No mostrar la acción si ya existe cualquier perfil.
- [x] T043 [US4] Ejecutar `php artisan test tests/Feature/CvProfileInitialProfilesTest.php` y `npm run types:check`; corregir solo lo necesario. **Checkpoint US4**: presets explícitos, idempotencia, no recreación y perfiles adicionales funcionan de forma independiente.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: auditoría final de ausencia de eliminación, suite completa, tipos, formato, migración local y validación manual del quickstart.

- [x] T044 [P] Añadir a `tests/Feature/CvProfileTest.php` la prueba negativa de ausencia de eliminación: `Route::has('profiles.destroy')` es `false`; `method_exists(App\Http\Controllers\CvProfileController::class, 'destroy')` es `false`; ninguna ruta de `/profiles` declara `DELETE`; `DELETE /profiles/{id}` responde 405. La ausencia de acción de eliminación en la UI se revisa en T051. Ejecutar `php artisan test tests/Feature/CvProfileTest.php`.
- [x] T045 Ejecutar la suite completa de backend con `php artisan test`; no debe haber fallos ni pruebas omitidas que afecten a esta capacidad.
- [x] T046 [P] Ejecutar la suite completa de frontend con `npm test`; no debe haber fallos en `CvProfileForm` ni en `use-current-url`. **Evidencia 2026-10-22:** 13 archivos / 31 tests passed con Vitest directo antes de restaurar `package.json`; el frontend no cambió después de esa restauración.
- [x] T047 [P] Ejecutar `npm run types:check` y corregir cualquier error de TypeScript en los archivos de esta capacidad.
- [x] T048 [P] Ejecutar `npm run check` y `composer lint:check`; corregir solo los archivos de esta capacidad. No usar `npm run check:fix` ni formateadores automáticos sobre archivos ajenos. **Estado 2026-10-22: PASS.** Los 7 documentos SPEC se formatearon con `vp fmt`; `./node_modules/.bin/vpr check` pasa (131 archivos formateados; 75 sin lint), `vpr check --no-fmt` pasa y `git diff --check` está limpio. `package.json` restaurado sin `devEngines`.
- [x] T049 [P] Aplicar la migración en la base local con `php artisan migrate` y verificar con `php artisan migrate:status` que `2026_10_22_000001_create_cv_profiles_and_content_tables` figura como aplicada. No usar `migrate:fresh`, `migrate:refresh` ni `db:wipe`; confirmar que las tablas y datos del Perfil Maestro y de SPEC-002 no cambiaron.
- [ ] T050 Ejecutar manualmente los 11 escenarios de [quickstart.md](./quickstart.md) en la aplicación local: estado vacío y presets, creación/nombre, selección, relevancia/orden, reutilización, herencia/título propio, actualización/eliminación de maestros, activo/inactivo, persistencia tras reiniciar, fallo de guardado y límites de alcance. Registrar la evidencia (resultado de cada escenario y cualquier incidencia) en el informe de Implement/Validate; no crear artefactos nuevos ni modificar la especificación. **Estado 2026-10-22: parcial — aceptación manual del responsable; subescenarios no confirmados documentados en `quickstart.md`; el responsable autorizó el cierre, pero no se declara cobertura total de los 11 escenarios.**
- [x] T051 [P] Auditar el alcance: `php artisan route:list --name=profiles` debe mostrar solo las nueve rutas y ninguna `DELETE`; revisar que no exista `Route::resource('profiles')`, `profiles.destroy` ni `CvProfileController@destroy`, botón de eliminación, orden manual/drag-and-drop, generación de CV/PDF, plantillas, exportación, IA, ofertas, postulaciones, asociación documental, historial/versionado ni dependencias nuevas. Confirmar que no se modificaron SPEC-001, SPEC-002 ni sus datos.

**Checkpoint final (cierre documental autorizado)**: automatizado verde (backend, frontend, tipos, lint de código y migración); prueba negativa de no `DELETE` verificada; T048 PASS; T050 parcial con aceptación manual del responsable y subescenarios no confirmados documentados en `quickstart.md`. El cierre documental está autorizado; queda pendiente el merge manual a `main`, sin push. No se declara cobertura manual total de los 11 escenarios ni aceptación funcional total.

## Validation Evidence (2026-10-22)

- **Implementación**: T001–T049 y T051 completadas; T050 parcial con aceptación manual del responsable. T048 PASS.
- **Backend**: `php artisan test` → **143 passed, 1255 assertions**.
- **QA edge cases**: `php artisan test tests/Feature/CvProfileQaEdgeCasesTest.php` → **7 passed, 114 assertions**.
- **Frontend**: **13 archivos / 31 tests passed** con Vitest directo, antes de restaurar `package.json`; el frontend no cambió después. No se re-ejecutó `npm test` tras la restauración.
- **Tipos**: `tsc --noEmit` → **0 errores**.
- **Lint y formato**: `./node_modules/.bin/vpr check` → **PASS** (131 archivos formateados; 75 sin lint); `vpr check --no-fmt` → **PASS**; `composer lint:check` → **passed**; `git diff --check` limpio.
- **Rutas**: `php artisan route:list --name=profiles` → **9 rutas, ninguna DELETE**. Verificada la ausencia de `profiles.destroy`, `destroy`, rutas DELETE y botones de eliminación (T044/T051).
- **Migración**: `php artisan migrate:status` → `2026_10_22_000001_create_cv_profiles_and_content_tables` **Ran, batch 2**.
- **package.json**: restaurado sin `devEngines`; `git diff -- package.json` vacío.
- **Limitaciones**: T050 conserva subescenarios no confirmados documentados en `quickstart.md`; el cierre documental fue autorizado por el responsable y queda pendiente el merge manual a `main`, sin push. No se declara cobertura total de los 11 escenarios.

## Manual Acceptance & Closure (2026-10-22)

**Carácter**: aceptación manual del responsable de SPEC-003 registrada en la conversación de cierre; **no** es una prueba automatizada ni una validación de QA. Las pruebas automatizadas se registran en «Validation Evidence».

**Evidencias manuales aceptadas (10)**:

1. Creación, edición, consulta, activación y desactivación de perfiles.
2. Selección, guardado y consulta de registros del Perfil Maestro con relevancia Alta/Media/Baja.
3. Reutilización de elementos entre perfiles con relevancias independientes.
4. Actualizaciones del Perfil Maestro reflejadas en los perfiles asociados.
5. Eliminación de registros maestros sin afectar a los perfiles restantes.
6. Conservación del contenido al desactivar y reactivar.
7. Persistencia de perfiles y asociaciones después de reiniciar.
8. Validaciones de nombres duplicados y vacíos.
9. Recuperación ante fallos de guardado y reintento.
10. Exclusiones de alcance: sin generación de CV, PDF, exportaciones ni eliminación definitiva de perfiles.

**Mapeo y alcance**: [quickstart.md](./quickstart.md), sección «Registro de validación manual del responsable — 2026-10-22», contiene el mapeo a escenarios/pasos y los subescenarios no confirmados. Esos subescenarios no se declaran aprobados.

**T048**: PASS. Los 7 documentos SPEC se formatearon con `vp fmt`; `./node_modules/.bin/vpr check` pasa (131 archivos formateados; 75 sin lint), `vpr check --no-fmt` pasa y `git diff --check` está limpio. `package.json` restaurado sin `devEngines`.

**T050**: parcial. Aceptación manual del responsable con 10 evidencias; no se declara cobertura total de los 11 escenarios ni de los subescenarios no confirmados.

**Cierre**: el responsable autorizó el cierre documental de SPEC-003; el spec quedó en estado `Closed` con T050 parcial documentada. Queda pendiente el merge manual a `main`; no se realizó push.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (T001–T008)**: puede comenzar inmediatamente. Dependencias internas: T003 depende de T002; T006 depende de T005; T008 depende de T007.
- **Foundational (T009–T013)**: depende de Setup. T010 depende de T002 y T009; T012 depende de T009 y T010; T013 depende de T012. **Bloquea todas las historias.**
- **US1 (T014–T024)**: depende de T001–T013. T014 (tests) va antes de T015–T023.
- **US2 (T025–T033)**: depende de US1 (perfil y rutas de gestión) y de T009–T013. T025 y T026 (tests) van antes de T027–T032.
- **US3 (T034–T039)**: depende de US1 y US2 (contenido y props existentes). T034 y T035 (tests) van antes de T036–T038.
- **US4 (T040–T043)**: depende de US1 y T007. T040 (tests) va antes de T041–T042. Puede ejecutarse después de US1, pero con un solo Developer se recomienda después de US3.
- **Polish (T044–T051)**: depende de todas las historias. T045 debe ejecutarse después de T044 si T044 añade pruebas; T046–T051 son verificaciones posteriores.

### User Story Dependencies

- **US1**: no depende de otras historias; entrega la base de gestión.
- **US2**: depende de US1 para crear/editar perfiles y de la base de contenido de Phase 2; no puede considerarse completa sin la selección explícita.
- **US3**: depende de US1 y US2 porque consume el perfil, el contenido y las props ya construidas.
- **US4**: depende de US1 (índice y rutas) y de T007; es independiente de US2/US3 en lógica, aunque comparte la página de índice.

### Critical Dependencies

- T001 → T002 → T003/T010/T012/T013.
- T009 → T010 → T012 → T013.
- T011 → T015/T027.
- T013 → T028.
- T025/T026 → T027–T032.
- T034/T035 → T036–T038.
- T040 → T041–T042.
- T044–T051 → cierre; no iniciar Polish antes de completar las historias autorizadas.

### Within Each User Story

- Las pruebas se escriben y se ejecutan primero; se confirma el fallo antes de implementar.
- Backend antes que la integración frontend: modelo/validación/controlador antes de las páginas.
- Los formularios y páginas se implementan después de las props y la persistencia que consumen.
- Cada historia termina en su checkpoint con los comandos indicados.
- No se avanza a la siguiente historia si el checkpoint anterior no está en verde.

### Parallel Opportunities

- **[P] solo indica independencia de archivos**, no ejecución concurrente. En esta capacidad hay un solo Developer secuencial.
- T005 puede escribirse en paralelo con T001–T004 (archivo distinto).
- T011 puede prepararse en paralelo con T009/T010 (archivo distinto); T010 depende lógicamente de T009, así que no se ejecuta antes.
- T025 y T026 pueden escribirse en paralelo (archivos distintos) antes de T027.
- T034 y T035 pueden escribirse en paralelo (archivos distintos) antes de T036.
- T044–T049 y T051 son verificaciones independientes en cuanto a archivos, pero se ejecutan al final; T045 va después de T044 y T050 depende de T049 (migración aplicada).

---

## Parallel Example: User Story 2

```text
# Solo si existiera más de un desarrollador; en esta ejecución se hacen secuencialmente.
T025: escribir tests/Feature/CvProfileContentTest.php
T026: escribir resources/js/components/__tests__/CvProfileForm.test.tsx
```

Ambos archivos son independientes; después, un único Developer ejecuta T027–T032 en orden.

---

## Implementation Strategy

### MVP First (P1)

1. Completar Setup (T001–T008).
2. Completar Foundational (T009–T013).
3. Completar US1 (T014–T024) y validar su checkpoint: gestión y ciclo de vida del perfil.
4. Completar US2 (T025–T033) y validar su checkpoint: selección, relevancia, orden y reutilización.
5. **MVP P1 implementado**: US1 + US2. US1 por sí sola es un corte demostrable, pero no el MVP funcional, porque la selección y la relevancia son el núcleo de la capacidad. Queda registrada la aceptación manual parcial del responsable y el cierre documental autorizado; T050 conserva subescenarios no confirmados.

### Incremental Delivery

1. Setup + Foundational → base lista.
2. US1 → gestión de perfiles (primer corte demostrable).
3. US2 → configuración de contenido (MVP P1).
4. US3 → consulta con herencia, procedencia y estados vacíos.
5. US4 → presets Analista/Programador.
6. Polish → auditoría, suite completa, migración local y validación manual.

Cada incremento debe dejar las pruebas de las historias anteriores en verde y no modificar el Perfil Maestro ni otros perfiles.

### Checkpoints

- **Tras T013**: `php artisan route:list --name=profiles` y `php artisan test tests/Feature/SkillTest.php`.
- **Tras T024**: `php artisan test tests/Feature/CvProfileTest.php` en verde.
- **Tras T033**: `php artisan test tests/Feature/CvProfileContentTest.php` y `npm test -- resources/js/components/__tests__/CvProfileForm.test.tsx` en verde.
- **Tras T039**: `php artisan test tests/Feature/CvProfileTest.php` y `php artisan test tests/Feature/CvProfileContentTest.php` en verde.
- **Tras T043**: `php artisan test tests/Feature/CvProfileInitialProfilesTest.php` en verde.
- **Tras T051 (cierre documental autorizado)**: backend, frontend, tipos, lint de código y migración verificados; T048 PASS; T050 parcial con subescenarios no confirmados en `quickstart.md`. Cierre autorizado; pendiente merge manual a `main`, sin push. No se declara cobertura manual total.

---

## FR/SC Traceability

### Cobertura por historia

| Historia | Tareas    | Requisitos principales                                         |
| -------- | --------- | -------------------------------------------------------------- |
| US1      | T014–T024 | FR-005, FR-013, FR-014, FR-016, FR-018, FR-019, FR-020         |
| US2      | T025–T033 | FR-003, FR-004, FR-008, FR-009, FR-010, FR-011, FR-012, FR-020 |
| US3      | T034–T039 | FR-001, FR-002, FR-006, FR-007, FR-014, FR-015, FR-016         |
| US4      | T040–T043 | FR-005, FR-017, FR-018                                         |

### Requisitos funcionales

| Requisito | Tareas de implementación           | Tareas de prueba |
| --------- | ---------------------------------- | ---------------- |
| FR-001    | T001, T002, T010, T012, T013, T028 | T025, T034       |
| FR-002    | T001, T010, T012, T037             | T035             |
| FR-003    | T010, T013, T028, T031             | T014, T025       |
| FR-004    | T001, T010, T012, T036             | T025, T035       |
| FR-005    | T007, T008, T016, T021, T022, T023 | T014             |
| FR-006    | T018, T030, T036, T037, T038       | T034, T035       |
| FR-007    | T001, T012, T031, T038             | T014, T025, T034 |
| FR-008    | T011, T013, T027, T028, T031       | T025             |
| FR-009    | T010, T012, T031, T032             | T025, T026       |
| FR-010    | T001, T002, T011, T013, T030, T038 | T025, T026       |
| FR-011    | T010, T012, T030                   | T025, T035       |
| FR-012    | T009, T010, T012                   | T025             |
| FR-013    | T002, T011, T015, T017, T019       | T014             |
| FR-014    | T002, T011, T015, T019, T036, T037 | T014, T034       |
| FR-015    | T012, T036, T037                   | T034             |
| FR-016    | T001, T002, T016, T020, T021, T037 | T014, T034       |
| FR-017    | T003, T007, T041, T042             | T040             |
| FR-018    | T007, T020, T021, T023, T037       | T014, T044       |
| FR-019    | T001, T002, T013                   | T014, T050       |
| FR-020    | T011, T013, T015, T027, T028, T031 | T025, T026       |

### Criterios de éxito

| Criterio | Tareas de implementación     | Tareas de prueba |
| -------- | ---------------------------- | ---------------- |
| SC-001   | T001, T010, T012             | T025, T034, T035 |
| SC-002   | T010, T013, T028             | T014, T025       |
| SC-003   | T010, T012, T030             | T025, T035       |
| SC-004   | T001, T020, T041, T042       | T014, T040, T044 |
| SC-005   | T011, T015, T017, T019, T031 | T014, T025, T026 |
| SC-006   | T001, T002, T013             | T014, T050       |

---

## Notes

- **Pruebas obligatorias**: la especificación exige pruebas de persistencia, referencia frente a copia, reutilización/aislamiento, inclusión/relevancia/orden, nombre/herencia, presets/ciclo de vida, casos límite y límites de alcance. No se acepta una historia sin su evidencia.
- **[P]**: solo independencia de archivos; no autoriza ejecución concurrente ni cambia el orden secuencial de un único Developer.
- **Sin tareas de Git**: este `tasks.md` no incluye commits, staging, push, merge ni operaciones de rama. La coordinación Git se realiza fuera de este documento y requiere su propia autorización.
- **Sin eliminación de perfiles**: no existe `DELETE /profiles/{id}`, `profiles.destroy`, método `destroy` ni acción de UI. La única retirada es la desactivación reversible. T044 verifica la ausencia.
- **Sin `Route::resource`**: las rutas de `profiles` son explícitas para que la ausencia de `DELETE` sea auditable.
- **Sin migraciones destructivas**: no usar `migrate:fresh`, `migrate:refresh` ni `db:wipe`; la migración es aditiva y su `down()` solo elimina datos de esta capacidad.
- **Sin dependencias nuevas**: no modificar `composer.json` ni `package.json`.
- **Sin cambios de alcance**: no generar CV, PDF, vistas previas, plantillas, exportaciones, IA, análisis de ofertas, postulaciones, cartas, asociación documental, historial/versionado ni orden manual. Cualquier necesidad de ampliar el alcance debe escalarse.
- **Evidencia**: diferenciar prueba ejecutada, prueba pendiente y prueba bloqueada; no declarar terminado nada que no tenga validación suficiente. Si Git está denegado, indicar qué estado no se puede confirmar y continuar con el seguimiento independiente.
