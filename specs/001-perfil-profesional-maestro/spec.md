# Feature Specification: Perfil Profesional Maestro

**Feature Branch**: `001-perfil-profesional-maestro`

**Created**: 2026-10-15

**Status**: Cerrado (2026-10-22) — validación manual del responsable aprobada (9 escenarios, 4.5 y formularios corregidos); T063/T064 completadas; SC-001 retirado por decisión del responsable; pendiente de integración en `main` (commits `63bb79c`, `a04954d`)

**Input**: User description: "Gestión centralizada del Perfil Profesional Maestro: datos personales, resumen profesional, experiencia laboral, educación, cursos y certificaciones, habilidades y proyectos. Es la base de todo el sistema: una sola trayectoria profesional registrada una única vez."

## Problema y Objetivo

**Problema**: la información profesional del usuario vive dispersa entre documentos y archivos, y se reescribe manualmente cada vez que se prepara un currículum. Esto produce duplicación, inconsistencias y datos desactualizados.

**Objetivo**: ofrecer una capacidad que permita registrar y mantener, en un único lugar, toda la trayectoria profesional del usuario, de modo que cualquier vista o documento futuro se derive de esa única fuente de verdad, sin copias independientes por sección ni edición directa de documentos finales.

## Alcance

### Incluido en esta capacidad

- Registro y mantenimiento (crear, consultar, editar y eliminar) de:
    - Datos personales y enlaces de contacto profesional.
    - Resumen profesional reutilizable.
    - Experiencia laboral.
    - Educación.
    - Cursos y certificaciones.
    - Habilidades clasificadas por categoría.
    - Proyectos.
- Relaciones entre habilidades y los elementos que las utilizan (experiencia, certificaciones y proyectos).
- Validaciones de integridad: fechas coherentes, formatos de correo y enlaces, control de duplicados de habilidades.
- Navegación del administrador orientada a las secciones del perfil.
- Persistencia local de toda la información entre sesiones de uso.

### Fuera de alcance

- Carga, almacenamiento y asociación de archivos PDF de certificados (corresponde a la capacidad de repositorio documental).
- Extracción automática de información desde documentos.
- Perfiles objetivo (Analista de Sistemas, Programador/Desarrollador) y reglas de relevancia.
- Generación de CV, vista previa y exportación a PDF.
- Selección de evidencias y CV documentado.
- Multiusuario, autenticación, sincronización con servicios externos e importación automática de datos.
- Exportación, respaldo y restauración de los datos del perfil; se evaluarán en una especificación posterior.

## Clarifications

### Session 2026-10-15

- Q: ¿Cómo deberían almacenarse y reutilizarse las tecnologías asociadas a experiencias y proyectos? → A: Las tecnologías son habilidades de categoría "Tecnologías": un único catálogo reutilizable con las mismas relaciones.
- Q: ¿Las habilidades del catálogo deben incluir un nivel de dominio (por ejemplo: básico, intermedio, avanzado)? → A: No en el MVP: la habilidad se registra solo con nombre y categoría.
- Q: ¿Esta capacidad debe incluir alguna forma de exportar o respaldar los datos del perfil? → A: Fuera de alcance en esta capacidad: el respaldo/exportación se evaluará en una especificación posterior.
- Q: ¿Qué campos deberían ser obligatorios al guardar cada tipo de registro? → A: Solo el mínimo identificador: experiencia (empresa y cargo), educación (institución y programa), curso (nombre), habilidad (nombre y categoría), proyecto (nombre); el resto es opcional, con coherencia de fechas cuando se informen.
- Q: ¿Con qué precisión deben registrarse las fechas de experiencia, educación y proyectos? → A: Mixta: mes y año, o solo año cuando el usuario no recuerde el mes.

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Registrar datos personales y resumen profesional (Priority: P1)

Como usuario, quiero registrar mis datos personales y mi resumen profesional una sola vez, para que sirvan de base a todos los currículums y documentos que genere en el futuro.

**Why this priority**: Sin identidad ni contacto profesional no es posible producir ningún documento. Es la base de todas las demás capacidades y de todo el MVP.

**Independent Test**: Completar los datos personales y el resumen, guardar, cerrar y volver a abrir la aplicación: la información debe aparecer idéntica, editable y sin duplicados.

**Acceptance Scenarios**:

1. **Dado** un perfil sin datos, **Cuando** el usuario completa su nombre y guarda, **Entonces** los datos quedan guardados y se muestran al volver a la sección.
2. **Dado** un dato personal ya guardado, **Cuando** el usuario lo corrige y guarda, **Entonces** la versión anterior deja de existir y solo se muestra la corregida.
3. **Dado** un correo electrónico o enlace con formato inválido, **Cuando** el usuario intenta guardar, **Entonces** el sistema muestra un mensaje claro y no guarda.
4. **Dado** que el usuario no completa su nombre, **Cuando** intenta guardar, **Entonces** el sistema impide guardar e indica que el nombre es obligatorio.
5. **Dado** que el usuario agrega varios enlaces adicionales, **Cuando** los guarda, **Entonces** todos quedan visibles con su etiqueta y dirección correspondientes.

---

### User Story 2 - Gestionar experiencia laboral (Priority: P1)

Como usuario, quiero registrar mis experiencias laborales con sus responsabilidades, logros y habilidades asociadas (incluidas las tecnologías), para construir la sección más importante de mi trayectoria profesional.

**Why this priority**: La experiencia laboral es el contenido principal de cualquier currículum. Junto con los datos personales, constituye el mínimo utilizable del sistema.

**Independent Test**: Registrar una experiencia, verificarla en la lista ordenada, editarla, asociarle una habilidad existente y eliminarla con confirmación; comprobar que nada más se ve afectado.

**Acceptance Scenarios**:

1. **Dado** un perfil sin experiencia, **Cuando** el usuario registra una experiencia con empresa, cargo y fecha de inicio, **Entonces** aparece en la lista ordenada de más reciente a más antigua.
2. **Dada** una experiencia marcada como actual, **Cuando** se visualiza, **Entonces** se muestra como vigente y no exige fecha de fin.
3. **Dado** un intento de guardar una experiencia con fecha de fin anterior a la fecha de inicio, **Cuando** el usuario confirma el guardado, **Entonces** el sistema lo bloquea y explica el motivo.
4. **Dada** una experiencia existente, **Cuando** el usuario la edita, **Entonces** los cambios se reflejan de inmediato en todas las vistas donde aparece.
5. **Dada** una experiencia existente, **Cuando** el usuario la elimina y confirma, **Entonces** deja de mostrarse y no reaparece.
6. **Dada** una experiencia, **Cuando** el usuario le asocia habilidades del catálogo, **Entonces** la relación es visible tanto desde la experiencia como desde cada habilidad.

---

### User Story 3 - Gestionar educación (Priority: P2)

Como usuario, quiero registrar mi formación académica con institución, programa, nivel y fechas, para completar mi perfil profesional con mi historial educativo.

**Why this priority**: La educación respalda y cualifica la experiencia laboral, pero se actualiza con menor frecuencia. No bloquea el uso inicial del sistema.

**Independent Test**: Registrar una entrada de educación, marcarla como en curso, editarla y eliminarla con confirmación.

**Acceptance Scenarios**:

1. **Dado** un perfil sin educación registrada, **Cuando** el usuario registra institución, programa y fechas, **Entonces** la entrada aparece en la lista ordenada de más reciente a más antigua.
2. **Dado** un estudio en curso, **Cuando** el usuario lo marca como en curso sin fecha de fin, **Entonces** se guarda correctamente y se muestra como en curso.
3. **Dado** un intento de guardar con fecha de fin anterior a la fecha de inicio, **Cuando** el usuario confirma, **Entonces** el sistema lo bloquea y explica el motivo.
4. **Dada** una entrada de educación existente, **Cuando** el usuario la elimina y confirma, **Entonces** deja de mostrarse y no reaparece.

---

### User Story 4 - Gestionar cursos y certificaciones (Priority: P2)

Como usuario, quiero registrar mis cursos y certificaciones con sus datos de respaldo (institución, fecha, código, URL de validación y categoría), para acreditar mis conocimientos y preparar la futura evidencia documental.

**Why this priority**: Las certificaciones alimentan la credibilidad del perfil y son la antesala del CV documentado, pero no bloquean el uso inicial del sistema.

**Independent Test**: Registrar una certificación completa, verificar la validación de su URL de validación, asociarle habilidades, editarla y eliminarla con confirmación.

