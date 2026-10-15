# Implementation Plan: Perfil Profesional Maestro

**Branch**: `001-perfil-profesional-maestro` | **Date**: 2026-10-15 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-perfil-profesional-maestro/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command; its definition describes the execution workflow.

## Summary

Implementar la gestión completa del Perfil Profesional Maestro como una aplicación web local monousuario: datos personales, enlaces, resumen, experiencia laboral, educación, cursos y certificaciones, habilidades y proyectos, con relaciones reutilizables (habilidad ↔ experiencia/certificación/proyecto), validaciones de integridad (fechas coherentes con precisión mes-año o solo año, formatos de contacto, duplicados de habilidades) y persistencia local.

**Enfoque técnico**: aplicación Laravel 13 (monolito) con frontend React 19 + TypeScript mediante Inertia, Tailwind CSS 4 y componentes shadcn/ui; base de datos SQLite local; sin autenticación (monousuario); sin servicios externos. Se parte del starter kit oficial Laravel React y se elimina el scaffolding de autenticación. Pruebas con Pest 4 (backend: persistencia, validaciones, relaciones) y Vitest + React Testing Library (confirmaciones, duplicados, estados vacíos).

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13.x) + TypeScript 5.x (React 19)

**Primary Dependencies**: Laravel 13, Inertia (adaptador Laravel `inertiajs/inertia-laravel` + `@inertiajs/react`), React 19, Tailwind CSS 4, shadcn/ui (Radix UI), Vite; Wayfinder para rutas tipadas. Dev: Pest 4, Vitest, React Testing Library

**Storage**: SQLite local (`database/database.sqlite`) con claves foráneas activadas; sin almacenamiento remoto ni servicios externos

**Testing**: Pest 4 (tests Feature/Unit sobre SQLite en memoria) + Vitest + React Testing Library (componentes React); comandos `php artisan test` y `npm test`

**Target Platform**: Aplicación web local (localhost) en navegador de escritorio; funciona sin conexión a internet; un solo usuario

**Project Type**: Aplicación web (monolito Laravel + SPA Inertia/React en el mismo proyecto)

**Performance Goals**: Guardado de formularios percibido como instantáneo (< 300 ms en local); transiciones entre secciones < 200 ms; sin dependencia de red externa

**Constraints**: Todos los datos permanecen locales (FR-031); sin autenticación ni multiusuario; interfaz en español; fechas con precisión mes-año o solo año (FR-034); sin carga de archivos en esta capacidad

**Scale/Scope**: 1 usuario; hasta cientos de registros por sección; 7 tablas de entidad + 3 tablas pivote; 6 secciones de UI + pantalla de perfil; ~14 pantallas (listas y formularios)

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio                                    | Estado | Evidencia del diseño                                                                 |
| -------------------------------------------- | ------ | ------------------------------------------------------------------------------------ |
| I. Fuente única de verdad                    | PASA   | Perfil Maestro único con entidades normalizadas y relaciones; sin copias por sección |
| II. El CV es resultado                       | PASA   | Capacidad excluye edición de CV; FR-032 cubierto                                     |
| III. Separación datos/reglas/presentación    | PASA   | Entidades en backend; relevancia por perfil diferida a capacidad posterior           |
| IV. MVP acotado                              | PASA   | Alcance idéntico al spec; sin funcionalidades extra                                  |
| V. Perfil Maestro                            | PASA   | Todos los campos mínimos exigidos están en el modelo de datos                        |
| VI. Perfiles como configuración              | PASA   | Fuera de alcance (capacidad posterior), sin conflicto de diseño                      |
| VII–XI. Documentos/evidencias/CV documentado | PASA   | Fuera de alcance; certificaciones no almacenan archivos                              |
| XII. Arquitectura simple local               | PASA   | Laravel + React + SQLite + almacenamiento local                                      |
| XIII. Evitar complejidad prematura           | PASA   | Monolito Inertia; sin API separada, colas ni contenedores                            |
| XIV. Datos relacionales                      | PASA   | Relaciones N:M con tablas pivote; habilidad único catálogo                           |
| XV. UI orientada a tareas                    | PASA   | Navegación directa a las 6 secciones del perfil                                      |
| XVI–XVIII. Vista previa/plantilla            | N/A    | Capacidades posteriores; sin conflicto                                               |
| XIX. Spec verificable                        | PASA   | Criterios medibles y pruebas requeridas definidos en spec                            |
| XX. Tareas dentro del Spec                   | PASA   | Flujo Spec → Plan → Tasks                                                            |
| XXI. Pruebas proporcional al riesgo          | PASA   | Pest + Vitest cubren persistencia, validaciones, relaciones y confirmaciones         |
| XXII. No romper datos                        | PASA   | Proyecto nuevo; migraciones aditivas                                                 |
| XXIII. Privacidad local                      | PASA   | Sin llamadas externas; datos solo en SQLite local                                    |
| XXIV. No secretos en repositorio             | PASA   | `.env` fuera de control de versiones; sin claves en código                           |
| XXV–XXVI. Gobernanza de alcance              | PASA   | Sin ampliaciones; decisiones ya registradas en spec                                  |

