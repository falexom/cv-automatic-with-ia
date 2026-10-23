# Implementation Plan: Exportación PDF

**Branch**: `005-exportacion-pdf` | **Date**: 2026-10-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/005-exportacion-pdf/spec.md`

**SPEC-004 (dependencia)**: [Generador de CV](../004-generador-de-cv/plan.md) · [contrato HTTP](../004-generador-de-cv/contracts/http-contracts.md) · [modelo de datos](../004-generador-de-cv/data-model.md). No se reabre ni se modifica.

**Governance**: especificación clarificada el 2026-10-23 (C1–C10, sin decisiones funcionales de negocio pendientes). El encargo autoriza únicamente la etapa **Plan** y la escritura de los artefactos de Plan bajo `specs/005-exportacion-pdf/`. No se modifican `spec.md`, código, pruebas, `tasks.md` ni SPEC-004. No se instalan dependencias, no se ejecuta shell, no se hace staging/commit. La base declarada por el encargo es la rama `005-exportacion-pdf`, con HEAD = MAIN = `df45be157284bede61aaef93c9cbc3e09b9a222d` y `check` con `BASE_READY true`; ese dato es **heredado del encargo** y no se re-ejecutó en esta fase. **No se ejecutaron pruebas.** Este Plan es una propuesta: no está aprobado y no inicia Tasks ni Implement. Las dependencias nuevas requieren aprobación humana en el gate de Plan.

## Summary

Implementar la **Exportación PDF**: una acción «Exportar PDF» en la sección «Generador de CV» que, para un perfil activo con vista `ready` y `has_data`, genera bajo demanda un PDF A4 de una columna a partir de la **misma composición** de SPEC-004 (`CvPreviewContent::build()`), lo descarga con un nombre determinista y seguro, valida su estructura antes de entregarlo y no lo persiste ni lo envía a servicios externos.

**Enfoque técnico**: monolito Laravel 13 + Inertia 3 + React 19/TypeScript y SQLite. Se añade una ruta `GET /cv/pdf` (`cv.pdf`), un controlador invocable `CvPdfController`, un resolvedor de perfil compartido `CvProfileResolver`, un renderizador `CvPdfRenderer` basado en **Dompdf 3.1.x**, una vista Blade de impresión `resources/views/cv/pdf.blade.php`, helpers de nombre/formato/enlaces y un validador estructural. La vista previa y su plantilla React no cambian; la acción se añade fuera del `article` en `CvGeneratorPanel`. Se añaden `dompdf/dompdf:^3.1` (runtime) y `smalot/pdfparser:^2.13` (solo pruebas). No hay tablas, migraciones, seeders ni factories nuevos.

### Decisiones clave

- **Renderizador local Dompdf**: PHP puro, sin binario externo, sin proceso de navegador, sin red y con fuentes DejaVu embebidas. Se descartan Browsershot/Chrome y la generación cliente por complejidad, portabilidad y duplicación de plantilla (ver [research.md](./research.md)).
- **Una sola plantilla, dos representaciones**: la Blade A4 es la representación de impresión de la única plantilla de SPEC-004, no un segundo diseño seleccionable. Consume la misma composición (`CvPreviewData`); no recompone, filtra ni ordena contenido. El renderizador solo omite `source_links`, que no es contenido, para que no se rendericen enlaces internos.
- **Composición única**: `CvPreviewContent::build()` no se modifica. No hay segunda lógica de selección, relevancia, orden, herencia, datos propios ni contenido.
- **Selección compartida**: `CvProfileResolver` extrae la resolución de perfiles activos y query de `CvGeneratorController@index`; lo usan la vista previa y la exportación. El cambio en el controlador existente es focalizado y queda cubierto por las pruebas de SPEC-004.
- **Controlador separado**: `CvPdfController` aísla la respuesta binaria y los errores de exportación de la página Inertia. No se crea una API JSON ni una segunda página.
- **Ruta GET**: `GET /cv/pdf` (`cv.pdf`), solo lectura, sin CSRF. Un query inválido o un perfil no disponible redirige con toast; no produce 404 ni PDF vacío.
- **Nombre determinista**: `cv-{base}.pdf` con `Str::ascii`, minúsculas, guiones, truncado a 80 y fallback `cv.pdf`; se usa solo para el archivo, nunca se imprime.
- **Validación antes de entregar**: cabecera, trailer, páginas y `startxref`; las ausencias de cifrado/JS/formularios/adjuntos/acciones se verifican en pruebas y por configuración. Si la validación estructural falla, no se entrega archivo.
- **Sin persistencia**: el PDF se genera en memoria; el directorio de trabajo de Dompdf se elimina en `finally`; solo contiene métricas/temporales de fuente, no el PDF; si la limpieza falla, no se entrega el archivo. No hay historial, versión, URL posterior ni registro del contenido. Excepción heredada: `CvProfileContent::personalData()` usa `Profile::current()` (`firstOrCreate`) y puede crear el singleton vacío del Maestro en una base pristine; es comportamiento de SPEC-001, no una copia del CV.
- **Sin numeración de página**: decisión de Plan dentro de C10; evita contenido adicional y scripts de paginación. La paginación es natural y se revisa manualmente.
- **Pruebas proporcionales**: Feature con PDF real y parser dev; Unit de nombre/formato/enlaces/validador; Vitest de la acción; apertura en dos lectores y revisión visual manual.

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json` requiere `^8.3`; el entorno observado tiene 8.5.5) con Laravel 13.17; TypeScript 5.7 y React 19.2.

