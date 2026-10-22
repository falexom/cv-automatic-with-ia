# Feature Specification: Perfiles de CV

**Feature Branch**: `003-perfiles-cv`

**Created**: 2026-10-22

**Status**: Implemented — 2026-10-22; validación automatizada aprobada (143 tests backend, 31 frontend, typecheck/lint de código); T050 manual pendiente; sin merge ni push.

**Input**: User description: "Permitir crear y administrar múltiples perfiles de CV especializados a partir de los datos del Perfil Profesional Maestro, sin duplicar innecesariamente sus registros. Cada perfil debe conservar relaciones con los registros maestros, poder seleccionar y ordenar contenido relevante y disponer de nombre, título profesional y resumen propios. Los perfiles iniciales previstos son Analista y Programador/Desarrollador. La gestión de perfiles activos/inactivos forma parte del alcance. La generación de CV/PDF, plantillas, exportación, IA, análisis de ofertas, postulaciones y gestión documental adicional están fuera de alcance."

## Problema y Objetivo

**Problema**: el Perfil Profesional Maestro ya centraliza la trayectoria del usuario, pero un CV para Analista y otro para Programador/Desarrollador no requieren el mismo contenido ni el mismo énfasis. Sin una configuración intermedia, el usuario tendría que duplicar registros, reescribir títulos y resúmenes o decidir manualmente cada vez qué información mostrar. Esa duplicación contradice el Principio I y el principio rector del producto: «Una sola trayectoria profesional. Múltiples CV. Cero duplicación de información».

**Objetivo**: incorporar una capacidad que permita crear y mantener múltiples perfiles de CV como configuraciones derivadas del Perfil Maestro. Cada perfil deberá conservar referencias a los registros maestros, seleccionar explícitamente los elementos que utiliza, asignarles relevancia y orden, y disponer de nombre, título profesional y resumen propios. La capacidad también deberá permitir activar o desactivar perfiles y preparar los dos perfiles iniciales previstos por el MVP, sin generar currículums ni vistas previas.

## Alcance

### Incluido en esta capacidad

- Creación, consulta, edición, activación y desactivación de perfiles de CV.
- Nombre, título profesional y resumen propios de cada perfil, conforme a lo aprobado en Clarify.
- Visualización de los datos personales heredados del Perfil Maestro en modo lectura, sin copias ni overrides por perfil, con la excepción del título profesional, que es propio del perfil.
- Asociación explícita de elementos maestros de las cinco clases: experiencia laboral, educación, cursos y certificaciones, habilidades y proyectos.
- Asignación de un nivel de relevancia por elemento y por perfil: Alta, Media o Baja, con Media por defecto.
- Orden del contenido por relevancia descendente, conservando el orden del Perfil Maestro en los empates.
- Reutilización del mismo elemento maestro en varios perfiles, con configuración independiente en cada uno.
- Consulta del perfil con su contenido agrupado por clase, su relevancia y la procedencia de cada elemento.
- Gestión de perfiles activos e inactivos, con activación y desactivación reversibles.
- Creación de los perfiles iniciales Analista y Programador/Desarrollador mediante una acción explícita, activos, sin resumen ni contenido preseleccionado, y posibilidad de crear perfiles adicionales.
- Navegación desde el menú principal hacia la sección Perfiles, estados vacíos y persistencia local entre sesiones de uso.

### Fuera de alcance

- Generación de CV, vista previa, PDF, plantillas visuales, exportación, CV documentado y concatenación de documentos.
- Análisis de ofertas laborales, adaptación a una oferta, postulaciones y generación de cartas.
- Inteligencia artificial para calcular relevancia, sugerir contenido o redactar resúmenes.
- Asociación de documentos o evidencias a perfiles de CV; la gestión documental pertenece a SPEC-002 y no es dependencia funcional de esta capacidad.
- Duplicación completa del Perfil Maestro o creación de copias independientes de sus registros.
- Cambios funcionales a SPEC-001 o SPEC-002: no se redefinen sus entidades, campos, validaciones ni reglas.
- Edición de datos personales heredados por perfil o cualquier forma de override de los datos heredados; el título profesional propio del perfil es la excepción y se rige por lo aprobado en Clarify.
- Orden manual drag-and-drop, reordenamiento de secciones o edición libre de la presentación.
- Eliminación definitiva de perfiles de CV; la única retirada de uso es la desactivación reversible.
- Historial o versionado de perfiles.
- Multiusuario, autenticación, sincronización externa, exportación o respaldo de perfiles.

