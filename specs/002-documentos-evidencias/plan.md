# Implementation Plan: Documentos y Evidencias

**Branch**: `002-documentos-evidencias` | **Date**: 2026-10-15 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/002-documentos-evidencias/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command; its definition describes the execution workflow.

## Summary

Implementar el repositorio documental local del CV Automático: carga validada de PDFs originales (un archivo por operación, ≤ 20 MB), conservación sin alteración en almacenamiento privado con reutilización automática de archivos idénticos (una sola copia por contenido), asociación N:M a cursos/certificaciones, educación y experiencia, visualización del original dentro de la aplicación con verificación de integridad al acceder, verificación completa bajo demanda, y gestión del repositorio (metadatos, filtros, eliminación con confirmación). Sin OCR, sin extracción y sin generación de CV (fuera de alcance).

**Enfoque técnico**: monolito Laravel 13 + Inertia/React existente, sin dependencias nuevas. Los originales se guardan en el disco `local` privado (`storage/app/private/documents/{sha256}.pdf`) y el nombre del archivo se deriva de su checksum SHA-256, lo que garantiza la copia única y la reutilización automática (FR-007). Las asociaciones usan tablas pivote dedicadas (una por entidad permitida), coherentes con los pivotes de habilidades existentes y con borrado en cascada a nivel de base de datos. La visualización usa el visor PDF nativo del navegador sobre un endpoint de streaming `inline` que verifica integridad al acceder; una verificación completa bajo demanda actualiza el estado de disponibilidad. Pruebas con Pest 4 (backend) y Vitest + RTL (componentes), siguiendo las convenciones del proyecto.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13.x) + TypeScript 5.x (React 19)

**Primary Dependencies**: las mismas del proyecto — Laravel 13, Inertia (`inertiajs/inertia-laravel` + `@inertiajs/react`), Tailwind CSS 4, shadcn/ui (Radix UI), Vite; visor PDF nativo del navegador; **no se agregan dependencias nuevas** (ni PDF.js, ni parsers de PDF, ni librerías de subida). Dev: Pest 4, Vitest, React Testing Library

**Storage**: SQLite local (metadatos y asociaciones) + PDFs originales en el disco `local` privado del framework (`storage/app/private/documents/`, con la opción `serve => true` ya configurada); sin almacenamiento remoto ni servicios externos

**Testing**: Pest 4 (Feature/Unit sobre SQLite en memoria y `Storage::fake('local')`) + Vitest + React Testing Library; comandos `php artisan test` y `npm test`

**Target Platform**: Aplicación web local (localhost) en navegador de escritorio; sin conexión a internet; un solo usuario

**Project Type**: Aplicación web (monolito Laravel + SPA Inertia/React en el mismo proyecto)

**Performance Goals**: carga de un PDF de 20 MB < 2 s en local; apertura del visor < 1 s; respuesta del repositorio percibida como instantánea en uso monousuario, sin benchmarks de volumen ni pruebas de carga

**Constraints**: solo PDF ≤ 20 MB (límite ajustable); un archivo por operación; originales conservados sin modificación, recomposición ni renombrado destructivo (Principio VII); verificación de integridad al visualizar y bajo demanda; configuración PHP recomendada `upload_max_filesize = 25M` y `post_max_size = 25M` (documentada en quickstart); interfaz en español; sin OCR/extracción (Principio VIII queda para la capacidad de extracción)

