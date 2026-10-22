# Feature Specification: Documentos y Evidencias

**Feature Branch**: `002-documentos-evidencias`

**Created**: 2026-10-15

**Status**: Cerrado formalmente — 2026-10-21. Implementación terminada; validación manual aprobada (2026-10-15); pruebas automatizadas aprobadas (2026-10-21); integrado en main mediante el commit 1f154bc. Cierre aprobado por el responsable del proyecto. Sin tag de versión: la Constitución y el roadmap no exigen tag y se acepta el commit de integración como referencia de cierre.

**Input**: User description: "Crear el módulo Documentos y Evidencias del proyecto CV Automático. Debe permitir subir y conservar PDFs originales, asociarlos a certificaciones y otras entidades permitidas, visualizar los documentos y preparar la base para el futuro CV documentado. Respetar estrictamente la Constitución activa. No incluir OCR ni generación de CV en este Spec."

## Problema y Objetivo

**Problema**: el Perfil Profesional Maestro ya registra cursos y certificaciones y educación, pero los PDFs originales que respaldan esa información no tienen un lugar propio donde conservarse. Los archivos quedan dispersos en el equipo del usuario, se pierden, se renombran o se alteran sin control. Sin documentos originales conservados íntegramente y vinculados a sus entidades, no es posible construir el futuro CV documentado (concatenación de los PDFs originales, Principio X) ni aplicar la selección de evidencias (Principio XI).

**Objetivo**: incorporar un repositorio documental local que permita cargar, conservar sin alteración y visualizar PDFs originales, y asociarlos a las entidades permitidas del Perfil Maestro (cursos/certificaciones, educación y experiencia laboral), dejando preparada la base documental para la futura extracción de información y para el CV documentado, sin implementar todavía ninguna de esas capacidades.

## Alcance

### Incluido en esta capacidad

- Carga de archivos PDF originales al repositorio documental (almacenamiento local).
- Validación de la carga: archivo PDF legible y tamaño dentro del límite permitido.
- Conservación íntegra y sin alteración de los originales: los archivos no se recomprimen, regeneran, convierten ni modifican en ninguna operación.
- Verificación de integridad: comprobar que el archivo conservado corresponde al cargado y detectar pérdida o alteración externa.
- Metadatos del documento: nombre original del archivo, tamaño, fecha de carga, título y descripción opcionales.
- Asociación de documentos a entidades permitidas del Perfil Maestro: cursos/certificaciones, educación y experiencia laboral.
- Desasociación y reasociación; documentos sin asociar visibles como pendientes.
- Carga de documentos desde el repositorio y directamente desde la entidad (certificación, educación o experiencia).
- Visualización del documento PDF dentro de la aplicación, sin modificar el archivo.
- Gestión del repositorio: listado con estado de asociación, filtrado por estado y por entidad, búsqueda, edición de título y descripción, y eliminación con confirmación explícita.
- Detección de archivos idénticos ya almacenados para evitar copias duplicadas.
- Navegación desde el menú principal hacia la sección Documentos.
- Persistencia del repositorio y de sus asociaciones entre sesiones de uso.

### Fuera de alcance

- OCR y cualquier procesamiento de imagen sobre los documentos.
- Extracción de texto o de datos desde los PDFs, y creación de registros a partir de un documento (incluida la pantalla de revisión humana del Principio IX). Se implementarán en la capacidad de extracción.
- Selección de evidencias, preselección por perfil profesional y ordenación de anexos (Principio XI).
- Concatenación de PDFs y generación del CV documentado (Principio X).
- Generación de CV, vista previa y exportación a PDF.
- Edición del contenido interno de los PDFs: los documentos solo se cargan, conservan, visualizan y asocian.
- Formatos distintos de PDF (imágenes, documentos de texto, etc.).
- Versionado de documentos: cada documento conserva una única versión del original; para sustituir un archivo se elimina el documento y se carga uno nuevo.
- Descarga o exportación del archivo original fuera de la aplicación.
- Carga masiva por carpeta o importación desde otras fuentes.
- Compartir documentos, multiusuario y sincronización con servicios externos.

