# Implementation Plan: Perfiles de CV

**Branch**: `003-perfiles-cv` | **Date**: 2026-10-22 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/003-perfiles-cv/spec.md`

**Governance**: especificación clarificada y aprobada el 2026-10-22 (decisiones D1–D3). El usuario autorizó el flujo Clarify → Plan → Tasks → Implement → Validate y la ejecución autónoma de decisiones técnicas ordinarias, sin merge ni push. Base declarada por el encargo: rama `003-perfiles-cv`, HEAD=main=1b20460; la verificación manual de componentes fue informada como satisfactoria. Este Plan no reabre SPEC-001 ni SPEC-002, no modifica `spec.md` y no implementa código.

## Summary

Implementar **perfiles de CV** como configuraciones derivadas del Perfil Profesional Maestro. Cada perfil almacena únicamente sus datos propios (nombre, título profesional y resumen), su estado activo/inactivo y asociaciones explícitas con experiencias, educación, cursos y certificaciones, habilidades y proyectos del Maestro. La selección nunca es automática; cada elemento incluido recibe una relevancia Alta, Media o Baja (Media por defecto) y el orden se calcula en el servidor por relevancia descendente y, en los empates, por el orden del Perfil Maestro.

**Enfoque técnico**: monolito Laravel 13 + Inertia 3 + React 19/TypeScript, SQLite local y los componentes shadcn/ui ya presentes. Se agrega la tabla `cv_profiles` y cinco tablas pivote explícitas (una por clase de contenido) con clave primaria compuesta, `relevance` y claves foráneas en cascada. No se copian datos maestros: el detalle del perfil lee los registros vigentes del Maestro y los datos personales heredados en modo lectura, excluyendo el título del Maestro porque el título es propio del perfil. Se implementan listado, creación, edición, detalle, activación/desactivación y una acción explícita para los perfiles iniciales Analista y Programador. **No existe eliminación definitiva de perfiles ni ruta `DELETE`.** Pruebas con Pest 4 (Feature) y Vitest + React Testing Library; sin dependencias nuevas.

### Decisiones clave

- **Agregado**: `App\Models\CvProfile` sobre `cv_profiles`; rutas `/profiles` con nombres `profiles.*`, separadas de `/profile` (Perfil Maestro).
- **Asociaciones**: cinco pivotes tipados (`cv_profile_work_experience`, `cv_profile_education_entry`, `cv_profile_certification`, `cv_profile_skill`, `cv_profile_project`) con `relevance` y FKs en cascada; se descarta la tabla polimórfica porque no puede garantizar integridad referencial.
- **Relevancia**: valores `alta`, `media`, `baja`; `media` por defecto en base de datos, validación y UI.
- **Orden**: una sola regla por clase, calculada en SQL. Se reutiliza `chronological()` para experiencia, educación, certificaciones y proyectos; se agrega un scope aditivo de orden de Maestro para habilidades (categoría y nombre). No hay columna ni UI de orden manual.
- **Guardado**: las selecciones se envían completas por clase y se sincronizan con `sync`, de modo que reincluir actualiza la asociación existente y desmarcar la elimina solo en ese perfil. Todo el guardado ocurre en una transacción.
- **Herencia**: nombre, título y resumen son propios; datos personales y enlaces se leen del Maestro sin copia ni override; el título del Maestro no se muestra en el perfil.
- **Ciclo de vida**: todo perfil nace activo; activar/desactivar son acciones explícitas y reversibles; no hay `destroy`. Los perfiles iniciales se crean solo cuando no existe ningún perfil y nunca se recrean ni sobrescriben.
- **UI**: `Profiles/Index`, `Profiles/Create`, `Profiles/Edit`, `Profiles/Show` y un formulario compartido; navegación «Perfiles». Se usa un ajuste de límite de segmento en `use-current-url` para que «Perfil» no quede activo en `/profiles`.

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json` requiere `^8.3`) con Laravel 13.17; TypeScript 5.7 y React 19.2.

**Primary Dependencies**: Laravel 13, Inertia 3 (`inertiajs/inertia-laravel` + `@inertiajs/react`), React 19, Tailwind CSS 4, shadcn/ui sobre Radix UI, Vite 8 y Wayfinder. Desarrollo: Pest 4.7, Vitest (vía `vp test`) y React Testing Library. **No se agregan dependencias.**

**Storage**: SQLite local en `database/database.sqlite`, con claves foráneas activadas; sin base remota, almacenamiento externo ni servicios de red.

**Testing**: Pest 4 (Feature/Unit con SQLite en memoria, `RefreshDatabase`) mediante `php artisan test`; Vitest + React Testing Library mediante `npm test`; verificación de tipos con `npm run types:check`.

