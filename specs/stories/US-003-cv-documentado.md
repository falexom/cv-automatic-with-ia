# US-003 — Generación y descarga del CV documentado

**Estado:** IMPLEMENTADA — ACEPTADA
**Aprobación humana:** Confirmada el 2026-10-27 (contrato funcional, siete criterios y decisiones de concatenación, orden y ausencia de selección).
**Aceptación humana:** Confirmada el 27/10/2026 (funcional y visual; descarga real verificada).

## Historia de usuario

Como persona que prepara un CV, quiero descargar un único PDF con el CV del perfil activo seguido de las evidencias que seleccioné, para enviar un expediente completo y verificable.

## Problema y objetivo

US-002 permite revisar y seleccionar evidencias, pero la selección todavía no produce ningún archivo. Falta un flujo que genere, bajo demanda, un único PDF compuesto por el CV normal del perfil y los anexos originales seleccionados, con orden estable, integridad verificada y errores controlados, sin reconstruir ni modificar los originales.

## Alcance

- Añadir la acción **Descargar CV documentado** dentro de la sección **Evidencias del CV** del Generador.
- Generar el PDF normal del perfil con la composición y la exportación existentes, y concatenarle los PDFs originales seleccionados.
- Utilizar qpdf como motor de concatenación y verificar su disponibilidad antes de generar.
- Validar la elegibilidad, unicidad, integridad SHA-256, disponibilidad, protección y estructura de los documentos seleccionados.
- Ordenar los anexos por educación, certificaciones (incluidos diplomados y cursos) y experiencia; dentro de cada grupo, por fecha descendente, nombre ascendente e ID.
- Entregar un único archivo descargable, generado temporalmente, sin persistir la selección, el PDF final ni historial.
- Conservar los estados actuales del Generador y la descarga normal del CV.
- Informar los errores de selección, integridad, protección o concatenación sin entregar archivos parciales.

## Criterios de aceptación

1. **Dado** un perfil activo en estado `ready`, con al menos una evidencia elegible seleccionada, **cuando** el usuario solicita **Descargar CV documentado**, **entonces** se descarga un único PDF llamado `cv-{perfil}-documentado.pdf` (o `cv-documentado.pdf` si no hay base) que contiene primero las páginas del CV del perfil y después exactamente las evidencias seleccionadas, sin incluir documentos no seleccionados. La respuesta es `application/pdf` como adjunto y `no-store`; la ruta solo acepta `GET`.
2. **Dadas** evidencias seleccionadas de educación, certificaciones y experiencia, **cuando** se genera el PDF, **entonces** el orden es educación → certificaciones (incluidos diplomados y cursos) → experiencia. Dentro de cada grupo se ordena por la fecha más reciente del registro asociado de forma descendente, luego por nombre visible ascendente y luego por ID. Un documento asociado a varios registros aparece una sola vez, en el grupo de mayor prioridad, usando la fecha más reciente de sus registros de ese grupo; si no tiene fecha, va después de los fechados. El orden es idéntico al repetir la solicitud.
3. **Dados** los documentos seleccionados, **cuando** se genera el PDF, **entonces** se verifica que cada archivo esté disponible, que su SHA-256 coincida con el checksum registrado y que su estructura sea válida antes de concatenarlo. Los originales, la base de datos, las asociaciones y los estados de disponibilidad no se modifican; no se guarda la selección, el PDF final ni historial.
4. **Dado** un documento seleccionado que no existe, fue alterado, está cifrado o protegido con contraseña, o no puede concatenarse, **cuando** se intenta generar, **entonces** no se descarga ningún PDF ni archivo parcial, se informa el documento y el motivo, y no se modifican originales ni metadatos.
5. **Dado** que no hay evidencias seleccionadas, **cuando** se consulta la sección, **entonces** la acción **Descargar CV documentado** está deshabilitada y se muestra un mensaje; una solicitud directa sin selección responde con error y no genera PDF. Si no hay evidencias elegibles, se conserva el estado vacío y no se ofrece la acción.
6. **Dados** los estados `no_active_profiles`, `selection_required`, `unavailable`, `error`, o un perfil desactivado o cambiado entre la vista y la solicitud, **cuando** se intenta generar, **entonces** no se ofrece ni se entrega el CV documentado y se conservan los estados actuales del Generador. Al cambiar de perfil se recalculan evidencias y selección según US-002, y la descarga usa únicamente el perfil y la selección vigentes.
7. **Dado** un PDF final generado, **cuando** se valida antes de entregarlo, **entonces** su estructura es válida, su cantidad de páginas es igual a la suma de las páginas del CV y de cada anexo, y el archivo abre correctamente. Si la validación o la concatenación falla, no se entrega el archivo ni una versión parcial, no quedan temporales recuperables y los métodos distintos de `GET` responden `405`.

## Decisiones aprobadas

- **Motor:** qpdf, con verificación de disponibilidad. Si no está disponible o falla, no se genera el CV documentado.
- **Selección:** solo se genera con al menos un anexo seleccionado; no se ofrece una descarga sin anexos.
- **Orden:** educación → certificaciones (incluidos diplomados y cursos) → experiencia; dentro de cada grupo, fecha descendente, nombre ascendente e ID como desempate.
- **Integridad:** se validan elegibilidad, unicidad, SHA-256, disponibilidad física, protección y estructura antes de concatenar.
- **Conservación:** los PDFs originales no se modifican; el PDF final se genera temporalmente y no se persiste.
- **Alcance:** no se agregan portadas, índices, OCR ni nuevas categorías.

## Exclusiones

- Portada, índice, separadores, etiquetas o numeración de anexos.
- Reordenamiento manual o drag-and-drop.
- OCR, extracción, edición, reconstrucción o modificación de los PDFs originales.
- Garantizar firmas digitales, formularios, marcadores o adjuntos internos de los anexos; se conserva el contenido y la apariencia de las páginas.
- Persistir la selección, el PDF generado, historial, versiones o URLs recuperables.
- Modificar US-002, la exportación normal del CV, los perfiles, los documentos o sus asociaciones.
- Múltiples plantillas, servicios externos, colas, procesamiento masivo o generación por IA.
- Nuevas categorías de contenido o cambios de modelo y migraciones.

## Dependencias

- US-002 — Selección de evidencias del perfil activo.
- SPEC-002 — Documentos y Evidencias.
- SPEC-005 — Exportación PDF.
- SPEC-006 — CV Documentado (roadmap).
- qpdf disponible en el entorno de ejecución, conforme a la decisión aprobada.
