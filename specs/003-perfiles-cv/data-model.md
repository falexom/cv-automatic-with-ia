# Fase 1 — Modelo de Datos: Perfiles de CV

**Feature**: `003-perfiles-cv` | **Fecha**: 2026-10-22
**Fuentes**: [spec.md](./spec.md) · [research.md](./research.md) · [plan.md](./plan.md)

## Visión general

Un **Perfil de CV** es una configuración derivada del Perfil Profesional Maestro. No contiene copias de datos profesionales ni de datos personales: solo guarda sus datos propios (nombre, título y resumen), su estado y las referencias a los elementos maestros que utiliza, junto con la relevancia de cada uno en ese perfil.

```text
Perfil Maestro (singleton, existente)
├── WorkExperience (N)
├── EducationEntry (N)
├── Certification (N)
├── Skill (N)
└── Project (N)
        ▲
        │ referencias por ID (sin copia de contenido)
        │
CvProfile (N)
├── cv_profile_work_experience ──> WorkExperience
├── cv_profile_education_entry ──> EducationEntry
├── cv_profile_certification ────> Certification
├── cv_profile_skill ────────────> Skill
└── cv_profile_project ──────────> Project
```

- El Perfil Maestro sigue siendo la única fuente de verdad (FR-001, FR-002).
- Un mismo elemento maestro puede estar referenciado por varios perfiles, con relevancia distinta en cada uno (FR-003, FR-010).
- La clase del elemento se identifica por la tabla pivote (FR-004).
- No se modela ninguna entidad de CV, PDF, plantilla, exportación, documento o evidencia.

## Convenciones

- **Base de datos**: SQLite local con claves foráneas activadas.
- **Identificadores**: enteros autoincrementales para `cv_profiles`; las pivotes usan clave primaria compuesta y no tienen `id` propio.
- **Relevancia**: valores `alta`, `media`, `baja`; peso de orden Alta = 3, Media = 2, Baja = 1; valor por defecto `media`.
- **Fechas**: se reutilizan las columnas del Maestro (`*_year`, `*_month`); no se agrega precisión ni se normaliza a una fecha ficticia.
- **Orden**: siempre derivado; no existe columna de posición ni orden manual.
- **Eliminación**: no hay eliminación definitiva de perfiles. Las pivotes se eliminan en cascada al eliminar un registro maestro o, a nivel de base, al eliminar un perfil.
- **Texto**: UTF-8; se conservan acentos y caracteres especiales. La comparación de nombres usa normalización de mayúsculas y espacios, no de acentos.

## Entidades

### CvProfile — Perfil de CV

| Columna                | Tipo        | Nulo | Reglas                                                              |
| ---------------------- | ----------- | ---- | ------------------------------------------------------------------- |
| id                     | integer PK  | no   |                                                                     |
| name                   | string(100) | no   | Obligatorio; no vacío ni solo espacios; forma escrita para mostrar. |
| normalized_name        | string(100) | no   | Calculado al guardar; minúsculas, recorte y colapso de espacios.    |
| professional_title     | string(255) | sí   | Propio del perfil; opcional; no se copia del Maestro.               |
| summary                | text        | sí   | Propio del perfil; opcional; no se copia del Maestro.               |
| is_active              | boolean     | no   | Default `true`; activo/inactivo reversible.                         |
| created_at, updated_at | timestamps  |      |                                                                     |

**Constraints e invariantes**

- Índice único sobre `normalized_name` (global; incluye perfiles inactivos).
- `name` es obligatorio y se conserva para mostrar con mayúsculas, acentos y espaciado interno; los espacios extremos se recortan como entrada de formulario.
- No existe FK `profile_id`: la aplicación es monousuario y tiene un único Perfil Maestro. Las pivotes referencian directamente a las entidades maestras, que ya pertenecen a ese Maestro.
- No hay `deleted_at`, `version`, `order` ni columnas de contenido profesional.
- No se permite eliminación definitiva desde la aplicación (FR-018).
- `is_active` es el contrato de disponibilidad para la futura generación de SPEC-004; no se implementa el generador.

**Relaciones**

- `belongsToMany` con `WorkExperience` a través de `cv_profile_work_experience`.
- `belongsToMany` con `EducationEntry` a través de `cv_profile_education_entry`.
- `belongsToMany` con `Certification` a través de `cv_profile_certification`.
- `belongsToMany` con `Skill` a través de `cv_profile_skill`.
- `belongsToMany` con `Project` a través de `cv_profile_project`.

Todas las relaciones incluyen `relevance` en la pivote y aplican el mismo orden: relevancia descendente y, en empate, orden del Maestro.