**Primary Dependencies**: Laravel 13, Inertia 3 (`inertiajs/inertia-laravel` + `@inertiajs/react`), React 19, Tailwind CSS 4, shadcn/ui sobre Radix UI, Vite 8 y Wayfinder. **Propuestas para esta capacidad**: `dompdf/dompdf:^3.1` (runtime; última estable observada 3.1.6) y `smalot/pdfparser:^2.13` (dev; última estable observada 2.13.1). No se añaden dependencias JS ni binarios de navegador.

**Storage**: SQLite local en `database/database.sqlite`; sin tablas nuevas. Dompdf usa un directorio temporal por petición bajo `storage/app/private/cv-pdf/{uuid}` para métricas de fuente/temporales; se elimina en `finally`. El PDF nunca se escribe en disco.

**Testing**: Pest 4 / PHPUnit 12 para Feature y Unit (`php artisan test`); Vitest + React Testing Library para frontend (`npm test`); `npm run types:check`, `npm run check` y `composer lint:check`. Las pruebas de PDF usan Dompdf real y `smalot/pdfparser` para extraer texto, páginas, tamaño y metadatos. La apertura en dos lectores, la revisión visual y el foco/teclado son validación manual.

**Target Platform**: aplicación web local (localhost) para navegador de escritorio, uso sin conexión, un solo usuario y un único Perfil Maestro.

**Project Type**: aplicación web única (monolito Laravel + SPA Inertia/React en el mismo repositorio); no se crea una API JSON separada.

**Performance Goals**: sin objetivos medibles. La aplicación es local y monousuario; una exportación compone un perfil, renderiza una vista y valida una cadena. No se introducen benchmarks, colas, caché ni pruebas de carga.

**Constraints**: monousuario y sin autenticación; interfaz en español con acentos y caracteres especiales; sin servicios externos, IA, red, persistencia, historial, versionado, múltiples plantillas, impresión integrada, publicación, correo, anexos, concatenación, OCR, firma, ATS ni postulaciones; sin migraciones ni cambios de modelo; sin modificar la vista previa de SPEC-004.

**Scale/Scope**: 1 usuario; 1 Perfil Maestro; N perfiles de CV, de los cuales solo los activos se ofrecen; 1 ruta nueva; 1 controlador nuevo; 1 controlador existente con cambio focalizado; 6 clases de soporte pequeñas (resolvedor, renderizador, validador, nombre, formato y enlaces); 1 vista Blade; 1 acción UI; 2 dependencias Composer (1 runtime, 1 dev); 0 tablas nuevas; FR-001 a FR-017.

## Constitution Check

_GATE: debe pasar antes de Phase 0 research. Re-evaluado después de Phase 1 design. Referencia: [Constitución del proyecto](../../.specify/memory/constitution.md)._

| Principio | Estado | Evidencia del diseño |
| --- | --- | --- |
| I. Fuente única de verdad | PASA | El PDF se compone desde el Maestro y el perfil en cada petición; no se copia, cachea ni persiste. |
| II. El CV es un resultado | PASA | El PDF es una representación derivada y de solo lectura; no se edita ni se convierte en fuente de datos. |
| III. Separación datos/reglas/presentación | PASA | Datos y reglas siguen en `CvPreviewContent`/SPEC-004; la Blade solo presenta; Dompdf renderiza; el controlador orquesta. |
| IV. MVP estrictamente acotado | PASA | La capacidad es el paso 13 del MVP; no se añaden funciones fuera de la spec. |
| V. Perfil Profesional Maestro | PASA | No se modifican entidades, campos, validaciones ni datos; solo se leen. |
| VI. Perfiles como configuración | PASA | Se usa el perfil activo seleccionado y su composición; no se duplican datos ni se crean variantes. |
| VII–XI. Documentos, evidencias y CV documentado | N/A | Fuera de alcance; no se anexan certificados, no se concatena, no se aplica OCR. |
| XII. Arquitectura simple local | PASA | Monolito Laravel + PHP/Dompdf; sin servicios, contenedores, colas ni cloud. |
| XIII. Evitar complejidad prematura | PASA | Una ruta, un controlador, helpers pequeños y una vista; sin interfaces/DTO/repositorios/caché. Las clases se justifican por composición compartida, seguridad, validación y testabilidad. |
| XIV. Datos relacionales, no duplicados | PASA | Se reutilizan relaciones y pivotes de SPEC-003; no se copian textos ni registros. |
| XV. Administrador orientado a tareas | PASA | La acción está en «Generador de CV», junto a la vista previa; un solo paso para descargar. |
| XVI. Vista previa antes de exportar | PASA | La exportación consume la vista previa existente y exige `ready` con datos; no es un proceso ciego. |
| XVII. UI/UX con herramientas existentes | PASA | Se reutilizan `Button`/`asChild`, tokens y el toast existente; no se añade tooling de diseño. |
| XVIII. Una plantilla excelente | PASA | Una sola plantilla; la Blade es su representación A4, sin selector ni variantes. |
| XIX. Cada Spec entrega una capacidad verificable | PASA | FR-001–FR-017 y SC-001–SC-008 con pruebas automatizadas y validación manual en `quickstart.md`. |
| XX. Las tareas no sustituyen a los Specs | PASA | El trabajo se descompone en Tasks dentro de este Spec; no se abren tareas funcionales independientes. |
| XXI. Pruebas proporcionales al riesgo | PASA | Feature de validez, fidelidad, multipágina, enlaces, errores y no persistencia; Unit y Vitest; manual de apertura/visual. |
| XXII. No romper datos existentes | PASA | Sin migraciones ni escrituras; no se modifican datos, asociaciones ni documentos. La prueba de regresión de rutas se actualiza de forma focalizada. |
| XXIII. Privacidad local por defecto | PASA | Dompdf local, `isRemoteEnabled=false`, sin envío de datos ni llamadas externas. |
| XXIV. No almacenar secretos | PASA | No se añaden credenciales, claves ni configuración sensible. |
| XXV. No expandir el MVP | PASA | Alcance idéntico a la spec: sin impresión, publicación, correo, anexos, OCR, firma, IA ni plantillas múltiples. |
| XXVI. Cambios importantes requieren decisión | PASA | Las dos dependencias, la ruta y la actualización de la prueba de regresión se someten al gate humano; no se cambia stack, SQLite, autenticación, modelo del Maestro ni número de plantillas. |

