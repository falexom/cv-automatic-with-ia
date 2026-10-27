# US-002 — Selección de evidencias del perfil activo

**Estado:** APROBADA_PARA_DESARROLLO

## Historia de usuario

Como persona que prepara un CV, quiero revisar y elegir las evidencias asociadas a los registros incluidos en el perfil activo, para decidir cuáles dejar seleccionadas para el futuro CV documentado.

## Problema y objetivo

El repositorio ya conserva documentos y sus asociaciones, y cada perfil de CV define qué registros incluye. Falta una vista que relacione ambos para que el usuario pueda revisar y elegir evidencias con su origen visible.

## Alcance

- Mostrar los documentos asociados a registros incluidos en el perfil activo: experiencia, educación y cursos/certificaciones.
- Identificar cada documento por su título o, si no tiene, por el nombre original del archivo; mostrar sus registros asociados incluidos en el perfil, su disponibilidad registrada y la fecha de última verificación, cuando exista.
- Mostrar una sola entrada por documento, aunque esté asociado a varios registros incluidos, conservando la trazabilidad de todos ellos.
- Preseleccionar todos los documentos elegibles, definidos como los asociados a por lo menos un registro incluido en el perfil activo.
- Permitir incluir o excluir documentos y actualizar inmediatamente la lista o el conteo de seleccionados.
- Recalcular documentos elegibles y selección inicial al cambiar de perfil, sin conservar evidencias del perfil anterior.
- Mostrar un estado vacío cuando no haya documentos elegibles y conservar los estados actuales del Generador cuando no haya perfil seleccionado, esté no disponible o falle la consulta.
- Mostrar la disponibilidad según el último estado conocido por el repositorio, sin ejecutar una nueva verificación de integridad.
- Mantener la selección de forma temporal durante la revisión; no guardar preferencias en el perfil.

## Criterios de aceptación

1. **Dado** un perfil activo seleccionado y el Generador en estado `ready`, **cuando** se consulta la selección de evidencias, **entonces** se muestran los documentos asociados a registros incluidos en ese perfil. Se consideran experiencia, educación y cursos/certificaciones, que son los tipos de registro con asociaciones documentales existentes.
2. **Dado** un documento elegible, **cuando** se muestra en la selección, **entonces** se identifica por su título o, si no tiene, por el nombre original del archivo; se identifican sus registros asociados incluidos en el perfil y se muestra su disponibilidad registrada y la fecha de última verificación, cuando exista.
3. **Dado** un documento asociado a varios registros incluidos en el perfil, **cuando** se muestra en la selección, **entonces** aparece una sola vez e identifica todos esos registros asociados.
4. **Dada** la selección de un perfil activo, **cuando** se abre la selección de evidencias, **entonces** todos los documentos elegibles —asociados a por lo menos un registro incluido— aparecen seleccionados; al incluir o excluir uno, la lista o el conteo de seleccionados se actualiza de inmediato.
5. **Dado** que el usuario cambia el perfil seleccionado, **cuando** se actualiza la selección de evidencias, **entonces** se recalculan los documentos elegibles y la selección inicial, sin arrastrar evidencias del perfil anterior.
6. **Dado** que no hay documentos elegibles, **cuando** se consulta la selección, **entonces** se muestra un estado vacío. Si el perfil no está seleccionado, no está disponible o la consulta falla, se conservan los estados actuales del Generador y no se ofrece selección de evidencias.

## Exclusiones

- Concatenar o descargar PDFs; corresponde a US-003.
- Extraer información de los documentos o modificar los archivos originales.
- Guardar una preferencia de evidencias en el perfil.

## Dependencias

- SPEC-002 — Documentos y Evidencias.
- SPEC-003 — Perfiles de CV.
- SPEC-004 — Generador de CV.
- US-003 — Generación y descarga del CV documentado consumirá la selección.
