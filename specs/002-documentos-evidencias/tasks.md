# Tasks: Documentos y Evidencias

**Input**: Design documents from `/specs/002-documentos-evidencias/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/http-contracts.md, quickstart.md

**Tests**: INCLUIDOS — el spec los exige en la sección "Pruebas Requeridas" y la Constitución (Principio XXI) exige cobertura de: importación de certificados y conservación de documentos originales, además de confirmaciones y relaciones. Los tests se escriben antes que la implementación de su historia y deben fallar primero.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- Aplicación Laravel única en la raíz del repositorio: `app/`, `database/`, `resources/js/`, `routes/`, `tests/`
- Referencias en docs: rutas relativas del proyecto
- Disco de archivos: `local` privado (`storage/app/private/documents/{checksum}.pdf`); en tests `Storage::fake('local')`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Insumos de prueba compartidos por todas las historias

- [x] T001 Create a minimal valid PDF fixture at `tests/Fixtures/minimal.pdf` (real PDF bytes: begins with `%PDF-`, ends with `%%EOF`) for upload tests, since `UploadedFile::fake()->create()` does not produce structurally valid PDFs
- [x] T002 [P] Create `database/factories/DocumentFactory.php`: `profile_id` → `Profile::current()->id`, `original_filename` (e.g. "certificado.pdf"), `title`/`description` null by default, `size_bytes`, `checksum` (unique, 64 chars via `fake()->sha256()`), `is_available` true, `last_verified_at` null; do not write files (tests control `Storage::fake('local')`)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Esquema, modelos y helpers compartidos que TODAS las historias necesitan

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Migrations (one file each; constraints from data-model.md)

- [x] T003 Migration `database/migrations/2026_10_15_000011_create_documents_table.php`: `profile_id` FK → `profiles` cascade delete; `original_filename` string(255) NOT NULL; `title` string(255) null; `description` text null; `size_bytes` unsignedBigInteger NOT NULL; `checksum` string(64) NOT NULL + unique index; `is_available` boolean default true; `last_verified_at` timestamp null; timestamps
- [x] T004 [P] Migration `database/migrations/2026_10_15_000012_create_document_certification_table.php`: `document_id` + `certification_id`, composite primary key, both FKs `cascadeOnDelete`
- [x] T005 [P] Migration `database/migrations/2026_10_15_000013_create_document_education_entry_table.php`: `document_id` + `education_entry_id`, composite primary key, both FKs `cascadeOnDelete`
- [x] T006 [P] Migration `database/migrations/2026_10_15_000014_create_document_work_experience_table.php`: `document_id` + `work_experience_id`, composite primary key, both FKs `cascadeOnDelete`

### Models and support (one file each)

- [x] T007 Create `app/Models/Document.php`: `$guarded = []`; casts `is_available` bool, `last_verified_at` datetime; `belongsTo` Profile; `belongsToMany` Certification, EducationEntry, WorkExperience; helper `path()` = `documents/{checksum}.pdf` on disk `local`; accessor `display_name` = `title ?: original_filename`; `booted()` `deleting` hook deletes the stored file if it exists (the checksum is unique, so no other document references it)
- [x] T008 [P] Add `documents()` `belongsToMany` relation (pivot `document_certification`) to `app/Models/Certification.php`
- [x] T009 [P] Add `documents()` `belongsToMany` relation (pivot `document_education_entry`) to `app/Models/EducationEntry.php`
- [x] T010 [P] Add `documents()` `belongsToMany` relation (pivot `document_work_experience`) to `app/Models/WorkExperience.php`
- [x] T011 [P] Create `app/Support/PdfFile.php`: `looksLikePdf(string $contents): bool` (prefix `%PDF-` and `%%EOF` within last 1024 bytes) and `sanitizeFilename(string $name): string` (strip quotes/newlines/control chars) for `Content-Disposition`
- [x] T012 [P] Create `app/Support/DocumentEntities.php`: allow-list `certification|education|experience` → model class; `resolve(string $type, int $id): Model`; `label(Model): string` (`certification` → `name`, `education` → `institution — program`, `experience` → `company — position`); `options(): array` grouped for UI selects and filters

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Cargar, conservar y visualizar documentos originales (Priority: P1) 🎯 MVP

**Goal**: Subir un PDF validado, conservarlo sin alteración (copia única por contenido) y visualizarlo dentro de la aplicación con verificación de integridad al abrir el visor.

**Independent Test**: Cargar un PDF válido, visualizarlo, reiniciar la aplicación y comprobar que el documento sigue disponible, íntegro y visualizable; verificar rechazos de archivos inválidos.

### Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T013 [P] [US1] Create `tests/Feature/DocumentTest.php`: upload valid PDF stores byte-identical file at `documents/{checksum}.pdf` (compare `hash_file('sha256')`), index page lists it (`assertInertia` component `Documents/Index`), validation errors for missing file, non-PDF, fake `.pdf` extension, truncated PDF and file > 20480 KB (`assertSessionHasErrors('file')`), nothing persisted on rejection (FR-002, FR-006), uploaded file identical to existing checksum creates no second row/file (FR-007)
- [x] T014 [P] [US1] Create `tests/Feature/DocumentIntegrityTest.php`: `documents.viewer` with intact file → `available = true` and flags updated; missing file → `is_available = false` + `available = false`; altered file (different content, same checksum in DB) → `is_available = false`; `documents.file` streams `application/pdf` inline with original filename and 404 when missing

### Implementation for User Story 1

- [x] T015 [US1] Create `app/Http/Requests/StoreDocumentRequest.php`: `file` required + file + `mimetypes:application/pdf` + `max:20480` + structural check via `PdfFile::looksLikePdf()` in `withValidator()`; `title` nullable string max 255; `description` nullable string max 2000; Spanish messages incl. specific message when PHP rejects the upload (`UPLOAD_ERR_INI_SIZE` / empty upload suggesting `upload_max_filesize`/`post_max_size`)
- [x] T016 [US1] Implement `DocumentController@index/@create/@store` in `app/Http/Controllers/DocumentController.php`: serialize documents (`id, display_name, original_filename, title, size_bytes, uploaded_at, is_available, last_verified_at`, `associations: []` placeholder for now) ordered by `created_at` desc; store computes SHA-256, reuses existing document when checksum exists (toast "El archivo ya estaba en el repositorio; se reutilizó el documento existente.") or writes file + row inside a transaction (toast "Documento subido."); redirect → `documents.index`
- [x] T017 [US1] Implement `DocumentController@viewer/@file` in `app/Http/Controllers/DocumentController.php`: viewer verifies `hash_file('sha256')` vs stored checksum, updates `is_available` + `last_verified_at`, renders `Documents/Viewer` with `document`, `available`, `fileUrl`, `backUrl` (default `documents.index`); `file` streams inline (`Storage::disk('local')->response(...)` with sanitized filename) and 404 when the file is missing; los PDFs protegidos quedan a cargo del visor nativo, sin detección avanzada ni parsing adicional (FR-019)
- [x] T018 [US1] Register US1 routes in `routes/web.php`: `documents.index` (GET `/documents`), `documents.create`, `documents.store`, `documents.viewer` (GET `/documents/{document}/viewer`), `documents.file` (GET `/documents/{document}/file`)
- [x] T019 [P] [US1] Create `resources/js/lib/documents.ts` with `formatBytes(bytes: number): string` (Spanish formatting)
- [x] T020 [P] [US1] Create `resources/js/components/forms/DocumentForm.tsx`: file input + optional `title`/`description`, `forceFormData: true`, inline Spanish error display (`aria-invalid` + `<p className="text-sm text-destructive">`), submit `POST /documents`
- [x] T021 [US1] Create `resources/js/pages/Documents/Create.tsx`: `AppLayout` + `DocumentForm`
- [x] T022 [US1] Create `resources/js/pages/Documents/Index.tsx`: list of documents (display name, `formatBytes`, upload date, "Sin asociar" state, "No disponible" badge when `is_available = false`), "Ver" link to viewer, "Subir documento" button, `EmptyState` when empty (FR-024)
- [x] T023 [US1] Create `resources/js/pages/Documents/Viewer.tsx`: full-page iframe of `fileUrl` when `available`, alert with reason when not, back link to `backUrl`
- [x] T024 [US1] Add "Documentos" entry to the nav array in `resources/js/layouts/AppLayout.tsx` pointing to `/documents` (FR-025)

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Asociar documentos a cursos y certificaciones (Priority: P1)

**Goal**: Asociar documentos a certificaciones desde el repositorio y desde la entidad; desasociar; eliminar la certificación conserva los documentos.

**Independent Test**: Cargar un PDF, asociarlo a una certificación existente, verificarlo desde la certificación y desde el repositorio, desasociarlo y volver a asociarlo; eliminar la certificación y comprobar que el documento sigue en el repositorio.

### Tests for User Story 2 ⚠️

- [x] T025 [P] [US2] Create `tests/Feature/DocumentLinkTest.php` (certification scenarios): associate via `POST /documents/{id}/links`; associate at upload with `linkable_type=certification`; duplicate link → validation error; `linkable_type` outside allow-list → validation error; unlink keeps document and file; deleting the certification keeps documents (unassociated) and deletes links (FR-014); deleting a document deletes file + links and keeps the certification intact (FR-023); upload from entity redirects back to `certifications.edit`
- [x] T026 [P] [US2] Create component test `resources/js/components/__tests__/AssociateDocumentDialog.test.tsx` (entity type switch updates options, submits `linkable_type`/`linkable_id`)

### Implementation for User Story 2

- [x] T027 [US2] Extend `app/Http/Requests/StoreDocumentRequest.php`: `linkable_type` nullable `in:certification,education,experience`; `linkable_id` required_with `linkable_type`, integer, exists in the table for the given type (via `DocumentEntities`)
- [x] T028 [US2] Create `app/Http/Requests/StoreDocumentLinkRequest.php`: `linkable_type` required `in:certification,education,experience`; `linkable_id` required integer + exists per type; duplicate link → Spanish validation error ("Este documento ya está asociado a esa entidad.")
- [x] T029 [US2] Create `app/Http/Controllers/DocumentLinkController.php`: `store` creates the pivot row, `destroy` removes it (both via `DocumentEntities` allow-list), redirect back + toasts ("Documento asociado." / "Documento desasociado.")
- [x] T030 [US2] Register link routes in `routes/web.php`: `documents.links.store` (POST `/documents/{document}/links`), `documents.links.destroy` (DELETE `/documents/{document}/links`)
- [x] T031 [US2] Extend `DocumentController@index/@create` in `app/Http/Controllers/DocumentController.php`: serialize `associations: [{type, id, label}]` (eager load the three relations) and pass `associationOptions` (`DocumentEntities::options()`) to both pages
- [x] T032 [US2] Update `CertificationController@edit` to pass `documents` (serialized: `id, display_name, size_bytes, is_available, uploaded_at`) and `@index` to pass per-record `documents_count` (`withCount('documents')` for the delete warning)
- [x] T033 [US2] Create `resources/js/components/documents/AssociateDocumentDialog.tsx`: dialog with entity type select (Certificación/Educación/Experiencia) + entity select filtered by type, submits `POST /documents/{id}/links`
- [x] T034 [US2] Create `resources/js/components/documents/LinkedDocumentsSection.tsx`: card listing linked documents (display name, size, "No disponible" badge, "Ver" link to viewer with `back` to the current page, "Quitar" via `DELETE /documents/{id}/links`), `EmptyState` guidance and "Subir PDF" dialog reusing `DocumentForm` with hidden `linkable_type`/`linkable_id` (FR-013)
- [x] T035 [US2] Update `resources/js/pages/Documents/Index.tsx`: show associations per document (labels, "Sin asociar" when none) and add "Asociar" row action opening `AssociateDocumentDialog`
- [x] T036 [US2] Update `resources/js/components/forms/DocumentForm.tsx` and `resources/js/pages/Documents/Create.tsx`: optional association section (type + entity select from `associationOptions`) that sends `linkable_type`/`linkable_id`
- [x] T037 [US2] Update `resources/js/pages/Certifications/Edit.tsx` to render `LinkedDocumentsSection` (`entityType="certification"`) and `resources/js/pages/Certifications/Index.tsx` delete dialog copy to warn when `documents_count > 0` ("Sus N documentos quedarán sin asociar.")

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - Asociar documentos a educación y experiencia (Priority: P2)

**Goal**: Extender la asociación a entradas de educación y experiencias laborales, incluida la carga desde su página de edición y la eliminación segura.

**Independent Test**: Cargar un PDF, asociarlo a un registro de educación y a una experiencia, verificarlo desde ambas vistas, desasociarlo y eliminar las entidades conservando los documentos.

### Tests for User Story 3 ⚠️

- [x] T038 [P] [US3] Extend `tests/Feature/DocumentLinkTest.php`: associate/unlink for `education` and `experience`; upload with `linkable_type` redirects back to `education.edit` / `experiences.edit`; deleting an education entry or an experience keeps documents unassociated (FR-014); invalid target id for the type → validation error

### Implementation for User Story 3

- [x] T039 [US3] Update `EducationController@edit/@index` and `WorkExperienceController@edit/@index` in `app/Http/Controllers/EducationController.php` and `app/Http/Controllers/WorkExperienceController.php`: pass `documents` (edit) and `documents_count` (index) with the same serialization as T032
- [x] T040 [P] [US3] Update `resources/js/pages/Education/Edit.tsx` to render `LinkedDocumentsSection` (`entityType="education"`) and `resources/js/pages/Education/Index.tsx` delete dialog copy with `documents_count` warning
- [x] T041 [P] [US3] Update `resources/js/pages/Experiences/Edit.tsx` to render `LinkedDocumentsSection` (`entityType="experience"`) and `resources/js/pages/Experiences/Index.tsx` delete dialog copy with `documents_count` warning

**Checkpoint**: All user stories should now be independently functional

---

## Phase 6: User Story 4 - Gestionar el repositorio documental (Priority: P2)

**Goal**: Listado con filtros y búsqueda, edición de metadatos, eliminación con confirmación y verificación de integridad completa bajo demanda con reporte.

**Independent Test**: Con documentos asociados y sin asociar, listar, filtrar, buscar, editar metadatos, ejecutar la verificación completa y eliminar un documento con confirmación.

### Tests for User Story 4 ⚠️

- [x] T042 [P] [US4] Extend `tests/Feature/DocumentTest.php`: `PUT /documents/{id}` updates `title`/`description` only (checksum/size unchanged); delete removes file (assert `Storage::missing`) + links and keeps entities; `GET /documents?q=…&status=…&entity_type=…&entity_id=…` filters correctly and returns active filters
- [x] T043 [P] [US4] Extend `tests/Feature/DocumentIntegrityTest.php`: `POST /documents/verify` returns flash `verification` with `{checked, ok, unavailable, problems[]}` and updates `is_available`/`last_verified_at` for all documents (intact, missing, altered)
- [x] T044 [P] [US4] Create component tests `resources/js/components/__tests__/VerificationReport.test.tsx` (totals + problems list, no report when null) and `resources/js/components/__tests__/DocumentMetaDialog.test.tsx` (prefill, submit payload)

### Implementation for User Story 4

- [x] T045 [US4] Create `app/Http/Requests/UpdateDocumentRequest.php`: `title` nullable string max 255; `description` nullable string max 2000 (file/checksum/original_filename not editable)
- [x] T046 [US4] Implement `DocumentController@update/@destroy` in `app/Http/Controllers/DocumentController.php`: update metadata only (toast "Documento actualizado."); destroy deletes row + file via model hook + links cascade (toast "Documento eliminado."), redirect → `documents.index`
- [x] T047 [US4] Implement `DocumentController@verify` in `app/Http/Controllers/DocumentController.php`: iterate all documents (existence + `hash_file('sha256')`), update `is_available`/`last_verified_at`, redirect back with flash `verification: {checked, ok, unavailable, problems: [{id, display_name, reason}]}` (`reason` ∈ `missing|altered`)
- [x] T048 [US4] Extend `DocumentController@index` in `app/Http/Controllers/DocumentController.php`: filters `q` (title/original_filename like), `status` (`all|linked|unlinked`), `entity_type` + `entity_id`; pass `filters` and `filterOptions` (`DocumentEntities::options()`) props
- [x] T049 [US4] Register remaining routes in `routes/web.php`: `documents.update` (PUT/PATCH `/documents/{document}`), `documents.destroy` (DELETE), `documents.verify` (POST `/documents/verify`)
- [x] T050 [P] [US4] Create `resources/js/components/documents/DocumentMetaDialog.tsx`: edit `title`/`description` with `useForm` + `PUT /documents/{id}`
- [x] T051 [P] [US4] Create `resources/js/components/documents/VerificationReport.tsx`: render the `verification` flash (totals + problems with reason labels "Archivo ausente"/"Archivo alterado")
- [x] T052 [US4] Update `resources/js/pages/Documents/Index.tsx`: filter bar (search `q`, estado select, entidad select from `filterOptions`, clear action), "Editar" (`DocumentMetaDialog`) and "Eliminar" (`DeleteConfirmDialog` listing associations) row actions, "Verificar integridad" button (`POST /documents/verify`) + `VerificationReport`, empty search state

**Checkpoint**: All user stories should now be independently functional

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [x] T053 Update `database/seeders/DemoSeeder.php` `clearExistingData()`: delete stored document files (`Storage::disk('local')->deleteDirectory('documents')`) and truncate document tables before reseeding
- [x] T054 [P] Extend `tests/Feature/PersistenceTest.php` with a document + associations written via HTTP and re-read with independent queries after "restart" (FR-008, SC-001)
- [x] T055 Run full CI locally: `npm run check`, `npm run types:check`, `vendor/bin/pint --parallel --test`, `vendor/bin/phpstan analyse`, `php artisan test`, `npm test` — fix any findings
- [x] T056 Execute [quickstart.md](./quickstart.md) validation end-to-end (US1–US4 flows, conservation hash check, edge cases) on a fresh SQLite database

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
    - **US1 (P1)**: first story; delivers the repository base (upload/conservation/viewer)
    - **US2 (P1)**: depends on US1 (uses document rows and the repository pages); delivers the shared link mechanism (`DocumentLinkController`, `DocumentEntities`, `LinkedDocumentsSection`) and certification integration
    - **US3 (P2)**: depends on US2 (reuses the shared link mechanism and card) - adds education + experience integration only
    - **US4 (P2)**: depends on US1 and US2 (delete confirmation lists associations serialized since US2); filters and verification are forward-compatible with US3 but do not require it
- **Polish (Final Phase)**: Depends on all desired user stories being complete

### Within Each User Story

- Tests (T013/T014, T025/T026, T038, T042–T044) MUST be written and FAIL before implementation
- Requests before controllers; controllers before routes; routes before UI wiring
- Shared components (`DocumentForm`, `LinkedDocumentsSection`) before consuming pages
- Story complete before moving to next priority

### Parallel Opportunities

- T001/T002 (setup) and T003–T012 (foundational, except sequential pairs) can run in parallel where marked [P]
- Within US1: T013/T014 (tests) and T019/T020 (lib + form) are parallelizable
- Within US2: T025/T026 (tests) parallel; T033/T034 (new components) parallel
- Within US3: T040/T041 (education vs experience pages) parallel
- Within US4: T042–T044 (tests) parallel; T050/T051 (dialogs) parallel
- T053/T054 (polish) parallel

---

## Parallel Example: User Story 4

```bash
# Tests first (different files):
Task: "Extend tests/Feature/DocumentTest.php: metadata, delete, filters"
Task: "Extend tests/Feature/DocumentIntegrityTest.php: verify report"
Task: "Create VerificationReport + DocumentMetaDialog component tests"

