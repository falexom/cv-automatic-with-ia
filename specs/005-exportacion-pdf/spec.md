# Feature Specification: Exportación PDF

**Feature Branch**: `005-exportacion-pdf`

**Created**: 2026-10-23

**Status**: Clarified — ready for Plan (2026-10-23). Sin aprobación humana; la implementación y la validación quedan pendientes. La regla de nombre de archivo queda resuelta en C5/FR-011.

**Input**: User description: "Exportar y descargar un PDF profesional del CV generado en SPEC-004, conservando contenido, orden, jerarquía visual, márgenes, tipografía, saltos de página, enlaces compatibles y soporte multipágina. Utilizar un nombre de archivo consistente y verificar que el PDF generado sea válido y pueda abrirse."

## Problema y Objetivo

**Problema**: SPEC-004 ya permite obtener una vista previa de solo lectura, derivada y efímera, del CV de un perfil activo. Sin embargo, el usuario no dispone de un archivo que pueda conservar, adjuntar o entregar. Si armara el PDF por fuera —copiando, imprimiendo o reconstruyendo la vista— perdería la fidelidad del contenido, el orden, la jerarquía, los saltos de página y los enlaces, y crearía una segunda fuente de verdad que se desactualizaría en cuanto cambiaran el Perfil Maestro o el perfil. Eso contradice los Principios I–III y deja incompleto el paso 13 del criterio de éxito del MVP.

**Objetivo**: incorporar en la sección «Generador de CV» una acción «Exportar PDF» que, para un perfil activo con vista `ready` y datos, genere bajo demanda un PDF profesional A4 de una columna a partir de la misma composición y el mismo perfil que la vista previa, lo descargue con un nombre determinista y seguro, verifique su validez y no lo persista ni lo envíe a servicios externos. El PDF debe conservar contenido, orden, jerarquía, márgenes, tipografía embebida, saltos de página, soporte multipágina y enlaces del CV clicables, y no debe convertirse en una fuente de datos ni en un documento editable.

## Alcance

### Incluido en esta capacidad

- Acción visible «Exportar PDF» en la sección «Generador de CV», fuera de la hoja del CV, disponible solo para un perfil activo con la vista en `ready` y `has_data` verdadero.
- Generación bajo demanda del PDF a partir de la misma composición (`CvPreviewContent::build()` o el contrato equivalente) y el mismo perfil activo de SPEC-004, sin una segunda lógica de selección, orden o contenido.
- Documento A4 vertical, de una columna, con márgenes consistentes, tipografía legible embebida, jerarquía visual equivalente a la vista previa, saltos de página naturales y soporte de una o varias páginas sin truncar contenido.
- Fidelidad verificable: mismas secciones, campos, elementos incluidos y orden; sin elementos excluidos, contenido de otros perfiles, metadatos de relevancia o estado, enlaces internos de corrección ni datos inventados.
- Enlaces del CV clicables cuando el formato lo permita: teléfono (visible y, cuando el formato lo permita, enlace `tel:`), correo, LinkedIn, GitHub, sitio web, enlaces adicionales, validación de certificación y URL de proyecto.
- Nombre de archivo determinista y seguro, derivado del perfil, con fallback; descarga del archivo.
- Validación de que el PDF es completo y puede abrirse; errores claros fuera del documento, sin archivo parcial o corrupto, con reintento.
- Generación local, sin servicios externos, sin IA y sin conexión a internet; PDF autocontenido con fuentes embebidas.
- Pruebas de fidelidad, validez, multipágina, enlaces, nombre, errores, seguridad y regresión.

### Fuera de alcance

- Certificados anexos, concatenación de PDFs, OCR, CV documentado, selección de evidencias y cualquier asociación documental (SPEC-002 y SPEC-006).
- Múltiples diseños, plantillas, variantes por perfil, personalización gráfica o selector de diseño.
- Firma digital, publicación, envío del PDF por correo, compartición, URL pública o cualquier forma de distribución.
- Persistencia, almacenamiento, historial, versionado, edición del PDF o conversión del PDF en fuente de datos.
- Edición del CV o cambios en la vista previa de SPEC-004; la acción se añade en la sección, fuera de la hoja, y la vista conserva su solo lectura.
- Nuevas reglas de selección, relevancia u orden; nuevos campos o clases de contenido; migraciones o cambios de modelo.
- ATS, adaptación a ofertas, cartas de presentación, postulaciones o generación de contenido por IA.
- Impresión integrada en la aplicación; el usuario puede imprimir el PDF con su lector, pero la aplicación no añade una acción de impresión.
- Autenticación, multiusuario, sincronización externa, servicios cloud y analítica.

## Clarifications

### Decisiones ya resueltas aplicables

