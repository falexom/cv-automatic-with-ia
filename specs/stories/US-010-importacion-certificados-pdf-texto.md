# US-010 — Importación de certificados PDF con texto

- **Identificador:** US-010
- **Estado:** APROBADA_PARA_DESARROLLO
- **Aprobación humana:** 2026-11-20; el responsable aprueba la historia y sus límites en la conversación de preparación de SPEC-009.
- **SPEC:** [SPEC-009 — Importación asistida de cursos y certificaciones](../009-importacion-asistida-certificados/spec.md).
- **Prioridad y orden:** primera historia de SPEC-009.
- **Rama documental:** `009-importacion-asistida-certificados`, creada desde `main` en `eae90399073e3895a24a1a8c81e8417f33ee0822`, preservando los documentos pendientes del mismo encargo.
- **Ejecución:** no iniciada. La aprobación del contrato no habilita implementación en esta fase; se espera la siguiente instrucción del responsable. Sin autorización de commit, push ni merge.

## Historia de usuario

Como usuario, quiero cargar un certificado PDF con texto legible al iniciar un curso o certificación, revisar y corregir la propuesta de Luna 6 y confirmar su registro con el original asociado, para reducir la transcripción sin incorporar información no aprobada.

## Autoridad del contrato y alcance

Este archivo es la fuente única del contrato del recorrido con texto y de las reglas comunes de importación: campos, validaciones, privacidad, revisión, errores, cancelación y persistencia. [US-011](US-011-importacion-certificados-ocr-local.md) hereda estas reglas y define solamente el respaldo OCR local. La SPEC y el roadmap enlazan los contratos sin mantener copias alternativas.

Incluye el recorrido completo para un único PDF con texto nativo suficiente y un único curso/certificación: consentimiento, extracción, propuesta con Luna 6, revisión corregible, confirmación y asociación del original, con recuperación manual ante problemas. Un PDF escaneado o mixto insuficiente no se procesa mediante OCR en esta historia; se informa la limitación y se ofrece ingreso manual. Un PDF mixto cuyo contenido relevante sea completamente legible como texto nativo puede seguir el recorrido sin OCR.

## Requisitos funcionales comunes