### Tablas pivote

| Tabla                        | FK de perfil                       | FK de elemento maestro                        | Columnas adicionales |
| ---------------------------- | ---------------------------------- | --------------------------------------------- | -------------------- |
| `cv_profile_work_experience` | `cv_profile_id` → `cv_profiles.id` | `work_experience_id` → `work_experiences.id`  | `relevance`          |
| `cv_profile_education_entry` | `cv_profile_id` → `cv_profiles.id` | `education_entry_id` → `education_entries.id` | `relevance`          |
| `cv_profile_certification`   | `cv_profile_id` → `cv_profiles.id` | `certification_id` → `certifications.id`      | `relevance`          |
| `cv_profile_skill`           | `cv_profile_id` → `cv_profiles.id` | `skill_id` → `skills.id`                      | `relevance`          |
| `cv_profile_project`         | `cv_profile_id` → `cv_profiles.id` | `project_id` → `projects.id`                  | `relevance`          |

**Reglas comunes**

- Clave primaria compuesta por `(cv_profile_id, <elemento>_id)`: impide duplicados de asociación.
- Ambas FKs con `ON DELETE CASCADE`.
- `relevance` string(10), no nulo, default `media`.
- Sin `id`, sin timestamps y sin columnas de contenido copiado.
- Eliminar un registro maestro elimina sus asociaciones de **todos** los perfiles; los perfiles y las demás asociaciones permanecen (FR-002, US3 AS6).
- Eliminar un perfil (no expuesto por la aplicación) eliminaría solo sus asociaciones; no afecta al Maestro ni a otros perfiles.
- Reincluir un elemento actualiza la fila existente (relevancia), no crea una segunda.
- En la creación y en la edición, cada clase se envía como conjunto completo: el servidor sincroniza las asociaciones de esa clase (agrega, actualiza relevancia y desasocia las ausentes) sin afectar a otras clases ni a otros perfiles.

**Procedencia (FR-004)**

La tabla pivote determina la clase del elemento. El detalle del perfil agrupa por clase y expone un enlace de procedencia (`master_path`) hacia la sección del Maestro; no se almacena un discriminador textual ni una copia del registro.

## Relevancia

| Valor   | Etiqueta visible | Peso de orden | Uso                                       |
| ------- | ---------------- | ------------- | ----------------------------------------- |
| `alta`  | Alta             | 3             | Prioridad máxima dentro de la sección.    |
| `media` | Media            | 2             | Valor por defecto al incluir un elemento. |
| `baja`  | Baja             | 1             | Prioridad mínima dentro de la sección.    |

- La relevancia es independiente por perfil: el mismo elemento puede ser Alta en un perfil y Baja en otro.
- Se valida contra la lista canónica; un valor fuera de la lista se rechaza con error de validación.
- Se muestra en el detalle junto a cada elemento.
- No existe relevancia por defecto a nivel de elemento maestro; el default es de la asociación.

## Reglas de orden

Todas las secciones se ordenan primero por relevancia descendente. En empate se aplica el orden del Perfil Maestro:

| Clase           | Criterio de desempate                                                                                 | Registros sin fecha |
| --------------- | ----------------------------------------------------------------------------------------------------- | ------------------- |
| Experiencia     | `start_year` descendente; mes de inicio efectivo (enero si falta) descendente; `id` descendente       | Al final            |
| Educación       | Igual que experiencia, usando `start_year`/`start_month`                                              | Al final            |
| Certificaciones | `date_year` descendente; mes efectivo (enero si falta) descendente; `id` descendente                  | Al final            |
| Proyectos       | `start_year` descendente; mes efectivo (enero si falta) descendente; `id` descendente                 | Al final            |
| Habilidades     | Categoría en orden técnica, funcional, metodológica, herramienta, tecnología; luego `name` ascendente | No aplica           |

- El orden es estable y reproducible (FR-012): ante mismos valores, el `id` descendente de las cuatro clases cronológicas evita ambigüedad. En habilidades, la unicidad normalizada por perfil hace determinista el nombre.
- SQLite ordena `NULL` al final en `DESC`, por lo que los registros sin año quedan al final de su grupo de relevancia.
- El servidor devuelve los arreglos ya ordenados; la UI no reordena.
- No hay columna de orden, drag-and-drop ni reordenamiento de secciones.
- Para habilidades se reutiliza el mismo orden del listado maestro (`/skills`): categoría y nombre. Una prueba compara ambos listados.

## Propiedad y herencia de datos

