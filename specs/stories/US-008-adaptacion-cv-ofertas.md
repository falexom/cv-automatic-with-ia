# US-008 — Adaptar CV a una oferta laboral

**Identificador:** US-008  
**Estado:** IMPLEMENTADA_ACEPTADA — el responsable aceptó US-008, R1 y los ajustes de seguridad/publicación el 2026-11-10; integrada localmente en `main` mediante fast-forward el 2026-11-10.
**SPEC relacionada:** [SPEC-008 — Adaptación de CV a una oferta laboral](../008-adaptacion-cv-ofertas/spec.md)  
**Fuente contractual:** este archivo contiene el único contrato funcional de SPEC-008. La SPEC mantiene contexto y dependencias, sin duplicar estos criterios.

## Contrato inicial conservado (aprobado el 2026-11-06)

Las secciones iniciales conservan el alcance implementado, incluidas sus exclusiones de persistencia. La revisión R1 al final está implementada técnicamente y sustituye expresamente las reglas que indica. No hay dos contratos activos: la base con las sustituciones R1 es el contrato vigente; la aceptación funcional y visual sigue pendiente.

## Historia de usuario

Como usuario de CV Automático, quiero contrastar una oferta con mi Perfil Maestro y mis perfiles de CV, revisar una propuesta fundamentada y aprobar cómo usarla, para generar un CV/PDF pertinente sin alterar mis datos profesionales ni mis perfiles existentes.

## Alcance funcional

- El usuario pega una oferta laboral y, después de revisar qué información profesional se enviará a Codex/OpenAI, consiente explícitamente el análisis.
- Codex identifica requisitos y contrasta el texto con el Perfil Maestro y los perfiles de CV. Entrega una recomendación, justificación, contenido y relevancias sugeridos, orden conforme a las reglas existentes, brechas y posibles preguntas aclaratorias.
- Se permiten tres operaciones, claramente diferenciadas:
  - **Reutilizar**: recomendar y utilizar un perfil existente activo exactamente como está almacenado. No cambia campos, selecciones ni relevancias y no requiere volver a guardar el perfil.
  - **Duplicar y adaptar**: tomar un perfil existente (activo o inactivo) como base. Tras revisión y aprobación, crear un perfil nuevo con los cambios aprobados. El perfil de origen permanece intacto.
  - **Crear**: proponer un perfil nuevo específico para la oferta, con registros existentes del Perfil Maestro, relevancias, nombre, título y resumen revisables. Crear únicamente después de aprobación humana.
- Nunca se actualiza ni elimina un perfil preexistente ni se modifica un registro del Perfil Maestro. La adaptación solo puede crear un perfil nuevo y sus asociaciones mediante las validaciones y operaciones existentes de Laravel.
- El agente puede recomendar habilidades, cursos, certificaciones o formación para incorporar o desarrollar. Estas recomendaciones se presentan separadas del contenido seleccionado para el CV e indican si falta evidencia registrada o si se requiere confirmación. No acreditan que el usuario tenga esa capacidad y no se agregan al Maestro, a asociaciones de perfiles ni al CV. El usuario puede incorporarlas mediante las funciones existentes y solicitar luego otro análisis.
- Durante la revisión se pueden hacer preguntas aclaratorias si un dato existente es ambiguo o requiere confirmación. Son opcionales, no convierten cada análisis en cuestionario ni crean una plataforma conversacional. Una respuesta no registrada no se convierte por sí sola en evidencia profesional; el usuario puede actualizar el Maestro por sus funciones existentes y volver a analizar.
- Laravel valida de nuevo la disponibilidad y validez del perfil de origen y de cada registro seleccionado al confirmar. Si la propuesta dejó de ser válida, se solicita revisión y no se guarda parcialmente.
- Tras crear el perfil, el usuario puede usar la vista previa y el PDF existentes. Para la operación reutilizar, el perfil activo se usa tal cual, sin operación de guardado.

## Primer paso interno de la historia: validación técnica

Antes de implementar los pasos funcionales dependientes, verificar la autenticación de ChatGPT disponible en Codex, el modelo técnico `gpt-6-luna`, el esfuerzo `xhigh` (Extra High) y una respuesta estructurada válida usando una oferta real y datos profesionales existentes. Esta validación no agrega otra historia, no incorpora interfaz ni persiste datos en la aplicación. La persistencia local de sesiones de Codex no es bloqueo ni criterio de aceptación.