## Clarifications

### Session 2026-10-15

- Q: ¿Qué entidades del Perfil Maestro debe permitir asociar documentos este módulo en el MVP? → A: Cursos/certificaciones, educación y experiencia laboral pueden llevar documentos asociados; proyectos queda excluido hasta una ampliación explícita de alcance.
- Q: Cuando el usuario carga un archivo con contenido idéntico a uno ya almacenado, ¿qué debe hacer el sistema? → A: Reutilizar automáticamente el original existente y crear la asociación indicada; el sistema informa la reutilización y nunca almacena una segunda copia.
- Q: ¿En qué momento debe el sistema verificar que los archivos conservados siguen íntegros y corresponden al original cargado? → A: Al visualizar o acceder a cada documento, más una verificación completa del repositorio bajo demanda desde la sección Documentos.
- Q: ¿Cómo deben usarse los términos "documento" y "evidencia" a lo largo de la especificación y de la interfaz? → A: "Documento" es la unidad almacenada del repositorio; "evidencia" es el papel que asume un documento al asociarse a una entidad y el término de la futura selección de evidencias.
- Q: ¿La carga debe permitir seleccionar varios archivos PDF a la vez o un archivo por operación? → A: Un archivo por operación; la selección múltiple y la carga masiva quedan para una ampliación futura.
- Q: ¿Se implementa detección de PDFs protegidos con contraseña? → A: No; sin parsing adicional ni dependencias nuevas. Si una comprobación simple ya disponible lo permite, se muestra una advertencia informativa; el visor nativo puede solicitar la contraseña o informar que no puede abrir el archivo.
- Q: ¿Se mantiene el criterio de rendimiento del repositorio (300 documentos en menos de 1 segundo)? → A: No; es una aplicación local monousuario y ese benchmark no forma parte del MVP. Sin pruebas de carga ni tareas asociadas.

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Cargar, conservar y visualizar documentos originales (Priority: P1)

Como usuario, quiero cargar mis PDFs originales en un repositorio y poder visualizarlos, para disponer de mis documentos originales sin riesgo de perderlos o alterarlos.

**Why this priority**: Sin un original conservado íntegramente no hay evidencia que asociar ni CV documentado posible. Es la base de toda la capacidad y de los Principios VII y X de la Constitución.

**Independent Test**: Cargar un PDF válido, visualizarlo, reiniciar la aplicación y comprobar que el documento sigue disponible, íntegro y visualizable.

**Acceptance Scenarios**:

1. **Dado** un repositorio vacío, **Cuando** el usuario carga un archivo PDF válido, **Entonces** el documento aparece en el repositorio con su nombre original, tamaño y fecha de carga, disponible para visualizar.
2. **Dado** un documento cargado, **Cuando** el usuario lo visualiza, **Entonces** ve el contenido original con todas sus páginas dentro de la aplicación y el archivo no sufre ninguna modificación.
3. **Dado** un documento cargado, **Cuando** el usuario cierra y vuelve a abrir la aplicación, **Entonces** el documento sigue disponible e idéntico al original.
4. **Dado** un archivo que no es un PDF o que está dañado, **Cuando** el usuario intenta cargarlo, **Entonces** el sistema lo rechaza con un mensaje claro y el archivo no ingresa al repositorio.
5. **Dado** un archivo que supera el límite de tamaño permitido, **Cuando** el usuario intenta cargarlo, **Entonces** el sistema lo rechaza indicando el límite.
6. **Dado** un archivo idéntico a uno ya almacenado, **Cuando** el usuario lo carga, **Entonces** el sistema informa que ya existe, reutiliza automáticamente el original y no almacena una copia adicional.
7. **Dado** que una carga se interrumpe, **Cuando** el usuario vuelve al repositorio, **Entonces** no encuentra documentos incompletos ni archivos parciales.

---

### User Story 2 - Asociar documentos a cursos y certificaciones (Priority: P1)

Como usuario, quiero asociar mis PDFs originales a mis cursos y certificaciones, para que cada credencial quede respaldada por su evidencia y preparada para el futuro CV documentado.

