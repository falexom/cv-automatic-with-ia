# Roadmap — CV Automático

**Proyecto:** CV Automático

**Estado:** SPEC-001 a SPEC-006 completadas e integradas en `main`; cierre formal de v1.0 pendiente de alineación constitucional

**Documento:** Estado del alcance funcional de CV Automático v1.0

**Metodología:** Spec-Driven Development con SpecKit

---

## Propósito

Este documento registra el estado del alcance funcional de CV Automático v1.0 y las decisiones sobre capacidades posteriores.

Este roadmap no sustituye a los archivos formales `spec.md` que serán creados posteriormente mediante `/speckit.specify`.

La fuente normativa global del proyecto sigue siendo:

`.specify/memory/constitution.md`

Cada nuevo Spec deberá:

1. Leer y respetar la Constitución activa.
2. Revisar los Specs anteriores relacionados.
3. Mantener compatibilidad con el modelo ya implementado.
4. Evitar duplicar capacidades existentes.
5. No ampliar alcance sin una decisión explícita.

---

# SPEC-002 — Documentos y Evidencias

**Estado:** Cerrado formalmente (2026-10-21) — implementación terminada; validación manual aprobada (2026-10-15); pruebas automatizadas aprobadas (2026-10-21); integrado en main mediante `1f154bc`; sin tag (no exigido por la Constitución ni el roadmap). El alcance definitivo está en `specs/002-documentos-evidencias/spec.md`.

**Referencia de cierre:** commit de integración `1f154bc` en `main`; no existe tag de versión.

## Objetivo

Crear el módulo de Documentos y Evidencias del proyecto CV Automático para permitir almacenar, asociar y consultar archivos PDF originales vinculados al Perfil Profesional Maestro.

## Alcance preliminar

- Subir archivos PDF desde la interfaz.
- Conservar el archivo original sin reconstruirlo.
- Registrar metadatos básicos del documento.
- Asociación a cursos/certificaciones, educación y experiencia laboral; proyectos excluido.
- Visualización desde la aplicación.
- Mostrar qué registro utiliza cada evidencia.
- Eliminación con confirmación; sustituir un archivo exige eliminar el documento y cargar uno nuevo, sin versionado ni descarga.
- Mantener integridad entre el archivo físico y su registro en base de datos.
- Preparar la base documental necesaria para el futuro CV documentado.

## Fuera de alcance

- OCR.
- Extracción automática de datos.
- Clasificación inteligente.
- Generación de CV.
- Exportación del CV a PDF.
- Concatenación de PDFs.
- Análisis de contenido mediante IA.

## Dependencias

- SPEC-001 — Perfil Profesional Maestro.

## Resultado esperado

El usuario puede subir un certificado PDF, asociarlo a una certificación existente, visualizarlo y conservar el archivo original dentro del sistema.

---

# SPEC-003 — Perfiles de CV

**Estado:** Cerrado formalmente (2026-10-22; roadmap actualizado 2026-10-23) — implementación terminada; aceptación manual del responsable registrada; T050 con cobertura manual parcial documentada en `quickstart.md` (subescenarios no confirmados no aprobados); T048 de formato documental corregido; rama `003-perfiles-cv`; `main` apunta a `4801c72`; sin push. El alcance definitivo está en `specs/003-perfiles-cv/spec.md`.

## Objetivo

Permitir crear y gestionar perfiles de CV derivados del Perfil Profesional Maestro sin duplicar la información profesional.

## Alcance preliminar

- Crear perfiles de CV.
- Editar perfiles existentes.
- Activar o desactivar perfiles.
- Incluir inicialmente:
    - Analista de Sistemas.
    - Programador / Desarrollador.
- Definir título profesional específico por perfil.
- Definir resumen profesional específico por perfil.
- Relacionar habilidades con cada perfil.
- Relacionar experiencias con cada perfil.
- Relacionar educación con cada perfil.
- Relacionar cursos y certificaciones con cada perfil.
- Relacionar proyectos con cada perfil.
- Definir relevancia o prioridad de cada elemento dentro de un perfil.
- Permitir reutilizar un mismo elemento del Perfil Maestro en múltiples perfiles.
- Visualizar qué contenido pertenece o tiene relevancia en cada perfil.
- Mantener una única fuente de verdad para todos los datos profesionales.

