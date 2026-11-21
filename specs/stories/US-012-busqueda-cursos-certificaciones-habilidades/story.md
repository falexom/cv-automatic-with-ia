# US-012 — Búsqueda en Cursos, Certificaciones y Habilidades

- **Identificador:** US-012
- **Título:** Búsqueda en Cursos, Certificaciones y Habilidades
- **Tipo:** FUNCIONALIDAD
- **Estado:** IMPLEMENTADA_ACEPTADA — aceptación funcional y visual registrada el 2026-11-21.
- **Aprobación para desarrollo:** autorización explícita del usuario el 2026-11-21.
- **Rama de implementación:** `feat/us-012-busqueda-cursos-certificaciones-habilidades`, creada desde `main`.

## Historia de usuario

Como usuario de CV Automático, quiero filtrar Cursos, Certificaciones y Habilidades mientras escribo para encontrar un registro sin recorrer todo el listado.

## Contexto

Los cursos, certificaciones y habilidades forman parte de los registros del Perfil Profesional Maestro descritos en [SPEC-001 — Perfil Profesional Maestro](../../001-perfil-profesional-maestro/spec.md). Esta historia añade búsqueda local a esos tres listados. El buscador del listado de Documentos, descrito en [SPEC-002 — Documentos y Evidencias](../../002-documentos-evidencias/spec.md), se toma únicamente como referencia visual.

En la interfaz actual, Cursos y Certificaciones aparecen juntos en el listado «Cursos y certificaciones» y comparten ese buscador; Habilidades tiene un listado y buscador propios.

## Alcance

- Incorporar un buscador independiente encima del listado combinado de Cursos y Certificaciones y otro encima del listado de Habilidades.
- Filtrar cada listado por coincidencias parciales mientras se escribe, sin requerir Enter.
- Buscar Cursos y Certificaciones por nombre e institución cuando esté registrada; buscar Habilidades por nombre.
- Aplicar la consulta a todos los registros de su sección, incluidos los que estén en otras páginas si el listado tiene paginación.
- Recuperar el listado completo de la sección al borrar la consulta y mostrar el estado sin coincidencias definido en los criterios de aceptación.

## Exclusiones

- Búsqueda global entre secciones.
- Búsqueda o cambios funcionales en el listado de Documentos; se usa solo como referencia visual.
- Cambios a los datos, operaciones de gestión o relaciones de Cursos, Certificaciones y Habilidades.

## Criterios de aceptación

1. Cursos, Certificaciones y Habilidades tienen cada uno un buscador independiente encima de su listado, con la misma ubicación visual que el buscador de Documentos.
2. El listado se filtra por coincidencias parciales a medida que se escribe, sin pulsar Enter. Cursos y Certificaciones coinciden si la consulta aparece en cualquiera de los campos nombre o institución cuando esté registrada. Habilidades coincide por nombre.
3. Al borrar la consulta, se recupera el listado completo de la sección. Si hay paginación, la búsqueda abarca todos los registros de esa sección, incluidos los de otras páginas.
4. Cuando una consulta no devuelve coincidencias, se muestra «No se encontraron resultados».
5. Escribir en un buscador solo modifica el listado de su sección.

## Aceptación humana y verificaciones

- **Aceptación funcional y visual:** 2026-11-21. El responsable probó la funcionalidad y aprobó el buscador compartido de Cursos y Certificaciones y el buscador independiente de Habilidades.
- **Paginación:** la prueba entre páginas no aplica; ninguno de estos listados tiene paginación y los controladores entregan todos los registros.
- **Verificaciones técnicas:** 3 pruebas focalizadas de interfaz; TypeScript; lint y formato focalizados; compilación frontend; `git diff --check`.