La validación debe registrar evidencia de autenticación, acceso al modelo y esfuerzo, validez del contrato y límites observados. Si falla cualquier requisito, se detienen los pasos dependientes y se informa el error con alternativas concretas: evaluar una invocación puntual de `codex exec` bajo sus propios controles, conservar el flujo manual con Codex o considerar una API key mediante decisión explícita porque su facturación es separada. No se sustituye el modelo, esfuerzo, autenticación o facturación automáticamente. No se ejecuta esta validación como parte de la preparación documental.

## Datos enviados y privacidad

Antes de cada análisis, se informa y confirma el envío de:

- el texto completo de la oferta pegada;
- título profesional y resumen del Perfil Maestro;
- campos profesionales de experiencias (cargo, empleador, fechas, descripción, responsabilidades, logros y tecnologías), estudios (programa, institución, nivel, fechas y descripción), cursos/certificaciones (nombre, entidad, fechas, categoría y duración), habilidades (nombre y categoría) y proyectos (nombre, rol, fechas, descripción y resultados);
- estado, nombre, título, resumen y selecciones/relevancias de los perfiles de CV disponibles.

No se envían nombre personal, teléfono, correo, ubicación, enlaces personales, fotografía, archivos/rutas, documentos adjuntos, códigos o URL de validación de certificaciones ni IDs de base de datos. Se usan referencias opacas por solicitud; Laravel resuelve esas referencias localmente. No se guarda en la aplicación historial de ofertas, prompts, respuestas ni propuestas. Solo una operación crear/duplicar aprobada persiste el perfil nuevo y sus asociaciones.

La oferta se trata como datos no confiables. Sus instrucciones no cambian el contrato, no se ejecutan ni autorizan acciones. Codex solo produce la propuesta estructurada; no tiene una operación de negocio que escriba perfiles o registros. Toda persistencia la ejecuta Laravel tras aprobación humana y validación propia.

## Contrato JSON propuesto

La salida tiene `schema_version: 1`, estructura cerrada (`additionalProperties: false` en el objeto raíz y los objetos anidados) y se valida antes de mostrarse y antes de persistir. Las referencias de perfiles y registros son opacas; no son IDs de base de datos.

```json
{
  "schema_version": 1,
  "operation": "duplicate",
  "source_profile_ref": "cv_a81",
  "rationale": ["..."],
  "profile_details": {
    "name": "Perfil para ...",
    "professional_title": null,
    "summary": null
  },
  "requirements": [
    {
      "ref": "req_01",
      "text": "...",
      "importance": "required",
      "coverage": "partial"
    }
  ],
  "gaps": [
    {
      "requirement_ref": "req_01",
      "reason": "...",
      "evidence_state": "not_recorded"
    }
  ],
  "selected_content": {
    "work_experiences": [{"ref": "exp_1f2", "relevance": "alta"}],
    "education_entries": [],
    "certifications": [],
    "skills": [],
    "projects": []
  },
  "development_recommendations": [
    {
      "kind": "skill",
      "label": "...",
      "reason": "...",
      "evidence_state": "not_recorded"
    }
  ],
  "clarifying_questions": [
    {"requirement_ref": "req_01", "question": "..."}
  ]
}
```

Reglas del contrato:

- `operation` es `reuse`, `duplicate` o `create`. `reuse` requiere referencia a perfil activo; `duplicate` requiere referencia a perfil origen; `create` no tiene origen.
- Para `reuse`, `profile_details` y `selected_content` son `null`; la aplicación carga y utiliza exactamente el perfil existente. Para `duplicate` y `create`, contienen los campos nuevos y las asociaciones seleccionadas.
- El nombre de perfil nuevo es obligatorio; `professional_title` y `summary` pueden ser texto o `null`, de acuerdo con las validaciones actuales. Los tres campos son revisables. El título y resumen pueden resumir únicamente los datos profesionales recibidos; no inventan experiencia, dominio, titulaciones ni logros. No se exigen referencias por frase ni trazabilidad exhaustiva del texto.
- `requirements[].importance` es `required`, `preferred` o `unspecified`; `coverage` es `supported`, `partial`, `gap` o `needs_confirmation`. No se requieren referencias de evidencia para cada requisito o frase.
- `gaps` identifica requisitos clasificados como brecha o pendientes de confirmación mediante `requirement_ref`, una explicación y `evidence_state` (`not_recorded` o `needs_confirmation`); no acredita ausencia de capacidad.
- `selected_content` solo usa referencias opacas del catálogo profesional recibido y relevancias `alta`, `media` o `baja`. Laravel resuelve y vuelve a validar las referencias.
- Las selecciones se presentan en el orden vigente de Laravel: relevancia descendente y, en empates, el orden cronológico o de categoría/nombre que corresponda. No se persiste un orden manual adicional.
- `development_recommendations[].kind` es `skill`, `course`, `certification` o `education`; `evidence_state` es `not_recorded` o `needs_confirmation`. Esta lista nunca forma parte de `selected_content`.
- `clarifying_questions` puede estar vacío. Las preguntas no son un requisito previo para cada análisis.

