# Fase 1 — Modelo de Datos: Generador de CV

**Feature**: `004-generador-de-cv` | **Fecha**: 2026-10-23
**Fuentes**: [spec.md](./spec.md) · [research.md](./research.md) · [plan.md](./plan.md) · [SPEC-003 data-model](../003-perfiles-cv/data-model.md)

## Visión general

El **Generador de CV** no introduce ninguna entidad persistente. La vista de CV es un **valor derivado y efímero** que se calcula en cada petición a partir de:

1. el Perfil Profesional Maestro (datos personales, contacto y registros maestros);
2. un perfil de CV activo seleccionado explícitamente (título y resumen propios, selección y relevancia);
3. las reglas de orden ya definidas en SPEC-003;
4. una única plantilla de presentación de una columna.

```text
Perfil Maestro (singleton, existente)
├── ProfileLink (N)
├── WorkExperience (N) ──┐
├── EducationEntry (N) ──┤
├── Certification (N) ───┤   incluidas y ordenadas por SPEC-003
├── Skill (N) ───────────┤
└── Project (N) ─────────┘
        ▲
        │ lectura en vivo, sin copia
        │
CvProfile (existente, SPEC-003)
├── name, is_active                 → disponibilidad y etiqueta del selector
├── professional_title, summary     → datos propios del CV
└── pivotes con relevance           → selección y orden

GET /cv?profile=ID
        │
        ▼
CvPreviewContent::build(CvProfile)
        │
        ▼
Vista de CV (payload Inertia en memoria, no persistido)
```

No se modela ninguna entidad de CV, PDF, plantilla, exportación, documento, evidencia, historial o versión. No hay tablas, migraciones, seeders ni factories nuevas.

## Fuentes de datos

| Dato                                             | Fuente                                                     | Tipo de acceso | Observación                                              |
| ------------------------------------------------ | ---------------------------------------------------------- | -------------- | -------------------------------------------------------- |
| Nombre completo, teléfono, correo, ubicación     | `profiles` (Maestro)                                       | Lectura viva   | Heredado; sin copia ni sobrescritura por perfil.         |
| LinkedIn, GitHub, sitio web                      | `profiles` (Maestro)                                       | Lectura viva   | Enlaces clicables cuando existen.                        |
| Enlaces adicionales                              | `profile_links` (Maestro)                                  | Lectura viva   | Orden por `id`; etiqueta y URL.                          |
| Título profesional del CV                        | `cv_profiles.professional_title`                           | Lectura viva   | Propio del perfil; sin fallback al Maestro.              |
| Resumen del CV                                   | `cv_profiles.summary`                                      | Lectura viva   | Propio del perfil; sin fallback al Maestro.              |
| Nombre del perfil y estado                       | `cv_profiles.name`, `cv_profiles.is_active`                | Lectura viva   | El nombre solo etiqueta el selector; el estado filtra.   |
| Experiencia, educación, certificaciones, habilidades y proyectos | Tablas maestras + pivotes de SPEC-003 | Lectura viva | Solo las asociaciones incluidas; orden ya aplicado.     |
| Relevancia de una asociación                     | Pivotes de SPEC-003                                        | Lectura viva   | Solo gobierna el orden; no se envía ni se muestra.       |

**Título y resumen del Maestro**: no se leen para el CV. `CvProfileContent::personalData()` ya los excluye y el generador no los consulta por otra vía.

## Estructura de la vista

La página `Cv/Index` recibe cuatro props:

| Prop                  | Tipo                                      | Descripción                                                                 |
| --------------------- | ----------------------------------------- | --------------------------------------------------------------------------- |
| `profiles`            | `Array<{id, name, professional_title}>`   | Solo perfiles activos, ordenados por `name`; alimenta el selector.          |
| `status`              | `no_active_profiles \| selection_required \| unavailable \| ready \| error` | Estado de la página. |
| `selected_profile_id` | `number \| null`                           | Perfil activo cuya vista se está mostrando o se intentó mostrar.             |
| `preview`             | `CvPreviewData \| null`                   | Vista derivada; solo existe en `ready`.                                     |

`CvPreviewData`:

