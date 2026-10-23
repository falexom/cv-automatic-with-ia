# Feature Specification: Generador de CV

**Feature Branch**: `004-generador-de-cv`

**Created**: 2026-10-23

**Status**: Ready for integration — 2026-10-23; aceptación funcional y visual del MVP por el responsable; T025 con escenarios manuales 1–13 no ejecutados individualmente y documentados; commit local f86d17a; merge --no-ff a main autorizado pero bloqueado por el harness; SPEC no integrado; sin push.

**Input**: User description: "Construir el Generador de CV de SPEC-004: seleccionar un perfil de CV activo y obtener una vista de currículum de solo lectura, derivada de los datos vigentes del Perfil Profesional Maestro y de la configuración del perfil —selección, relevancia y orden—, con una única plantilla profesional, sin PDF, CV documentado, edición directa ni almacenamiento."

## Problema y Objetivo

**Problema**: el proyecto ya permite mantener el Perfil Profesional Maestro y configurar varios perfiles de CV con selección, relevancia y orden propios. Sin embargo, todavía no existe el paso que convierte esa configuración en un currículum revisable. Si el usuario tuviera que armar el CV por fuera del sistema, volvería a copiar datos, podría incluir elementos que no corresponden al perfil, perdería el orden configurado y generaría una segunda fuente de verdad que se desactualizaría en cuanto cambiara el Maestro. Eso contradice el Principio I y el principio rector del producto: «Una sola trayectoria profesional. Múltiples CV. Cero duplicación de información».

**Objetivo**: incorporar una sección «Generador de CV» que permita seleccionar un perfil de CV activo y obtener una vista previa de currículum de solo lectura, compuesta en el momento de la consulta a partir del Perfil Maestro y de la configuración del perfil. La vista deberá aplicar la selección, la relevancia y el orden ya definidos en SPEC-003, omitir estrictamente el contenido no incluido, usar una única plantilla profesional y mantener enlaces a las fuentes para que toda corrección se realice en el perfil o en el Maestro, nunca en el CV.

## Alcance

### Incluido en esta capacidad

- Sección «Generador de CV» accesible desde la navegación principal.
- Selección de un perfil de CV activo, cambio de perfil y regeneración de la vista.
- Vista previa compuesta por datos personales y de contacto, título profesional y resumen propios del perfil, experiencia, educación, cursos y certificaciones, habilidades y proyectos incluidos.
- Aplicación de la selección explícita, la relevancia Alta/Media/Baja y el orden aprobados en SPEC-003, sin reordenamiento manual.
- Omisión estricta de los elementos no incluidos y de las secciones sin contenido.
- Una única plantilla profesional, común a todos los perfiles, sin selector de plantillas.
- Vista de solo lectura, derivada en cada consulta, sin almacenar un CV ni permitir edición directa.
- Enlaces identificables al perfil seleccionado y a las secciones del Perfil Maestro desde las que se corrigen los datos.
- Disponibilidad limitada a perfiles activos; estados vacíos orientativos; manejo de errores de carga; accesibilidad básica.
- Composición local, sin envío de datos a servicios externos ni generación de contenido por IA.

### Fuera de alcance

- Exportación a PDF, impresión, descarga, envío o publicación del CV.
- CV documentado, concatenación de certificados o cualquier asociación de documentos y evidencias al CV.
- Múltiples plantillas, selector de plantillas, variantes visuales por perfil, marketplace de plantillas o personalización gráfica.
- Editor visual, arrastrar y soltar, edición del CV como documento independiente, guardado de versiones o historial.
- Análisis ATS, adaptación automática a ofertas laborales, postulaciones, cartas de presentación y generación de contenido mediante IA.
- Almacenamiento de un CV generado, de una copia del contenido del Maestro o de un documento descargable.
- Gestión de perfiles de CV —creación, edición, activación, desactivación— y gestión del Perfil Maestro; pertenecen a SPEC-003 y SPEC-001 respectivamente.
- Autenticación, multiusuario, sincronización externa, servicios cloud y analítica avanzada.
- Nuevas clases de contenido profesional, nuevos campos del Maestro o cambios en las entidades existentes.

## Clarifications

### Decisiones ya resueltas aplicables

