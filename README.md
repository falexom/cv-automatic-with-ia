# CV Automático

CV Automático es una aplicación web **local y monousuario** para gestionar el **Perfil Profesional Maestro**. No incluye autenticación web ni está preparada para ejecutarse como un servidor público o un servicio para terceros.

- **Stack**: Laravel 13 + PHP 8.3–8.5, Inertia + React 19 + TypeScript, Tailwind CSS 4, shadcn/ui, SQLite local.
- La información se guarda en el equipo. La adaptación de un CV a una oferta invoca el **Codex CLI** y envía a Codex/OpenAI el texto de la oferta y los datos profesionales aprobados tras consentimiento; requiere conexión a internet. No usa el SDK de OpenAI ni incluye credenciales.
- Cada instalación requiere Codex CLI, su propia sesión iniciada con ChatGPT (`codex login`) y acceso al modelo configurado. La integración verifica el método y rechaza sesiones con API key. Clonar el código o instalar dependencias no concede acceso a la cuenta del autor.
- Especificaciones: `specs/001-perfil-profesional-maestro/` y `specs/002-documentos-evidencias/` (spec, plan, modelo de datos, contratos, quickstart, tasks).
- Reglas del proyecto: `.specify/memory/constitution.md`.

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

Inicia Laravel y Vite en terminales separadas. Laravel escucha explícitamente solo en la interfaz local; Vite también está configurado para enlazarse a `127.0.0.1`:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

```bash
npm run dev
```

Abre `http://127.0.0.1:8000` (redirige a `/profile`). No configures `0.0.0.0`, un proxy público ni reenvío de puertos para esta aplicación monousuario.

Comparte el proyecto mediante Git, publicando el código versionado. No distribuyas la carpeta de trabajo completa: la base SQLite, copias previas, documentos, fotografías, sesiones y credenciales locales no forman parte del proyecto compartible.

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

- SPEC-001 — Perfil Profesional Maestro: cerrado formalmente (2026-10-22) — validación manual del responsable aprobada; SC-001 retirado por decisión del responsable; pendiente de integración en `main` (commits `63bb79c`, `a04954d`).
- SPEC-002 — Documentos y Evidencias: cerrado formalmente el 2026-10-21; integración en main: `1f154bc`; sin tag.
