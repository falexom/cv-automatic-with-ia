# CV Automatic with IA

**Versión:** 1.2.0 (2026-11-21) · **Estado:** MVP funcional completo; SPEC-001 a SPEC-006, SPEC-008 y SPEC-009 completadas y aceptadas; US-012 aceptada. SPEC-007 permanece cancelada.

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
- Chrome completo o Chromium en el equipo donde se ejecuta Laravel, solo para exportar el CV a PDF. No hace falta en los equipos que únicamente acceden a la aplicación desde su navegador.

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

## Chrome para exportar el CV a PDF

Chrome/Chromium es una dependencia **del equipo que ejecuta Laravel**, sea la PC local o el servidor. Se usa únicamente para convertir a PDF la vista previa del CV normal. Las computadoras que solo abren la aplicación desde su navegador **no** necesitan instalar Chrome para descargar el PDF: la exportación ocurre en el equipo de Laravel.

La aplicación no descarga navegadores durante el arranque, la validación ni la exportación. `pnpm install` tampoco ejecuta el instalador de Puppeteer: `pnpm-workspace.yaml` bloquea su build (`allowBuilds: puppeteer: false`) y `package.json` declara `puppeteer.skipDownload: true`. La única descarga admitida es el comando manual de la sección siguiente.

### Verificar el navegador

```bash
php artisan cv:pdf:chrome
# o
composer check:pdf
```

El comando muestra la ruta y el origen del Chrome que Laravel usará, inicia el navegador y genera un PDF de prueba local sin datos del CV. Si no encuentra un navegador o no puede iniciarlo, termina con error y muestra las instrucciones para corregirlo. `composer setup` ejecuta la misma comprobación en modo de aviso, sin bloquear la instalación.

La resolución reutiliza, en este orden:

1. `BROWSERSHOT_CHROME_PATH`, si está definida y apunta a un ejecutable válido.
2. El Chrome compatible que resuelve Puppeteer en su propia caché.
3. Un Google Chrome o Chromium completo instalado en el sistema.
4. Un Chrome for Testing que ya exista en la caché de Playwright.

### Reutilizar un Chrome existente

Si ya hay un Chrome completo instalado, apunta la aplicación a su ejecutable absoluto en `.env`:

```dotenv
BROWSERSHOT_CHROME_PATH="/ruta/absoluta/al/Chrome"
```

Después valida de nuevo con `php artisan cv:pdf:chrome`. No uses `chrome-headless-shell`: la exportación usa `newHeadless()` y necesita Chrome completo.

### Instalar Chrome una sola vez

Si no hay un navegador reutilizable, ejecuta explícitamente el instalador de Puppeteer desde la raíz del proyecto:

```bash
pnpm exec puppeteer browsers install chrome
php artisan cv:pdf:chrome
```

Ese comando descarga el Chrome compatible con la versión de Puppeteer fijada por el proyecto. Es la única vía de descarga automatizada y requiere una decisión manual; no se ejecuta desde `composer setup`, la aplicación ni las pruebas. `puppeteer.skipDownload` solo bloquea las instalaciones implícitas: el comando explícito `install chrome` sigue disponible.

### Cachés de Puppeteer y Playwright

- **Puppeteer** guarda su navegador en `~/.cache/puppeteer` (macOS/Linux) o `%USERPROFILE%\.cache\puppeteer` (Windows). Es la caché que controla CV Automático: se consulta y, si hace falta, se instala solo con el comando manual anterior.
- **Playwright** usa `~/Library/Caches/ms-playwright` en macOS y `~/.cache/ms-playwright` en Linux. Esa caché pertenece a Playwright: CV Automático solo reutiliza un Chrome completo que ya esté allí; no descarga, no actualiza y no borra sus navegadores.

No borres ninguna de las dos cachés para “limpiar” la instalación. Si una ruta existente está rota, corrige `BROWSERSHOT_CHROME_PATH` o instala el Chrome de Puppeteer con el comando explícito.

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

SPEC-001 a SPEC-006, SPEC-008 y SPEC-009 están completadas, aceptadas e integradas en `main`; SPEC-007 está cancelada. La versión 1.2.0 incorpora la búsqueda local de US-012 y el cierre de SPEC-009. Fechas, historias y commits: `docs/roadmap.md`.
