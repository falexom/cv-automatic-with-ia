# US-004 — Fotografía profesional en el CV

**Estado:** PENDIENTE_ACEPTACION

**Aprobación humana:** Confirmada por el responsable en esta conversación; decisiones definitivas ratificadas el 2026-10-30.

**Implementación:** Completada; pendiente de aceptación funcional y visual.

## Historia de usuario

Como usuario de CV Automático, quiero cargar y administrar una fotografía profesional desde mi Perfil Maestro, para que todos los CV generados la incluyan automáticamente mientras esté guardada y se generen normalmente sin ella cuando no tenga una.

## Problema y objetivo

El Perfil Maestro contiene los datos personales usados por todos los perfiles de CV, pero actualmente no permite administrar una fotografía. Se necesita incorporar una sola imagen a esa fuente de datos y reutilizarla en la vista previa, la exportación PDF normal y el CV documentado, sin crear una fuente de datos, plantilla o historial de variantes adicional.

## Alcance

- Administrar una fotografía desde el Perfil Maestro: cargar, visualizar, reemplazar y eliminar.
- Aceptar JPG, JPEG y PNG, con un máximo de 5 MB por archivo; permitir ajustar y conservar un encuadre cuadrado 1:1.
- Incluir automáticamente la fotografía guardada en la vista previa, el PDF normal y el CV documentado. No ofrecer una opción para ocultarla mientras siga registrada.
- Si no hay fotografía, permitir generar el CV con el diseño actual y sin un espacio vacío reservado.
- Presentar la fotografía en un marco de 30 × 30 mm, arriba a la izquierda, sin superponer los datos personales.
- Usar almacenamiento local privado, validar el contenido real de la imagen y evitar exponer rutas internas.
- Mantener la misma fotografía y encuadre entre la vista previa y el PDF; conservar intactos los anexos PDF originales del CV documentado.

## Criterios de aceptación

1. **Carga:** Dado un archivo JPG, JPEG o PNG válido dentro del límite de 5 MB, cuando el usuario lo carga desde el Perfil Maestro, entonces el sistema confirma que quedó almacenado y disponible para visualizarse.
2. **Recorte:** Dada una fotografía cargada, cuando el usuario ajusta su encuadre, entonces puede seleccionar un recorte cuadrado 1:1 y el encuadre elegido queda conservado junto con la fotografía.
3. **Gestión:** Dada una fotografía registrada, cuando el usuario abre el Perfil Maestro, entonces puede visualizarla, reemplazarla o eliminarla.
4. **Persistencia:** Dada una fotografía y un encuadre guardados, cuando se cierra y vuelve a abrir la aplicación, entonces ambos continúan disponibles.
5. **Inclusión automática:** Dada una fotografía guardada, cuando se genera la vista previa, el PDF normal o el CV documentado, entonces la fotografía se incluye automáticamente. Mientras esté registrada no se ofrece un control para ocultarla.
6. **Ausencia de fotografía:** Dado un Perfil Maestro sin fotografía, cuando se genera o exporta un CV, entonces el flujo continúa normalmente, conserva el diseño actual y no deja un espacio vacío para la imagen.
7. **Ubicación y tamaño:** Dada una fotografía incluida, cuando se muestra el CV, entonces aparece arriba a la izquierda en un marco de 30 × 30 mm y no se superpone a los datos personales.
8. **Fidelidad del PDF normal:** Dada una vista previa con fotografía, cuando se exporta el PDF normal, entonces reproduce la misma fotografía y encuadre con fidelidad visual respecto de la vista previa.
9. **Estado vigente:** Dada una fotografía reemplazada o eliminada, cuando se genera un CV posteriormente, entonces se utiliza la fotografía vigente del Perfil Maestro o se omite si fue eliminada; no se conserva una selección de variante ni historial persistente.
10. **CV documentado:** Dada una fotografía guardada y evidencias seleccionadas, cuando se genera el CV documentado, entonces el CV inicial refleja la fotografía vigente y los anexos originales se mantienen intactos y en su orden actual.
11. **Validación y almacenamiento:** Dado un archivo con formato no permitido, contenido que no sea realmente una imagen compatible o tamaño superior a 5 MB, cuando se intenta cargar, entonces se rechaza sin almacenarlo. La fotografía válida se conserva en almacenamiento local privado y ninguna respuesta expone rutas internas.
12. **Regresión:** Dadas las funcionalidades existentes de generación, vista previa, exportación PDF y CV documentado, cuando se usan con o sin fotografía, entonces continúan funcionando sin alterar sus estados, contenido profesional ni anexos originales.