**Why this priority**: La asociación documento–certificación es el vínculo que hará posible anexar los certificados originales al CV (Principios X y XI). Es el caso de uso principal del módulo.

**Independent Test**: Cargar un PDF, asociarlo a una certificación existente, verificarlo desde la certificación y desde el repositorio, desasociarlo y volver a asociarlo.

**Acceptance Scenarios**:

1. **Dado** un curso o certificación existente, **Cuando** el usuario adjunta un PDF desde su detalle, **Entonces** el documento queda asociado y es visible tanto desde la certificación como desde el repositorio.
2. **Dada** una certificación con varios documentos asociados, **Cuando** el usuario la consulta, **Entonces** ve todos sus documentos y puede visualizar cada uno.
3. **Dado** un documento asociado a una certificación, **Cuando** el usuario lo desasocia, **Entonces** el documento queda sin asociación, permanece en el repositorio y el archivo original se conserva intacto.
4. **Dado** un documento asociado a una certificación, **Cuando** el usuario lo reasocia a otra, **Entonces** ambas vistas (documento y entidades) quedan actualizadas.
5. **Dada** una certificación con documentos asociados y otras certificaciones sin relación, **Cuando** el usuario elimina la certificación y confirma tras la advertencia, **Entonces** los documentos permanecen en el repositorio como no asociados y ninguna otra certificación resulta afectada.
6. **Dado** un documento asociado, **Cuando** el usuario decide eliminarlo y confirma, **Entonces** la asociación desaparece, el documento se elimina y la certificación permanece intacta.

---

### User Story 3 - Asociar documentos a educación y experiencia (Priority: P2)

Como usuario, quiero asociar documentos a mis registros de educación y a mis experiencias laborales, para respaldarlos con sus documentos originales.

**Why this priority**: La Constitución prevé evidencia documental asociada a la educación y el usuario determinó incluir también la experiencia. Usa el mismo mecanismo que las certificaciones y puede incorporarse después sin bloquear el uso inicial.

**Independent Test**: Cargar un PDF, asociarlo a un registro de educación y a una experiencia, verificarlo desde ambas vistas, desasociarlo y eliminarlo tras confirmación.

**Acceptance Scenarios**:

1. **Dado** un registro de educación existente, **Cuando** el usuario adjunta un PDF, **Entonces** el documento queda asociado y es visible desde la educación y desde el repositorio.
2. **Dada** una experiencia laboral existente, **Cuando** el usuario adjunta un PDF, **Entonces** el documento queda asociado y es visible desde la experiencia y desde el repositorio.
3. **Dado** un documento asociado a una entrada de educación o a una experiencia, **Cuando** el usuario lo desasocia, **Entonces** el documento queda sin asociación y el archivo original se conserva intacto.
4. **Dada** una entrada de educación o una experiencia con documentos asociados, **Cuando** el usuario la elimina y confirma, **Entonces** los documentos permanecen en el repositorio como no asociados.

---

### User Story 4 - Gestionar el repositorio documental (Priority: P2)

Como usuario, quiero consultar, buscar y mantener mis documentos (título, descripción, asociación y eliminación), para mantener el repositorio ordenado y localizar cada evidencia rápidamente.

**Why this priority**: Un repositorio que crece necesita visibilidad de su estado (asociado o pendiente) y control de metadatos, pero el módulo ya aporta valor con las historias anteriores.

**Independent Test**: Con varios documentos asociados y sin asociar, listar, filtrar, buscar, editar metadatos, verificar integridad y eliminar uno con confirmación.

**Acceptance Scenarios**:

1. **Dado** un repositorio con documentos asociados y sin asociar, **Cuando** el usuario lo consulta, **Entonces** cada documento muestra su título o nombre original, fecha de carga, tamaño y su estado de asociación con las entidades correspondientes.
2. **Dado** un repositorio con documentos, **Cuando** el usuario filtra por estado o por entidad, o busca por título o nombre de archivo, **Entonces** obtiene únicamente los documentos que cumplen el criterio.
3. **Dado** un documento cargado, **Cuando** el usuario edita su título o descripción, **Entonces** los cambios se reflejan en el listado y en las entidades asociadas sin alterar el archivo original.
4. **Dado** un documento, **Cuando** el usuario lo elimina, **Entonces** el sistema exige confirmación explícita, indicando las entidades asociadas si las hubiera, y solo tras confirmar se elimina el archivo y sus asociaciones sin afectar a las entidades.
5. **Dado** un documento cuyo archivo fue eliminado o alterado fuera de la aplicación, **Cuando** el usuario intenta visualizarlo o ejecuta la verificación bajo demanda, **Entonces** el sistema lo marca como no disponible, informa el motivo y no regenera ni sustituye nada.
6. **Dado** un repositorio con documentos, **Cuando** el usuario ejecuta la verificación de integridad bajo demanda, **Entonces** el sistema revisa todos los archivos e informa el resultado, señalando los documentos no disponibles.
7. **Dado** un repositorio sin documentos, **Cuando** el usuario accede a la sección, **Entonces** ve un estado vacío con orientación sobre cómo cargar el primer documento.

---

### Edge Cases

- **Archivo con extensión falsa** (renombrado como `.pdf` sin serlo): se rechaza al validar el contenido, no solo la extensión.
- **PDF dañado o ilegible**: se rechaza con un mensaje claro y no ingresa al repositorio.
- **PDF protegido con contraseña**: se conserva sin alteración y sin detección avanzada. Si una comprobación simple ya disponible lo permite, se muestra una advertencia informativa; el visor nativo puede solicitar la contraseña o informar que no puede abrir el archivo.
- **Archivo idéntico ya almacenado**: se detecta por su contenido; el sistema informa y reutiliza automáticamente el original existente, sin almacenar una copia adicional y sin requerir decisión del usuario.
- **Archivos con el mismo nombre pero contenido distinto**: se admiten y se distinguen entre sí sin ambigüedad.
- **Carga interrumpida o fallida**: no quedan documentos incompletos ni archivos parciales; el usuario puede reintentar.
- **Documento sin asociación durante mucho tiempo**: es un estado válido; aparece como pendiente y no bloquea ninguna operación.
- **Eliminar la entidad (certificación, educación o experiencia) estando asociada**: los documentos no se eliminan; quedan sin asociar. La advertencia se muestra antes de confirmar.
- **Eliminar un documento asociado a varias entidades**: la confirmación indica todas las entidades afectadas; al confirmar, solo desaparecen las asociaciones y el documento, nunca las entidades.
- **Archivo desaparecido o alterado externamente**: se detecta al visualizarlo o en la verificación bajo demanda; el documento se marca como no disponible y el sistema no lo regenera, reemplaza ni oculta el problema.
- **Nombres de archivo con acentos, espacios o caracteres especiales**: se conservan y se muestran correctamente.
- **Muchos documentos**: el listado, los filtros y la búsqueda siguen siendo utilizables; el MVP no define un volumen objetivo ni un requisito de rendimiento.
- **Documento asociado a una entidad eliminada y luego reasociado**: la reasociación es posible en cualquier momento y no crea copias del archivo.

## Requirements _(mandatory)_

### Functional Requirements

**Carga y conservación**

- **FR-001**: El sistema DEBE permitir cargar un archivo PDF por operación al repositorio documental local e informar el resultado de cada carga (éxito o motivo del rechazo).
- **FR-002**: El sistema DEBE validar, antes de conservar, que el archivo sea un PDF legible y que su tamaño no exceda el límite permitido; DEBE rechazar con un mensaje claro los archivos que no sean PDF, los dañados o ilegibles y los que superen el límite, sin incorporarlos al repositorio.
- **FR-003**: El sistema DEBE conservar cada archivo original sin alteración: nunca lo modifica, recomprime, reconvierte ni regenera, y ninguna operación del sistema cambia su contenido.
- **FR-004**: El sistema DEBE verificar que el archivo conservado corresponde a la copia cargada al visualizar o acceder a un documento, y DEBE ofrecer además una verificación completa del repositorio bajo demanda desde la sección Documentos; si detecta que falta o fue alterado externamente, DEBE marcarlo como no disponible e informarlo al usuario, sin regenerarlo ni sustituirlo silenciosamente.
- **FR-005**: El sistema DEBE registrar como metadatos de cada documento: nombre original del archivo, tamaño, fecha de carga y, opcionalmente, título y descripción editables; el nombre original se conserva como referencia aunque se edite el título.
- **FR-006**: Si una carga falla o se interrumpe, el sistema NO DEBE dejar documentos incompletos ni archivos parciales en el repositorio.
- **FR-007**: El sistema DEBE detectar cuando el mismo archivo (contenido idéntico) ya está almacenado y reutilizar automáticamente el original existente, informando la reutilización y sin almacenar una segunda copia en ningún caso.
- **FR-008**: El sistema DEBE conservar todos los documentos y sus asociaciones entre sesiones de uso, sin pérdida de datos.

