# Roadmap — CV Automático

**Proyecto:** CV Automático  
**Estado:** Activo  
**Documento:** Roadmap funcional de Specs posteriores al SPEC-001  
**Metodología:** Spec-Driven Development con SpecKit

---

## Propósito

Este documento registra los Specs propuestos pendientes del proyecto CV Automático, su objetivo, alcance preliminar, exclusiones y dependencias.

Este roadmap no sustituye a los archivos formales `spec.md` que serán creados posteriormente mediante `/speckit.specify`.

La fuente normativa global del proyecto sigue siendo:

`.specify/memory/constitution.md`

Cada nuevo Spec deberá:

1. Leer y respetar la Constitución activa.
2. Revisar los Specs anteriores relacionados.
3. Mantener compatibilidad con el modelo ya implementado.
4. Evitar duplicar capacidades existentes.
5. No ampliar alcance sin una decisión explícita.

---

# SPEC-002 — Documentos y Evidencias

**Estado:** Cerrado formalmente (2026-10-21) — implementación terminada; validación manual aprobada (2026-10-15); pruebas automatizadas aprobadas (2026-10-21); integrado en main mediante `1f154bc`; sin tag (no exigido por la Constitución ni el roadmap). El alcance definitivo está en `specs/002-documentos-evidencias/spec.md`.

**Referencia de cierre:** commit de integración `1f154bc` en `main`; no existe tag de versión.

## Objetivo

Crear el módulo de Documentos y Evidencias del proyecto CV Automático para permitir almacenar, asociar y consultar archivos PDF originales vinculados al Perfil Profesional Maestro.

## Alcance preliminar

- Subir archivos PDF desde la interfaz.
- Conservar el archivo original sin reconstruirlo.
- Registrar metadatos básicos del documento.
- Asociación a cursos/certificaciones, educación y experiencia laboral; proyectos excluido.
- Visualización desde la aplicación.
- Mostrar qué registro utiliza cada evidencia.
- Eliminación con confirmación; sustituir un archivo exige eliminar el documento y cargar uno nuevo, sin versionado ni descarga.
- Mantener integridad entre el archivo físico y su registro en base de datos.
- Preparar la base documental necesaria para el futuro CV documentado.

## Fuera de alcance

- OCR.
- Extracción automática de datos.
- Clasificación inteligente.
- Generación de CV.
- Exportación del CV a PDF.
- Concatenación de PDFs.
- Análisis de contenido mediante IA.

## Dependencias

- SPEC-001 — Perfil Profesional Maestro.

## Resultado esperado

El usuario puede subir un certificado PDF, asociarlo a una certificación existente, visualizarlo y conservar el archivo original dentro del sistema.

---

# SPEC-003 — Perfiles de CV

**Estado:** Cerrado formalmente (2026-10-22; roadmap actualizado 2026-10-23) — implementación terminada; aceptación manual del responsable registrada; T050 con cobertura manual parcial documentada en `quickstart.md` (subescenarios no confirmados no aprobados); T048 de formato documental corregido; rama `003-perfiles-cv`; `main` apunta a `4801c72`; sin push. El alcance definitivo está en `specs/003-perfiles-cv/spec.md`.

## Objetivo

Permitir crear y gestionar perfiles de CV derivados del Perfil Profesional Maestro sin duplicar la información profesional.

## Alcance preliminar

- Crear perfiles de CV.
- Editar perfiles existentes.
- Activar o desactivar perfiles.
- Incluir inicialmente:
    - Analista de Sistemas.
    - Programador / Desarrollador.
- Definir título profesional específico por perfil.
- Definir resumen profesional específico por perfil.
- Relacionar habilidades con cada perfil.
- Relacionar experiencias con cada perfil.
- Relacionar educación con cada perfil.
- Relacionar cursos y certificaciones con cada perfil.
- Relacionar proyectos con cada perfil.
- Definir relevancia o prioridad de cada elemento dentro de un perfil.
- Permitir reutilizar un mismo elemento del Perfil Maestro en múltiples perfiles.
- Visualizar qué contenido pertenece o tiene relevancia en cada perfil.
- Mantener una única fuente de verdad para todos los datos profesionales.

## Fuera de alcance

- Generación visual del CV.
- Plantillas.
- Exportación PDF.
- CV documentado.
- Análisis automático de ofertas laborales.
- Relevancia calculada automáticamente por IA.

## Dependencias

- SPEC-001 — Perfil Profesional Maestro.
- Puede consumir información documental creada en SPEC-002, pero no depende funcionalmente de ella para existir.

## Resultado esperado

El usuario puede mantener al menos dos perfiles distintos —Analista y Programador— reutilizando la misma trayectoria profesional y definiendo qué contenido debe priorizar cada uno.

---

# SPEC-004 — Generador de CV