**Target Platform**: aplicación web local (localhost) para navegador de escritorio, uso sin conexión, un solo usuario y un único Perfil Maestro.

**Project Type**: aplicación web única (monolito Laravel + SPA Inertia/React en el mismo repositorio); no se crea una API JSON separada.

**Performance Goals**: sin objetivos de rendimiento medibles. La aplicación es local y monousuario; la respuesta debe ser la habitual del monolito Laravel/Inertia y no se introducen benchmarks ni pruebas de carga.

**Constraints**: monousuario y sin autenticación; el Perfil Maestro es la única fuente de verdad; interfaz en español con acentos y caracteres especiales; sin generación de CV/PDF, plantillas, exportación, IA, ofertas, postulaciones ni asociación documental; sin eliminación definitiva de perfiles; sin orden manual; migración aditiva y reversible; sin dependencias nuevas.

**Scale/Scope**: 1 usuario; 1 Perfil Maestro; N perfiles de CV (inicialmente 2 y luego los que el usuario cree); 6 tablas nuevas (1 de perfiles + 5 pivotes); 4 pantallas nuevas + 1 formulario compartido; 20 requisitos funcionales.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio                                         | Estado | Evidencia del diseño                                                                                                                        |
| ------------------------------------------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------- |
| I. Fuente única de verdad                         | PASA   | `cv_profiles` solo guarda datos propios; los pivotes guardan IDs y relevancia; el detalle lee el Maestro en vivo.                           |
| II. El CV es un resultado                         | PASA   | No se genera, previsualiza, edita ni exporta ningún CV; solo se configura la selección y la relevancia.                                     |
| III. Separación datos/reglas/presentación         | PASA   | Datos en el Maestro; reglas de selección/relevancia en los pivotes; presentación en páginas Inertia; sin mezclar responsabilidades.         |
| IV. MVP estrictamente acotado                     | PASA   | Alcance idéntico al spec; sin generación, documentos, IA ni funcionalidades accesorias.                                                     |
| V. Perfil Profesional Maestro                     | PASA   | No se modifican sus entidades, campos, validaciones ni datos; solo se leen sus registros y datos personales.                                |
| VI. Perfiles profesionales como configuración     | PASA   | Los perfiles Analista y Programador se crean por acción explícita; un mismo elemento se reutiliza con relevancia independiente.             |
| VII–XI. Documentos, evidencias y CV documentado   | N/A    | Fuera de alcance; no se asocian documentos ni evidencias a perfiles.                                                                        |
| XII. Arquitectura simple para aplicación local    | PASA   | Laravel + Inertia/React + SQLite + almacenamiento local; sin servicios externos.                                                            |
| XIII. Evitar complejidad prematura                | PASA   | Cinco pivotes tipados en lugar de polimorfismo/JSON; sin colas, contenedores ni API separada.                                               |
| XIV. Datos relacionales, no contenido duplicado   | PASA   | Claves foráneas, claves primarias compuestas y cascadas; no se copian textos ni registros maestros.                                         |
| XV. Administrador orientado a tareas              | PASA   | Navegación «Perfiles» y pantallas de listado, creación, edición y detalle con estados vacíos y acciones directas.                           |
| XVI–XVIII. Vista previa, plantilla y calidad      | N/A    | Capacidades futuras; el detalle del perfil es configuración, no una vista previa de CV.                                                     |
| XIX. Cada Spec entrega una capacidad verificable  | PASA   | FR-001 a FR-020 y SC-001 a SC-006 con pruebas automatizadas y validación manual en `quickstart.md`.                                         |
| XX. Las tareas no sustituyen a los Specs          | PASA   | El trabajo se descompone en Tasks dentro de este Spec; no se abren tareas funcionales independientes.                                       |
| XXI. Pruebas proporcionales al riesgo             | PASA   | Feature tests para persistencia, validación, orden, aislamiento, cascadas y ciclo de vida; Vitest para el formulario y la navegación.       |
| XXII. No romper datos existentes                  | PASA   | Migración aditiva: solo crea tablas nuevas; no altera ni reescribe tablas del Maestro. `down()` elimina únicamente datos de esta capacidad. |
| XXIII. Privacidad local por defecto               | PASA   | Todos los datos permanecen en SQLite local; no hay llamadas externas.                                                                       |
| XXIV. No almacenar secretos en código fuente      | PASA   | No se agregan credenciales, claves ni configuración sensible.                                                                               |
| XXV. No expandir el MVP durante la implementación | PASA   | Sin orden manual, historial, versionado, exportación ni documentos; cualquier mejora futura queda fuera del alcance.                        |
| XXVI. Cambios importantes requieren decisión      | PASA   | No se cambia el stack, SQLite, autenticación, modelo del Maestro, edición del CV ni plantillas.                                             |

