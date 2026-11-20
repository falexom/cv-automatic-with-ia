# US-011 — Importación de certificados escaneados o mixtos con OCR local

- **Identificador:** US-011
- **Estado:** APROBADA_PARA_DESARROLLO
- **Aprobación humana:** 2026-11-20; aprobación expresa del responsable para extender el recorrido de US-010 con OCR local cuando sea necesario.
- **SPEC:** [SPEC-009](../009-importacion-asistida-certificados/spec.md).
- **Dependencia obligatoria:** [US-010 — Importación de certificados PDF con texto](US-010-importacion-certificados-pdf-texto.md), implementada y aceptada antes de iniciar esta extensión.
- **Rama documental:** `009-importacion-asistida-certificados`, base `eae90399073e3895a24a1a8c81e8417f33ee0822`.
- **Rama de implementación:** propia, a crear desde la base con US-010 aceptada cuando corresponda; esta rama documental no autoriza desarrollar ambas historias juntas.
- **Ejecución:** no iniciada; la aprobación del contrato no autoriza implementación en esta fase ni commit, push o merge.

## Historia de usuario

Como usuario, quiero registrar un certificado escaneado o mixto por el mismo recorrido revisable de los PDF con texto, para aprovechar OCR local solo en las partes que no puedan leerse nativamente y conservar mi original intacto.

## Autoridad del contrato y alcance

Este archivo es la fuente única de la extensión OCR. Hereda por referencia todos los requisitos comunes FR-001–015, campos/validaciones, entidades, criterios CA-01–14 y criterios de éxito de [US-010](US-010-importacion-certificados-pdf-texto.md). Sustituye únicamente la limitación de US-010 FR-003/CA-02/CA-15 que deriva texto insuficiente al ingreso manual sin OCR. No copia ni redefine el contrato común.

Incluye PDF escaneado, mixto o con texto nativo insuficiente, manteniendo un PDF y un registro por operación. No añade proveedor ni servicios externos: las imágenes y derivados OCR se procesan localmente y el texto mínimo resultante entra al recorrido ya existente de consentimiento, propuesta de Luna 6, revisión y confirmación.

## Requisitos específicos

- **OCR-001:** Intentar texto nativo antes de OCR. Texto vacío, ilegible o solo de partes ajenas al certificado justifica respaldo local. Un campo ausente en contenido legible no lo justifica. En PDF mixto, usar texto nativo útil y OCR únicamente donde sea necesario para el contenido relevante, sin duplicar valores ni confundir páginas.
- **OCR-002:** Mantener el PDF fuente inalterado y separado de derivados temporales. Al confirmar se asocia el original, nunca una versión OCR regenerada o recompuesta.
- **OCR-003:** Aplicar a texto OCR las mismas restricciones de datos no confiables, ausencia/ambigüedad, sugerencias, catálogo y validaciones del recorrido común. Identificar el origen OCR durante la revisión; conservar referencia al fragmento y página original cuando se disponga, sin inventar localizaciones.
- **OCR-004:** No enviar imágenes, páginas ni PDF al servicio de IA u OCR externo. Tras OCR local, informar el texto concreto minimizado que se enviará y obtener consentimiento antes del análisis externo; reutilizar Luna 6 y configuración vigente. El consentimiento previo para texto nativo no se extiende silenciosamente a un contenido adicional obtenido después.
- **OCR-005:** Si OCR no está disponible, falla o el resultado sigue siendo insuficiente/dudoso, informar y permitir ingreso manual o reintento explícito, manteniendo la edición manual previa. No completar valores inventados, crear registros ni guardar resultados parciales por el fallo.
- **OCR-006:** Cancelación, abandono, sustitución, fallo y confirmación eliminan imágenes/textos/archivos OCR temporales conforme a FR-015. Invalidar cualquier resultado tardío; no conservar borradores ni modificar/eliminar el archivo fuente del usuario o documentos preexistentes.

## Criterios de aceptación