- **C1 — Disponibilidad y datos (encargo; SPEC-004 FR-017–FR-019)**: la exportación solo se ofrece para un perfil activo seleccionado, con la vista en estado `ready` y `has_data` verdadero. No se ofrece ni se produce un PDF en `no_active_profiles`, `selection_required`, `unavailable`, `error` o `ready` con `has_data` falso. Un PDF vacío no es un resultado válido.
- **C2 — Origen único y datos vigentes (SPEC-004 FR-006–FR-016; encargo)**: la exportación consume la misma composición derivada que la vista previa (`CvPreviewContent::build()` o el contrato equivalente) y el mismo perfil activo seleccionado; no crea una segunda lógica de selección, relevancia, orden, herencia, datos propios ni contenido. Se recompone en el momento de la exportación con los datos vigentes: si el Maestro o el perfil cambian entre la vista previa y la descarga, el PDF refleja el estado actual y no una copia obsoleta; la vista previa puede actualizarse. No hay fallback a otro perfil ni al título o resumen del Maestro.
- **C3 — Documento y fidelidad (encargo; Constitución XVI y XVIII)**: el PDF es A4 vertical, de una columna, con márgenes consistentes, tipografía legible embebida y jerarquía visual equivalente a la vista previa. Conserva las mismas secciones, campos, elementos incluidos y orden; omite elementos no incluidos, secciones sin elementos, campos vacíos, contenido de otros perfiles, metadatos de relevancia o estado y enlaces internos de corrección; no añade datos, fechas, logros, resultados, textos de relleno ni secciones. El contenido puede ocupar varias páginas con saltos naturales, sin truncar, ocultar ni solapar información. Se usa una única plantilla, sin opciones de diseño.
- **C4 — Enlaces (SPEC-004 FR-010, FR-011 y FR-014; encargo)**: los enlaces que la vista previa presenta como clicables se conservan como enlaces PDF cuando el formato lo permite, con el mismo texto visible y destino: teléfono (siempre como texto visible y, cuando el renderizador y el lector lo permitan, como enlace `tel:`), correo (`mailto:`), LinkedIn, GitHub, sitio web, enlaces adicionales, validación de certificación y URL de proyecto. Si el teléfono no puede conservarse como enlace, permanece como texto visible. Los enlaces internos de corrección de la vista previa (`profile`, `personal_data` y secciones del Maestro) no forman parte del PDF. Las anotaciones no introducen JavaScript, formularios, adjuntos, acciones automáticas ni recursos remotos; las URL inválidas o no seguras no se activan. Los metadatos del archivo no incluyen relevancia, estado, rutas internas, identificadores de configuración ni datos de otros perfiles.
- **C5 — Nombre de archivo (encargo)**: el nombre es determinista y seguro, derivado del nombre del perfil seleccionado. Regla adoptada: `cv-` + base normalizada, donde la base se obtiene transliterando el nombre a ASCII, pasando a minúsculas, sustituyendo cualquier secuencia de caracteres no alfanuméricos por un guion, colapsando guiones repetidos, eliminando guiones al inicio y al final, truncando a 80 caracteres y eliminando cualquier guion final que hubiera quedado; se añade una sola extensión `.pdf`. Si la base queda vacía, el nombre es `cv.pdf`. El nombre final solo contiene `[a-z0-9-]` y `.pdf`, sin rutas, separadores de directorio, `..`, comillas, caracteres de control ni secuencias de escape. La misma entrada produce siempre el mismo nombre. El nombre del perfil se usa solo para el archivo y no se imprime en el documento. Decisión resuelta para Plan; el usuario puede cambiarla antes de Implement si lo desea.
- **C6 — Sin persistencia (encargo; SPEC-004 FR-015)**: el PDF se genera bajo demanda y se descarga; no se persiste, almacena, versiona, comparte ni conserva. No hay historial, URL posterior, copia temporal recuperable ni registro del contenido. Cualquier recurso temporal usado durante la generación se elimina antes de completar la respuesta y no queda accesible. La única copia es la que recibe el usuario. No se crean tablas, columnas, migraciones, registros ni archivos persistentes.
- **C7 — Errores y validez (encargo; Constitución XXI)**: antes de entregar el archivo se verifica que el PDF es válido y completo. Si la generación o la validación falla, se muestra un mensaje claro fuera del documento, no se entrega un archivo parcial, truncado o corrupto y se permite reintentar; la selección se conserva cuando sea posible. Un PDF incompleto no se presenta como válido.
- **C8 — Generación local y autocontenida (Constitución XXIII y XXV; encargo)**: la generación es local, sin servicios externos, sin IA, sin envío de datos y sin conexión a internet. El PDF es autocontenido: fuentes embebidas, sin recursos remotos ni dependencia de red para abrirse o visualizarse.
- **C9 — Relación con SPEC-004 (encargo)**: SPEC-004 definió la vista previa y excluyó la exportación dentro de la hoja (FR-022). SPEC-005 añade una acción de exportación en la sección «Generador de CV», fuera del cuerpo del CV. La hoja sigue siendo de solo lectura y no incorpora controles de edición, plantilla, exportación, impresión o descarga dentro de ella. No se modifica la especificación de SPEC-004, ni su plantilla, ni su composición, ni el comportamiento de la hoja. La ampliación se limita a la sección: se añade la acción fuera de la hoja. Las pruebas de regresión de SPEC-004 que verificaban la ausencia de exportación en la sección se actualizan para reconocer la nueva acción; no se cambia ninguna expectativa sobre la hoja, su composición, su solo lectura ni sus datos.
- **C10 — Decisiones delegadas a Plan (no requieren Clarify)**: familia y tamaño tipográfico, márgenes exactos, interlineado, numeración de página, algoritmo de paginación, mecanismo local de renderizado, estrategia de validación estructural y forma concreta de la ruta y la descarga. Todas deben respetar C3, C6 y C8 y no introducir servicios externos, persistencia, plantillas múltiples ni cambios de contenido.

### Session 2026-10-23 — Specify