### Dependencias y relaciones

- **SPEC-001 — Perfil Profesional Maestro (dependencia principal)**: el Perfil Maestro es la única fuente de verdad y ya contiene datos personales, resumen, experiencia, educación, cursos y certificaciones, habilidades y proyectos. Esta capacidad no reabre ni modifica su alcance; solo agrega una capa de configuración que referencia sus registros.
- **SPEC-002 — Documentos y Evidencias (relación opcional)**: puede aportar información documental a capacidades futuras, pero esta especificación no asocia documentos ni evidencias a perfiles y no depende funcionalmente de SPEC-002 para existir.
- **SPEC-004 — Generador de CV (dependencia futura)**: consumirá la configuración de perfiles para producir la vista del CV. Esta especificación define qué significa un perfil activo para esa futura capacidad, pero no implementa la generación, la vista previa ni la exportación.

## Clarifications

### Session 2026-10-22

- Q: **D1 — ¿Cómo se seleccionan y ordenan los elementos del Perfil Maestro en un perfil?** → A: La selección es explícita; cada elemento incluido recibe relevancia Alta, Media o Baja, con Media por defecto; el orden es descendente por relevancia y, en los empates, se conserva el orden del Perfil Maestro. Ese orden es, en experiencia, educación, cursos/certificaciones y proyectos, de más reciente a más antiguo, con los registros sin fecha al final; en habilidades, por categoría (técnica, funcional, metodológica, herramienta, tecnología) y luego por nombre. No se ofrece orden manual ni reordenamiento de secciones.
- Q: **D2 — ¿Cómo se crean y gestionan los perfiles iniciales y los estados activo/inactivo?** → A: Los perfiles iniciales «Analista» y «Programador» se crean mediante una acción explícita, activos, sin resumen ni contenido preseleccionado; se permiten perfiles adicionales. La activación y la desactivación son reversibles; los perfiles inactivos siguen visibles, editables, conservan su contenido y pueden reactivarse. No se implementa eliminación definitiva de perfiles en SPEC-003 y los perfiles iniciales no se recrean ni duplican automáticamente.
- Q: **D3 — ¿Qué datos son propios del perfil y cómo se heredan los del Maestro?** → A: El nombre es obligatorio y único, normalizado, incluyendo los perfiles inactivos. El título y el resumen son propios del perfil y opcionales. Los datos personales se heredan del Maestro en modo lectura, sin duplicación ni overrides; el título propio del perfil es el que se usa en la configuración y en la futura vista, sin mostrar adicionalmente el título del Maestro ni modificar el Maestro.

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Crear y mantener perfiles de CV (Priority: P1)

Como usuario, quiero crear, editar, activar y desactivar varios perfiles de CV, para disponer de configuraciones distintas de mi trayectoria sin duplicar mi información profesional.

**Why this priority**: Es la base de toda la capacidad. Sin perfiles no existe un lugar donde configurar la selección, la relevancia ni los datos propios que distinguirán un CV de otro.

**Independent Test**: Crear dos perfiles, editar su nombre, título y resumen, comprobar el rechazo de un nombre duplicado, desactivar y reactivar uno, comprobar que no existe eliminación definitiva de perfiles y verificar que el Perfil Maestro y el perfil restante no cambian.

**Acceptance Scenarios**:

1. **Dado** que no existe ningún perfil de CV, **Cuando** el usuario crea uno con nombre y título, **Entonces** el perfil aparece en la lista como activo y conserva esos datos propios.
2. **Dado** un perfil llamado «Analista», **Cuando** el usuario intenta crear otro llamado « analista » con diferencias de mayúsculas o espacios, **Entonces** el sistema rechaza la creación e identifica el perfil existente.
3. **Dado** un perfil existente, **Cuando** el usuario edita su título o resumen, **Entonces** el cambio se conserva solo en ese perfil y el Perfil Maestro no se modifica.
4. **Dado** un perfil activo con contenido, **Cuando** el usuario lo desactiva, **Entonces** el perfil pasa a inactivo, conserva todo su contenido y deja de ofrecerse para la futura generación; al reactivarlo vuelve a estar disponible.
5. **Dado** un perfil existente, **Cuando** el usuario busca una acción para eliminarlo definitivamente, **Entonces** el sistema no ofrece esa acción y solo permite desactivarlo o reactivarlo según su estado, conservando el Perfil Maestro y los demás perfiles.
6. **Dado** un nombre vacío o compuesto solo por espacios, **Cuando** el usuario intenta guardar, **Entonces** el sistema lo impide y explica que el nombre es obligatorio.

---

### User Story 2 - Seleccionar, priorizar y reutilizar contenido (Priority: P1)

Como usuario, quiero seleccionar explícitamente qué experiencias, educación, cursos y certificaciones, habilidades y proyectos incluye cada perfil y con qué relevancia, pudiendo reutilizar el mismo elemento en varios perfiles, para que el CV futuro destaque lo pertinente sin copiar ni reescribir registros.

**Why this priority**: Es el núcleo funcional de la capacidad y la condición para que los perfiles Analista y Programador/Desarrollador produzcan CV distintos sobre la misma trayectoria. La reutilización independiente evita duplicar registros y demuestra que un perfil no interfiere con otro.

**Independent Test**: Con al menos un elemento maestro de cada clase, crear dos perfiles y comprobar que no incluyen nada por defecto; incluir elementos de las cinco clases, asignar relevancia, verificar agrupamiento y orden, comprobar que desasociar en un perfil no altera el Maestro ni el otro perfil y que el mismo elemento puede tener relevancia distinta en cada uno.

**Acceptance Scenarios**:

1. **Dado** un perfil recién creado y un Perfil Maestro con elementos, **Cuando** el usuario consulta el perfil, **Entonces** no aparece ningún elemento incluido por defecto y el sistema invita a seleccionar contenido.
2. **Dado** un perfil sin contenido, **Cuando** el usuario incluye una experiencia, una entrada de educación, un curso o certificación, una habilidad y un proyecto existentes, **Entonces** los cinco elementos aparecen agrupados en sus secciones correspondientes.
3. **Dado** varios elementos incluidos en una misma sección, **Cuando** el usuario les asigna relevancia Alta, Media o Baja, **Entonces** el orden resultante respeta la relevancia descendente.
4. **Dado** dos elementos con la misma relevancia en experiencia, educación, cursos/certificaciones o proyectos, **Cuando** el usuario consulta el perfil, **Entonces** se conserva el orden del Perfil Maestro: de más reciente a más antiguo, con los registros sin fecha al final.
5. **Dado** dos habilidades con la misma relevancia, **Cuando** el usuario consulta el perfil, **Entonces** se conserva el orden del Perfil Maestro: por categoría (técnica, funcional, metodológica, herramienta, tecnología) y luego por nombre.
6. **Dado** un elemento incluido en dos perfiles, **Cuando** el usuario lo desasocia de uno, **Entonces** deja de formar parte de ese perfil, sigue incluido en el otro y el registro maestro permanece intacto.
7. **Dado** un mismo elemento maestro incluido en dos perfiles con relevancias distintas, **Cuando** el usuario cambia su relevancia en uno, **Entonces** la relevancia del otro no cambia.
8. **Dado** un elemento incluido, **Cuando** el usuario intenta editar sus datos desde la configuración del perfil, **Entonces** el sistema no lo permite y lo remite a la sección del Perfil Maestro.
9. **Dado** un elemento ya incluido, **Cuando** el usuario vuelve a incluirlo, **Entonces** el sistema no crea una asociación duplicada y actualiza la asociación existente.

---

### User Story 3 - Consultar el perfil con datos heredados y contenido agrupado (Priority: P2)

Como usuario, quiero consultar un perfil y ver su título propio y sus datos personales heredados del Maestro junto con su contenido agrupado, ordenado y con su relevancia, para revisar la configuración antes de utilizarla en un CV.

**Why this priority**: Permite verificar que el perfil es una vista derivada coherente y no una copia. Es la base de confianza para que SPEC-004 consuma la configuración, pero el valor principal ya se entrega con las historias P1.

