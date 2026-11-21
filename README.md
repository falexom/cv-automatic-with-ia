# CV Automatic with IA

**Versión:** 1.1.0 (2026-11-21) · **Estado:** MVP funcional completo; SPEC-001 a SPEC-006, SPEC-008 y SPEC-009 aceptadas. SPEC-007 permanece cancelada.

**Tu experiencia, potenciada con IA.** CV Automatic with IA es una aplicación web **local y monousuario** para gestionar el **Perfil Profesional Maestro**. No incluye autenticación web ni está preparada para ejecutarse como un servidor público o un servicio para terceros.

- **Stack**: Laravel 13 + PHP 8.3–8.5, Inertia + React 19 + TypeScript, Tailwind CSS 4, shadcn/ui, SQLite local.
- La información se guarda en el equipo. La adaptación de un CV a una oferta invoca el **Codex CLI** y envía a Codex/OpenAI el texto de la oferta y los datos profesionales aprobados tras consentimiento; requiere conexión a internet. No usa el SDK de OpenAI ni incluye credenciales.
- La importación asistida de certificados extrae texto nativo y, si hace falta, aplica **OCR local** con Tesseract; solo el texto revisado y autorizado se envía a Codex/OpenAI. El PDF y las imágenes permanecen en el equipo.
- Cada instalación requiere Codex CLI, su propia sesión iniciada con ChatGPT (`codex login`) y acceso al modelo configurado. La integración verifica el método y rechaza sesiones con API key. Clonar el código o instalar dependencias no concede acceso a la cuenta del autor.
- Especificaciones y cierres: `specs/` (SPEC-001 a SPEC-009) y `docs/roadmap.md`.
- Reglas del proyecto: `.specify/memory/constitution.md`.

## Requisitos

- PHP 8.3+ (recomendado 8.4) con extensión `pdo_sqlite`
- Composer 2.x
- Node.js 22+ y Corepack para activar pnpm **11.1.1** (versión fijada en `package.json`)

## Puesta en marcha

```bash
composer install
corepack enable pnpm
pnpm --version  # debe mostrar 11.1.1
pnpm install --frozen-lockfile
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
pnpm run build:ssr
```

Inicia Laravel y Vite en terminales separadas. Laravel escucha explícitamente solo en la interfaz local; Vite también está configurado para enlazarse a `127.0.0.1`:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

```bash
pnpm run dev
```

Abre `http://127.0.0.1:8000` (redirige a `/profile`). No configures `0.0.0.0`, un proxy público ni reenvío de puertos para esta aplicación monousuario.

Comparte el proyecto mediante Git, publicando el código versionado. No distribuyas la carpeta de trabajo completa: la base SQLite, copias previas, documentos, fotografías, sesiones y credenciales locales no forman parte del proyecto compartible.

## Pruebas

```bash
php artisan test      # Pest: tests Feature + Unit (SQLite en memoria)
pnpm run test         # Vitest + React Testing Library
pnpm run check        # formato + lint + tipos (Vite+)
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

Implementadas y aceptadas las capacidades del MVP: Perfil Profesional Maestro, Documentos y Evidencias, Perfiles de CV, Generador de CV, Exportación PDF, CV Documentado, Adaptación de CV a una oferta e **Importación asistida de cursos y certificaciones desde PDF** (texto nativo y OCR local). El detalle de aceptación, historias y commits de integración está en `docs/roadmap.md`.

La importación asistida (SPEC-009) conserva el PDF original, exige consentimiento y revisión humana, permite editar, quitar o completar manualmente cualquier valor y no envía PDFs ni imágenes a servicios externos.

### Estado de las especificaciones

SPEC-001 a SPEC-006, SPEC-008 y SPEC-009 están completadas, aceptadas e integradas en `main`; SPEC-007 está cancelada. La versión 1.1.0 incorpora el cierre de SPEC-009. Fechas, historias y commits: `docs/roadmap.md`.