- Revisión de contraste con la Constitución, el roadmap de SPEC-005, la especificación y los contratos de SPEC-004, y las reglas de SPEC-003 y SPEC-001 consumidas por SPEC-004. Se confirman C1–C10; no se identificaron contradicciones materiales.
- La exportación se apoya en la misma composición y el mismo perfil de SPEC-004; no se reabre su especificación ni se duplican sus reglas.
- La acción de exportación vive fuera de la hoja del CV; la vista previa conserva su carácter de solo lectura.
- No se ejecutaron código ni pruebas en esta fase; la implementación y la validación quedan pendientes. Este documento no declara la capacidad aprobada.
- La confirmación del patrón visible del nombre de archivo quedó resuelta en la sesión de Clarify.

### Session 2026-10-23 — Clarify

- Se acepta la regla de nombre C5/FR-011: `cv-{base-normalizada}.pdf` con fallback `cv.pdf`. No queda como decisión pendiente ni bloquea Plan; el usuario puede proponer otro patrón antes de Implement si lo desea.
- Se explicita el teléfono en C4, FR-009, SC-004 y las pruebas: siempre como texto visible y, cuando el renderizador y el lector lo permitan, como enlace `tel:`.
- Se clasifican las pruebas requeridas en automatizables y de validación manual.
- Se precisa C9: se actualizan pruebas de regresión de la sección, no la especificación de SPEC-004, la hoja ni su comportamiento.
- No quedan decisiones funcionales de negocio pendientes. La spec queda Clarified — ready for Plan; no se declara aprobada.

### Pendientes

- **Ninguna decisión funcional de negocio pendiente.** La regla de nombre de C5/FR-011 queda resuelta y no bloquea Plan. El usuario conserva la opción de proponer otro patrón visible antes de Implement (por ejemplo, `CV-{Perfil}.pdf`, incluir fecha o usar el nombre completo); si lo hace, se ajustarán FR-011 y SC-005, pero no hay ninguna decisión abierta en esta fase.
- **Decisiones de presentación para Plan**: las de C10. No requieren Clarify.

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Exportar un PDF fiel del perfil seleccionado (Priority: P1)

Como usuario, quiero exportar el CV del perfil activo que estoy previsualizando y descargarlo como PDF, para obtener un documento profesional que represente fielmente lo que revisé y pueda usar fuera de la aplicación.

**Why this priority**: es el corazón de la capacidad y completa el paso 13 del criterio de éxito del MVP. Sin esta historia no existe PDF descargable y la vista previa sigue siendo el único resultado, por lo que el usuario tendría que reconstruir el documento por fuera.

**Independent Test**: con un Perfil Maestro y un perfil activo que tenga datos personales y de contacto, título y resumen propios, elementos incluidos de las cinco clases con relevancias distintas, elementos excluidos y otro perfil con contenido distinto, abrir la vista previa y pulsar «Exportar PDF»; comprobar que se descarga un archivo con nombre seguro, que abre, que contiene exactamente el contenido incluido en el mismo orden, que no aparecen elementos excluidos, contenido de otro perfil ni metadatos, y que no queda ningún archivo, registro, historial ni versión en el servidor.

**Acceptance Scenarios**:

1. **Dado** un perfil activo con la vista en `ready` y `has_data` verdadero, **Cuando** el usuario pulsa «Exportar PDF», **Entonces** se descarga un archivo PDF con el nombre determinista y seguro definido en FR-011 y el archivo puede abrirse.
2. **Dados** elementos incluidos y excluidos, relevancias distintas y otro perfil con contenido distinto, **Cuando** el usuario exporta, **Entonces** el PDF contiene solo los elementos incluidos, en el mismo orden de la vista previa, sin elementos excluidos, sin contenido del otro perfil y sin metadatos de relevancia o estado.
3. **Dados** un título y un resumen propios del perfil y datos personales heredados del Maestro, **Cuando** el usuario exporta, **Entonces** el PDF muestra los mismos datos que la vista previa, omite los campos vacíos sin etiquetas ni relleno y no usa el título ni el resumen del Maestro como respaldo.
4. **Dado** un PDF descargado, **Cuando** el usuario lo compara con la vista previa, **Entonces** reconoce las mismas secciones, campos y elementos, el mismo orden, una sola columna A4 y una jerarquía equivalente, sin datos inventados ni contenido adicional.
5. **Dada** una exportación completada, **Cuando** se inspeccionan el servidor, el almacenamiento y la base de datos, **Entonces** no existe un PDF persistido, un historial, una versión ni un registro nuevo, y el Perfil Maestro y el perfil no cambiaron.

---

### User Story 2 - Conservar contenido, paginación y enlaces en un CV extenso (Priority: P2)

Como usuario, quiero que un CV largo se exporte en varias páginas sin perder contenido y con los enlaces del CV clicables, para usar el documento en procesos reales sin revisarlo ni corregirlo después.

**Why this priority**: los CV reales suelen superar una página. Una exportación que solo funcione en una página o que pierda enlaces no cumple el resultado esperado; esta historia cubre el riesgo de truncamiento, saltos defectuosos y pérdida de enlaces.

