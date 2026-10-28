# Integración operativa FALEXOM v2 / Spec Kit

Constitución única: `.specify/memory/constitution.md` (contenido y versión preservados).
`AGENTS.md` es la referencia principal de gobernanza del proyecto; este documento es complementario y debe mantenerse coherente con ella. Los comandos y plantillas SPEC siguen siendo Spec Kit.

## Flujo principal Plan/Build

Codex/OpenCode Plan → SpecKit (Constitution, Specify, Clarify, según corresponda)
→ historias aprobadas → planificación de tareas → Build → verificaciones técnicas
→ aceptación funcional y visual humana → QA/seguridad según riesgo → cierre Git.

Se conservan estructura, comandos, plantillas y artefactos nativos de SpecKit para
Constitution, Specify y Clarify. Sus escrituras requieren una fase autorizada;
Plan sigue siendo de solo lectura. Cada funcionalidad o fix independiente exige
una historia aprobada con criterios de aceptación, también si deriva de un SPEC.
La descomposición técnica de tareas y verificaciones es obligatoria y proporcional
al riesgo; puede registrarse en la conversación o en un artefacto autorizado.
En el flujo incremental `tasks.md` es opcional: no se exigen `plan.md`, `tasks.md`
ni Implement. Esto no suprime los requisitos del ciclo SPEC clásico si se utiliza.

Plan analiza el encargo y el proyecto en modo de solo lectura; propone historias
pequeñas con criterios de aceptación y no modifica archivos. El usuario aprueba
expresamente una historia. Entonces Build verifica que árbol e índice estén
limpios y autorizados, crea una rama desde la base aprobada, registra únicamente
el contrato aprobado en `specs/stories/` y realiza el commit documental inicial
con solo ese Markdown, sujeto a los permisos efectivos y a la revisión exacta del
diff staged. Antes de implementar y antes de cada fase o escritura de producto,
Build ejecuta `check`; solo implementa cuando devuelve `MODE=story` y
`BASE_READY=true`. Build limita el trabajo a la historia aprobada, ejecuta
verificaciones técnicas de código y pruebas focalizadas. No ejecuta suites completas
ni pruebas funcionales automatizadas sin autorización, salvo controles técnicos
mínimos indispensables por riesgo o exigencias vigentes de la Constitución/contrato;
justifica y comunica la excepción. El Principio XXI mantiene la cobertura
automatizada suficiente de capacidades críticas. Reutiliza evidencia y distingue
lo ejecutado, pendiente y bloqueado. La aceptación funcional y visual es exclusiva
del usuario; después se revisan QA y seguridad según riesgo, sin agentes
personalizados obligatorios. Si esa revisión cambia el comportamiento, se repite
la aceptación afectada. Build requiere autorización para el commit de
implementación/cierre después de la aceptación funcional y visual del usuario.
La integración a `main` requiere autorización humana y controles Git vigentes.

La metodología no exige perfiles especializados, Orchestrator ni adaptadores de
agentes. Los especialistas FALEXOM son apoyo opcional. La integración instalada
aún contiene dependencias incompatibles con este objetivo: véase la sección de
compatibilidad real. Esta regla no autoriza a saltarlas.

Para los flujos SPEC que lo requieran se conservan Spec Kit y sus gates. Build
conduce el flujo y puede ejecutar directamente los pasos autorizados que la
integración y los permisos efectivos permitan. En la metodología los
especialistas son apoyo opcional (sin eliminar las dependencias heredadas): Analyst puede apoyar requisitos, Architect el
diseño, Project Manager las tareas, QA la revisión independiente, Orchestrator la
coordinación y Developer la implementación. Build conserva el control de la
secuencia, las aprobaciones y los commits descritos en AGENTS.md. Ninguna
modalidad incremental elude controles SpecKit que sean aplicables.

## Ejecutor local (shell requiere aprobación selectiva)

En un flujo SPEC nuevo autorizado, desde la raíz del repositorio, Build utiliza:

```sh
python3 .specify/scripts/falexom-workflow.py prepare nombre-corto "objetivo autorizado"
python3 .specify/scripts/falexom-workflow.py check
```

prepare exige main limpio, calcula el siguiente número incluyendo ramas locales,
crea/selecciona una única rama NNN-nombre y llama al generador real de Spec Kit.
No fetch ni push. Si se repite sobre esa misma rama, verifica sin duplicar.
Si falla el generador después de crear la rama, conserva el estado para revisión;
no borra ramas ni revierte cambios automáticamente.

Reanudar una rama existente: `resume NNN-nombre`. No cambia de rama con trabajo
sin guardar. Resume verifica que la rama existente contenga su spec.md; actualiza explícitamente
el puntero local ignorado feature.json tras seleccionar esa rama. Si el puntero
se versiona o no está ignorado, exige reconciliación manual. No modifica el SPEC.
Los scripts nativos consultan el guard antes de resolver/persistir contexto o
generar estructura. main + feature.json histórico no es un contexto de escritura.

## Staging y commits