## Criterios de aceptación

1. **CA-01 — Validación técnica interna:** antes de implementar el flujo dependiente se prueba autenticación, `gpt-6-luna`, `xhigh` y generación estructurada con una oferta real y datos profesionales existentes. Si una verificación falla, el trabajo dependiente se detiene y se presenta evidencia y alternativas; no hay sustitución automática. Esta etapa no agrega interfaz ni persistencia de aplicación.
2. **CA-02 — Consentimiento y datos:** antes de enviar, se muestran el destino, los campos concretos definidos en Datos enviados y privacidad, y el modelo y esfuerzo configurados para Codex, obtenidos de la misma configuración efectiva del backend que usa la invocación. La indicación informa la configuración previa al envío; no afirma disponibilidad ni uso previo. Sin consentimiento no se envía información.
3. **CA-03 — Propuesta válida y fundamentada:** se recibe una salida que cumple el contrato JSON y enumera requisitos, recomendación, selecciones, relevancias, brechas y recomendaciones de desarrollo separadas. No se presentan como hechos experiencia, dominio, titulaciones, logros o formación sin sustento en los datos recibidos.
4. **CA-04 — Reutilizar sin alterar:** si se aprueba `reuse`, el perfil debe seguir activo y disponible; se usa exactamente como existe, sin crear copia, actualizar campos, selecciones o relevancias ni guardar nuevamente.
5. **CA-05 — Duplicar sin alterar origen:** al aprobar `duplicate`, Laravel crea un perfil nuevo y sus asociaciones a partir de las referencias validadas. Ningún campo o asociación del perfil origen ni del Perfil Maestro cambia.
6. **CA-06 — Crear tras aprobación:** al aprobar `create`, Laravel crea el perfil nuevo con nombre, título, resumen, selección y relevancias revisados. Sin aprobación no hay escritura.
7. **CA-07 — Recomendaciones separadas:** habilidades, cursos, certificaciones o formación por incorporar/desarrollar se muestran aparte con `not_recorded` o `needs_confirmation`; nunca se asocian al perfil ni aparecen en el CV por esta propuesta.
8. **CA-08 — Preguntas opcionales:** cuando haya ambigüedad, el usuario puede responder o descartar preguntas. No se exige responder para terminar el análisis, no se conservan las respuestas como historial ni se convierten en registros profesionales automáticamente.
9. **CA-09 — Revalidación y atomicidad:** al confirmar, Laravel comprueba que el perfil de origen requerido siga disponible y, para `reuse`, activo; también confirma que todos los registros actuales/seleccionados continúen existiendo y perteneciendo al Perfil Maestro. Si una referencia, nombre o condición dejó de ser válida, solicita revisar la propuesta y no guarda ningún cambio parcial.
10. **CA-10 — Uso de CV/PDF actual:** después de crear, el perfil nuevo se puede seleccionar y generar con la vista previa y el PDF actuales; con `reuse`, se usa el perfil activo original. No se crea renderer ni orden manual paralelo.
11. **CA-11 — Sin historial ni cambios destructivos:** la aplicación no guarda oferta, prompt, respuesta o propuesta; la historia no actualiza ni elimina perfiles o registros preexistentes y no incorpora snapshots o versionado.
12. **CA-12 — Fallos sin fallback silencioso:** error de autenticación, modelo/esfuerzo, esquema o servicio no produce escrituras parciales ni selección automática de otro modelo, proveedor o modalidad de facturación.

## Requisitos funcionales

- **FR-001**: informar y consentir el envío de datos antes de analizar.
- **FR-002**: emitir exclusivamente JSON estructurado según este contrato y validar su estructura y referencias.
- **FR-003**: ofrecer las operaciones `reuse`, `duplicate` y `create` con las diferencias y restricciones establecidas.
- **FR-004**: permitir revisar, editar, aprobar, cancelar y, opcionalmente, responder preguntas aclaratorias.
- **FR-005**: separar recomendaciones de aprendizaje de las selecciones que aparecerán en el CV.
- **FR-006**: para `reuse`, utilizar el perfil activo sin guardarlo ni modificarlo; para `duplicate` y `create`, guardar exclusivamente un perfil nuevo y sus asociaciones.
- **FR-007**: impedir que Codex escriba directamente a la base de datos, modifique el Maestro o cambie perfiles existentes.
- **FR-008**: al confirmar creación, revalidar perfiles y registros seleccionados; si la validación falla, no persistir parcialmente y solicitar revisión.
- **FR-009**: reutilizar generación, vista previa y PDF existentes; no guardar historial de ofertas o propuestas.
- **FR-010**: no cambiar automáticamente el modelo, el esfuerzo, el proveedor o la facturación ante un fallo.