**Scale/Scope**: 1 usuario; repositorio local de tamaño moderado (sin volumen objetivo ni requisito de rendimiento en el MVP); 1 tabla de entidad nueva + 3 tablas pivote; 3 pantallas nuevas (`Documents/Index`, `Documents/Create`, `Documents/Viewer`), 1 sección de documentos en las páginas de edición de certificaciones, educación y experiencia, 1 entrada de navegación

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio                                       | Estado | Evidencia del diseño                                                                                                                  |
| ----------------------------------------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------- |
| I. Fuente única de verdad                       | PASA   | Los documentos son evidencia conservada, no datos duplicados; asociaciones por referencia; no se copian datos del perfil              |
| II. El CV es resultado                          | PASA   | Sin generación de CV; los documentos no son fuente editable de datos (FR-028)                                                         |
| III. Separación datos/reglas/presentación       | PASA   | Almacenamiento y reglas en backend; visor y UI como presentación; sin reglas en plantillas                                            |
| IV. MVP acotado                                 | PASA   | Alcance idéntico al spec: sin OCR, extracción, selección ni concatenación; experiencia incluida por decisión explícita registrada     |
| V. Perfil Maestro                               | PASA   | Extiende certificaciones ("Archivo PDF original") y educación ("Evidencia documental asociada"); experiencia por clarificación        |
| VI. Perfiles como configuración                 | N/A    | Capacidad posterior; sin conflicto                                                                                                    |
| VII. Conservación de originales                 | PASA   | Archivo guardado byte a byte en disco privado; checksum como identidad; nunca se re-comprime, regenera ni modifica (FR-003)           |
| VIII. Extracción primero, OCR solo si necesario | N/A    | OCR y extracción explícitamente fuera de alcance (FR-027)                                                                             |
| IX. Confirmación humana obligatoria             | N/A    | No hay datos extraídos en esta capacidad; sin conflicto                                                                               |
| X–XI. CV documentado y selección de evidencias  | PASA   | Solo se prepara la base (originales íntegros + asociaciones estables, FR-029); selección y concatenación quedan para capacidad futura |
| XII. Arquitectura simple local                  | PASA   | Almacenamiento local privado del framework; SQLite; sin servicios externos; sin nuevas dependencias                                   |
| XIII. Evitar complejidad prematura              | PASA   | Sin colas, sin librerías PDF, sin visor propio; pivotes simples; rutas delgadas                                                       |
| XIV. Datos relacionales                         | PASA   | Asociaciones N:M con pivotes dedicados y claves únicas compuestas; sin copias del archivo por entidad                                 |
| XV. UI orientada a tareas                       | PASA   | Nueva sección "Documentos" en la navegación principal; carga y asociación en pocos pasos                                              |
| XVI–XVIII. Vista previa/plantilla               | N/A    | Capacidades posteriores; sin conflicto                                                                                                |
| XIX. Spec verificable                           | PASA   | Criterios medibles (SC-001…SC-010) y pruebas requeridas en el spec                                                                    |
| XX. Tareas dentro del Spec                      | PASA   | Flujo Spec → Plan → Tasks                                                                                                             |
| XXI. Pruebas proporcionales al riesgo           | PASA   | Pest cubre conservación íntegra, importación, asociaciones, confirmaciones e integridad (puntos críticos de la Constitución)          |
| XXII. No romper datos                           | PASA   | Migraciones aditivas (1 tabla + 3 pivotes); sin cambios destructivos sobre datos existentes                                           |
| XXIII. Privacidad local                         | PASA   | Todo permanece en disco y SQLite locales; sin llamadas externas (FR-026)                                                              |
| XXIV. No secretos en repositorio                | PASA   | Sin claves nuevas; configuración vía `.env` ya existente                                                                              |
| XXV–XXVI. Gobernanza de alcance                 | PASA   | Inclusión de experiencia aprobada explícitamente en `/speckit.clarify` (2026-10-15); sin otras ampliaciones                           |

**Resultado**: sin violaciones. No se requiere Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/002-documentos-evidencias/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
│   └── http-contracts.md
├── checklists/
│   └── requirements.md  # Spec quality checklist (/speckit.specify output)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
# Aplicación Laravel 13 + Inertia/React (monolito) en la raíz del repositorio
app/
├── Http/
│   ├── Controllers/
│   │   ├── DocumentController.php        # index / create / store / update / destroy / viewer / file / verify
│   │   ├── DocumentLinkController.php    # store / destroy (asociar / desasociar)
│   │   ├── CertificationController.php   # edit(): agrega prop documents
│   │   ├── EducationController.php       # edit(): agrega prop documents
│   │   └── WorkExperienceController.php  # edit(): agrega prop documents
│   └── Requests/
│       ├── StoreDocumentRequest.php      # archivo PDF, tamaño, metadatos, asociación opcional
│       ├── UpdateDocumentRequest.php     # title / description
│       └── StoreDocumentLinkRequest.php  # linkable_type + linkable_id (allow-list)
├── Models/
│   ├── Document.php                      # checksum, metadatos, relaciones N:M, borrado de archivo
│   ├── Certification.php                 # + documents()
│   ├── EducationEntry.php                # + documents()
│   └── WorkExperience.php                # + documents()
└── Support/
    ├── PdfFile.php                       # validación estructural (magic %PDF-, marcador %%EOF) y saneado de nombre de archivo
    └── DocumentEntities.php              # allow-list de entidades asociables, resolución y etiquetas para la UI