| Campo                | Tipo                                      | Descripción                                                                 |
| -------------------- | ----------------------------------------- | --------------------------------------------------------------------------- |
| `personal_data`      | `CvPreviewPersonalData`                   | Datos heredados del Maestro, incluidos `links` y `has_data`; misma forma que `CvProfilePersonalData`. |
| `professional_title` | `string \| null`                          | Título propio del perfil; `null` si está vacío.                              |
| `summary`            | `string \| null`                          | Resumen propio del perfil; `null` si está vacío.                             |
| `content`            | `CvPreviewSections`                       | Cinco colecciones ya ordenadas.                                              |
| `source_links`       | `CvPreviewSourceLinks`                    | Enlaces al perfil y a las secciones del Maestro.                             |
| `has_data`           | `boolean`                                 | `false` solo si no hay ningún dato que mostrar; la UI usa estado vacío.       |

`CvPreviewData` **no incluye** el nombre del perfil, `is_active`, `relevance`, `master_path`, `normalized_name` ni ningún identificador de plantilla.

## Mapeo de campos por clase

### Experiencia laboral (`work_experiences`)

| Campo payload      | Origen                          | Regla de presentación / omisión                                      |
| ------------------ | ------------------------------- | -------------------------------------------------------------------- |
| `id`               | `work_experiences.id`           | Clave interna de React; no se muestra.                               |
| `position`         | `work_experiences.position`     | Obligatorio; se muestra.                                             |
| `company`          | `work_experiences.company`      | Obligatorio; se muestra.                                             |
| `start_year`       | `work_experiences.start_year`   | Se pasa a `formatRange`; si no hay fechas, se omite el período.       |
| `start_month`      | `work_experiences.start_month`  | Se pasa a `formatRange`; mes abreviado si existe.                    |
| `end_year`         | `work_experiences.end_year`     | Se pasa a `formatRange`.                                             |
| `end_month`        | `work_experiences.end_month`    | Se pasa a `formatRange`.                                             |
| `is_current`       | `work_experiences.is_current`   | `true` → etiqueta «actual»; no se inventa si es `false`.              |
| `description`      | `work_experiences.description`  | Opcional; se omite si está vacío.                                    |
| `responsibilities` | `work_experiences.responsibilities` | Opcional; se muestra completo, con saltos de línea; se omite si vacío. |
| `achievements`     | `work_experiences.achievements` | Opcional; se muestra completo, con saltos de línea; se omite si vacío. |

### Educación (`education_entries`)

| Campo payload     | Origen                           | Regla de presentación / omisión                                      |
| ----------------- | -------------------------------- | -------------------------------------------------------------------- |
| `id`              | `education_entries.id`           | Clave interna; no se muestra.                                        |
| `institution`     | `education_entries.institution`  | Obligatorio; se muestra.                                             |
| `program`         | `education_entries.program`      | Obligatorio; se muestra.                                             |
| `degree_level`    | `education_entries.degree_level` | Opcional; se omite si está vacío.                                    |
| `start_year`      | `education_entries.start_year`   | Se pasa a `formatRange`; si no hay fechas, se omite el período.       |
| `start_month`     | `education_entries.start_month`  | Se pasa a `formatRange`.                                             |
| `end_year`        | `education_entries.end_year`     | Se pasa a `formatRange`.                                             |
| `end_month`       | `education_entries.end_month`    | Se pasa a `formatRange`.                                             |
| `is_in_progress`  | `education_entries.is_in_progress` | `true` → etiqueta «en curso»; no se inventa si es `false`.          |
| `description`     | `education_entries.description`  | Opcional; se omite si está vacío.                                    |

### Cursos y certificaciones (`certifications`)

| Campo payload    | Origen                         | Regla de presentación / omisión                                      |
| ---------------- | ------------------------------ | -------------------------------------------------------------------- |
| `id`             | `certifications.id`            | Clave interna; no se muestra.                                        |
| `name`           | `certifications.name`          | Obligatorio; se muestra.                                             |
| `institution`    | `certifications.institution`   | Opcional; se omite si está vacío.                                    |
| `date_year`      | `certifications.date_year`     | `formatYearMonth`; si no hay año, se omite la fecha.                  |
| `date_month`     | `certifications.date_month`    | `formatYearMonth`; mes abreviado si existe.                          |
| `duration`       | `certifications.duration`      | Opcional; se omite si está vacío.                                    |
| `category`       | `certifications.category`      | Texto libre; se omite si está vacío.                                 |
| `code`           | `certifications.code`          | Opcional; se muestra con etiqueta «Código»; se omite si vacío.       |
| `validation_url` | `certifications.validation_url` | Si existe, enlace «Validar certificación»; si no, no se muestra.     |