## Criterios de éxito

- **SC-001**: ninguna oferta o dato profesional se envía antes del consentimiento explícito.
- **SC-002**: el 100 % de las asociaciones nuevas pasa la validación de pertenencia, unicidad y relevancia de Laravel; las asociaciones inválidas producen cero persistencia parcial.
- **SC-003**: la operación `reuse` no modifica ni vuelve a guardar el perfil; las operaciones de adaptación crean un perfil nuevo y conservan intactos los perfiles preexistentes y el Maestro.
- **SC-004**: las recomendaciones de aprendizaje permanecen separadas y no aparecen como competencias del perfil ni del CV sin incorporación por las funciones existentes.
- **SC-005**: el CV/PDF final procede de la funcionalidad existente y refleja el perfil aprobado y los datos actuales.

## Dependencias y exclusiones

- Dependencias: SPEC-001 (fuente profesional), SPEC-003 (perfiles y asociaciones), SPEC-004 (vista previa) y SPEC-005 (PDF). SPEC-002 no se necesita. SPEC-007 está cancelada y no es dependencia.
- Laravel: `POST /profiles`, `StoreCvProfileRequest`, `ValidatesCvProfile` y `CvProfileContent::create` para crear perfiles. No se usa el endpoint de actualización para adaptar un perfil existente.
- Se excluyen: modificar o eliminar perfiles/maestro; cambios de esquema o migraciones; automatizar importación de oferta, postulación o carta; ATS scoring; historial, snapshots o versionado; ordenación manual; persistencia automática de recomendaciones de aprendizaje; plataforma conversacional genérica; renderer o lógica de selección duplicados; servicio Node residente sin necesidad probada.
- No se crea un skill dedicado en esta historia: el caso de uso empieza con instrucciones reutilizables y el contrato JSON.

## Criterios de revisión y verificación

La validación técnica inicial y los flujos de `reuse`, `duplicate`, `create`, revalidación ante referencias obsoletas y exportación con la ruta existente deben comprobarse de forma focalizada durante Build. La prueba técnica no sustituye la aceptación funcional del responsable; no se ejecutan suites completas ni automatizaciones adicionales sin autorización.

## Estado de la implementación inicial

**Estado inicial (2026-11-06):** implementación técnica completada; aceptación del responsable pendiente en ese momento. La aceptación funcional se confirmó el 2026-11-10 como parte del cierre de US-008.

- Rama: `008-adaptacion-cv-ofertas`, creada desde `main` (`2f5871c`), sin modificar `main`.
- Commit preparatorio documental: `722d77f`.
- Validación técnica de CA-01: superada con autenticación ChatGPT, `gpt-6-luna`, esfuerzo `xhigh`, oferta real y datos existentes. Evidencia en [validation-evidence.md](../008-adaptacion-cv-ofertas/validation-evidence.md).
- Implementación: página `/cv/adapt` accesible desde el generador; análisis con Codex; revisión y edición de la propuesta; operaciones `reuse`, `duplicate` y `create`; revalidación de referencias al confirmar; creación atómica mediante las validaciones y la operación existentes de Laravel.
- Verificaciones técnicas: pruebas focalizadas de backend y frontend, comprobación de tipos TypeScript, formato/lint de los archivos modificados, build cliente y SSR. `phpstan` conserva errores previos en archivos no modificados por esta historia.
- Aceptación funcional de US-008 confirmada por el responsable el 2026-11-10; el usuario no registró un desglose de aceptación separado por cada subescenario.
- Implementación inicial conservada en `d8d56f65af1e55f45ac944d9e752d24690c71cc4` (`Implement CV adaptation feature with Codex integration`), posterior a `722d77f`.

### Validación manual sugerida

1. Entrar a `/cv/adapt` desde el generador de CV.
2. Pegar una oferta, revisar la lista de datos que se enviarán, marcar el consentimiento y analizar.
3. Revisar requisitos, brechas, selecciones, recomendaciones de desarrollo separadas y preguntas opcionales.
4. Probar `reuse` (no debe guardar), `duplicate` (el perfil de origen no debe cambiar) y `create`.
5. Confirmar que el perfil creado o reutilizado se abre en `/cv?profile=...` y que la vista previa y el PDF existentes funcionan.
6. Verificar que cancelar no guarda cambios y que una referencia obsoleta produce un error sin persistencia parcial.