**Resultado**: sin violaciones. La única complejidad nueva son dos dependencias justificadas y helpers pequeños; se documenta en Complexity Tracking.

## Complexity Tracking

> **Solo se completa si hay violaciones que justificar.**

**Sin violaciones.** No se requiere justificación de complejidad estructural: no hay tablas, migraciones, colas, caché, servicios, interfaces, DTO, repositorios ni patrones adicionales.

**Justificación de las dependencias nuevas**

| Dependencia | Tipo | Necesidad técnica | Alternativa descartada |
| --- | --- | --- | --- |
| `dompdf/dompdf:^3.1` | Runtime | Es el renderizador local HTML/CSS→PDF: produce A4, fuentes embebidas y anotaciones sin binario externo ni red. Sin él no se puede cumplir FR-004/FR-009/FR-013/FR-014. | Chrome headless/Browsershot (binario, proceso, portabilidad) y generación cliente (duplica plantilla y no valida en servidor). |
| `smalot/pdfparser:^2.13` | Dev | Permite verificar en pruebas el texto real, páginas, tamaño A4, fuentes y metadatos del PDF generado, sin depender de binarios como `pdfinfo`/`pdftotext`. | Binarios externos (no portables) o validación solo estructural (insuficiente para fidelidad/contenido). No se usa en runtime. |

- Ambas se fijan con restricciones de versión y se revisan con `composer audit`; el lock las pinnea.
- Comandos propuestos, **no ejecutados**: `composer require dompdf/dompdf:^3.1` y `composer require --dev smalot/pdfparser:^2.13`.
- No se añade `barryvdh/laravel-dompdf`: un wrapper no aporta y añade configuración.
- No se añade ninguna dependencia JS.

## Project Structure

### Documentation (this feature)

```text
specs/005-exportacion-pdf/
├── plan.md                  # Este archivo
├── research.md              # Fase 0: comparación y decisiones técnicas
├── data-model.md            # Fase 1: PDF efímero, entrada, nombre, validación, no persistencia
├── contracts/
│   └── http-contracts.md    # Fase 1: ruta, query, éxito/errores, headers, UI, enlaces
├── quickstart.md            # Fase 1: comandos y validación manual
├── spec.md                  # Entrada clarificada; no se modifica
└── tasks.md                 # Fase 2: lo crea Project Manager; no lo crea Plan
```

### Source Code (repository root)

```text
app/
├── Http/
│   └── Controllers/
│       ├── CvGeneratorController.php   # modificación focalizada: usa CvProfileResolver
│       └── CvPdfController.php         # nuevo: revalida, compone, renderiza, valida y descarga
└── Support/
    ├── CvPreviewContent.php            # reutilizado; NO se modifica
    ├── CvProfileContent.php            # reutilizado; NO se modifica
    ├── CvProfileResolver.php           # nuevo: resolución compartida de perfil activo/query
    ├── CvPdfRenderer.php               # nuevo: Blade + Dompdf + limpieza temporal
    ├── CvPdfValidator.php              # nuevo: validación estructural previa a la entrega
    ├── CvPdfFilename.php               # nuevo: nombre determinista C5
    ├── CvPdfFormatter.php              # nuevo: fechas/categorías equivalentes a dates.ts/skill-categories.ts
    └── CvPdfLink.php                   # nuevo: allowlist de esquemas y href seguros

routes/
└── web.php                             # + GET /cv/pdf (cv.pdf)

resources/
├── views/
│   └── cv/
│       └── pdf.blade.php               # nuevo: representación A4 de la única plantilla
└── js/
    └── components/
        ├── CvGeneratorPanel.tsx        # + acción «Exportar PDF» fuera del article
        ├── CvPreview.tsx               # NO se modifica
        └── __tests__/
            ├── CvGeneratorPanel.test.tsx  # + pruebas de la acción
            └── CvPreview.test.tsx         # sin cambios; conserva la garantía de solo lectura

tests/
├── Feature/
│   ├── CvPdfExportTest.php             # nuevo: contrato de exportación
│   └── CvGeneratorTest.php             # actualización focalizada de la prueba de rutas
└── Unit/
    ├── CvPdfFilenameTest.php           # nuevo
    ├── CvPdfFormatterTest.php          # nuevo
    ├── CvPdfLinkTest.php               # nuevo
    └── CvPdfValidatorTest.php          # nuevo

composer.json / composer.lock           # + dompdf/dompdf ^3.1; + smalot/pdfparser ^2.13 (dev)

database/
└── (sin cambios: no hay migraciones, seeders ni factories nuevos)
```

