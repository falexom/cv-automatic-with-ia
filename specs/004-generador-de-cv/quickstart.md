# Quickstart — Validación del Generador de CV

**Feature**: `004-generador-de-cv` | **Fecha**: 2026-10-23
**Referencias**: [plan.md](./plan.md) · [data-model.md](./data-model.md) · [contracts/http-contracts.md](./contracts/http-contracts.md) · [spec.md](./spec.md)

Guía para ejecutar las pruebas y validar la capacidad de extremo a extremo una vez implementada. No contiene código de implementación. Esta fase de Plan **no ejecutó** los comandos ni los escenarios: el registro queda pendiente para Implement/QA.

## Requisitos previos

- PHP 8.3+ con extensión `pdo_sqlite`.
- Composer 2.x.
- Node.js 22+ y npm.
- Navegador de escritorio.
- Base de datos local con el esquema de SPEC-001, SPEC-002 y SPEC-003 ya migrado.
- Al menos un Perfil Maestro y, para los escenarios de selección, dos perfiles de CV activos (por ejemplo, los presets «Analista» y «Programador»).

## Base de datos: no hay migración nueva

El Generador de CV **no agrega tablas, columnas, migraciones, seeders ni factories**. La vista es derivada y efímera; no requiere cambios de esquema.

```bash
# Solo si hay migraciones pendientes de especificaciones anteriores
php artisan migrate
php artisan migrate:status
```

- Verificar que el esquema existente de SPEC-001/SPEC-002/SPEC-003 esté aplicado.
- **No ejecutar** `php artisan migrate:fresh`, `php artisan migrate:refresh` ni `php artisan db:wipe`: destruyen el Perfil Maestro, los perfiles de CV y los documentos existentes.
- No hay rollback específico de SPEC-004 porque no hay migración de SPEC-004.

## Ejecutar pruebas

```bash
# Prueba Feature del contrato del generador
php artisan test tests/Feature/CvGeneratorTest.php

# Suite completa de backend (Pest; SQLite en memoria)
php artisan test

# Plantilla del CV
npm test -- resources/js/components/__tests__/CvPreview.test.tsx

# Selector y estados vacíos/error
npm test -- resources/js/components/__tests__/CvGeneratorPanel.test.tsx

# Suite completa de frontend (Vitest + React Testing Library)
npm test

# Verificación de tipos
npm run types:check

# Formato/lint del proyecto
npm run check
composer lint:check
```

Si se quiere ejecutar el chequeo integral definido por Composer:

```bash
composer test
```

- `composer test` ejecuta lint, análisis estático y `php artisan test`.
- Las pruebas Feature usan SQLite en memoria y `RefreshDatabase`; no tocan la base local.
- Las pruebas Vitest no requieren servidor ni navegador real (`jsdom`).

## Flujo manual de validación

Cada escenario deriva de los criterios de aceptación del spec y debe pasar antes de dar la capacidad por terminada.

### 1. Entorno y navegación (FR-001, FR-021)

1. Iniciar la aplicación con `composer dev` (o el comando local equivalente).
2. Abrir la aplicación y verificar que «Generador de CV» aparece en la navegación principal, después de «Perfiles».
3. Abrir el ítem y comprobar que la URL es `/cv` y que el ítem queda activo en la navegación.
4. Verificar que la página tiene un `h1` «Generador de CV».

### 2. Sin perfiles activos (FR-017, US4)

1. Ir a **Perfiles** y desactivar todos los perfiles.
2. Abrir `/cv`.
3. Verificar que **no** se muestra ninguna hoja de CV.
4. Comprobar el estado vacío orientativo y el enlace a la gestión de perfiles.
5. Reactivar al menos dos perfiles y continuar.

### 3. Sin selección (FR-003, US4)

1. Abrir `/cv` sin query.
2. Verificar que el selector muestra **solo perfiles activos**, ordenados por nombre, con el título propio cuando existe.
3. Comprobar que **no** hay una opción preseleccionada: se muestra «Seleccioná un perfil».
4. Verificar que se solicita la selección y que **no** se muestra una hoja de CV.
5. Enviar el formulario sin elegir: no debe generarse una vista.

### 4. Generar la vista de un perfil activo (FR-006, FR-007, FR-011, FR-012, US1)