## Revisión R1 — Consulta y gestión de la adaptación (ACEPTADA)

**Fecha:** 2026-11-10. **Estado:** aprobada, implementada y aceptada por el responsable el 2026-11-10. Cierre e integración local autorizados; no incluye push ni tag. Sigue siendo la única historia de SPEC-008; base inicial `d8d56f6` conservada.

### Diagnóstico y evidencia del avance

- Git inicial: HEAD `d8d56f6`, árbol e índice limpios, sin archivos sin seguimiento; único worktree en esta rama. No se reescribe ni repite la implementación inicial.
- Código reutilizable: catálogo y referencias opacas, consentimiento, invocación aislada de Codex, esquema/validador, revisión editable, tres operaciones, revalidación y creación transaccional de `CvAdaptationApplier`, gestión de perfiles y generador/PDF existentes.
- Causa raíz: `Cv/Adapt.tsx` mantiene oferta y resultado en `useState`; `CvAdaptationReview.tsx` mantiene respuestas y descartes localmente. Su petición `apply` solo envía operación, referencias, detalles y selección: no envía evaluación ni respuestas. `reuse` retorna sin escribir; `create`/`duplicate` guardan únicamente perfil y asociaciones. La pérdida al salir responde al contrato inicial, no a una persistencia fallida.
- Evidencia técnica histórica: CA-01 y comprobaciones registradas en esta historia y `validation-evidence.md`; no se han vuelto a ejecutar en esta revisión documental. No prueban aceptación humana.
- Historial de aceptación: al descubrirse esta ampliación, el responsable confirmó el funcionamiento de la versión inicial y el problema de pérdida del análisis. Después confirmó la validación funcional de US-008 y R1 y, en esta solicitud, aceptó el cierre con los ajustes de seguridad/publicación. No se inventa evidencia de ejecución individual por subescenario.

### Sustituciones explícitas del contrato inicial

R1 sustituye la prohibición de persistencia del análisis en «Datos enviados y privacidad», CA-08, CA-11, FR-009 y las exclusiones de historial/snapshots y cambios de esquema, **solo** para conservar evaluaciones confirmadas según esta sección. Ajusta CA-04/FR-006: `reuse` sigue sin guardar o modificar el perfil, pero sí guarda una nueva evaluación vinculada. CA-05/06/09 y FR-008 incluyen evaluación y observaciones en la misma operación atómica. El resto permanece vigente, incluidos consentimiento, revisión humana, Laravel como única autoridad de escritura y ausencia de acciones autónomas del agente.

No se guarda historial de prompts, respuestas técnicas completas, propuestas sin confirmar o versiones del CV. El registro histórico acotado de evaluación no es un snapshot de la trayectoria profesional ni un sistema de postulaciones.

### Alcance actualizado y datos mínimos

Como usuario, quiero consultar desde mis perfiles la evaluación histórica de cada oferta confirmada y gestionar sus observaciones para recordar qué requiere atención, sin confundir esa gestión con mis competencias o con una nueva evaluación de Codex.

Al confirmar la adaptación, se revisa y acepta expresamente que Laravel conserve localmente:

- Puesto identificado o «Puesto no identificado»; empresa nullable, solo si se conoce, revisable sin inventarla; fecha/hora del análisis fijada por backend al obtener el resultado y fecha/hora de confirmación separada.
- Requisitos con identificadores locales estables, texto, importancia y cobertura evaluada; brechas y sus motivos/estado de evidencia; recomendaciones y justificación de la recomendación de operación.
- Marca de excluyente **solo** cuando la oferta lo expresa, acompañada de su fragmento mínimo justificativo. `required` no equivale automáticamente a excluyente. Sin soporte explícito, no se marca.
- Preguntas y respuestas aclaratorias vinculadas al requisito, diferenciando respondida, sin respuesta y descartada. Conservar el texto literal de la respuesta y su procedencia «declaración del usuario», separada de la evaluación IA. Una respuesta positiva tampoco acredita una capacidad automáticamente.
- Operación confirmada y vínculo al perfil destino. No se conserva el catálogo completo del Maestro, el prompt, la salida técnica completa, el CV ni documentos.