**Structure Decision**: se mantiene la aplicación única Laravel + Inertia/React de la raíz del repositorio. La exportación se separa en un controlador binario (`CvPdfController`) y una vista Blade, en lugar de crear una segunda página Inertia o una API JSON. La resolución de perfil se comparte para no duplicar la lógica de SPEC-004. La vista React `CvPreview` y la composición `CvPreviewContent` no se tocan; la acción se añade en el panel, fuera de la hoja. Los helpers son funciones/clases concretas, sin interfaces ni DTO, porque cada una cubre una necesidad verificable: selección compartida, renderizado, validación, nombre, formato y seguridad de enlaces. No se modifica `CvProfileContent`, los modelos, las migraciones ni el comportamiento de SPEC-001/003/004.

## Backend design

### Flujo de una exportación

```text
GET /cv/pdf?profile=ID
  │
  ├─ 1. resolver->resolve($request->query())
  │      ├─ sin perfiles activos → no_active_profiles
  │      ├─ sin query/''        → selection_required
  │      ├─ query inválido      → unavailable
  │      └─ perfil activo       → ready + profile
  │
  ├─ 2. Si no es ready: redirect a /cv o /cv?profile=ID + flash toast error.
  │      Sin PDF, sin excepción visible.
  │
  ├─ 3. CvPreviewContent::build($profile)
  │      └─ si has_data=false → redirect a /cv?profile=ID + toast; sin PDF.
  │
  ├─ 4. renderer->render($preview)
  │      ├─ Blade cv.pdf con el payload de contenido (sin source_links)
  │      ├─ Dompdf A4/portrait, DejaVu Sans, CSS 2.1
  │      └─ directorio temporal por petición, limpieza en finally
  │
  ├─ 5. validador->isValid($pdf)
  │      └─ si false → excepción controlada + report(); redirect + toast; sin PDF.
  │
  └─ 6. Respuesta 200 application/pdf
         Content-Disposition: attachment; filename="cv-....pdf"
         Content-Length, Cache-Control: no-store, private, Pragma: no-cache,
         X-Content-Type-Options: nosniff
```

- El `try/catch (Throwable)` cubre resolución, composición, renderizado y validación. `report($exception)` registra el error; el usuario recibe un mensaje genérico.
- `CvPdfController` recibe por constructor `CvProfileResolver`, `CvPreviewContent`, `CvPdfRenderer` y `CvPdfValidator`; los dobles de prueba se enlazan en el contenedor. `CvPdfFilename` es una función pura del nombre del perfil.
- `CvPdfRenderer` y `CvPdfValidator` son clases concretas inyectables, no finales, para permitir dobles en las pruebas Feature; no se introduce una interfaz ni un DTO solo para testear.
- El render de la respuesta binaria no se intenta si falló la validación.
- No se inicia la descarga antes de tener el PDF completo y validado; no hay archivo parcial.

### Resolución de perfil y cambio mínimo

`CvProfileResolver` devuelve `status`, `profiles` (solo activos, ordenados por `name`) y `profile` (o `null`). `CvGeneratorController@index` lo usa y conserva exactamente su contrato:

- `no_active_profiles` si no hay activos;
- `selection_required` si no hay query o está vacío;
- `unavailable` si el query no es un entero positivo o el perfil no está activo;
- `ready` con `selected_profile_id` y `preview` si el perfil activo existe.

El resolvedor recibe el **array completo de query** (`$request->query()`), no `$request->query('profile')`: así `?profile[]=1` se trata como inválido y no lanza una excepción de scalar de Symfony. Esto conserva el comportamiento actual de `parse_str` de SPEC-004 y evita convertir un query array en `status = error`.

El cambio en `CvGeneratorController` se limita a sustituir el bloque de consulta/validación por la llamada al resolvedor y mantener el mapeo de `profiles`, el `try/catch`, `report()` y los estados. Las pruebas existentes de SPEC-004 (opciones activas, orden, query inválido, perfiles inactivos, no persistencia, error, textos largos) son la red de seguridad. No se cambia `CvPreviewContent`, `CvPreview` ni los props de la página.

### Renderizador y vista Blade

