# Fase 1 — Modelo de Datos: Perfil Profesional Maestro

**Feature**: `001-perfil-profesional-maestro` | **Fecha**: 2026-10-15
**Fuente**: [spec.md](./spec.md) · Decisiones: [research.md](./research.md)

## Visión general

Un **Perfil Profesional Maestro** único (una fila) es la raíz de todas las relaciones. Las habilidades forman un catálogo único y se relacionan N:M con experiencias, certificaciones y proyectos.

```text
Profile (1)
├── ProfileLink (N)                 enlaces adicionales (etiqueta + URL)
├── WorkExperience (N)  ──N:M── Skill
├── EducationEntry (N)              (sin relación con habilidades)
├── Certification (N)   ──N:M── Skill
└── Project (N)         ──N:M── Skill
```

## Convenciones

- **Fechas**: cada fecha se modela como `*_year` (integer, opcional) + `*_month` (integer 1–12, opcional). `month` sin `year` es inválido. Regla de comparación (FR-034): año sin mes = enero en fechas de inicio y diciembre en fechas de fin.
- **Orden cronológico** (FR-010 y equivalentes): descendente por `(start_year, COALESCE(start_month, 1))`; registros sin `start_year` al final.
- **Eliminaciones**: definitivas, con confirmación en UI (FR-026). Los pivotes se eliminan en cascada; las entidades no se afectan entre sí.
- **Normalización**: los textos de deduplicación usan minúsculas + recorte + colapso de espacios (Unicode `mb_*`).

## Entidades

### Profile — Perfil Profesional Maestro (singleton)

| Columna | Tipo | Nulo | Reglas |
|---------|------|------|--------|
| id | integer PK | no | |
| full_name | string(255) | no | FR-006: obligatorio |
| professional_title | string(255) | sí | |
| phone | string(50) | sí | |
| email | string(255) | sí | formato de correo (FR-005) |
| location | string(255) | sí | |
| linkedin_url | string(2048) | sí | formato URL (FR-005) |
| github_url | string(2048) | sí | formato URL (FR-005) |
| website_url | string(2048) | sí | formato URL (FR-005) |
| summary | text | sí | resumen reutilizable (FR-004) |
| timestamps | | | |

- **Regla de instancia única**: se crea con `firstOrCreate`; las pruebas verifican que no puede existir más de una.
- **Relaciones**: `hasMany` ProfileLink, WorkExperience, EducationEntry, Certification, Project.

### ProfileLink — Enlaces adicionales (FR-003)

| Columna | Tipo | Nulo | Reglas |
|---------|------|------|--------|
| id | integer PK | no | |
| profile_id | FK → profiles | no | cascade delete |
| label | string(100) | no | obligatorio si la fila existe |
| url | string(2048) | no | formato URL (FR-005) |

- Gestión: se editan dentro del formulario del perfil; al guardar se reemplaza la colección completa (sin endpoints propios).

### WorkExperience — Experiencia laboral (FR-007–FR-011)

| Columna | Tipo | Nulo | Reglas |
|---------|------|------|--------|
| id | integer PK | no | |
| profile_id | FK → profiles | no | cascade delete |
| company | string(255) | no | FR-033: obligatorio |
| position | string(255) | no | FR-033: obligatorio |
| start_year | integer | sí | 1900–(año actual + 10) |
| start_month | integer | sí | 1–12; requiere `start_year` |
| end_year | integer | sí | 1900–(año actual + 10) |
| end_month | integer | sí | 1–12; requiere `end_year` |
| is_current | boolean | no | default false (FR-008) |
| description | text | sí | |
| responsibilities | text | sí | |
| achievements | text | sí | |
| timestamps | | | |

- **Validación de coherencia** (FR-009): si hay `end_year` y `start_year`, `(end_year, end_month ?? 12) ≥ (start_year, start_month ?? 1)`.
- **Transiciones de estado** (caso límite del spec):
  - Al guardar con fecha de fin ⇒ `is_current = false`.
  - Al quitar la fecha de fin ⇒ `is_current = true`.
  - Marcar `is_current` exige `end_year = null`.
- **Relaciones**: `belongsToMany` Skill (pivote `skill_work_experience`).
- Solapamiento entre experiencias: permitido, sin advertencia.

### EducationEntry — Educación (FR-012–FR-014)

| Columna | Tipo | Nulo | Reglas |
|---------|------|------|--------|
| id | integer PK | no | |
| profile_id | FK → profiles | no | cascade delete |
| institution | string(255) | no | FR-033: obligatorio |
| program | string(255) | no | FR-033: obligatorio |
| degree_level | string(100) | sí | |
| start_year / start_month | integer | sí | igual que WorkExperience |
| end_year / end_month | integer | sí | igual que WorkExperience |
| is_in_progress | boolean | no | default false (FR-013) |
| description | text | sí | |
| timestamps | | | |

- Misma validación de fechas que WorkExperience (FR-014).
- Transiciones de estado idénticas a `is_current` de WorkExperience (simetría).
- **Sin relación con habilidades** (el spec solo vincula habilidades a experiencia, certificaciones y proyectos).