**Estado:** Implementación y aceptación funcional/visual del MVP completadas; integración en main pendiente. Commit local f86d17a; merge --no-ff autorizado pero bloqueado por el harness; main permanece en 63ab15b; sin push.

## Objetivo

Construir el motor que transforma el Perfil Profesional Maestro y un Perfil de CV seleccionado en una vista de currículum lista para revisar.

## Alcance preliminar

- Seleccionar un perfil de CV.
- Generar contenido a partir del Perfil Profesional Maestro.
- Aplicar prioridades y relaciones definidas en el perfil.
- Mostrar:
    - Datos personales.
    - Título profesional.
    - Resumen.
    - Experiencia relevante.
    - Educación relevante.
    - Certificaciones relevantes.
    - Habilidades.
    - Proyectos relevantes.
- Ordenar contenido de acuerdo con reglas definidas.
- Omitir contenido no seleccionado o no relevante para el perfil.
- Generar una vista previa del CV.
- Permitir cambiar de perfil y regenerar la vista.
- Mantener una sola plantilla profesional para el MVP.
- Garantizar que la vista sea derivada de datos maestros y no editable como documento independiente.

## Fuera de alcance

- Exportación a PDF.
- Concatenación de certificados.
- Múltiples plantillas.
- Editor drag-and-drop.
- Edición directa del CV generado.
- ATS scoring.
- Adaptación automática a una oferta laboral.
- Generación mediante IA de contenido no aprobado por el usuario.

## Dependencias

- SPEC-001 — Perfil Profesional Maestro.
- SPEC-003 — Perfiles de CV.

## Resultado esperado

El usuario selecciona Analista o Programador y obtiene una vista previa coherente del CV, con contenido distinto según el perfil elegido.

---

# SPEC-005 — Exportación PDF

## Objetivo

Permitir exportar el CV generado a un archivo PDF profesional y reproducible.

## Alcance preliminar

- Agregar acción visible de Exportar PDF.
- Convertir la vista generada del CV a PDF.
- Mantener la misma información y jerarquía visual de la vista previa.
- Respetar márgenes, tipografía, saltos de página y orden.
- Generar un nombre de archivo consistente.
- Permitir descargar el PDF.
- Soportar CV de una o más páginas.
- Mantener enlaces funcionales cuando técnicamente corresponda.
- Validar que el PDF pueda abrirse correctamente.

## Fuera de alcance

- Anexar certificados.
- Concatenar PDFs.
- OCR.
- Múltiples diseños de exportación.
- Firma digital.
- Publicación online.
- Envío por correo.

## Dependencias

- SPEC-004 — Generador de CV.

## Resultado esperado

El usuario puede generar y descargar un PDF normal del CV correspondiente al perfil seleccionado.

---

# SPEC-006 — CV Documentado

## Objetivo

Generar una versión documentada del currículum que contenga primero el CV y, a continuación, los PDFs originales de las evidencias seleccionadas.

## Alcance preliminar

- Partir del PDF normal del CV.
- Consultar las evidencias disponibles.
- Permitir seleccionar qué documentos anexar.
- Preseleccionar evidencias relacionadas con el perfil cuando sea posible mediante reglas determinísticas.
- Mostrar claramente los anexos seleccionados antes de generar.
- Definir un orden estable de anexos.
- Concatenar:
    - PDF del CV.
    - PDFs originales seleccionados.
- Mantener intactos los PDFs originales.
- Generar un único archivo final.
- Permitir descargar el CV documentado.
- Validar que el archivo final pueda abrirse correctamente.
- Mantener trazabilidad entre anexo y registro profesional asociado.

## Orden preliminar de anexos

1. Educación formal.
2. Diplomados.
3. Certificaciones.
4. Cursos.
5. Otras evidencias.

Dentro de cada grupo, inicialmente:

más reciente → más antiguo.

## Fuera de alcance

- Reconstruir certificados.
- OCR.
- Extraer datos del PDF.
- Editar el contenido interno de los certificados.
- Reordenamiento drag-and-drop.
- Firma digital.
- Portada adicional de expediente, salvo que posteriormente se apruebe.

## Dependencias

- SPEC-002 — Documentos y Evidencias.
- SPEC-005 — Exportación PDF.

## Resultado esperado

El usuario obtiene un único PDF compuesto por el CV seguido de los certificados y demás evidencias originales seleccionadas.

---

# SPEC-007 — Importación Inteligente de Certificados

## Objetivo

Automatizar el registro de cursos y certificaciones a partir de PDFs cargados por el usuario, reduciendo la entrada manual de información.

## Alcance preliminar