### Habilidades (`skills`)

| Campo payload | Origen                | Regla de presentación / omisión                                      |
| ------------- | --------------------- | -------------------------------------------------------------------- |
| `id`          | `skills.id`           | Clave interna; no se muestra.                                        |
| `name`        | `skills.name`         | Obligatorio; se muestra.                                             |
| `category`    | `skills.category`     | Se traduce con `SKILL_CATEGORY_LABELS`; se muestra como etiqueta.    |

- La lista es plana y conserva el orden recibido: relevancia descendente → categoría del Maestro → nombre.
- No se agrupa por categoría para no romper el orden por relevancia.

### Proyectos (`projects`)

| Campo payload  | Origen                  | Regla de presentación / omisión                                      |
| -------------- | ----------------------- | -------------------------------------------------------------------- |
| `id`           | `projects.id`           | Clave interna; no se muestra.                                        |
| `name`         | `projects.name`         | Obligatorio; se muestra.                                             |
| `role`         | `projects.role`         | Opcional; se omite si está vacío.                                    |
| `start_year`   | `projects.start_year`   | Se pasa a `formatRange`; si no hay fechas, se omite el período.       |
| `start_month`  | `projects.start_month`  | Se pasa a `formatRange`.                                             |
| `end_year`     | `projects.end_year`     | Se pasa a `formatRange`.                                             |
| `end_month`    | `projects.end_month`    | Se pasa a `formatRange`.                                             |
| `description`  | `projects.description`  | Opcional; se omite si está vacío.                                    |
| `results`      | `projects.results`      | Opcional; se muestra completo, con saltos de línea; se omite si vacío. |
| `url`          | `projects.url`          | Si existe, enlace «Ver proyecto»; si no, no se muestra.              |

- Los proyectos no tienen un campo de vigencia; no se muestra «actual» ni se infiere un fin.

## Datos personales y de contacto

`personal_data` se obtiene de `CvProfileContent::personalData()`:

| Campo           | Presentación                                                                 |
| --------------- | ---------------------------------------------------------------------------- |
| `full_name`     | Encabezado visible del CV (`h2`). Si falta, se usa un `h2` estructural visualmente oculto «Currículum» para conservar la jerarquía; no se muestra un nombre inventado. |
| `phone`         | Enlace `tel:` con el valor visible, si existe.                               |
| `email`         | Enlace `mailto:` con el valor visible, si existe.                            |
| `location`      | Texto visible, si existe.                                                    |
| `linkedin_url`  | Enlace «LinkedIn», si existe.                                                |
| `github_url`    | Enlace «GitHub», si existe.                                                  |
| `website_url`   | Enlace «Sitio web», si existe.                                               |
| `links`         | Lista de enlaces adicionales con su `label` como texto, si existen.          |
| `has_data`      | `false` si no hay ningún dato personal ni enlace; la cabecera no inventa datos. |

- Los campos vacíos no generan etiqueta, separador, guion, «N/D» ni espacio reservado.
- Los enlaces de contacto y adicionales se abren en una pestaña nueva solo cuando son externos (`target="_blank"` y `rel="noreferrer"`); email/teléfono no.
- El título y el resumen del Maestro no forman parte de `personal_data`.

## Datos propios y herencia

| Dato                                   | Origen                           | ¿Editable en el CV? | Regla en la vista                                      |
| -------------------------------------- | -------------------------------- | ------------------- | ------------------------------------------------------ |
| Nombre del perfil                      | `cv_profiles.name`               | No                  | Solo etiqueta el selector; no se imprime en la hoja.   |
| Título profesional                     | `cv_profiles.professional_title` | No                  | Propio; si está vacío, `null`; sin fallback.           |
| Resumen                                | `cv_profiles.summary`            | No                  | Propio; si está vacío, `null`; sin fallback.           |
| Estado activo/inactivo                 | `cv_profiles.is_active`          | No                  | Solo disponibilidad; no se envía al payload de la vista. |
| Datos personales y de contacto         | Maestro                          | No                  | Lectura viva; sin copia ni sobrescritura.              |
| Enlaces adicionales                    | Maestro                          | No                  | Lectura viva.                                          |
| Contenido profesional                  | Maestro + pivotes                | No                  | Solo elementos incluidos; orden de SPEC-003.           |
| Relevancia                             | Pivotes                          | No                  | Solo orden; no se imprime.                             |
| Título y resumen del Maestro           | Maestro                          | No                  | Excluidos; no se usan como respaldo.                   |