- `CvPdfRenderer` expone `render(array $preview): string`: construye un view data con `personal_data`, `professional_title`, `summary`, `content` y `has_data`, renderiza `view('cv.pdf', ['preview' => $viewData])` y devuelve la cadena de Dompdf. Omite `source_links` para que los enlaces internos de corrección no puedan renderizarse por error. No altera contenido, orden ni datos.
- No recibe el nombre del perfil: así no puede imprimirlo ni meterlo en metadatos.
- Opciones: `isRemoteEnabled=false`, `isPhpEnabled=false`, `isJavascriptEnabled=false`, `chroot` a un directorio vacío propio, `tempDir`/`fontCache` al mismo directorio, `defaultFont=DejaVu Sans`, A4/portrait, subsetting activo, `defaultMediaType=print`.
- La Blade usa solo HTML/CSS 2.1: sin flex, grid, `float`, posiciones absolutas/fijas, tablas de maquetación, imágenes, JS, formularios, `iframe`, `object`, `embed`, recursos remotos ni `@font-face` remoto.
- Todos los datos se escapan con `{{ }}`; los textos multilínea usan `{!! nl2br(e($text)) !!}`. No se inserta HTML del usuario.
- La Blade recorre el payload en el orden fijo: cabecera (nombre, contacto, título, resumen), experiencia, educación, cursos y certificaciones, habilidades, proyectos. Las secciones sin elementos no se renderizan; los campos vacíos se omiten.
- `source_links` no se pasa a la vista. El nombre del perfil no se renderiza.
- Si no hay nombre completo, la Blade no imprime un nombre inventado ni el encabezado sr-only «Currículum» de la vista previa (no es contenido visible); conserva el resto de la cabecera disponible.
- `CvPdfFormatter` replica en PHP las reglas de `dates.ts` y `skill-categories.ts` (meses `ene…dic`, rango con `–`, `actual`/`en curso`, etiquetas de categoría) y se cubre con Unit/Feature para evitar divergencias.
- `CvPdfLink` aplica la allowlist `http/https/mailto/tel`; un enlace inválido conserva su texto visible pero no se activa.

### Paginación y presentación

- A4 vertical, una columna, márgenes de 18 mm, DejaVu Sans 10,5 pt / 1,4; nombre 22 pt, título 12 pt, encabezados 12,5 pt, metadatos 9,5 pt. Solo normal/negrita.
- `page-break-after: avoid` en encabezados de sección y títulos de elemento; sin `page-break-inside: avoid` en elementos largos para permitir su continuación. Si Dompdf no respeta `page-break-after: avoid`, se evalúa agrupar encabezado + primer elemento corto en un bloque con `page-break-inside: avoid`; no se agrupan elementos largos para no crear huecos.
- Sin numeración de página, cabeceras, pies, logotipos, marcas de agua, fechas visibles ni textos promocionales.
- Sin `page-break-after: always` ni bloques vacíos al final; se revisan páginas en blanco evitables.
- El contenido largo se ajusta con `word-wrap: break-word`; las URLs largas solo van en anotaciones, con etiquetas cortas visibles.

### Validación

`CvPdfValidator` expone `isValid(string $pdf): bool` y comprueba, en runtime, solo la estructura:

- no vacío; `%PDF-` y `%%EOF` (reutiliza `PdfFile::looksLikePdf`);
- `/Type /Pages` y al menos un `/Type /Page`;
- `startxref` en el tramo final.

La legibilidad del texto, el número exacto de páginas, el tamaño A4, las fuentes y las anotaciones se comprueban con `smalot/pdfparser` en las pruebas. La ausencia de cifrado, JavaScript, formularios, adjuntos y acciones `Launch` se verifica también en las pruebas sobre el PDF real y se garantiza por la configuración del renderizador y la plantilla; **no** se usan búsquedas de subcadenas en runtime para no rechazar URLs legítimas del usuario que contengan esos textos en una ruta. No se usa el parser en runtime. Si la validación estructural falla, se lanza una excepción controlada, se reporta y se redirige con toast; no se entrega archivo.

## Frontend design

En `CvGeneratorPanel`, antes de `CvPreview` y fuera del `article`:

```text
{status === 'ready' && preview?.has_data && selected_profile_id !== null ? (
    <div className="flex justify-end">
        <Button asChild>
            <a href={`/cv/pdf?profile=${selected_profile_id}`}>Exportar PDF</a>
        </Button>
    </div>
) : null}
```

- Anchor real, no `Inertia Link` ni botón con `onClick`; el navegador gestiona la descarga.
- Sin `target="_blank"` ni `download`; el nombre lo fija `Content-Disposition`.
- Nombre accesible «Exportar PDF»; foco visible por los estilos de `Button`; operable con teclado.
- Visible solo en `ready` con `has_data` y `selected_profile_id`; ausente en `no_active_profiles`, `selection_required`, `unavailable`, `error` y `ready` sin datos.
- No se modifica `CvPreview`; la hoja no incorpora controles de exportación, impresión, edición ni plantilla.

## Testing design

### Feature (`tests/Feature/CvPdfExportTest.php`)