## Fuera de alcance

- Generación visual del CV.
- Plantillas.
- Exportación PDF.
- CV documentado.
- Análisis automático de ofertas laborales.
- Relevancia calculada automáticamente por IA.

## Dependencias

- SPEC-001 — Perfil Profesional Maestro.
- Puede consumir información documental creada en SPEC-002, pero no depende funcionalmente de ella para existir.

## Resultado esperado

El usuario puede mantener al menos dos perfiles distintos —Analista y Programador— reutilizando la misma trayectoria profesional y definiendo qué contenido debe priorizar cada uno.

---

# SPEC-004 — Generador de CV

**Estado:** COMPLETADA e integrada en `main` mediante `df45be1`; aceptación funcional y visual registrada. Los escenarios manuales 1–13 de T025 no se ejecutaron individualmente y están documentados en su evidencia; no queda integración pendiente.

## Objetivo

Construir el motor que transforma el Perfil Profesional Maestro y un Perfil de CV seleccionado en una vista de currículum lista para revisar.

## Alcance preliminar

- Seleccionar un perfil de CV.
- Generar contenido a partir del Perfil Profesional Maestro.
- Aplicar prioridades y relaciones definidas en el perfil.
- Mostrar:
    - Datos personales.
    - Título profesional.
    - Resumen.
    - Experiencia relevante.
    - Educación relevante.
    - Certificaciones relevantes.
    - Habilidades.
    - Proyectos relevantes.
- Ordenar contenido de acuerdo con reglas definidas.
- Omitir contenido no seleccionado o no relevante para el perfil.
- Generar una vista previa del CV.
- Permitir cambiar de perfil y regenerar la vista.
- Mantener una sola plantilla profesional para el MVP.
- Garantizar que la vista sea derivada de datos maestros y no editable como documento independiente.

## Fuera de alcance

- Exportación a PDF.
- Concatenación de certificados.
- Múltiples plantillas.
- Editor drag-and-drop.
- Edición directa del CV generado.
- ATS scoring.
- Adaptación automática a una oferta laboral.
- Generación mediante IA de contenido no aprobado por el usuario.

## Dependencias

- SPEC-001 — Perfil Profesional Maestro.
- SPEC-003 — Perfiles de CV.

## Resultado esperado

El usuario selecciona Analista o Programador y obtiene una vista previa coherente del CV, con contenido distinto según el perfil elegido.

---

# SPEC-005 — Exportación PDF

## Objetivo

Permitir exportar el CV generado a un archivo PDF profesional y reproducible.

## Alcance preliminar

- Agregar acción visible de Exportar PDF.
- Convertir la vista generada del CV a PDF.
- Mantener la misma información y jerarquía visual de la vista previa.
- Respetar márgenes, tipografía, saltos de página y orden.
- Generar un nombre de archivo consistente.
- Permitir descargar el PDF.
- Soportar CV de una o más páginas.
- Mantener enlaces funcionales cuando técnicamente corresponda.
- Validar que el PDF pueda abrirse correctamente.

## Fuera de alcance

- Anexar certificados.
- Concatenar PDFs.
- OCR.
- Múltiples diseños de exportación.
- Firma digital.
- Publicación online.
- Envío por correo.

## Dependencias

- SPEC-004 — Generador de CV.

## Resultado esperado

El usuario puede generar y descargar un PDF normal del CV correspondiente al perfil seleccionado.

---

# SPEC-006 — CV Documentado

**Estado:** COMPLETADA (2026-10-27) — ejecutada mediante historias incrementales aprobadas US-002 y US-003; integrada en main `b0b0578`; sin `spec.md` formal.

**Defecto resuelto:** el CV documentado con el certificado de Telefónica fallaba porque qpdf devolvía código 3 por advertencias de estructura al concatenar. US-005 corrigió el manejo de advertencias recuperables y validó la exportación; integrado en main mediante `4fb5397`.

## Objetivo