- **FR-001**: Ofrecer asistencia opcional al iniciar un curso/certificación, para un PDF y un registro por operación, sin bloquear la creación manual. No añadir campos profesionales a los existentes.
- **FR-002**: Admitir únicamente PDF con las validaciones documentales vigentes y el límite actual de 20 MB (20480 KB); rechazar entradas inválidas con motivo visible antes de su análisis. Seleccionar o analizar no equivale a cargar definitivamente en Documentos.
- **FR-003**: Extraer primero texto nativo; considerarlo suficiente cuando el contenido relevante del certificado sea legible y permita sustentar valores o constatar ausencias. En US-010 no ejecutar OCR: si el texto está vacío, es ilegible o solo cubre partes ajenas al contenido relevante, informar que requiere OCR y ofrecer ingreso manual, sin presentar una extracción parcial como completa. El respaldo OCR para ese caso corresponde exclusivamente a US-011.
- **FR-004**: Usar Luna 6 a través de la integración y configuración existentes. No instalar otro SDK, cambiar modelo, proveedor, autenticación, credenciales ni esfuerzo configurado; ante indisponibilidad informar y habilitar ingreso manual. Se reutiliza el adaptador CLI efectivo conciliado en la SPEC, sin incorporar SDK ni dependencias nuevas.
- **FR-005**: Antes de cada análisis externo, mostrar destino Codex/OpenAI, modelo y esfuerzo efectivos, contenido concreto que se enviará y carácter externo del procesamiento; requerir consentimiento explícito. El envío incluye solo texto pertinente del certificado y sus referencias de página disponibles, los nombres/referencias de campos y las opciones mínimas del catálogo de habilidades necesarias para sugerir asociaciones. Puede contener código y URL de validación si aparecen en el certificado, a diferencia de la adaptación a ofertas. Excluir datos ajenos como identificación/contacto del titular, firmas y cualquier contenido del Maestro, CV o perfiles no necesario. Si no es posible minimizar sin revelar contenido adicional, solicitar al usuario retirar ese contenido de la selección enviada o continuar manualmente.
- **FR-006**: No enviar el PDF completo ni imágenes cuando baste texto extraído. El OCR de US-011 será local; incorporar un servicio OCR externo o transmitir páginas/imágenes requiere decisión humana específica previa y aviso/consentimiento sobre ese contenido. Si no hay OCR local disponible, informar y permitir ingreso manual. El consentimiento al análisis no autoriza nuevos servicios.
- **FR-007**: Limitar la propuesta a la tabla de campos; dejar en blanco los datos no encontrados y señalar ambigüedades sin inventar valores, completar fechas, transformar duración en estimaciones ni consultar fuentes externas. Solo categoría y habilidades admiten inferencias, separadas como sugerencias, justificadas por el contenido y sin presentarlas como hechos acreditados.
- **FR-008**: Identificar cada valor como encontrado, sugerido o ingresado/corregido por el usuario; las ausencias se indican expresamente. Para datos encontrados mostrar fragmento de soporte y página original cuando estén disponibles; si falta localización, indicarlo, sin inventar citas. Toda sugerencia debe exponer su fundamento, sin aparentar cita literal.
- **FR-009**: Restringir habilidades propuestas al catálogo vigente del Perfil Maestro; tanto coincidencias literales como sugerencias se revisan. Las inferidas requieren aceptación individual explícita, al igual que la categoría inferida. Si no hay opciones pertinentes, dejar la selección vacía. No crear habilidades ni afirmar dominio profesional por aparecer en el temario.
- **FR-010**: Permitir revisar, corregir, quitar valores, aceptar/rechazar sugerencias, consultar el original, cancelar o confirmar. Si ya había datos escritos, presentar la propuesta sin reemplazarlos hasta que el usuario decida aplicarla. Consentir el envío, aceptar un campo o terminar el análisis no confirma el registro.
- **FR-011**: Revalidar en la confirmación todos los campos, habilidades vigentes y PDF de la operación. Mantener la advertencia de duplicado por nombre e institución normalizados, con confirmación adicional explícita; no usar la IA para decidir su omisión.
- **FR-012**: Solo tras confirmar expresamente guardar los valores revisados, habilidades aceptadas y asociación con el PDF original inalterado. Crear y asociar como una operación íntegra: sin curso, documento, asociaciones o archivos nuevos parciales ante fallo. Reutilizar originales idénticos íntegros sin duplicarlos y conservar visibles ambas direcciones de la asociación. No duplicar una misma confirmación reenviada.
- **FR-013**: Ante fallo de archivo, extracción, OCR, servicio o salida no válida, explicar el problema y permitir reintento explícito o ingreso manual. No guardar por el fallo ni perder datos previos o el archivo fuente del usuario. El registro manual requiere su propia confirmación y validaciones actuales; no puede asociar un archivo inválido.
- **FR-014**: Tratar PDF, texto extraído y respuesta de IA como datos no confiables. No obedecer instrucciones del documento, ejecutar acciones, abrir enlaces, acceder a otros archivos ni permitir escrituras autónomas de IA. Validar estructura, valores permitidos, referencias y procedencia antes de presentar o guardar la propuesta.
- **FR-015**: Mantener solo estado temporal necesario durante la operación. Al cancelar, abandonar, sustituir PDF o fallar el análisis, invalidar el contexto y eliminar PDF temporal, texto/OCR, borradores, prompts y resultados intermedios locales; descartar respuestas tardías. No escribir esos contenidos en logs, historiales o sesiones durables de la aplicación/integración. Conservar solo los datos manuales previos en el formulario si se continúa allí. Tras confirmar conservar únicamente el registro revisado y su documento/relaciones; sin historial del análisis ni persistencia de citas o prompts. Informar que la eliminación local no revierte un envío externo ya autorizado ni garantiza borrado en el proveedor.