**Asociación con entidades**

- **FR-009**: El sistema DEBE permitir asociar cualquier documento a un curso o certificación existente, en cualquier momento tras la carga.
- **FR-010**: El sistema DEBE permitir asociar cualquier documento a un registro de educación o a una experiencia laboral existente.
- **FR-011**: El sistema DEBE permitir que un documento esté asociado a una o más entidades permitidas, o a ninguna; el estado de asociación DEBE ser visible tanto desde el documento como desde la entidad asociada.
- **FR-012**: El sistema DEBE permitir desasociar y reasociar documentos en cualquier momento, conservando siempre el archivo original.
- **FR-013**: El sistema DEBE permitir cargar documentos directamente desde el detalle de un curso, certificación, registro de educación o experiencia, creando el documento y la asociación en un mismo flujo.
- **FR-014**: Cuando el usuario elimine un curso, certificación, registro de educación o experiencia con documentos asociados, el sistema DEBE advertirlo antes de confirmar y, tras la eliminación, conservar los documentos en el repositorio como no asociados; en ningún caso elimina evidencias como consecuencia de esa acción.
- **FR-015**: El sistema NO DEBE permitir asociar documentos a entidades distintas de cursos/certificaciones, educación y experiencia laboral; proyectos queda excluido del MVP.
- **FR-016**: La asociación o desasociación de un documento NUNCA DEBE alterar el archivo original ni el contenido de la entidad.

**Visualización**

- **FR-017**: El sistema DEBE permitir visualizar el contenido de cada documento conservado dentro de la aplicación, con todas sus páginas, sin modificar el archivo y sin requerir aplicaciones externas.
- **FR-018**: La visualización DEBE estar disponible tanto desde el repositorio como desde la entidad asociada.
- **FR-019**: El sistema DEBE conservar sin alteración los PDFs protegidos con contraseña y NO DEBE implementar detección avanzada ni parsing adicional de archivos protegidos. Si el visor nativo o una comprobación simple ya disponible permite detectar que el documento requiere contraseña, el sistema PUEDE mostrar una advertencia informativa; en caso contrario, es aceptable que el visor nativo solicite la contraseña o informe que no puede abrir el archivo. Con documentos dañados o no disponibles, el sistema DEBE informar el motivo en lugar de mostrar contenido incorrecto.

**Gestión del repositorio**

- **FR-020**: El sistema DEBE mostrar un listado de todos los documentos con título o nombre original, fecha de carga, tamaño y estado de asociación (entidades asociadas o sin asociar).
- **FR-021**: El sistema DEBE permitir filtrar el listado por estado de asociación y por entidad asociada, y buscarlo por título o nombre de archivo.
- **FR-022**: El sistema DEBE permitir editar el título y la descripción de un documento sin afectar el archivo original.
- **FR-023**: El sistema DEBE solicitar confirmación explícita antes de eliminar un documento, indicando las entidades asociadas si las hubiera; la eliminación es definitiva (sin papelera en el MVP), elimina el archivo y sus asociaciones, y nunca afecta a las entidades.
- **FR-024**: El sistema DEBE mostrar un estado vacío con orientación cuando el repositorio no tenga documentos.
- **FR-025**: El sistema DEBE ofrecer navegación desde el menú principal hacia la sección Documentos, siguiendo la estructura del administrador.

