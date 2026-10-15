# Constitución del Proyecto — CV Automático

**Proyecto:** CV Automático  
**Versión de la Constitución:** 1.0.0  
**Estado:** Activa  
**Fecha de ratificación:** 2026-10-14  
**Metodología:** Spec-Driven Development con SpecKit  
**Ámbito inicial:** Aplicación web local, monousuario

---

## 1. Propósito de esta Constitución

Esta Constitución establece los principios obligatorios que deben gobernar el análisis, diseño, planificación, implementación, pruebas y evolución del proyecto CV Automático.

Toda especificación, plan técnico, tarea, implementación o cambio posterior debe respetar estas reglas.

Si una decisión de implementación entra en conflicto con esta Constitución, prevalece esta Constitución salvo que sea modificada explícitamente mediante una enmienda documentada.

---

# I. PRINCIPIOS FUNDAMENTALES

## Principio I — Fuente Única de Verdad

Toda la información profesional debe almacenarse una sola vez en el Perfil Profesional Maestro.

El sistema no debe mantener copias independientes de la misma experiencia, curso, certificación, habilidad, proyecto o dato personal para cada CV.

Los distintos currículums serán vistas derivadas de la información centralizada.

### Regla obligatoria

Un dato profesional se registra una vez y puede reutilizarse en múltiples perfiles y documentos.

### Consecuencia

Si se corrige una experiencia laboral, certificación o dato personal, todos los CV generados posteriormente deben utilizar automáticamente la información actualizada.

---

## Principio II — El CV es un Resultado, no una Fuente de Datos

El usuario no editará directamente el contenido final del CV como si fuera un documento Word.

El CV debe generarse a partir de:

1. Perfil Profesional Maestro.
2. Perfil profesional objetivo.
3. Reglas de selección y relevancia.
4. Plantilla visual.
5. Motor de renderizado.

### Flujo conceptual

Perfil Maestro  
→ Perfil objetivo  
→ Reglas  
→ Plantilla  
→ Vista previa  
→ PDF

Todo cambio de contenido debe realizarse en la fuente correspondiente, no en el PDF generado.

---

## Principio III — Separación entre Datos, Reglas y Presentación

La arquitectura debe mantener separadas las siguientes responsabilidades:

### Datos

Información profesional y documental.

### Reglas

Criterios que determinan qué información debe mostrarse, ocultarse, priorizarse u ordenarse.

### Presentación

Plantillas y estilos visuales.

### Renderizado

Transformación de los datos seleccionados en HTML y posteriormente PDF.

Ninguna plantilla debe contener información profesional fija que corresponda al Perfil Maestro.

---

## Principio IV — MVP Estrictamente Acotado

El proyecto debe priorizar un MVP funcional completo antes de ampliar capacidades.

No se implementarán funcionalidades futuras únicamente porque resulten técnicamente interesantes.

Toda nueva capacidad deberá responder a una necesidad concreta del MVP o estar aprobada como ampliación posterior.

### El MVP incluye

- Perfil profesional.
- Datos personales.
- Experiencia laboral.
- Educación.
- Cursos y certificaciones.
- Habilidades.
- Proyectos.
- Repositorio documental.
- Carga de certificados PDF.
- Extracción automática de información.
- Revisión humana antes de guardar información detectada.
- Perfil Analista de Sistemas.
- Perfil Programador / Desarrollador.
- Una plantilla profesional.
- Vista previa del CV.
- Generación de PDF.
- Generación de CV documentado.
- Selección de evidencias a anexar.

### Fuera del MVP

No forman parte del MVP inicial:

- Multiusuario.
- SaaS.
- Aplicación móvil.
- Autenticación compleja.
- Sincronización automática con LinkedIn.
- Scraping de empleos.
- Postulación automática.
- ATS scoring avanzado.
- Editor visual drag-and-drop.
- Marketplace de plantillas.
- Múltiples plantillas.
- Traducción automática.
- CV multidioma.
- Generación automática de cartas.
- Integración con bolsas de trabajo.
- Portal público.
- Analítica avanzada.