# Then new components (different files):
Task: "Create resources/js/components/documents/DocumentMetaDialog.tsx"
Task: "Create resources/js/components/documents/VerificationReport.tsx"
```

---

## Implementation Strategy

### MVP First (User Story 1)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Test User Story 1 independently
5. Deploy/demo if ready

### Incremental Delivery

1. Setup + Foundational → Foundation ready
2. US1 → repository with conservation/viewer → validate (MVP)
3. US2 → certification association (core module use case) → validate
4. US3 → education + experience association → validate
5. US4 → metadata, filters, delete, bulk verification → validate
6. Polish → seeder, persistence regression, CI, quickstart

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
    - Developer A: US1 (must finish first)
    - After US1: Developer B: US2; Developer C starts US4 filters/verify (repository-side only) in parallel with US2; US3 starts after US2's shared link mechanism lands

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence

---

## Validación funcional (2026-10-15)

Validación manual en navegador realizada y aprobada por el usuario:

1. El módulo Documentos y Evidencias carga correctamente.
2. Los PDFs originales pueden visualizarse.
3. Los documentos pueden asociarse a experiencias laborales.
4. Un documento puede reutilizarse en diferentes entidades.
5. Desasociar un documento no elimina el archivo original.
6. La búsqueda y los filtros funcionan correctamente.
7. La carga de un PDF duplicado no genera un segundo registro.
8. La verificación de integridad funciona.
9. Los documentos y asociaciones persisten después de reiniciar el servidor.

**Estado**: 56/56 tareas completadas; validación técnica automatizada y validación manual aprobadas.