**Transversales**

- **FR-026**: El sistema DEBE mantener los archivos y sus metadatos en almacenamiento local de la aplicación y NO DEBE enviar documentos ni información derivada a servicios externos como parte de esta capacidad.
- **FR-027**: El sistema NO DEBE aplicar OCR, extracción de texto ni interpretación de contenido sobre los documentos en esta capacidad.
- **FR-028**: El sistema NO DEBE permitir editar el contenido interno de los PDFs ni utilizarlos como fuente editable de datos del perfil; los documentos son evidencia conservada, no fuente de verdad.
- **FR-029**: El repositorio y sus asociaciones DEBEN dejar preparada la base para el futuro CV documentado (selección y concatenación de PDFs originales, Principios X y XI), sin implementar en esta capacidad la selección, el orden ni la concatenación de evidencias.
- **FR-030**: El sistema DEBE almacenar y mostrar correctamente nombres de archivo, títulos y descripciones con acentos y caracteres especiales.
- **FR-031**: El sistema DEBE distinguir cada documento de forma inequívoca, aunque existan archivos con el mismo nombre.

### Key Entities _(include if feature involves data)_

- **Documento**: PDF original conservado sin alteración, con metadatos (nombre original del archivo, tamaño, fecha de carga, título y descripción opcionales) y estado de disponibilidad. Puede existir sin asociación; cuando se asocia a una entidad permitida, el documento actúa como evidencia documental de esa entidad. Si el mismo archivo ya está almacenado, no se crea una copia adicional y se reutiliza el original.
- **Asociación documento–entidad**: vínculo explícito entre un documento y una entidad permitida (curso/certificación, educación o experiencia laboral). No contiene copias del archivo ni de los datos de la entidad; puede crearse, cambiarse y eliminarse sin afectar a ninguno de los dos extremos.
- **Curso / Certificación (existente, SPEC-001)**: extiende su modelo con uno o más documentos asociados, correspondiente al "Archivo PDF original" previsto en el Principio V.
- **Educación (existente, SPEC-001)**: extiende su modelo con uno o más documentos asociados que actúan como evidencia documental, correspondiente a la "Evidencia documental asociada" prevista en el Principio V.
- **Experiencia laboral (existente, SPEC-001)**: admite uno o más documentos asociados por decisión explícita registrada en esta clarificación; la Constitución no detalla evidencia documental para experiencia, pero no la prohíbe.
- **Proyectos (existente, SPEC-001)**: no admite documentos asociados en el MVP; incorporarlos requeriría una ampliación explícita de alcance.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El 100% de los documentos cargados permanece idéntico al original tras visualizarlo, asociarlo, desasociarlo, editar sus metadatos y reiniciar la aplicación; la verificación de integridad nunca falla por causas del sistema.
- **SC-002**: Cargar un PDF y asociarlo a un curso o certificación toma menos de 1 minuto para un usuario nuevo.
- **SC-003**: El 100% de los intentos de cargar archivos no PDF, dañados o sobredimensionados es rechazado con un mensaje que explica el motivo, y ninguno ingresa al repositorio.
- **SC-004**: El 100% de las asociaciones es visible desde el documento y desde la entidad, sin discrepancias entre ambas vistas.
- **SC-005**: Tras eliminar un curso, certificación, registro de educación o experiencia, el 100% de sus documentos permanece disponible en el repositorio como no asociado.
- **SC-006**: El 100% de las eliminaciones de documentos exige confirmación explícita; ninguna eliminación ocurre en un solo paso.
- **SC-007**: El 100% de los documentos es visualizable dentro de la aplicación sin modificar el archivo.
- **SC-008**: Al cargar repetidamente un archivo con contenido idéntico, el sistema almacena una sola copia y reutiliza el original en el 100% de los casos.
- **SC-009**: El 100% de las cargas interrumpidas no deja documentos incompletos ni archivos parciales.
- **SC-010**: El 100% de los archivos faltantes o alterados externamente se detecta al visualizar el documento o en la verificación bajo demanda, informándose y marcándose como no disponible.