**Acceptance Scenarios**:

1. **Dado** un perfil sin cursos registrados, **Cuando** el usuario registra un curso con nombre, institución, fecha y categoría, **Entonces** aparece en la lista ordenada de más reciente a más antigua.
2. **Dado** un curso con URL de validación, **Cuando** el usuario ingresa una dirección con formato inválido, **Entonces** el sistema lo advierte y no guarda.
3. **Dado** un curso sin fecha indicada, **Cuando** el usuario lo guarda, **Entonces** el sistema lo permite y lo muestra sin fecha.
4. **Dado** un curso con el mismo nombre e institución que otro ya registrado, **Cuando** el usuario intenta guardarlo, **Entonces** recibe una advertencia y solo puede continuar con confirmación explícita.
5. **Dado** un curso registrado, **Cuando** el usuario le asocia habilidades, **Entonces** la relación es visible tanto desde el curso como desde cada habilidad.

---

### User Story 5 - Gestionar habilidades (Priority: P3)

Como usuario, quiero mantener un catálogo único de habilidades clasificadas por categoría, vinculadas a mis experiencias, certificaciones y proyectos, para reutilizarlas sin duplicarlas en todas mis vistas profesionales.

**Why this priority**: Enriquece el perfil y conecta sus secciones, pero el sistema ya aporta valor sin el catálogo completo. Puede alimentarse progresivamente.

**Independent Test**: Crear una habilidad con categoría, intentar duplicarla, asociarla a una experiencia y eliminar la habilidad verificando que la experiencia permanece intacta.

**Acceptance Scenarios**:

1. **Dado** un perfil sin habilidades, **Cuando** el usuario crea una habilidad con su categoría (técnica, funcional, metodológica, herramienta o tecnología), **Entonces** aparece en el catálogo agrupada por categoría.
2. **Dado** un intento de crear una habilidad ya existente con diferencias de mayúsculas o espacios sobrantes, **Cuando** el usuario confirma, **Entonces** el sistema impide el duplicado.
3. **Dada** una habilidad asociada a una o varias experiencias, certificaciones o proyectos, **Cuando** el usuario la consulta, **Entonces** puede ver en qué elementos se utiliza.
4. **Dada** una habilidad en uso, **Cuando** el usuario la elimina, **Entonces** el sistema le informa en cuántos elementos se usa y, tras confirmar, elimina los vínculos sin borrar las experiencias, certificaciones ni proyectos.

---

### User Story 6 - Gestionar proyectos (Priority: P3)

Como usuario, quiero registrar mis proyectos con descripción, rol, habilidades y tecnologías asociadas, resultados, enlace y fechas, para demostrar experiencia práctica complementaria a mi historial laboral.

**Why this priority**: Los proyectos refuerzan el perfil técnico, pero no todos los usuarios los necesitan desde el primer uso.

**Independent Test**: Registrar un proyecto con sus campos y fechas, asociarle una habilidad, editarlo y eliminarlo con confirmación.

**Acceptance Scenarios**:

1. **Dado** un perfil sin proyectos, **Cuando** el usuario registra un proyecto con nombre, descripción y fechas, **Entonces** aparece en la lista ordenada de más reciente a más antiguo.
2. **Dado** un intento de guardar un proyecto con fecha de fin anterior a la de inicio, **Cuando** el usuario confirma, **Entonces** el sistema lo bloquea y explica el motivo.
3. **Dado** un proyecto registrado, **Cuando** el usuario le asocia habilidades del catálogo (incluidas las tecnologías), **Entonces** la relación es visible tanto desde el proyecto como desde cada habilidad.
4. **Dado** un proyecto existente, **Cuando** el usuario lo edita o elimina con confirmación, **Entonces** los cambios se reflejan y ninguna habilidad del catálogo resulta afectada.

---

### Edge Cases

