# Contratos HTTP e Interfaces: Perfiles de CV

**Feature**: `003-perfiles-cv` | **Fecha**: 2026-10-22
**Referencias**: [spec.md](./spec.md) · [data-model.md](./data-model.md) · [research.md](./research.md)

Aplicación Inertia: cada petición devuelve una página Inertia (HTML + props JSON) o una redirección. No existe una API JSON pública separada ni autenticación: la aplicación es local y monousuario. Los errores de validación usan el bolsillo `errors` de Inertia (compatible con 422) y las redirecciones usan el flash `toast` existente.

## Rutas

Todas las rutas se declaran explícitamente en `routes/web.php`; no se usa `Route::resource`. El parámetro `{cvProfile}` se resuelve por implicit binding al modelo `CvProfile`.

| Método | URI                                | Nombre                | Acción                             | Respuesta                       |
| ------ | ---------------------------------- | --------------------- | ---------------------------------- | ------------------------------- |
| GET    | `/profiles`                        | `profiles.index`      | `CvProfileController@index`        | Página `Profiles/Index`         |
| GET    | `/profiles/create`                 | `profiles.create`     | `CvProfileController@create`       | Página `Profiles/Create`        |
| POST   | `/profiles`                        | `profiles.store`      | `CvProfileController@store`        | Redirect 302 → `profiles.show`  |
| POST   | `/profiles/initial`                | `profiles.initial`    | `CvProfileController@storeInitial` | Redirect 302 → `profiles.index` |
| GET    | `/profiles/{cvProfile}`            | `profiles.show`       | `CvProfileController@show`         | Página `Profiles/Show`          |
| GET    | `/profiles/{cvProfile}/edit`       | `profiles.edit`       | `CvProfileController@edit`         | Página `Profiles/Edit`          |
| PUT    | `/profiles/{cvProfile}`            | `profiles.update`     | `CvProfileController@update`       | Redirect 302 → `profiles.show`  |
| POST   | `/profiles/{cvProfile}/activate`   | `profiles.activate`   | `CvProfileController@activate`     | Redirect 302 → `profiles.index` |
| POST   | `/profiles/{cvProfile}/deactivate` | `profiles.deactivate` | `CvProfileController@deactivate`   | Redirect 302 → `profiles.index` |

**Orden de declaración**: `/profiles/create` y `/profiles/initial` se declaran antes de `/profiles/{cvProfile}` para que el comodín no las capture.

**No existe**:

- `DELETE /profiles/{cvProfile}`.
- Nombre de ruta `profiles.destroy`.
- Método `destroy` en `CvProfileController`.
- Acción, botón o icono de eliminación en la UI.

Una petición `DELETE /profiles/{id}` responde **405 Method Not Allowed** porque la URI coincide con rutas GET/PUT existentes. La ausencia se verifica en pruebas con `Route::has('profiles.destroy') === false` y con la respuesta 405.

## Contratos de páginas Inertia

### `Profiles/Index`

Props:

```json
{
    "profiles": [
        {
            "id": 1,
            "name": "Analista",
            "professional_title": "Analista de Sistemas",
            "is_active": true
        }
    ],
    "can_create_initial_profiles": false
}
```

- `profiles`: todos los perfiles, activos e inactivos, ordenados por `name` ascendente.
- `professional_title`: puede ser `null`; el listado muestra el título propio o un texto equivalente a «Sin título propio».
- `is_active`: estado actual; el listado lo muestra con `Badge` y acciones Editar/Ver/Activar o Desactivar.
- `can_create_initial_profiles`: `true` solo si no existe ningún perfil. La acción de presets se muestra únicamente en ese caso; el servidor vuelve a comprobarlo.
- Estado vacío (`profiles: []`): mensaje de orientación, acción «Crear perfil» y acción secundaria «Crear perfiles iniciales».
- No se envían contadores de contenido ni datos maestros en el listado.

### `Profiles/Create`

Props:

```json
{
    "options": {
        "work_experiences": [],
        "education_entries": [],
        "certifications": [],
        "skills": [],
        "projects": []
    }
}
```

Cada opción incluye los campos necesarios para mostrar una etiqueta legible y su período, sin datos de asociación:

| Sección             | Campos de cada opción                                                                                                  | Etiqueta sugerida                  |
| ------------------- | ---------------------------------------------------------------------------------------------------------------------- | ---------------------------------- |
| `work_experiences`  | `id`, `company`, `position`, `start_year`, `start_month`, `end_year`, `end_month`, `is_current`                        | «Cargo — Empresa» + período        |
| `education_entries` | `id`, `institution`, `program`, `degree_level`, `start_year`, `start_month`, `end_year`, `end_month`, `is_in_progress` | «Programa — Institución» + período |
| `certifications`    | `id`, `name`, `institution`, `date_year`, `date_month`                                                                 | «Nombre — Institución» + fecha     |
| `skills`            | `id`, `name`, `category`                                                                                               | Nombre + etiqueta de categoría     |
| `projects`          | `id`, `name`, `role`, `start_year`, `start_month`, `end_year`, `end_month`                                             | «Nombre — Rol» + período           |

- Las opciones se ordenan con el orden del Perfil Maestro (cronológico descendente o categoría/nombre) para que el usuario las vea en el mismo orden que en sus secciones.
- Las secciones sin opciones muestran orientación y un enlace a la sección del Maestro correspondiente.
- No se envía ninguna opción seleccionada: un perfil nuevo no incluye contenido por defecto (FR-009).

### `Profiles/Edit`

Props:

```json
{
    "profile": {
        "id": 1,
        "name": "Analista",
        "professional_title": "Analista de Sistemas",
        "summary": null,
        "is_active": true
    },
    "options": { "...": "igual que en Create" },
    "selections": {
        "work_experiences": [{ "id": 12, "relevance": "alta" }],
        "education_entries": [],
        "certifications": [{ "id": 4, "relevance": "media" }],
        "skills": [{ "id": 7, "relevance": "baja" }],
        "projects": []
    }
}
```

- `profile` contiene solo los datos propios; no incluye datos personales ni el título del Maestro.
- `options` es el catálogo completo del Maestro para poder agregar o quitar elementos.
- `selections` contiene las asociaciones actuales con su relevancia; el formulario las marca y muestra su select correspondiente.
- El formulario no incluye campos para editar datos personales heredados ni registros maestros.

### `Profiles/Show`

Props:

```json
{
    "profile": {
        "id": 1,
        "name": "Analista",
        "professional_title": "Analista de Sistemas",
        "summary": "Resumen propio del perfil.",
        "is_active": true
    },
    "personal_data": {
        "full_name": "Ada Lovelace",
        "phone": "+54 11 5555 1234",
        "email": "ada@example.com",
        "location": "Buenos Aires, Argentina",
        "linkedin_url": "https://www.linkedin.com/in/ada",
        "github_url": "https://github.com/ada",
        "website_url": "https://ada.example.com",
        "links": [
            { "id": 1, "label": "Blog", "url": "https://blog.example.com" }
        ],
        "has_data": true
    },
    "content": {
        "work_experiences": [
            {
                "id": 12,
                "relevance": "alta",
                "master_path": "/experiences/12/edit",
                "company": "Acme",
                "position": "Analista funcional",
                "start_year": 2022,
                "start_month": 3,
                "end_year": null,
                "end_month": null,
                "is_current": true,
                "description": "Relevamiento y documentación de procesos."
            }
        ],
        "education_entries": [],
        "certifications": [],
        "skills": [
            {
                "id": 7,
                "relevance": "baja",
                "master_path": "/skills/7/edit",
                "name": "PHP",
                "category": "tecnologia"
            }
        ],
        "projects": []
    }
}
```

**`personal_data`**

- Incluye solo datos personales y de contacto del Maestro y sus enlaces adicionales.
- **No incluye `professional_title` ni `summary` del Maestro**: el título y el resumen del perfil son propios.
- `has_data` es `false` cuando el Maestro no tiene ningún dato personal ni enlace; la UI muestra una orientación en lugar de inventar información.
- Es de solo lectura: no hay payload de escritura para estos campos en el perfil.

**`content`**

| Sección             | Campos de cada elemento                                                                                                                                           |
| ------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `work_experiences`  | `id`, `relevance`, `master_path`, `company`, `position`, `start_year`, `start_month`, `end_year`, `end_month`, `is_current`, `description`                        |
| `education_entries` | `id`, `relevance`, `master_path`, `institution`, `program`, `degree_level`, `start_year`, `start_month`, `end_year`, `end_month`, `is_in_progress`, `description` |
| `certifications`    | `id`, `relevance`, `master_path`, `name`, `institution`, `date_year`, `date_month`, `duration`, `category`, `code`, `validation_url`                              |
| `skills`            | `id`, `relevance`, `master_path`, `name`, `category`                                                                                                              |
| `projects`          | `id`, `relevance`, `master_path`, `name`, `role`, `start_year`, `start_month`, `end_year`, `end_month`, `url`, `description`                                      |

