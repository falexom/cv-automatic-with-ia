# CV Automático

Aplicación web local monousuario para gestionar el **Perfil Profesional Maestro**: una sola trayectoria profesional registrada una única vez (datos personales, resumen, experiencia laboral, educación, cursos y certificaciones, habilidades y proyectos).

- **Stack**: Laravel 13 + PHP 8.3–8.5, Inertia + React 19 + TypeScript, Tailwind CSS 4, shadcn/ui, SQLite local.
- **Sin autenticación** y **sin servicios externos**: toda la información permanece en tu equipo.
- Especificaciones: `specs/001-perfil-profesional-maestro/` (spec, plan, modelo de datos, contratos, quickstart, tasks).
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
├── Models/                # Profile, ProfileLink, WorkExperience, EducationEntry, Certification, Skill, Project
└── Support/               # YearMonth (reglas de fechas), NameNormalizer (duplicados)

resources/js/
├── components/            # UI compartida (YearMonthInput, SkillMultiSelect, DeleteConfirmDialog...)
├── layouts/AppLayout.tsx  # Navegación principal
└── pages/                 # Una sección por carpeta (Profile, Experiences, Education, Certifications, Skills, Projects)
```

## Alcance actual

Implementada la capacidad **Perfil Profesional Maestro** (`specs/001-perfil-profesional-maestro/`).
Fuera de alcance por ahora: repositorio documental (PDFs), extracción automática, perfiles objetivo (Analista/Programador), generación de CV y CV documentado.