- **Experiencia marcada como actual y luego con fecha de fin**: al agregar una fecha de fin, deja de considerarse vigente; al quitarla, vuelve a considerarse actual.
- **Fechas de fin futuras** (por ejemplo, estudios en curso con finalización estimada): se permiten siempre que no sean anteriores a la fecha de inicio.
- **Certificaciones sin fecha**: se permiten y se muestran sin fecha, sin que el registro se considere incompleto.
- **Habilidad duplicada** con diferencias de mayúsculas/minúsculas o espacios sobrantes: se bloquea el duplicado y se sugiere la habilidad existente.
- **Eliminar una habilidad en uso**: el sistema informa cuántos elementos la usan; al confirmar, los vínculos se eliminan pero las experiencias, certificaciones y proyectos permanecen intactos.
- **Eliminar una experiencia, certificación o proyecto**: las habilidades asociadas permanecen en el catálogo; solo desaparecen los vínculos.
- **Perfil vacío**: cada sección muestra un estado vacío con orientación sobre cómo comenzar.
- **Registros sin fecha de inicio** (permitidos porque las fechas son opcionales): se muestran al final del orden cronológico de su sección, sin marcarse como vigentes ni en curso.
- **Fechas sin mes** (solo año): se permiten sin advertencia; se comparan y ordenan como comienzo del año (inicio) o fin del año (fin), según FR-034.
- **Descripciones extensas**: el sistema conserva el texto completo sin truncarlo y lo muestra de forma legible.
- **Texto con acentos y caracteres especiales**: se almacena y muestra correctamente en todas las secciones.
- **Fallo durante el guardado**: el sistema informa el problema y conserva lo ingresado en pantalla para reintentar, sin pérdida silenciosa de datos.

## Requirements _(mandatory)_

### Functional Requirements

**Datos personales y resumen profesional**

- **FR-001**: El sistema DEBE mantener un único Perfil Profesional Maestro, editable en cualquier momento, que agrupe toda la información profesional del usuario.
- **FR-002**: El sistema DEBE permitir registrar y editar los datos personales: nombre completo, título profesional, teléfono, correo electrónico, ubicación, LinkedIn, GitHub y sitio web.
- **FR-003**: El sistema DEBE permitir registrar múltiples enlaces adicionales, cada uno con su etiqueta y dirección.
- **FR-004**: El sistema DEBE permitir registrar y editar un resumen profesional como texto base reutilizable.
- **FR-005**: El sistema DEBE validar el formato del correo electrónico y de los enlaces antes de guardar, mostrando un mensaje claro cuando el formato sea inválido.
- **FR-006**: El sistema NO DEBE guardar los datos personales cuando el nombre completo esté vacío.

**Experiencia laboral**

- **FR-007**: El sistema DEBE permitir crear, consultar, editar y eliminar experiencias laborales con: empresa, cargo, fecha de inicio, fecha de fin, descripción, responsabilidades y logros.
- **FR-008**: El sistema DEBE permitir marcar una experiencia como actual, sin exigir fecha de fin.
- **FR-009**: El sistema DEBE rechazar el guardado cuando la fecha de fin sea anterior a la fecha de inicio, explicando el motivo.
- **FR-010**: El sistema DEBE mostrar las experiencias en orden cronológico descendente (más reciente primero).
- **FR-011**: El sistema DEBE permitir asociar a cada experiencia una o más habilidades existentes en el catálogo, incluidas las de categoría Tecnologías.

**Educación**

- **FR-012**: El sistema DEBE permitir crear, consultar, editar y eliminar registros de educación con: institución, programa, grado o nivel, fecha de inicio, fecha de fin y descripción.
- **FR-013**: El sistema DEBE permitir registrar estudios en curso, sin exigir fecha de fin.
- **FR-014**: El sistema DEBE aplicar a la educación la misma validación de coherencia de fechas que a la experiencia laboral (FR-009).

**Cursos y certificaciones**

- **FR-015**: El sistema DEBE permitir crear, consultar, editar y eliminar cursos y certificaciones con: nombre, institución, fecha, duración, código o identificador, URL de validación y categoría.
- **FR-016**: El sistema DEBE permitir asociar a cada curso o certificación una o más habilidades del catálogo.
- **FR-017**: El sistema DEBE permitir guardar cursos o certificaciones sin fecha, sin considerarlos incompletos.
- **FR-018**: El sistema DEBE advertir cuando ya exista un curso o certificación con el mismo nombre e institución, y solo permitir continuar con confirmación explícita.

**Habilidades**