**Independent Test**: Abrir un perfil con contenido, comprobar que los datos personales heredados son los del Maestro y no editables, salvo el título profesional propio del perfil; actualizar un dato personal distinto del título y un registro profesional en el Maestro y verificar que el perfil refleja el cambio; revisar el agrupamiento, la relevancia y el orden; abrir un perfil sin contenido y comprobar sus estados vacíos.

**Acceptance Scenarios**:

1. **Dado** un perfil con datos personales heredados, **Cuando** el usuario lo consulta, **Entonces** ve los datos personales del Perfil Maestro en modo lectura, con la excepción del título profesional, que es propio y editable en el perfil, y no dispone de campos para modificar los datos heredados desde el perfil.
2. **Dado** un dato personal o un registro profesional actualizado en el Perfil Maestro, **Cuando** el usuario vuelve a consultar cualquier perfil que lo utilice, **Entonces** ve la información actualizada sin haber realizado ninguna acción de sincronización, salvo el título profesional del Maestro, que no se muestra porque el perfil usa su título propio; cada perfil conserva su relevancia.
3. **Dado** un perfil con elementos incluidos, **Cuando** el usuario lo consulta, **Entonces** el contenido aparece agrupado por experiencia, educación, cursos y certificaciones, habilidades y proyectos, con la relevancia y el orden configurados.
4. **Dado** un perfil sin contenido, **Cuando** el usuario lo consulta, **Entonces** cada sección muestra un estado vacío con orientación y el perfil sigue siendo válido y consultable.
5. **Dado** un perfil inactivo, **Cuando** el usuario lo consulta en la gestión de perfiles, **Entonces** lo distingue visualmente como inactivo, conserva su contenido y comprueba que no se ofrece para la futura generación.
6. **Dado** un elemento maestro que fue eliminado, **Cuando** el usuario consulta un perfil que lo referenciaba, **Entonces** el elemento ya no aparece en el perfil, el perfil permanece y ninguna copia obsoleta se muestra.
7. **Dado** un elemento incluido, **Cuando** el usuario lo consulta en el perfil, **Entonces** puede identificar de qué registro maestro procede.

---

### User Story 4 - Disponer de los perfiles iniciales Analista y Programador (Priority: P2)

Como usuario, quiero disponer de los perfiles iniciales Analista y Programador/Desarrollador mediante una acción explícita, para empezar con las dos configuraciones previstas por el MVP sin crearlas desde cero.

**Why this priority**: El MVP prevé explícitamente esos dos perfiles (Principio VI). Los presets reducen el trabajo inicial y demuestran la diferenciación, pero la capacidad ya es utilizable creando perfiles manualmente.

**Independent Test**: Sin perfiles existentes, ejecutar la acción explícita de creación inicial y comprobar que aparecen los dos perfiles activos con sus nombres y títulos, sin resumen ni contenido; comprobar que, una vez existentes, no se duplican ni se sobrescriben; renombrar y desactivar uno y comprobar que no reaparecen automáticamente.

**Acceptance Scenarios**:

1. **Dado** que no existe ningún perfil, **Cuando** el usuario ejecuta la acción explícita de crear los perfiles iniciales, **Entonces** aparecen dos perfiles activos: «Analista» con título «Analista de Sistemas» y «Programador» con título «Programador / Desarrollador».
2. **Dado** que se crearon los perfiles iniciales, **Cuando** el usuario los consulta, **Entonces** no tienen resumen ni contenido preseleccionado y están listos para configurarse.
3. **Dado** que los dos perfiles iniciales ya existen, **Cuando** el usuario intenta ejecutar de nuevo la creación inicial, **Entonces** el sistema no crea duplicados ni sobrescribe los perfiles existentes.
4. **Dado** que uno de los perfiles iniciales fue renombrado, **Cuando** el usuario consulta la lista, **Entonces** no aparece un perfil nuevo creado automáticamente con el nombre anterior.
5. **Dado** que uno de los perfiles iniciales fue desactivado, **Cuando** el usuario consulta la lista, **Entonces** sigue visible como inactivo, puede reactivarse y no aparece un duplicado automático.
6. **Dado** un perfil inicial creado, **Cuando** el usuario edita su nombre, título, resumen, estado o contenido, **Entonces** se comporta como cualquier otro perfil.

