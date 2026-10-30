# CV Automático — FALEXOM v2

## Autoridad y alcance

La Constitución en `.specify/memory/constitution.md` rige los principios y restricciones del producto. Este archivo define la operación del proyecto; `.specify/FALEXOM.md` la complementa. Las historias aprobadas y sus criterios de aceptación definen el contrato de cada funcionalidad o fix. No alterar la Constitución, contratos aprobados ni alcance sin la decisión humana correspondiente.

Consultar `docs/roadmap.md` y las historias en `specs/stories/` para conocer estado, prioridades y aceptación. No deducir trabajo pendiente ni autorización de instrucciones históricas o de la existencia de una rama.

## Flujo de trabajo

1. **Codex Plan:** analizar y aclarar requisitos en modo de solo lectura; reutilizar decisiones vigentes y proponer historias pequeñas con criterios de aceptación verificables. No escribir archivos desde Plan.
2. **SpecKit:** utilizar únicamente Constitution, Specify y Clarify cuando corresponda, con sus comandos, rutas y plantillas oficiales compatibles con 1.1.0. Ejecutar las escrituras en una sesión nativa y una fase expresamente autorizadas. No iniciar una especificación nueva para reanudar una existente.
3. **Historia aprobada:** toda funcionalidad o fix independiente requiere su propia historia expresamente aprobada, incluso si deriva de un SPEC. Registrar el contrato autorizado en `specs/stories/` con ID, alcance, criterios y estado `APROBADA_PARA_DESARROLLO` antes de implementar. La aprobación de contenido no autoriza por sí sola commits ni integración.
4. **Descomposición:** definir siempre las tareas y verificaciones antes de implementar. Registrarlas en la conversación o en un artefacto autorizado; `tasks.md` es opcional según complejidad. No usar el ciclo Plan/Tasks/Implement de SpecKit para conducir la implementación.
5. **OpenCode Build nativo:** implementar exclusivamente la historia aprobada, en su propia rama o worktree, desde la base acordada. Conservar el contrato funcional aprobado; si hace falta ampliarlo, detener el cambio de alcance y consultar al usuario.
6. **Verificación técnica:** realizar comprobaciones focalizadas y proporcionadas al riesgo, como sintaxis, tipos, lint o compilación pertinentes. Respetar la cobertura de capacidades críticas exigida por el Principio XXI; si requiere pruebas adicionales fuera de lo autorizado, explicar la necesidad y solicitar autorización. No ejecutar suites completas ni automatizaciones funcionales adicionales sin autorización.
7. **Aceptación humana:** las pruebas funcionales y visuales corresponden al usuario. No declarar aceptación ni cierre a partir de comprobaciones técnicas. Después, realizar revisiones QA y de seguridad según riesgo mediante las herramientas nativas; no se requieren agentes personalizados. Si una corrección cambia comportamiento, repetir la aceptación afectada.
8. **Cierre Git:** revisar el diff y las rutas exactas antes de staging. Los commits documentales, de implementación y de cierre requieren autorización expresa correspondiente. Las integraciones y publicaciones requieren su propia autorización; nunca son automáticas.

## Git y protección del trabajo

Antes de escribir, comprobar rama, HEAD, árbol, índice, archivos sin seguimiento y worktrees. No implementar funcionalidades en `main`. Si existen cambios o una rama activa de otro encargo, detenerse y acordar una forma de aislar el trabajo con Git o worktrees nativos. No hacer stash, reset, descartes ni cambios de rama que arrastren modificaciones sin autorización.

Relacionar explícitamente la rama, la historia aprobada y el SPEC pertinente, si existe. La revisión del archivo aprobado, su estado y sus criterios se realiza con lectura y Git nativos; no depende de un registro ejecutable ni de un formato obligatorio de nombre de rama. No modificar `.specify/feature.json` para registrar historias: es contexto nativo de especificaciones, no un estado de implementación.

## Herramientas y permisos

FALEXOM v2 es una metodología. Utilizar Codex y OpenCode nativos, SpecKit para especificación y Git/worktrees nativos. Las cuatro skills consultivas de Codex (Product Manager, Analyst, Project Manager y Architect) son opcionales; no ejecutan ni coordinan trabajo y no conceden permisos.

No cargar perfiles, adaptadores, plugins, ejecutores ni agentes especializados como requisito del flujo. Respetar los permisos efectivos; no ampliar aprobaciones, cambiar herramientas ni alterar controles para eludir una denegación. No cambiar configuraciones globales, credenciales ni datos ajenos al encargo. Informar hechos verificados, limitaciones, bloqueos y comprobaciones pendientes con precisión.