## Criterios de aceptación

1. **CA-01 — Entrada y consentimiento:** al iniciar un registro se puede elegir un solo PDF válido dentro del límite vigente. Antes de enviarlo se informa el contenido concreto minimizado, destino y configuración efectiva según FR-005/006. Sin consentimiento no hay envío; seleccionar o analizar no crea curso, documento ni asociación.
2. **CA-02 — Texto nativo:** con texto relevante suficiente se extrae y propone información sin OCR ni envío de PDF/imágenes. Texto vacío, ilegible o solo de membrete produce aviso de necesidad de OCR y opción manual, sin inventar ni presentar como completa una lectura insuficiente. La ausencia de un campo no implica insuficiencia si el contenido ya es legible.
3. **CA-03 — Propuesta y campos:** Luna 6 propone solo los campos de la tabla siguiente. Los datos ausentes o ambiguos quedan vacíos; año sin mes conserva mes vacío, duración no se estima y código/URL no se reconstruyen. Fechas contradictorias o varias credenciales no generan decisiones ni registros automáticos.
4. **CA-04 — Procedencia y sugerencias:** cada valor distingue dato encontrado, sugerencia o edición humana. Muestra soporte textual y página original cuando estén disponibles, y señala cuando falten sin inventar citas. Categoría inferida y cada habilidad inferida requieren aceptación individual; ninguna habilidad ajena al catálogo se propone o crea. Catálogo vacío deja selección vacía. Las inferencias nunca acreditan dominio.
5. **CA-05 — Revisión:** el usuario puede consultar el original, modificar o quitar cualquier valor, rechazar sugerencias, cancelar y confirmar. La propuesta no sobrescribe silenciosamente lo ingresado antes. Consentir el envío o aceptar un campo no confirma el registro.
6. **CA-06 — Confirmación y conservación:** solo una confirmación final válida crea un registro con exactamente los valores revisados y las habilidades aceptadas, vinculado al PDF idéntico al seleccionado. Registro y evidencia son consultables en ambas direcciones y permanecen al reabrir la aplicación; título/descripción documental no se inventan.
7. **CA-07 — Validaciones:** nombre obligatorio, longitudes, URL, fechas y habilidades cumplen la tabla vigente. Datos inválidos o habilidad desaparecida antes de guardar impiden toda persistencia parcial, explican el campo afectado y conservan la revisión para corregirla.
8. **CA-08 — Duplicado profesional:** nombre e institución normalizados coincidentes muestran la advertencia actual; guardar el duplicado exige confirmación adicional explícita. Cancelar la advertencia permite corregir sin crear nada. No se omite la advertencia por decisión de IA.
9. **CA-09 — Reutilización documental:** un PDF idéntico e íntegro ya conservado se reutiliza informando al usuario, sin nueva copia ni alteración de sus metadatos/vínculos existentes. Mismo nombre con distinto contenido no es idéntico. Un original preexistente faltante o alterado no se reutiliza silenciosamente.
10. **CA-10 — Integridad del guardado:** un fallo en curso, habilidades, PDF o asociación no deja curso, documento, asociación ni archivo nuevo parcial, no altera documentos preexistentes y permite reintento. Doble clic o reenvío de la misma confirmación no duplica nada; una operación independiente de duplicación sigue CA-08.
11. **CA-11 — Cancelación y abandono:** cancelar antes, durante o después del análisis, abandonar o sustituir PDF deja cero registros nuevos, invalida la propuesta/consentimiento y limpia los temporales según FR-015; respuestas tardías no repueblan ni guardan. No se conservan borradores, prompts o intermedios en logs, historiales ni sesiones durables. El archivo fuente del usuario y documentos preexistentes permanecen intactos.
12. **CA-12 — Recuperación manual:** archivo inválido/protegido/no procesable, extracción fallida, timeout, falta de acceso a Luna 6 o salida inválida informan el problema y permiten ingreso manual o reintento explícito, sin cambiar configuración ni perder datos manuales previos. Si continúa allí, estos permanecen en pantalla; cancelar todo el registro los descarta sin borrador durable. No se solicitan ni conservan contraseñas ni se intenta eludir protección. El registro manual solo se guarda al confirmarlo, con sus validaciones; permite asociar el original válido aún seleccionado y exige retirar/sustituir uno inválido.
13. **CA-13 — Contenido no confiable:** órdenes, enlaces o solicitudes dentro del PDF o respuesta IA no ejecutan acciones, no acceden a otros archivos, no abren enlaces ni conceden escritura al agente. Una respuesta con estructura, valores o referencias inválidas se rechaza antes de usarla.
14. **CA-14 — Minimización y retención:** solo se envía lo autorizado por FR-005/006, sin contactos/identificación del titular, firmas o datos ajenos del Maestro. Cancelar/fallar elimina el estado intermedio local; confirmar conserva únicamente registro, original y relaciones, sin historial ni citas persistidas. El aviso aclara que limpiar localmente no revierte un envío externo ya autorizado.
15. **CA-15 — Límites de entrega:** no hay OCR, creación de perfiles/habilidades/campos nuevos, adaptación de CV, procesamiento masivo ni guardado automático. La aceptación de esta historia no cierra SPEC-009 ni sustituye la aceptación de US-011.