**Resultado**: sin violaciones. No se requiere Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/001-perfil-profesional-maestro/
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
│   │   ├── ProfileController.php
│   │   ├── WorkExperienceController.php
│   │   ├── EducationController.php
│   │   ├── CertificationController.php
│   │   ├── SkillController.php
│   │   └── ProjectController.php
│   └── Requests/
│       ├── UpdateProfileRequest.php
│       ├── StoreWorkExperienceRequest.php / UpdateWorkExperienceRequest.php
│       ├── StoreEducationRequest.php / UpdateEducationRequest.php
│       ├── StoreCertificationRequest.php / UpdateCertificationRequest.php
│       ├── StoreSkillRequest.php / UpdateSkillRequest.php
│       └── StoreProjectRequest.php / UpdateProjectRequest.php
├── Models/
│   ├── Profile.php
│   ├── ProfileLink.php
│   ├── WorkExperience.php
│   ├── EducationEntry.php
│   ├── Certification.php
│   ├── Skill.php
│   └── Project.php
└── Support/
    ├── YearMonth.php        # Comparación/orden de fechas mes-año o solo año (FR-034)
    └── NameNormalizer.php   # Normalización de nombres (habilidades FR-020, duplicados de certificaciones FR-018)

database/
├── migrations/
├── factories/
└── database.sqlite

resources/
├── css/app.css
├── views/app.blade.php      # Raíz Inertia
└── js/
    ├── app.tsx
    ├── components/
    │   ├── ui/              # Componentes shadcn/ui
    │   ├── YearMonthInput.tsx
    │   ├── SkillMultiSelect.tsx
    │   ├── DeleteConfirmDialog.tsx
    │   └── EmptyState.tsx
    ├── layouts/AppLayout.tsx  # Navegación principal (6 secciones)
    ├── pages/
    │   ├── Profile/Edit.tsx
    │   ├── Experiences/{Index,Create,Edit}.tsx
    │   ├── Education/{Index,Create,Edit}.tsx
    │   ├── Certifications/{Index,Create,Edit}.tsx
    │   ├── Skills/{Index,Create,Edit}.tsx
    │   └── Projects/{Index,Create,Edit}.tsx
    └── types/

routes/web.php

tests/
├── Feature/                 # Pest: ProfileTest, WorkExperienceTest, EducationTest,
│                            #       CertificationTest, SkillTest, ProjectTest, PersistenceTest
└── Unit/                    # Pest: YearMonthTest, NameNormalizerTest

resources/js/components/__tests__/   # Vitest + RTL (diálogos de confirmación, duplicados, estados vacíos)
```

**Structure Decision**: Aplicación única Laravel en la raíz del repositorio (monolito) con frontend Inertia/React compilado por Vite — no se crean proyectos backend/frontend separados ni capa de API JSON. Razón: la Constitución fija Laravel + React y exige simplicidad (Principios XII–XIII); Inertia elimina la infraestructura de API separada manteniendo el controlador Laravel clásico y componentes React.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

Sin violaciones: no se requiere justificación de complejidad.

## Post-Design Constitution Re-Check (Phase 1)

Re-evaluado tras generar `research.md`, `data-model.md`, `contracts/http-contracts.md` y `quickstart.md`:

- **I / XIV (fuente única, relacional)**: el modelo usa un perfil singleton con referencias y pivotes; las tecnologías se normalizan como habilidades de categoría Tecnologías. Sin duplicación.
- **XII / XIII (simplicidad)**: SQLite local, monolito Inertia, sin colas ni servicios; solo dependencias estándar del starter kit oficial.
- **XXI (pruebas)**: el plan de pruebas cubre los puntos críticos listados en la Constitución (persistencia, validaciones, relaciones, confirmación antes de eliminar).
- **XXIII / XXIV (privacidad y secretos)**: sin llamadas externas en runtime; configuración vía `.env`.

Sin violaciones nuevas introducidas por el diseño.
