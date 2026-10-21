# Fase 0 — Investigación y Decisiones Técnicas: Documentos y Evidencias

**Feature**: Documentos y Evidencias (`002-documentos-evidencias`)
**Fecha**: 2026-10-15
**Fuente**: [spec.md](./spec.md) · [plan.md](./plan.md) · Constitución v1.0.0

Todas las incógnitas del contexto técnico quedaron resueltas. No quedan `NEEDS CLARIFICATION`.

---

## 1. Almacenamiento de originales y reutilización automática por checksum (FR-003, FR-007)

- **Decisión**: cada PDF se guarda en el disco `local` privado ya configurado (`storage/app/private/documents/{sha256}.pdf`); el nombre del archivo es el checksum SHA-256 de su contenido. La tabla `documents` almacena el mismo checksum con índice único. Al subir un archivo cuyo checksum ya existe, no se crea una segunda copia: se reutiliza el documento existente y solo se crea la asociación solicitada (con aviso al usuario).
- **Rationale**: el nombre derivado del contenido hace imposible la duplicación física (una escritura idéntica reescribe el mismo byte a byte) y convierte la reutilización en una búsqueda por índice único. Además, el checksum es la base de la verificación de integridad (FR-004) sin almacenar información adicional. Cumple Principio VII (conservación) y el incorporado Principio I (cero duplicación) a nivel de archivos.
- **Alternativas consideradas**:
    - Nombre aleatorio (UUID) por documento: imposibilita la garantía de copia única a nivel de disco; requiere lógica extra para la reutilización. Rechazada.
    - Carpetas por entidad (`documents/certifications/…`): acopla el archivo a una entidad cuando un mismo original puede respaldar varias (FR-011); mover copias al reasociar. Rechazada.
    - Re-compresión o normalización del PDF al guardar: prohibido por el Principio VII (alteraría el original). Rechazada.

## 2. Validación de PDF sin dependencias nuevas (FR-002)

- **Decisión**: validación en tres capas: (1) regla `mimetypes:application/pdf` (detección real por contenido, no solo extensión); (2) comprobación estructural mínima — el archivo comienza con el marcador `%PDF-` y contiene el marcador final `%%EOF` dentro de sus últimos 1024 bytes; (3) límite de tamaño (`max:20480` KB = 20 MB). Los archivos que no cumplen se rechazan con mensaje claro en español. Se agrega un mensaje específico cuando PHP rechaza la carga por configuración (`UPLOAD_ERR_INI_SIZE`/`post_max_size`).
- **Rationale**: cubre los casos del spec (archivos no PDF, renombrados con extensión falsa, truncados o corruptos) sin incorporar un parser PDF (nueva dependencia no justificada para una comprobación de admisión; Principios XII–XIII). La verificación profunda del contenido no es necesaria: la integridad posterior se garantiza por checksum y el visor nativo informa si un PDF es ilegible.
- **Alternativas consideradas**:
    - `smalot/pdfparser` u otra librería de parseo: dependencia nueva y pesada para validar solo la admisión; aumenta superficie de mantenimiento. Rechazada.
    - Confiar solo en la extensión `.pdf`: insuficiente (caso límite "extensión falsa" del spec). Rechazada.

## 3. Visualización con el visor PDF nativo del navegador (FR-017, FR-018)

- **Decisión**: endpoint de streaming propio `GET /documents/{document}/file` que responde el archivo `inline` con `Content-Type: application/pdf` y el nombre original sanitizado en `Content-Disposition`; la página `Documents/Viewer` incrusta ese endpoint en un `iframe` a pantalla completa de la aplicación. Ningún visor JavaScript ni dependencia nueva. No se implementa detección ni parsing de PDFs protegidos (FR-019): queda a cargo del visor nativo, que puede solicitar la contraseña o informar que no puede abrirlo.
- **Rationale**: navegadores de escritorio modernos (Chrome, Edge, Firefox, Safari) incluyen visor PDF integrado con paginación y zoom; es la solución más simple y fiel al original (el archivo se sirve tal cual). Un endpoint de aplicación permite controlar la verificación previa (FR-004) y no depende de URLs firmadas del framework (que evitan la lógica de negocio).
- **Alternativas consideradas**:
    - URL temporal firmada del disco `serve => true` del framework: útil, pero no permite ejecutar la verificación de integridad ni devolver estados de error con contexto de aplicación. Rechazada como mecanismo principal.
    - `pdf.js` / `react-pdf` / `@react-pdf-viewer`: dependencia nueva de peso considerable para un visor que el navegador ya provee; además no se implementa detección de PDFs protegidos (FR-019), por lo que no hay parsing adicional que justifique la librería. Rechazada.
    - Extraer páginas a imágenes: altera la fidelidad del original y es propio de la futura extracción, no de la visualización. Rechazada.

