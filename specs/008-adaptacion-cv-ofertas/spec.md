# Feature Specification: Adaptación de CV a una oferta laboral

**Feature Branch**: `008-adaptacion-cv-ofertas`  
**Created**: 2026-11-06  
**Status**: APROBADA — aprobada por el responsable el 2026-11-06  
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

La aplicación ya es local y usa React/Inertia y Laravel. Codex se invocaría desde un proceso Node de vida corta; no hay motivo demostrado para mantener un servicio Node residente. Node.js 22 y Codex CLI 0.166.0 están instalados y `codex login status` indica inicio de sesión con ChatGPT. `@openai/codex-sdk` no está instalado. El SDK envuelve el CLI y permite una respuesta estructurada; el proceso local no implica inferencia local: la oferta y los campos profesionales expresamente autorizados se envían a Codex/OpenAI. El plan y la cuota efectivos, así como el acceso de esta cuenta al modelo solicitado, no se han verificado.

Los campos exactos, exclusiones de datos, consentimiento previo y controles para contenido no confiable se definen una sola vez en US-008. La persistencia local de sesiones de Codex no bloquea esta propuesta ni es criterio de aceptación. La aplicación no mantendrá historial de ofertas, prompts o propuestas.

## Referencias

- [Constitución del proyecto](../../.specify/memory/constitution.md) · [Metodología FALEXOM](../../.specify/FALEXOM.md) · [Roadmap](../../docs/roadmap.md)
- Código existente revisado: `routes/web.php`, `app/Http/Controllers/CvProfileController.php`, `app/Http/Requests/Concerns/ValidatesCvProfile.php`, `app/Support/CvProfileContent.php`, `app/Support/CvPreviewContent.php`, `app/Http/Controllers/CvPdfController.php`.
- [Codex TypeScript SDK](https://developers.openai.com/codex/sdk) · [README del SDK](https://github.com/openai/codex/blob/main/sdk/typescript/README.md) · [Opciones de hilo](https://github.com/openai/codex/blob/main/sdk/typescript/src/threadOptions.ts) · [GPT-6 Luna](https://developers.openai.com/api/docs/models/gpt-6-luna)
- [Uso de Codex con un plan ChatGPT](https://help.openai.com/en/articles/11369540-using-codex-with-your-chatgpt-plan) · [Facturación ChatGPT y API](https://help.openai.com/en/articles/9039756-managing-billing-for-chatgpt-and-the-api-platform) · [Niveles de integración Codex](https://developers.openai.com/blog/codex-as-a-platform)