## Campos existentes y validaciones que se conservan

| Campo del formulario | Referencia actual | Regla al confirmar y propuesta admitida |
| --- | --- | --- |
| Nombre | `name` | Obligatorio, texto hasta 255 caracteres; nombre literal del curso/certificación. Si falta, vacío hasta completarlo manualmente. |
| Institución | `institution` | Opcional, texto hasta 255; no confundir con titular ni completar por logotipo dudoso. |
| Fecha | `date_year`, `date_month` | Opcional, sin día. Año entero entre 1900 y año actual + 10; mes entre 1 y 12, solo con año. Año sin mes permitido; no inventar componentes ni escoger entre fechas ambiguas. |
| Duración | `duration` | Opcional, texto libre hasta 100; conservar unidad expresada, sin calcular horas no indicadas. |
| Código o identificador | `code` | Opcional, texto hasta 255; solo código visible del certificado. |
| URL de validación | `validation_url` | Opcional, URL válida hasta 2048; no reconstruir direcciones ni abrirlas automáticamente. |
| Categoría | `category` | Opcional, texto libre hasta 100, sin catálogo fijo; si inferida, marcar sugerencia y exigir aceptación. |
| Habilidades asociadas | `skill_ids` | Selección opcional de habilidades existentes; no texto libre ni creación de opciones. |

El PDF es un documento relacionado, no un campo textual de certificación. Nombre original, tamaño, integridad y fecha de carga son metadatos documentales existentes; título y descripción documentales opcionales no se generan mediante este análisis. Identificadores internos, controles de duplicado y referencias de soporte no son campos profesionales nuevos.

## Entidades del recorrido

- **Curso/certificación existente**: registro único del Perfil Maestro con los campos anteriores y sus habilidades aceptadas.
- **Documento original existente o nuevo**: PDF íntegro conservado localmente; su asociación se crea solo al confirmar y no duplica archivos idénticos.
- **Propuesta temporal**: valores, ausencias, sugerencias y soporte para revisión; no constituye trayectoria profesional ni un registro durable.
- **Habilidad existente**: opción del catálogo actual; las sugerencias solo pueden referenciar estas opciones y no acreditan nivel de dominio.

## Criterios de éxito del recorrido común

