# US-005 — Tolerar advertencias recuperables de qpdf en el CV documentado

**Tipo:** FIX

**Estado:** APROBADA_PARA_DESARROLLO

**Aprobación humana:** Confirmada por el responsable el 30/10/2026; seis criterios de aceptación aprobados sin modificaciones de alcance.

**Implementación:** No iniciada.

## Historia de usuario

Como persona que genera un CV documentado, quiero que una advertencia recuperable de qpdf no bloquee la descarga cuando el PDF resultante es válido, para poder obtener el expediente completo sin perder ni alterar los anexos originales.

## Problema y objetivo

La generación del CV documentado trata cualquier salida distinta de cero de qpdf como error de concatenación. qpdf utiliza el código 3 para indicar que la operación terminó con advertencias recuperables y que el archivo de salida puede ser válido. El caso se reprodujo con el certificado de trabajo de Telefónica: qpdf genera el PDF concatenado, pero el sistema devuelve «No se pudo concatenar el CV con las evidencias seleccionadas».

El objetivo es distinguir una advertencia recuperable de un error real, manteniendo las validaciones de integridad, cantidad de páginas y estructura antes de entregar el archivo.

## Alcance

- Ajustar únicamente el manejo del resultado de qpdf durante la concatenación del CV documentado.
- Aceptar el código 3 solo cuando exista el archivo de salida, la cantidad de páginas coincida con la esperada y la validación estructural final no reporte errores.
- Mantener como error los códigos 2 y cualquier otro resultado anómalo, incluida la ausencia de salida, una cantidad de páginas incorrecta o una validación final fallida.
- Registrar la advertencia recuperable sin exponer rutas internas ni detalles técnicos al usuario.
- Conservar intactos los PDFs originales y no reparar, reconstruir ni reemplazar sus archivos.
- No conservar archivos parciales ni temporales recuperables cuando la generación falle.
- Añadir pruebas focalizadas para advertencias recuperables y errores reales.

## Criterios de aceptación

1. **Advertencia recuperable:** Dado un anexo cuyo proceso de concatenación termina con código 3 pero genera un PDF con la cantidad de páginas esperada, cuando el usuario solicita el CV documentado, entonces la descarga se completa con el CV seguido de las evidencias seleccionadas y no se muestra un error de concatenación.

2. **Validación obligatoria:** Dado un resultado con código 3, cuando el archivo de salida no existe, está vacío, no puede leerse, tiene una cantidad de páginas distinta de la esperada o no supera la validación estructural final, entonces no se entrega ningún PDF, se informa un error controlado y no queda un archivo parcial ni un temporal recuperable.

3. **Errores reales:** Dado que qpdf termina con código 2 o con cualquier otro fallo no clasificado como advertencia recuperable, cuando se intenta generar el CV documentado, entonces se conserva el comportamiento de error controlado y no se entrega ningún archivo.

4. **Integridad del resultado:** Dado un PDF final entregado, cuando se valida antes de la descarga, entonces contiene todas las páginas del CV y de cada anexo seleccionado, su estructura es válida y la cantidad de páginas coincide con la suma esperada.

5. **Anexos originales:** Dados los documentos seleccionados y sus checksums, cuando se completa la generación con advertencias recuperables, entonces los archivos originales, sus checksums, asociaciones y metadatos permanecen sin cambios.

6. **Sin regresión:** Dados anexos sin advertencias, cuando se genera el CV documentado, entonces el flujo, el orden, la selección, la descarga normal del CV y la exportación PDF existentes continúan funcionando sin cambios.

## Restricciones

- El cambio se limita a `app/Support/CvDocumentedPdf.php` y a pruebas focalizadas.
- No se modifica el binario, la configuración ni la versión de qpdf.
- No se usa `--no-warn` para ocultar advertencias ni se ignoran errores reales.
- No se reparan, reconstruyen ni reescriben los PDFs originales.
- No se agregan dependencias, migraciones, cambios de interfaz ni nuevas opciones de usuario.
- Los mensajes de error conservan el formato actual y no exponen rutas internas.
- La limpieza de temporales se mantiene incluso cuando la generación falla.

## Exclusiones

- Reparación o normalización de PDFs originales.
- Cambios en la carga, validación o almacenamiento de documentos.
- Nuevos formatos, OCR, extracción de contenido o reconstrucción de certificados.
- Cambios en el orden, la selección o la vista previa del CV documentado.
- Manejo general de todos los posibles tipos de advertencias de qpdf; solo se cubre el resultado recuperable necesario para este defecto.

## Tareas técnicas

1. Añadir pruebas focalizadas con un qpdf simulado que devuelva código 3 y genere una salida válida, y con código 3 sin salida, con páginas incorrectas o con validación final fallida.
2. Ajustar `CvDocumentedPdf` para clasificar el código 3 como advertencia recuperable solo después de comprobar la existencia del archivo, la cantidad de páginas y la validación estructural.
3. Mantener el código 2 y los fallos desconocidos como errores, sin entregar archivos parciales y conservando la limpieza de temporales.
4. Reutilizar y ampliar las pruebas existentes de concatenación, integridad y conservación de originales.
5. Ejecutar verificaciones focalizadas de pruebas, formato, análisis estático y compilación.

No se crea `tasks.md`: esta descomposición proporcional queda dentro de la historia.

## Dependencias y referencias

- US-003 — Generación y descarga del CV documentado.
- SPEC-006 — CV Documentado.
- Defecto conocido registrado en `docs/roadmap.md` (SPEC-006/US-003).
- `app/Support/CvDocumentedPdf.php` y `tests/Feature/CvDocumentedPdfTest.php`.
- Evidencia reproducida con el certificado de trabajo de Telefónica y qpdf.