## Reglas de orden

El generador **no ordena**: consume las relaciones de `CvProfile`, que ya aplican:

| Clase           | Orden                                                                 | Registros sin fecha |
| --------------- | --------------------------------------------------------------------- | ------------------- |
| Experiencia     | Relevancia descendente; empate: `start_year` descendente, mes efectivo descendente, `id` descendente | Al final |
| Educación       | Relevancia descendente; empate: `start_year` descendente, mes efectivo descendente, `id` descendente | Al final |
| Certificaciones | Relevancia descendente; empate: `date_year` descendente, mes efectivo descendente, `id` descendente | Al final |
| Habilidades     | Relevancia descendente; empate: categoría (técnica, funcional, metodológica, herramienta, tecnología), luego `name` ascendente | No aplica |
| Proyectos       | Relevancia descendente; empate: `start_year` descendente, mes efectivo descendente, `id` descendente | Al final |

- El orden es estable y reproducible; repetir la consulta produce el mismo resultado mientras no cambien los datos.
- `relevance` es un metadato de configuración: no se envía ni se muestra (C7, FR-008).
- No existe columna de orden manual, drag-and-drop ni endpoint de reordenamiento.
- La UI recorre los arreglos tal como llegan; no aplica `sort`, agrupaciones ni reversos.

## Enlaces a las fuentes

`source_links` se calcula en el servidor:

| Clave               | Valor                         | Uso                                                  |
| ------------------- | ----------------------------- | ---------------------------------------------------- |
| `profile`           | `/profiles/{id}/edit`         | Corregir título, resumen, selección y relevancia.    |
| `personal_data`     | `/profile`                    | Corregir datos personales, contacto y enlaces.       |
| `work_experiences`  | `/experiences`                | Corregir o agregar experiencias.                     |
| `education_entries` | `/education`                  | Corregir o agregar educación.                        |
| `certifications`    | `/certifications`             | Corregir o agregar cursos y certificaciones.         |
| `skills`            | `/skills`                     | Corregir o agregar habilidades.                      |
| `projects`          | `/projects`                   | Corregir o agregar proyectos.                        |

- Se renderizan en un bloque «Corregir en las fuentes» **fuera** del `article` del CV.
- No introducen edición dentro de la hoja; los índices del Maestro ofrecen la edición de cada registro.
- Las secciones sin elementos se marcan como «Sin elementos en este CV» y enlazan a la sección para agregar contenido; esto es orientación fuera de la hoja.

## Estados y `has_data`

| Condición                                                                 | `status`             | `profiles`            | `selected_profile_id` | `preview` | UI                                                      |
| ------------------------------------------------------------------------- | -------------------- | --------------------- | --------------------- | --------- | ------------------------------------------------------- |
| No hay ningún perfil activo                                               | `no_active_profiles` | `[]`                  | `null`                | `null`    | Estado vacío; enlace a `/profiles`.                     |
| Hay activos; no hay query o `profile` está vacío                          | `selection_required` | Activos por nombre    | `null`                | `null`    | Selector sin preselección; se solicita elegir.          |
| Hay activos; query válido, perfil activo, al menos un dato                | `ready`              | Activos por nombre    | ID                    | Objeto    | Hoja de CV; `has_data: true`.                           |
| Hay activos; query válido, perfil activo, ningún dato                     | `ready`              | Activos por nombre    | ID                    | Objeto    | Estado vacío fuera de la hoja; `has_data: false`.       |
| Hay activos; query inválido, inactivo o inexistente                       | `unavailable`        | Activos por nombre    | `null`                | `null`    | Aviso y selector de alternativas; sin hoja.             |
| Falla la carga o composición                                              | `error`              | Best effort           | Best effort           | `null`    | Alerta, sin vista parcial; reintento.                   |

