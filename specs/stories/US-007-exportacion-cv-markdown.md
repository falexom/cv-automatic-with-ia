# US-007 — Exportación de CV en formato Markdown

**Identificador:** US-007
**Estado:** IMPLEMENTADA_ACEPTADA
**Aprobación humana:** concedida el 2026-11-03
**Aprobación funcional y visual humana:** 2026-11-03 — exportación Markdown validada satisfactoriamente.
**SPEC relacionada:** [SPEC-005 — Exportación PDF](../005-exportacion-pdf/spec.md), como mejora incremental posterior a su cierre.

## Historia de usuario

Como usuario de CV Automático, quiero exportar el contenido del perfil de CV seleccionado en un archivo Markdown para proporcionarlo a herramientas de inteligencia artificial sin extraer texto desde un PDF.

## Alcance funcional

- Añadir al generador un botón **Exportar Markdown** para el perfil activo seleccionado.
- Descargar un archivo `.md` en UTF-8 con la información del perfil: datos personales y contacto, título y resumen, experiencia, educación, cursos y certificaciones, habilidades y tecnologías, y proyectos. Omitir información y secciones vacías.
- Reutilizar los registros seleccionados, su relevancia y su orden en el generador. Se pueden incluir otros campos originales disponibles en esos mismos registros, aunque el PDF no los muestre, como la descripción de educación.
- Presentar el contenido con encabezados y listas Markdown legibles. Conservar textos, fechas y enlaces originales sin inventar, completar, resumir ni modificar información profesional. Tratar los textos y enlaces de forma segura.

## Criterios de aceptación

1. **CA-01 — Descarga:** el usuario puede exportar el perfil de CV seleccionado mediante el botón **Exportar Markdown**, descargando un archivo `.md` válido en UTF-8.
2. **CA-02 — Selección y orden:** el Markdown reutiliza los registros seleccionados, la relevancia y el orden del generador de CV, sin incorporar información excluida del perfil.
3. **CA-03 — Campos disponibles:** el archivo incluye la información profesional disponible de los registros seleccionados y puede incluir campos que no se muestran en el PDF, como la descripción de educación.
4. **CA-04 — Fidelidad y seguridad:** el contenido utiliza Markdown semántico y legible, conserva textos, fechas y enlaces válidos sin inventar ni modificar información profesional, y trata los contenidos y enlaces de forma segura.
5. **CA-05 — Aislamiento y errores:** la exportación no modifica datos ni afecta el funcionamiento actual del PDF normal o documentado. Los errores se manejan sin generar archivos parciales.

## Exclusiones y restricciones

- No integrar IA ni enviar información a servicios externos.
- No exportar todo el Perfil Maestro ni generar exportaciones JSON.
- No persistir archivos generados ni mantener versiones o historial.
- No modificar el PDF normal, el CV documentado ni sus comportamientos actuales.
- No añadir modelos, migraciones ni cambios de arquitectura.
- La historia queda aprobada para desarrollo; la aprobación de contenido no autoriza commits de implementación, integración ni publicación.