- Cada arreglo llega **ya ordenado** por relevancia descendente y, en empate, por el orden del Maestro. La UI debe renderizar el orden recibido y no reordenar.
- `master_path` identifica la procedencia y permite navegar al registro maestro (`/experiences/{id}/edit`, `/education/{id}/edit`, `/certifications/{id}/edit`, `/skills/{id}/edit` o `/projects/{id}/edit`); no se muestra un formulario de edición dentro del perfil.
- Los arreglos vacíos son válidos: cada sección muestra un estado vacío con orientación (FR-007).
- Un elemento maestro eliminado deja de aparecer porque su asociación se elimina en cascada; no se devuelve una copia obsoleta.
- El detalle es una pantalla de configuración y revisión, no una vista previa de CV: no aplica plantilla, formato de impresión ni exportación.

## Payload de creación y actualización

`POST /profiles` y `PUT /profiles/{cvProfile}` comparten la misma forma:

```json
{
    "name": "Analista",
    "professional_title": "Analista de Sistemas",
    "summary": null,
    "work_experiences": [{ "id": 12, "relevance": "alta" }],
    "education_entries": [{ "id": 3, "relevance": "media" }],
    "certifications": [{ "id": 4 }],
    "skills": [{ "id": 7, "relevance": "baja" }],
    "projects": []
}
```

**Reglas del payload**

- `name`: obligatorio; string; máximo 100; se compara normalizado.
- `professional_title`: opcional; string; máximo 255; `null` o vacío se guarda como `null`.
- `summary`: opcional; string; `null` o vacío se guarda como `null`.
- Cada arreglo de selección es opcional. Si se omite, se interpreta como **vacío**.
- Cada elemento requiere `id`; `relevance` es opcional y, si se omite, se guarda como `media`.
- **Actualización por reemplazo completo**: los arreglos enviados representan el conjunto completo de esa clase. Un elemento ausente se desasocia; un elemento presente se agrega o actualiza; un elemento con nueva relevancia actualiza la asociación existente. No hay actualizaciones parciales por clase.
- No se procesan campos de datos personales, `normalized_name`, `is_active` ni datos maestros en este payload. El estado solo cambia por las acciones de activación/desactivación.
- Un mismo elemento se puede reutilizar en varios perfiles; cada payload solo afecta al perfil indicado.

**Respuestas**

- `POST /profiles`: 302 → `profiles.show` del perfil creado; flash `toast` de éxito.
- `PUT /profiles/{cvProfile}`: 302 → `profiles.show` del perfil actualizado; flash `toast` de éxito.
- En errores de validación: redirección de vuelta al formulario con el bolsillo `errors` de Inertia (compatible con 422) y los valores anteriores conservados por `useForm`.
- Si la actualización falla antes de completar la transacción, no se guarda ningún cambio parcial; el usuario puede reintentar con los valores que siguen en pantalla.

## Validaciones

| Campo                           | Reglas                                                                                              | Mensaje esperado                                                               |
| ------------------------------- | --------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| `name`                          | `required`, `string`, `max:100`, único normalizado (excluyendo el propio perfil al actualizar)      | «El nombre del perfil es obligatorio.» / «Ya existe un perfil con ese nombre.» |
| `professional_title`            | `nullable`, `string`, `max:255`                                                                     | «El título profesional no puede superar los 255 caracteres.»                   |
| `summary`                       | `nullable`, `string`                                                                                | —                                                                              |
| `work_experiences`              | `sometimes`, `array`                                                                                | —                                                                              |
| `work_experiences.*.id`         | `required`, `integer`, `distinct`, existe en `work_experiences` con `profile_id` del Maestro actual | «El elemento seleccionado no pertenece al Perfil Maestro o ya no existe.»      |
| `work_experiences.*.relevance`  | `sometimes`, `in:alta,media,baja`                                                                   | «La relevancia debe ser Alta, Media o Baja.»                                   |
| `education_entries.*.id`        | igual, contra `education_entries`                                                                   | igual                                                                          |
| `education_entries.*.relevance` | igual                                                                                               | igual                                                                          |
| `certifications.*.id`           | igual, contra `certifications`                                                                      | igual                                                                          |
| `certifications.*.relevance`    | igual                                                                                               | igual                                                                          |
| `skills.*.id`                   | igual, contra `skills`                                                                              | igual                                                                          |
| `skills.*.relevance`            | igual                                                                                               | igual                                                                          |
| `projects.*.id`                 | igual, contra `projects`                                                                            | igual                                                                          |
| `projects.*.relevance`          | igual                                                                                               | igual                                                                          |