## Pruebas Requeridas

- Conservación íntegra: comparar el archivo conservado con el cargado tras visualizar, asociar, desasociar, editar metadatos y reiniciar la aplicación.
- Validación de cargas: archivo no PDF, PDF renombrado con extensión falsa, PDF dañado, archivo sobre el límite de tamaño y carga interrumpida.
- Detección de archivos idénticos y reutilización del original sin copia adicional.
- Asociación, desasociación y reasociación documento ↔ certificación, documento ↔ educación y documento ↔ experiencia, con visibilidad en ambas direcciones.
- Eliminación de curso, certificación, educación o experiencia con documentos asociados: los documentos permanecen y quedan sin asociar.
- Eliminación de documento: caso confirmado y caso cancelado; la confirmación indica las entidades asociadas; las entidades permanecen intactas.
- Integridad: archivo faltante o alterado en el almacenamiento se marca como no disponible y se informa, al visualizarlo y en la verificación bajo demanda, sin regeneración ni sustitución.
- Verificación completa del repositorio bajo demanda: informa documentos correctos y no disponibles.
- Visualización: documento de muchas páginas, PDF protegido con contraseña, nombres con acentos y caracteres especiales.
- Filtros y búsqueda del listado por estado de asociación y por entidad.
- Persistencia del repositorio completo y de sus asociaciones tras reiniciar la aplicación.

## Assumptions

- Aplicación web local monousuario con almacenamiento local, consistente con la Constitución (Principios XII y XXIII); los documentos no se envían a servicios externos.
- Las entidades permitidas en el MVP son cursos/certificaciones y educación (Principios V, VII y X) y, por decisión explícita registrada en la clarificación del 2026-10-15, también la experiencia laboral. Proyectos no admite documentos en el MVP; incorporarlo requiere una ampliación explícita de alcance.
- Solo se admiten archivos PDF. El límite inicial de tamaño por archivo es de 20 MB, con valor ajustable; los archivos que lo superen se rechazan con un mensaje claro.
- Cada documento conserva una única versión del original: no hay versionado ni historial; sustituir un archivo implica eliminar el documento (con confirmación) y cargar uno nuevo.
- Un documento puede asociarse a una o más entidades permitidas o a ninguna; los documentos sin asociar son válidos y permanecen visibles como pendientes.
- La reutilización de archivos idénticos es automática: el repositorio conserva una única copia por contenido y cada carga idéntica crea únicamente la asociación sobre el original existente.
- La eliminación es definitiva y siempre requiere confirmación; no se incluye papelera, en consistencia con SPEC-001.
- El nombre original del archivo se conserva como referencia del documento; el título opcional permite describirlo mejor y será útil para la futura selección de evidencias.
- Los PDFs protegidos con contraseña se conservan sin alteración y sin detección avanzada; la visualización queda a cargo del visor nativo (puede solicitar la contraseña o informar que no puede abrirlo). No se agregan dependencias ni parsing por esta razón.
- La extracción de información (texto nativo primero, OCR solo como respaldo) y la pantalla de revisión humana pertenecen a una capacidad futura (Principios VIII y IX) y no se implementan en esta capacidad.
- La selección de evidencias, su orden y la concatenación del CV documentado pertenecen a una capacidad futura (Principios X y XI); esta capacidad solo conserva los originales y sus asociaciones.
- Terminología canónica: "documento" designa la unidad almacenada en el repositorio; "evidencia" designa el papel que asume un documento al asociarse a una entidad y es el término empleado por la futura capacidad de selección de evidencias.
- La descarga o exportación de archivos fuera de la aplicación no forma parte del MVP.
- La carga es de un archivo por operación; la selección múltiple de archivos y la carga masiva quedan fuera del MVP.
- La interfaz está en español; los nombres de archivo, títulos y descripciones admiten texto con acentos y caracteres especiales.
- Las entidades del Perfil Maestro (cursos, certificaciones, educación y experiencia) ya existen según SPEC-001; esta capacidad no redefine sus campos ni sus reglas, solo agrega la relación documental.