- **C1 — Disponibilidad por estado del perfil (SPEC-003, D2/FR-016)**: solo los perfiles activos se ofrecen y se previsualizan. Los perfiles inactivos conservan su configuración, siguen visibles y editables en la gestión, no se eliminan y pueden reactivarse; al reactivarse vuelven a estar disponibles. Esta capacidad consume ese contrato y no implementa la gestión de perfiles.
- **C2 — Selección explícita y orden (SPEC-003, D1/FR-009–FR-012)**: solo los elementos incluidos explícitamente forman parte del contenido del perfil. Cada elemento tiene relevancia Alta, Media o Baja. El orden es descendente por relevancia y, en los empates, conserva el orden del Perfil Maestro. No existe orden manual ni reordenamiento de secciones.
- **C3 — Datos propios y herencia (SPEC-003, D3/FR-014–FR-015)**: el título profesional y el resumen son propios del perfil, opcionales y no se copian del Maestro. Los datos personales y de contacto se heredan del Maestro en modo lectura, sin copia ni sobrescritura por perfil. El título propio del perfil sustituye al del Maestro en la vista; si está vacío, no se usa el del Maestro como respaldo.
- **C4 — El CV es un resultado (Constitución I, II, III, XVI, XXVI)**: la vista se deriva de las fuentes, no se edita como documento y no se almacena. Toda corrección se realiza en el perfil o en el Maestro. Se usa una única plantilla profesional y la vista previa precede a cualquier exportación futura.
- **C5 — Alcance y privacidad (Constitución IV, XII, XVIII, XXIII, XXV)**: sin PDF, CV documentado, múltiples plantillas, IA, ATS, adaptación a ofertas ni postulaciones. La composición es local y no requiere servicios externos.
- **C6 — Fechas y estados (SPEC-001; SPEC-003 FR-012)**: se utilizan las fechas disponibles del Maestro con su precisión parcial y los estados de vigencia o en curso ya registrados. No se introduce precisión, contenido ni interpretación nuevos.
- **C7 — Relevancia y estado como metadatos (consecuencia de C1, C2 y C4)**: la relevancia de cada elemento y el estado del perfil son configuración interna. La relevancia se usa para el orden y el estado para la disponibilidad; ninguno forma parte del contenido visible del CV.

### Session 2026-10-23

- Revisión de contraste con la Constitución, el roadmap de SPEC-004 y los contratos cerrados de SPEC-003 (D1–D3, FR-009–FR-016). Se confirman las decisiones aplicables C1–C7, ya resueltas por SPEC-003 y la Constitución.
- No se identificaron contradicciones ni duplicaciones materiales entre historias, requisitos, criterios de éxito y pruebas.
- No se requirió intervención humana: no se identificaron decisiones funcionales de negocio imprescindibles sin resolver.
- No quedan decisiones funcionales pendientes para Plan.
- La autorización para continuar el ciclo completo proviene del encargo del usuario y no equivale a validación manual ni a aprobación de resultados.
- La implementación queda en pausa para revisión documental previa a Implement; no se ha ejecutado código ni pruebas.

### Pendientes

- Ninguna.

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Generar la vista previa de un perfil activo (Priority: P1)

Como usuario, quiero seleccionar un perfil de CV activo y obtener su vista previa, para revisar el currículum que resulta de mi trayectoria y de la configuración del perfil sin armarlo manualmente.

**Why this priority**: es el corazón de la capacidad: convierte la configuración de SPEC-003 en un resultado revisable. Sin esta historia no existe generador y el valor de los perfiles queda limitado a su edición.

**Independent Test**: con un Perfil Maestro que tenga al menos un elemento de cada clase y un perfil activo con título, resumen y una selección de elementos con relevancias distintas, abrir el Generador de CV, seleccionar el perfil y comprobar que la vista incluye datos personales y de contacto, título y resumen propios, las cinco secciones con solo los elementos incluidos, el orden configurado, una única plantilla y ningún control de edición.

**Acceptance Scenarios**:

1. **Dado** un Perfil Maestro con datos y un perfil activo con contenido incluido, **Cuando** el usuario selecciona el perfil en el Generador de CV, **Entonces** la vista muestra los datos personales y de contacto, el título y el resumen propios, y las secciones de experiencia, educación, cursos y certificaciones, habilidades y proyectos con los elementos incluidos.
2. **Dado** un perfil con elementos no incluidos y otro perfil con contenido distinto, **Cuando** el usuario consulta la vista, **Entonces** los elementos no incluidos y el contenido del otro perfil no aparecen.
3. **Dados** varios elementos incluidos con relevancia Alta, Media y Baja, **Cuando** el usuario consulta la vista, **Entonces** cada sección respeta la relevancia descendente y, en los empates, el orden del Perfil Maestro.
4. **Dado** un perfil activo sin título o sin resumen, **Cuando** el usuario consulta la vista, **Entonces** el campo vacío se omite y no se sustituye por el dato del Maestro ni por texto inventado.
5. **Dada** la vista generada, **Cuando** el usuario busca acciones para editar, reordenar o guardar el CV, **Entonces** no las encuentra; la edición se realiza en las fuentes enlazadas.
6. **Dada** la vista generada, **Cuando** el usuario busca opciones de plantilla, **Entonces** comprueba que se aplica una única plantilla y no existe selector.