---

### Edge Cases

- **Perfil Maestro vacío**: se permite crear y consultar un perfil con sus datos propios; no se muestran datos personales heredados ni contenido heredado y el sistema lo indica, sin inventar información (supuesto de trabajo).
- **Perfil sin contenido**: es un estado válido; se guarda, se consulta y muestra estados vacíos con orientación. Un perfil activo sin contenido no se considera un error.
- **Perfil inactivo o todos los perfiles inactivos**: conserva nombre, título, resumen, asociaciones y relevancias; se muestra diferenciado, puede editarse y reactivarse, no se ofrece para la futura generación y la gestión sigue disponible.
- **Nombre duplicado, vacío o solo con espacios**: se rechaza la creación o el renombrado con un mensaje que identifica el problema; renombrar un perfil al mismo nombre normalizado se permite.
- **Actualización de un registro maestro o de un dato personal heredado**: se refleja en todos los perfiles que lo utilizan, sin copias obsoletas ni sincronización manual; el título del Maestro no se muestra porque el perfil usa su título propio.
- **Eliminación de un elemento maestro**: desaparecen sus asociaciones de todos los perfiles; los perfiles y los demás registros maestros permanecen.
- **Perfiles iniciales ya existentes, renombrados o desactivados**: no se duplican, sobrescriben ni recrean automáticamente; si están desactivados, la reactivación es la vía para volver a usarlos.
- **Elementos sin fecha**: quedan al final del orden del Perfil Maestro dentro de su clase y relevancia.
- **Empates de orden**: se conserva el orden del Perfil Maestro; no se aplican desempates adicionales ni orden manual.
- **Fallo de guardado**: se informa el problema y se conserva lo ingresado en pantalla para reintentar, sin pérdida silenciosa.
- **Texto extenso, acentos y caracteres especiales** en nombres, títulos y resúmenes: se conservan y muestran correctamente.

## Requirements _(mandatory)_

### Functional Requirements

#### Fuente única, configuración derivada y aislamiento (obligatorios)

- **FR-001**: El Perfil de CV DEBE ser una configuración derivada del Perfil Profesional Maestro: DEBE contener únicamente sus datos propios (nombre, título y resumen), su estado y las asociaciones con elementos maestros; NO DEBE almacenar copias independientes de experiencias, educación, cursos o certificaciones, habilidades, proyectos ni datos personales heredados, ni duplicar el Perfil Maestro. El perfil NO DEBE ser un documento editable ni una vista final de CV.
- **FR-002**: El Perfil Profesional Maestro DEBE seguir siendo la única fuente de verdad de la información profesional y de los datos personales heredados. Toda actualización de un registro maestro o de un dato personal heredado DEBE reflejarse automáticamente en todos los perfiles que lo utilizan, sin sincronización manual. La eliminación de un registro maestro DEBE eliminar solo sus asociaciones y conservar los perfiles y los demás registros; la eliminación de un dato personal heredado DEBE dejar de mostrarlo en los perfiles. El título profesional propio del perfil se rige por FR-014.
- **FR-003**: El sistema DEBE permitir que un mismo elemento maestro sea referenciado por varios perfiles; ninguna operación sobre un perfil DEBE modificar los registros del Perfil Maestro ni la configuración, contenido o estado de otro perfil. Los datos de un registro maestro NO DEBEN editarse desde la configuración del perfil.
- **FR-004**: Cada asociación perfil–elemento DEBE identificar el registro maestro referenciado y su clase (experiencia, educación, curso o certificación, habilidad o proyecto), de modo que la procedencia sea verificable.

#### Gestión y consulta de perfiles (obligatorios)

