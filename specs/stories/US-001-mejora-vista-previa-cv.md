# US-001 — Mejora de la vista previa del CV

**Estado:** APROBADA_PARA_DESARROLLO

## Historia de usuario

Como persona que prepara un CV, quiero elegir un perfil, previsualizarlo y acceder desde esa vista a sus fuentes de información, para corregir datos y exportar el PDF con claridad.

## Problema y objetivo

Actualmente, la selección y la acción para ver la vista previa aparecen antes del documento, la exportación queda separada de esos controles y los enlaces para corregir datos aparecen debajo del CV sin una relación visual suficientemente clara. Se busca hacer más intuitivo el flujo de selección, visualización, corrección y exportación, con una composición vertical compacta que conecte los controles, el documento y las fuentes de información.

## Criterios de aceptación

1. El selector de perfil y **Ver vista previa** se presentan juntos en un bloque responsive. Al consultar un perfil, la vista previa aparece inmediatamente debajo de ese bloque, con separación vertical compacta y sin espacio vacío innecesario. Se conserva el envío actual de la selección a `/cv` mediante GET.
2. **Exportar PDF** se presenta junto a los controles principales únicamente cuando la vista previa está lista, tiene datos y hay un perfil seleccionado. El enlace conserva el destino actual `/cv/pdf?profile={id}` y exporta el perfil seleccionado.
3. La vista previa conserva su formato actual de documento y queda visualmente conectada con la sección para editar información, ubicada inmediatamente después en la misma composición vertical. No se introduce una barra lateral ni se mezclan enlaces de edición dentro del contenido del CV.
4. La sección de correcciones usa el título **Editar información del CV** y la explicación **Modifica los datos de origen y vuelve a generar la vista previa.** Mantiene estos siete enlaces y sus destinos actuales, derivados de `preview.source_links`: **Perfil de CV** (`profile`), **Datos personales** (`personal_data`), **Experiencia** (`work_experiences`), **Educación** (`education_entries`), **Cursos y certificaciones** (`certifications`), **Habilidades** (`skills`) y **Proyectos** (`projects`).
5. En pantallas estrechas, los controles, las acciones y los enlaces se acomodan sin desbordamiento horizontal. Los elementos interactivos siguen siendo accesibles por teclado y muestran el foco visible.
6. Se conservan los estados y comportamientos actuales: sin perfiles activos, selección requerida, perfil no disponible, error y perfil sin datos. La exportación permanece oculta fuera de la condición del criterio 2; los enlaces para editar información siguen disponibles cuando existe una vista previa lista.

## Alcance

- Reorganizar visualmente los controles de perfil, vista previa y exportación en la pantalla Generador de CV.
- Reducir el espacio vertical innecesario entre los controles y el documento.
- Mejorar la continuidad visual entre la vista previa y la sección **Editar información del CV**.
- Conservar la composición vertical y el diseño responsive del sistema visual existente.

## Exclusiones

- Cambios en la generación, el contenido o la plantilla del CV y del PDF.
- Cambios en las rutas, destinos o lógica de los siete enlaces y de la exportación.
- Cambios en perfiles, datos de origen o sus formularios de edición.
- Rediseño de otras pantallas o del módulo completo; no se incorpora una barra lateral ni nuevas funcionalidades.

## Referencias técnicas

- `resources/js/components/CvGeneratorPanel.tsx` — controles, estados, exportación y enlaces de corrección.
- `resources/js/pages/Cv/Index.tsx` — página Generador de CV.
- `resources/js/components/CvPreview.tsx` — documento de vista previa.
- `app/Http/Controllers/CvGeneratorController.php` — selección del perfil y datos de vista previa.
- `resources/js/components/__tests__/CvGeneratorPanel.test.tsx` — comportamiento y enlaces existentes del panel.