**Independent Test**: con un CV que supere una página, textos largos, acentos, URL largas y todos los tipos de enlace externo, exportar y comprobar que el PDF tiene varias páginas, que todo el contenido aparece completo y una sola vez, que no hay líneas cortadas, solapamientos, contenido fuera de márgenes ni encabezados huérfanos, que las fuentes están embebidas y que cada enlace externo conserva su destino y ningún enlace interno de corrección aparece.

**Acceptance Scenarios**:

1. **Dado** un CV cuyo contenido excede una página, **Cuando** el usuario exporta, **Entonces** el PDF contiene todas las páginas necesarias, todo el contenido de la vista aparece completo y no se trunca, oculta ni duplica, y no se generan páginas en blanco evitables.
2. **Dados** textos largos, acentos, eñes, signos y cadenas sin espacios como URL, **Cuando** el usuario exporta, **Entonces** el texto se muestra completo, se ajusta sin desbordar la caja ni recortarse y los caracteres se renderizan correctamente.
3. **Dados** enlaces de correo, LinkedIn, GitHub, sitio web, adicionales, validación de certificación y URL de proyecto, **Cuando** el usuario exporta, **Entonces** cada enlace conserva el mismo texto visible y destino como enlace clicable, y no aparece ningún enlace interno de corrección.
4. **Dado** el PDF descargado, **Cuando** el usuario lo abre sin conexión, **Entonces** todas las páginas y enlaces funcionan, las fuentes están embebidas y no se solicitan recursos externos.

---

### User Story 3 - Exportar solo cuando corresponde y fallar sin archivos corruptos (Priority: P3)

Como usuario, quiero que la exportación no esté disponible cuando no hay datos y que, si algo falla, me avise sin descargar un archivo incompleto, para confiar en que el PDF que recibo es válido.

**Why this priority**: protege la confianza y evita resultados engañosos. Los estados sin datos, la desactivación del perfil y los fallos de generación son casos previsibles; un PDF vacío, parcial o corrupto sería peor que no descargar nada.

**Independent Test**: comprobar la sección sin perfiles activos, sin selección, con un perfil sin datos, con un perfil desactivado entre la vista y la exportación y ante un fallo simulado de generación o validación; verificar que no se ofrece o no se produce PDF, que el mensaje aparece fuera del documento, que no queda archivo parcial y que el reintento funciona.

**Acceptance Scenarios**:

1. **Dado** un estado `no_active_profiles`, `selection_required`, `unavailable`, `error` o `ready` con `has_data` falso, **Cuando** el usuario consulta la sección, **Entonces** no se ofrece la acción «Exportar PDF» y una petición directa de exportación no produce un PDF vacío.
2. **Dado** un perfil que se desactiva, deja de existir o pierde todos sus datos entre la vista previa y la exportación, **Cuando** el usuario intenta exportar, **Entonces** no se genera ni descarga el PDF, se informa fuera del documento y se ofrecen los perfiles activos o la actualización de la vista.
3. **Dado** un fallo de generación o de validación del PDF, **Cuando** el usuario intenta exportar, **Entonces** no se descarga ningún archivo parcial, truncado o corrupto, se muestra un mensaje claro fuera del documento y se ofrece reintentar conservando la selección cuando sea posible.
4. **Dado** un fallo de exportación, **Cuando** se inspeccionan el servidor y el almacenamiento, **Entonces** no queda un archivo temporal recuperable, un PDF a medias ni un registro de la exportación fallida.

### Edge Cases

