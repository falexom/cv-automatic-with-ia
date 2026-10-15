# Fase 0 — Investigación y Decisiones Técnicas

**Feature**: Perfil Profesional Maestro (`001-perfil-profesional-maestro`)
**Fecha**: 2026-10-15

Todas las incógnitas del contexto técnico quedaron resueltas. No quedan `NEEDS CLARIFICATION`.

---

## 1. Versión de Laravel y PHP

- **Decisión**: Laravel 13.x con PHP 8.4 (rango soportado 8.3–8.5).
- **Rationale**: Laravel 13 es la línea con soporte activo (released 2026-03-17, soporte de seguridad hasta 2028-03). PHP 8.4 es la versión recomendada para Laravel 13.3+ (componentes Symfony 8). Laravel 12 entra en EOL en 2027-02 y no se elige para un proyecto nuevo.
- **Alternativas consideradas**: Laravel 12 (grafo de soporte más corto; sin ventajas); Livewire + Blade (rechazado: la Constitución exige React como frontend).

## 2. Integración Laravel + React

- **Decisión**: Inertia (adaptador oficial Laravel + `@inertiajs/react`), partiendo del starter kit oficial `laravel/react-starter-kit` y eliminando su scaffolding de autenticación (el MVP es monousuario sin autenticación).
- **Rationale**: Inertia es el enfoque oficialmente recomendado para usar React con controladores y enrutado de Laravel: evita construir y mantener una API JSON separada (Principio XIII), mantiene la lógica de validación/redirección en el servidor y permite componentes React tipados con TypeScript. El starter kit aporta Vite, Tailwind, shadcn/ui y pruebas ya configurados.
- **Alternativas consideradas**:
    - SPA React separada + API Laravel (rechazada: infraestructura innecesaria para una app local de un solo usuario).
    - Blade + islas React (rechazada: más glue code y peor DX para formularios complejos).
    - Livewire (rechazada: la Constitución fija React).

## 3. UI: TypeScript, Tailwind 4 y shadcn/ui

- **Decisión**: TypeScript en todo el frontend; Tailwind CSS 4; componentes shadcn/ui (Radix) para diálogos de confirmación, formularios, tablas y estados; etiquetas e interfaz en español.
- **Rationale**: Es la base del starter kit oficial; shadcn/ui provee el diálogo de confirmación accesible (FR-026) y los controles de formulario sin dependencias propietarias.
- **Alternativas consideradas**: Librerías de componentes cerradas (MUI, Ant) rechazadas por peso y opinión de estilo; CSS propio rechazado por costo de mantenimiento.

## 4. Base de datos y claves foráneas

- **Decisión**: SQLite en `database/database.sqlite`; claves foráneas activadas (constraint enforcement) y borrado en cascada en los pivotes; pruebas con SQLite en memoria.
- **Rationale**: Fijado por la Constitución (Principio XII). Un solo usuario ⇒ sin concurrencia real; WAL no es necesario. SQLite en memoria en tests es el estándar de Laravel y da aislamiento sin infraestructura.
- **Alternativas consideradas**: MySQL/PostgreSQL (rechazadas por la Constitución); modo WAL (sin beneficio con un usuario).

## 5. Modelado de fechas con precisión mes-año o solo año (FR-034)

- **Decisión**: almacenar cada fecha como dos columnas: `*_year` (entero, opcional) y `*_month` (entero 1–12, opcional). Validación: `month` requiere `year`. Comparación y orden: un año sin mes se interpreta como enero para fechas de inicio y diciembre para fechas de fin; un registro sin año de inicio se ordena al final.
- **Rationale**: evita "días falsos" (nunca se inventa el día), hace trivial la validación `fin ≥ inicio`, y el orden cronológico es determinista con una regla explícita. Consultable e indexable en SQLite sin transformaciones.
- **Alternativas consideradas**: columna `date` con bandera de precisión (requiere normalizar a un día ficticio y complica comparaciones); texto libre (imposible validar/ordenar); fecha completa mes-año-día (rechazada en clarificación: D, se acordó precisión mixta).

## 6. Normalización y unicidad de habilidades (FR-020)

- **Decisión**: columna `normalized_name` calculada al guardar: minúsculas, recorte de espacios extremos y colapso de espacios internos (Unicode-aware vía `mb_*`); índice único compuesto `(profile_id, normalized_name)`. El nombre visible conserva el formato escrito por el usuario.
- **Rationale**: cumple exactamente la regla del spec (ignorar mayúsculas/minúsculas y espacios sobrantes) sin heurísticas adicionales; el índice único garantiza la regla a nivel de datos, no solo de UI.
- **Alternativas consideradas**: unicidad insensible a acentos (rechazada: excede el spec y produce falsos duplicados); validación solo en aplicación (insuficiente: una regla de datos requiere garantía de datos).