- **FR-019**: El sistema DEBE permitir crear, consultar, editar y eliminar habilidades clasificadas en, al menos: técnicas, funcionales, metodológicas, herramientas y tecnologías. El MVP no incluye niveles de dominio (básico, intermedio, avanzado).
- **FR-020**: El sistema DEBE impedir habilidades duplicadas, ignorando diferencias de mayúsculas/minúsculas y espacios sobrantes.
- **FR-021**: El sistema DEBE permitir consultar, para cada habilidad, en qué experiencias, cursos y certificaciones, y proyectos se utiliza.

**Proyectos**

- **FR-022**: El sistema DEBE permitir crear, consultar, editar y eliminar proyectos con: nombre, descripción, rol, resultados, URL, fecha de inicio y fecha de fin.
- **FR-023**: El sistema DEBE permitir asociar a cada proyecto habilidades del catálogo, incluidas las de categoría Tecnologías.
- **FR-024**: El sistema DEBE aplicar a los proyectos la misma validación de coherencia de fechas que a la experiencia laboral (FR-009).

**Transversales**

- **FR-025**: El sistema DEBE tratar el Perfil Profesional Maestro como fuente única de verdad: cada dato se registra una sola vez y toda corrección se refleja automáticamente en todas las vistas que lo utilizan, sin copias obsoletas.
- **FR-026**: El sistema DEBE solicitar confirmación explícita antes de eliminar cualquier registro.
- **FR-027**: El sistema DEBE conservar toda la información registrada entre sesiones de uso, sin pérdida de datos.
- **FR-028**: El sistema DEBE ofrecer navegación desde el menú principal hacia las secciones del perfil: datos personales y resumen, experiencia, educación, cursos y certificaciones, habilidades y proyectos.
- **FR-029**: El sistema DEBE mostrar un estado vacío con orientación en cada sección que no tenga registros.
- **FR-030**: El sistema DEBE almacenar y mostrar correctamente texto con acentos y caracteres especiales en todos los campos.
- **FR-031**: El sistema NO DEBE enviar información del perfil a servicios externos como parte de esta capacidad.
- **FR-032**: El sistema NO DEBE permitir editar documentos finales (CV, PDF) como fuente de datos; toda modificación de contenido se realiza sobre las entidades del perfil.
- **FR-033**: El sistema DEBE exigir como únicos campos obligatorios: empresa y cargo (experiencia), institución y programa (educación), nombre (curso o certificación), nombre y categoría (habilidad) y nombre (proyecto); todos los demás datos son opcionales, aplicando la validación de coherencia de fechas cuando se informen.
- **FR-034**: El sistema DEBE permitir registrar cada fecha con precisión de mes y año, o solo año cuando el mes no se recuerde; para comparar y ordenar rangos, un año sin mes se interpreta como comienzo del año en fechas de inicio y como fin del año en fechas de fin.

### Key Entities _(include if feature involves data)_

- **Perfil Profesional Maestro**: contenedor raíz único del usuario (aplicación monousuario). Agrupa datos personales, resumen, experiencia, educación, cursos y certificaciones, habilidades y proyectos. No existe más de un perfil.
- **Datos personales**: nombre completo (obligatorio), título profesional, teléfono, correo electrónico, ubicación, enlaces (LinkedIn, GitHub, sitio web) y enlaces adicionales ilimitados con etiqueta y dirección.
- **Resumen profesional**: texto base reutilizable, editable, que alimentará los resúmenes de los futuros CV.
- **Experiencia laboral**: empresa, cargo, rango de fechas (abierto cuando es actual; mes y año, o solo año), descripción, responsabilidades y logros; se relaciona con una o más habilidades (las tecnologías son habilidades de categoría Tecnologías). Varias experiencias pueden solaparse en el tiempo (por ejemplo, trabajo simultáneo).
- **Educación**: institución, programa, grado o nivel, rango de fechas (abierto cuando está en curso; mes y año, o solo año) y descripción.
- **Curso / certificación**: nombre, institución, fecha (opcional), duración, código o identificador, URL de validación y categoría; se relaciona con una o más habilidades. La relación con el archivo PDF original se definirá en la capacidad de repositorio documental.
- **Habilidad**: nombre normalizado único y categoría (técnica, funcional, metodológica, herramienta o tecnología); sin nivel de dominio en el MVP; se relaciona con experiencias, cursos y certificaciones, y proyectos. Las tecnologías de uso profesional (p. ej., PHP, React, SQL) se registran aquí con la categoría Tecnologías: no existe un catálogo separado de tecnologías.
- **Proyecto**: nombre, descripción, rol, resultados, URL y rango de fechas (mes y año, o solo año); se relaciona con habilidades, incluidas las de categoría Tecnologías.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-002**: El 100% de los datos registrados permanece intacto y accesible tras cerrar y volver a abrir la aplicación.
- **SC-003**: El 100% de las correcciones sobre un dato se refleja de inmediato en todas las vistas que lo utilizan, sin quedar valores obsoletos ni copias desincronizadas.
- **SC-004**: El 100% de los intentos de guardar con fechas incoherentes (fin anterior a inicio) es bloqueado con un mensaje que explica el motivo.
- **SC-005**: Registrar una experiencia laboral o una certificación nueva toma menos de 2 minutos.
- **SC-006**: Al menos el 90% de los usuarios encuentra cualquier sección del perfil en un máximo de 2 clics desde el menú principal, en su primer intento.
- **SC-007**: El 100% de las eliminaciones requiere confirmación explícita; ninguna acción destructiva ocurre en un solo paso.