Cualquier funcionalidad de esta lista requiere una decisión explícita de ampliación de alcance.

---

# II. MODELO FUNCIONAL

## Principio V — Perfil Profesional Maestro

El sistema debe mantener un Perfil Profesional Maestro como núcleo funcional.

Debe poder contener como mínimo:

### Datos personales

- Nombre completo.
- Título profesional.
- Teléfono.
- Correo electrónico.
- Ubicación.
- LinkedIn.
- GitHub.
- Sitio web.
- Otros enlaces relevantes.

### Resumen profesional

El sistema podrá mantener contenido base reutilizable para construir el resumen de cada perfil.

### Experiencia laboral

Cada experiencia podrá contener:

- Empresa.
- Cargo.
- Fecha de inicio.
- Fecha de fin.
- Descripción.
- Responsabilidades.
- Logros.
- Tecnologías.
- Habilidades relacionadas.

### Educación

- Institución.
- Programa.
- Grado o nivel.
- Fechas.
- Descripción.
- Evidencia documental asociada.

### Cursos y certificaciones

- Nombre.
- Institución.
- Fecha.
- Duración.
- Código o identificador.
- URL de validación.
- Categoría.
- Habilidades relacionadas.
- Archivo PDF original.

### Proyectos

- Nombre.
- Descripción.
- Rol.
- Tecnologías.
- Resultados.
- URL.
- Fechas.

### Habilidades

Las habilidades podrán clasificarse, como mínimo, en:

- Técnicas.
- Funcionales.
- Metodológicas.
- Herramientas.
- Tecnologías.

---

## Principio VI — Perfiles Profesionales como Configuración

Los perfiles profesionales no duplicarán información.

Cada perfil determinará la relevancia de los elementos del Perfil Maestro.

El MVP debe incluir:

### Analista de Sistemas

Debe priorizar información relacionada con:

- Análisis.
- Procesos.
- Requerimientos.
- SQL.
- Arquitectura.
- Sistemas.
- Documentación.
- Integraciones.
- APIs.
- Gestión y proyectos.

### Programador / Desarrollador

Debe priorizar información relacionada con:

- Programación.
- PHP.
- Laravel.
- JavaScript.
- React.
- SQL.
- REST API.
- WordPress.
- Git.
- Desarrollo de proyectos técnicos.

Una misma experiencia, habilidad, curso o proyecto puede participar en varios perfiles con distinta relevancia.

---

# III. DOCUMENTOS Y EVIDENCIAS

## Principio VII — Conservación de Documentos Originales

Los PDFs originales subidos por el usuario deben conservarse sin alteración.

El sistema deberá almacenar una referencia al archivo, no reemplazarlo por una reconstrucción gráfica o una versión regenerada.

Los certificados y otras evidencias documentales son archivos fuente.

---

## Principio VIII — Extracción Primero, OCR Solo Cuando Sea Necesario

El sistema no debe aplicar OCR indiscriminadamente.

El flujo de procesamiento será:

1. Detectar si el PDF contiene texto extraíble.
2. Extraer texto directamente cuando sea posible.
3. Usar OCR únicamente cuando el documento sea una imagen o el texto nativo sea insuficiente.
4. Transformar la información extraída en datos estructurados.
5. Mostrar los datos al usuario para revisión.
6. Guardar únicamente después de confirmación.

La extracción automática nunca debe sustituir la validación humana.

---

## Principio IX — Confirmación Humana Obligatoria

Ningún dato extraído automáticamente de un certificado debe incorporarse definitivamente al Perfil Maestro sin una pantalla previa de revisión.

El usuario debe poder:

- Confirmar.
- Corregir.
- Cancelar.

La automatización debe reducir trabajo manual, no introducir información incorrecta silenciosamente.

---

## Principio X — CV Documentado por Concatenación de PDFs

