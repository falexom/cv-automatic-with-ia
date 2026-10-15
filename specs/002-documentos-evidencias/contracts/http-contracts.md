# Contratos HTTP e Interfaces: Documentos y Evidencias

**Feature**: `002-documentos-evidencias` | **Fecha**: 2026-10-15
**Referencias**: [spec.md](./spec.md) · [data-model.md](./data-model.md) · [research.md](./research.md)

Aplicación Inertia: cada petición web devuelve una página Inertia (HTML + props JSON) o una redirección; no existe API JSON pública separada. Los errores de validación usan el bolsillo de errores estándar de Inertia. No hay autenticación (monousuario). La única respuesta no-Inertia es el stream binario del PDF (`documents.file`).

## Rutas (routes/web.php)

| Método | URI                             | Nombre                 | Acción                           | Respuesta                                                          |
| ------ | ------------------------------- | ---------------------- | -------------------------------- | ------------------------------------------------------------------ |
| GET    | `/documents`                    | `documents.index`      | DocumentController@index         | página `Documents/Index`                                           |
| GET    | `/documents/create`             | `documents.create`     | DocumentController@create        | página `Documents/Create`                                          |
| POST   | `/documents`                    | `documents.store`      | DocumentController@store         | redirect → entidad Edit (si viene de una entidad) o `documents.index` |
| PUT    | `/documents/{document}`         | `documents.update`     | DocumentController@update        | redirect → `documents.index`                                       |
| DELETE | `/documents/{document}`         | `documents.destroy`    | DocumentController@destroy       | redirect → `documents.index`                                       |
| GET    | `/documents/{document}/viewer`  | `documents.viewer`     | DocumentController@viewer        | página `Documents/Viewer`                                          |
| GET    | `/documents/{document}/file`    | `documents.file`       | DocumentController@file          | stream PDF `inline` (no Inertia); 404 si no existe                 |
| POST   | `/documents/{document}/links`   | `documents.links.store`| DocumentLinkController@store     | redirect back (asociación creada)                                  |
| DELETE | `/documents/{document}/links`   | `documents.links.destroy` | DocumentLinkController@destroy | redirect back (vínculo eliminado)                                  |
| POST   | `/documents/verify`             | `documents.verify`     | DocumentController@verify        | redirect back con flash `verification`                             |

- Parámetros de filtro de `GET /documents`: `q` (texto sobre título/nombre original), `status` (`all|linked|unlinked`), `entity_type` (`certification|education|experience`) + `entity_id` (entidad concreta). Los filtros se preservan como query string.
- `linkable_type` se resuelve contra allow-list en servidor: `certification` → `Certification`, `education` → `EducationEntry`, `experience` → `WorkExperience`.
- El recurso de vínculo se identifica por el par (`linkable_type`, `linkable_id`) en el cuerpo de la petición (no hay ID de pivote en la URL).

## Contratos de páginas Inertia

### `Documents/Index` — Repositorio

Props:

- `documents`: `[{ id, display_name, original_filename, title, size_bytes, uploaded_at, is_available, last_verified_at, associations: [{ type, id, label }] }]` — orden por fecha de carga descendente; `display_name = title || original_filename`
- `filters`: `{ q, status, entity_type, entity_id }` (valores activos de la consulta)
- `filterOptions`: `{ certifications: [{id,label}], education: [{id,label}], experiences: [{id,label}] }` para el filtro por entidad
- `verification` (flash opcional): `{ checked, ok, unavailable, problems: [{ id, display_name, reason }] }` con `reason` ∈ `missing|altered`
- `errors`: bolsillo estándar de Inertia

Comportamiento:

- Cada documento muestra: nombre visible, tamaño legible, fecha de carga, estado de asociación (entidades o "Sin asociar") y aviso "No disponible" cuando `is_available = false`.
- Acciones por fila: **Ver** (→ `documents.viewer`), **Editar** (diálogo de metadatos → `PUT /documents/{id}`), **Asociar** (diálogo → `POST /documents/{id}/links`), **Eliminar** (`DeleteConfirmDialog` → `DELETE /documents/{id}`, indicando las entidades asociadas).
- Barra superior: **Subir documento** (→ `documents.create`) y **Verificar integridad** (`POST /documents/verify`), que muestra el reporte flash.
- Estado vacío con orientación (FR-024).

### `Documents/Create` — Carga

Props:

- `associationOptions`: `{ certifications: [{id,label}], education: [{id,label}], experiences: [{id,label}] }` (para la asociación opcional al subir desde el repositorio)
- `errors`

Comportamiento: un único formulario `POST /documents` con `forceFormData: true`:

- `file`: selector de un archivo PDF (un archivo por operación)
- `title`, `description`: opcionales
- `linkable_type` + `linkable_id`: opcionales (categoría + entidad específica)
- Éxito: toast "Documento subido." o "El archivo ya estaba en el repositorio; se reutilizó el documento existente." (FR-007); redirección a `documents.index`.

### `Documents/Viewer` — Visualización

Props:

- `document`: `{ id, display_name, original_filename, size_bytes, uploaded_at, is_available, last_verified_at }`
- `available`: boolean (resultado de la verificación de integridad realizada al abrir el visor)
- `fileUrl`: string (URL de `documents.file`, presente solo si `available`)
- `backUrl`: string (origen: repositorio o página de entidad)

Comportamiento:

- Si `available = true`: visor a pantalla completa (iframe con el PDF original servido por `documents.file`); no modifica el archivo.
- Si `available = false` (archivo ausente o alterado): aviso claro con el motivo, sin regenerar ni sustituir el archivo (FR-004); el documento queda marcado como no disponible.
- `documents.file` responde el archivo con `Content-Type: application/pdf` y `Content-Disposition: inline; filename="{original_filename sanitizado}"`; si el archivo no existe responde 404.

### `Certifications/Edit` · `Education/Edit` · `Experiences/Edit` — sección Documentos (FR-013)

Props adicionales (solo en `edit`):

- `documents`: `[{ id, display_name, size_bytes, is_available, uploaded_at }]` — documentos asociados a la entidad
- `errors`

Comportamiento:

- Tarjeta "Documentos" bajo el formulario: lista de documentos asociados (con **Ver** y **Quitar**), estado vacío orientativo y acción **Subir PDF** (mismo formulario de carga en un diálogo; crea documento + asociación en un solo flujo).
- **Quitar** ejecuta `DELETE /documents/{id}/links` con `linkable_type`/`linkable_id` de la entidad; el documento permanece en el repositorio como no asociado (FR-012).
- La carga envía `POST /documents` con `linkable_type`/`linkable_id`; el servidor redirige de vuelta a la página Edit de la entidad.

## Contratos de flujos especiales

### 1. Carga y reutilización por contenido (FR-001, FR-002, FR-006, FR-007)

1. `POST /documents` (multipart) con un archivo.
2. Validación: PDF real (mimetypes), estructura mínima (`%PDF-` … `%%EOF`), ≤ 20 MB, metadatos y asociación opcional válidos. Errores ⇒ volver al formulario con mensajes en español; nunca se persiste.
3. Se calcula el SHA-256. Si ya existe un documento con ese checksum: no se escribe archivo ni fila; se crea solo el vínculo solicitado (si lo hay) y se informa la reutilización.
4. Si es nuevo: se escribe `documents/{checksum}.pdf`, se crea la fila y el vínculo en una transacción; si algo falla no quedan archivos parciales ni filas.
5. Redirección según origen y flash `toast` de éxito.

### 2. Asociar / desasociar (FR-009–FR-015)

- Asociar desde el repositorio: `POST /documents/{document}/links` con `linkable_type` + `linkable_id`. Vínculo duplicado ⇒ error de validación ("Este documento ya está asociado a esa entidad."). Tipo fuera de la allow-list ⇒ error de validación.
- Desasociar desde entidad o repositorio: `DELETE /documents/{document}/links` con `linkable_type` + `linkable_id`.
- Ambas operaciones conservan el archivo y el contenido de la entidad (FR-016) y responden con redirect back + toast.

### 3. Visualización e integridad (FR-004, FR-017–FR-019)

1. `GET /documents/{document}/viewer` verifica el SHA-256 del archivo contra el checksum almacenado y actualiza `is_available` y `last_verified_at`.
2. Disponible ⇒ página con iframe a `documents.file` (visor nativo del navegador). Sin detección avanzada de PDFs protegidos (FR-019): si una comprobación simple ya disponible lo permite, se muestra una advertencia informativa; el visor nativo solicita la contraseña o informa que no puede abrir el archivo, y el original nunca se altera.
3. No disponible ⇒ página con aviso (`missing`: el archivo ya no está en el almacenamiento; `altered`: el contenido cambió) y sin iframe.
4. No hay descarga/exportación ni edición del PDF desde la aplicación (fuera de alcance).

### 4. Verificación completa bajo demanda (FR-004)

1. `POST /documents/verify` recorre todos los documentos: existencia del archivo + comparación de checksum.
2. Actualiza `is_available` y `last_verified_at` de cada documento.
3. Redirige de vuelta con flash `verification: { checked, ok, unavailable, problems[] }`.
4. La UI muestra el reporte (componente `VerificationReport`): totales y lista de documentos no disponibles con motivo.

### 5. Eliminación (FR-023)

1. La UI muestra `DeleteConfirmDialog` con el nombre visible y las entidades asociadas (si las hay); la acción es definitiva (sin papelera en el MVP).
2. `DELETE /documents/{document}`: elimina vínculos (cascada), fila y archivo físico; las entidades permanecen intactas.
3. Redirección a `documents.index` con toast "Documento eliminado."

### 6. Eliminación de entidades con documentos (FR-014)

1. Al eliminar una certificación, entrada de educación o experiencia, la UI existente advierte que los documentos asociados quedarán sin asociar (extensión del `DeleteConfirmDialog` actual con la cantidad de documentos).
2. La eliminación de la entidad elimina los vínculos por cascada; los documentos y archivos permanecen en el repositorio.

### 7. Errores y casos límite

- Archivo no PDF, extensión falsa o truncado ⇒ error de validación `file` ("El archivo debe ser un PDF válido.").
- Archivo mayor a 20 MB ⇒ error `file` ("El PDF supera el límite de 20 MB.").
- Carga rechazada por configuración de PHP ⇒ error `file` ("El servidor rechazó la carga por su tamaño; revisá upload_max_filesize y post_max_size.").
- Archivo idéntico ya almacenado ⇒ reutilización automática + toast informativo (no es error).
- Filtros de `Index` sin resultados ⇒ estado vacío de búsqueda con opción de limpiar filtros.