1. Seleccionar el perfil «Analista» y pulsar «Ver vista previa».
2. Verificar que la URL incluye `?profile=ID` y que el perfil seleccionado sigue identificable.
3. Comprobar la cabecera: nombre completo y datos de contacto del Maestro, título y resumen propios del perfil.
4. Verificar que los enlaces de contacto y adicionales son clicables y tienen texto identificable.
5. Comprobar que aparecen las secciones con contenido: experiencia, educación, cursos y certificaciones, habilidades y proyectos.
6. Verificar que cada elemento muestra los campos de FR-011:
    - experiencia: cargo, empresa, fechas o «actual», descripción, responsabilidades y logros;
    - educación: institución, programa, grado/nivel, fechas o «en curso», descripción;
    - certificaciones: nombre, institución, fecha, duración, categoría, código y validación;
    - habilidades: nombre y categoría;
    - proyectos: nombre, rol, fechas, descripción, resultados y enlace.
7. Comprobar que los campos opcionales vacíos no dejan etiquetas, separadores, guiones, «N/D» ni espacios reservados.
8. Verificar que no se muestra la relevancia Alta/Media/Baja en la hoja.
9. Verificar que no existe ningún control de edición, reordenamiento, guardado, plantilla, exportación, impresión, descarga ni PDF dentro de la hoja.
10. Comprobar que la hoja usa una sola columna y una única plantilla, sin variantes por perfil.

### 5. Cambio de perfil y regeneración (FR-004, US2)

1. Con dos perfiles activos que tengan títulos, resúmenes, selecciones o relevancias distintas, cambiar de «Analista» a «Programador».
2. Verificar que la vista se reconstruye por completo con el título, el resumen, el contenido y el orden del segundo perfil.
3. Comprobar que no queda ningún encabezado, sección ni elemento del perfil anterior.
4. Volver al primer perfil: la vista debe reconstruirse desde sus fuentes, no desde una copia guardada.
5. Recargar la página con el query del segundo perfil: la vista se genera de nuevo y coincide.

### 6. Selección y exclusividad (FR-007, SC-001)

1. Abrir la edición de un perfil y comprobar qué elementos están incluidos.
2. Generar su CV.
3. Verificar que aparecen **todos** los incluidos y **ninguno** de los no incluidos, ni como contenido, referencia o marcador.
4. Incluir el mismo elemento en otro perfil con otra relevancia; generar ambos CV y comprobar que cada uno respeta su configuración sin mezclar contenido.

### 7. Orden y relevancia (FR-008, SC-002)

1. Configurar en una sección elementos con relevancia Alta, Media y Baja.
2. Generar el CV y verificar el orden Alta → Media → Baja.
3. Con la misma relevancia, incluir elementos con fechas distintas y uno sin fecha; verificar el orden del Maestro y el registro sin fecha al final.
4. Para habilidades, incluir varias con la misma relevancia y comprobar el orden por categoría (técnica, funcional, metodológica, herramienta, tecnología) y luego por nombre.
5. Repetir la consulta y comprobar que el orden es idéntico.
6. Buscar controles de arrastre o reordenamiento: no deben existir.

### 8. Datos propios y herencia (FR-009, FR-010, US3)

1. Completar en el Perfil Maestro nombre, teléfono, correo, ubicación y enlaces.
2. Abrir un perfil con título y resumen propios; verificar que se muestran esos y no los del Maestro.
3. Dejar el título y el resumen del perfil vacíos; verificar que **no** se muestran y que **no** se usa el título ni el resumen del Maestro como respaldo.
4. Cambiar un dato personal en el Maestro y recargar el CV: el cambio debe reflejarse sin sincronización manual.
5. Eliminar un dato personal o un enlace del Maestro y recargar: debe dejar de mostrarse.
6. Verificar que editar el título o el resumen del perfil no modifica el Maestro.

### 9. Perfil inactivo o query inválido (FR-005, US4)

1. Generar el CV de un perfil y anotar su ID.
2. Desactivarlo desde **Perfiles**.
3. Abrir o recargar `/cv?profile=ID`.
4. Verificar que **no** se muestra su vista, que aparece un aviso y que el selector ofrece únicamente perfiles activos.
5. Probar `/cv?profile=999999` y `/cv?profile=abc`: mismo comportamiento, sin 404 y sin hoja.
6. Reactivar el perfil y abrir su query: vuelve a estar disponible.