---

### User Story 2 - Cambiar de perfil y regenerar la vista (Priority: P2)

Como usuario, quiero cambiar el perfil seleccionado y regenerar la vista, para comparar cómo se presenta la misma trayectoria en perfiles con objetivos distintos.

**Why this priority**: el MVP prevé al menos los perfiles Analista y Programador/Desarrollador. La capacidad solo demuestra su valor si el usuario puede alternar entre perfiles y obtener resultados distintos sin duplicar datos ni reordenar a mano.

**Independent Test**: con dos perfiles activos que tengan selecciones, relevancias, títulos o resúmenes distintos, alternar entre ellos y comprobar que la vista se regenera por completo con la configuración del perfil elegido, sin residuos del anterior, sin mezclar contenido y sin que se cree un CV almacenado.

**Acceptance Scenarios**:

1. **Dados** dos perfiles activos con configuraciones distintas, **Cuando** el usuario cambia del primero al segundo, **Entonces** la vista se regenera con el título, el resumen, el contenido y el orden del segundo.
2. **Dado** un mismo elemento incluido en ambos perfiles con relevancia distinta, **Cuando** el usuario alterna entre perfiles, **Entonces** el orden refleja la relevancia de cada perfil.
3. **Dado** un cambio de perfil, **Cuando** el usuario revisa la vista, **Entonces** no queda contenido, encabezado ni sección del perfil anterior.
4. **Dado** un cambio de perfil, **Cuando** el usuario vuelve al primero, **Entonces** la vista se reconstruye desde sus fuentes y no desde una copia guardada.

---

### User Story 3 - Mantener el CV como vista derivada y trazable (Priority: P2)

Como usuario, quiero que la vista use siempre los datos vigentes y me permita llegar a las fuentes, para confiar en el CV y corregir la información donde corresponde.

**Why this priority**: la vista sería peligrosa si mostrara copias obsoletas o si invitara a editarla como documento. El Principio I exige que toda corrección se haga en el Maestro o en el perfil y que la siguiente consulta refleje el cambio.

**Independent Test**: abrir una vista, modificar datos personales y un registro profesional en el Maestro, y el título, el resumen o la relevancia en el perfil; volver a consultar y comprobar que todo cambio se refleja; eliminar un registro maestro referenciado y comprobar que desaparece; seguir los enlaces a las fuentes y comprobar que no se creó ningún CV persistido.

**Acceptance Scenarios**:

1. **Dado** un dato personal o un registro maestro actualizado, **Cuando** el usuario vuelve a consultar o regenerar la vista, **Entonces** ve la información actualizada sin sincronización manual.
2. **Dado** un cambio en el título, el resumen, la selección o la relevancia del perfil, **Cuando** el usuario regenera la vista, **Entonces** el cambio se refleja solo en ese perfil.
3. **Dado** un elemento maestro referenciado que fue eliminado, **Cuando** el usuario consulta la vista, **Entonces** el elemento ya no aparece y el resto del CV permanece; no se muestra una copia obsoleta.
4. **Dado** un dato personal o un enlace eliminado en el Maestro, **Cuando** el usuario consulta la vista, **Entonces** deja de mostrarse.
5. **Dada** la vista generada, **Cuando** el usuario sigue un enlace al perfil o a una sección del Maestro, **Entonces** llega a la fuente correspondiente y puede corregir allí; el CV no ofrece edición directa.
6. **Dado** que el usuario abandona y vuelve a abrir el Generador de CV, **Cuando** selecciona un perfil, **Entonces** la vista se calcula de nuevo y no existe historial ni versión guardada.

---

### User Story 4 - Entender la disponibilidad, los estados vacíos y los errores (Priority: P3)

Como usuario, quiero que el generador me explique qué hacer cuando no hay un perfil disponible, falta contenido o falla la carga, para no quedarme con una pantalla vacía, datos inventados o una vista engañosa.

**Why this priority**: los estados incompletos son normales en un sistema local que se va completando. La capacidad debe seguir siendo comprensible y honesta, sin convertir la ausencia de datos en contenido ficticio ni en un error opaco.

