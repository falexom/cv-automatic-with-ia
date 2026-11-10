# Feature Specification: Adaptación de CV a una oferta laboral

**Feature Branch**: `008-adaptacion-cv-ofertas`  
**Created**: 2026-11-06  
**Status**: Base APROBADA el 2026-11-06 e implementada en `d8d56f6`; ampliación R1 PENDIENTE_ACEPTACION (2026-11-10), implementación técnica completada  
**Clasificación**: Nueva funcionalidad  
**Input**: Adaptar un CV a una oferta mediante Codex, con datos del Perfil Maestro y los perfiles existentes, revisión humana y persistencia validada por Laravel.

## Problema y objetivo

El usuario necesita enfocar un CV existente o crear una configuración nueva para responder a una oferta laboral, distinguiendo la experiencia registrada de las brechas y de la formación recomendada. La capacidad debe preservar intactos el Perfil Maestro y todos los perfiles existentes, y reutilizar la generación actual de CV/PDF.

## Contrato funcional y única historia

Toda la funcionalidad de esta SPEC pertenece a **una sola historia: [US-008 — Adaptar CV a una oferta laboral](../stories/US-008-adaptacion-cv-ofertas.md)**. US-008 es la fuente única del contrato funcional: alcance, operaciones, datos enviados, JSON, escenarios, requisitos, criterios de éxito y exclusiones. Esta SPEC no mantiene copias de esos criterios ni crea historias adicionales.

La historia comprende la validación técnica de autenticación, modelo/esfuerzo y salida estructurada como su primer paso interno. Si esa validación falla, se detienen los pasos dependientes y se informa la evidencia; no se cambia automáticamente modelo, proveedor ni facturación.

## Dependencias reales y situación actual

- **SPEC-001 — Perfil Profesional Maestro**: fuente de experiencias, estudios, cursos/certificaciones, habilidades y proyectos. El agente no modifica estos datos.
- **SPEC-003 — Perfiles de CV**: ya permite crear y editar perfiles, asociar los cinco tipos de registros y guardar su relevancia. `CvProfileContent::create` crea el perfil y sus asociaciones de forma transaccional; los perfiles existentes no necesitan duplicar registros maestros. No hay operación de clonación dedicada.
- **SPEC-004 — Generador de CV** y **SPEC-005 — Exportación PDF**: aportan la vista previa, selección del perfil activo y generación PDF que se reutilizan sin duplicar reglas ni renderizado.
- Laravel existente: `StoreCvProfileRequest` y `ValidatesCvProfile` comprueban el nombre, que cada referencia exista en el Perfil Maestro, que no haya referencias repetidas y que las relevancias sean alta, media o baja. La creación usa `POST /profiles`; el flujo de adaptación no usa `PUT` ni elimina perfiles.
- **SPEC-002 no es dependencia**: no se envían archivos ni documentos. **SPEC-007 está cancelada** y no guarda relación de dependencia con esta capacidad.

## Contexto técnico y privacidad

La aplicación ya es local y usa React/Inertia y Laravel. La implementación inicial invoca Codex desde un proceso Node de vida corta; no hay motivo demostrado para mantener un servicio Node residente. Node.js 22 y Codex CLI 0.166.0 están instalados y `codex login status` indica inicio de sesión con ChatGPT. `@openai/codex-sdk` no está instalado. El SDK envuelve el CLI y permite una respuesta estructurada; el proceso local no implica inferencia local: la oferta y los campos profesionales expresamente autorizados se envían a Codex/OpenAI. La evidencia histórica del 2026-11-06 confirma autenticación, acceso al modelo y esfuerzo solicitados; no garantiza cuota futura ni disponibilidad permanente (ver `validation-evidence.md`).

Los campos exactos, exclusiones de datos, consentimiento previo y controles para contenido no confiable se definen una sola vez en US-008. La persistencia local de sesiones de Codex no bloquea esta propuesta ni es criterio de aceptación. La base no conserva análisis en la aplicación. La revisión R1 propuesta en US-008 sustituye esa exclusión únicamente para evaluaciones confirmadas y observaciones vinculadas a perfiles; no conserva oferta completa, prompts ni propuestas sin confirmar. Los criterios y la retención mínima se definen solo en US-008.

## Referencias

- [Constitución del proyecto](../../.specify/memory/constitution.md) · [Metodología FALEXOM](../../.specify/FALEXOM.md) · [Roadmap](../../docs/roadmap.md)
- Código existente revisado: `routes/web.php`, `app/Http/Controllers/CvProfileController.php`, `app/Http/Requests/Concerns/ValidatesCvProfile.php`, `app/Support/CvProfileContent.php`, `app/Support/CvPreviewContent.php`, `app/Http/Controllers/CvPdfController.php`.
- [Codex TypeScript SDK](https://developers.openai.com/codex/sdk) · [README del SDK](https://github.com/openai/codex/blob/main/sdk/typescript/README.md) · [Opciones de hilo](https://github.com/openai/codex/blob/main/sdk/typescript/src/threadOptions.ts) · [GPT-6 Luna](https://developers.openai.com/api/docs/models/gpt-6-luna)
- [Uso de Codex con un plan ChatGPT](https://help.openai.com/en/articles/11369540-using-codex-with-your-chatgpt-plan) · [Facturación ChatGPT y API](https://help.openai.com/en/articles/9039756-managing-billing-for-chatgpt-and-the-api-platform) · [Niveles de integración Codex](https://developers.openai.com/blog/codex-as-a-platform)


## Revisión de alcance R1 — PENDIENTE_ACEPTACION

Necesidad descubierta por el usuario al validar: el perfil adaptado permanece, pero la evaluación y las respuestas desaparecen al salir. La versión inicial funciona según su confirmación general; no se declara aceptación individual de todos los escenarios ni cierre.

La ampliación implementada extiende la misma US-008 para consultar desde Perfiles evaluaciones históricas confirmadas y gestionar observaciones sin alterar cobertura, Maestro ni CV. La relación será un perfil con varias evaluaciones independientes, incluidas las confirmadas mediante `reuse`; `duplicate` y `create` vinculan la nueva evaluación al perfil nuevo. No se sobrescriben análisis anteriores ni se reconstruyen los ausentes.

Las exclusiones iniciales de persistencia de análisis, snapshots y migraciones dejan de aplicar para R1, únicamente para el resumen estructurado histórico y sus observaciones. Se permiten cambios de esquema aditivos mínimos; siguen excluidos oferta completa, prompts, versiones del Maestro/CV y sistema de postulaciones. La evaluación no duplica la fuente profesional y no se actualiza automáticamente al editarla. No se modifica la Constitución.

[US-008, revisión R1](../stories/US-008-adaptacion-cv-ofertas.md#revisión-r1--consulta-y-gestión-de-la-adaptación-pendiente_aceptacion) contiene el único contrato aprobado, modelo mínimo, sustituciones explícitas de criterios iniciales, criterios verificables y pasos técnicos. La aprobación se concedió el 2026-11-10; la implementación técnica está completada en la rama `008-adaptacion-cv-ofertas` y pendiente de aceptación. No autoriza integración ni cierre. Base documental `722d77f`, implementación inicial `d8d56f6`; se conserva su historial.