Generar una versión documentada del currículum que contenga primero el CV y, a continuación, los PDFs originales de las evidencias seleccionadas.

## Alcance preliminar

- Partir del PDF normal del CV.
- Consultar las evidencias disponibles.
- Permitir seleccionar qué documentos anexar.
- Preseleccionar evidencias relacionadas con el perfil cuando sea posible mediante reglas determinísticas.
- Mostrar claramente los anexos seleccionados antes de generar.
- Definir un orden estable de anexos.
- Concatenar:
    - PDF del CV.
    - PDFs originales seleccionados.
- Mantener intactos los PDFs originales.
- Generar un único archivo final.
- Permitir descargar el CV documentado.
- Validar que el archivo final pueda abrirse correctamente.
- Mantener trazabilidad entre anexo y registro profesional asociado.

## Orden preliminar de anexos

1. Educación formal.
2. Diplomados.
3. Certificaciones.
4. Cursos.
5. Otras evidencias.

Dentro de cada grupo, inicialmente:

más reciente → más antiguo.

## Fuera de alcance

- Reconstruir certificados.
- OCR.
- Extraer datos del PDF.
- Editar el contenido interno de los certificados.
- Reordenamiento drag-and-drop.
- Firma digital.
- Portada adicional de expediente, salvo que posteriormente se apruebe.

## Dependencias

- SPEC-002 — Documentos y Evidencias.
- SPEC-005 — Exportación PDF.

## Resultado esperado

El usuario obtiene un único PDF compuesto por el CV seguido de los certificados y demás evidencias originales seleccionadas.

## Cierre (2026-10-27)

SPEC-006 se ejecutó mediante historias incrementales aprobadas, sin `spec.md` formal:

- [US-002 — Selección de evidencias del perfil activo](../specs/stories/US-002-seleccion-evidencias-cv.md)
- [US-003 — Generación y descarga del CV documentado](../specs/stories/US-003-cv-documentado.md)

Alcance entregado:

- Selección y preselección de evidencias del perfil activo.
- Descarga de un único PDF con el CV normal seguido de las evidencias seleccionadas.
- Concatenación de los PDFs originales mediante qpdf, con el orden aprobado: educación → certificaciones (incluidos diplomados y cursos) → experiencia; dentro de cada grupo, fecha descendente, nombre ascendente e ID.
- Validación de elegibilidad, integridad SHA-256, protección, estructura y cantidad de páginas.
- Conservación de los PDFs originales y de la selección temporal, sin persistir el archivo final ni historial.

Aceptación funcional y visual: 27/10/2026. Integración en `main`: `b0b0578`.

---

# SPEC-007 — Importación Inteligente de Certificados

**Estado: CANCELADA (2026-10-29).** Por decisión del responsable, se retira definitivamente del alcance de CV Automático v1.0 y no se implementará. No quedan historias ni funcionalidades pendientes; el backlog H1–H5 queda descartado.

---

# Orden Propuesto de Implementación (referencia histórica)

1. SPEC-002 — Documentos y Evidencias.
2. SPEC-003 — Perfiles de CV.
3. SPEC-004 — Generador de CV.
4. SPEC-005 — Exportación PDF.
5. SPEC-006 — CV Documentado.
6. SPEC-007 — Importación Inteligente de Certificados (cancelada; fuera de v1.0).

---

# Dependencias entre Specs

SPEC-001 — Perfil Profesional Maestro  
├── SPEC-002 — Documentos y Evidencias  
├── SPEC-003 — Perfiles de CV  
│ └── SPEC-004 — Generador de CV  
│ └── SPEC-005 — Exportación PDF  
│ └── SPEC-006 — CV Documentado  
└── SPEC-007 — Importación Inteligente (cancelada; sin dependencia activa)

---

# Regla de Ejecución

Los Specs deberán implementarse uno a uno.

Flujo recomendado por cada Spec:

`/speckit.specify`

→ `/speckit.clarify`

→ `/speckit.plan`

→ `/speckit.tasks`

→ `/speckit.analyze` cuando aporte valor

→ `/speckit.implement`

→ validación

→ commit

→ merge a `main`

