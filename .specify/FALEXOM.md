# FALEXOM v2 — Guía operativa de CV Automático

`AGENTS.md` define el flujo y `.specify/memory/constitution.md` conserva la autoridad sobre los principios del producto. El estado de las funcionalidades se consulta en `docs/roadmap.md` y las historias; esta guía no mantiene un estado histórico de SPEC ni autoriza nuevas implementaciones.

## Plan, especificación e historias

Codex Plan analiza y aclara en modo de solo lectura. Cuando corresponda, Constitution, Specify y Clarify se ejecutan con las instrucciones oficiales de SpecKit en una sesión nativa con escritura autorizada. Plan no ejecuta esas escrituras. La aprobación de una especificación no sustituye la historia aprobada exigida para cada funcionalidad o fix independiente.

La historia se registra en `specs/stories/`, respetando IDs y convenciones existentes, con alcance, criterios verificables y estado `APROBADA_PARA_DESARROLLO`. Build comprueba la aprobación del usuario y el contenido del contrato antes de implementar. No se necesita un integrador para reconocer la historia: basta la evidencia de aprobación, el archivo y la relación explícita con su rama y, cuando exista, su SPEC.

La descomposición de tareas y verificaciones siempre se realiza antes de implementar; puede quedar en la conversación o en un artefacto autorizado. `tasks.md` es opcional según complejidad. OpenCode Build nativo implementa solo la historia aprobada en una rama o worktree propio. No se usa el workflow completo de SpecKit para implementar.

## Comandos y rutas de SpecKit

Los comandos nativos de OpenCode se conservan en `.opencode/commands/`:

- `/speckit.constitution`: instrucciones en `speckit.constitution.md`; Constitución en `.specify/memory/constitution.md`; plantilla en `.specify/templates/constitution-template.md`.
- `/speckit.specify`: instrucciones en `speckit.specify.md`; especificaciones bajo `specs/` conforme a la resolución oficial; plantilla en `.specify/templates/spec-template.md`.
- `/speckit.clarify`: instrucciones en `speckit.clarify.md`; resuelve el contexto de una especificación existente mediante `.specify/scripts/bash/check-prerequisites.sh --json --paths-only`.

Estas instrucciones también pueden consultarse desde Codex para preparar el análisis. La generación o modificación de artefactos requiere una fase de escritura autorizada, conservando la ruta real y las plantillas oficiales. No se instala en esta migración otro adaptador o un conjunto paralelo de comandos para Codex.

El contexto de especificación sigue los mecanismos oficiales: `SPECIFY_FEATURE_DIRECTORY` y `.specify/feature.json`. Para una consulta de contexto sin persistencia, usar `--paths-only` en `check-prerequisites.sh` o `SPECIFY_FEATURE_NO_PERSIST=1` donde corresponda. No confundir el contexto de SPEC con una historia incremental ni exigir un SPEC nuevo para trabajar con una historia.

Los demás comandos, scripts y workflows oficiales de SpecKit se preservan por compatibilidad de instalación. Su presencia y sus metadatos de handoff no autorizan iniciar Plan, Tasks o Implement de SpecKit ni representan agentes personalizados. FALEXOM v2 usa únicamente Constitution, Specify y Clarify; el paso a implementación es una decisión humana seguida de Build nativo.

## Verificación y cierre

Build realiza verificaciones técnicas focalizadas y proporcionales al cambio. La cobertura de capacidades críticas del Principio XXI sigue vigente; cualquier prueba adicional que exceda la autorización se plantea al usuario. No ejecutar suites completas ni automatizaciones funcionales adicionales sin autorización. Las pruebas funcionales y visuales y su aceptación corresponden al usuario.

QA y seguridad son revisiones posteriores según riesgo, sin agentes obligatorios. Una corrección funcional requiere repetir la aceptación afectada. Revisar el diff y los archivos exactos antes del cierre. Todo commit, incluido uno documental, necesita autorización expresa; integrar o publicar requiere la autorización correspondiente. Utilizar Git y worktrees nativos.

## Compatibilidad y procedencia

Los artefactos del proyecto se mantienen compatibles con SpecKit 1.1.0. La fuente oficial contrastada es `github/spec-kit`, etiqueta `v1.1.0`: https://github.com/github/spec-kit/tree/v1.1.0 . Los scripts `common.sh` y `create-new-feature.sh` se restauraron a esa fuente; sus originales también coinciden con el commit local de inicialización `e7bfb11`. Los cinco comandos que contenían instrucciones de orquestación se contrastaron con las plantillas oficiales y con los hashes del manifiesto de instalación 1.1.0.

Se conserva la personalización de `speckit.tasks.md` que exige pruebas según Constitución y riesgo; no depende de un agente ni ejecutor. Se conservan asimismo las sustituciones normales de instalación para comandos OpenCode y los formatos existentes ajenos a la integración retirada. Los manifiestos y plantillas oficiales mantienen sus versiones.

El CLI global instalado no se actualiza ni reinstala en esta migración. No ejecutar inicializaciones, regeneraciones ni workflows globales para cambiar los artefactos del proyecto incidentalmente. Los comandos Markdown y scripts locales nativos son la vía documentada de esta instalación.

La Constitución conserva referencias históricas en su registro de enmiendas. Son antecedentes documentales y no cargadores ni obligaciones de un sistema de ejecución. Cualquier discrepancia de alcance entre roadmap, historias y Constitución requiere una decisión humana separada; esta migración no modifica esos artefactos.