**Independent Test**: comprobar el generador sin perfiles activos, con todos los perfiles inactivos, con un perfil activo sin contenido, con un Perfil Maestro vacío, con secciones sin elementos, con campos opcionales vacíos y ante un fallo de carga; verificar que cada caso muestra orientación o error fuera del CV, sin inventar datos y sin presentar una vista parcial como válida.

**Acceptance Scenarios**:

1. **Dado** que no existe ningún perfil activo, **Cuando** el usuario abre el Generador de CV, **Entonces** no se muestra una vista previa y aparece un estado vacío orientativo con acceso a la gestión de perfiles para crear o reactivar uno.
2. **Dado** un perfil activo sin contenido incluido y un Perfil Maestro con datos personales, **Cuando** el usuario selecciona el perfil, **Entonces** la vista muestra solo los datos disponibles; las secciones sin elementos no aparecen dentro del CV y la orientación para agregar contenido se muestra fuera del CV.
3. **Dado** un Perfil Maestro sin datos personales ni contenido y un perfil con título o resumen, **Cuando** el usuario consulta la vista, **Entonces** solo se muestran los datos propios del perfil y no se inventan datos personales, fechas ni secciones.
4. **Dado** un perfil activo sin ningún dato disponible, **Cuando** el usuario consulta, **Entonces** se muestra un estado vacío en lugar de un CV en blanco, con orientación fuera del CV.
5. **Dado** un perfil seleccionado que se desactiva antes de regenerar, **Cuando** el usuario vuelve a consultar, **Entonces** no se muestra su vista y se ofrecen los perfiles activos o el estado vacío.
6. **Dado** un fallo de carga o generación, **Cuando** el usuario abre la vista, **Entonces** se informa del problema, no se presenta una vista parcial o desactualizada como válida y se ofrece reintentar.
7. **Dado** un perfil con campos opcionales vacíos, **Cuando** el usuario consulta la vista, **Entonces** no aparecen etiquetas, separadores ni textos de relleno para esos campos.

### Edge Cases

- **Perfil Maestro vacío**: la vista se limita a los datos propios del perfil; no se inventan datos personales, fechas, secciones ni contenido, y se orienta fuera del CV para completar el Maestro.
- **Perfil activo sin contenido**: es un estado válido; se muestra lo disponible y se orienta fuera del CV para agregar contenido. No se considera un error.
- **Perfil inactivo o sin perfiles activos**: el perfil inactivo no se ofrece ni se previsualiza; si no hay ningún activo, se muestra un estado vacío con acceso a la gestión de perfiles. Un perfil puede reactivarse y volver a estar disponible.
- **Perfil seleccionado que se desactiva antes de regenerar**: la vista deja de mostrarse y se ofrecen los perfiles activos disponibles o el estado vacío; no se muestra una vista obsoleta.
- **Sin selección con perfiles activos**: se solicita la selección; no se muestra una vista previa hasta que el usuario elija un perfil.
- **Secciones sin elementos**: no aparecen dentro del cuerpo del CV; la orientación para agregarlas se muestra fuera del CV.
- **Campos opcionales vacíos**: no se muestran etiquetas, separadores, guiones, «N/D» ni espacios reservados; tampoco se completan con datos del Maestro o de otro perfil.
- **Textos largos, acentos y caracteres especiales**: se conservan y se muestran completos, sin truncamientos que oculten información; la vista permite desplazamiento vertical cuando el contenido excede la pantalla.
- **Datos actualizados**: los cambios en el Maestro o en el perfil se reflejan en la siguiente consulta o regeneración, sin sincronización manual y sin copias obsoletas.
- **Referencias eliminadas**: si se elimina un elemento maestro referenciado, deja de aparecer en el CV; si se elimina un dato personal o un enlace, deja de mostrarse. El resto de la vista permanece.
- **Cambio de perfil**: la vista se regenera por completo; no se mezcla contenido del perfil anterior ni se conserva una copia.
- **Fallo de carga o generación**: se informa del problema, no se presenta una vista parcial o desactualizada como válida y se permite reintentar sin pérdida silenciosa de la selección cuando sea posible.
- **CV extenso**: la vista se adapta al espacio disponible y permite desplazamiento vertical; no se trunca ni se oculta contenido para forzar una sola pantalla.

## Requirements _(mandatory)_

### Functional Requirements

#### Acceso, selección y regeneración

