# Feature Specification: Importación asistida de cursos y certificaciones desde PDF

**Feature Branch**: `009-importacion-asistida-certificados`, creada desde `main` en `eae90399073e3895a24a1a8c81e8417f33ee0822`; conserva la preparación documental previa del mismo encargo. Implementación de US-010 (`b693626`) y US-011 (`3e91ee1`) en esta rama por instrucción expresa del responsable; ajustes finales `7e16e22`; integrada en `main` mediante fast-forward.

**Created**: 2026-11-20

**Status**: CERRADA Y ACEPTADA (2026-11-21) — US-010 y US-011 aceptadas funcional y visualmente, incluidos los ajustes finales de la pantalla de importación. Implementación integrada en `main`; versión 1.1.0; sin tag por el flujo histórico del repositorio.

**Identificador**: SPEC-009

**Input**: Formalizar la carga de un PDF al iniciar un curso o certificación, con propuestas de Luna 6 para campos existentes, extracción de texto antes de OCR, revisión humana y conservación del original únicamente al confirmar.

## Problema, objetivo y alcance

El registro actual exige transcribir el certificado y después adjuntar su evidencia. Esta capacidad reduce la transcripción sin convertir las inferencias de IA en hechos profesionales ni guardar información sin revisión.

Incluye una operación sobre un único PDF para crear un único curso o certificación en el Perfil Maestro: cargar, informar y consentir el envío, extraer, proponer, revisar, corregir, cancelar o confirmar y asociar el original. El ingreso manual permanece disponible.

Excluye adaptación de CV a ofertas, creación o modificación de perfiles de CV, cambios de relevancia, importación de educación/experiencia/proyectos, edición asistida de registros existentes, procesamiento masivo, creación de habilidades, nuevos campos profesionales, guardado automático e historial de análisis.

SPEC-007 permanece cancelada históricamente. SPEC-009 registra la nueva decisión del responsable, sin recuperar su backlog descartado ni modificar sus antecedentes. La nueva capacidad desarrolla los Principios IV, VII–IX, XXI y XXIII y la sección IX de la Constitución. Las exclusiones de extracción y envío externo de SPEC-001/SPEC-002 delimitan aquellas capacidades; esta especificación autoriza solo el análisis asistido aquí descrito. No cambia los contratos de sus operaciones manuales ni el de SPEC-008. No se identifica una contradicción que requiera enmienda constitucional. Formalizar esta capacidad no declara cerrado v1.0.

## Contrato funcional y autoridad documental

El responsable aprobó SPEC-009 y su división en dos historias el 2026-11-20. El detalle funcional anteriormente contenido aquí se traslada a las historias sin ampliar el alcance aprobado:

- [US-010 — Importación de certificados PDF con texto](../stories/US-010-importacion-certificados-pdf-texto.md): fuente única de campos, validaciones y reglas comunes, con recorrido completo de texto nativo. Primera implementación prevista.
- [US-011 — Importación de certificados escaneados o mixtos con OCR local](../stories/US-011-importacion-certificados-ocr-local.md): fuente única del respaldo OCR local; hereda el contrato común de US-010 por referencia.

No hay tercera historia ni contrato duplicado en el roadmap/checklist. Las referencias FR-001–015 y SC-001–006 de la formalización inicial se conservan en US-010, con el comportamiento OCR separado en US-011. Cambiar esos contratos requiere la decisión humana correspondiente.

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Importar PDF con texto (Priority: P1)

**Historia aprobada:** [US-010](../stories/US-010-importacion-certificados-pdf-texto.md).

**Why this priority**: Entrega el recorrido completo y seguro sin depender del respaldo OCR.

**Independent Test**: Los criterios CA-01–15 de US-010 definen la revisión y confirmación de un PDF con texto y las cancelaciones/errores previsibles; la aceptación funcional y visual será humana.