**Retención:** basta este resumen estructurado, revisable antes de confirmar; no guardar la oferta completa. Permite reconocer la oferta, consultar requisitos y entender pendientes sin retener contactos, instrucciones u otros datos irrelevantes. El fragmento que sustenta un excluyente es la única cita necesaria; no se requiere una reconstrucción de la oferta. No agregar subida de ofertas ni un selector de retención ampliada. El envío de la oferta al análisis conserva el consentimiento y límites iniciales; la consulta y gestión posteriores son locales, sin llamadas a Codex.

La evaluación conserva su contenido y fecha originales. Un nuevo análisis genera otra evaluación al confirmarse; nunca actualiza silenciosamente una anterior. El aviso «Evaluación histórica del [fecha]; no se actualiza con cambios del Maestro o perfil» es siempre visible. No se necesita detectar cambios ni guardar versiones del Maestro.

### Relación mínima con perfiles e integridad

Relación **perfil 1:N evaluaciones**; cada evaluación pertenece a un único perfil destino y puede tener N observaciones. No hace falta una relación N:M ni una entidad postulación.

| Operación | Perfil destino | Evaluación al confirmar |
|---|---|---|
| `reuse` | Existente y activo; sin guardar ni alterar sus campos/asociaciones | Nueva evaluación ligada a ese perfil, aunque ya tenga otras |
| `duplicate` | Nuevo perfil adaptado; origen intacto | Nueva evaluación ligada solo al nuevo; no copiar ni trasladar evaluaciones del origen |
| `create` | Nuevo perfil revisado | Nueva evaluación ligada al nuevo |

En confirmaciones independientes, incluso para el mismo puesto/empresa, se agregan evaluaciones identificables por fecha; no se deduplican por nombre de oferta. El reintento de **la misma confirmación** usa un identificador único validado por Laravel para no duplicar perfiles ni evaluaciones. Cancelar o abandonar antes de confirmar no conserva análisis en la aplicación.

Propuesta física mínima: tabla `cv_adaptations` con FK al perfil, contexto estructurado JSON validado y fecha original; tabla `cv_adaptation_observations` con FK a evaluación, identificador estable, tipo, vínculo a requisito/recomendación/pregunta, estado y nota nullable. El contexto es histórico; estado/nota son editables aparte. Sin copia de registros profesionales ni tabla de versiones. Perfiles actuales admiten cero evaluaciones; migraciones futuras solo aditivas, sin backfill ni reconstrucción de análisis.

Laravel valida estructura cerrada, límites de texto, referencias internas, enumeraciones, pertenencia de observaciones y perfil, procedencia de datos y fecha del análisis (no confiar en una fecha arbitraria del cliente). Conservar un contexto de confirmación autenticado por backend, mínimo y temporal, sin persistir la oferta; revalidarlo al aplicar. No aceptar una evaluación alterada como si fuera salida de Codex. El usuario revisa sus respuestas y metadatos; no edita la cobertura IA.

Una única transacción abarca perfil/asociaciones nuevos, evaluación y observaciones; en `reuse`, evaluación y observaciones con validación del perfil activo. Si falla cualquier parte no queda ningún registro parcial. Un reintento retorna el resultado ya confirmado. No se amplían operaciones sobre Maestro/perfiles ni se habilita escritura del agente.

### Consulta y gestión de observaciones

- En la gestión existente de perfiles, acción «Ver adaptación» abre la evaluación si hay una; con varias, permite elegir por puesto, empresa conocida y fecha (más reciente primero), sin ocultar las anteriores. Disponible también para consultar perfiles inactivos. Con cero, mostrar «Sin adaptación guardada», manteniendo edición y generación existentes.
- El generador sigue mostrando/exportando el CV; no se convierte en gestor de observaciones. Se reutilizan componentes visuales y navegación de Perfiles.
- Crear observaciones para cada brecha, recomendación y pregunta que requiera atención, conservando referencias y tipo. Cada una inicia `pendiente` y permite `resuelto` o `descartado`, volver a `pendiente` y guardar/borrar una nota opcional. No se agrega un historial de cambios de estados.
- La respuesta original es contexto histórico, no la nota editable. Para una ausencia confirmada, el usuario debe marcar explícitamente «Confirmo que no dispongo de este requisito» junto a su respuesta. Guardar esa declaración como `user_confirmed_absent`; no inferirla automáticamente del texto. Por ejemplo, «no tengo esa certificación» se conserva literalmente y, con esa confirmación, se muestra «Carencia confirmada por el usuario». Sin ella, mostrar la respuesta y mantener la incertidumbre, sin atribuir reevaluación a IA.
- Mostrar «Sin información registrada», «Requiere confirmación» o «Carencia confirmada por el usuario» según procedencia. Conservar aparte `not_recorded`/`needs_confirmation` originales de Codex. Resolver/descartar no cambia ninguna de esas evidencias ni la cobertura.
- Cantidades: cubiertos = `supported`, parciales = `partial`, pendientes de cobertura = `gap` + `needs_confirmation`. Cada requisito cuenta una vez; total = suma de los tres. Desglosar los pendientes por brecha y por confirmar. El número de observaciones pendientes es distinto y se etiqueta aparte. No usar porcentajes. Resaltar excluyentes tanto en listado como en el detalle, solo con soporte en la oferta.
- Brechas, declaraciones, notas y recomendaciones quedan fuera de resumen/título profesional, selecciones, CV, PDF normal/documentado y Markdown. Ningún cambio de gestión modifica Maestro, asociaciones o cobertura. Incorporar nuevos datos profesionales sigue las pantallas existentes; requiere otro análisis confirmado para una nueva evaluación.