## Decisiones aprobadas

- La fotografía es única por Perfil Maestro y se administra exclusivamente desde esa sección; reemplazarla sustituye la vigente y eliminarla deja el perfil sin fotografía.
- Formatos admitidos: JPG, JPEG y PNG. Tamaño máximo: 5 MB.
- El usuario ajusta un encuadre 1:1; el encuadre elegido persiste con la fotografía.
- Si hay fotografía guardada, se incluye siempre y automáticamente en la vista previa, el PDF normal y el CV documentado. No hay selector para ocultarla ni preferencia de variante.
- Sin fotografía, se conserva la generación actual sin bloquearla ni reservar espacio vacío.
- Presentación de 30 × 30 mm en la esquina superior izquierda del CV.
- Almacenamiento local privado. La generación no conserva PDFs, variantes ni historial persistente.
- El CV documentado reutiliza la generación normal del CV y no modifica los anexos PDF originales.
- No se agregan plantillas ni fuentes duplicadas de datos profesionales.

## Exclusiones

- Eliminación automática de fondo, retoque o generación de imágenes con IA.
- Varias fotografías por perfil o editor avanzado de imágenes.
- Selector para ocultar una fotografía que siga guardada.
- Nuevas plantillas, almacenamiento en la nube o historial persistente de exportaciones y variantes.
- Modificaciones, reconstrucción o sustitución de certificados y evidencias originales.

## Descomposición técnica para Build

1. **Persistencia y seguridad:** Añadir a `Profile` la referencia a una sola fotografía y los datos mínimos del encuadre; guardar el archivo en el disco local privado existente. Validar formato, contenido real y tamaño en servidor; reemplazar y eliminar limpiando el archivo anterior, sin serializar su ruta interna.
2. **Interfaz de gestión y recorte:** Incorporar carga, vista, reemplazo y eliminación en el Perfil Maestro. Proveer un marco de recorte 1:1 ajustable y persistir su selección. Reutilizar los controles de confirmación existentes cuando corresponda.
3. **Previsualización:** Extender el payload derivado del CV para incluir la fotografía y el encuadre cuando existan. Actualizar el componente compartido para ubicar la imagen a 30 × 30 mm; mantener sin cambios el encabezado cuando no exista imagen y no añadir un control de variante.
4. **PDF normal y documentado:** Reutilizar la misma composición y el renderizador vigentes para que el PDF normal coincida con la vista previa. Confirmar que el CV documentado genera esa misma variante antes de anexar las evidencias, sin modificar sus archivos originales.
5. **Verificaciones focalizadas:** Cubrir carga válida e inválida, límite de tamaño, contenido real, persistencia del archivo y encuadre, reemplazo/eliminación, ausencia de rutas internas, generación con y sin foto, paridad del PDF y conservación de anexos. La aceptación visual final corresponde al responsable.

No se crea `tasks.md`: esta descomposición proporcional queda dentro de la historia.

## Dependencias y referencias

- SPEC-001 — Perfil Profesional Maestro: fuente única y persistencia de datos personales.
- SPEC-004 — Generador de CV: composición derivada y vista previa compartida.
- SPEC-005 — Exportación PDF: renderizado y fidelidad de la exportación.
- SPEC-006 — CV Documentado, registrado en `docs/roadmap.md` y ejecutado mediante US-002/US-003: regresión del CV inicial y conservación de anexos.
- US-001 — Mejora de la vista previa del CV; US-002 — Selección de evidencias; US-003 — Generación y descarga del CV documentado: superficies de regresión relacionadas.