- **FR-005**: El sistema DEBE permitir crear, consultar, editar, activar y desactivar perfiles de CV, tanto iniciales como adicionales, con capacidad para los dos perfiles previstos por el MVP —Analista y Programador/Desarrollador—, mostrar un listado con nombre, título y estado, ofrecer navegación a la sección Perfiles y un estado vacío con orientación cuando no exista ninguno.
- **FR-006**: El sistema DEBE permitir consultar el detalle de un perfil con sus datos propios, sus datos personales heredados y su contenido agrupado por clase, identificando la procedencia de cada elemento.
- **FR-007**: Un perfil sin contenido DEBE ser un estado válido: DEBE poder guardarse y consultarse, mostrando estados vacíos con orientación en cada sección.
- **FR-008**: El sistema DEBE permitir asociar y desasociar únicamente elementos existentes del Perfil Maestro pertenecientes a las cinco clases —experiencia, educación, cursos y certificaciones, habilidades y proyectos—; NO DEBE crear registros maestros desde el perfil; una misma asociación no DEBE duplicarse —si se reincluye un elemento, DEBE actualizarse la asociación existente— y la desasociación DEBE afectar solo a la configuración del perfil.

#### Selección, relevancia y orden

- **FR-009**: El sistema NO DEBE incluir ningún elemento del Perfil Maestro por defecto; toda inclusión DEBE ser explícita. Solo los elementos incluidos DEBEN formar parte del contenido del perfil; los no incluidos permanecen disponibles para incluirlos, pero NO DEBEN mostrarse como contenido.
- **FR-010**: Cada elemento incluido DEBE recibir un nivel de relevancia Alta, Media o Baja, con valor inicial Medio; la relevancia DEBE ser independiente por perfil y DEBE mostrarse en la consulta del perfil.
- **FR-011**: El sistema DEBE ordenar el contenido de cada sección por relevancia descendente. En caso de empate, DEBE conservar el orden del Perfil Maestro definido en FR-012.
- **FR-012**: A efectos de desempate, el orden del Perfil Maestro DEBE ser: experiencia, educación, cursos/certificaciones y proyectos de más reciente a más antiguo, con los registros sin fecha al final; habilidades por categoría —técnica, funcional, metodológica, herramienta, tecnología— y luego por nombre. El orden resultante DEBE ser estable y reproducible. El sistema NO DEBE ofrecer orden manual drag-and-drop ni reordenamiento de secciones.

#### Nombre, datos propios y datos heredados

- **FR-013**: El nombre del perfil DEBE ser obligatorio y no vacío ni solo espacios; DEBE ser único sin distinguir mayúsculas/minúsculas ni espacios sobrantes, conservando la forma escrita para mostrarla; la unicidad DEBE revalidarse al renombrar y aplicarse a todos los perfiles, incluidos los inactivos.
- **FR-014**: El título profesional y el resumen DEBEN ser datos propios del perfil, editables, opcionales e independientes del Maestro y de otros perfiles; NO DEBEN copiarse automáticamente del Maestro. El título propio DEBE usarse en la configuración del perfil y en la futura vista de CV, sin mostrar adicionalmente el título del Maestro ni modificar el registro maestro; un perfil con título o resumen vacíos sigue siendo válido.
- **FR-015**: El sistema DEBE mostrar los datos personales del Perfil Maestro en el perfil en modo lectura, sin almacenar copia ni permitir overrides; todos los perfiles DEBEN mostrar los mismos datos vigentes del Maestro, con la excepción del título profesional, que es propio del perfil conforme a FR-014.

#### Ciclo de vida y perfiles iniciales

- **FR-016**: Todo perfil nuevo, no solo los iniciales, DEBE crearse activo por defecto y poder desactivarse después. La desactivación DEBE ser reversible mediante reactivación. Un perfil activo DEBE quedar disponible para la futura generación de CV de SPEC-004; un perfil inactivo NO DEBE ofrecerse para esa generación, DEBE conservar nombre, título, resumen, asociaciones y relevancias, y DEBE permanecer visible, diferenciado, editable y reactivable en la gestión. Esta capacidad NO DEBE implementar la generación.
- **FR-017**: El sistema DEBE permitir crear los perfiles iniciales «Analista» y «Programador» mediante una acción explícita, activos, con los títulos propuestos «Analista de Sistemas» y «Programador / Desarrollador», sin resumen ni contenido preseleccionado. NO DEBE crearlos ni recrearlos automáticamente si ya existen, fueron renombrados o desactivados, ni sobrescribirlos o duplicarlos; pueden editarse como cualquier otro perfil.
- **FR-018**: El sistema NO DEBE ofrecer eliminación definitiva de perfiles en esta capacidad. La única forma de retirar un perfil de uso DEBE ser la desactivación, reversible mediante reactivación, sin alterar el Perfil Maestro ni otros perfiles.