## 4. Verificación de integridad y estado de disponibilidad (FR-004, decisión de clarificación Q3)

- **Decisión**: la verificación compara `hash_file('sha256')` con el checksum almacenado. Se ejecuta de dos formas: (a) al abrir `Documents/Viewer` (verificación perezosa, antes de mostrar el iframe); (b) bajo demanda con `POST /documents/verify`, que recorre todos los documentos y emite un reporte (`{checked, ok, unavailable, problems[]}`). El resultado se persiste en `is_available` (booleano, default `true`) y `last_verified_at` (timestamp), de modo que el listado puede mostrar el estado sin re-verificar en cada carga (el listado no se penaliza con lecturas de disco en cada apertura).
- **Rationale**: verificar al acceder detecta el problema justo cuando importa y no penaliza el listado; el estado persistido da señales de UI estables y testeables ("No disponible" + fecha de última verificación). No se regenera ni sustituye nada (spec); restaurar un archivo desde una copia externa vuelve a marcar disponible en la siguiente verificación.
- **Alternativas consideradas**:
    - Verificar todo el repositorio en cada listado: rechazado en clarificación (Q3-A) por costo innecesario (una lectura de disco por documento en cada apertura).
    - Solo hash en memoria sin persistir estado: la UI no podría señalar documentos no disponibles sin releer el disco en cada visita. Rechazada.
    - Detección por eventos del sistema de archivos: infraestructura fuera de alcance para un MVP local. Rechazada.

## 5. Modelado de asociaciones: pivotes dedicados por entidad (FR-009–FR-015, Principio XIV)

- **Decisión**: tres tablas pivote explícitas con FK en cascada y clave primaria única compuesta: `document_certification (document_id, certification_id)`, `document_education_entry (document_id, education_entry_id)`, `document_work_experience (document_id, work_experience_id)`. Un documento puede tener N vínculos (o ninguno); una entidad puede tener N documentos. Eliminar una entidad elimina sus vínculos por cascada y **no** toca documentos ni archivos (FR-014). Eliminar un documento elimina sus vínculos y su archivo (FR-023; el archivo es único por checksum, así que no hay otros documentos que lo referencien).
- **Rationale**: es la convención ya usada en el proyecto (`skill_work_experience`, `skill_certification`, `skill_project`), garantiza integridad referencial a nivel de base de datos y evita lógica de limpieza manual. La garantía "documentos conservados al eliminar la entidad" es exactamente el comportamiento de la cascada del pivote.
- **Alternativas consideradas**:
    - Tabla polimórfica única `document_links (linkable_type, linkable_id)`: menos tablas, pero sin FK reales (integridad no garantizada por la base), limpieza manual al borrar entidades y consultas polimórficas menos explícitas; se aparta de la convención del proyecto. Rechazada hoy; la ampliación futura a proyectos puede reconsiderarla con justificación.
    - Columna `certification_id`/etc. en `documents`: una sola entidad por documento, incompatible con FR-011 (N entidades) y con la reutilización. Rechazada.

## 6. Rutas y flujos de asociación (FR-009–FR-013)

- **Decisión**: superficie HTTP mínima centrada en el repositorio, más dos puntos de integración:
    - Desde el repositorio: diálogo "Asociar" en `Documents/Index` → `POST /documents/{document}/links` con `linkable_type` (`certification|education|experience`) y `linkable_id`; desasociar → `DELETE /documents/{document}/links`.
    - Desde la entidad (páginas **Edit** de certificaciones, educación y experiencia): tarjeta "Documentos" que lista los documentos asociados (con "Ver" y "Quitar") y permite **subir un PDF nuevo**; la carga usa el mismo `POST /documents` con `linkable_type`/`linkable_id`, creando documento y vínculo en un solo flujo (FR-013). No existen páginas "show" en el proyecto; la página Edit es el detalle natural de cada entidad.
    - Redirección tras la carga: si viene de una entidad, vuelve a su página Edit; si viene del repositorio, a `Documents/Index`. Los `linkable_type` se resuelven contra una allow-list (evita cargar modelos arbitrarios).
