# Tasks: Perfil Profesional Maestro

**Input**: Design documents from `/specs/001-perfil-profesional-maestro/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/http-contracts.md, quickstart.md

**Tests**: INCLUIDOS — el spec los exige en la sección "Pruebas Requeridas" y la Constitución (Principio XXI) exige cobertura de: persistencia, validaciones, relaciones, confirmación antes de eliminar. Los tests se escriben antes que la implementación de su historia y deben fallar primero.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- Aplicación Laravel única en la raíz del repositorio: `app/`, `database/`, `resources/js/`, `routes/`, `tests/`
- Referencias en docs: rutas relativas del proyecto

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [x] T001 Initialize the application from the official `laravel/react-starter-kit` (Laravel 13, Inertia, React 19, TypeScript, Tailwind 4, shadcn/ui) at the repository root, preserving `.specify/`, `specs/`, `.opencode/` and `.git/`; run `composer install` and `npm install`; verify `composer dev` boots
- [x] T002 [P] Configure local environment and SQLite: create `database/database.sqlite`, set `DB_CONNECTION=sqlite` in `.env` and `.env.example`, app name "CV Automático", locale `es` in `.env`
- [x] T003 Remove authentication scaffolding (el MVP es monousuario, sin auth): delete `routes/auth.php`, `app/Http/Controllers/Auth/`, `app/Http/Requests/Auth/`, `app/Models/User.php`, users migration, `resources/js/pages/auth/`, `resources/js/pages/settings/`, and auth-related feature tests
- [x] T004 Configure Pest 4 backend tests: keep `tests/Feature/` and `tests/Unit/`, ensure `phpunit.xml` uses SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), remove starter example/auth tests
- [x] T005 [P] Install and configure Vitest + React Testing Library: add devDependencies and `test` script in `package.json`, create `vitest.config.ts` and setup file `resources/js/test-setup.ts` (jsdom, globals, RTL cleanup)
- [x] T006 [P] Verify lint/format tooling runs clean: Pint for PHP (`composer` script), ESLint + Prettier for JS/TS (`npm run lint`, `npm run format`)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Schema, models, shared helpers and shared UI that ALL user stories depend on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Migrations (one file each; constraints from data-model.md)

- [x] T007 [P] Migration `database/migrations/*_create_profiles_table.php`: `full_name` string(255) NOT NULL; `professional_title` string(255) null; `phone` string(50) null; `email` string(255) null; `location` string(255) null; `linkedin_url`, `github_url`, `website_url` string(2048) null; `summary` text null; timestamps
- [x] T008 [P] Migration `database/migrations/*_create_profile_links_table.php`: `profile_id` FK → profiles cascade delete; `label` string(100) NOT NULL; `url` string(2048) NOT NULL
- [x] T009 [P] Migration `database/migrations/*_create_work_experiences_table.php`: `profile_id` FK → profiles cascade; `company` string(255) NOT NULL; `position` string(255) NOT NULL; `start_year`/`end_year` integer null; `start_month`/`end_month` integer null; `is_current` boolean default false; `description`, `responsibilities`, `achievements` text null
- [x] T010 [P] Migration `database/migrations/*_create_education_entries_table.php`: `profile_id` FK cascade; `institution` string(255) NOT NULL; `program` string(255) NOT NULL; `degree_level` string(100) null; `start_year`/`start_month`/`end_year`/`end_month` integer null; `is_in_progress` boolean default false; `description` text null
- [x] T011 [P] Migration `database/migrations/*_create_certifications_table.php`: `profile_id` FK cascade; `name` string(255) NOT NULL; `institution` string(255) null; `date_year`/`date_month` integer null; `duration` string(100) null; `code` string(255) null; `validation_url` string(2048) null; `category` string(100) null
- [x] T012 [P] Migration `database/migrations/*_create_skills_table.php`: `profile_id` FK cascade; `name` string(100) NOT NULL; `normalized_name` string(100) NOT NULL; `category` string(20) NOT NULL; unique index `(profile_id, normalized_name)`
- [x] T013 [P] Migration `database/migrations/*_create_projects_table.php`: `profile_id` FK cascade; `name` string(255) NOT NULL; `description` text null; `role` string(255) null; `results` text null; `url` string(2048) null; `start_year`/`start_month`/`end_year`/`end_month` integer null
- [x] T014 [P] Pivot migrations `database/migrations/*_create_skill_work_experience_table.php`, `*_create_skill_certification_table.php`, `*_create_skill_project_table.php`: composite primary keys, FKs cascade on both sides

### Models (one file each)

- [x] T015 [P] `app/Models/Profile.php`: fillable fields; `hasMany` links/workExperiences/educationEntries/certifications/skills/projects; static `current()` using `firstOrCreate` (singleton, FR-001)
- [x] T016 [P] `app/Models/ProfileLink.php`: fillable `label`, `url`; `belongsTo` Profile
- [x] T017 [P] `app/Models/WorkExperience.php`: fillable fields; casts (`is_current` bool, years/months int); `belongsToMany` Skill; ordered scope by `(start_year, COALESCE(start_month,1))` desc with null-year last, via `app/Support/YearMonth.php`
- [x] T018 [P] `app/Models/EducationEntry.php`: fillable fields; casts (`is_in_progress` bool); ordered scope as T017
- [x] T019 [P] `app/Models/Certification.php`: fillable fields; `belongsToMany` Skill; ordered scope by `(date_year, COALESCE(date_month,1))` desc with null-date last
- [x] T020 [P] `app/Models/Skill.php`: fillable `name`, `category`; `normalized_name` auto-computed with `app/Support/NameNormalizer.php` on save; `belongsToMany` WorkExperience, Certification, Project
- [x] T021 [P] `app/Models/Project.php`: fillable fields; `belongsToMany` Skill; ordered scope as T017
- [x] T022 [P] Model factories for all entities in `database/factories/` (Profile, ProfileLink, WorkExperience, EducationEntry, Certification, Skill, Project) including year/month states (`yearOnly()`, `monthYear()`) for date-rule tests

### Shared helpers (with unit tests)

- [x] T023 [P] `app/Support/YearMonth.php`: comparison and sort rules from FR-034 — year-only start = January, year-only end = December; `greaterOrEqual($start, $end)`; sort key `(year, month ?? 1)` with null year last
- [x] T024 [P] `app/Support/NameNormalizer.php`: lowercase + trim + collapse inner whitespace, Unicode `mb_*` aware (used by skills FR-020 and certification duplicates FR-018)
- [x] T025 [P] Unit tests `tests/Unit/YearMonthTest.php`: year-only vs month-year comparisons; equal years; end ≥ start cases; ordering keys
- [x] T026 [P] Unit tests `tests/Unit/NameNormalizerTest.php`: case-insensitive equality, extra spaces, accents preserved, empty input

### Shared UI components (Spanish UI)

- [x] T027 [P] `resources/js/layouts/AppLayout.tsx`: main navigation with the 6 sections (Perfil, Experiencia, Educación, Cursos y certificaciones, Habilidades, Proyectos) using literal paths, active state, Spanish labels
- [x] T028 [P] `resources/js/components/EmptyState.tsx`: icon, guidance text and "Crear" action button (FR-029)
- [x] T029 [P] `resources/js/components/DeleteConfirmDialog.tsx`: shadcn/ui `AlertDialog`; shows record name; confirm submits DELETE, cancel closes without side effects (FR-026)
- [x] T030 [P] `resources/js/components/YearMonthInput.tsx`: year select (1900..año actual+10) + optional month select; month disabled/cleared when no year; used by all date-based forms (FR-034)
- [x] T031 [P] `resources/js/components/SkillMultiSelect.tsx`: checkbox group grouped by skill category, receives catalog + selected ids
- [x] T032 [P] Component test `resources/js/components/__tests__/DeleteConfirmDialog.test.tsx`: confirm triggers delete callback; cancel does not
- [x] T033 [P] Component test `resources/js/components/__tests__/EmptyState.test.tsx`: renders guidance and create action

**Checkpoint**: Foundation ready — user story implementation can now begin

---

## Phase 3: User Story 1 - Registrar datos personales y resumen profesional (Priority: P1) 🎯 MVP

**Goal**: Perfil singleton editable: datos personales, enlaces adicionales y resumen, con validaciones y persistencia local.

**Independent Test**: Completar datos y resumen, guardar, reiniciar la app y verificar que todo aparece idéntico y editable (quickstart escenarios 1 y 2).

### Tests for User Story 1 (write first, must FAIL) ⚠️

- [x] T034 [P] [US1] Feature test `tests/Feature/ProfileTest.php`: `GET /profile` crea el perfil singleton una sola vez (FR-001); `PUT /profile` persiste todos los campos; `full_name` obligatorio (FR-006); formato de `email` y URLs (FR-005); los enlaces adicionales se reemplazan en bloque (FR-003); acentos se conservan (FR-030)

### Implementation for User Story 1

- [x] T035 [US1] `app/Http/Requests/UpdateProfileRequest.php`: `full_name` required|string|max:255; `email` nullable|email|max:255; `linkedin_url`,`github_url`,`website_url` nullable|url|max:2048; `phone` nullable|string|max:50; `professional_title` nullable|string|max:255; `location` nullable|string|max:255; `summary` nullable|string; `links` array; `links.*.label` required|string|max:100; `links.*.url` required|url|max:2048 (mensajes en español)
- [x] T036 [US1] `app/Http/Controllers/ProfileController.php` con `edit` (carga `Profile::current()` + links) y `update` (actualiza perfil y reemplaza la colección de enlaces); registrar rutas en `routes/web.php`: `GET /` redirect → `profile.edit`, `GET /profile`, `PUT /profile`; eliminar `resources/js/pages/Welcome.tsx`
- [x] T037 [US1] Página `resources/js/pages/Profile/Edit.tsx`: formulario único con `useForm` de Inertia, filas dinámicas para enlaces (agregar/quitar), errores junto al campo, layout `AppLayout`

**Checkpoint**: User Story 1 fully functional and testable independently

---

## Phase 4: User Story 2 - Gestionar experiencia laboral (Priority: P1) 🎯 MVP

**Goal**: CRUD de experiencias con fechas mes-año o solo año, estados "actual", y asociación de habilidades.

**Independent Test**: Registrar una experiencia con empresa, cargo y fecha; ver orden, editar, marcar como actual, asociar habilidad existente (vía factory/seed o US5), eliminar con confirmación (quickstart escenario 3).

### Tests for User Story 2 (write first, must FAIL) ⚠️

- [x] T038 [P] [US2] Feature test `tests/Feature/WorkExperienceTest.php`: obligatorios `company`+`position` (FR-033); mes sin año inválido (FR-034); año suelto aceptado; `end ≥ start` bloqueado con mensaje (FR-009); orden descendente con año-solo como enero (FR-010, FR-034); transiciones `is_current` (fin ⇒ false; quitar fin ⇒ true; current con fin ⇒ error) (FR-008, caso límite); sync de `skill_ids` en pivote (FR-011); eliminar experiencia borra pivotes pero conserva habilidades

### Implementation for User Story 2

- [x] T039 [US2] `app/Http/Requests/StoreWorkExperienceRequest.php` y `UpdateWorkExperienceRequest.php`: `company` required|string|max:255; `position` required|string|max:255; `start_year`,`end_year` nullable|integer|between:1900,(año actual + 10); `start_month`,`end_month` nullable|integer|between:1,12 + `required_with` recíproco (mes exige año); regla `end ≥ start` con `app/Support/YearMonth.php`; `is_current` boolean; `skill_ids` array; `skill_ids.*` exists en skills; mensajes en español
- [x] T040 [US2] `app/Http/Controllers/WorkExperienceController.php` (index ordenado, create, store, edit, update, destroy; `sync()` de habilidades; transiciones de estado) + rutas de `contracts/http-contracts.md` para `/experiences` en `routes/web.php`
- [x] T041 [P] [US2] Página `resources/js/pages/Experiences/Index.tsx`: lista ordenada con fechas formateadas ("mar 2022", "2024", "actual"), chips de habilidades, `DeleteConfirmDialog`, `EmptyState`
- [x] T042 [P] [US2] Formulario compartido `resources/js/components/forms/WorkExperienceForm.tsx` + páginas `resources/js/pages/Experiences/Create.tsx` y `Edit.tsx` usando `YearMonthInput` y `SkillMultiSelect`

**Checkpoint**: US1 y US2 funcionan independientemente

---

## Phase 5: User Story 3 - Gestionar educación (Priority: P2)

**Goal**: CRUD de educación con fechas y estado "en curso".

**Independent Test**: Registrar institución y programa, marcar en curso, validar fechas, eliminar con confirmación.

### Tests for User Story 3 (write first, must FAIL) ⚠️

- [x] T043 [P] [US3] Feature test `tests/Feature/EducationTest.php`: obligatorios `institution`+`program` (FR-033); validación de fechas (FR-014); `is_in_progress` sin fin y transiciones (FR-013); orden descendente; eliminación

### Implementation for User Story 3

- [x] T044 [US3] `app/Http/Requests/StoreEducationRequest.php` y `UpdateEducationRequest.php` (mismas reglas de fecha que US2, sin `skill_ids`)
- [x] T045 [US3] `app/Http/Controllers/EducationController.php` + rutas `/education` en `routes/web.php` según contrato
- [x] T046 [P] [US3] Páginas `resources/js/pages/Education/{Index,Create,Edit}.tsx` con formulario compartido `resources/js/components/forms/EducationForm.tsx` (sin selector de habilidades)

**Checkpoint**: US1–US3 funcionan independientemente

---

## Phase 6: User Story 4 - Gestionar cursos y certificaciones (Priority: P2)

**Goal**: CRUD de certificaciones con fecha opcional, URL de validación, categoría y flujo de duplicados con confirmación explícita.

**Independent Test**: Registrar certificación completa; probar advertencia de duplicado (bloqueo y "Guardar de todos modos"); guardar sin fecha; asociar habilidades.

### Tests for User Story 4 (write first, must FAIL) ⚠️

- [x] T047 [P] [US4] Feature test `tests/Feature/CertificationTest.php`: solo `name` obligatorio (FR-033); sin fecha permitido (FR-017); formato de `validation_url`; duplicado nombre+institución normalizados se bloquea sin `confirm_duplicate` y persiste con `confirm_duplicate=true` (FR-018); sync de habilidades (FR-016)

### Implementation for User Story 4

- [x] T048 [US4] `app/Http/Requests/StoreCertificationRequest.php` y `UpdateCertificationRequest.php`: `name` required|string|max:255; `institution` nullable|string|max:255; `date_year` nullable|integer|between:1900,(año actual + 10); `date_month` nullable|integer|between:1,12 + required_with; `duration` nullable|string|max:100; `code` nullable|string|max:255; `validation_url` nullable|url|max:2048; `category` nullable|string|max:100; `confirm_duplicate` boolean; `skill_ids` array
- [x] T049 [US4] `app/Http/Controllers/CertificationController.php`: CRUD + detección de duplicado con `app/Support/NameNormalizer.php` (nombre+institución normalizados); sin `confirm_duplicate` ⇒ redirect de vuelta con flash `duplicate_warning` (registro existente) sin persistir; con flag ⇒ persiste; rutas `/certifications` en `routes/web.php`
- [x] T050 [P] [US4] `resources/js/components/DuplicateWarningDialog.tsx` + páginas `resources/js/pages/Certifications/{Index,Create,Edit}.tsx` y formulario compartido; el diálogo ofrece "Guardar de todos modos" reenviando con `confirm_duplicate=true`
- [x] T051 [P] [US4] Component test `resources/js/components/__tests__/DuplicateWarningDialog.test.tsx`: muestra el registro existente; confirmar reenvía con `confirm_duplicate=true`; cancelar conserva el formulario

**Checkpoint**: US1–US4 funcionan independientemente

---

## Phase 7: User Story 5 - Gestionar habilidades (Priority: P3)

**Goal**: Catálogo único de habilidades por categoría, sin duplicados, con visibilidad de uso.

**Independent Test**: Crear habilidad, bloquear duplicado normalizado, consultar usos, eliminar conservando experiencias/certificaciones/proyectos.

### Tests for User Story 5 (write first, must FAIL) ⚠️

- [x] T052 [P] [US5] Feature test `tests/Feature/SkillTest.php`: `name`+`category` obligatorios (FR-033); categorías válidas `tecnica|funcional|metodologica|herramienta|tecnologia` (FR-019); duplicado normalizado bloqueado sugiriendo la existente (FR-020); vista de usos (FR-021); eliminar habilidad elimina solo pivotes y conserva entidades (caso límite)

### Implementation for User Story 5

- [x] T053 [US5] `app/Http/Requests/StoreSkillRequest.php` y `UpdateSkillRequest.php`: `name` required|string|max:100; `category` required|in:tecnica,funcional,metodologica,herramienta,tecnologia
- [x] T054 [US5] `app/Http/Controllers/SkillController.php`: calcula `normalized_name` con `NameNormalizer`; captura violación de índice único y devuelve error de validación amigable con la habilidad existente; `edit` carga usos (experiencias, certificaciones, proyectos); rutas `/skills` en `routes/web.php`
- [x] T055 [P] [US5] Páginas `resources/js/pages/Skills/{Index,Create,Edit}.tsx`: catálogo agrupado por categoría; panel "Se usa en" en `Edit`

**Checkpoint**: Escenario 4 del quickstart completo (incluye asociaciones manuales de US2/US4)

---

## Phase 8: User Story 6 - Gestionar proyectos (Priority: P3)

**Goal**: CRUD de proyectos con fechas, enlaces, resultados y tecnologías/habilidades asociadas.

**Independent Test**: Registrar proyecto con fechas válidas, asociar habilidades, validar fechas incoherentes, eliminar con confirmación.

### Tests for User Story 6 (write first, must FAIL) ⚠️

- [x] T056 [P] [US6] Feature test `tests/Feature/ProjectTest.php`: `name` obligatorio (FR-033); validación de fechas (FR-024); formato de `url`; sync de habilidades (FR-023); eliminación conserva el catálogo de habilidades

### Implementation for User Story 6

- [x] T057 [US6] `app/Http/Requests/StoreProjectRequest.php` y `UpdateProjectRequest.php`: `name` required|string|max:255; `description` nullable|string; `role` nullable|string|max:255; `results` nullable|string; `url` nullable|url|max:2048; reglas de fecha de US2 (sin flag de estado); `skill_ids` array
- [x] T058 [US6] `app/Http/Controllers/ProjectController.php` + rutas `/projects` en `routes/web.php` según contrato
- [x] T059 [P] [US6] Páginas `resources/js/pages/Projects/{Index,Create,Edit}.tsx` con formulario compartido `resources/js/components/forms/ProjectForm.tsx`

**Checkpoint**: Todos los escenarios del quickstart funcionan

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [x] T060 [P] Feature test `tests/Feature/PersistenceTest.php`: escribir perfil completo (todas las secciones + relaciones) y releerlo en una instancia fresca de la aplicación simulando reinicio (FR-027, SC-002)
- [x] T061 Run full backend suite `php artisan test` and fix any failures
- [x] T062 [P] Run `npm test`, `npm run lint` and Pint (`composer` script) and fix any issues
- [x] T063 [P] Audit privacy/offline (FR-031, Principio XXIII): no CDNs, fuentes/iconos locales, ninguna llamada externa en runtime; verificar `.env` fuera de git
- [ ] T064 Manual validation of `specs/001-perfil-profesional-maestro/quickstart.md` (9 escenarios) including SC-001 timing (<20 min) and acentos/texto extenso; record results
- [x] T065 [P] Update `README.md` with setup, run and test instructions for the application

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion — BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
    - P1: US1 → US2 (recommended order; both are the MVP)
    - P2: US3, US4 can start after Foundational (US4 uses `SkillMultiSelect` from Foundational)
    - P3: US5, US6; US6 reuses the same date/skill patterns
- **Polish (Phase 9)**: Depends on all desired user stories being complete

### User Story Dependencies

- **US1 (P1)**: Independent after Foundational
- **US2 (P1)**: Independent after Foundational; skill association UI needs the Skill catalog data — automated via factories; manual end-to-end validation of association scenarios requires US5 (noted in quickstart)
- **US3 (P2)**: Independent after Foundational
- **US4 (P2)**: Independent after Foundational; same skill-association note as US2
- **US5 (P3)**: Independent after Foundational; enables manual validation of all association scenarios
- **US6 (P3)**: Independent after Foundational; same skill-association note

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Requests/validation before controller; controller/routes before pages; pages before checkpoint validation

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel
- Foundational migrations (T007–T014), models (T015–T022) and helpers (T023–T026) are all [P] (different files)
- Shared UI components (T027–T031) are [P]; component tests (T032–T033) follow them
- Once Foundational completes, US1 and US2 can proceed in parallel by different developers; US3–US6 too, with attention to `routes/web.php` (single shared file — serialize edits across stories)
- Within each story, [P] test and page tasks can run in parallel with request/controller work

---

## Parallel Example: Foundational Phase

```bash
# Launch all migrations together (different files):
Task: "T007 Migration profiles ..."
Task: "T008 Migration profile_links ..."
Task: "T009 Migration work_experiences ..."

# Launch all models together:
Task: "T015 Profile model ..."
Task: "T016 ProfileLink model ..."
Task: "T017 WorkExperience model ..."
```

## Parallel Example: User Story 2

```bash
# Test first (must fail), then in parallel:
Task: "T038 Feature test WorkExperienceTest"
Task: "T041 Index page Experiences/Index.tsx"
Task: "T042 Form + Create/Edit pages"
```

---

## Implementation Strategy

### MVP First (US1 + US2)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL — blocks all stories)
3. Complete Phase 3: US1 (perfil) and Phase 4: US2 (experiencia) — the P1 core
4. **STOP and VALIDATE**: quickstart escenarios 1–3
5. This is the minimum usable system (identidad + trayectoria principal)

### Incremental Delivery

1. Setup + Foundational → base lista
2. US1 + US2 → validar → MVP
3. US3 → educación → validar → incremento
4. US4 → certificaciones → validar → incremento
5. US5 → catálogo de habilidades → validar asociaciones end-to-end
6. US6 → proyectos → validar → capacidad completa
7. Polish → persistencia global, lint, privacidad y quickstart completo

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- El archivo `routes/web.php` es compartido: las tareas que lo editan (T036, T040, T045, T049, T054, T058) no deben ejecutarse en paralelo entre sí
- Cada historia se valida en su checkpoint antes de continuar
- Commit after each task or logical group
- Mensajes de validación y UI en español; contenido admite acentos (UTF-8)