La versión documentada no reconstruirá los certificados.

El proceso será:

1. Generar el PDF del CV.
2. Determinar las evidencias seleccionadas.
3. Ordenar los documentos.
4. Concatenar los PDFs originales al PDF del CV.
5. Generar un único archivo final.

### Ejemplo

CV generado  
+ certificado 1 original  
+ certificado 2 original  
+ certificado 3 original  
= CV documentado final

Los certificados anexados deben mantener su contenido y apariencia originales.

---

## Principio XI — Selección Explícita de Evidencias

El sistema puede sugerir o preseleccionar evidencias según el perfil profesional.

La decisión final de qué certificados o documentos anexar debe permanecer bajo control del usuario.

El usuario podrá incluir o excluir evidencias antes de generar el CV documentado.

---

# IV. ARQUITECTURA TÉCNICA

## Principio XII — Arquitectura Simple para Aplicación Local

El proyecto debe evitar infraestructura innecesaria.

La arquitectura base será:

### Backend

Laravel.

### Frontend

React.

### Base de datos

SQLite.

### Archivos

Almacenamiento local.

### Renderizado del CV

HTML/CSS.

### Exportación

HTML/CSS → PDF.

### CV documentado

Concatenación de PDFs.

### Procesamiento documental

Extracción de texto nativo primero; OCR como fallback.

### Inteligencia artificial

Podrá utilizarse para:

- Interpretación estructurada de certificados.
- Clasificación.
- Sugerencias de relevancia.
- Generación asistida de resúmenes.
- Capacidades futuras de adaptación de CV.

La IA no será la fuente de verdad de los datos.

---

## Principio XIII — Evitar Complejidad Prematura

No se introducirán sin necesidad:

- Microservicios.
- Colas distribuidas.
- Contenedores obligatorios.
- Infraestructura cloud.
- Brokers de eventos.
- Bases de datos remotas.
- Arquitecturas multi-tenant.
- Sistemas de permisos complejos.

Toda complejidad técnica debe justificar un requisito real.

---

## Principio XIV — Datos Relacionales, no Contenido Duplicado

Las relaciones entre entidades deben modelarse explícitamente.

Por ejemplo:

Una certificación puede relacionarse con múltiples habilidades.

Una habilidad puede relacionarse con múltiples certificaciones.

Un proyecto puede relacionarse con múltiples tecnologías.

Un elemento puede tener distinta relevancia según el perfil profesional.

Las relaciones deben preferirse frente a duplicar textos o registros.

---

# V. UI Y EXPERIENCIA DE USUARIO

## Principio XV — Administrador Orientado a Tareas

La interfaz administrativa debe priorizar las acciones reales del usuario.

La navegación principal podrá incluir:

- Dashboard.
- Perfil.
- Experiencia.
- Educación.
- Cursos y certificaciones.
- Habilidades.
- Proyectos.
- Documentos.
- Perfiles.
- Generador de CV.

La UI debe minimizar pasos innecesarios.

---

## Principio XVI — Vista Previa antes de Exportar

El usuario debe poder visualizar el CV antes de producir el PDF definitivo.

La vista previa debe reflejar con fidelidad suficiente:

- Contenido.
- Orden.
- Jerarquía.
- Perfil seleccionado.
- Plantilla.
- Distribución general.

La generación de PDF no debe ser un proceso ciego.

---

## Principio XVII — Impeccable como Herramienta de UI/UX

Impeccable podrá utilizarse para mejorar:

- Jerarquía visual.
- Layout.
- Componentes.
- Formularios.
- Tablas.
- Estados.
- Navegación.
- Diseño del CV.
- Consistencia visual.

Impeccable no definirá la arquitectura funcional ni modificará reglas de negocio sin una especificación correspondiente.

---

## Principio XVIII — Una Plantilla Excelente Antes que Muchas Mediocres

El MVP debe incluir una sola plantilla profesional de alta calidad.

No se implementarán múltiples plantillas hasta que:

1. El motor de datos funcione correctamente.
2. La selección por perfiles esté validada.
3. La generación de PDF sea estable.
4. El CV documentado funcione de extremo a extremo.

---

# VI. CALIDAD Y PRUEBAS

## Principio XIX — Cada Spec Debe Entregar una Capacidad Verificable

Una especificación no debe existir únicamente para producir infraestructura sin resultado funcional cuando pueda evitarse.

Cada Spec deberá definir:

- Problema.
- Objetivo.
- Alcance.
- Fuera de alcance.
- Requisitos funcionales.
- Criterios de aceptación.
- Casos límite.
- Pruebas requeridas.

Un Spec se considera terminado únicamente cuando su comportamiento puede verificarse.

---

## Principio XX — Las Tareas no Sustituyen a los Specs

Las tareas técnicas deben existir dentro del contexto de una especificación.

No se deben crear cadenas indefinidas de tareas independientes que amplíen el sistema sin control documental.

Regla:

> Un Spec implementa una capacidad completa y verificable. No se abren tareas funcionales independientes fuera del Spec salvo correcciones de bugs, mantenimiento o trabajo técnico explícitamente justificado.

---

## Principio XXI — Pruebas Proporcionales al Riesgo

Las funcionalidades críticas deben tener cobertura automatizada suficiente.

Se consideran críticas, como mínimo:

- Persistencia del Perfil Maestro.
- Relaciones entre perfiles y contenido.
- Importación de certificados.
- Confirmación antes de persistir datos extraídos.
- Generación del CV.
- Generación del PDF.
- Selección de anexos.
- Concatenación de PDFs.
- Conservación de documentos originales.

Los tests deberán verificar tanto casos exitosos como errores previsibles.

---

## Principio XXII — No Romper Datos Existentes

Las migraciones y cambios de esquema deben preservar la información previamente registrada.

Cuando una modificación estructural pueda producir pérdida de datos:

1. Debe documentarse.
2. Debe existir estrategia de migración.
3. Debe existir copia de seguridad o mecanismo de recuperación.

---

# VII. SEGURIDAD Y PRIVACIDAD

## Principio XXIII — Privacidad Local por Defecto

La información profesional y los documentos deben permanecer localmente salvo que una funcionalidad aprobada requiera explícitamente un servicio externo.

Si se utiliza un servicio externo de IA u OCR, el sistema debe identificar claramente qué información será enviada.

No debe enviarse automáticamente documentación completa cuando solo sea necesario transmitir texto extraído o información parcial.

---

## Principio XXIV — No Almacenar Secretos en Código Fuente

Claves API, tokens o credenciales nunca deben almacenarse directamente en el repositorio.

Deben utilizarse mecanismos de configuración apropiados como variables de entorno.

Los archivos con secretos deben excluirse del control de versiones.

---

# VIII. GOBERNANZA DE ALCANCE

## Principio XXV — No Expandir el MVP Durante la Implementación

El descubrimiento de una posible mejora no implica que deba implementarse inmediatamente.

Cuando aparezca una idea nueva durante un Spec:

- Si es necesaria para cumplir el criterio de aceptación, puede incorporarse.
- Si no es necesaria, debe registrarse como backlog o futura especificación.

El objetivo es evitar que un Spec aumente indefinidamente de tamaño.

---

## Principio XXVI — Cambios Importantes Requieren Decisión Explícita

Las siguientes decisiones no podrán introducirse incidentalmente durante la implementación:

- Cambiar el stack principal.
- Sustituir SQLite.
- Introducir autenticación.
- Convertir el sistema en multiusuario.
- Migrar a cloud.
- Agregar integración automática con LinkedIn.
- Cambiar el modelo central de Perfil Maestro.
- Permitir edición directa del CV.
- Agregar múltiples plantillas.
- Modificar el mecanismo del CV documentado.

Estas decisiones requieren actualización del Product Brief, de la Constitución o una especificación arquitectónica explícita.

---