- **FR-001**: La aplicación DEBE ofrecer una sección «Generador de CV» accesible desde la navegación principal. La sección DEBE permitir seleccionar un perfil de CV activo y consultar su vista previa en el mismo lugar.
- **FR-002**: El selector DEBE mostrar únicamente perfiles de CV activos, identificados por su nombre y, cuando exista, su título profesional. Los perfiles inactivos NO DEBEN mostrarse en el selector, seleccionarse ni previsualizarse.
- **FR-003**: La vista previa DEBE generarse solo para un perfil activo seleccionado explícitamente. Si existen perfiles activos pero ninguno está seleccionado, el sistema DEBE solicitar la selección y NO DEBE mostrar una vista previa.
- **FR-004**: El sistema DEBE permitir cambiar el perfil seleccionado y regenerar la vista con los datos y la configuración vigentes del nuevo perfil, sin conservar, mezclar ni reutilizar contenido del perfil anterior. El perfil seleccionado DEBE permanecer identificable durante la consulta.
- **FR-005**: Si el perfil seleccionado deja de estar activo o deja de estar disponible antes de generar la vista, el sistema NO DEBE mostrar su vista previa y DEBE ofrecer los perfiles activos disponibles o el estado vacío correspondiente.

#### Composición y fidelidad del CV

- **FR-006**: La vista previa DEBE componerse exclusivamente con datos vigentes del Perfil Maestro y del perfil seleccionado, y DEBE presentarlos en este orden: datos personales y de contacto, título profesional propio, resumen propio, experiencia, educación, cursos y certificaciones, habilidades y proyectos. NO DEBE incluir contenido de otros perfiles ni registros maestros que no estén incluidos en el perfil; los datos personales y de contacto se rigen por FR-010.
- **FR-007**: Solo los elementos incluidos explícitamente en el perfil DEBEN aparecer en el CV. Los elementos no incluidos NO DEBEN mostrarse en ninguna forma, ni como contenido, ni como referencia, ni como marcador. Las secciones sin elementos incluidos NO DEBEN aparecer en el cuerpo del CV.
- **FR-008**: El orden de los elementos dentro de cada sección DEBE ser el definido en SPEC-003: relevancia descendente y, en caso de empate, orden del Perfil Maestro. El orden DEBE ser estable y reproducible. NO DEBE existir reordenamiento manual ni controles de orden en el generador. La relevancia es un metadato de configuración y NO DEBE mostrarse como contenido del CV.
- **FR-009**: El título profesional y el resumen del CV DEBEN ser los propios del perfil seleccionado. Si alguno está vacío, NO DEBE mostrarse ni sustituirse por el del Perfil Maestro, por el de otro perfil ni por texto inventado.
- **FR-010**: Los datos personales y de contacto DEBEN provenir del Perfil Maestro vigente, en modo lectura, sin copia ni sobrescritura por perfil. Los campos opcionales vacíos NO DEBEN mostrarse. Los enlaces de contacto y los enlaces adicionales DEBEN mostrarse como enlaces identificables cuando existan.
- **FR-011**: Para cada elemento incluido, la vista DEBE mostrar la información registrada en el Maestro que corresponda a su clase: en experiencia, cargo, empresa, fechas disponibles o indicación de vigente, descripción, responsabilidades y logros; en educación, institución, programa, grado o nivel, fechas disponibles o indicación de en curso, y descripción; en cursos y certificaciones, nombre, institución, fecha, duración, categoría, código e identificación de validación; en habilidades, nombre y categoría; en proyectos, nombre, rol, fechas disponibles, descripción, resultados y enlace. Los campos opcionales vacíos NO DEBEN mostrarse y NO DEBEN añadirse textos, fechas ni logros no registrados.
- **FR-012**: La vista DEBE usar una única plantilla profesional, común a todos los perfiles, sin selector de plantillas, variantes ni personalización visual por perfil.
- **FR-013**: La vista DEBE ser de solo lectura. NO DEBE contener campos editables, controles de arrastre, acciones de guardado ni posibilidad de modificar el CV como documento independiente. Cualquier corrección DEBE realizarse en el perfil o en la sección del Maestro correspondiente.
- **FR-014**: La sección DEBE ofrecer enlaces identificables al perfil seleccionado y a las secciones del Maestro desde las que se editan los datos mostrados, para que el usuario pueda corregir en la fuente. Los enlaces NO DEBEN introducir edición dentro del CV.

#### Derivación, vigencia y trazabilidad