- **Rationale**: un único par de endpoints de vínculo evita triplicar rutas anidadas por entidad y mantiene `DocumentController` como único responsable del ciclo de vida de documentos. Respeta el patrón actual (controllers delgados, flash `toast`, sin API JSON).
- **Alternativas consideradas**:
    - Rutas anidadas por entidad (`/certifications/{id}/documents`…): 6+ rutas adicionales y lógica repetida en tres controllers. Rechazada.
    - Solo asociación desde el repositorio: incumple FR-013 (cargar desde la entidad) y empeora la UX de respaldo inmediato. Rechazada.

## 7. Límite de tamaño y configuración PHP (FR-002, Supuestos del spec)

- **Decisión**: límite de validación 20 MB (`max:20480`). En `quickstart.md` y en el mensaje de error se documenta que el entorno PHP debe tener `upload_max_filesize` y `post_max_size` ≥ 25 MB; `.env.example` no define variables PHP (no aplica) y no se modifica. Si PHP descarta la carga antes de Laravel, se muestra un mensaje específico solicitando revisar la configuración.
- **Rationale**: el default de PHP (2 MB) es menor que el límite del producto; sin esta nota, el límite de 20 MB es inalcanzable en entornos no ajustados. No se agrega `.user.ini` ni `php.ini` al repositorio (afectan solo a algunos SAPIs y esconderían el problema).
- **Alternativas consideradas**:
    - Subir el límite por `.user.ini`: solo aplica a PHP-FPM/cgi, no al servidor embebido de `php artisan serve`. Rechazada.
    - Procesar cargas troceadas: complejidad desproporcionada (una operación, un archivo ≤ 20 MB). Rechazada.

## 8. Metadatos y estados del documento (FR-005, FR-019, FR-022)

- **Decisión**: columnas `original_filename` (inmutable, nombre del archivo subido), `title` y `description` (opcionales, editables), `size_bytes`, `checksum` (único), `is_available` (bool, default `true`), `last_verified_at` (nullable). El título visible en UI es `title` y, si falta, `original_filename`. La fecha de carga usa `created_at`.
- **Rationale**: cubre exactamente los metadatos del spec, separa el nombre original (referencia) del título descriptivo editable y da soporte a la señalización de disponibilidad sin columnas especulativas.
- **Alternativas consideradas**: mime_type almacenado (siempre `application/pdf`; innecesario); versión/revisión (fuera de alcance: sin versionado).

## 9. Convención de rutas y peticiones en el frontend

- **Decisión**: seguir la convención existente del código: URLs por string en `router.post/put/delete` y `useForm`, sin importar Wayfinder (generado pero no usado hoy); archivos con `forceFormData: true`; errores en español bajo cada campo; confirmaciones con `DeleteConfirmDialog`; resultados con flash `toast` y un nuevo flash `verification` para el reporte bajo demanda.
- **Rationale**: consistencia con las 16 páginas existentes y con las pruebas de componentes; introducir Wayfinder solo en esta capacidad crearía dos convenciones simultáneas.
- **Alternativas consideradas**: migrar las páginas nuevas a Wayfinder: fuera del alcance y del estilo actual. Rechazada por ahora.

## 10. Estrategia de pruebas (Principio XXI)

- **Decisión**:
    - Backend (Pest 4): `DocumentTest.php` — validación de carga (no PDF, extensión falsa, truncado, sobredimensionado, carga vacía), conservación byte a byte (comparar hash del archivo guardado con el original), reutilización automática de idénticos, metadatos, eliminación con vínculos, asociación/desasociación en las tres entidades, eliminación de entidades conserva documentos. `DocumentIntegrityTest.php` — visor con archivo presente/ausente/alterado, verificación bajo demanda y reporte.
    - Disco de pruebas: `Storage::fake('local')` + archivos PDF mínimos reales (fixture con cabecera `%PDF-` y `%%EOF`) mediante `UploadedFile::fake()->createWithContent(...)`, porque los `UploadedFile::fake()->create()` no producen contenido PDF válido.
    - Frontend (Vitest + RTL): componentes nuevos con lógica no trivial (`VerificationReport`, `AssociateDocumentDialog`, `LinkedDocumentsSection`, `DocumentMetaDialog`).
    - Regresión: extender `PersistenceTest` con un documento y sus vínculos.
- **Rationale**: cubre los puntos críticos constitucionales (importación de certificados, conservación de originales) y los criterios SC del spec con el stack de pruebas ya configurado.

---

**Resultado**: contexto técnico completo; sin `NEEDS CLARIFICATION` pendientes.