**Cálculo de `has_data`**:

```text
has_data =
    personal_data.has_data
    || professional_title no vacío (trim)
    || summary no vacío (trim)
    || alguna de las cinco colecciones de content no está vacía
```

- El nombre del perfil no cuenta como dato visible.
- Los metadatos (`relevance`, `is_active`) no cuentan.
- `status = ready` con `has_data = false` es un resultado válido; la UI muestra un estado vacío en lugar de una hoja en blanco (FR-018).

## Composición paso a paso

```text
GET /cv[?profile=ID]
  │
  ├─ 1. Cargar perfiles activos: CvProfile::where('is_active', true)->orderBy('name')->get()
  │      └─ Si no hay ninguno → status = no_active_profiles; fin.
  │
  ├─ 2. Interpretar el query:
  │      ├─ ausente o '' → status = selection_required; fin.
  │      ├─ no es un entero positivo → status = unavailable; fin.
  │      └─ entero positivo → buscar el perfil activo en la colección.
  │             └─ Si no existe → status = unavailable; fin.
  │
  ├─ 3. CvPreviewContent::build(profile):
  │      ├─ profile->load([workExperiences, educationEntries, certifications, skills, projects])
  │      ├─ personal_data = CvProfileContent::personalData()
  │      ├─ professional_title = normalizar(profile.professional_title)
  │      ├─ summary = normalizar(profile.summary)
  │      ├─ content = mapear las cinco relaciones (sin relevance)
  │      ├─ source_links = rutas de fuentes
  │      └─ has_data = fórmula anterior
  │
  ├─ 4. status = ready; preview = resultado
  │
  └─ 5. Inertia::render('Cv/Index', props)

Si cualquier paso de carga o composición (1–3) lanza Throwable:
  report(e); status = error; preview = null; perfiles/ID best effort; fin.
```

El `try/catch` cubre la consulta de perfiles activos, la resolución del query y la composición; el render de Inertia queda fuera, de modo que un fallo de renderizado no se confunde con el estado `error`.

- No se consulta el Maestro ni se cargan relaciones cuando el estado es `no_active_profiles`, `selection_required` o `unavailable`.
- No se escribe en ninguna tabla.
- No se usa caché ni sesión.

## No persistencia y ausencia de esquema

- **No hay entidad persistente**: la vista no tiene identidad, ID, tabla, historial ni versión.
- **No hay migración**: el esquema de SPEC-001/SPEC-002/SPEC-003 no se altera.
- **No hay seeder ni factory**: la vista no se siembra ni se fabrica.
- **No hay archivo**: no se genera un PDF, un JSON descargable ni un documento temporal; la respuesta HTML/JSON se transmite en memoria.
- **No hay caché de aplicación**: cada petición ejecuta la composición.
- **No hay almacenamiento del navegador**: no se usa `localStorage`, `sessionStorage`, IndexedDB ni cookies para la vista o la selección.
- **No hay historial de servidor**: la selección vive en el query string; recargar la URL recompone.
- **Lectura solamente**: `GET /cv` no modifica `cv_profiles`, pivotes, `profiles`, `profile_links` ni registros maestros.

**Excepción heredada**: `CvProfileContent::personalData()` usa `Profile::current()` (`firstOrCreate`). En una base pristine sin Maestro, la lectura puede crear el singleton vacío de SPEC-001. No es una copia del CV ni un dato profesional; es el comportamiento existente de la fuente. En el uso normal el Maestro ya existe.

## Invariantes

1. Solo se previsualiza un perfil **activo** seleccionado explícitamente.
2. Solo aparecen los elementos **incluidos** en el perfil; los no incluidos no se envían ni se renderizan.
3. El orden de cada sección es el de SPEC-003: relevancia descendente y, en empate, orden del Maestro.
4. `relevance` e `is_active` son metadatos: no se muestran como contenido.
5. El título y el resumen son **propios del perfil**; no hay fallback al Maestro ni a otro perfil.
6. Los datos personales y de contacto son del **Maestro vigente**, en lectura, sin copia ni sobrescritura.
7. Las secciones sin elementos **no aparecen** dentro del CV.
8. Los campos opcionales vacíos **no generan** etiquetas, separadores ni textos de relleno.
9. No se inventan datos personales, fechas, logros, resultados ni secciones.
10. No se muestra contenido de otro perfil.
11. La vista no se persiste, no se cachea, no se versiona y no se descarga.
12. La vista es de solo lectura y usa una única plantilla.
13. Los enlaces a las fuentes viven fuera de la hoja y no introducen edición dentro del CV.
14. El nombre del perfil no se imprime como título del CV.