- **FR-015**: La vista DEBE derivarse en cada consulta a partir de las fuentes vigentes. NO DEBE almacenarse, persistirse ni conservarse un CV generado, ni existir historial, versionado o copia descargable del resultado. La composición DEBE realizarse localmente, sin envío de datos a servicios externos ni generación de contenido por IA.
- **FR-016**: Los cambios realizados en el Perfil Maestro o en el perfil seleccionado DEBEN reflejarse en la siguiente consulta o regeneración de la vista, sin sincronización manual. Si un elemento maestro referenciado se elimina, DEBE dejar de aparecer; si un dato personal o un enlace se elimina, DEBE dejar de mostrarse.

#### Estados, errores y accesibilidad

- **FR-017**: Si no existe ningún perfil activo, el sistema NO DEBE generar una vista previa y DEBE mostrar un estado vacío orientativo con acceso a la gestión de perfiles para crear o reactivar uno.
- **FR-018**: Un perfil activo sin contenido incluido, o un Perfil Maestro sin datos, DEBE producir una vista válida con los datos disponibles. Si no hay ningún dato que mostrar, DEBE mostrarse un estado vacío en lugar de un CV en blanco. La orientación para agregar contenido DEBE mostrarse fuera del cuerpo del CV y NO DEBEN inventarse datos personales, fechas, secciones ni contenido.
- **FR-019**: Si la carga o generación de la vista falla, el sistema DEBE informar del problema, NO DEBE presentar una vista parcial o desactualizada como válida y DEBE permitir reintentar sin pérdida silenciosa de la selección cuando sea posible.
- **FR-020**: Los textos largos, acentos y caracteres especiales DEBEN conservarse y mostrarse correctamente, sin truncamientos que oculten información. La vista DEBE adaptarse al espacio disponible y permitir el desplazamiento vertical cuando el contenido exceda la pantalla.
- **FR-021**: La sección y la vista previa DEBEN cumplir accesibilidad básica: encabezados semánticos jerárquicos, estructura comprensible, enlaces con texto que identifique su destino, foco visible, operabilidad por teclado y alternativas textuales cuando existan elementos no textuales. La información NO DEBE depender exclusivamente del color.

#### Límites de la vista

- **FR-022**: La vista NO DEBE ofrecer acciones de exportación, impresión, descarga, generación de PDF, CV documentado, concatenación de documentos, asociación de documentos o evidencias al CV, envío, publicación ni postulaciones. Tampoco DEBE ofrecer editor visual, arrastrar y soltar, análisis ATS ni adaptación a ofertas laborales.

### Key Entities _(include if feature involves data)_

- **Vista de CV generada**: resultado derivado y efímero que se compone en cada consulta a partir del perfil seleccionado y de los datos vigentes del Maestro, aplicando la selección, la relevancia, el orden y la única plantilla. No tiene identidad persistente, contenido editable, historial ni archivo asociado; no es una fuente de datos.
- **Perfil de CV activo (existente, SPEC-003)**: entrada de la generación. Aporta su nombre para identificarlo en el selector, su título profesional y resumen propios, su selección de elementos, la relevancia de cada uno y su estado activo. No contiene copias de datos profesionales ni de datos personales heredados.
- **Perfil Profesional Maestro y registros maestros (existentes, SPEC-001)**: fuente única de verdad. Aporta datos personales y de contacto, experiencia, educación, cursos y certificaciones, habilidades y proyectos, con sus fechas, estados de vigencia y enlaces. La vista los lee en vivo y no los modifica.

No se introduce ninguna entidad persistente nueva ni se redefinen las entidades de SPEC-001 o SPEC-003.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: La vista de un perfil activo coincide exactamente con su configuración: muestra todos los elementos incluidos y ninguno de los no incluidos; las secciones sin elementos no aparecen dentro del CV.
- **SC-002**: El orden visible de cada sección es relevancia descendente y, en los empates, orden del Maestro; repetir la consulta produce el mismo orden y no existe ningún control de reordenamiento manual.
- **SC-003**: Cambiar entre dos perfiles activos produce vistas diferentes cuando sus configuraciones difieren, sin residuos del perfil anterior y sin pasos de sincronización.
- **SC-004**: Tras modificar o eliminar datos en el Maestro o en el perfil, la siguiente consulta refleja el cambio; no se observan datos obsoletos en ninguna sección.
- **SC-005**: La capacidad no crea ni conserva un CV, PDF o documento; no existe historial ni versionado; la vista no ofrece edición, exportación, impresión ni selector de plantilla; se usa una sola plantilla profesional.
- **SC-006**: Los perfiles inactivos no se ofrecen ni se previsualizan; sin perfiles activos se muestra orientación; un perfil sin contenido o un Maestro vacío produce una vista válida o un estado vacío, sin datos inventados.
- **SC-007**: La sección y la vista presentan encabezados jerárquicos, enlaces con texto identificable y foco visible; pueden recorrerse y operarse por teclado; los textos largos, acentos y caracteres especiales se muestran completos.
- **SC-008**: Con un Perfil Maestro y los perfiles Analista y Programador/Desarrollador activos, el usuario obtiene dos vistas que respetan la configuración de cada perfil y difieren cuando esta difiere, cubriendo los pasos 10–12 del criterio de éxito del MVP —selección de perfil, inclusión o priorización y visualización del CV— sin generar PDF ni versión documentada.

