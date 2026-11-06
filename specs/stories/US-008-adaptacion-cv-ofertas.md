# US-008 — Adaptar CV a una oferta laboral

**Identificador:** US-008  
**Estado:** APROBADA_PARA_DESARROLLO — aprobada por el responsable el 2026-11-06  
**SPEC relacionada:** [SPEC-008 — Adaptación de CV a una oferta laboral](../008-adaptacion-cv-ofertas/spec.md)  
**Fuente contractual:** este archivo contiene el único contrato funcional de SPEC-008. La SPEC mantiene contexto y dependencias, sin duplicar estos criterios.

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
2. **CA-02 — Consentimiento y datos:** antes de enviar, se muestran el destino y los campos concretos definidos en Datos enviados y privacidad. Sin consentimiento no se envía información.
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