**Acceptance Scenarios**: Fuente única en [Criterios de aceptación de US-010](../stories/US-010-importacion-certificados-pdf-texto.md#criterios-de-aceptación).

### User Story 2 - Extender a escaneados y mixtos (Priority: P2)

**Historia aprobada:** [US-011](../stories/US-011-importacion-certificados-ocr-local.md), dependiente de US-010 implementada y aceptada.

**Why this priority**: Extiende el recorrido ya aceptado a documentos que necesitan OCR local.

**Independent Test**: Los criterios CA-OCR-01–08 de US-011 verifican la extensión con documentos escaneados/mixtos y la conservación del recorrido común.

**Acceptance Scenarios**: Fuente única en [Criterios de aceptación de US-011](../stories/US-011-importacion-certificados-ocr-local.md#criterios-de-aceptación).

### Edge Cases

Los casos de ausencia/ambigüedad, límites/protección, catálogo, duplicados, integridad, cancelación, respuestas tardías, inyección y errores pertenecen a CA-02–14 de US-010. La insuficiencia nativa, documentos mixtos, incertidumbre y fallo OCR pertenecen a CA-OCR-01–07 de US-011. No se mantienen versiones paralelas de estos escenarios aquí.

## Requirements _(mandatory)_

### Functional Requirements

El contrato normativo común es [US-010, FR-001–015](../stories/US-010-importacion-certificados-pdf-texto.md#requisitos-funcionales-comunes), incluida su [tabla de campos existentes y validaciones](../stories/US-010-importacion-certificados-pdf-texto.md#campos-existentes-y-validaciones-que-se-conservan). La extensión normativa es [US-011, OCR-001–006](../stories/US-011-importacion-certificados-ocr-local.md#requisitos-específicos). La primera historia no implementa OCR; la segunda sustituye únicamente su límite de texto insuficiente con respaldo local.

### Key Entities _(include if feature involves data)_

Las [entidades del recorrido](../stories/US-010-importacion-certificados-pdf-texto.md#entidades-del-recorrido) se definen una sola vez en US-010; US-011 no añade entidades profesionales ni persistencia de derivados OCR.

## Success Criteria _(mandatory)_

### Measurable Outcomes

Aplican [SC-001–006 de US-010](../stories/US-010-importacion-certificados-pdf-texto.md#criterios-de-éxito-del-recorrido-común) y los [criterios de éxito específicos OCR de US-011](../stories/US-011-importacion-certificados-ocr-local.md#criterios-de-éxito). Ambas historias fueron implementadas y aceptadas por el responsable el 2026-11-21; la SPEC se cierra con la versión 1.1.0. La aceptación funcional/visual fue humana; las comprobaciones técnicas no la sustituyen.

## Assumptions

Los supuestos operativos y límites son los del contrato común de US-010 y la extensión local de US-011. Se mantiene la aplicación local monousuario, el Perfil Maestro como fuente única y el análisis externo opcional con revisión previa al guardado. No se añade infraestructura ni integración alternativa.

## Dependencias y evidencia revisada

- [SPEC-001](../001-perfil-profesional-maestro/spec.md): campos, habilidades, fechas y advertencias existentes.
- [SPEC-002](../002-documentos-evidencias/spec.md): original íntegro, validaciones, reutilización y asociaciones.
- [SPEC-008](../008-adaptacion-cv-ofertas/spec.md): antecedente técnico de integración IA; sin dependencia de su flujo de adaptación ni de evaluaciones. Permanece intacta; SPEC-007 continúa cancelada históricamente.
- Evidencia de código: formulario y páginas `Certifications`, modelo/migración de `Certification`, `CertificationController`, solicitudes de certificación, `ValidatesYearMonth`, `YearMonth`, `NameNormalizer`, `LinkedDocumentsSection`, `DocumentController`, `StoreDocumentRequest` y `PdfFile`. La tabla real y sus validaciones se conservan en US-010.
- La implementación incorpora extracción nativa con `pdftotext` y OCR local con Tesseract + `pdftoppm`/`pdfimages`; `smalot/pdfparser` sigue sin uso productivo. No se incorporó un SDK de IA ni un servicio OCR externo.

## Conciliación de la integración efectiva

Inspección local del 2026-11-20, sin ejecutar análisis IA ni procesar documentos:

| Comprobación | Evidencia observada |
| --- | --- |
| Camino invocado por la aplicación | `AppServiceProvider` vincula `CvAdaptationAnalyzer` a `CvAdaptationCodex`; este inicia Node y `scripts/codex-structured.mjs`; este usa `codex exec`, esquema estructurado, ejecución efímera, aislamiento de lectura y herramientas de acción deshabilitadas. No importa el SDK. |
| Configuración efectiva de Laravel | Bootstrap de la aplicación y lectura exclusiva de `services.codex`: binario `codex`, Node `node`, modelo `gpt-6-luna` (Luna 6), esfuerzo `xhigh`, timeout 300 segundos; configuración no cacheada. |
| Binarios del entorno | Node v22.17.1 en `/usr/local/bin/node`; Codex CLI 0.171.0 en `/Users/aom/.npm-global/bin/codex`. |
| Autenticación observada | `codex login status`: `Logged in using ChatGPT`. Sin lectura/exposición de credenciales ni cambio de sesión. |
| Paquetes comprobados | `@openai/codex-sdk` no resuelve desde la aplicación y no está en el directorio npm global activo. El paquete global observado es `@openai/codex` 0.171.0, correspondiente al CLI. `package.json`/lockfile no declaran el SDK. No se afirma ausencia en todos los entornos del equipo. |
| Herramientas documentales locales | `pdftotext` 26.11.0, Tesseract 5.5.4, `pdftoppm`/`pdfimages` 26.11.0 y OCRmyPDF 17.14.1 disponibles. La implementación usa `pdftotext`, Tesseract y Poppler; OCRmyPDF fue identificado pero no se usó. |

**Decisión conciliada:** reutilizar la integración efectiva **CLI existente**, su proceso Node aislado y la configuración actual, conservando Luna 6, esfuerzo, proveedor y autenticación. La referencia anterior a «SDK instalado» no describe el camino que usa esta instalación; no se introduce el SDK para hacerla coincidir. No queda pendiente una elección SDK/CLI para iniciar US-010. La inspección no garantiza cuota, acceso remoto futuro al modelo ni éxito de procesamiento; esas condiciones deben manejarse mediante los errores y alternativa manual del contrato, sin cambios automáticos.

El CLI emitió una advertencia de permisos al intentar preparar alias PATH en el entorno restringido, pero devolvió versión y estado de sesión correctamente. No impidió la inspección ni requiere modificar configuración global.

## Ejecución y cierre

- **Implementación:** US-010 en `b693626`; US-011 en `3e91ee1`; ajustes finales de alineación y etiqueta de la pantalla de importación en `7e16e22`. Por instrucción expresa del responsable, ambas historias se implementaron en la rama vigente; no se creó una rama separada para US-011.
- **Aceptación:** US-010 y US-011 fueron aceptadas funcional y visualmente por el responsable el 2026-11-21, incluidos los ajustes finales. La SPEC se cierra con esa aceptación.
- **Integración:** fast-forward de `009-importacion-asistida-certificados` a `main`; versión 1.1.0. Sin tag por el flujo histórico del repositorio.
- **Verificación:** regresión del recorrido con texto nativo; pruebas focalizadas de importación, OCR y UI; comprobación local de OCR sobre un certificado con 0 caracteres nativos; PDF mixto con páginas nativas y OCR; cita no coincidente conservada como sugerencia sin respaldo. La automatización no realizó llamadas reales a Luna 6. No se enviaron PDFs ni imágenes a servicios externos.
- **Estado final:** contrato implementado, aceptado y cerrado; no quedan historias pendientes en SPEC-009.