## Trazabilidad FR → modelo

| Requisito | Elemento del modelo / regla                                                                 |
| --------- | ------------------------------------------------------------------------------------------- |
| FR-001    | Ruta `cv.index`, página `Cv/Index`, selector y vista en la misma página.                     |
| FR-002    | `profiles` solo con `is_active = true`, ordenado por `name`; etiqueta con título propio.     |
| FR-003    | Sin query → `selection_required`, `preview = null`; selector sin preselección.               |
| FR-004    | Selección en query; cada `GET` recompone; no hay estado cliente ni caché.                    |
| FR-005    | Query inactivo/inexistente/inválido → `unavailable`, alternativas activas.                   |
| FR-006    | Fuentes: Maestro + perfil; orden fijo de cabecera y cinco secciones.                        |
| FR-007    | Solo relaciones incluidas; colecciones vacías no generan sección.                           |
| FR-008    | Relaciones ordenadas de `CvProfile`; `relevance` fuera del payload y de la UI.               |
| FR-009    | `professional_title`/`summary` propios; normalización a `null`; sin fallback.               |
| FR-010    | `personalData()`; campos vacíos omitidos; enlaces clicables.                                |
| FR-011    | Mapeo completo de las cinco clases, incluidos responsabilidades, logros y resultados.        |
| FR-012    | Una única `CvPreview`; sin prop de plantilla.                                               |
| FR-013    | Payload de solo lectura; `CvPreview` sin formularios ni acciones.                            |
| FR-014    | `source_links` a perfil, Maestro y secciones, fuera de la hoja.                             |
| FR-015    | Composición por petición; sin tabla, archivo, caché, sesión ni historial.                   |
| FR-016    | Lectura viva de relaciones y `personalData()`; cascadas de SPEC-003 reflejadas.              |
| FR-017    | `no_active_profiles` + estado vacío con enlace a `/profiles`.                               |
| FR-018    | `has_data`; vista válida con lo disponible; estado vacío si no hay nada; orientación fuera.  |
| FR-019    | `status = error`, `preview = null`, `report()`, reintento.                                  |
| FR-020    | Texto completo con `whitespace-pre-line`/`break-words`; sin truncado; scroll natural.        |
| FR-021    | h1 → h2 → h3; etiquetas; enlaces; foco; sin dependencia del color.                          |
| FR-022    | Sin exportación, impresión, descarga, PDF, CV documentado, plantilla, ATS ni IA.             |

## Pruebas previstas sobre el modelo

| Riesgo / regla                                      | Prueba                                                                                  |
| --------------------------------------------------- | --------------------------------------------------------------------------------------- |
| Solo perfiles activos y orden por nombre            | Feature: opciones activas, inactivas ausentes, orden alfabético.                        |
| Sin selección no hay vista                          | Feature: `selection_required`, `preview = null`; Vitest: placeholder y ausencia de hoja. |
| Composición y exclusividad                          | Feature: una de cada clase, no incluidas ausentes, sin contenido de otro perfil.        |
| Campos FR-011                                       | Feature: payload con responsabilidades, logros, resultados, código y validación.        |
| Orden y estabilidad                                 | Feature: relevancia, empate por Maestro y repetición; `missing('relevance')`.            |
| Herencia y datos propios                            | Feature: Maestro con título/resumen; perfil sin ellos; sin fallback.                     |
| Vigencia                                            | Feature: actualizar/eliminar Maestro y perfil; la siguiente respuesta refleja.           |
| No persistencia                                     | Feature: conteos y `updated_at` sin cambios; ausencia de migraciones nuevas por revisión. |
| Estados vacíos                                      | Feature: sin activos, perfil sin contenido, Maestro vacío; Vitest: estados.              |
| Error                                               | Feature: doble de `CvPreviewContent` que lanza; `status = error`, `preview = null`.      |
| Solo lectura y accesibilidad                        | Vitest: sin controles, headings, enlaces y texto largo; validación manual.              |