- Los IDs de otro Maestro se rechazan porque la regla de existencia incluye `profile_id` del Maestro actual.
- Los IDs inexistentes o de otra clase se rechazan.
- Los duplicados dentro de una misma clase se rechazan con `distinct` y, a nivel de datos, con la clave primaria compuesta.
- Un elemento ya incluido puede reenviarse: actualiza su relevancia, no crea una fila nueva.
- No se crean registros maestros desde este payload.
- Los errores anidados se muestran junto al elemento correspondiente (por ejemplo, `work_experiences.0.id`).

## Acción de perfiles iniciales

`POST /profiles/initial`

- Sin cuerpo.
- **Si no existe ningún perfil**: crea en una transacción los dos perfiles iniciales y redirige a `profiles.index` con toast de éxito.

| Nombre      | `professional_title`        | `is_active` | `summary` | Contenido |
| ----------- | --------------------------- | ----------- | --------- | --------- |
| Analista    | Analista de Sistemas        | `true`      | `null`    | Vacío     |
| Programador | Programador / Desarrollador | `true`      | `null`    | Vacío     |

- **Si ya existe cualquier perfil**: no escribe nada, no sobrescribe, no duplica y redirige a `profiles.index` con toast informativo («Los perfiles iniciales solo se crean cuando no existe ningún perfil.»).
- La acción es idempotente desde la perspectiva del usuario: ejecutarla de nuevo no modifica el estado.
- No se crean automáticamente en migraciones, seeders ni al abrir la aplicación.
- Renombrar o desactivar un preset no hace que se vuelva a crear: la guarda es la existencia de cualquier perfil, no el nombre.

## Activación y desactivación

`POST /profiles/{cvProfile}/activate` y `POST /profiles/{cvProfile}/deactivate`

- Sin cuerpo.
- Cambian `is_active` a `true` o `false` respectivamente.
- No modifican nombre, título, resumen, asociaciones ni relevancias.
- Redirigen a `profiles.index` con toast de éxito.
- Son reversibles: desactivar y reactivar deja el perfil en el estado anterior.
- Un perfil inactivo sigue disponible en `profiles.show` y `profiles.edit`, y se lista diferenciado.
- Si el perfil ya está en el estado solicitado, la operación es idempotente y no altera otros datos.
- No existe eliminación; la única retirada de uso es desactivar.

## Errores y estados

- **Validación**: bolsillo `errors` de Inertia (compatible con 422); mensajes en español junto al campo o elemento correspondiente; el formulario conserva los valores.
- **404**: perfil inexistente en `show`, `edit`, `update`, `activate` o `deactivate`; página de error estándar de Laravel.
- **405**: `DELETE /profiles/{cvProfile}`; no hay ruta `DELETE`.
- **500**: error inesperado; la transacción evita asociaciones parciales; la UI muestra el error estándar y no marca el guardado como exitoso.
- **Fallo de guardado**: no se limpia el formulario; el usuario puede reintentar. No hay actualizaciones optimistas de estado.
- **Flash**: `toast` con `type` (`success` o `info`) y `message`, consumido por `useFlashToast` en `AppLayout`.

## Garantías de orden

- El servidor devuelve los cinco arreglos de `content` ya ordenados.
- Orden: relevancia descendente (Alta, Media, Baja) y, en empate, orden del Perfil Maestro.
- Experiencia, educación, certificaciones y proyectos: fecha de inicio más reciente primero; sin fecha al final; desempate estable por `id` descendente.
- Habilidades: categoría (técnica, funcional, metodológica, herramienta, tecnología) y luego nombre.
- La UI no debe ordenar, agrupar por relevancia ni reordenar secciones; solo renderiza el orden recibido.
- No hay parámetros de orden ni endpoint de reordenamiento.

## Límites de alcance del contrato

Este contrato **no** incluye:

- Generación de CV, vista previa, PDF, plantillas, exportación ni CV documentado.
- Análisis de ofertas, adaptación a ofertas, postulaciones, cartas ni IA.
- Asociación de documentos o evidencias a perfiles.
- Edición de datos personales o de registros maestros desde el perfil.
- Duplicación del Perfil Maestro o copia de sus registros.
- Eliminación definitiva de perfiles, historial o versionado.
- Orden manual, drag-and-drop o reordenamiento de secciones.
- Multiusuario, autenticación, sincronización externa, exportación o respaldo.

Cualquier necesidad de ampliar este contrato debe escalarse y no implementarse incidentalmente (Principios XXV–XXVI).