| Dato                                                             | Origen                           | ¿Editable en el perfil? | Observación                                   |
| ---------------------------------------------------------------- | -------------------------------- | ----------------------- | --------------------------------------------- |
| Nombre del perfil                                                | `cv_profiles.name`               | Sí                      | Obligatorio y único normalizado.              |
| Título profesional                                               | `cv_profiles.professional_title` | Sí                      | Propio; sustituye al del Maestro en la vista. |
| Resumen                                                          | `cv_profiles.summary`            | Sí                      | Propio; opcional.                             |
| Estado activo/inactivo                                           | `cv_profiles.is_active`          | Sí, por acciones        | Reversible; no elimina contenido.             |
| Datos personales y de contacto                                   | `profiles` (Maestro)             | No                      | Lectura en vivo, sin copia ni override.       |
| Enlaces adicionales                                              | `profile_links` (Maestro)        | No                      | Lectura en vivo.                              |
| Título profesional del Maestro                                   | `profiles.professional_title`    | No                      | No se muestra en el perfil ni se copia.       |
| Resumen del Maestro                                              | `profiles.summary`               | No                      | No se usa en el perfil.                       |
| Experiencia, educación, certificaciones, habilidades y proyectos | Tablas maestras                  | No desde el perfil      | Se referencian; se editan en sus secciones.   |
| Relevancia de una asociación                                     | Pivote correspondiente           | Sí                      | Independiente por perfil.                     |
| Pertenencia de una asociación                                    | Pivote correspondiente           | Sí (incluir/desasociar) | Solo afecta a ese perfil.                     |

**Regla del título (D3, FR-014)**

- El perfil usa siempre su `professional_title`.
- Si el título propio está vacío, no se muestra ningún título; no se usa el del Maestro como fallback.
- El título del Maestro no se incluye en el payload de datos heredados.
- Editar el título del perfil no modifica el Maestro.

## Ciclo de vida

```text
Creación ──> Activo ──> Inactivo
                 ▲          │
                 └──────────┘
                  Reactivar

Sin eliminación definitiva en la aplicación.
```

| Transición/evento                  | Efecto                                                                                          |
| ---------------------------------- | ----------------------------------------------------------------------------------------------- |
| Crear perfil                       | Nace activo, sin contenido; datos propios opcionales salvo el nombre.                           |
| Editar datos propios               | Solo cambia ese perfil; no toca el Maestro ni otros perfiles.                                   |
| Incluir/desasociar elementos       | Solo cambia las pivotes de ese perfil; el Maestro y otros perfiles no cambian.                  |
| Cambiar relevancia                 | Solo cambia la pivote de ese perfil para ese elemento.                                          |
| Desactivar                         | `is_active = false`; conserva datos, asociaciones y relevancias; sigue visible y editable.      |
| Reactivar                          | `is_active = true`; vuelve a estar disponible para la futura generación.                        |
| Eliminar un registro maestro       | La cascada elimina sus asociaciones de todos los perfiles; los perfiles permanecen.             |
| Actualizar un registro maestro     | Se refleja en todos los perfiles que lo referencian al volver a consultar; no hay copia.        |
| Eliminar un dato personal heredado | Deja de mostrarse en todos los perfiles; no hay copia que borrar.                               |
| Crear perfiles iniciales           | Solo si no existe ningún perfil; crea Analista y Programador activos, sin resumen ni contenido. |
| Renombrar/desactivar un preset     | No se recrea ni se sobrescribe automáticamente.                                                 |
| Eliminar un perfil                 | No existe acción ni ruta; la única retirada es desactivar.                                      |

**Perfiles iniciales**

| Nombre      | Título propio               | Estado inicial | Resumen | Contenido |
| ----------- | --------------------------- | -------------- | ------- | --------- |
| Analista    | Analista de Sistemas        | Activo         | Vacío   | Vacío     |
| Programador | Programador / Desarrollador | Activo         | Vacío   | Vacío     |

- La acción es explícita (`POST /profiles/initial`), no un seeder ni un efecto de migración.
- Si ya existe cualquier perfil, no se crea, recrea ni sobrescribe ninguno.
- Los presets se editan como cualquier perfil.

## Matriz de validaciones