### Criterios retirados

- **SC-001 — Retirado formalmente el 2026-10-22 por decisión del responsable.** Texto original: «Un usuario nuevo puede registrar su perfil completo —datos personales, una experiencia, una entrada de educación, una certificación, cinco habilidades y un proyecto— en menos de 20 minutos.» Motivo: el flujo previsto de captura y aprovechamiento de la información profesional contempla asistencia mediante inteligencia artificial; la duración de entrada manual no constituye una medida representativa del objetivo futuro del producto. No fue medido ni aprobado y no se sustituye por otro criterio.

## Pruebas Requeridas

- Persistencia íntegra del perfil completo tras reiniciar la aplicación.
- Reglas de validación: nombre obligatorio, formatos de correo y enlaces, coherencia de fechas en experiencia, educación y proyectos.
- Prevención de habilidades duplicadas (mayúsculas y espacios).
- Relaciones habilidad-experiencia, habilidad-certificación y habilidad-proyecto, incluyendo la eliminación de vínculos sin afectar a las entidades.
- Confirmación obligatoria antes de eliminar (caso confirmado y caso cancelado).
- Orden cronológico descendente y marcado de elementos vigentes o en curso.
- Comportamiento de cursos o certificaciones sin fecha.
- Estados vacíos con orientación.
- Acentos, caracteres especiales y descripciones extensas.

## Assumptions

- Aplicación local monousuario: existe un único Perfil Profesional Maestro; no se contempla autenticación ni multiusuario (fuera del MVP).
- El nombre completo es el único campo obligatorio de los datos personales; el resto puede completarse progresivamente.
- Las categorías de habilidades son un conjunto fijo inicial (técnicas, funcionales, metodológicas, herramientas y tecnologías); la gestión de categorías personalizadas no forma parte de esta capacidad.
- La eliminación de registros es definitiva y siempre requiere confirmación; no se incluye papelera ni recuperación en el MVP.
- El orden cronológico descendente es el orden por defecto en experiencia, educación, cursos y proyectos.
- La carga, conservación y asociación de certificados PDF se especificará en la capacidad de repositorio documental; esta capacidad no gestiona archivos.
- Los perfiles objetivo (Analista de Sistemas y Programador/Desarrollador) y sus reglas de relevancia se especificarán en una capacidad posterior.
- El registro de información es manual: no se incluye importación desde CV existente, LinkedIn u otras fuentes automáticas.
- No se incluye exportación ni restauración de datos en esta capacidad; el respaldo se evaluará en una especificación posterior.
- La categoría de cursos y certificaciones (p. ej., Programación, Análisis, Gestión) y su duración se registran como texto libre en el MVP; no se define un catálogo fijo de categorías.
- Las fechas se registran con precisión de mes y año, o solo año (sin día), según FR-034.
- La interfaz está en español y el contenido admite texto multilingüe con acentos.
- Las tecnologías no se modelan como catálogo separado: son habilidades de categoría Tecnologías dentro del catálogo único de habilidades.
- El MVP no incluye niveles de dominio para las habilidades; incorporarlos más adelante requiere una ampliación explícita de alcance.
- Cada vista lee siempre de las entidades del Perfil Maestro; no se generan copias de datos por sección.