1. **CA-OCR-01 — Escaneado:** con PDF de imagen sin texto útil, se intenta extracción nativa y después OCR local. El texto útil llega a revisión con los campos del contrato común y Luna 6; solo tras confirmación se crea el registro y se asocia el original inalterado.
2. **CA-OCR-02 — Mixto:** con PDF multipágina que tiene membrete legible pero contenido relevante escaneado, se reconocen solo las partes necesarias y se combinan con texto nativo útil, sin duplicación. El soporte disponible remite al número de página del original y distingue extracción OCR de texto nativo durante la revisión.
3. **CA-OCR-03 — Evitar OCR innecesario:** con texto nativo relevante suficiente, incluso en PDF mixto, no se ejecuta OCR. Un código, duración o fecha ausente permanece vacío y no dispara OCR para buscar información inexistente.
4. **CA-OCR-04 — Lectura incierta:** errores, contradicciones o caracteres dudosos del OCR no se presentan como valores seguros; los campos no resolubles quedan vacíos y se permite corrección. Categoría y habilidades siguen las reglas de sugerencias, catálogo y aceptación de US-010.
5. **CA-OCR-05 — Privacidad:** OCR ocurre localmente, sin enviar páginas/imágenes/PDF ni incorporar proveedor. Antes de enviar texto OCR se muestra el contenido concreto mínimo y se requiere consentimiento; rechazarlo no envía datos ni crea registros.
6. **CA-OCR-06 — Fallo y cancelación:** indisponibilidad de herramienta/idioma necesario, PDF protegido/ilegible, fallo OCR, cancelación, abandono o respuesta tardía cumplen CA-11/12/14 de US-010, incluyendo limpieza de derivados, datos manuales previos, alternativa manual y cero registros parciales. La limpieza no sustituye la conservación del original.
7. **CA-OCR-07 — Recorrido compartido:** los certificados escaneados/mixtos cumplen CA-01 y CA-03–14 de US-010, incluidos confirmación explícita, validaciones, duplicados, reutilización, guardado íntegro y reintentos. No hay segunda persistencia o reglas alternativas para OCR.
8. **CA-OCR-08 — Cierre:** aceptar esta extensión junto con US-010 permite proponer el cierre de SPEC-009; la ejecución técnica de pruebas o aceptar solo una historia no declara aceptada la SPEC completa.

## Criterios de éxito

- En todos los casos previstos con texto suficiente se evita OCR; en escaneados/mixtos insuficientes se intenta OCR local únicamente donde hace falta o se informa el impedimento.
- El 100% de los registros confirmados conserva el PDF original idéntico y las reglas comunes de éxito; en todos los casos sin confirmación hay cero registros/documentos/asociaciones nuevos persistidos y no quedan derivados OCR locales al finalizar/cancelar.
- Ningún PDF, imagen o página se envía a servicios externos; ningún texto OCR se envía sin aviso y consentimiento del contenido correspondiente.

## Descomposición y verificaciones previstas

Preparación para fase futura; no ejecutada ahora:

1. Comprobar aceptación de US-010, base Git, rama propia y disponibilidad de OCR local/idiomas antes de implementar. Usar las herramientas ya instaladas; si se descubre una necesidad de instalación o cambio fuera del alcance aprobado, documentarla antes de proceder.
2. Extender la detección de texto insuficiente con OCR local selectivo y trazabilidad al original, sin alterar extracción nativa suficiente ni persistencia común.
3. Integrar los derivados con minimización/consentimiento, revisión de incertidumbre y limpieza/cancelación comunes, sin crear otro recorrido de guardado.
4. Prever comprobaciones técnicas focalizadas y cobertura de CA-OCR-01–07: escaneado, mixto, texto suficiente sin OCR, lectura dudosa, privacidad, cancelación tardía, fallo OCR y conservación/reutilización del original. La automatización de IA será controlada, sin envíos reales; no ejecutar suites completas ni ampliar verificaciones sin autorización.
5. Solicitar aceptación funcional/visual al responsable y revisar QA/seguridad según riesgo. Registrar evidencia de ambas historias antes de proponer cierre de SPEC-009; no hacer cierre Git automático.

## Exclusiones y preparación

Hereda todas las exclusiones de US-010 salvo la exclusión específica de OCR, levantada exclusivamente para OCR local descrito aquí. No incluye OCR externo, imágenes como formato de entrada independiente, procesamiento masivo ni guardado automático.

Aprobada para desarrollo, pendiente de US-010 implementada y aceptada y de su instrucción de ejecución. Tesseract y OCRmyPDF se identificaron instalados; no se ha probado procesamiento, idiomas ni limpieza bajo fallos. No existe impedimento documental pendiente para esta preparación.