| Regla                                                 | Dónde se valida                       | Requisito       |
| ----------------------------------------------------- | ------------------------------------- | --------------- |
| Nombre obligatorio y no solo espacios                 | FormRequest + regla `required`        | FR-013          |
| Nombre máximo 100                                     | FormRequest + columna                 | FR-013          |
| Nombre único normalizado, incluidos inactivos         | Controlador + índice único            | FR-013          |
| Renombrar al mismo nombre normalizado permitido       | Controlador excluye el propio ID      | FR-013          |
| Título máximo 255, opcional                           | FormRequest + columna                 | FR-014          |
| Resumen opcional                                      | FormRequest + columna                 | FR-014          |
| Cada ID existe en la tabla de su clase                | `Rule::exists`                        | FR-008          |
| Cada ID pertenece al Perfil Maestro actual            | `Rule::exists` con `profile_id`       | FR-008          |
| Sin IDs duplicados dentro de una clase                | `distinct` + PK compuesta             | FR-008, US2 AS9 |
| Relevancia en Alta/Media/Baja                         | `Rule::in`                            | FR-010          |
| Relevancia omitida se guarda como Media               | Default de columna + normalización    | FR-010          |
| Perfil sin contenido es válido                        | Arreglos de selección vacíos          | FR-007          |
| No crear ni editar registros maestros desde el perfil | Sin endpoints ni campos               | FR-003, FR-008  |
| No eliminar perfiles                                  | Sin ruta/método/botón                 | FR-018          |
| Crear presets solo si no hay ningún perfil            | Guarda de conteo en el controlador    | FR-017          |
| Activación/desactivación reversible                   | Acciones explícitas sobre `is_active` | FR-016          |

## Modelo de lectura del detalle

El detalle no es una tabla ni una vista materializada: se compone en cada consulta.

- **Datos propios**: se leen de `cv_profiles`.
- **Datos heredados**: se leen de `profiles` y `profile_links` en vivo; se excluyen título y resumen del Maestro.
- **Contenido**: se leen las cinco relaciones ordenadas; cada elemento se serializa con su `relevance` y su `master_path`.
- **Perfil sin contenido**: las cinco colecciones quedan vacías; la UI muestra orientación por sección.
- **Perfil Maestro vacío**: no hay datos heredados ni contenido; el perfil sigue siendo válido y consultable.
- **Aislamiento**: ninguna consulta del detalle escribe en el Maestro ni en otro perfil.

No se almacenan contadores, etiquetas, títulos de sección ni contenido derivado en `cv_profiles` o en las pivotes.

## Migración y reversibilidad

- Una migración aditiva crea `cv_profiles` y las cinco pivotes; no altera tablas existentes ni reescribe filas del Maestro.
- El orden de creación es `cv_profiles` y luego las pivotes.
- `down()` elimina primero las pivotes y después `cv_profiles`.
- No hay backfill ni datos iniciales; los presets se crean por acción del usuario.
- El rollback elimina únicamente datos de esta capacidad (perfiles, asociaciones y relevancias); no afecta a experiencias, educación, certificaciones, habilidades, proyectos, datos personales ni documentos.
- No se usa `migrate:fresh` como estrategia de actualización; destruiría datos existentes (Principio XXII).

## Trazabilidad FR → modelo

| Requisito | Elemento del modelo                                                          |
| --------- | ---------------------------------------------------------------------------- |
| FR-001    | `cv_profiles` con solo datos propios; pivotes con IDs y relevancia.          |
| FR-002    | Relaciones en vivo; FKs en cascada desde las pivotes.                        |
| FR-003    | Una pivote por perfil; sin escrituras al Maestro.                            |
| FR-004    | Tabla pivote por clase + `master_path` en el detalle.                        |
| FR-005    | `cv_profiles` + rutas/páginas de gestión.                                    |
| FR-006    | Detalle compuesto por datos propios, heredados y cinco relaciones.           |
| FR-007    | Selecciones vacías válidas.                                                  |
| FR-008    | PK compuesta, `exists` restringido, `distinct`, `sync`.                      |
| FR-009    | Sin asociaciones por defecto; solo pivotes existentes.                       |
| FR-010    | Columna `relevance` con default `media`.                                     |
| FR-011    | Orden por relevancia descendente.                                            |
| FR-012    | Reglas de desempate por clase; sin columna de orden.                         |
| FR-013    | `name` + `normalized_name` único.                                            |
| FR-014    | `professional_title` y `summary` propios.                                    |
| FR-015    | Datos heredados leídos de `profiles`/`profile_links`; sin copia ni override. |
| FR-016    | `is_active` + acciones reversibles.                                          |
| FR-017    | Acción de presets; sin datos iniciales automáticos.                          |
| FR-018    | Sin entidad/acción de eliminación de perfiles.                               |
| FR-019    | SQLite local; texto UTF-8.                                                   |
| FR-020    | Validación antes de persistir; transacción; sin pérdida silenciosa.          |