No debe iniciarse la implementación del siguiente Spec hasta que el anterior se encuentre suficientemente estable y versionado.

---

# Estado del alcance v1.0

SPEC-001 a SPEC-006 están completadas e integradas en `main`, según el historial y los artefactos verificados. SPEC-007 está cancelada y retirada del alcance. No se declara cerrado formalmente CV Automático v1.0: la Constitución vigente todavía incluye la extracción automática de información de certificados en el MVP (Principio IV), exige revisión humana de esos datos (Principios VIII y IX) y la incorpora al criterio de éxito del MVP (sección IX). La cancelación de SPEC-007 entra en conflicto con esas reglas. Resolverlo requiere una decisión y alineación constitucional explícitas; esta actualización no modifica la Constitución.

### Historia incremental US-004 — Fotografía profesional en el CV

**Estado:** ACEPTADA (30/10/2026); integrada en `main` mediante `6cfc620`.

Añade una fotografía única al Perfil Maestro con encuadre 1:1, almacenamiento local privado e inclusión automática en la vista previa, el PDF normal y el CV documentado. La aceptación funcional y visual fue confirmada por el responsable. El escenario de CV documentado con el certificado de Telefónica quedó corregido y aceptado posteriormente mediante US-005.

### Historia incremental US-005 — Corrección de advertencias qpdf en CV documentado

**Estado:** ACEPTADA (30/10/2026); integrada en `main` mediante `4fb5397`.

Corrige el tratamiento del código 3 de qpdf: acepta advertencias recuperables solo tras verificar la salida, la cantidad de páginas y la estructura, mantiene los errores reales y conserva intactos los anexos originales. La exportación con el certificado de Telefónica fue validada por el responsable.

### Historia incremental US-006 — Rediseño visual del CV en dos columnas

**Estado:** COMPLETADA Y ACEPTADA (2026-11-03) — aceptación funcional y visual del responsable; integrada localmente en `main` mediante `5d9857a`; sin push.

Entrega el rediseño full bleed 30/70 del CV compartido por la vista previa y el PDF: A4 digital sin márgenes exteriores, cabecera navy, fotografía opcional, chips de habilidades, educación compacta, jerarquía editorial de experiencia y proyectos, paginación multipágina y numeración segura. Reutiliza React, SSR y Browsershot y mantiene la compatibilidad con el CV documentado y sus anexos.

Relación: [US-006](../specs/stories/US-006-rediseno-visual-cv-dos-columnas.md) concreta la aclaración visual de [SPEC-005](../specs/005-exportacion-pdf/spec.md) sobre la composición de la plantilla compartida de [SPEC-004](../specs/004-generador-de-cv/spec.md). No se reabre el alcance funcional de SPEC-004/SPEC-005.

### Historia incremental US-007 — Exportación de CV en Markdown

**Estado:** INTEGRADA (2026-11-03).

**Objetivo:** descargar el contenido profesional del perfil de CV activo seleccionado como Markdown UTF-8 legible por personas y herramientas de IA, sin extraer texto desde el PDF.

**Relación:** mejora incremental posterior de [SPEC-005](../specs/005-exportacion-pdf/spec.md); conserva su cierre y criterios históricos. **Dependencias principales:** Perfil Maestro (SPEC-001), perfiles derivados (SPEC-003) y composición del generador (SPEC-004).

**Alcance:** acción de descarga desde el generador, contenido semántico vigente, selección/relevancia/orden existentes, encabezados y listas, fechas y enlaces conservados y nombre predecible `.md`; sin IA, imágenes, anexos, persistencia de CV ni cambios funcionales en PDF. La descripción de educación y los cinco criterios fueron aprobados por el responsable humano el 2026-11-03.

Contrato aprobado: [US-007 — Exportación de CV en formato Markdown](../specs/stories/US-007-exportacion-cv-markdown.md). Implementada, aceptada funcionalmente e integrada localmente en `main` el 2026-11-03.

## Posibles evoluciones posteriores

Las mejoras visuales del CV quedaron cubiertas por US-006. La adaptación inteligente del CV a ofertas laborales continúa como idea para evaluar en una versión posterior. No se inicia ni propone una nueva SPEC.