database/
├── migrations/
│   ├── 2026_10_15_000011_create_documents_table.php
│   ├── 2026_10_15_000012_create_document_certification_table.php
│   ├── 2026_10_15_000013_create_document_education_entry_table.php
│   └── 2026_10_15_000014_create_document_work_experience_table.php
└── factories/
    └── DocumentFactory.php

resources/
└── js/
    ├── components/
    │   ├── documents/
    │   │   ├── LinkedDocumentsSection.tsx   # tarjeta en Edit de certificaciones/educación/experiencia
    │   │   ├── AssociateDocumentDialog.tsx  # asociar a una entidad desde el repositorio
    │   │   ├── DocumentMetaDialog.tsx       # editar título/descripción
    │   │   └── VerificationReport.tsx       # resultado de la verificación bajo demanda
    │   └── forms/
    │       └── DocumentForm.tsx             # formulario de carga (archivo + metadatos + asociación opcional)
    ├── layouts/AppLayout.tsx                # + entrada de navegación "Documentos"
    ├── lib/documents.ts                     # formatBytes y etiquetas de tipo de entidad
    └── pages/
        └── Documents/
            ├── Index.tsx                    # repositorio: listado, filtros, acciones, verificación
            ├── Create.tsx                   # carga de un PDF (un archivo por operación)
            └── Viewer.tsx                   # visualización del original (visor nativo) o estado no disponible

routes/web.php                                # + rutas documents.*

tests/
├── Feature/
│   ├── DocumentTest.php                     # carga, validación, reutilización, metadatos, eliminación
│   └── DocumentIntegrityTest.php            # visor, archivo faltante/alterado, verificación bajo demanda
└── Fixtures/                                # PDF mínimo válido para pruebas
```

**Structure Decision**: se mantiene la aplicación única Laravel + Inertia/React (monolito) ya existente, sin capa de API separada ni servicios nuevos (Principios XII–XIII). El repositorio documental se integra como una sección más de la navegación (Principio XV) con controladores delgados y componentes React siguiendo los patrones actuales del proyecto (URLs por string, FormRequests con mensajes en español, flash `toast`, `DeleteConfirmDialog`, `EmptyState`).

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

Sin violaciones: no se requiere justificación de complejidad.

## Post-Design Constitution Re-Check (Phase 1)

Re-evaluado tras generar `research.md`, `data-model.md`, `contracts/http-contracts.md` y `quickstart.md`:

- **I / VII (fuente única y conservación)**: el archivo se guarda una sola vez por contenido (`documents/{sha256}.pdf`) y nunca se modifica; el checksum es la identidad y la base de la verificación de integridad.
- **XIV (relacional)**: pivotes dedicados con clave única compuesta y borrado en cascada; eliminar una entidad elimina solo vínculos (FR-014), nunca documentos ni archivos.
- **XII / XIII (simplicidad)**: sin dependencias nuevas; visor nativo del navegador; endpoint de streaming del propio framework; señalización de duplicados por checksum sin índices adicionales.
- **XXI (pruebas)**: el plan de pruebas cubre los puntos críticos constitucionales de esta capacidad (importación de certificados, conservación de originales) y los flujos de asociación, eliminación e integridad.
- **XXIII / XXIV (privacidad y secretos)**: los PDFs y metadatos permanecen en almacenamiento local privado; ninguna llamada externa.

Sin violaciones nuevas introducidas por el diseño.
