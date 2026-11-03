# US-006 — Rediseño visual del CV en dos columnas

**Tipo:** MEJORA VISUAL

**Estado:** PENDIENTE_ACEPTACION — implementación full bleed integrada el 30/10/2026; pendiente revisión humana de la previsualización y del PDF.

**Relación contractual:** aclaración aprobada de SPEC-005 C3, FR-004, FR-007, FR-016 y SC-003.

## Historia de usuario

Como usuario de CV Automático, quiero que la plantilla única del CV presente mi trayectoria en dos columnas con un diseño editorial full bleed, para destacar experiencia y proyectos con una composición profesional, legible y completa en pantalla y en el PDF.

## Problema y objetivo

La plantilla anterior organiza todas las secciones en una sola columna y reduce el aprovechamiento de la superficie A4. Se adopta una composición editorial 30/70 con márgenes exteriores de 0 mm: la cabecera navy y la barra lateral llegan a los bordes de la página, mientras el contenido conserva padding interno seguro. La misma composición se reutiliza en la vista previa y el PDF. El objetivo es un documento digital full bleed, no una hoja con un diseño reducido dentro de márgenes blancos.

## Alcance

- Actualizar únicamente la presentación visual de la plantilla compartida de `CvPreview` en la vista previa y el PDF.
- Mantener 30 % para la barra lateral izquierda y 70 % para la columna principal, calculados sobre los 210 mm del A4.
- Establecer `@page { size: A4; margin: 0; }` y márgenes exteriores de 0 mm en los cuatro lados.
- Extender la cabecera navy hasta los bordes superior, izquierdo y derecho de la primera página. La cabecera principal se muestra solo en la primera página.
- Extender el fondo gris de la barra lateral hasta el borde izquierdo e inferior de todas las páginas, incluso cuando ya no contenga información.
- Mantener la columna principal sobre fondo blanco.
- Conservar padding interno suficiente para textos, fotografía, enlaces, chips y numeración; no reducir la tipografía para conseguir el full bleed.
- Ubicar a la izquierda la fotografía opcional, el contacto, la educación y las skills/tecnologías.
- Ubicar a la derecha el nombre, el título profesional, el resumen, la experiencia profesional, los proyectos y los cursos/certificaciones completos.
- Presentar la educación únicamente con los campos estructurados: programa/carrera, institución, periodo y grado académico. No consumir `education_entries[].description` en el CV. La descripción libre permanece íntegra en el Perfil Maestro y en la pantalla de edición; no se elimina ni se modifica.
- Mantener unidos el encabezado de cada proyecto y su primer bloque de contenido mediante reglas CSS reutilizables. Los párrafos posteriores pueden continuar en la página siguiente.
- Mantener Instrument Sans y los colores `#182B49`, `#242B35`, `#667085` y `#F4F6F8`, los chips de habilidades y la jerarquía tipográfica aprobada.
- Conservar la disposición de dos columnas en todas las páginas. No repetir artificialmente el contenido lateral ni añadir contenido de relleno; se acepta una barra izquierda vacía cuando su contenido se haya agotado.
- Reservar áreas seguras de página mediante filas repetidas de tabla: 14 mm superiores y 12 mm inferiores. La numeración se coloca en la zona inferior segura.
- Reutilizar React, SSR, Browsershot y la composición de datos vigentes.
- Mantener el comportamiento ya aprobado de fotografía de US-004: misma imagen y encuadre cuando existe; sin espacio reservado cuando no existe. En la cabecera full bleed el marco aprobado es de 36 mm.

## Exclusiones

- Nuevas plantillas, variantes, selector de diseño, editor visual o personalización por perfil.
- Cambios en la selección, relevancia, orden de los elementos dentro de cada sección, datos de origen, payload, modelos, persistencia o reglas de negocio.
- Carga, almacenamiento, recorte o gestión de fotografías.
- Cambios en rutas, disponibilidad o comportamiento de exportación.
- Cambios en la composición ni reconstrucción de los anexos del CV documentado.
- Heurísticas por palabras clave, expresiones regulares o eliminación automática de fragmentos para la educación. La omisión de `description` es una regla de presentación explícita, no una limpieza de datos.
- Renderer paralelo, paginador propio, segmentación manual de páginas, alturas fijas basadas en la información actual, páginas estáticas, dependencias nuevas o servicios externos.
- ATS scoring, garantías de extracción idéntica en todos los ATS, generación de contenido y cambios ajenos a la presentación visual.

## Criterios de aceptación