En una historia incremental aprobada, Build puede realizar el commit documental
inicial únicamente con el Markdown aprobado, después de verificar árbol e índice
limpios/autorizados, revisar que el diff staged contenga exactamente ese archivo
y confirmar que no está en `main`. La aprobación expresa de la historia autoriza
ese paso solo hasta donde lo permitan los permisos efectivos. En OpenCode, rama,
`git add` y `git commit` dependen de la aprobación `ask` del perfil; en Codex,
del permiso y sandbox de la sesión. Si se deniegan, detenerse sin cambiar de
herramienta o permisos.

El ejecutor `stage`/`commit` no realiza ese primer commit: su `check` exige que
la historia aprobada ya conste en `HEAD`. Tras el commit documental, Build
ejecuta `check` antes de implementar; los commits de implementación requieren
aceptación funcional/visual del usuario y autorización correspondiente, y usan
el ejecutor local con rutas explícitas revisadas:

```sh
python3 .specify/scripts/falexom-workflow.py stage ruta/archivo.ext
python3 .specify/scripts/falexom-workflow.py commit --message "Descripción del cambio" ruta/archivo.ext
```

No acepta directorios, rutas externas, symlinks/hardlinks, secretos, controles
ni configuración de integración. Commit exige índice exactamente coincidente,
sin modificaciones posteriores de esos archivos. Hooks personalizados requieren
commit manual tras revisión: no se desactivan. No certifica contenido ni
aceptación; antes de cada commit revisar exactamente el diff staged y ejecutar
las validaciones reales antes del cierre. Merge a main requiere aprobación humana
expresa y ejecución manual; push nunca
automático. No ejecutar merge, push, rebase, reset ni operaciones destructivas
sin autorización correspondiente.

## CLI de Spec Kit compatible

El paquete specify-cli 1.1.0 no se modifica. Su override soportado
`SPECKIT_INTEGRATION_OPENCODE_EXECUTABLE` selecciona el adaptador local por proceso.
Usar `.specify/scripts/speckit-local --profile aom <argumentos de specify>`
(o `--profile falexom`). No utilizar el workflow CLI global sin ese adaptador.

El adaptador no es requisito del ciclo incremental Plan/Build. En el workflow CLI
SPEC instalado sigue siendo la vía local documentada y exige un agente
especializado por código: no es compatible con el objetivo de operar ese workflow
solo con Plan/Build nativos. No utilizar otra vía para sortear esa dependencia.
Acepta los cinco comandos del ciclo. Convierte
`run --command`
en el cuerpo canónico del Markdown local, sustituye `$ARGUMENTS` y lo envía por
stdin al `opencode run --standalone --agent falexom-orchestrator` soportado.
Conserva JSON/modelo solicitado y XDG del perfil; elimina variables de proveedor
y overrides heredados. No fija proveedor/modelo, no utiliza --auto, no reintenta.
No implementa dispatch nativo de comandos ni simula resultados: ejecuta las
instrucciones reales como entrada. Rechaza metadatos de agente/modelo y plantillas
dinámicas que no sabe interpretar; no pretende soportar toda la API de comandos.

Comprobación sin modelos (con entorno XDG ya seleccionado por el perfil):
`.specify/scripts/opencode-speckit run --command speckit.specify --dry-run "objetivo"`.
También valida el contexto; main sucio o selección incoherente bloquean antes de
iniciar OpenCode. Ejecutar sin --dry-run sí puede consumir cuota del modelo elegido.

El workflow oficial conserva sus gates humanos. Una ejecución no interactiva
no dispone de una aprobación humana ni puede resolver preguntas por sí sola:
puede bloquearse y requerir continuar interactivamente. No es automatización
completa ni un orquestador externo. No usar el adaptador desde un subagente para
crear recursivamente otra sesión de coordinación.

## Límites y conservación

Shell continúa en ask: la evaluación por segmentos de OpenCode no permite una
allowlist textual automática suficientemente segura. Los permisos edit estáticos
no pueden prohibir ediciones según la rama: la protección efectiva añadida cubre
los scripts y operaciones Git, mientras las ediciones nativas requieren respetar
el guard y AGENTS.md. No es una sandbox del sistema. No ampliar permisos para
resolver estas limitaciones. Pruebas multiagente/modelos pendientes, no ejecutadas
por esta instalación.

Los comandos son personalizaciones locales: una actualización/regeneración de
Spec Kit puede reemplazarlas. Revisar el diff y respaldarlas antes de actualizar.
Antes de preparar un SPEC, comprobar el estado Git real y resolver mediante
revisión y autorización humana cualquier trabajo pendiente, sin descartarlo.
`docs/roadmap.md` es la fuente de estado y prioridades; esta gobernanza no mantiene
estados históricos de SPEC ni autoriza por sí sola a iniciar o cerrar trabajo.

## Recuperación de ramas históricas y gate previo al desarrollo

