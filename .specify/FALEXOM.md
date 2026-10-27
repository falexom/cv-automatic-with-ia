# Integración operativa FALEXOM / Spec Kit

Constitución única: `.specify/memory/constitution.md` (contenido y versión preservados).
AGENTS.md define la política local; los comandos y plantillas siguen siendo Spec Kit.

## Uso recomendado en OpenCode

Desde este repositorio, iniciar `aom` o `falexom` y seleccionar
`falexom-orchestrator` con `/agents`. Solicitar un objetivo autorizado y pedir
que utilice el ciclo Spec Kit. Los comandos `/speckit.specify`, `clarify`, `plan`,
`tasks` e `implement` contienen las instrucciones locales de coordinación.
La selección de Orchestrator sigue siendo manual; no cambia el predeterminado.
No iniciar otro SPEC si el objetivo es reanudar uno existente.

Para SPEC, Developer prepara/reanuda ramas y ejecuta scripts; también implementa
código y realiza commits de implementación. Analyst redacta requisitos y, en el
flujo incremental aprobado, prepara la rama y el commit documental inicial bajo
AGENTS.md. Architect redacta el diseño; Project Manager, tareas; QA verifica
independientemente.
Orchestrator transmite las instrucciones del comando a cada especialista,
revisa evidencia y conserva los gates humanos de especificación y plan.

## Ejecutor local (shell requiere aprobación selectiva)

Desde la raíz del repositorio, durante un encargo nuevo autorizado:

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

Developer realiza el staging y los commits de implementación mediante el ejecutor
local y rutas explícitas revisadas:

```sh
python3 .specify/scripts/falexom-workflow.py stage ruta/archivo.ext
python3 .specify/scripts/falexom-workflow.py commit --message "Descripción del cambio" ruta/archivo.ext
```

En una historia incremental, Analyst puede registrar únicamente el archivo
aprobado de `specs/stories/` como commit documental inicial en su rama, después
de la autorización humana específica y solo si el permiso efectivo lo permite.
Revisa un índice vacío y un diff staged que contenga exactamente ese archivo;
no incluye código ni realiza esta operación en `main`. En OpenCode, la creación
de rama, `git add` de ese Markdown y `git commit` requieren aprobación `ask` del
perfil; en Codex dependen del permiso y sandbox de la sesión. Si se deniegan,
detente sin cambiar de herramienta o permisos.

El ejecutor `stage`/`commit` no realiza ese primer commit: su `check` exige que
la historia aprobada ya conste en `HEAD`. Tras el commit documental, Developer
ejecuta `check` antes de implementar; los commits de implementación siguen usando
el ejecutor local.

No acepta directorios, rutas externas, symlinks/hardlinks, secretos, controles
ni configuración de integración. Commit exige índice exactamente coincidente,
sin modificaciones posteriores de esos archivos. Hooks personalizados requieren
commit manual tras revisión: no se desactivan. No certifica contenido ni aceptación;
revisar diff y ejecutar las validaciones reales antes del cierre. No hay operaciones
merge/push/reset/clean. Merge a main requiere aprobación humana y ejecución manual.

## CLI de Spec Kit compatible

El paquete specify-cli 1.1.0 no se modifica. Su override soportado
`SPECKIT_INTEGRATION_OPENCODE_EXECUTABLE` selecciona el adaptador local por proceso.
Usar `.specify/scripts/speckit-local --profile aom <argumentos de specify>`
(o `--profile falexom`). No utilizar el workflow CLI global sin ese adaptador.

El adaptador acepta los cinco comandos del ciclo. Convierte `run --command`
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
La instalación deja cambios de integración sin commit en el repositorio; deben
versionarse mediante revisión humana antes de preparar un SPEC desde main limpio.
SPEC-002 permanece cerrado; T063/T064 y SPEC-003 no cambian.

## Recuperación de ramas históricas y gate previo al desarrollo

Antes de delegar código, Orchestrator exige AGENTS.md, Constitución y ejecutor;
si faltan, solicita preparación humana sin desarrollar ni reconstruir integración.
Para un SPEC existente, Developer ejecuta primero resume/check, como encargo
separado de implementación. Para una historia incremental ya preparada por
Analyst, ejecuta solo check: `resume` está limitado a ramas SPEC.
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
Esta recuperación no cierra SPEC-001 ni acredita validación funcional del escenario 4.5.
