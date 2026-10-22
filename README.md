# CV Automático

Aplicación web local monousuario para gestionar el **Perfil Profesional Maestro**: una sola trayectoria profesional registrada una única vez (datos personales, resumen, experiencia laboral, educación, cursos y certificaciones, habilidades y proyectos).

- **Stack**: Laravel 13 + PHP 8.3–8.5, Inertia + React 19 + TypeScript, Tailwind CSS 4, shadcn/ui, SQLite local.
- **Sin autenticación** y **sin servicios externos en las capacidades implementadas**: la información permanece en tu equipo. La auditoría offline de SPEC-001 (T063) sigue pendiente.
- Especificaciones: `specs/001-perfil-profesional-maestro/` y `specs/002-documentos-evidencias/` (spec, plan, modelo de datos, contratos, quickstart, tasks).
- Reglas del proyecto: `.specify/memory/constitucion.md`.

## Requisitos

- PHP 8.3+ (recomendado 8.4) con extensión `pdo_sqlite`
- Composer 2.x
- Node.js 22+ y npm

## Puesta en marcha

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

Desarrollo (servidor + Vite):

```bash
composer dev
```

Abrir `http://localhost:8000` (redirige a `/profile`).

## Pruebas

```bash
php artisan test      # Pest: tests Feature + Unit (SQLite en memoria)
npm test              # Vitest + React Testing Library
npm run check         # formato + lint + tipos (Vite+)
```

## Estructura principal

```
app/
├── Http/Controllers/      # Un controlador por sección del perfil
├── Http/Requests/         # Validaciones (incluye reglas de fechas mes-año)
├── Models/                # Profile, ProfileLink, WorkExperience, EducationEntry, Certification, Skill, Project, Document
└── Support/               # YearMonth (reglas de fechas), NameNormalizer (duplicados), PdfFile (validación de PDF), DocumentEntities (entidades asociables)

resources/js/
├── components/            # UI compartida (YearMonthInput, SkillMultiSelect, DeleteConfirmDialog...)
├── layouts/AppLayout.tsx  # Navegación principal
└── pages/                 # Una sección por carpeta (Profile, Experiences, Education, Certifications, Skills, Projects, Documents)
```

## Alcance actual

Implementadas las capacidades **Perfil Profesional Maestro** (`specs/001-perfil-profesional-maestro/`) y **Documentos y Evidencias** (`specs/002-documentos-evidencias/`): PDFs originales conservados sin alteración, visualización dentro de la aplicación y asociación a certificaciones, educación y experiencia laboral.
Fuera de alcance por ahora: extracción/OCR, perfiles objetivo (Analista/Programador), generación de CV, exportación PDF y CV documentado.

### Estado de las especificaciones

- SPEC-001 — Perfil Profesional Maestro: implementado; cierre formal pendiente (T063 y T064).
- SPEC-002 — Documentos y Evidencias: cerrado formalmente el 2026-10-21; integración en main: `1f154bc`; sin tag.