- Subir un certificado PDF.
- Detectar si el PDF contiene texto nativo.
- Extraer texto directamente cuando sea posible.
- Usar OCR únicamente cuando el documento sea una imagen o el texto nativo sea insuficiente.
- Interpretar la información extraída.
- Detectar, cuando sea posible:
    - Nombre del curso.
    - Institución.
    - Fecha.
    - Duración.
    - Código de certificado.
    - URL de validación.
    - Nombre del participante.
    - Categoría sugerida.
- Proponer habilidades o tecnologías relacionadas cuando sea viable.
- Presentar una pantalla de revisión antes de guardar.
- Permitir:
    - Confirmar.
    - Corregir.
    - Cancelar.
- Registrar la certificación solo después de confirmación humana.
- Asociar automáticamente el PDF original como evidencia.
- Evitar duplicados cuando sea posible.
- Registrar errores de procesamiento sin perder el archivo original.

## Flujo esperado

PDF  
→ detección de texto nativo  
→ extracción directa u OCR  
→ interpretación estructurada  
→ revisión humana  
→ confirmación  
→ registro de certificación  
→ asociación del documento original

## Fuera de alcance

- Guardado silencioso sin confirmación.
- Edición del PDF original.
- Clasificación definitiva de relevancia por perfil mediante IA.
- Generación automática completa de CV.
- Postulación automática.
- Procesamiento masivo de carpetas, salvo que se apruebe posteriormente.
- Entrenamiento de modelos propios.

## Dependencias

- SPEC-001 — Perfil Profesional Maestro.
- SPEC-002 — Documentos y Evidencias.

## Resultado esperado

El usuario sube un certificado PDF, revisa los datos detectados y confirma el registro sin tener que escribir manualmente toda la información.

---

# Orden Propuesto de Implementación

1. SPEC-002 — Documentos y Evidencias.
2. SPEC-003 — Perfiles de CV.
3. SPEC-004 — Generador de CV.
4. SPEC-005 — Exportación PDF.
5. SPEC-006 — CV Documentado.
6. SPEC-007 — Importación Inteligente de Certificados.

---

# Dependencias entre Specs

SPEC-001 — Perfil Profesional Maestro  
├── SPEC-002 — Documentos y Evidencias  
├── SPEC-003 — Perfiles de CV  
│ └── SPEC-004 — Generador de CV  
│ └── SPEC-005 — Exportación PDF  
│ └── SPEC-006 — CV Documentado  
└── SPEC-007 — Importación Inteligente  
└── depende también de SPEC-002

---

# Regla de Ejecución

Los Specs deberán implementarse uno a uno.

Flujo recomendado por cada Spec:

`/speckit.specify`

→ `/speckit.clarify`

→ `/speckit.plan`

→ `/speckit.tasks`

→ `/speckit.analyze` cuando aporte valor

→ `/speckit.implement`

→ validación

→ commit

→ merge a `main`

No debe iniciarse la implementación del siguiente Spec hasta que el anterior se encuentre suficientemente estable y versionado.

---

# Estado del Roadmap

| Spec     | Capacidad                               | Estado                                                                                                                                                                                                          |
| -------- | --------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| SPEC-001 | Perfil Profesional Maestro              | Cerrado formalmente (2026-10-22) — validación manual del responsable aprobada; T063/T064 completadas; SC-001 retirado por decisión del responsable; pendiente de integración en main (commits 63bb79c, a04954d) |
| SPEC-002 | Documentos y Evidencias                 | Cerrado formalmente (2026-10-21) — validación manual 2026-10-15; pruebas 2026-10-21; main 1f154bc; sin tag                                                                                                      |
| SPEC-003 | Perfiles de CV | Cerrado formalmente (2026-10-22; actualizado 2026-10-23) — implementación terminada; aceptación manual del responsable; T050 parcial documentado; T048 PASS; rama 003-perfiles-cv; main 4801c72; sin push |
| SPEC-004 | Generador de CV | Implementación y aceptación funcional/visual completadas; integración en main pendiente; commit local f86d17a; merge bloqueado por el harness; sin push |
| SPEC-005 | Exportación PDF | Cerrado formalmente (2026-10-26) — aceptación funcional/visual del MVP por el responsable; T019 cerrada por aceptación humana con cobertura manual parcial documentada; 5 legacy eliminados; integrado en main mediante merge --no-ff; sin push |
| SPEC-006 | CV Documentado                          | Pendiente                                                                                                                                                                                                       |
| SPEC-007 | Importación Inteligente de Certificados | Pendiente                                                                                                                                                                                                       |

---

# Nota de Gobernanza

Este roadmap es deliberadamente preliminar.

Cuando un Spec pase a ejecución, su alcance definitivo será el contenido de su `spec.md` formal.

Si existe una discrepancia entre este roadmap, un Spec formal y la Constitución:

1. Prevalece la Constitución.
2. Luego prevalece el Spec formal activo.
3. El roadmap deberá actualizarse posteriormente para reflejar la decisión aprobada.