**Resultado**: sin violaciones. No se requiere justificación en Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/003-perfiles-cv/
├── plan.md              # Este archivo
├── research.md          # Fase 0: decisiones técnicas
├── data-model.md        # Fase 1: entidades, tablas, reglas y ciclo de vida
├── contracts/
│   └── http-contracts.md # Fase 1: rutas, payloads, respuestas y errores
├── quickstart.md        # Fase 1: migración, pruebas y validación manual
├── spec.md              # Entrada aprobada; no se modifica
└── tasks.md             # Fase 2: lo crea Project Manager; no lo crea Plan
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── CvProfileController.php
│   └── Requests/
│       ├── StoreCvProfileRequest.php
│       └── UpdateCvProfileRequest.php
├── Models/
│   ├── CvProfile.php
│   └── Skill.php                 # + scopeMasterOrder (aditivo, sin cambio de comportamiento)
└── Support/
    └── NameNormalizer.php        # reutilizado; sin cambios

database/
├── factories/
│   └── CvProfileFactory.php
└── migrations/
    └── 2026_10_22_000001_create_cv_profiles_and_content_tables.php

resources/js/
├── components/
│   ├── forms/
│   │   └── CvProfileForm.tsx
│   └── __tests__/
│       └── CvProfileForm.test.tsx
├── hooks/
│   ├── use-current-url.ts        # límite de segmento para la navegación
│   └── __tests__/
│       └── use-current-url.test.ts
├── layouts/
│   └── AppLayout.tsx             # ítem «Perfiles»
├── lib/
│   └── cv-profiles.ts            # etiquetas y tipos compartidos
└── pages/
    └── Profiles/
        ├── Index.tsx
        ├── Create.tsx
        ├── Edit.tsx
        └── Show.tsx

routes/
└── web.php                       # rutas profiles.* (sin DELETE)