1. **Ruta, headers y nombre**: perfil activo con datos → `200`, `application/pdf`, `Content-Disposition` attachment con `cv-....pdf`, `Content-Length` correcto, cuerpo no vacío, `no-store`, `nosniff`.
2. **PDF válido**: cabecera/trailer, páginas, A4 vertical, fuentes embebidas, texto real; sin cifrado, JS, formularios, adjuntos ni `Launch`.
3. **Fidelidad**: Maestro + perfil con las cinco clases, incluidos/excluidos, relevancias, título/resumen propios, datos heredados y otro perfil distinto; el texto extraído contiene solo lo incluido, en orden; sin excluidos, otro perfil, relevancia/estado, nombre del perfil ni `source_links`; sin fallback al Maestro.
4. **Multipágina**: CV extenso → páginas > 1; todo el contenido una vez; páginas con texto; repetición estable.
5. **Enlaces**: anotaciones `/URI` con `mailto:`, `tel:` y `http(s)`; mismo texto visible; sin enlaces internos; URL insegura sin anotación.
6. **Disponibilidad**: sin activos, sin selección, inactivo, inexistente, query array (`?profile[]=1`), sin datos (incluido el Maestro) → redirect + toast + sin PDF; el query array no debe convertirse en `status = error`.
7. **Errores**: doble de `CvPdfRenderer` que lanza; doble que devuelve PDF inválido; doble de `CvPdfValidator` que falla → redirect + toast + sin PDF, sin filtrar excepción.
8. **No persistencia**: conteos y `updated_at` sin cambios; directorio temporal vacío; sin archivos PDF; configuración sin red y comprobación manual sin conexión. Crear el Maestro antes de medir para no atribuir al PDF el `firstOrCreate` heredado de `Profile::current()`.
9. **Solo GET**: `POST/PUT/PATCH/DELETE /cv/pdf` → 405.
10. **Vista sin recursos remotos**: renderizar la Blade y comprobar que no contiene `img`, `link`, `script`, `iframe`, `object`, `embed`, `form` ni `url(http...)`; los `http(s)` solo aparecen en `<a href>` de enlaces del CV. Refuerza `isRemoteEnabled=false` sin depender de un firewall.

Los helpers del archivo deben usar nombres únicos (por ejemplo `cvPdfMaster`, `cvPdfExport`) para no colisionar con las funciones globales `cvGeneratorMaster`/`pdfUpload` que Pest carga entre archivos. Las aserciones sobre texto extraído deben normalizar espacios y saltos de línea, porque el extractor puede introducirlos; el orden se comprueba con `strpos` sobre el texto normalizado.

### Unit

- `CvPdfFilenameTest`: acentos, ñ, espacios, `/`, `\`, `..`, comillas, puntos, controles, emojis, vacío, 100 caracteres, estabilidad y patrón final.
- `CvPdfFormatterTest`: meses, año solo, rango, `actual`, `en curso`, categorías y fallback.
- `CvPdfLinkTest`: `http/https/mailto/tel` válidos; `javascript:`, `data:`, `file:`, `vbscript:`, `phar:`, `//`, credenciales y controles rechazados; teléfono/correo inválidos sin enlace.
- `CvPdfValidatorTest`: PDF válido; vacío; truncado; sin `%%EOF`; sin páginas; sin `startxref`. Las ausencias de cifrado, JS, formularios, adjuntos y `Launch` se comprueban en Feature sobre el PDF generado.

### Vitest

- `CvGeneratorPanel.test.tsx`: acción visible en `ready` con datos; `href="/cv/pdf?profile=ID"`; fuera del `article`; sin `target`/`download`; foco visible; ausente en los demás estados y en `ready` sin datos.
- `CvPreview.test.tsx`: sin cambios; conserva la garantía de que la hoja no contiene exportación, impresión, edición ni plantilla.

### Ajuste focalizado de SPEC-004

La última prueba de `tests/Feature/CvGeneratorTest.php` prohibía `cv.pdf`. Se actualiza para:

- afirmar que `cv.index` y `cv.pdf` existen;
- mantener prohibidas `cv.export`, `cv.print`, `cv.download`, `cv.template`, `cv.store`, `cv.update`, `cv.destroy`, `cv.regenerate` y `cv.reorder`;
- recolectar `cv` y `cv/pdf`, exigir exactamente dos rutas y que ambas incluyan GET y no admitan POST/PUT/PATCH/DELETE;
- comprobar 405 en `POST/PUT/PATCH/DELETE` para ambas.

No se cambia ninguna expectativa sobre la hoja, su composición, su solo lectura ni sus datos. No se modifica `spec.md` de SPEC-004 ni su contrato.

### Manual

Apertura en dos lectores; revisión visual de A4, una columna, márgenes, jerarquía, tipografía, cortes, solapamientos, encabezados huérfanos y páginas en blanco; foco/teclado; exportación sin red; comprobación de no persistencia. Detalle en [quickstart.md](./quickstart.md).

## Requirement Coverage

