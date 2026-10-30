# US-006 — Rediseño visual del CV en dos columnas

**Tipo:** MEJORA VISUAL

**Estado:** APROBADA_PARA_DESARROLLO — aprobación humana del 30/10/2026

**Relación contractual:** aclaración aprobada de SPEC-005 C3, FR-004, FR-016 y SC-003.

## Historia de usuario

Como usuario de CV Automático, quiero que la plantilla única del CV presente mi trayectoria en dos columnas compartidas por la previsualización y el PDF, para destacar experiencia y proyectos con una composición profesional, legible y completa.

## Problema y objetivo

La plantilla actual organiza todas las secciones en una sola columna y les da un peso visual parecido. Se propone una composición editorial 25/75 que destaque el resumen, la experiencia y los proyectos, reutilizando la presentación compartida por la vista previa y el PDF.

## Alcance

- Actualizar únicamente la presentación visual de la plantilla compartida de `CvPreview` en la vista previa y el PDF.
- Mantener aproximadamente 25 % para la barra izquierda y 75 % para la columna principal, calculados sobre el ancho imprimible restante después del separador.
- Ubicar a la izquierda la fotografía opcional, el contacto, la educación y las skills/tecnologías.
- Ubicar a la derecha el nombre, el título profesional, el resumen, la experiencia profesional, los proyectos y los cursos/certificaciones completos.
- Mantener Instrument Sans y los colores `#182B49`, `#242B35`, `#667085` y `#F4F6F8`.
- Conservar la disposición de dos columnas en todas las páginas. No repetir artificialmente el contenido lateral ni añadir contenido de relleno; se acepta una barra izquierda vacía cuando su contenido se haya agotado.
- Reutilizar React, SSR, Browsershot y la composición de datos vigentes.
- Mantener el comportamiento ya aprobado de fotografía de US-004: misma imagen y encuadre cuando existe; sin espacio reservado cuando no existe.

## Exclusiones

- Nuevas plantillas, variantes, selector de diseño, editor visual o personalización por perfil.
- Cambios en la selección, relevancia, orden de los elementos dentro de cada sección, datos de origen, payload, modelos, persistencia o reglas de negocio.
- Carga, almacenamiento, recorte o gestión de fotografías.
- Cambios en rutas, disponibilidad o comportamiento de exportación.
- Cambios en la composición ni reconstrucción de los anexos del CV documentado.
- Renderer paralelo, paginador propio, segmentación manual de páginas, dependencias nuevas o servicios externos. Si fueran necesarios para cumplir los criterios, detenerse y presentar la decisión de alcance al responsable.
- ATS scoring, garantías de extracción idéntica en todos los ATS, generación de contenido y cambios ajenos a la presentación visual.

## Criterios de aceptación

