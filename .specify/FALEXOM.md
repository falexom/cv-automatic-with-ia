# Integración operativa FALEXOM / Spec Kit

Constitución única: `.specify/memory/constitution.md` (contenido y versión preservados).
`AGENTS.md` es la referencia principal de gobernanza del proyecto; este documento es complementario y debe mantenerse coherente con ella. Los comandos y plantillas SPEC siguen siendo Spec Kit.

## Flujo principal Plan/Build

Plan analiza el encargo y el proyecto en modo de solo lectura; propone historias
pequeñas con criterios de aceptación y no modifica archivos. El usuario aprueba
expresamente una historia. Entonces Build verifica que árbol e índice estén
limpios y autorizados, crea una rama desde la base aprobada, registra únicamente
el contrato aprobado en `specs/stories/` y realiza el commit documental inicial
con solo ese Markdown, sujeto a los permisos efectivos y a la revisión exacta del
diff staged. Antes de implementar y antes de cada fase o escritura de producto,
Build ejecuta `check`; solo implementa cuando devuelve `MODE=story` y
`BASE_READY=true`. Build limita el trabajo a la historia aprobada, ejecuta
verificaciones proporcionales y requiere autorización para el commit de
implementación/cierre después de la aceptación funcional y visual del usuario.
La integración a `main` requiere autorización humana y controles Git vigentes.

No se requiere seleccionar Orchestrator ni otro especialista para este flujo.
Los especialistas FALEXOM siguen disponibles como apoyo opcional.

Para los flujos SPEC que lo requieran se conservan Spec Kit y sus gates. Build
conduce el flujo y puede ejecutar los pasos autorizados directamente. Los
especialistas son apoyo opcional: Analyst puede apoyar requisitos, Architect el
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

El adaptador es una vía opcional para flujos SPEC, no un requisito del ciclo
incremental Plan/Build. Acepta los cinco comandos del ciclo. Convierte
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
La instalación deja cambios de integración sin commit en el repositorio; deben
versionarse mediante revisión humana antes de preparar un SPEC desde main limpio.
SPEC-002 permanece cerrado; T063/T064 y SPEC-003 no cambian.

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
Esta recuperación no cierra SPEC-001 ni acredita validación funcional del escenario 4.5.
