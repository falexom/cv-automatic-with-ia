# Contratos HTTP e Interfaces: Perfil Profesional Maestro

**Feature**: `001-perfil-profesional-maestro` | **Fecha**: 2026-10-15

Aplicación Inertia: cada petición web devuelve una página Inertia (HTML + props JSON) o una redirección; no existe API JSON pública separada. Las respuestas de error de validación usan el bolsillo de errores de Inertia (compatible con 422).

## Rutas (routes/web.php)

| Método | URI                                    | Nombre                   | Acción                         | Respuesta                                                       |
| ------ | -------------------------------------- | ------------------------ | ------------------------------ | --------------------------------------------------------------- |
| GET    | `/`                                    | `home`                   | redirección                    | `redirect()->route('profile.edit')`                             |
| GET    | `/profile`                             | `profile.edit`           | ProfileController@edit         | página `Profile/Edit`                                           |
| PUT    | `/profile`                             | `profile.update`         | ProfileController@update       | redirect → `profile.edit`                                       |
| GET    | `/experiences`                         | `experiences.index`      | WorkExperienceController@index | página `Experiences/Index`                                      |
| GET    | `/experiences/create`                  | `experiences.create`     | @create                        | página `Experiences/Create`                                     |
| POST   | `/experiences`                         | `experiences.store`      | @store                         | redirect → `experiences.index`                                  |
| GET    | `/experiences/{experience}/edit`       | `experiences.edit`       | @edit                          | página `Experiences/Edit`                                       |
| PUT    | `/experiences/{experience}`            | `experiences.update`     | @update                        | redirect → `experiences.index`                                  |
| DELETE | `/experiences/{experience}`            | `experiences.destroy`    | @destroy                       | redirect → `experiences.index`                                  |
| GET    | `/education`                           | `education.index`        | EducationController@index      | página `Education/Index`                                        |
| GET    | `/education/create`                    | `education.create`       | @create                        | página `Education/Create`                                       |
| POST   | `/education`                           | `education.store`        | @store                         | redirect → `education.index`                                    |
| GET    | `/education/{entry}/edit`              | `education.edit`         | @edit                          | página `Education/Edit`                                         |
| PUT    | `/education/{entry}`                   | `education.update`       | @update                        | redirect → `education.index`                                    |
| DELETE | `/education/{entry}`                   | `education.destroy`      | @destroy                       | redirect → `education.index`                                    |
| GET    | `/certifications`                      | `certifications.index`   | CertificationController@index  | página `Certifications/Index`                                   |
| GET    | `/certifications/create`               | `certifications.create`  | @create                        | página `Certifications/Create`                                  |
| POST   | `/certifications`                      | `certifications.store`   | @store                         | redirect → `certifications.index` (o de vuelta con advertencia) |
| GET    | `/certifications/{certification}/edit` | `certifications.edit`    | @edit                          | página `Certifications/Edit`                                    |
| PUT    | `/certifications/{certification}`      | `certifications.update`  | @update                        | redirect → `certifications.index` (o de vuelta con advertencia) |
| DELETE | `/certifications/{certification}`      | `certifications.destroy` | @destroy                       | redirect → `certifications.index`                               |
| GET    | `/skills`                              | `skills.index`           | SkillController@index          | página `Skills/Index`                                           |
| GET    | `/skills/create`                       | `skills.create`          | @create                        | página `Skills/Create`                                          |
| POST   | `/skills`                              | `skills.store`           | @store                         | redirect → `skills.index`                                       |
| GET    | `/skills/{skill}/edit`                 | `skills.edit`            | @edit                          | página `Skills/Edit`                                            |
| PUT    | `/skills/{skill}`                      | `skills.update`          | @update                        | redirect → `skills.index`                                       |
| DELETE | `/skills/{skill}`                      | `skills.destroy`         | @destroy                       | redirect → `skills.index`                                       |
| GET    | `/projects`                            | `projects.index`         | ProjectController@index        | página `Projects/Index`                                         |
| GET    | `/projects/create`                     | `projects.create`        | @create                        | página `Projects/Create`                                        |
| POST   | `/projects`                            | `projects.store`         | @store                         | redirect → `projects.index`                                     |
| GET    | `/projects/{project}/edit`             | `projects.edit`          | @edit                          | página `Projects/Edit`                                          |
| PUT    | `/projects/{project}`                  | `projects.update`        | @update                        | redirect → `projects.index`                                     |
| DELETE | `/projects/{project}`                  | `projects.destroy`       | @destroy                       | redirect → `projects.index`                                     |