#### Persistencia y robustez (obligatorios)

- **FR-019**: El sistema DEBE conservar localmente los perfiles, sus datos propios, estado, asociaciones y relevancias entre sesiones de uso, sin enviarlos a servicios externos, y DEBE mostrar correctamente nombres, títulos y resúmenes con acentos y caracteres especiales.
- **FR-020**: Si falla el guardado de un perfil, una asociación o un cambio de relevancia, el sistema DEBE informar el problema y conservar lo ingresado en pantalla para reintentar, sin pérdida silenciosa de datos.

### Key Entities _(include if feature involves data)_

- **Perfil de CV**: configuración derivada del Perfil Profesional Maestro. Tiene un nombre propio, un título profesional propio, un resumen propio y un estado activo o inactivo. No contiene copias de datos profesionales ni de datos personales heredados; su contenido se compone de asociaciones a registros maestros. Puede existir sin contenido.
- **Asociación perfil–elemento maestro**: vínculo explícito entre un perfil y un registro existente del Perfil Maestro, con la clase del elemento y la relevancia asignada en ese perfil. Es única por perfil y elemento, y puede crearse, modificarse o eliminarse sin alterar el registro maestro ni otros perfiles. No almacena una copia del contenido del elemento.
- **Datos personales heredados**: datos personales y de contacto del Perfil Maestro que se muestran en el perfil en modo lectura. No se almacenan como copia en el perfil y no admiten overrides por perfil; el título profesional no se hereda, porque es un dato propio del perfil conforme a FR-014.
- **Elemento maestro (existente, SPEC-001)**: experiencia laboral, educación, curso o certificación, habilidad o proyecto ya registrado en el Perfil Maestro. Esta capacidad no redefine sus campos ni sus reglas; solo lo referencia desde los perfiles.
- **Estado del perfil**: condición activo/inactivo que determina si el perfil se ofrece para la futura generación de CV de SPEC-004, sin afectar a la conservación de su contenido ni a la gestión del perfil.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Ningún perfil almacena copias independientes de datos profesionales ni de datos personales heredados; todo el contenido profesional y los datos heredados que muestra provienen del Perfil Maestro, y toda actualización o eliminación de un registro maestro se refleja en todos los perfiles que lo referencian sin sincronización manual.
- **SC-002**: Ninguna operación sobre un perfil altera el Perfil Maestro ni otro perfil; el mismo elemento puede reutilizarse en varios perfiles con relevancia independiente y la edición del título o resumen propios no afecta al Maestro ni a otros perfiles.
- **SC-003**: Todo perfil muestra como contenido únicamente elementos incluidos explícitamente y presenta un orden determinista: relevancia descendente y, en los empates, orden del Perfil Maestro.
- **SC-004**: Todo perfil nuevo se crea activo; los inactivos conservan su contenido, siguen visibles y editables, y pueden reactivarse; los perfiles iniciales no se recrean automáticamente; no existe eliminación definitiva de perfiles.
- **SC-005**: Los nombres vacíos o duplicados se rechazan; los perfiles sin contenido son válidos y muestran orientación; los fallos de guardado conservan lo ingresado para reintentar.
- **SC-006**: Tras reiniciar la aplicación se conservan perfiles, estados, datos propios, asociaciones y relevancias, y los nombres, títulos y resúmenes con acentos o caracteres especiales se muestran correctamente.

## Pruebas Requeridas

