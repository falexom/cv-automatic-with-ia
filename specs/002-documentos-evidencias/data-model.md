# Fase 1 — Modelo de Datos: Documentos y Evidencias

**Feature**: `002-documentos-evidencias` | **Fecha**: 2026-10-15
**Fuente**: [spec.md](./spec.md) · Decisiones: [research.md](./research.md)

## Visión general

El **Documento** es la unidad almacenada del repositorio: un PDF original conservado sin alteración, con metadatos y estado de disponibilidad. Un documento puede existir sin asociación (pendiente) y asociarse a una o más entidades permitidas: **Certificación**, **EducationEntry** (educación) y **WorkExperience** (experiencia). Los proyectos quedan excluidos del MVP (FR-015).

```text
Profile (1)
├── Certification (N)     ──N:M── Document
├── EducationEntry (N)    ──N:M── Document
├── WorkExperience (N)    ──N:M── Document
└── Document (N)                        (puede existir sin vínculos)
```

- Un documento con **cero vínculos** es un documento "pendiente" (estado válido, visible en el repositorio).
- El archivo físico se identifica por su contenido: `documents/{sha256}.pdf` en el disco `local` privado. Un documento = un checksum = un archivo (reutilización automática, FR-007).

## Convenciones

- **Nombre de archivo original**: se conserva como metadato inmutable (`original_filename`); el nombre en disco es el checksum.
- **Inmutabilidad del original**: ninguna operación (visualizar, asociar, desasociar, editar metadatos, verificar) escribe sobre el archivo (FR-003).
- **Eliminaciones**: definitivas, con confirmación en UI (FR-023). Los vínculos se eliminan en cascada; las entidades nunca se ven afectadas al borrar un documento, y los documentos nunca se ven afectados al borrar una entidad (FR-014).
- **Documento disponible/no disponible**: estado derivado de la verificación de integridad (checksum) y persistido en `is_available` + `last_verified_at`.
- **Auditoría temporal**: la fecha de carga es `created_at`; no hay versionado ni historial (supuesto del spec).

## Entidades

### Document — Documento original (FR-001–FR-008, FR-020–FR-023)

| Columna           | Tipo              | Nulo | Reglas                                                                 |
| ----------------- | ----------------- | ---- | ---------------------------------------------------------------------- |
| id                | integer PK        | no   |                                                                        |
| profile_id        | FK → profiles     | no   | cascade delete                                                         |
| original_filename | string(255)       | no   | nombre del archivo subido por el usuario (inmutable)                   |
| title             | string(255)       | sí   | editable (FR-022); si falta, la UI muestra `original_filename`         |
| description       | text              | sí   | editable (FR-022)                                                      |
| size_bytes        | unsignedBigInteger| no   | tamaño del archivo cargado (FR-005)                                    |
| checksum          | string(64), único | no   | SHA-256 del contenido; identidad del archivo y base de la integridad   |
| is_available      | boolean           | no   | default `true`; pasa a `false` si falta o fue alterado (FR-004)        |
| last_verified_at  | timestamp         | sí   | última verificación de integridad (al visualizar o bajo demanda)       |
| timestamps        |                   |      | `created_at` = fecha de carga (FR-005)                                 |

- **Índice único**: `checksum` (global; el perfil es singleton). Garantiza una sola fila por contenido y hace determinista la reutilización.
- **Archivo en disco**: disco `local`, ruta `documents/{checksum}.pdf`. El archivo se escribe una vez al crear el documento; nunca se modifica ni se renombra (FR-003).
- **Relaciones**: `belongsTo` Profile; `belongsToMany` Certification, EducationEntry, WorkExperience (pivotes dedicados).
- **Ciclo de vida del archivo**:
    - **Alta con contenido ya existente** (FR-007): si `checksum` ya está registrado, no se crea fila ni se escribe archivo: se reutiliza el documento existente y se crea únicamente el vínculo solicitado; la UI informa "El archivo ya estaba en el repositorio; se reutilizó el documento existente".
    - **Alta con contenido nuevo**: se escribe el archivo en `documents/{checksum}.pdf`, se crea la fila y (opcionalmente) el primer vínculo, en una sola transacción.
    - **Baja** (FR-023): se elimina la fila (vínculos en cascada) y el archivo físico; por la unicidad del checksum no hay otros documentos que lo referencien.
    - **Carga fallida o interrumpida** (FR-006): sin archivo parcial ni fila; si la escritura del archivo o la transacción falla, se informa y no queda residuo (el archivo se escribe en una ruta definitiva solo tras validar y calcular el checksum).

### Certification — Curso / certificación (existente, SPEC-001)

- **Cambio**: nueva relación `belongsToMany` Document (pivote `document_certification`).
- Sin cambios de columnas ni de reglas existentes (FR-025 del SPEC-001 intacto).
- Al eliminar la certificación: los vínculos caen por cascada y los documentos permanecen sin asociar (FR-014).

### EducationEntry — Educación (existente, SPEC-001)

- **Cambio**: nueva relación `belongsToMany` Document (pivote `document_education_entry`).
- Corresponde a la "Evidencia documental asociada" del Principio V (FR-010).