- Sin autenticación: no hay middleware `auth`; el starter kit se recorta a este alcance.
- `Profile` es singleton: no hay create/store/delete de perfil; la fila se crea implícitamente en el primer acceso a `/profile`.

## Contratos de páginas Inertia

### `Profile/Edit`

Props:

- `profile`: `{ id, full_name, professional_title, phone, email, location, linkedin_url, github_url, website_url, summary }`
- `links`: `[{ id, label, url }]` (colección completa actual)
- `errors`: bolsillo estándar de Inertia

Comportamiento: un solo formulario `PUT /profile`. Los enlaces adicionales se editan como filas dinámicas (agregar/quitar); al guardar se reemplaza la colección completa. Validación: `full_name` obligatorio; correo y URLs con formato válido.

### `Experiences/{Index,Create,Edit}` · `Projects/{Index,Create,Edit}`

Props de `Index`:

- `records`: lista ordenada cronológicamente (desc) con campos de la entidad, `skills[]` asociadas y bandera de estado si aplica
- `errors`

Props de `Create`/`Edit`:

- `record` (solo `Edit`)
- `skills`: catálogo completo `[{ id, name, category }]` para selección múltiple
- `errors`

Cuerpo esperado (`POST`/`PUT`): campos de la entidad + `skill_ids[]` (arreglo de ids). El servidor sincroniza el pivote.

### `Education/{Index,Create,Edit}`

Igual que Experiences, sin `skills` (la educación no se relaciona con habilidades).

### `Certifications/{Index,Create,Edit}`

Como Experiences, más:

- `duplicate_warning` (flash opcional): `{ existing_id, name, institution }` cuando el guardado fue detenido por duplicado (FR-018)
- Cuerpo esperado: campos + `skill_ids[]` + `confirm_duplicate` (boolean, solo al reintentar tras la advertencia)

### `Skills/{Index,Create,Edit}`

Props:

- `records` agrupadas por categoría (Index)
- `usage` (solo `Edit`/detalle): `{ experiences: [...], certifications: [...], projects: [...] }` — dónde se usa la habilidad (FR-021)
- Cuerpo esperado: `{ name, category }`

## Contratos de entrada de fecha (FR-034)

Cada fecha se envía como dos campos independientes:

- `*_year`: entero opcional (1900 ≤ año ≤ año actual + 10)
- `*_month`: entero opcional (1–12)

Reglas de validación del servidor:

- `*_month` presente exige `*_year` presente.
- Con fin e inicio presentes: `(end_year, end_month ?? 12) ≥ (start_year, start_month ?? 1)`; si no, error `end_year` con mensaje "La fecha de fin no puede ser anterior a la de inicio."
- Educación: `end_*` no aplica cuando `is_in_progress=true`; experiencia: `end_*` no aplica cuando `is_current=true`.

## Contratos de flujos especiales

### Confirmación de eliminación (FR-026, SC-007)

1. La UI muestra un `AlertDialog` con el nombre del registro.
2. Solo al confirmar se envía `DELETE {resource}/{id}`.
3. El servidor elimina (cascada de pivotes) y redirige al índice. No se requiere confirmación adicional en servidor.

### Advertencia de certificación duplicada (FR-018)

1. `POST/PUT` sin `confirm_duplicate`.
2. Si existe duplicado (nombre + institución normalizados): no persiste; responde redirect de vuelta al formulario con flash `duplicate_warning` (incluye el registro existente).
3. La UI muestra la advertencia y ofrece "Guardar de todos modos".
4. Reenvío con `confirm_duplicate=true` ⇒ persiste normalmente.

### Reemplazo de enlaces del perfil (FR-003)

- `PUT /profile` recibe `links[]` (arreglo completo, orden de filas) y reemplaza la colección: elimina los ausentes, crea los nuevos, actualiza los modificados.

### Estados vigente / en curso (FR-008, FR-013, caso límite)

- Enviar `end_year`/`end_month` con experiencia ⇒ `is_current` se fuerza a falso.
- Enviar experiencia sin fecha de fin ⇒ `is_current` se marca verdadero (la experiencia "vuelve a considerarse actual").
- `is_current=true` con fecha de fin ⇒ error de validación.
- Misma mecánica con `is_in_progress` en educación.

## Errores y estados

- **422 (validación)**: bolsillo `errors` de Inertia; cada formulario muestra mensajes junto al campo correspondiente, en español.
- **404**: registro inexistente ⇒ página de error estándar de Laravel.
- **Estados vacíos** (FR-029): cada `Index` recibe `records: []` y renderiza `EmptyState` con texto de orientación y botón de creación.
- **Fallo de guardado** (caso límite del spec): la UI conserva los valores del formulario y muestra el error; no se limpia el formulario ante errores.
