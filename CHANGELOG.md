# Changelog

Todas las versiones notables de CV Automatic with IA. El proyecto usa versionado semántico (SemVer) a partir de esta entrega. El hito funcional previo (SPEC-001 a SPEC-006 y mejoras posteriores) se considera la base 1.0.0; no tuvo tag ni changelog.

## [1.2.0] - 2026-11-21

### Added

- Búsqueda local en el listado combinado de Cursos y Certificaciones por coincidencia parcial en nombre o institución y en Habilidades por nombre (US-012).

### Documentation

- Aceptación funcional y visual de US-012; se conserva el flujo de publicación sin tag.

## [1.1.0] - 2026-11-21

### Added

- Importación asistida de cursos y certificaciones desde PDF (SPEC-009): extracción de texto nativo, respaldo OCR local con Tesseract, consentimiento explícito, propuesta de Luna 6, precarga de campos editables, revisión humana y asociación del PDF original.
- OCR local selectivo para PDF escaneados o mixtos, con origen por página (`Nativo`, `OCR`, `Mixto`), reintento explícito e ingreso manual.
- Sugerencias sin respaldo verificado cuando una cita o página de la propuesta no coincide con el texto enviado, conservando el valor para revisión sin bloquear el resto de la propuesta.
- Guardado atómico del curso/certificación, habilidades aceptadas y documento original; reutilización de originales idénticos íntegros y protección frente a confirmaciones reenviadas.
- Cierre documental de US-010, US-011 y SPEC-009, con la aceptación funcional y visual del responsable (2026-11-21).

### Changed

- Alta de certificaciones: la importación opcional se integra sin bloquear el formulario manual ni alterar sus validaciones.
- Pantalla de importación: los botones «Ver PDF original», «Quitar PDF» y la acción principal se alinean con el campo de archivo; el texto de ayuda y el nombre del archivo quedan debajo.
- La etiqueta «Preparar texto» pasa a «Analizar PDF», conservando la preparación del texto y el consentimiento previos al envío a Luna 6.

### Security

- El PDF, las imágenes y las páginas renderizadas no se envían a servicios externos; el OCR se ejecuta localmente.
- El texto se minimiza antes del análisis y solo se envía tras consentimiento explícito.
- Los temporales de OCR y el estado del análisis se eliminan; no se persisten prompts, citas ni historial del análisis.

### Documentation

- Actualización de `README.md`, `docs/roadmap.md`, SPEC-009 y ambas historias con el cierre, la aceptación y la versión 1.1.0.
- El repositorio no tenía tags de versión; los cierres anteriores se registraron sin tag. Esta entrega conserva ese flujo y no crea un tag.