- **Sin perfiles activos, sin selección, perfil no disponible o error**: no se ofrece exportación, no hay PDF y se mantienen los estados de SPEC-004.
- **Perfil activo sin datos (`has_data` falso)**: no se ofrece exportación y no se genera un PDF vacío; se muestra el estado vacío de SPEC-004.
- **Perfil desactivado, eliminado o sin datos entre la vista previa y la exportación**: no se genera el PDF; se informa y se ofrecen alternativas activas o la actualización de la vista.
- **Datos modificados entre la vista previa y la exportación**: el PDF usa los datos vigentes en el momento de la exportación; puede diferir de una vista previa desactualizada, pero nunca de una copia obsoleta. La vista puede actualizarse.
- **CV de una o varias páginas**: se admiten ambas; un CV extenso no se trunca ni se fuerza a una página; se evitan páginas en blanco evitables.
- **Elemento más largo que una página o encabezado al final de página**: el elemento puede continuar en la página siguiente; el encabezado no queda aislado sin su primer elemento; no se pierde contenido.
- **Textos largos, acentos, caracteres especiales y cadenas sin espacios**: se conservan completos, se ajustan sin desbordar la caja ni recortarse y no aparecen glifos faltantes.
- **Enlaces numerosos, largos, duplicados, inválidos o no seguros**: los válidos se conservan sin solaparse con el texto; los inválidos o no seguros no se activan; los enlaces internos de corrección no aparecen.
- **Teléfono sin soporte `tel:` en el lector**: el número se conserva como texto visible; si el formato lo permite, se conserva además la anotación `tel:`.
- **Nombre de perfil vacío, solo con emojis o símbolos, con acentos, espacios, `/`, `\`, `..`, comillas, puntos, caracteres de control o longitud máxima**: se aplica la regla de FR-011; si no queda base, se usa `cv.pdf`; nunca se incluyen rutas ni caracteres peligrosos. Si dos perfiles comparten nombre, el nombre de archivo coincide; no se añade el ID ni un sufijo no especificado.
- **Fallo del renderizador, fuente no disponible, memoria insuficiente, validación fallida o descarga interrumpida**: no se entrega archivo parcial o corrupto; se informa y se permite reintentar; no queda un archivo temporal recuperable.
- **Sin conexión a internet**: la exportación funciona localmente y el PDF se abre sin recursos remotos.
- **Sin nombre completo en el Maestro**: no se imprime un nombre inventado; el encabezado conserva el resto de datos disponibles.
- **Metadatos del PDF**: no incluyen relevancia, estado, rutas internas, identificadores de configuración ni datos de otros perfiles.

## Requirements _(mandatory)_

### Functional Requirements

#### Disponibilidad y origen

- **FR-001**: La sección «Generador de CV» DEBE ofrecer una acción visible «Exportar PDF» fuera del cuerpo del CV, únicamente cuando el perfil seleccionado esté activo, la vista se encuentre en estado `ready` y `has_data` sea verdadero. En `no_active_profiles`, `selection_required`, `unavailable`, `error` o `ready` con `has_data` falso, la acción NO DEBE ofrecerse y el sistema NO DEBE producir un PDF vacío.
- **FR-002**: La exportación DEBE usar el mismo perfil activo seleccionado y la misma composición derivada que la vista previa de SPEC-004 (`CvPreviewContent::build()` o el contrato equivalente), recompuesta en el momento de la exportación con los datos vigentes. NO DEBE crear una segunda lógica de selección, relevancia, orden, herencia, datos propios ni contenido, ni reutilizar una copia almacenada de la vista.
- **FR-003**: Antes de generar el PDF, el sistema DEBE revalidar que el perfil sigue activo y que la composición produce datos. Si el perfil dejó de estar activo, no existe, no está disponible o no produce datos, NO DEBE generar ni descargar un PDF y DEBE informar de la situación fuera del documento, ofreciendo actualizar la vista o elegir otro perfil activo.

#### Documento y fidelidad

- **FR-004**: El PDF DEBE ser un documento A4 vertical (210 × 297 mm) de una sola columna, con márgenes consistentes y suficientes, tipografía legible embebida y presentación profesional. DEBE conservar la jerarquía visual de la vista previa: encabezado con el nombre (si existe) y los datos de contacto, título profesional y resumen (si existen), y las secciones de experiencia, educación, cursos y certificaciones, habilidades y proyectos que tengan elementos, con la misma prominencia relativa de encabezados, títulos de elemento y metadatos secundarios. La adaptación a A4 NO DEBE alterar el orden ni la jerarquía relativa.
- **FR-005**: El PDF DEBE contener exactamente las mismas secciones, campos y elementos incluidos que la vista previa, en el mismo orden, y DEBE omitir los elementos no incluidos, las secciones sin elementos, los campos opcionales vacíos, el contenido de otros perfiles, los metadatos de relevancia o estado, los enlaces internos de corrección y cualquier control de la interfaz. NO DEBE añadir datos, fechas, logros, resultados, textos de relleno, secciones, encabezados o pies con contenido ajeno a la vista, logotipos, marcas de agua, fechas de generación ni textos promocionales. La numeración de página, si se incluye, es un elemento de paginación no informativo y no altera el contenido.
- **FR-006**: La exportación DEBE aplicar, sin redefinirlas, las reglas de contenido, herencia, selección, relevancia y orden de SPEC-004 (FR-006–FR-011) y SPEC-003 (D1–D3). El título y el resumen son los propios del perfil, sin fallback al Maestro ni a otro perfil; los datos personales y de contacto se heredan del Maestro en lectura; las fechas conservan su precisión disponible; los estados de vigencia y en curso se muestran como en la vista; los campos vacíos se omiten.
- **FR-007**: El PDF DEBE soportar una o más páginas sin truncar, ocultar ni solapar contenido. Los saltos de página DEBEN ser naturales: no deben cortar líneas, palabras, enlaces ni elementos de forma que se pierda información, y los encabezados de sección no deben quedar aislados al final de una página sin su primer elemento. Un elemento largo puede continuar en la página siguiente sin repetir datos ni inventar contenido. NO DEBEN generarse páginas en blanco evitables.
- **FR-008**: El contenido textual del PDF DEBE ser texto real y seleccionable, no una imagen rasterizada, y DEBE conservar completos los acentos, eñes, signos y caracteres especiales presentes en los datos. Las cadenas largas sin espacios, como las URL, DEBEN ajustarse o dividirse sin desbordar la caja de texto ni recortarse.

#### Enlaces

- **FR-009**: Los enlaces que la vista previa presenta como clicables DEBEN conservarse en el PDF como enlaces clicables cuando el formato lo permita, con el mismo texto visible y el mismo destino. Esto incluye, cuando existan, teléfono (siempre como texto visible y, cuando el renderizador y el lector lo permitan, como enlace `tel:`), correo electrónico, LinkedIn, GitHub, sitio web, enlaces adicionales, validación de certificación y URL de proyecto. Si el teléfono no puede conservarse como enlace, permanece como texto visible. Los enlaces internos de corrección de la vista previa NO DEBEN formar parte del PDF.
- **FR-010**: Los enlaces del PDF DEBEN ser anotaciones válidas y seguras: NO DEBEN introducir JavaScript, formularios, adjuntos, acciones automáticas, contenido remoto ni destinos no seguros. Los valores que no sean URL válidas y seguras NO DEBEN convertirse en enlaces activos. Los metadatos del archivo NO DEBEN incluir relevancia, estado, rutas internas, identificadores de configuración ni datos de otros perfiles.

#### Nombre, descarga y no persistencia

- **FR-011**: El archivo DEBE descargarse con un nombre determinista, consistente y seguro, derivado del nombre del perfil seleccionado. La regla es: `cv-` + base normalizada, donde la base se obtiene transliterando el nombre a ASCII, pasando a minúsculas, sustituyendo cualquier secuencia de caracteres no alfanuméricos por un guion, colapsando guiones repetidos, eliminando guiones al inicio y al final, truncando a 80 caracteres y eliminando cualquier guion final que hubiera quedado; se añade una sola extensión `.pdf`. Si la base queda vacía, el nombre DEBE ser `cv.pdf`. El nombre final solo contiene `[a-z0-9-]` y `.pdf`, sin rutas, separadores de directorio, `..`, comillas, caracteres de control ni secuencias de escape. La misma entrada DEBE producir siempre el mismo nombre. El nombre del perfil se usa solo para el archivo y NO se imprime en el documento.
- **FR-012**: La descarga DEBE entregar el PDF completo como archivo descargable de tipo `application/pdf`, con el nombre definido en FR-011. El archivo NO DEBE persistirse, almacenarse, versionarse, compartirse ni quedar accesible por una URL posterior; NO DEBE existir historial, copia temporal recuperable ni registro del contenido. La única copia es la que recibe el usuario.
- **FR-013**: La generación DEBE ser local, sin servicios externos, sin conexión a internet, sin IA y sin envío de datos. El PDF DEBE ser autocontenido: fuentes embebidas, sin recursos remotos ni dependencias de red para abrirse o visualizarse.

#### Errores, validez y alcance

- **FR-014**: Antes de entregar el archivo, el sistema DEBE verificar que el PDF es válido y completo: estructura PDF reconocible, páginas presentes, contenido legible y ausencia de errores de generación. El archivo entregado DEBE abrirse correctamente en un lector de PDF estándar, mostrar todas sus páginas y no estar protegido por contraseña ni restricciones que impidan su apertura. Si la verificación falla, NO DEBE entregarse el archivo ni una versión parcial, truncada o corrupta.
- **FR-015**: Si la generación o la validación falla, el sistema DEBE mostrar un mensaje claro fuera del documento, NO DEBE iniciar ni completar una descarga corrupta o parcial y DEBE permitir reintentar. NO DEBE presentar un PDF incompleto como válido. Cuando sea posible, la selección del perfil DEBE conservarse para el reintento.
- **FR-016**: La exportación NO DEBE modificar el Perfil Maestro, los perfiles de CV, sus asociaciones, datos, documentos ni configuración; NO DEBE crear tablas, columnas, migraciones, registros, archivos persistidos ni historial. La vista previa de SPEC-004 DEBE conservar su comportamiento, su composición y su carácter de solo lectura.
- **FR-017**: La capacidad NO DEBE ofrecer ni realizar certificados anexos, concatenación de PDFs, OCR, múltiples diseños o plantillas, firma digital, publicación, envío del PDF por correo, compartición, CV documentado, edición del PDF, historial, versionado, ATS, adaptación a ofertas ni generación de contenido por IA. Tampoco DEBE añadir opciones de diseño, personalización gráfica, variantes por perfil ni una acción de impresión integrada en la aplicación.

### Key Entities _(include if feature involves data)_

- **Documento PDF del CV (efímero)**: representación descargable y temporal del CV de un perfil activo, generada bajo demanda a partir de la misma composición de SPEC-004. Contiene el contenido fiel, el orden, la jerarquía A4, la tipografía embebida, los enlaces del CV y el nombre de archivo seguro. No tiene identidad persistente, almacenamiento, historial, versión, URL compartible ni contenido editable; no es una fuente de datos y no se conserva después de la descarga.

No se introduce ninguna entidad persistente nueva. El perfil activo, el Perfil Maestro y sus registros son entradas existentes de SPEC-004, SPEC-003 y SPEC-001 y no se redefinen.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: La comparación campo a campo entre la vista previa y el PDF de un perfil configurado no encuentra diferencias de secciones, campos, elementos, orden ni jerarquía; no aparece ningún elemento excluido, contenido de otro perfil ni metadato de relevancia o estado.
- **SC-002**: Un CV que excede una página se descarga como PDF multipágina en el que todo el contenido de la vista aparece completo, sin truncamientos, solapamientos ni pérdida de líneas, y sin encabezados de sección aislados al final de página.
- **SC-003**: El archivo generado es un PDF válido de tamaño A4 vertical, una sola columna y márgenes consistentes, con fuentes embebidas, que abre sin errores en al menos dos lectores estándar, muestra todas sus páginas y no contiene JavaScript, formularios, adjuntos, cifrado que impida la apertura ni recursos remotos.
- **SC-004**: La inspección de anotaciones del PDF encuentra el mismo conjunto de destinos externos que los enlaces clicables de la vista previa —correo, LinkedIn, GitHub, sitio web, enlaces adicionales, validación de certificación y URL de proyecto—, conserva el teléfono visible y, cuando el formato lo permita, como enlace `tel:`, y no incluye ningún enlace interno de corrección.
- **SC-005**: Para una muestra de nombres de perfil con acentos, espacios, barras, comillas, puntos, caracteres de control, emojis, vacío y longitud máxima, el nombre descargado cumple la regla de FR-011, no contiene rutas ni caracteres peligrosos y es idéntico al repetir la exportación.
- **SC-006**: En los estados sin datos, sin perfil activo, perfil no disponible, error y fallo simulado de generación o validación, no se descarga ningún archivo; se muestra un mensaje claro fuera del documento y el reintento funciona sin dejar archivos parciales.
- **SC-007**: Después de exportar, no existe ningún archivo persistente, registro, tabla, migración ni historial; la generación funciona con la red desconectada y no se envía información a servicios externos.
- **SC-008**: Con los perfiles Analista y Programador activos, cada uno produce un PDF válido y distinto según su configuración, cubriendo el paso 13 del criterio de éxito del MVP; la vista previa de SPEC-004 mantiene su comportamiento de solo lectura y la hoja no incorpora controles de exportación, edición ni plantilla.

## Pruebas Requeridas

**Clasificación**: las comprobaciones de estructura y validez, contenido y orden, número de páginas, enlaces y anotaciones, nombre de archivo, disponibilidad y errores, no persistencia y regresión deben cubrirse con automatización. La apertura en al menos dos lectores estándar, la revisión visual de paginación, márgenes y tipografía, y la comprobación de foco y teclado quedan como validación manual.

### Automatizables

- **Fidelidad de contenido y orden**: con un Perfil Maestro y un perfil activo que incluyan las cinco clases, elementos incluidos y excluidos, relevancias distintas, título y resumen propios, datos heredados y otro perfil con contenido distinto, exportar y comparar con la vista previa: mismas secciones, campos, elementos y orden; sin excluidos, sin otro perfil, sin relevancia o estado, sin campos vacíos y sin datos inventados.
- **Validez estructural**: comprobar que el archivo es un PDF completo y no vacío, de tamaño A4 vertical, con el número de páginas esperado y fuentes embebidas; verificar que no contiene JavaScript, formularios, adjuntos, contraseña ni recursos remotos; comprobar que la validación previa a la entrega rechaza un archivo truncado o corrupto.
- **Multipágina**: generar un CV con muchas entradas y textos largos; comprobar el número de páginas, que todo el contenido aparece completo y una sola vez, que no se generan páginas en blanco evitables y que repetir la exportación produce un resultado estable. La revisión visual de cortes, solapamientos y encabezados huérfanos queda en la validación manual.
- **Enlaces y anotaciones**: incluir teléfono, correo, LinkedIn, GitHub, sitio web, enlaces adicionales, validación de certificación y URL de proyecto; inspeccionar las anotaciones y comprobar que cada una conserva el mismo destino y el texto visible; comprobar que el teléfono se conserva como texto y, si el generador emite la anotación, que su destino `tel:` es correcto; verificar que no aparece ningún enlace interno de corrección; probar una URL larga y un valor no seguro y comprobar que no se activa como enlace.
- **Nombre de archivo**: probar nombres de perfil con acentos, ñ, espacios, `/`, `\`, `..`, comillas, puntos, caracteres de control, emojis, vacío y 100 caracteres; comprobar la regla `cv-{base}.pdf` o `cv.pdf`, la ausencia de rutas y caracteres peligrosos, la estabilidad del nombre y la seguridad del encabezado de descarga.
- **Disponibilidad, errores y reintento**: probar sin perfiles activos, sin selección, `ready` sin datos, perfil desactivado entre la vista y la exportación, datos eliminados, acceso directo a la exportación sin perfil válido, fallo simulado del renderizador y fallo de validación; comprobar ausencia de acción o de archivo, mensaje fuera del documento, reintento y conservación de la selección.
- **Seguridad, privacidad y no persistencia**: después de una exportación exitosa y de un fallo, comprobar que no queda archivo, temporal recuperable, registro, historial ni cambio en la base; que no hay llamadas externas; que el PDF no contiene rutas internas, metadatos de configuración ni datos de otros perfiles; y que la generación funciona sin red.
- **Regresión y alcance**: actualizar las pruebas de regresión que verificaban la ausencia de exportación en la sección para reconocer la nueva acción; ejecutar las pruebas de SPEC-004 y comprobar que la vista previa sigue siendo de solo lectura, que la acción de exportación está fuera de la hoja, que sigue sin haber controles de edición, plantilla o exportación dentro de la hoja y que no se añadieron anexos, concatenación, OCR, firma, publicación, correo, ATS, IA, migraciones ni cambios de modelo.

### Validación manual

- **Apertura y legibilidad**: abrir el PDF en al menos dos lectores estándar y comprobar que muestra todas las páginas, que las fuentes se ven correctamente y que los enlaces, incluido el teléfono cuando el lector lo admita, funcionan.
- **Maquetación**: revisar visualmente una sola columna, paginación, márgenes, jerarquía, tipografía, cortes, solapamientos, encabezados huérfanos y ausencia de contenido fuera de los bordes.
- **Accesibilidad de la acción**: comprobar que «Exportar PDF» es operable por teclado, tiene nombre accesible y foco visible.

## Assumptions

- Aplicación local monousuario con un único Perfil Profesional Maestro, conforme a SPEC-001 y a los Principios XII y XXIII de la Constitución.
- SPEC-004 está implementado e integrado en la base de trabajo; esta capacidad consume su composición, sus estados y su contrato sin reabrirlos ni modificarlos.
- El PDF es un artefacto final, basado en texto real y seleccionable, no editable; no se exige PDF/A, PDF etiquetado, firma digital, cifrado ni contraseña.
- La familia y el tamaño tipográfico, los márgenes exactos, el interlineado, la numeración de página, el algoritmo de paginación y el mecanismo local de renderizado son decisiones de Plan dentro de las restricciones de C3, C6 y C8.
- No se definen objetivos de rendimiento medibles: la aplicación es local y monousuario; se espera que un CV se genere en un tiempo razonable en el equipo local.
- El nombre del perfil se usa solo para el nombre del archivo; no se imprime en el documento, conforme a SPEC-004.
- Si los datos cambian entre la vista previa y la exportación, el PDF refleja los datos vigentes en el momento de la exportación; no se usa una copia obsoleta y la vista puede actualizarse.
- La única copia del PDF es la que recibe el usuario; no se conserva ninguna copia en el servidor ni se comparte por una URL.
- La acción de exportación vive fuera de la hoja del CV; la vista previa conserva su carácter de solo lectura y su única plantilla.
- El contenido es en español y admite acentos, eñes, signos y caracteres especiales del alfabeto latino; la fuente embebida debe cubrirlos.
- Los enlaces se conservan como anotaciones cuando el lector de PDF las admite; el texto visible y el destino quedan registrados aunque un lector concreto no haga clicable la anotación.
- No se añaden clases de contenido, campos, relaciones ni entidades nuevas; no hay migraciones ni cambios en SPEC-001, SPEC-003 o SPEC-004.
- No se exige accesibilidad PDF/UA ni una estructura etiquetada; se exige texto legible, orden de lectura razonable en una columna y enlaces identificables.

## Dependencias

- **SPEC-004 — Generador de CV**: dependencia principal. Aporta la vista previa derivada, la composición (`CvPreviewContent::build()` o el contrato equivalente), la selección del perfil activo, los estados, la única plantilla, la no persistencia y el carácter de solo lectura.
- **SPEC-001 — Perfil Profesional Maestro** y **SPEC-003 — Perfiles de CV**: fuentes y reglas consumidas a través de SPEC-004; no se redefinen ni se modifican.
- **Constitución**: Principios I, II, III, XVI, XVIII, XXI, XXIII, XXV y XXVI; criterio de éxito del MVP, pasos 12–13.
- **Renderizador local de PDF**: se requiere un mecanismo local que produzca PDF con fuentes embebidas y anotaciones de enlace; no se permite un servicio externo ni una dependencia de red. La selección concreta corresponde a Plan.
- **SPEC-002 y SPEC-006**: no son dependencias funcionales. No se anexan certificados, no se concatenan PDFs, no se aplica OCR y no se genera un CV documentado.
- No se requieren cambios de esquema, migraciones, tablas, seeders ni factories.

## Trazabilidad

| Origen                                                                  | Requisitos                                        | Historias | Observación                                                                 |
| ----------------------------------------------------------------------- | ------------------------------------------------- | --------- | --------------------------------------------------------------------------- |
| Constitución I, II, III, XVI y XXVI; SPEC-004 FR-006–FR-011, FR-015–FR-016 | FR-002, FR-004–FR-007, FR-016                   | US1, US2  | Misma composición y perfil; PDF derivado, fiel y sin segunda lógica.        |
| Constitución XVI y XVIII; roadmap SPEC-005                              | FR-001, FR-004, FR-007                            | US1, US2  | Acción de exportar; A4, una columna, una plantilla, jerarquía y paginación. |
| SPEC-004 FR-005 y FR-017–FR-019; C1                                      | FR-001, FR-003                                    | US3       | Solo perfiles activos con datos; revalidación antes de exportar.            |
| SPEC-004 FR-006–FR-011; SPEC-003 D1–D3                                   | FR-005, FR-006                                    | US1, US2  | Contenido incluido, orden, herencia, datos propios y omisiones.             |
| SPEC-004 FR-020; encargo                                                 | FR-008                                            | US2       | Texto completo, acentos y cadenas largas sin recortes.                      |
| SPEC-004 FR-010, FR-011 y FR-014                                       | FR-009, FR-010                                    | US1, US2  | Enlaces del CV y teléfono clicables cuando el formato lo permita; sin enlaces internos. |
| Constitución XXI; roadmap «validar que el PDF pueda abrirse»            | FR-014, FR-015; Pruebas Requeridas                | US3       | Validez, errores, reintento y ausencia de archivos parciales.               |
| Constitución XXIII y XXV; encargo                                       | FR-013, FR-017                                    | US3       | Generación local, sin servicios externos y alcance acotado.                 |
| SPEC-004 FR-015 y FR-022; encargo                                        | FR-012, FR-016, FR-017                            | US1, US3  | Sin persistencia ni historial; la hoja sigue siendo de solo lectura.        |
| C5; encargo                                                             | FR-011, SC-005                                    | US1, US3  | Nombre determinista, seguro y con fallback.                                 |
| Criterio de éxito del MVP, paso 13                                       | SC-008                                            | US1       | PDF normal descargable y válido.                                            |
| SPEC-002 y SPEC-006                                                      | —                                                 | —         | Sin anexos, concatenación, OCR ni CV documentado.                           |