- **SC-001**: En el 100% de los escenarios sin confirmación final hay cero cursos, documentos y asociaciones nuevos persistidos, incluidos cancelación, abandono, fallo y respuesta tardía.
- **SC-002**: En todos los casos de aceptación con información ausente, los campos correspondientes permanecen vacíos; el 100% de las categorías/habilidades inferidas se identifica como sugerencia y ninguna se incorpora sin aceptación explícita.
- **SC-003**: Cada confirmación válida permite consultar un único registro con exactamente los valores revisados y el PDF idéntico al original; repetir la misma confirmación no aumenta esos registros. Los archivos idénticos preexistentes conservan una sola copia.
- **SC-004**: En el 100% de los casos inválidos o duplicados se mantienen las validaciones o advertencias actuales; ningún fallo de guardado deja resultados parciales y el usuario puede corregir o reintentar.
- **SC-005**: Todos los fallos previstos ofrecen mensaje y alternativa manual sin perder lo ingresado previamente; cancelar elimina los intermedios locales de la operación.
- **SC-006**: Ningún envío ocurre sin consentimiento; en US-010 nunca se ejecuta OCR ni se envían PDF/imágenes. Todos los valores encontrados con fragmento/página disponible muestran ese soporte; los restantes indican su ausencia.

## Supuestos operativos

- Aplicación local monousuario con Perfil Maestro único; interfaz en español y conservación de acentos y contenido multilingüe del certificado.
- Los límites de campos son los actuales, sin cambios al modelo profesional ni catálogo. La selección y propuesta son temporales, sin borrador durable; los archivos fuente del usuario y documentos preexistentes quedan fuera de la limpieza de la operación.

## Dependencias y exclusiones

- Requiere SPEC-001 (Perfil Maestro) y SPEC-002 (Documentos y Evidencias), ya disponibles.
- Reutiliza la integración efectiva descrita en [SPEC-009, conciliación SDK/CLI](../009-importacion-asistida-certificados/spec.md#conciliación-de-la-integración-efectiva); SPEC-008 es solo antecedente técnico. No depende de US-011 para entregar valor.
- US-011 depende de este recorrido y sus reglas comunes; no se implementa incidentalmente en US-010.
- Excluye educación, experiencia, proyectos, edición asistida de registros existentes, perfiles/CV/ofertas, lotes, nuevos campos/catálogos, historial de análisis y cambios de modelo, proveedor, autenticación, credenciales, esfuerzo o dependencias para incorporar SDK.

## Descomposición y verificaciones previstas

Preparación para la siguiente fase; no ejecutada ahora:

1. Revisar el contrato aprobado, estado Git y aislamiento de US-010 antes de escribir código; usar la rama de SPEC-009 exclusivamente para esta primera implementación. US-011 requerirá rama propia desde la base que contenga US-010 aceptada, sin mezclar implementaciones. No resolver el aislamiento mediante stash, descartes o commits no autorizados.
2. Incorporar selección/validación temporal y extracción nativa aprovechando herramientas locales existentes, con detección de insuficiencia y alternativa manual, sin OCR.
3. Reutilizar invocación aislada y configuración Codex vigente; definir propuesta estructurada acotada a los campos, minimización, consentimiento y controles de procedencia/contenido no confiable.
4. Incorporar revisión y aceptación de sugerencias, cancelación/limpieza e invalidación de respuestas tardías, preservando edición manual.
5. Integrar confirmación, revalidación, advertencia de duplicados, persistencia íntegra del curso/habilidades/evidencia, reutilización documental y protección frente a reenvíos.
6. Realizar comprobaciones técnicas focalizadas según archivos cambiados y cobertura automatizada proporcional al Principio XXI: CA-01–14, sin llamadas reales a IA en la automatización. Incluir PDF con texto, texto insuficiente y archivo inválido, datos ausentes/ambiguos, referencias ajenas/obsoletas, inyección, cancelación tardía, fallos de guardado y reintentos. No ejecutar suites completas ni automatizaciones adicionales sin autorización.
7. Presentar al responsable el recorrido con texto para aceptación funcional/visual; después revisar QA y seguridad según riesgo. No declarar aceptación a partir de comprobaciones técnicas ni cerrar la SPEC sin ambas historias aceptadas.

## Estado de preparación

Contrato aprobado; implementación, verificaciones y aceptación funcional/visual pendientes. Herramientas locales identificadas mediante inspección, sin procesar certificados ni invocar análisis. La siguiente instrucción del responsable habilitará iniciar US-010; esta fase se limita a rama y documentación.