| Requisito | Diseño | Prueba prevista |
| --- | --- | --- |
| FR-001 | Acción visible solo en `ready && has_data && selected_profile_id`; `CvPdfController` revalida perfil activo y datos; sin PDF en los demás estados. | Vitest de visibilidad; Feature de estados y acceso directo. |
| FR-002 | `CvPreviewContent::build()` sin cambios; misma selección/composición; sin segunda lógica. | Feature de fidelidad y orden; revisión de código; regresión de SPEC-004. |
| FR-003 | `CvProfileResolver` revalida perfil activo; `has_data` antes de renderizar; redirect + toast si no procede. | Feature de perfil desactivado/eliminado/sin datos. |
| FR-004 | Blade A4, una columna, 18 mm, DejaVu Sans embebida, jerarquía y orden. | MediaBox A4; fuentes; revisión visual manual. |
| FR-005 | La Blade solo recorre el payload; sin `source_links`, nombre del perfil, metadatos ni controles; secciones/campos vacíos omitidos. | Texto extraído incluidos/excluidos; `missing` de enlaces internos; manual. |
| FR-006 | Formatos equivalentes de fechas/categorías; título/resumen propios; datos personales heredados; sin fallback. | Unit de formato; Feature de herencia y datos propios. |
| FR-007 | Saltos CSS naturales; `page-break-after: avoid`; sin truncar; sin numeración; sin páginas en blanco evitables. | Feature multipágina; revisión visual manual. |
| FR-008 | Texto real, DejaVu, `nl2br(e())`, `word-wrap`; sin imágenes rasterizadas. | Parser de texto con acentos/largos; `pdftotext` manual opcional. |
| FR-009 | `CvPdfLink` + `<a href>`; teléfono visible y `tel:`; mismos textos; sin enlaces internos. | Feature de anotaciones `/URI` y texto visible. |
| FR-010 | Allowlist de esquemas; sin JS/formularios/adjuntos/acciones por configuración y plantilla; metadatos sin datos internos. | Unit de enlaces; Feature de PDF seguro y metadata. |
| FR-011 | `CvPdfFilename` con C5; `Content-Disposition` seguro. | Unit de tabla de nombres; Feature de header. |
| FR-012 | Descarga en memoria; `no-store`; sin persistencia; sin URL posterior. | Feature de headers y no persistencia. |
| FR-013 | Dompdf local; `isRemoteEnabled=false`; fuentes embebidas; sin servicios. | Configuración; manual sin red; PDF autocontenido. |
| FR-014 | `CvPdfValidator` antes de entregar; parser en pruebas; dos lectores manual. | Unit y Feature de validez/truncado; manual. |
| FR-015 | `try/catch`, `report()`, redirect + toast, reintento, sin archivo parcial. | Feature de fallo de renderizador/validador; manual de reintento. |
| FR-016 | Sin escrituras, migraciones, archivos persistentes ni cambios en la vista previa. | Conteos/`updated_at`; regresión SPEC-004; revisión de migraciones. |
| FR-017 | Sin anexos, concatenación, OCR, firma, publicación, correo, ATS, IA, impresión ni plantillas múltiples. | Prueba de rutas/alcance; revisión de UI; regresión. |

## Risks and Mitigations

| Riesgo | Impacto | Mitigación | Verificación |
| --- | --- | --- | --- |
| Dompdf no soporta flex/grid o parte de CSS 2.1. | Diferencia visual o layout roto. | CSS 2.1 simple, una columna, sin tablas de maquetación ni posiciones; revisión visual. | Feature de PDF + manual. |
| Diferencia visual con la vista previa Tailwind. | Percepción de baja fidelidad. | Jerarquía/contenido equivalentes, no pixel-perfect; tipografía y márgenes definidos. | Comparación manual campo a campo. |
| Saltos de página defectuosos, encabezados huérfanos o páginas en blanco. | CV poco profesional o contenido perdido. | `page-break-after: avoid`, sin breaks forzados ni bloques vacíos; elementos largos pueden continuar; fallback de agrupación para encabezado + primer elemento corto. | Feature multipágina; manual. |
| Fuente no embebida, glifos faltantes o acentos incorrectos. | Texto ilegible o no fiel. | DejaVu Sans, subsetting, UTF-8, `nl2br(e())`; pruebas con acentos/eñes/signos. | Parser de texto; inspección `/FontFile2`; manual. |
| Enlaces no clicables, `tel:` no soportado o anotaciones inseguras. | Pérdida de enlaces o riesgo. | `<a href>` + allowlist; teléfono siempre visible; validación de esquemas; prueba de `/URI`. | Feature de anotaciones; dos lectores. |
| URL largas desbordan la caja. | Texto recortado. | Etiquetas cortas visibles; `word-wrap`; URLs solo en anotación. | Manual con URL larga. |
| Directorio temporal o caché de fuentes queda en disco. | Incumplimiento de C6. | Directorio único por petición, `finally`, fallo de limpieza aborta la entrega. | Feature de directorio vacío; revisión manual. |
| Dompdf tiene avisos de seguridad o acceso remoto. | Ejecución/lectura no deseada. | `isRemoteEnabled=false`, `isPhpEnabled=false`, `isJavascriptEnabled=false`, `chroot` vacío, sin HTML de usuario, versión estable y audit. | Revisión de opciones; Feature de PDF sin JS/remoto; `composer audit`. |
| Dependencia nueva no aprobada o incompatible. | Bloqueo de Implement. | Gate humano; restricciones `^3.1`/`^2.13`; verificar extensiones; lock. | `composer install`/audit en Implement. |
| `smalot/pdfparser` en mantenimiento limitado. | Pruebas de contenido dependientes de un proyecto poco activo. | Es dev-only, acotado y fijado; si falla, se sustituye por otra librería de parsing o por `pdftotext` en QA sin tocar runtime. | Suite Feature; revisión de compatibilidad. |
| Falso positivo/negativo del validador estructural. | PDF válido rechazado o inválido entregado. | Comprobaciones conservadoras; Unit con PDF válido/corrupto; parser en pruebas; ajuste con evidencia. | Unit/Feature. |
| Regresión por el cambio en `CvGeneratorController`. | Se rompe SPEC-004. | Cambio focalizado, mismo contrato; pruebas existentes como red. | `CvGeneratorTest` completo; Vitest. |
| Pruebas de PDF lentas o con memoria alta. | Suite incómoda. | Pocas pruebas Feature con render real; parser con límite de memoria; Unit para casos límite. | Tiempo de suite en Implement. |
| Metadatos con datos internos. | Privacidad. | No se pasa el perfil a la vista; `<title>` genérico; sin author/subject; test de metadata. | Feature de metadata. |
| Nombre de archivo peligroso o inestable. | Header injection o nombres extraños. | Regla C5 con `Str::ascii` y allowlist final; Unit de tabla; header ASCII. | Unit + Feature. |
| Navegador no descarga o abre el PDF. | Mala experiencia. | `Content-Disposition` attachment, `Content-Type` correcto, `no-store`; dos lectores. | Manual. |
| Cambio de comportamiento de Dompdf entre versiones. | Layout/validación inestable. | Restricción `^3.1`, lock, pruebas con PDF real; actualizar con evidencia. | Suite en CI/local. |