- Persistencia y texto: comprobar que perfiles, estados, datos propios, asociaciones y relevancias sobreviven al reinicio, y que nombres, títulos y resúmenes con acentos o caracteres especiales se conservan.
- Referencia frente a copia: crear un perfil, asociar elementos de las cinco clases y verificar que no almacena copias; actualizar y eliminar registros maestros y comprobar la actualización o desaparición en todos los perfiles que los referencian.
- Reutilización y aislamiento: incluir el mismo elemento en dos perfiles con relevancia distinta, modificar y desasociar en uno y comprobar que el Maestro y el otro perfil no cambian.
- Inclusión, relevancia y orden: verificar que un perfil nuevo no incluye nada por defecto, que solo aparecen los elementos incluidos y que se aplican relevancia descendente y, en empates, orden del Perfil Maestro: experiencia, educación, cursos/certificaciones y proyectos de más reciente a más antiguo, con los registros sin fecha al final; habilidades por categoría y nombre; sin orden manual.
- Nombre, datos propios y herencia: comprobar nombre obligatorio y único normalizado, revalidación al renombrar, título y resumen opcionales e independientes, título propio sin copia del Maestro y datos personales heredados en lectura salvo el título.
- Perfiles iniciales y ciclo de vida: verificar creación explícita de los presets, estado activo por defecto, no recreación ni sobrescritura, conservación al desactivar, visibilidad y reactivación de inactivos, y comprobar que no existe acción ni ruta de eliminación definitiva de perfiles.
- Casos límite: Maestro vacío, perfil sin contenido, elementos sin fecha, empates de orden y fallo de guardado, comprobando que no se inventan datos ni se pierde lo ingresado.
- Límites de alcance: comprobar que la capacidad no genera CV, vistas previas, PDFs, plantillas, exportaciones, asociaciones documentales ni eliminación definitiva de perfiles.

## Assumptions

- Aplicación local monousuario con un único Perfil Profesional Maestro, conforme a SPEC-001 y a los Principios XII y XXIII de la Constitución.
- El Perfil Maestro ya existe y sus entidades, campos y validaciones están definidos por SPEC-001; esta capacidad no los reabre ni los modifica.
- Las cinco clases de contenido son las de SPEC-001: experiencia laboral, educación, cursos y certificaciones, habilidades y proyectos. No se agregan clases nuevas.
- Un perfil puede crearse aunque el Perfil Maestro no tenga registros; en ese caso solo muestra sus datos propios y estados vacíos, sin inventar información heredada. Este comportamiento se registra como supuesto de trabajo de la capacidad.
- Las fechas del Perfil Maestro pueden ser parciales (mes y año, o solo año) según SPEC-001; el orden de perfiles utiliza las fechas disponibles sin introducir una nueva precisión.
- No se definen métricas de volumen, rendimiento ni satisfacción: la aplicación es local y monousuario, y esta capacidad no introduce objetivos de carga.
- No se modifican datos existentes del Perfil Maestro; al ser una capacidad nueva, no se prevén migraciones destructivas. Cualquier cambio estructural deberá preservar los datos conforme al Principio XXII.
- La interfaz está en español; el contenido admite acentos y caracteres especiales.
- SPEC-002 es una relación opcional: no se asocian documentos ni evidencias en esta capacidad y no se requiere su implementación para usar perfiles.

## Trazabilidad

| Origen | Requisitos | Historias | Observación |
| --- | --- | --- | --- |
| Constitución, Principios I, II, III, V, VI y XIV; SPEC-001 | FR-001 a FR-004, FR-014, FR-015 | US1 a US4 | Fuente única, no copia, aislamiento, título propio y herencia. |
| Constitución, Principios XIX, XXI, XXII, XXV y XXVI; encargo autorizado | FR-005 a FR-008, FR-019 y FR-020 | US1 a US4 | Capacidad verificable, gestión, persistencia, robustez y alcance acotado. |
| Clarify D1 — aprobada 2026-10-22 | FR-009 a FR-012 | US2, US3 | Selección explícita, relevancia y orden con empates según el Perfil Maestro. |
| Clarify D2 — aprobada 2026-10-22 | FR-005, FR-016 a FR-018 | US1, US3, US4 | Perfiles iniciales, activo/inactivo y ausencia de eliminación definitiva. |
| Clarify D3 — aprobada 2026-10-22 | FR-013 a FR-015 | US1, US3 | Nombre, datos propios y herencia con título propio. |
| SPEC-002 (relación opcional) | — | — | Sin asociación documental; no es dependencia funcional. |
| SPEC-004 (dependencia futura) | FR-014 y FR-016 | US3, US4 | Título propio en la futura vista y disponibilidad por estado activo; sin implementar generación. |