Las siguientes oportunidades se registran como propuestas de análisis. No son requisitos aprobados, no amplían el MVP y no tienen SPEC ni ID asignado.

### Metadatos estructurados de proyectos — PROPUESTA / PENDIENTE DE ANÁLISIS

**Problema:** `Stack`, `Estado` y `Repositorio` pueden estar incluidos en campos de texto libre de los proyectos; el modelo actual no tiene campos estructurados específicos para todos esos metadatos.

**Objetivo futuro:** evaluar una evolución del modelo de proyectos que permita almacenar y presentar metadatos profesionales de forma estructurada, reutilizable y coherente con los distintos perfiles del CV.

**Consideraciones:** reutilizar habilidades y relaciones existentes cuando corresponda; evitar duplicación; conservar compatibilidad con los proyectos registrados; no eliminar contenido histórico; evaluar la presentación de `Stack`, `Estado` y `Repositorio` en el CV. No se diseñan migraciones ni se define una solución técnica.

**Referencias:** [US-006](../specs/stories/US-006-rediseno-visual-cv-dos-columnas.md) y [SPEC-005](../specs/005-exportacion-pdf/spec.md) registran la limitación actual.

### Optimización de compatibilidad ATS — PROPUESTA / PENDIENTE DE ANÁLISIS

**Problema:** la composición visual de dos columnas conserva texto seleccionable y encabezados identificables, pero algunos sistemas de extracción pueden intercalar contenido de ambas columnas y alterar el orden lógico de lectura.

**Objetivo futuro:** evaluar mejoras de extracción textual y compatibilidad con sistemas ATS sin sacrificar innecesariamente la presentación profesional aprobada.

**Consideraciones:** orden de lectura del PDF; extracción de información profesional; encabezados reconocibles; datos de contacto; compatibilidad con diferentes analizadores ATS; posibilidad de una estrategia alternativa de exportación solo si un análisis posterior demuestra que es necesaria. No se implementa scoring ATS ni se promete compatibilidad universal.

**Referencias:** [US-006](../specs/stories/US-006-rediseno-visual-cv-dos-columnas.md) y [SPEC-005](../specs/005-exportacion-pdf/spec.md) documentan el límite de extracción.

---

# Estado del Roadmap

| Spec | Capacidad | Estado verificado en `main` |
|---|---|---|
| SPEC-001 | Perfil Profesional Maestro | COMPLETADA e integrada (`323eeb1`); aceptación manual y cierre registrados (`bffb1e2`). T063/T064 completadas; SC-001 retirado por decisión del responsable. |
| SPEC-002 | Documentos y Evidencias | COMPLETADA e integrada (`1f154bc`); validación manual y pruebas automatizadas registradas. |
| SPEC-003 | Perfiles de CV | COMPLETADA e integrada; implementación presente (`2c0658f`) y cierre registrado (`63ab15b`). T050 conserva cobertura manual parcial documentada; aceptación del responsable registrada. |
| SPEC-004 | Generador de CV | COMPLETADA e integrada (`df45be1`); aceptación funcional y visual registrada. T025 conserva escenarios manuales no ejecutados individualmente, documentados en su evidencia. |
| SPEC-005 | Exportación PDF | COMPLETADA e integrada (`d4e8a1b`); aceptación funcional y visual registrada, con cobertura manual parcial documentada para T019. |
| SPEC-006 | CV Documentado | COMPLETADA e integrada mediante US-002 y US-003 (`b0b0578`); cierre registrado (`006d3ed`). |
| SPEC-007 | Importación Inteligente de Certificados | CANCELADA; fuera del alcance de v1.0, sin trabajo pendiente. |

---

# Nota de Gobernanza

Este roadmap es deliberadamente preliminar.

Cuando un Spec pase a ejecución, su alcance definitivo será el contenido de su `spec.md` formal.

Si existe una discrepancia entre este roadmap, un Spec formal y la Constitución:

1. Prevalece la Constitución.
2. Luego prevalece el Spec formal activo.
3. El roadmap deberá actualizarse posteriormente para reflejar la decisión aprobada.