## Rollback

- **Sin migraciones ni cambios de datos**: no hay rollback de esquema.
- Si el renderizador falla o no se aprueba, se puede retirar la ruta `cv.pdf`, el controlador, la vista Blade, los helpers y la acción UI; `GET /cv` y la vista previa siguen operativas sin cambios.
- Revertir el cambio focalizado de `CvGeneratorTest` restaura la prueba anterior; no se pierde ninguna garantía de solo lectura de la hoja.
- Retirar dependencias: `composer remove dompdf/dompdf` y `composer remove --dev smalot/pdfparser` (solo si se revierte la capacidad; no se ejecuta en Plan).
- No hay datos, archivos persistidos ni historial que limpiar; el directorio temporal se elimina por petición.

## Human approval gate (Plan)

El Plan queda **propuesto, no aprobado**. Antes de Tasks/Implement se requiere decisión humana sobre:

1. **Renderizador**: aprobar Dompdf 3.1.x + vista Blade A4 como representación de la única plantilla, con las limitaciones de CSS 2.1 y la fidelidad equivalente (no pixel-perfect).
2. **Dependencias**: aprobar `dompdf/dompdf:^3.1` (runtime) y `smalot/pdfparser:^2.13` (dev), con sus comandos de instalación, el `composer.lock` resultante y las licencias observadas (LGPL-2.1 y LGPL-3.0).
3. **Ruta y nombre**: aprobar `GET /cv/pdf` (`cv.pdf`) y el patrón `cv-{slug}.pdf` con fallback `cv.pdf`.
4. **Presentación**: aprobar A4, 18 mm, DejaVu Sans, sin numeración de página y sin cabeceras/pies.
5. **Regresión**: aprobar la actualización focalizada de la prueba de rutas de SPEC-004 para reconocer `cv.pdf`, manteniendo prohibidas las rutas de escritura, impresión, descarga alternativa, plantilla, historial y reordenamiento.

Hasta que exista esa aprobación, no se instalan dependencias, no se crean Tasks y no se implementa. La aceptación funcional/visual final, el merge a `main` y cualquier push requieren una aprobación humana expresa y quedan fuera de esta fase.

## Phase 0/1 Outputs

- **[research.md](./research.md)**: comparación Dompdf / navegador headless / cliente / otras librerías PHP; decisión, opciones, seguridad, nombre, validación y evidencia documental.
- **[data-model.md](./data-model.md)**: PDF efímero, entrada `CvPreviewData`, nombre, validación, enlaces, ciclo de vida, no persistencia e invariantes.
- **[contracts/http-contracts.md](./contracts/http-contracts.md)**: ruta, query, éxito, headers, errores/redirect, contrato del PDF, enlaces, UI y obligaciones de prueba.
- **[quickstart.md](./quickstart.md)**: instalación propuesta, comandos de prueba y validación manual de extremo a extremo.
- **tasks.md**: no se crea en esta fase; lo generará Project Manager a partir de este Plan y del spec clarificado.

## Post-Design Constitution Re-Check (Phase 1)

Re-evaluado tras generar `research.md`, `data-model.md`, `contracts/http-contracts.md` y `quickstart.md`:

- **I / II / III**: el PDF se compone en memoria desde el Maestro y el perfil; no se copia, guarda ni edita; datos, reglas, presentación y renderizado quedan separados.
- **V / VI / XIV**: no se modifican entidades ni datos; se reutilizan relaciones y pivotes; no se duplica contenido.
- **XII / XIII**: monolito Laravel + Dompdf, una ruta, un controlador y helpers pequeños; sin servicios, colas, caché, interfaces ni tablas. Las dos dependencias tienen justificación técnica.
- **XVI / XVIII**: la exportación consume la vista previa existente y usa una sola plantilla; la Blade es su representación A4, sin selector.
- **XXI**: validez, fidelidad, multipágina, enlaces, disponibilidad, errores, no persistencia y regresión tienen cobertura Feature/Unit/Vitest; la apertura y la revisión visual son manuales.
- **XXII**: no hay migraciones ni escrituras; la prueba de rutas se actualiza de forma focalizada y no se altera la hoja.
- **XXV / XXVI**: no se añaden capacidades fuera de la spec; las dependencias y la ruta se someten a aprobación humana y no se introducen incidentalmente.

**Sin violaciones nuevas.** El Plan no se declara aprobado; la implementación y la validación quedan pendientes de decisión humana.