1. **Una presentación compartida:** la previsualización y el PDF usan el mismo `CvPreview` y la misma composición de datos. En ambos se reconocen las mismas secciones y elementos. El orden visual de lectura es nombre/título, resumen, experiencia, proyectos, certificaciones, contacto, educación y habilidades/tecnologías. La extracción textual conserva los encabezados y no impide identificar las secciones; en páginas de continuación puede intercalar fragmentos de las dos columnas. La diferencia se limita al contexto de pantalla frente a impresión.
2. **Full bleed 30/70:** el PDF es A4 vertical de 210 × 297 mm con márgenes exteriores de 0 mm. La barra lateral ocupa el 30 % y la columna principal el 70 %. La cabecera navy llega a los bordes superior, izquierdo y derecho de la primera página; la barra lateral llega al borde izquierdo e inferior de todas las páginas. No hay franjas blancas exteriores entre el diseño y los límites de la página.
3. **Distribución de contenido:** la barra izquierda muestra, en orden, fotografía opcional, datos de contacto, educación y skills/tecnologías. La columna principal muestra, en orden, nombre, título profesional, resumen, experiencia, proyectos y cursos/certificaciones completos.
4. **Jerarquía y estilo:** se usa Instrument Sans y la paleta aprobada. Nombre, título, secciones, contenido y metadatos se distinguen por tamaño, peso, color y espaciado, con prioridad visual para experiencia y proyectos. La escala aprobada en la maqueta full bleed es: nombre 24,5 pt; títulos de sección 12 pt; cuerpo 9,2–10,6 pt; metadatos 6,2–8,5 pt. No se reduce la tipografía para compensar el full bleed. No hay barras de progreso, gráficos de habilidades, iconos sin etiqueta ni adornos recargados.
5. **Fotografía:** si existe, se presenta arriba a la izquierda con el encuadre vigente y un marco de 36 mm en la cabecera full bleed; si no existe, la cabecera elimina la columna de foto y el contacto y las secciones se acomodan sin hueco reservado ni cambio de flujo.
6. **Información y enlaces:** ningún campo o elemento seleccionado se pierde, trunca o duplica, con la excepción de presentación aprobada de `education_entries[].description`, que no se muestra en el CV y permanece íntegra en el Perfil Maestro. El texto del PDF es seleccionable, las secciones tienen encabezados reconocibles y los enlaces existentes conservan su etiqueta y destino seguro cuando el formato lo permita. La extracción conserva los encabezados y el orden visual de la primera página; en páginas de continuación puede intercalar fragmentos de las dos columnas sin impedir identificar las secciones.
7. **Paginación con barra lateral más extensa:** con educación/skills que excedan la altura de la columna principal, todo el contenido lateral continúa en las páginas necesarias, una sola vez y dentro de sus límites. El fondo lateral se mantiene continuo en todas las páginas. La columna principal puede terminar antes y quedar vacía en la continuación; no se repite su contenido ni se crea una página totalmente vacía o una página extra sin contenido.
8. **Paginación con columna principal más extensa:** con experiencias/proyectos que excedan la altura de la barra, la derecha continúa en las páginas siguientes conservando su ancho y sin cortes, solapamientos, pérdidas ni duplicaciones. La izquierda puede quedar vacía tras agotarse su contenido y no se repite artificialmente.
9. **Paginación y estabilidad:** los títulos de sección no quedan huérfanos. Una entrada corta se conserva junta cuando cabe; una entrada larga puede continuar entre páginas sin perder texto ni enlaces. El encabezado de un proyecto y su primer bloque de contenido se mantienen juntos; los párrafos posteriores pueden continuar. No hay desbordamientos, cortes visuales problemáticos, páginas totalmente en blanco ni páginas adicionales originadas por saltos forzados. La numeración queda dentro de la zona segura inferior. Repetir con los mismos datos mantiene texto y cantidad de páginas.
10. **Continuidad preview/PDF:** la fotografía, proporciones, distribución, jerarquía, colores, cabecera y barra lateral son reconocibles en ambas superficies. En pantallas estrechas, la vista previa conserva el diseño de dos columnas mediante escala o desplazamiento, sin convertirlo en una plantilla distinta.
11. **CV documentado:** el bloque inicial del CV documentado hereda la nueva plantilla al reutilizar el renderer; los anexos PDF originales conservan contenido, orden e integridad.
12. **Aprobación visual:** la aceptación final requiere revisión del responsable de la vista previa y del PDF, con un CV de una página y los dos casos multipágina de las secciones 7 y 8. Las comprobaciones técnicas no sustituyen esa aprobación.

## Verificación focalizada prevista

- Un caso multipágina con barra lateral más extensa que el contenido principal.
- Un caso multipágina con contenido principal más extenso que la barra lateral.
- Comparación de contenido extraído, número de páginas, enlaces y PDF visual con la vista previa para ambos casos.
- Revisión del caso con fotografía y sin fotografía, incluyendo preview, PDF normal y bloque inicial del CV documentado.
- Comprobación de full bleed: cabecera a sangre solo en la primera página, barra lateral continua, ausencia de franjas exteriores y numeración dentro de la zona segura.
- Comprobación de educación: programa, institución, periodo y grado visibles; `description` ausente del CV y conservada en el Perfil Maestro.
- Comprobación de proyectos: encabezado y primer bloque juntos; párrafos posteriores continuables.
- Comprobación de extracción: encabezados y orden visual de la primera página; en páginas de continuación puede intercalar fragmentos de las dos columnas. Los marcadores `Stack`/`Estado`/`Repositorio` permanecen en el texto libre porque no hay campos estructurados.

No se crea `tasks.md`: esta lista acotada define las verificaciones previas a Build y la aceptación humana.

## Dependencias y trazabilidad

- SPEC-004 — Generador de CV: fuente de datos derivados, selección y vista previa de solo lectura.
- SPEC-005 — Exportación PDF: renderer compartido; la enmienda de C3/FR-004/FR-007/FR-016/SC-003 queda aprobada para el diseño full bleed 30/70 con márgenes exteriores de 0 mm.
- US-001 — Mejora de la vista previa: se conserva como registro histórico; esta historia reemplaza únicamente la exclusión visual que impedía cambiar la plantilla compartida.
- US-004 — Fotografía profesional en el CV: reutiliza sus criterios aprobados de fotografía y ausencia de espacio vacío; el marco de 36 mm corresponde a la cabecera full bleed aprobada en esta historia.
- US-003 — CV documentado: solo se valida que su CV inicial herede el nuevo renderer y que los anexos permanezcan intactos.

La dirección visual y el contrato funcional fueron aprobados expresamente por el responsable el 30/10/2026. La historia quedó en `APROBADA_PARA_DESARROLLO` para implementar y ahora queda en `PENDIENTE_ACEPTACION`: la enmienda contractual de SPEC-005 está aprobada, pero la aceptación final de la implementación full bleed sigue pendiente de revisión humana de la previsualización y del PDF.