## Pruebas Requeridas

- **Composición y exclusividad**: con un Perfil Maestro que tenga elementos de las cinco clases y un perfil activo con selección, comprobar que la vista muestra datos personales y de contacto, título y resumen propios, y solo los elementos incluidos; verificar que no aparece contenido de otro perfil ni elementos no incluidos.
- **Orden**: incluir elementos con relevancia Alta, Media y Baja y empates; comprobar relevancia descendente, desempate por orden del Maestro y estabilidad entre consultas; verificar que no existen controles de orden manual.
- **Datos propios y herencia**: comprobar título y resumen propios, omisión cuando están vacíos y ausencia de sustitución por el dato del Maestro; verificar datos personales heredados en lectura, sin sobrescritura por perfil, con campos vacíos omitidos.
- **Vigencia**: actualizar datos personales y registros maestros, y el título, resumen, selección o relevancia del perfil; comprobar que la siguiente consulta refleja los cambios sin sincronización manual; eliminar un elemento referenciado, un dato personal o un enlace y comprobar que deja de mostrarse.
- **No persistencia**: comprobar que no se crea ni conserva un CV, que no existe historial ni versionado y que volver a abrir la sección recalcula la vista desde las fuentes.
- **Disponibilidad y estados**: comprobar ausencia de perfiles, todos inactivos, falta de selección con perfiles activos, perfil seleccionado desactivado, perfil sin contenido, Maestro vacío, secciones sin elementos, campos opcionales vacíos y fallo de carga; verificar orientación fuera del CV, ausencia de datos inventados y ausencia de vistas parciales presentadas como válidas.
- **Solo lectura y alcance**: comprobar que la vista no permite editar, reordenar, guardar, exportar, imprimir, descargar, generar PDF, generar un CV documentado, concatenar o asociar documentos y evidencias, enviar, publicar ni ofrecer postulaciones; verificar que se usa una única plantilla, que los enlaces a las fuentes conducen al perfil y a las secciones del Maestro y que no hay análisis ATS, adaptación a ofertas ni contenido generado por IA.
- **Accesibilidad y robustez**: comprobar encabezados semánticos, enlaces con texto identificable, foco visible, operabilidad por teclado, alternativas textuales cuando correspondan, independencia del color y visualización completa de textos largos, acentos y caracteres especiales con desplazamiento vertical.
- **Regresión**: comprobar que la capacidad no modifica el Perfil Maestro, los perfiles de CV ni sus datos, y que no duplica información profesional ni personal.
- **Alineación con el MVP**: con los perfiles Analista y Programador/Desarrollador activos sobre el mismo Maestro, comprobar que el usuario obtiene dos vistas que respetan la configuración de cada perfil y difieren cuando esta difiere, correspondientes a los pasos 10–12 del criterio de éxito del MVP.

## Assumptions

- Aplicación local monousuario con un único Perfil Profesional Maestro, conforme a SPEC-001 y a los Principios XII y XXIII de la Constitución.
- SPEC-001 y SPEC-003 están implementados y cerrados formalmente; esta capacidad consume sus entidades, reglas y contratos sin reabrirlos ni modificarlos.
- La selección del perfil es explícita: la sección no elige un perfil por el usuario. Si hay perfiles activos y ninguno seleccionado, se solicita la selección.
- El nombre del perfil es una etiqueta interna de configuración; el encabezado del CV usa el nombre completo del Maestro y el título profesional propio del perfil. El nombre del perfil no se imprime como título del CV.
- La relevancia es un metadato de configuración que determina el orden; no se imprime como contenido del CV.
- La vista no introduce una entidad persistente ni requiere migraciones; se compone en cada consulta y no se guarda ningún archivo.
- Las fechas del Maestro pueden ser parciales según SPEC-001; la vista usa la precisión disponible y no inventa fechas ni normaliza a una fecha ficticia.
- Las cinco clases de contenido son las de SPEC-001 y SPEC-003; no se agregan clases, campos ni relaciones nuevas.
- Un perfil activo sin contenido, sin título o sin resumen es válido; un Perfil Maestro vacío también es un estado válido y no se convierte en datos ficticios.
- La interfaz está en español y el contenido admite acentos y caracteres especiales.
- No se definen métricas de rendimiento, volumen o satisfacción: la aplicación es local y monousuario.
- SPEC-002 es una relación opcional: no se asocian documentos ni evidencias al CV y no se requiere su implementación para usar el generador.
- La calidad visual de la plantilla es una responsabilidad de diseño posterior; esta especificación exige una única plantilla profesional y una jerarquía comprensible, sin definir su estética.
- La capacidad no modifica ni elimina datos del Maestro o de los perfiles; solo los lee.