# IX. CRITERIO DE ÉXITO DEL MVP

El MVP será considerado funcional cuando pueda completarse de extremo a extremo el siguiente escenario:

1. El usuario completa un nuevo curso.
2. Obtiene el certificado en PDF.
3. Abre CV Automático.
4. Carga el archivo.
5. El sistema extrae el texto nativamente o mediante OCR.
6. El sistema identifica los datos relevantes.
7. El usuario revisa la información.
8. El usuario confirma el registro.
9. La certificación queda incorporada al Perfil Profesional Maestro.
10. El usuario selecciona el perfil Programador o Analista de Sistemas.
11. El sistema incluye o prioriza la información correspondiente.
12. El usuario visualiza el CV.
13. El usuario genera un PDF normal.
14. El usuario genera una versión documentada.
15. El sistema concatena el PDF del CV con los certificados originales seleccionados.
16. El archivo final puede abrirse correctamente y mantiene intactas las evidencias anexadas.

Si este flujo funciona de forma estable, el MVP cumple su propósito principal.

---

# X. PRINCIPIO RECTOR DEL PRODUCTO

> Una sola trayectoria profesional. Múltiples CV. Cero duplicación de información.

Toda decisión del proyecto debe evaluarse contra esta premisa.

---

# XI. DISCIPLINA SPECKIT

El desarrollo seguirá el flujo definido por SpecKit.

Cada capacidad significativa deberá recorrer, cuando corresponda:

1. Constitution.
2. Specify.
3. Clarify.
4. Plan.
5. Tasks.
6. Implement.
7. Test / Validate.

No se implementará una funcionalidad de alcance significativo únicamente a partir de instrucciones informales si todavía existen decisiones funcionales relevantes no documentadas.

La Constitution define las reglas permanentes.

El Product Brief define la visión y alcance global.

Cada Spec define una capacidad concreta.

El Plan determina cómo implementarla.

Tasks descompone el trabajo técnico.

---

# XII. REGLAS PARA AGENTES DE IA

Los agentes que trabajen sobre este repositorio deben:

1. Leer esta Constitución antes de realizar cambios significativos.
2. Respetar el Spec activo.
3. No ampliar el alcance sin justificación.
4. No modificar reglas de negocio fuera del Spec.
5. Preferir soluciones simples.
6. Mantener separación entre datos, reglas y presentación.
7. Preservar compatibilidad con los datos existentes.
8. Ejecutar o agregar pruebas relevantes.
9. Informar cualquier decisión humana necesaria antes de asumirla.
10. Continuar autónomamente cuando no exista una decisión humana pendiente.
11. No introducir dependencias nuevas sin necesidad técnica demostrable.
12. No sustituir documentos originales por reconstrucciones.
13. No almacenar automáticamente información extraída sin confirmación del usuario.
14. No permitir que el CV final se convierta en una fuente de datos editable independiente.

---

# XIII. GOBERNANZA Y ENMIENDAS

Esta Constitución puede modificarse únicamente cuando exista una razón explícita.

Toda enmienda debe indicar:

- Regla modificada.
- Motivo.
- Impacto.
- Compatibilidad con Specs existentes.
- Migraciones requeridas, si corresponden.
- Nueva versión de la Constitución.

### Versionado

Se utilizará versionado semántico:

- **MAJOR:** cambios incompatibles en principios fundamentales.
- **MINOR:** incorporación de nuevos principios o ampliaciones importantes.
- **PATCH:** aclaraciones que no modifican el comportamiento esperado.

No se cambiarán silenciosamente principios fundamentales durante un Spec en ejecución.

---

# XIV. RATIFICACIÓN

Esta versión 1.0.0 establece la base inicial del proyecto CV Automático.

A partir de su ratificación, toda especificación deberá comprobar explícitamente su compatibilidad con esta Constitución antes de pasar a implementación.

**Estado:** RATIFICADA  
**Versión:** 1.0.0  
**Fecha:** 2026-10-14