### 10. Perfil sin contenido y Maestro vacío (FR-018, US4)

1. Crear un perfil activo sin título, sin resumen y sin elementos incluidos.
2. Con un Maestro con datos personales, generar su CV: debe mostrar solo los datos personales disponibles; las secciones sin elementos no deben aparecer dentro de la hoja.
3. Verificar que la orientación para agregar contenido aparece **fuera** de la hoja.
4. En un entorno de prueba (nunca sobre datos reales sin respaldo), dejar el Maestro sin datos personales y el perfil con un título propio: el CV debe mostrar solo el título, sin inventar datos personales, fechas ni secciones.
5. Dejar el perfil sin ningún dato disponible: debe mostrarse un estado vacío, no una hoja en blanco.

### 11. Vigencia, actualización y eliminación (FR-016, US3)

1. Generar un CV y anotar los datos mostrados.
2. Editar una experiencia, una educación, una certificación, una habilidad o un proyecto en el Maestro; recargar el CV y verificar el cambio.
3. Cambiar la selección o la relevancia en el perfil; recargar y verificar que solo cambia ese perfil.
4. Eliminar un registro maestro incluido; recargar y comprobar que desaparece y que el resto del CV permanece.
5. Verificar que no se muestra una copia obsoleta ni un error por el registro eliminado.

### 12. Textos largos, acentos y accesibilidad (FR-020, FR-021, SC-007)

1. Usar textos largos, acentos y caracteres especiales (por ejemplo, «Análisis y diseño — ñandú») en nombre, título, resumen y descripciones.
2. Verificar que se muestran completos, sin truncamientos, y que la página permite desplazamiento vertical.
3. Comprobar la jerarquía de encabezados: `h1` de página, `h2` del CV, `h3` de secciones.
4. Recorrer el selector, el botón de envío, los enlaces de contacto y los enlaces a fuentes solo con teclado; verificar foco visible y operabilidad.
5. Verificar que los enlaces tienen texto que identifica su destino y que la información no depende solo del color.

### 13. Límites de alcance y no persistencia (FR-013, FR-015, FR-022, SC-005)

1. Buscar acciones de exportación, impresión, descarga, PDF, CV documentado, plantilla, editor, guardado, historial, versionado, ATS, IA, ofertas o postulaciones: no deben existir.
2. Verificar que no hay botones ni enlaces que permitan editar el CV como documento.
3. Cambiar de perfil varias veces y recargar: la vista se recalcula; no aparece un CV guardado, un historial ni una versión.
4. Confirmar que no se creó ningún archivo, documento, PDF ni registro nuevo en la base local.
5. Detener la conexión de red (si el entorno lo permite) y generar la vista: debe funcionar localmente, sin llamadas externas.
6. Verificar que la única ruta nueva es `GET /cv` y que otros métodos responden 405.

### 14. Fallo de carga o composición (FR-019)

1. Este estado no se provoca desde la UI sin alterar el entorno; está cubierto por la prueba Feature con un doble de `CvPreviewContent` que lanza una excepción.
2. Verificar en la prueba que la respuesta tiene `status = error`, `preview = null`, que no se presenta una vista parcial y que la página ofrece reintento.
3. Si se simula un fallo real en un entorno de desarrollo, comprobar que no se muestra una hoja a medias y que recargar la URL conserva `?profile=ID`.

## Resultado esperado

- `php artisan test` en verde, incluido `CvGeneratorTest`.
- `npm test` en verde, incluidos `CvPreview.test.tsx` y `CvGeneratorPanel.test.tsx`.
- `npm run types:check` sin errores.
- `npm run check` y `composer lint:check` sin errores.
- Los escenarios manuales 1–13 pasan; el escenario 14 se acredita con la prueba automatizada.
- La base de datos no cambia de esquema y no se crea ningún CV persistido.
- El Perfil Maestro, los perfiles de CV, sus asociaciones y los documentos existentes permanecen intactos.
- La vista refleja la configuración de cada perfil y no muestra contenido de otro perfil.

## Registro de validación

No se ejecutaron pruebas ni escenarios manuales en la fase de Plan. Este documento define el objetivo de validación para Implement y QA; no declara la capacidad aprobada. Cualquier evidencia manual debe registrarse con fecha, entorno y resultado, distinguiendo prueba ejecutada de validación pendiente.