1. **Una presentación compartida:** la previsualización y el PDF usan el mismo `CvPreview` y la misma composición de datos. En ambos se reconocen las mismas secciones y elementos; el orden textual es nombre/título, contacto, resumen, experiencia, proyectos, certificaciones, educación y skills/tecnologías. La diferencia se limita al contexto de pantalla frente a impresión.
2. **Dos columnas:** en A4 vertical y con los márgenes vigentes, el ancho imprimible se divide aproximadamente 25/75 descontando el separador. La columna derecha mantiene la predominancia visual.
3. **Distribución de contenido:** la barra izquierda muestra, en orden, fotografía opcional, datos de contacto, educación y skills/tecnologías. La columna principal muestra, en orden, nombre, título profesional, resumen, experiencia, proyectos y cursos/certificaciones completos.
4. **Jerarquía y estilo:** se usa Instrument Sans y la paleta aprobada. Nombre, título, secciones, contenido y metadatos se distinguen por tamaño, peso, color y espaciado, con prioridad visual para experiencia y proyectos. El texto principal no baja de 10.5 pt y los metadatos no bajan de 9.5 pt. No hay barras de progreso, gráficos de habilidades, iconos sin etiqueta ni adornos recargados.
5. **Fotografía:** si existe, se presenta arriba a la izquierda con el encuadre y tamaño vigentes; si no existe, el contacto y las secciones se acomodan sin hueco reservado ni cambio de flujo.
6. **Información y enlaces:** ningún campo o elemento seleccionado se pierde, trunca o duplica. El texto del PDF es seleccionable, las secciones tienen encabezados reconocibles y los enlaces existentes conservan su etiqueta y destino seguro cuando el formato lo permita. La extracción conserva el orden textual del criterio 1 y no intercala columnas de forma que impida identificar las secciones.
7. **Paginación con barra lateral más extensa:** con educación/skills que excedan la altura de la columna principal, todo el contenido lateral continúa en las páginas necesarias, una sola vez y dentro de sus límites. La columna principal puede terminar antes y quedar vacía en la continuación; no se repite su contenido ni se crea una página totalmente vacía o una página extra sin contenido.
8. **Paginación con columna principal más extensa:** con experiencias/proyectos que excedan la altura de la barra, la derecha continúa en las páginas siguientes conservando su ancho y sin cortes, solapamientos, pérdidas ni duplicaciones. La izquierda puede quedar vacía tras agotarse su contenido y no se repite artificialmente.
9. **Paginación y estabilidad:** los títulos de sección no quedan huérfanos. Una entrada corta se conserva junta cuando cabe; una entrada larga puede continuar entre páginas sin perder texto ni enlaces. No hay desbordamientos, cortes visuales problemáticos, páginas totalmente en blanco ni páginas adicionales originadas por saltos forzados. Repetir con los mismos datos mantiene texto y cantidad de páginas.
10. **Continuidad preview/PDF:** la fotografía, proporciones, distribución, jerarquía, colores y separadores son reconocibles en ambas superficies. En pantallas estrechas, la vista previa conserva el diseño de dos columnas mediante escala o desplazamiento, sin convertirlo en una plantilla distinta.
11. **CV documentado:** el bloque inicial del CV documentado hereda la nueva plantilla al reutilizar el renderer; los anexos PDF originales conservan contenido, orden e integridad.
12. **Aprobación visual:** la aceptación final requiere revisión del responsable de la vista previa y del PDF, con un CV de una página y los dos casos multipágina de las secciones 7 y 8. Las comprobaciones técnicas no sustituyen esa aprobación.

## Verificación focalizada prevista

- Un caso multipágina con barra lateral más extensa que el contenido principal.
- Un caso multipágina con contenido principal más extenso que la barra lateral.
- Comparación de contenido extraído, número de páginas, enlaces y PDF visual con la vista previa para ambos casos.
- Revisión del caso con fotografía y sin fotografía, incluyendo preview, PDF normal y bloque inicial del CV documentado.

No se crea `tasks.md`: esta lista acotada define las verificaciones previas a Build y la aceptación humana.

## Dependencias y trazabilidad

- SPEC-004 — Generador de CV: fuente de datos derivados, selección y vista previa de solo lectura.
- SPEC-005 — Exportación PDF: renderer compartido; la aclaración de C3/FR-004/FR-016/SC-003 queda pendiente de aprobación final.
- US-001 — Mejora de la vista previa: se conserva como registro histórico; esta historia reemplaza únicamente la exclusión visual que impedía cambiar la plantilla compartida.
- US-004 — Fotografía profesional en el CV: reutiliza sus criterios aprobados de fotografía y ausencia de espacio vacío.
- US-003 — CV documentado: solo se valida que su CV inicial herede el nuevo renderer y que los anexos permanezcan intactos.

La dirección visual y el contrato funcional fueron aprobados expresamente por el responsable el 30/10/2026. La historia queda en `APROBADA_PARA_DESARROLLO` y la enmienda contractual de SPEC-005 se registra como aprobada. La aceptación final de la implementación sigue pendiente de revisión humana de la previsualización y del PDF.