### Criterios R1 verificables (implementados; aceptación pendiente)

1. **R1-CA-01 — Consulta durable:** confirmar, abandonar y volver desde Perfiles permite leer puesto, empresa conocida o ausencia explícita, fecha original, requisitos/cobertura, brechas, recomendaciones, preguntas y respuestas exactas. Cerrar/reabrir la aplicación conserva esos datos. Antes de confirmar o al cancelar no se crean registros.
2. **R1-CA-02 — Tres operaciones:** dos confirmaciones `reuse` para ofertas diferentes sobre el mismo perfil conservan ambas evaluaciones sin modificarlo. `duplicate` crea perfil y evaluación nuevos sin copiar evaluaciones ni alterar el origen. `create` crea perfil y evaluación nuevos. Con múltiples evaluaciones se puede abrir cada una por su identificador, fecha y contexto.
3. **R1-CA-03 — Respuestas y procedencia:** responder «no tengo esa certificación» y confirmar explícitamente la ausencia conserva texto y marca de declaración humana al volver. Sin esa marca no se infiere carencia. Preguntas sin responder o descartadas conservan su condición. Ninguna respuesta modifica automáticamente cobertura o acredita conocimientos.
4. **R1-CA-04 — Gestión independiente:** cambiar cada tipo de observación entre pendiente/resuelto/descartado, guardar o borrar nota y recargar conserva la elección. No cambia fecha, cobertura original, evidencia IA, respuesta original, Maestro ni perfil. Una observación ajena a la evaluación se rechaza sin cambios.
5. **R1-CA-05 — Cantidades y excluyentes:** con 2 `supported`, 1 `partial`, 1 `gap` y 1 `needs_confirmation`, mostrar 2 cubiertos, 1 parcial y 2 pendientes, total 5. Resolver una observación no cambia esos números. Un requisito obligatorio sin exclusión explícita no se marca excluyente; uno expresamente excluyente muestra su fragmento de soporte. Nunca hay porcentajes.
6. **R1-CA-06 — Separación e historicidad:** editar después Maestro o perfil conserva evaluación/fecha originales y aviso histórico, mientras el CV sigue usando los datos actuales. Notas, brechas, respuestas y recomendaciones no aparecen en vista previa, PDF normal/documentado o Markdown. Un nuevo análisis confirmado agrega otra evaluación sin reemplazar la previa.
7. **R1-CA-07 — Nombre y compatibilidad:** antes de `create`/`duplicate` se puede editar nombre, incluido puesto y empresa conocida; nombre vacío/duplicado se rechaza mediante reglas existentes. Sin empresa no se inventa. Perfiles previos y creados manualmente funcionan con cero evaluaciones; no se reconstruye información anterior. Perfil inactivo permite consultar su evaluación sin habilitar `reuse`.
8. **R1-CA-08 — Atomicidad y reintentos:** referencia obsoleta, contexto alterado, estado inválido o fallo de persistencia no deja perfil/evaluación/observaciones parciales. Reenviar la misma confirmación no duplica datos; confirmar un análisis distinto sí añade evaluación. Laravel ejecuta y valida todas las escrituras.
9. **R1-CA-09 — Minimización:** almacenamiento y avisos reflejan únicamente el contexto aprobado, sin oferta completa, prompt, catálogo del Maestro ni salida técnica bruta; consultar y cambiar estados no envía datos a Codex. El fragmento de excluyente solo contiene el soporte necesario.

### Pasos técnicos