## 7. Patrón del perfil singleton (FR-001)

- **Decisión**: tabla `profiles`, exactamente una fila, creada con `firstOrCreate` en el primer acceso; el resto de entidades referencian `profile_id` con FK. La pantalla de perfil gestiona datos personales, enlaces adicionales (reemplazo en bloque al guardar) y resumen.
- **Rationale**: mantiene relaciones explícitas (Principio XIV) y compatibilidad futura si se agregaran perfiles objetivo, sin introducir multi-tenant (fuera de alcance). Los enlaces son parte del formulario de perfil: reemplazo total en cada guardado evita endpoints adicionales.
- **Alternativas consideradas**: tabla clave-valor (rechazada: pierde tipado y validaciones); sin tabla de perfil y datos "sueltos" (rechazada: sin raíz relacional).

## 8. Advertencia de certificación duplicada (FR-018)

- **Decisión**: al guardar, si existe otro curso/certificación con el mismo nombre e institución normalizados (sin distinguir mayúsculas/espacios), el servidor no persiste y regresa al formulario con una advertencia que incluye el registro existente; el usuario puede reenviar con la marca explícita `confirm_duplicate=true` para guardar de todos modos.
- **Rationale**: cumple "advertir y solo continuar con confirmación explícita" sin bloquear casos legítimos (p. ej., dos ediciones del mismo curso). El estado de la advertencia vive en la sesión (flash), coherente con Inertia.
- **Alternativas consideradas**: bloqueo duro (rechazado: impide registros legítimos); advertencia solo en UI (rechazada: evitable con envíos directos; la garantía debe estar en el servidor).

## 9. Confirmación antes de eliminar (FR-026, SC-007)

- **Decisión**: diálogo de confirmación (shadcn/ui `AlertDialog`) en el cliente antes de enviar `DELETE`; eliminación definitiva en servidor (sin papelera, según Asunciones del spec).
- **Rationale**: la confirmación es un requisito de UX; el servidor no necesita un flag adicional para una app local de un usuario. Los borrados en cascada de pivotes mantienen la integridad (las habilidades y entidades sobreviven, solo desaparecen los vínculos).
- **Alternativas consideradas**: soft delete + papelera (fuera de alcance del MVP); doble envío con confirmación de servidor (complejidad sin valor aquí).

## 10. Estrategia de pruebas

- **Decisión**: Pest 4 para backend (Feature: persistencia, validaciones, relaciones, flujos de duplicados; Unit: `YearMonth`, `SkillName`); Vitest + React Testing Library para componentes críticos del frontend (diálogo de eliminación, advertencia de duplicado, estados vacíos). E2E automatizado fuera de alcance; validación manual guiada en `quickstart.md`.
- **Rationale**: cubre proporcionalmente los puntos críticos que exige la Constitución (XXI) sin introducir Playwright/BrowserStack en un MVP local. Los Feature tests de Laravel verifican rutas + validación + base de datos en una sola pasada.
- **Alternativas consideradas**: solo PHPUnit (Pest es el default moderno del starter kit y más legible); E2E con Playwright (rechazado por costo/beneficio en app local de un usuario).

## 11. Sin servicios externos y funcionamiento offline

- **Decisión**: ninguna llamada de red externa en runtime (FR-031); fuentes/iconos empaquetados por Vite; sin CDNs. `.env` fuera del control de versiones.
- **Rationale**: Principios XXIII–XXIV de la Constitución; la app debe funcionar sin conexión.
- **Alternativas consideradas**: CDNs de fuentes (rechazado: dependencia de red); servicios de IA/OCR (fuera de alcance de esta capacidad).

## 12. Regla de orden cronológico y estados vigente/en curso

- **Decisión**: orden descendente por `(start_year, COALESCE(start_month, 1))`; registros sin año de inicio al final. Reglas de estado: si una experiencia tiene fecha de fin ⇒ `is_current=false` (persistido); si se quita la fecha de fin ⇒ `is_current=true` (la experiencia "vuelve a considerarse actual", según caso límite del spec). Misma mecánica para `is_in_progress` en educación.
- **Rationale**: es la traducción directa de FR-010, FR-034 y del caso límite "experiencia marcada como actual y luego con fecha de fin"; determinista y testeable.
- **Alternativas consideradas**: orden manual por el usuario (fuera de alcance); estados derivados solo en presentación (rompería el caso límite al recargar).