tests/Feature/
├── CvProfileTest.php             # CRUD, nombre, estado, herencia básica
├── CvProfileContentTest.php      # selección, relevancia, orden, aislamiento, cascadas
└── CvProfileInitialProfilesTest.php # presets e idempotencia
```

**Structure Decision**: se mantiene la aplicación única Laravel + Inertia/React de la raíz del repositorio. No se crea una API JSON ni un proyecto frontend separado: Inertia permite controladores Laravel clásicos, validación en servidor y páginas React tipadas sin infraestructura adicional (Principios XII–XIII). Las cinco tablas pivote explícitas son la opción relacional más simple que garantiza integridad referencial y cascadas (Principio XIV). El ajuste de `use-current-url` es una corrección de integración: la comparación actual por prefijo haría que «Perfil» quedara activo en `/profiles`; se limita a coincidencia exacta o subruta con separador, sin cambiar el comportamiento de las secciones existentes.

## Phase 0/1 Outputs

- **[research.md](./research.md)**: decisiones sobre pivotes, relevancia, orden, validación de pertenencia, normalización, herencia, ciclo de vida, UI, pruebas y riesgos. Sin `NEEDS CLARIFICATION`.
- **[data-model.md](./data-model.md)**: `cv_profiles`, las cinco tablas pivote, constraints, relaciones, estados, reglas de orden, matriz de propiedad/herencia y ciclo de vida.
- **[contracts/http-contracts.md](./contracts/http-contracts.md)**: rutas, métodos, props Inertia, payloads, validaciones, errores, garantía de orden y ausencia explícita de `DELETE`.
- **[quickstart.md](./quickstart.md)**: migración, comandos de prueba y flujo manual de validación de extremo a extremo.
- **tasks.md**: no se crea en esta fase; lo generará Project Manager a partir de este Plan y del spec aprobado.

## Requirement Coverage

| Requisito | Diseño                                                                                                   | Prueba prevista                                                                           |
| --------- | -------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| FR-001    | `cv_profiles` solo con datos propios; pivotes con IDs y relevancia; sin copias.                          | Esquema de `cv_profiles`/pivotes y actualización del Maestro reflejada en el perfil.      |
| FR-002    | Relaciones en vivo al Maestro; cascada al eliminar un registro maestro; datos personales leídos en vivo. | Actualizar y eliminar registros maestros; el perfil conserva el resto.                    |
| FR-003    | Sin escrituras al Maestro desde el perfil; pivotes independientes por perfil.                            | Reutilización en dos perfiles, cambio y desasociación aislados.                           |
| FR-004    | Una tabla pivote por clase; el detalle identifica la sección y `master_path`.                            | Cinco clases serializadas con relevancia y procedencia.                                   |
| FR-005    | Índice, creación, edición, detalle, activación/desactivación, navegación y estado vacío.                 | Feature tests de índice/creación/estado vacío; validación manual de navegación.           |
| FR-006    | `Profiles/Show` con datos propios, `personal_data` heredado y `content` agrupado.                        | Props Inertia de detalle con las cinco secciones.                                         |
| FR-007    | Arreglos vacíos válidos; cada sección muestra orientación.                                               | Perfil sin contenido se guarda y se consulta.                                             |
| FR-008    | `exists` restringido al Maestro actual, `distinct`, PK compuesta y `sync`; sin creación de maestros.     | IDs inválidos/ajenos/duplicados rechazados; desasociación solo en el perfil.              |
| FR-009    | Perfil nuevo sin pivotes; solo se serializan los elementos incluidos.                                    | Perfil recién creado sin contenido; no incluidos ausentes.                                |
| FR-010    | Relevancia `alta` / `media` / `baja`, default `media`, validación y visualización.                       | Default, valores inválidos, relevancia independiente y mostrada.                          |
| FR-011    | Orden por relevancia descendente y desempate por orden del Maestro.                                      | Orden por clase con relevancias mixtas.                                                   |
| FR-012    | `chronological()` en cuatro clases; `masterOrder()` en habilidades; sin orden manual.                    | Fechas, sin fecha al final, categoría y nombre; comparación con el listado maestro.       |
| FR-013    | `NameNormalizer` + `normalized_name` único; revalidación al renombrar; incluye inactivos.                | Nombre vacío, duplicado por mayúsculas/espacios, renombrado y perfil inactivo.            |
| FR-014    | Título y resumen propios, opcionales; el título propio se usa en índice, edición y detalle.              | Título propio sin modificar el Maestro; perfil sin título válido.                         |
| FR-015    | `personal_data` leído del Maestro, sin campos editables; sin título ni resumen del Maestro.              | Datos heredados actualizados; título del Maestro ausente; sin overrides.                  |
| FR-016    | `is_active` con default verdadero; acciones reversibles; inactivos visibles y editables.                 | Crear activo, desactivar conservando contenido, reactivar.                                |
| FR-017    | Acción `POST /profiles/initial` solo si no hay perfiles; transacción; valores exactos; sin recreación.   | Presets exactos, sin contenido, segunda ejecución sin duplicados, renombrado/desactivado. |
| FR-018    | Sin ruta, método, botón ni nombre `profiles.destroy`; solo desactivación.                                | `Route::has('profiles.destroy')` falso y `DELETE` responde 405.                           |
| FR-019    | SQLite local; UTF-8; sin servicios externos.                                                             | Persistencia entre peticiones y validación manual tras reiniciar; acentos.                |
| FR-020    | Validación antes de persistir; transacción; `useForm` conserva lo ingresado ante 422.                    | Errores de validación y reintento sin pérdida silenciosa.                                 |

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

Sin violaciones. No se requiere justificación de complejidad. Las cinco tablas pivote no son deuda de complejidad: son la forma más simple de garantizar integridad referencial, cascadas y ausencia de asociaciones huérfanas con SQLite y Eloquent.

## Post-Design Constitution Re-Check (Phase 1)

Re-evaluado tras generar `research.md`, `data-model.md`, `contracts/http-contracts.md` y `quickstart.md`:

- **I / XIV (fuente única, relacional)**: `cv_profiles` no contiene copias; los pivotes solo referencian IDs maestros y relevancia; las cascadas eliminan asociaciones, nunca perfiles ni registros maestros.
- **II / III (CV como resultado, separación)**: no se genera ni edita un CV; los datos permanecen en el Maestro, las reglas en los pivotes y la presentación en Inertia.
- **XII / XIII (simplicidad)**: monolito Inertia, SQLite, cinco pivotes explícitos y cero dependencias nuevas; no se introduce polimorfismo, JSON ni una API separada.
- **XXI (pruebas)**: los riesgos críticos del Principio XXI (relaciones entre perfiles y contenido, persistencia y conservación de datos) tienen cobertura Feature; el formulario y la navegación tienen prueba Vitest.
- **XXII (no romper datos)**: una migración aditiva crea solo tablas nuevas; `down()` elimina solo datos de esta capacidad; no se modifica ninguna tabla ni fila existente.
- **XXV / XXVI (alcance y decisiones)**: no se agregan capacidades fuera del spec ni se cambian stack, autenticación, modelo del Maestro o mecanismo de CV.

Sin violaciones nuevas introducidas por el diseño.