1. Aprobación registrada; comprobar rama, base y limpieza Git. Conservar `d8d56f6` como avance inicial.
2. Extender el esquema estructurado y validadores existentes para puesto/empresa y soporte de excluyentes; conservar cobertura y separar respuestas humanas del contrato de salida IA. Transportar el contexto de análisis y respuestas hasta confirmación con integridad de backend, sin sesiones de oferta durables.
3. Agregar las dos tablas y relaciones aditivas; integrar persistencia e idempotencia en la transacción del applier existente para las tres operaciones. Actualizar aviso de retención y revisión previa; mantener editable el nombre.
4. Añadir consulta desde Perfiles y actualización acotada de estado/nota; reutilizar componentes existentes. No tocar compositores ni renderizadores del CV.
5. Verificación focalizada de esquema/tipos y pruebas críticas de atomicidad, pertenencia, idempotencia, relación 1:N, procedencia humana y separación de exportaciones. No ejecutar suites completas ni automatizaciones funcionales adicionales sin autorización. Aceptación funcional/visual del usuario sobre R1 y escenarios iniciales afectados; después QA/seguridad según riesgo y cierre Git solo con autorización expresa.

**Fuera de R1:** postulación/seguimiento laboral, pipeline, recordatorios, cartas, importación automática, historial de versiones o estados, reevaluación automática, nuevos datos profesionales desde respuestas, retención de oferta completa, reconstrucción de análisis anteriores y operaciones destructivas sobre evaluaciones.

### Estado de implementación y aceptación R1

**Estado:** R1 y los ajustes de seguridad/publicación aceptados por el responsable el 2026-11-10. US-008 se cierra e integra localmente en `main`.

- Implementado: extensión del contrato IA con puesto, empresa y excluyentes; contexto de confirmación cifrado por backend; tablas aditivas `cv_adaptations` y `cv_adaptation_observations`; persistencia transaccional e idempotente para `reuse`, `duplicate` y `create`; consulta desde Perfiles; gestión acotada de observaciones (pendiente/resuelto/descartado y nota); respuestas humanas con declaración explícita de ausencia; conteos de cobertura y aviso histórico.
- Verificaciones: 27 pruebas focalizadas de backend, 25 de frontend, comprobación de tipos TypeScript, Pint, `vp check` focalizado, build cliente/SSR y migraciones locales. `phpstan` conserva 16 errores previos en archivos no modificados por esta historia.
- Limitaciones: no se ejecutó una llamada real al Codex CLI durante esta revisión de seguridad/cierre. `phpstan` conserva 16 errores previos en archivos no modificados. La aceptación corresponde al responsable; no se afirma que cada subescenario se probara individualmente.
- Verificaciones focalizadas del ajuste de autenticación: CLI simulado permitió ChatGPT y rechazó API key/sesión ausente antes de `exec`; se comprobó que las variables API/workload no se heredaron y que CLI inexistente falla cerrado. `node --check`, `php -l`, exclusiones Git y `git diff --check` pasaron. No se repitieron las suites completas.
- Cierre Git: commit de cierre en `008-adaptacion-cv-ofertas`; fast-forward local a `main` el 2026-11-10. Rama de origen conservada. Sin push ni tag.

### Ajustes de publicación y autenticación local

Esta aplicación conserva el alcance monousuario local, sin autenticación web ni despliegue público. El análisis usa Codex CLI, requiere internet y autenticación ChatGPT propia en cada instalación; la aplicación no distribuye credenciales. Antes de `codex exec`, comprobar `codex login status`, permitir únicamente sesión ChatGPT, retirar del entorno del proceso variables de API key e identidad de workload y fallar sin analizar si no se puede verificar el método. No alterar credenciales ni configuración global. El servidor de Laravel y Vite se documentan enlazados a `127.0.0.1`; los archivos de entorno locales y el almacén `auth.json` de Codex deben quedar excluidos de Git, conservando los ejemplos versionados. Compartir código mediante Git, nunca la carpeta de trabajo con datos privados.

**Criterios de validación de seguridad:** (1) con estado ChatGPT, se permite la ruta normal de invocación; (2) con estado API key, sesión ausente, resultado desconocido, error o timeout de `codex login status`, no se ejecuta `codex exec` y se devuelve error controlado; (3) variables de API key e identidad de workload no llegan al proceso de análisis; (4) instalación sin sesión no cambia proveedor ni facturación y muestra un error que indica iniciar sesión con ChatGPT; (5) Laravel y Vite se enlazan a loopback en el inicio local; (6) `.env.example` y otros ejemplos permitidos siguen versionables mientras variantes locales, `.codex/auth.json` y datos de trabajo quedan fuera de Git. Los criterios se verificaron de forma focalizada con CLI simulado y comprobaciones locales; la aceptación final del responsable consta arriba. No se afirma haber probado una instalación nueva ni desplegado la app.