## Dependencias

- **SPEC-001 — Perfil Profesional Maestro**: fuente única de datos personales, experiencia, educación, cursos y certificaciones, habilidades y proyectos; aporta los campos, las fechas parciales, los estados de vigencia y las reglas de eliminación en cascada.
- **SPEC-003 — Perfiles de CV**: aporta los perfiles activos e inactivos, la selección explícita, la relevancia Alta/Media/Baja, las reglas de orden y desempate, el título y el resumen propios, y la herencia de datos personales sin sobrescritura por perfil.
- **Constitución**: Principios I, II, III, XII, XIV, XVI, XVIII, XXI, XXIII, XXV y XXVI; criterio de éxito del MVP, pasos 10–12.
- **SPEC-002 — Documentos y Evidencias**: relación opcional; no se asocian documentos ni evidencias al CV y no es una dependencia funcional.
- **SPEC-005 y SPEC-006**: consumidores futuros de la vista —exportación PDF y CV documentado—; no forman parte de esta capacidad.
- No se requieren dependencias nuevas, servicios externos, IA ni cambios en el stack.

## Trazabilidad

| Origen                                              | Requisitos                                             | Historias | Observación                                                              |
| --------------------------------------------------- | ------------------------------------------------------ | --------- | ------------------------------------------------------------------------ |
| Constitución I, II, III, XIV y XXVI; SPEC-001       | FR-006, FR-009, FR-010, FR-011, FR-013, FR-015, FR-016 | US1, US3  | Fuente única, vista derivada, sin copia ni edición del CV.               |
| Constitución XVI y XVIII; SPEC-003 D1/FR-009–FR-012 | FR-007, FR-008, FR-012                                 | US1, US2  | Selección explícita, orden por relevancia, vista previa y una plantilla. |
| Constitución XIX y XXI; encargo autorizado          | FR-017–FR-021; Pruebas Requeridas                      | US1, US4  | Capacidad verificable, pruebas proporcionales, robustez y accesibilidad. |
| Constitución XXIII y XXV                            | FR-015, FR-022                                         | US1, US4  | Composición local, sin servicios externos y alcance acotado.             |
| SPEC-003 D2/FR-016                                  | FR-002, FR-005, FR-017                                 | US4       | Solo perfiles activos; inactivos no disponibles; reactivación.           |
| SPEC-003 D3/FR-014–FR-015                           | FR-009, FR-010                                         | US1, US3  | Título y resumen propios; datos personales heredados en lectura.         |
| Roadmap SPEC-004                                    | FR-001–FR-022                                          | US1–US4   | Alcance definitivo de la capacidad.                                      |
| Criterio de éxito del MVP, pasos 10–12              | SC-008                                                 | US1, US2  | Selección de perfil, inclusión o priorización y visualización del CV.    |
| SPEC-002                                            | —                                                      | —         | Sin asociación documental; no es dependencia funcional.                  |

## Cierre

- **Aceptación del MVP**: el responsable aceptó funcional y visualmente el MVP de SPEC-004 el 2026-10-23. El cierre se autoriza con cobertura manual parcial documentada.
- **Cobertura manual**: la aceptación no equivale a la ejecución individual de los escenarios manuales 1–13 de `quickstart.md`; la tarea T025 queda documentada con esos escenarios como no verificados individualmente.
- **Escenario 14**: acreditado mediante prueba automatizada.
- **Tareas**: 26/26 tareas completadas, con evidencia en `tasks.md`.
- **Observaciones preexistentes no corregidas**: formato de `docs/roadmap.md` y 16 errores PHPStan de SPEC-003.
- **Cierre documental, commit e integración**: el cierre documental y el commit local `f86d17a` están completados. La integración en `main` mediante merge `--no-ff` está autorizada, pero fue denegada por el harness (`Permission denied: shell`); `main` permanece en `63ab15b`. El SPEC no se declara integrado ni cerrado, y no se hizo push.