### WorkExperience — Experiencia laboral (existente, SPEC-001)

- **Cambio**: nueva relación `belongsToMany` Document (pivote `document_work_experience`).
- Inclusión explícita registrada en la clarificación del 2026-10-15 (FR-010); la Constitución no detalla evidencia documental para experiencia, pero no la prohibe.

### Pivotes de asociación (FR-009–FR-014)

| Tabla                        | Columnas                                             | Reglas                                            |
| ---------------------------- | ---------------------------------------------------- | ------------------------------------------------- |
| `document_certification`     | `document_id`, `certification_id`                    | PK compuesta; ambas FK con `cascadeOnDelete`      |
| `document_education_entry`   | `document_id`, `education_entry_id`                  | PK compuesta; ambas FK con `cascadeOnDelete`      |
| `document_work_experience`   | `document_id`, `work_experience_id`                  | PK compuesta; ambas FK con `cascadeOnDelete`      |

- El pivote `document_id` es el mismo en las tres tablas: un documento puede estar asociado simultáneamente a entidades de distinto tipo (FR-011).
- Unicidad: la clave compuesta impide vínculos duplicados documento–entidad.
- No se admiten asociaciones a otras entidades (FR-015): la allow-list de tipos se valida en `StoreDocumentLinkRequest` y en la carga desde entidad.

## Reglas de validación (servidor)

### Carga de documento (FR-001, FR-002)

| Campo             | Reglas                                                                                                 |
| ----------------- | ------------------------------------------------------------------------------------------------------ |
| `file`            | obligatorio; archivo real; `mimetypes:application/pdf`; ≤ 20 MB (`max:20480`); estructura PDF válida mínima (prefijo `%PDF-` y marcador `%%EOF`); mensajes en español |
| `title`           | opcional; string; máximo 255                                                                           |
| `description`     | opcional; string; máximo 2000                                                                          |
| `linkable_type`   | opcional; `in:certification,education,experience`                                                      |
| `linkable_id`     | obligatorio si hay `linkable_type`; entero; existencia en la tabla correspondiente al tipo             |

- Carga de un solo archivo por operación (FR-001, clarificación del 2026-10-15).
- Si PHP rechaza la carga por límite de configuración, mensaje específico que indica revisar `upload_max_filesize`/`post_max_size` (ver quickstart).

### Edición de metadatos (FR-022)

| Campo         | Reglas                                  |
| ------------- | --------------------------------------- |
| `title`       | opcional; string; máximo 255            |
| `description` | opcional; string; máximo 2000           |

El archivo, el checksum, el nombre original y el tamaño son inmutables.

### Asociación (FR-009, FR-010, FR-012, FR-015)

| Campo           | Reglas                                                                                     |
| --------------- | ------------------------------------------------------------------------------------------ |
| `linkable_type` | obligatorio; `in:certification,education,experience`                                       |
| `linkable_id`   | obligatorio; entero; debe existir en la tabla del tipo; error si el vínculo ya existe      |

## Transiciones de estado

### Disponibilidad del documento (`is_available`)

| Estado inicial | Evento                                                       | Estado final | Efectos                                           |
| -------------- | ------------------------------------------------------------ | ------------ | ------------------------------------------------- |
| `true`         | Verificación al abrir el visor: archivo presente e íntegro    | `true`       | `last_verified_at = ahora`                        |
| `true`         | Verificación al abrir el visor: archivo ausente o alterado    | `false`      | `last_verified_at = ahora`; visor muestra aviso   |
| `false`        | Verificación al abrir el visor: archivo presente e íntegro    | `true`       | `last_verified_at = ahora`                        |
| `true/false`   | `POST /documents/verify` (recorrido completo)                 | según resultado | Reporte `{checked, ok, unavailable, problems[]}` |

- La alteración nunca se "repara" ni se sustituye automáticamente (FR-004): solo se señala.

### Vínculos documento–entidad

| Evento                             | Efecto                                                                 |
| ---------------------------------- | ---------------------------------------------------------------------- |
| Asociar (documento ↔ entidad)      | Se crea la fila del pivote; el archivo no se toca (FR-016)             |
| Desasociar                         | Se elimina la fila del pivote; el documento permanece (FR-012)         |
| Eliminar la entidad                | Cascada de pivotes: documentos quedan sin asociación; archivos intactos (FR-014) |
| Eliminar el documento              | Cascada de pivotes + borrado del archivo; entidades intactas (FR-023)  |

## Serialización para la UI (Inertia)

- **Documento (repositorio)**: `{ id, title, original_filename, display_name, size_bytes, uploaded_at, is_available, last_verified_at, associations: [{ type, id, label }] }`, donde `display_name = title || original_filename` y `type` ∈ `certification|education|experience` con etiqueta legible de la entidad (`label`).
- **Documento (en página de entidad)**: `{ id, display_name, size_bytes, is_available, uploaded_at }`.
- **Reporte de verificación** (flash): `{ checked, ok, unavailable, problems: [{ id, display_name, reason }] }`, con `reason` ∈ `missing|altered`.