### Certification — Curso / certificación (FR-015–FR-018)

| Columna | Tipo | Nulo | Reglas |
|---------|------|------|--------|
| id | integer PK | no | |
| profile_id | FK → profiles | no | cascade delete |
| name | string(255) | no | FR-033: obligatorio |
| institution | string(255) | sí | usada para la advertencia de duplicado |
| date_year | integer | sí | 1900–(año actual + 10) |
| date_month | integer | sí | 1–12; requiere `date_year` |
| duration | string(100) | sí | texto libre (p. ej. "40 horas") |
| code | string(255) | sí | código o identificador |
| validation_url | string(2048) | sí | formato URL (FR-005 local) |
| category | string(100) | sí | texto libre |
| timestamps | | | |

- **Sin fecha**: permitido (FR-017); no se considera incompleto.
- **Advertencia de duplicado** (FR-018): si existe otra fila con `name` e `institution` normalizados iguales (mismo `profile_id`), el guardado se detiene mostrando el registro existente; solo continúa con `confirm_duplicate=true`.
- **Relaciones**: `belongsToMany` Skill (pivote `skill_certification`).
- La relación con el PDF original se definirá en la capacidad de repositorio documental (no se modela aquí).

### Skill — Habilidad (FR-019–FR-021)

| Columna | Tipo | Nulo | Reglas |
|---------|------|------|--------|
| id | integer PK | no | |
| profile_id | FK → profiles | no | cascade delete |
| name | string(100) | no | FR-033: obligatorio; formato visible deseado por el usuario |
| normalized_name | string(100) | no | calculado: minúsculas + trim + colapso de espacios |
| category | string(20) | no | FR-033: obligatorio; valores: `tecnica`, `funcional`, `metodologica`, `herramienta`, `tecnologia` |
| timestamps | | | |

- **Único**: índice único compuesto `(profile_id, normalized_name)` (FR-020).
- **Categorías** (FR-019): técnica, funcional, metodológica, herramienta, tecnología (etiquetas en español en la UI). Las tecnologías del perfil (PHP, React, SQL…) se registran aquí con categoría `tecnologia` (clarificación aprobada).
- Sin niveles de dominio en el MVP.
- **Relaciones**: `belongsToMany` WorkExperience, Certification, Project (pivotes correspondientes). La vista de una habilidad lista en qué elementos se usa (FR-021).

### Project — Proyecto (FR-022–FR-024)

| Columna | Tipo | Nulo | Reglas |
|---------|------|------|--------|
| id | integer PK | no | |
| profile_id | FK → profiles | no | cascade delete |
| name | string(255) | no | FR-033: obligatorio |
| description | text | sí | |
| role | string(255) | sí | |
| results | text | sí | |
| url | string(2048) | sí | formato URL |
| start_year / start_month | integer | sí | igual que WorkExperience |
| end_year / end_month | integer | sí | igual que WorkExperience |
| timestamps | | | |

- Misma validación de fechas (FR-024). Sin flag "en curso" en el spec: un proyecto sin fecha de fin simplemente no muestra fin.
- **Relaciones**: `belongsToMany` Skill (pivote `skill_project`).

## Tablas pivote

| Tabla | Columnas | Reglas |
|-------|----------|--------|
| skill_work_experience | skill_id FK cascade, work_experience_id FK cascade | PK compuesta; sin duplicados |
| skill_certification | skill_id FK cascade, certification_id FK cascade | PK compuesta; sin duplicados |
| skill_project | skill_id FK cascade, project_id FK cascade | PK compuesta; sin duplicados |

## Matriz de validaciones (resumen para pruebas)

| Regla | Entidad(es) | Requisito |
|-------|-------------|-----------|
| Nombre obligatorio | Profile | FR-006 |
| Formatos de correo/URL | Profile, ProfileLink, Certification, Project | FR-005 |
| Identificadores mínimos | WorkExperience, EducationEntry, Certification, Skill, Project | FR-033 |
| Mes sin año inválido | Todas las fechas | FR-034 |
| Fin ≥ inicio | WorkExperience, EducationEntry, Project | FR-009, FR-014, FR-024 |
| Duplicado de habilidad bloqueado | Skill | FR-020 |
| Duplicado de certificación advertido | Certification | FR-018 |
| Sin fecha permitido | Certification | FR-017 |
| Vigente/en curso sin fin | WorkExperience, EducationEntry | FR-008, FR-013 |
| Confirmación antes de eliminar | Todas | FR-026 |

## Trazabilidad FR → modelo

- FR-001 → Profile singleton · FR-002/003 → Profile + ProfileLink · FR-004 → Profile.summary
- FR-007–011 → WorkExperience + pivote · FR-012–014 → EducationEntry
- FR-015–018 → Certification + pivote · FR-019–021 → Skill + pivotes
- FR-022–024 → Project + pivote · FR-025 → claves foráneas y vista única de datos
- FR-026 → borrado con confirmación (UI) · FR-027 → SQLite persistente
- FR-030 → charset UTF-8 (`utf8mb4` equivalente en SQLite: texto Unicode nativo)
- FR-033/034 → reglas de validación de la sección anterior