Antes de implementar, Build exige AGENTS.md, Constitución y ejecutor; si faltan,
solicita preparación humana sin desarrollar ni reconstruir integración. En un
SPEC existente ejecuta primero `resume` y `check`; en una historia incremental
aprobada, solo `check`, pues `resume` está limitado a ramas SPEC.
check devuelve HEAD, MAIN y BASE_READY solo si main es antecesor de la rama y la
integración requerida existe. Una base atrasada informa BASE_OUTDATED_FAST_FORWARD;
una divergente, BASE_DIVERGED. Ambas bloquean antes de editar, staging o commit.
No se añade una operación merge al ejecutor ni se cambia ningún permiso general.
Un avance lineal de main a la rama SPEC no integra nada en main, pero puede seguir
requiriendo intervención humana. Ante denegación, detenerse inmediatamente sin
variar comandos, wrappers o especialistas. La protección condicional de edit nativo
sigue siendo una regla operativa; no es una sandbox por rama.

resume sobre la rama ya activa puede corregir el puntero local ignorado incluso
con trabajo funcional pendiente; conserva archivos, staging y otros campos JSON.
No cambia de rama con trabajo sin guardar. Una rama histórica atrasada debe ser
actualizada primero por el usuario autorizado, preservando y verificando su trabajo.
La recuperación técnica no acredita cierre ni aceptación funcional; consultar el roadmap.

## Compatibilidad real y límites de la migración

Esta actualización documental no migra scripts, comandos, permisos ni perfiles.
Ante un paso incompatible, detenerlo y solicitar un encargo específico para la
migración; no omitir guards, editar punteros, usar otro wrapper ni fabricar contexto.

| Control o dependencia | Implementación vigente | Consecuencia |
|---|---|---|
| `prepare`, `resume`, `check` | `falexom-workflow.py`; `check-create` en `create-new-feature.sh` y `check` en `common.sh` antes de resolver contexto | Siguen activos. SPEC nuevo requiere `prepare` desde main limpio; SPEC existente requiere `resume` y `check`. |
| Modo historia | `check` exige rama válida, main antecesor, integración en HEAD y archivo aprobado en HEAD | Admite Build incremental sin `spec.md`, `plan.md`, `tasks.md` ni Implement; no certifica criterios de aceptación ni aprobación humana real. |
| Puntero en modo historia | `check` llama a `spec_context` y lee `.specify/feature.json` incluso antes de elegir modo historia | No lo modifica ni exige coincidencia en modo historia, pero el JSON y su clave `feature_directory` deben existir y ser legibles. No declarar independencia total del puntero. |
| Selección SPEC/historia | Rama `NNN-slug` o `feature|fix/US-NNN-slug`; coincidencia exacta de slug o identificador y una sola historia | Señales simultáneas de SPEC e historia bloquean; no usar la rama SPEC para registrar una historia coincidente. |
| Commit documental inicial | `stage`/`commit` llaman a `check`, que ya exige la historia aprobada en HEAD | No pueden crear el primer commit de historia. El paso Git autorizado descrito arriba sigue sujeto a permisos; no constituye una capacidad nueva del ejecutor. |
| Orquestación en comandos | `.opencode/commands/speckit.specify.md`, `clarify`, `plan`, `tasks` e `implement` ordenan delegar a especialistas | Es una restricción documental de esos comandos aún no migrados; no se elimina con estos dos documentos ni se autoriza a ignorarla durante su ejecución. |
| Adaptador y perfiles | `opencode-speckit` fija `--agent falexom-orchestrator`, instrucciones de coordinación y XDG aom/falexom; `speckit-local` configura el override | Dependencia ejecutable real del workflow CLI; Constitución no figura entre los cinco comandos aceptados por el adaptador. No declarar ese workflow compatible con Plan/Build nativos. |
| Ciclo SPEC clásico | `workflows/speckit/workflow.yml` encadena Specify → Plan → Tasks → Implement con gates humanos de spec/plan | No es el ciclo incremental v2. `setup-tasks.sh` exige `plan.md`; `check-prerequisites.sh` lo exige salvo `--paths-only` y exige `tasks.md` con `--require-tasks`; Implement usa esos requisitos y su gate de checklists. |
| Scripts de contexto SpecKit | `common.sh` sigue resolviendo el directorio SPEC mediante entorno/puntero | El modo historia del guard no convierte estos scripts en herramientas de historias. No invocarlos sobre una historia suponiendo esa compatibilidad. |

El guard no exige especialistas ni planes/tareas para implementar una historia.
Por tanto, el tramo incremental es compatible en código con Plan/Build, sujeto a
preparación Git autorizada y permisos efectivos. El flujo completo v2, incluyendo
especificación mediante los comandos locales y el workflow CLI sin especialistas,
no está migrado: requiere cambios autorizados en comandos y código del adaptador.
No se declara operativo ese tramo ni se prepara trabajo dependiente de él aquí.
La Constitución conserva autoridad: su Principio XX reconoce historias aprobadas;
la disciplina SPEC clásica y sus exigencias aplicables no se derogan por esta guía.
