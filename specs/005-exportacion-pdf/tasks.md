---
description: 'Task list for Exportación PDF (SPEC-005)'
---

# Tasks: Exportación PDF

**Feature**: `005-exportacion-pdf`

**Branch**: `005-exportacion-pdf`

**Date**: 2026-10-23

**Input**: Documentos de diseño de `/specs/005-exportacion-pdf/`: [spec.md](./spec.md), [plan.md](./plan.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/http-contracts.md](./contracts/http-contracts.md) y [quickstart.md](./quickstart.md).

**Estado**: Tareas planificadas — 2026-10-23; **0/19 tareas completadas**; todas pendientes. La fase Tasks no ejecutó comandos ni pruebas, no modificó código y no declara aceptación funcional ni cierre. La aceptación final, el merge a `main` y cualquier push requieren aprobación humana expresa.

**Aprobación humana**: Según el encargo autorizado, SPEC-005 clarificada y Plan aprobado por el responsable el **2026-10-23**, incluidos el renderizador Dompdf 3.1 + Blade A4, las dependencias `dompdf/dompdf:^3.1` (runtime) y `smalot/pdfparser:^2.13` (dev), la ruta `GET /cv/pdf` (`cv.pdf`), el nombre `cv-{slug}.pdf` con fallback `cv.pdf`, la presentación A4 / 18 mm / DejaVu Sans / sin numeración y el ajuste focalizado de la prueba de rutas de SPEC-004. La aprobación del Plan no equivale a aceptación funcional.

**Prerequisites**:

- SPEC-005 clarificada (C1–C10) y Plan aprobado según el encargo; Tasks autorizado únicamente para redactar este documento. Implement no está iniciado.
- Rama `005-exportacion-pdf` coherente con `specs/005-exportacion-pdf/` y `.specify/feature.json`; el encargo declara HEAD = MAIN = `df45be157284bede61aaef93c9cbc3e09b9a222d` y `check` con `BASE_READY true`. T001 revalida ese estado antes de cualquier escritura.
- `AGENTS.md`, Constitución y ejecutor local disponibles. **Gate obligatorio**: `python3 .specify/scripts/falexom-workflow.py check` antes de cada fase y antes de cada tarea que escriba archivos. Si falla, hay incoherencia de rama/SPEC, HEAD separado o trabajo ajeno en riesgo: detenerse y reportar.
- Dependencias de Composer aprobadas. No se crean tablas, migraciones, seeders ni factories; no se ejecutan `migrate:fresh`, `migrate:refresh` ni `db:wipe`.
- No se usan datos reales para pruebas destructivas. No hay operaciones de commit, merge, push ni despliegue en este documento.

**Tests**: **Obligatorias y proporcionales al riesgo.** Cada tarea de prueba se escribe antes de la implementación que la satisface y se ejecuta para registrar el fallo esperado, salvo cuando la prueba sea una regresión de funcionalidad ya construida; en ese caso se documenta la regresión y no se fuerza un fallo artificial. Las pruebas de PDF usan Dompdf real y `smalot/pdfparser` (dev) para extraer texto, páginas, tamaño, fuentes y anotaciones. No se ejecutaron pruebas en esta fase; los comandos de cada tarea son criterios para Implement/QA.

**Organization**: tareas agrupadas por resultado verificable y por historia de usuario. Hay **un solo Developer secuencial**; la independencia de archivos no autoriza ejecución concurrente. No se usan marcadores `[P]`. QA valida de forma independiente y no sustituye la aceptación humana.

## Format: `[ID] [Fase/Story] Descripción`

- **[ID]**: identificador secuencial `T001…T019`.
- **[Fase/Story]**: `[Setup]`, `[Base]`, `[US1]`, `[US2]`, `[US3]` o `[Validation]`.
- Cada tarea indica **archivos afectados**, **trabajo concreto**, **dependencias** y **criterio verificable de finalización**.
- Todas las tareas están pendientes: `[ ]`. No se marca ninguna como completada sin evidencia ejecutada y validación suficiente.
- Toda tarea que cree o modifique archivos comienza con `python3 .specify/scripts/falexom-workflow.py check`; si falla, se detiene y se reporta.

## Path Conventions

- **Backend**: `app/`, `routes/`, `resources/views/cv/`, `tests/Feature/`, `tests/Unit/`.
- **Frontend**: `resources/js/components/`, `resources/js/components/__tests__/`.
- **Especificación**: `specs/005-exportacion-pdf/`.
- Aplicación única Laravel 13 + Inertia/React; no se crea una API JSON separada ni un proyecto frontend independiente.
- Directorio temporal de Dompdf: `storage/app/private/cv-pdf/{uuid}`; es efímero, se elimina en `finally` y no se versiona.
- No hay migraciones ni cambios de esquema en SPEC-005.

---

## Phase 1: Preparación

**Purpose**: confirmar la base autorizada e instalar las dos dependencias aprobadas, sin tocar el esquema ni el producto.

**Gate obligatorio de fase**: ejecutar `python3 .specify/scripts/falexom-workflow.py check` antes de T001 y antes de T002. Si falla, detener y reportar.

- [ ] T001 [Setup] **Archivos**: ninguno (no escribe). **Trabajo**: ejecutar `python3 .specify/scripts/falexom-workflow.py check`; confirmar rama `005-exportacion-pdf`, `FEATURE_DIR` (`specs/005-exportacion-pdf/`), HEAD = MAIN = `df45be157284bede61aaef93c9cbc3e09b9a222d` y `BASE_READY true`. Si hay discrepancia, HEAD separado o trabajo ajeno en riesgo, detenerse y reportar. **Depende de**: ninguna; es el primer gate. **Criterio**: salida del gate registrada; sin escrituras; base coherente confirmada.

- [ ] T002 [Setup] **Archivos**: `composer.json` y `composer.lock` (modificados); no se crean migraciones ni dependencias JS. **Trabajo**: ejecutar exactamente `composer require dompdf/dompdf:^3.1` y `composer require --dev smalot/pdfparser:^2.13`; verificar que `php -m` incluye `dom`, `mbstring`, `gd`, `zlib`, `iconv`, `fileinfo` y `pdo_sqlite`; comprobar versiones con `composer show dompdf/dompdf` y `composer show smalot/pdfparser`; ejecutar `composer audit`; si hace falta contexto de esquema, ejecutar solo `php artisan migrate:status` (sin migrar). No ejecutar `migrate:fresh`, `migrate:refresh` ni `db:wipe`. **Depende de**: T001. **Criterio**: `dompdf/dompdf` 3.1.x en runtime y `smalot/pdfparser` 2.13.x en dev; `composer.lock` actualizado; extensiones presentes; `composer audit` sin avisos bloqueantes o con aviso documentado y escalado; sin migraciones nuevas ni dependencias JS.

**Criterio de salida (pendiente de ejecución)**: dependencias aprobadas instaladas y auditadas; base y esquema sin cambios; no se inicia implementación.

---

## Phase 2: Base compartida

**Purpose**: extraer la resolución de perfil de SPEC-004 y construir los helpers puros que sostienen el nombre, el formato, los enlaces y la validación del PDF. Esta fase no crea la ruta ni la vista.

**Gate obligatorio de fase**: `python3 .specify/scripts/falexom-workflow.py check` antes de T003 y antes de cada tarea de escritura.

- [ ] T003 [Base] **Archivos**: `app/Support/CvProfileResolver.php` (nuevo) y `app/Http/Controllers/CvGeneratorController.php` (modificado). **Trabajo**: crear `CvProfileResolver` con la resolución compartida de perfiles activos ordenados por `name`, query completo (`array $query`, no `query('profile')`) y estados `no_active_profiles`, `selection_required`, `unavailable`, `ready` con `profile`/`profiles`; hacer que `CvGeneratorController@index` lo use y conserve exactamente sus props, el mapeo de `profiles`, `selected_profile_id`, `preview`, `try/catch` y `report()`. No modificar `CvPreviewContent`, `CvProfileContent`, `CvPreview.tsx` ni los props de la página. **Depende de**: T002. **Criterio**: `php artisan test tests/Feature/CvGeneratorTest.php` en verde, incluido `?profile[]=1`; el contrato de `GET /cv` no cambia; no queda lógica de selección duplicada. La suite existente es la red de seguridad; no se fuerza un fallo artificial.

- [ ] T004 [Base] **Archivos**: `app/Support/CvPdfFilename.php` (nuevo) y `tests/Unit/CvPdfFilenameTest.php` (nuevo). **Trabajo**: escribir primero la tabla de casos y ejecutarla para confirmar el fallo; implementar `CvPdfFilename::fromProfileName(?string $name): string` con `Str::ascii`, minúsculas, `[^a-z0-9]+` → `-`, colapso de guiones, recorte inicial/final, truncado a 80, eliminación de guion final y fallback `cv.pdf`. Cubrir acentos, ñ, espacios, `/`, `\`, `..`, comillas, puntos, controles, emojis, vacío, 100 caracteres, estabilidad y patrón final. **Depende de**: T003. **Criterio**: `php artisan test tests/Unit/CvPdfFilenameTest.php` en verde; el nombre cumple `^cv(-[a-z0-9]+)*\.pdf$` o es `cv.pdf`; no contiene rutas, separadores, `..`, comillas, controles ni escapes.

- [ ] T005 [Base] **Archivos**: `app/Support/CvPdfFormatter.php` (nuevo) y `tests/Unit/CvPdfFormatterTest.php` (nuevo). **Trabajo**: escribir primero las pruebas y confirmar el fallo; implementar en PHP el equivalente de `resources/js/lib/dates.ts` y `resources/js/lib/skill-categories.ts`: meses `ene…dic`, año solo, rango con `–`, `actual`, `en curso`, etiquetas de categoría y fallback. No duplicar reglas de contenido ni de orden. **Depende de**: T004. **Criterio**: `php artisan test tests/Unit/CvPdfFormatterTest.php` en verde; fechas, rangos, estados y categorías coinciden con los casos de la vista previa; valores nulos o vacíos no generan texto inventado.

- [ ] T006 [Base] **Archivos**: `app/Support/CvPdfLink.php` (nuevo) y `tests/Unit/CvPdfLinkTest.php` (nuevo). **Trabajo**: escribir primero las pruebas y confirmar el fallo; implementar la allowlist `http`, `https`, `mailto` y `tel`, la validación de URL/correo/teléfono y el retorno de un `href` seguro o `null`. Rechazar `javascript:`, `data:`, `file:`, `vbscript:`, `phar:`, rutas relativas, `//`, credenciales embebidas y caracteres de control; el teléfono se normaliza a dígitos y `+`; un enlace inválido conserva su texto visible sin activarse. **Depende de**: T005. **Criterio**: `php artisan test tests/Unit/CvPdfLinkTest.php` en verde; destinos válidos intactos; esquemas y valores inseguros sin enlace; sin enlaces internos de corrección en esta clase.

- [ ] T007 [Base] **Archivos**: `app/Support/CvPdfValidator.php` (nuevo) y `tests/Unit/CvPdfValidatorTest.php` (nuevo). **Trabajo**: escribir primero las pruebas y confirmar el fallo; implementar `CvPdfValidator::isValid(string $pdf): bool` con: no vacío, `%PDF-` y `%%EOF` mediante `PdfFile::looksLikePdf`, presencia de `/Type /Pages` y al menos un `/Type /Page`, y `startxref` en el tramo final. No usar `smalot/pdfparser` en runtime ni modificar `app/Support/PdfFile.php`. **Depende de**: T006. **Criterio**: `php artisan test tests/Unit/CvPdfValidatorTest.php` en verde con PDF válido, vacío, truncado, sin `%%EOF`, sin páginas y sin `startxref`; un PDF inválido se rechaza antes de entregarse.

**Criterio de salida (pendiente de ejecución)**: `CvGeneratorTest` y las cuatro suites Unit en verde; `CvPdfFilename`, `CvPdfFormatter`, `CvPdfLink` y `CvPdfValidator` disponibles; `CvPreviewContent` y `CvPreview.tsx` sin cambios.

---

## Phase 3: User Story 1 - Exportar un PDF fiel del perfil seleccionado (Priority: P1) 🎯

**Goal**: descargar un PDF A4 de una columna, generado desde la misma composición de SPEC-004, con nombre determinista y válido, para el perfil activo en `ready` con datos.

**Why / Independent Test**: es el núcleo de la capacidad y completa el paso 13 del MVP. Con un Perfil Maestro y un perfil activo con datos personales, título/resumen propios, elementos incluidos y excluidos y otro perfil distinto, abrir la vista previa, pulsar «Exportar PDF» y comprobar que se descarga un archivo con nombre seguro, que abre, contiene exactamente lo incluido en el mismo orden y no persiste nada.

**Gate obligatorio de fase**: `python3 .specify/scripts/falexom-workflow.py check` antes de T008 y antes de cada tarea de escritura.

- [ ] T008 [US1] **Archivos**: `tests/Feature/CvPdfExportTest.php` (nuevo). **Trabajo**: escribir primero el contrato HTTP de US1 y ejecutarlo para confirmar el fallo por ausencia de `cv.pdf`/controlador/renderizador. Cubrir: `Route::has('cv.pdf')`; perfil activo con datos → `200`, `Content-Type: application/pdf`, `Content-Disposition: attachment` con `cv-{slug}.pdf`, `Content-Length` correcto, cuerpo no vacío, `Cache-Control: no-store, private`, `Pragma: no-cache` y `X-Content-Type-Options: nosniff`; PDF válido con cabecera/trailer, páginas, A4 vertical, fuentes embebidas y texto real; fidelidad con las cinco clases, incluidos/excluidos, relevancias, título/resumen propios, datos heredados, otro perfil distinto, sin relevancia/estado/nombre del perfil/`source_links` y sin fallback al Maestro. Usar Dompdf real y `smalot/pdfparser`; normalizar espacios y saltos del texto extraído y comprobar el orden con `strpos`. Usar helpers con nombres únicos (`cvPdfMaster`, `cvPdfExport`, etc.) para no colisionar con `cvGeneratorMaster`/`pdfUpload`. **Depende de**: T007. **Criterio**: archivo de prueba escrito y fallo esperado registrado; no se implementa aún la ruta ni el renderizador.

- [ ] T009 [US1] **Archivos**: `app/Support/CvPdfRenderer.php` (nuevo), `resources/views/cv/pdf.blade.php` (nuevo), `app/Http/Controllers/CvPdfController.php` (nuevo) y `routes/web.php` (modificado). **Trabajo**: implementar el camino de descarga completo. `CvPdfRenderer::render(array $preview): string` construye el view data con `personal_data`, `professional_title`, `summary`, `content` y `has_data`, omite `source_links` y no recibe el nombre del perfil; renderiza `cv.pdf` con Dompdf en memoria (`isRemoteEnabled=false`, `isPhpEnabled=false`, `isJavascriptEnabled=false`, `chroot`/`tempDir`/`fontCache` en `storage/app/private/cv-pdf/{uuid}`, `defaultFont=DejaVu Sans`, A4/portrait, subsetting, `defaultMediaType=print`) y limpia el directorio en `finally`; si la limpieza falla, aborta la entrega. La Blade usa HTML/CSS 2.1, una columna, 18 mm, DejaVu Sans, jerarquía de la vista previa, orden fijo de secciones, omisión de secciones/campos vacíos, `{{ }}` para escapar, `{!! nl2br(e($text)) !!}` para multilínea, `CvPdfFormatter` y `CvPdfLink`; sin `source_links`, nombre del perfil, controles, numeración, cabeceras/pies, imágenes, JS, formularios, `iframe`, `object`, `embed`, recursos remotos, flex, grid, `float`, posiciones ni tablas de maquetación. `CvPdfController` recibe por constructor `CvProfileResolver`, `CvPreviewContent`, `CvPdfRenderer` y `CvPdfValidator`; resuelve, revalida perfil activo, compone, exige `has_data`, renderiza, valida y responde `200` con `Content-Disposition: attachment; filename="<CvPdfFilename>"`, `Content-Length`, `Cache-Control: no-store, private`, `Pragma: no-cache` y `nosniff`; `try/catch (Throwable)` con `report()` y redirect a `/cv` o `/cv?profile=ID` con flash `toast` de error; sin PDF parcial. Declarar `Route::get('/cv/pdf', CvPdfController::class)->name('cv.pdf')` sin route model binding. **Depende de**: T008 (prueba escrita y fallo registrado), T004, T005, T006 y T007. **Criterio**: `php artisan test tests/Feature/CvPdfExportTest.php` en verde para headers, nombre, validez y fidelidad de US1; `php artisan route:list --name=cv` muestra `GET /cv` y `GET /cv/pdf`; no se escribe el PDF en disco y `storage/app/private/cv-pdf` queda vacío tras la petición.

- [ ] T010 [US1] **Archivos**: `resources/js/components/CvGeneratorPanel.tsx` (modificado) y `resources/js/components/__tests__/CvGeneratorPanel.test.tsx` (modificado). **Trabajo**: escribir primero las pruebas de la acción y confirmar el fallo; añadir en `CvGeneratorPanel`, antes de `CvPreview` y **fuera del `article`**, un anchor real con `Button asChild` y `href="/cv/pdf?profile={selected_profile_id}"`, visible solo con `status === 'ready'`, `preview?.has_data` y `selected_profile_id !== null`. Sin `Inertia Link`, `onClick`, `target="_blank"` ni `download`; nombre accesible «Exportar PDF»; foco visible por los estilos de `Button`; ausente en `no_active_profiles`, `selection_required`, `unavailable`, `error`, `ready` sin datos, `preview` nulo o `selected_profile_id` nulo. No modificar `CvPreview.tsx`. **Depende de**: T009. **Criterio**: `npm test -- resources/js/components/__tests__/CvGeneratorPanel.test.tsx` en verde con visibilidad, `href`, ubicación fuera de la hoja, ausencia de `target`/`download` y foco; `npm run types:check` en verde; `CvPreview.test.tsx` sigue en verde sin cambios.

**Criterio de salida (pendiente de ejecución)**: US1 quedará demostrable de extremo a extremo cuando el PDF sea descargable, válido, fiel y con nombre determinista, y la acción sea visible solo cuando corresponda y fuera de la hoja.

---

## Phase 4: User Story 2 - Conservar contenido, paginación y enlaces en un CV extenso (Priority: P2)

**Goal**: exportar CV extensos en varias páginas sin truncar contenido ni perder enlaces, con saltos naturales y textos largos/acentos correctos.

**Why / Independent Test**: los CV reales suelen superar una página. Con un CV extenso, textos largos, acentos, URL largas y todos los tipos de enlace externo, comprobar que el PDF tiene varias páginas, todo el contenido aparece completo y una sola vez, no hay páginas en blanco evitables, los enlaces externos conservan destino y no aparece ningún enlace interno de corrección.

**Gate obligatorio de fase**: `python3 .specify/scripts/falexom-workflow.py check` antes de T011 y antes de cada tarea de escritura.

- [ ] T011 [US2] **Archivos**: `resources/views/cv/pdf.blade.php` (modificado si hace falta) y `tests/Feature/CvPdfExportTest.php` (modificado). **Trabajo**: escribir primero las pruebas de multipágina y textos largos; crear un perfil con suficientes experiencias/entradas para superar una página; comprobar páginas > 1, que todo el contenido incluido aparece completo y una sola vez, que no hay páginas sin texto, que la repetición produce páginas y texto estables y que acentos, eñes, signos, guiones y cadenas sin espacios se conservan y ajustan sin desbordar. Ajustar la Blade con CSS 2.1: `page-break-after: avoid` en encabezados de sección y títulos de elemento, sin `page-break-inside: avoid` en elementos largos, sin `page-break-after: always`, sin alturas fijas, sin `truncate`/`line-clamp`, `word-wrap: break-word` y sin numeración de página. Si Dompdf no respeta `page-break-after: avoid`, documentar la evidencia y evaluar agrupar encabezado + primer elemento corto con `page-break-inside: avoid`; no agrupar elementos largos. **Depende de**: T010. **Criterio**: `php artisan test tests/Feature/CvPdfExportTest.php` en verde para multipágina, estabilidad y textos largos/acentos; sin truncamientos, duplicados ni páginas en blanco evitables en la prueba automatizable. La revisión visual de cortes, solapamientos y encabezados huérfanos queda en T019.

- [ ] T012 [US2] **Archivos**: `tests/Feature/CvPdfExportTest.php` (modificado) y `resources/views/cv/pdf.blade.php` (solo si la integración lo exige). **Trabajo**: escribir primero las pruebas de enlaces y anotaciones con `smalot/pdfparser`: teléfono visible y, si se emite, `tel:` correcto; correo `mailto:`; LinkedIn, GitHub, sitio web, enlaces adicionales, validación de certificación y URL de proyecto como `http(s)`; mismo texto visible y destino; sin enlaces internos de corrección (`/profiles`, `/profile`, `/experiences`, `/education`, `/certifications`, `/skills`, `/projects`); una URL larga usa etiqueta corta; un valor no seguro conserva el texto pero no genera anotación. Verificar que `CvPdfLink` está integrado en la Blade. Si la implementación de T009 ya satisface las pruebas, registrar la regresión y no introducir cambios artificiales. **Depende de**: T011 y T006. **Criterio**: `php artisan test tests/Feature/CvPdfExportTest.php` en verde para anotaciones, textos visibles, enlaces externos y ausencia de enlaces internos; URL insegura sin anotación.

**Criterio de salida (pendiente de ejecución)**: US2 quedará demostrable cuando el CV extenso sea multipágina, el texto esté completo y los enlaces externos sean clicables, sin enlaces internos ni truncamientos automatizables.

---

## Phase 5: User Story 3 - Exportar solo cuando corresponde y fallar sin archivos corruptos (Priority: P3)

**Goal**: no ofrecer ni producir PDF en estados sin datos o sin perfil válido; ante fallos de generación o validación, informar fuera del documento sin entregar un archivo parcial y permitir reintentar.

**Why / Independent Test**: protege la confianza y evita PDF vacíos o corruptos. Comprobar la sección sin perfiles activos, sin selección, con perfil sin datos, con perfil desactivado entre la vista y la exportación y ante fallos simulados de renderizador/validador; verificar que no se descarga archivo, que el mensaje aparece fuera del documento y que el reintento funciona.

**Gate obligatorio de fase**: `python3 .specify/scripts/falexom-workflow.py check` antes de T013 y antes de cada tarea de escritura.

- [ ] T013 [US3] **Archivos**: `tests/Feature/CvPdfExportTest.php` (modificado) y `app/Http/Controllers/CvPdfController.php` (modificado si hace falta). **Trabajo**: escribir primero las pruebas de disponibilidad y ejecutarlas: sin perfiles activos → `302` a `/cv` + toast de error «No hay perfiles activos para exportar.»; sin `profile` o `profile=` → `302` a `/cv` + «Seleccioná un perfil para exportar el PDF.»; query inválido (texto, cero, negativo, espacios, array `?profile[]=1`) → `302` a `/cv` + toast; perfil inactivo/inexistente → `302` a `/cv?profile=ID` + «El perfil seleccionado no está disponible. Elegí un perfil activo.»; perfil activo con `has_data=false` (incluido Maestro sin datos) → `302` a `/cv?profile=ID` + «Este perfil no tiene datos para exportar.»; perfil desactivado/eliminado entre vista y exportación; acceso directo sin PDF. Verificar que `?profile[]=1` no se convierte en `status=error` ni en 500, que no hay `Content-Type: application/pdf` y que no se genera archivo. La ausencia visual de la acción ya se cubre en T010; no duplicar esa prueba. **Depende de**: T012. **Criterio**: `php artisan test tests/Feature/CvPdfExportTest.php` en verde para los estados; redirects y mensajes fuera del documento; sin PDF vacío ni 404 para perfil no disponible.

- [ ] T014 [US3] **Archivos**: `tests/Feature/CvPdfExportTest.php` (modificado) y `app/Http/Controllers/CvPdfController.php` (modificado si hace falta). **Trabajo**: escribir primero las pruebas de fallo controlado y ejecutarlas: doble de `CvPdfRenderer` que lanza una excepción; doble que devuelve un PDF inválido/truncado; doble de `CvPdfValidator` que devuelve `false`; comprobar `302` con toast «No se pudo generar el PDF. Volvé a intentar.», ausencia de cuerpo PDF, ausencia de archivo parcial y que el mensaje de la excepción no se filtra. Verificar que el reintento vuelve a solicitar la exportación sin conservar una copia; la selección se conserva cuando el perfil sigue siendo válido. No añadir infraestructura de pruebas fuera de los dobles del contenedor. **Depende de**: T013. **Criterio**: `php artisan test tests/Feature/CvPdfExportTest.php` en verde para fallos de renderizador y validador; sin descarga parcial/corrupta; sin stack trace ni datos internos; reintento posible.

- [ ] T015 [US3] **Archivos**: `tests/Feature/CvPdfExportTest.php` (modificado); `resources/views/cv/pdf.blade.php` solo si una prueba revela un recurso remoto (no se espera). **Trabajo**: escribir primero las pruebas de no persistencia, solo GET y seguridad del PDF y ejecutarlas: conteos y `updated_at` de Maestro/perfiles/pivotes/documentos sin cambios; directorio `storage/app/private/cv-pdf` vacío tras éxito y tras fallo; sin PDF nuevo en `public`, `storage/app` ni el repositorio; sin historial, versión, registro ni URL posterior recuperable; `POST`/`PUT`/`PATCH`/`DELETE /cv/pdf` → `405`; el PDF real no contiene `/Encrypt`, `/JavaScript`, `/AcroForm`, `/EmbeddedFile` ni `/Launch`; los metadatos no incluyen relevancia, estado, rutas internas, identificadores de configuración, nombre del perfil ni datos de otro perfil; la Blade renderizada no contiene `img`, `link`, `script`, `iframe`, `object`, `embed`, `form` ni `url(http...)`, y los `http(s)` solo aparecen en `<a href>` de enlaces del CV. Crear el Maestro antes de medir para no atribuir al PDF el `firstOrCreate` heredado de `Profile::current()`. Verificar la configuración del renderizador (`isRemoteEnabled=false`, `isPhpEnabled=false`, `isJavascriptEnabled=false`). La comprobación real sin red queda en T019. **Depende de**: T014. **Criterio**: `php artisan test tests/Feature/CvPdfExportTest.php` en verde para no persistencia, 405, seguridad y metadata; sin recursos remotos; sin archivos temporales recuperables.

**Criterio de salida (pendiente de ejecución)**: US3 quedará demostrable cuando la exportación solo se produzca con perfil activo y datos, los errores se informen fuera del documento y no haya archivos parciales, persistencia ni métodos de escritura.

---

## Phase 6: Regresión y validación

**Purpose**: ajustar de forma focalizada la regresión de rutas de SPEC-004, ejecutar las suites completas, someter la capacidad a QA independiente y completar la validación manual de `quickstart.md`.

**Gate obligatorio de fase**: `python3 .specify/scripts/falexom-workflow.py check` antes de T016 y antes de cada escritura.

- [ ] T016 [Validation] **Archivos**: `tests/Feature/CvGeneratorTest.php` (modificado). **Trabajo**: actualizar solo la última prueba de rutas de SPEC-004 para reconocer `cv.pdf`: afirmar que `cv.index` y `cv.pdf` existen; mantener prohibidas `cv.export`, `cv.print`, `cv.download`, `cv.template`, `cv.store`, `cv.update`, `cv.destroy`, `cv.regenerate` y `cv.reorder`; recolectar `cv` y `cv/pdf`, exigir exactamente dos rutas y que ambas incluyan GET y no admitan POST/PUT/PATCH/DELETE; comprobar `405` en `POST/PUT/PATCH/DELETE` para ambas. No cambiar ninguna expectativa sobre la hoja, su composición, su solo lectura ni sus datos; no modificar `spec.md` de SPEC-004. **Depende de**: T015. **Criterio**: `php artisan test tests/Feature/CvGeneratorTest.php` en verde; el inventario de rutas `cv` es exactamente `GET /cv` y `GET /cv/pdf`; siguen prohibidas las rutas de escritura, impresión, descarga alternativa, plantilla, historial y reordenamiento.

- [ ] T017 [Validation] **Archivos**: ninguno (solo ejecución y registro de evidencia en el informe de Implement/QA; no se marca completado en este documento sin resultados). **Trabajo**: ejecutar, en este orden, `php artisan test`, `npm test`, `npm run types:check`, `npm run check` y `composer lint:check`; si se usa `composer test`, no sustituye a los comandos anteriores. Registrar comando, entorno y resultado; distinguir prueba ejecutada, pendiente y bloqueada. **Depende de**: T016. **Criterio**: todas las suites y comprobaciones estáticas en verde; cualquier fallo se documenta y se devuelve a la tarea correspondiente; no se declara cierre por esta ejecución.

- [ ] T018 [Validation] **Archivos**: ninguno (QA no modifica código ni pruebas). **Trabajo**: QA independiente revisa la implementación contra `spec.md`, `plan.md` y `contracts/http-contracts.md`; ejecuta por su cuenta las suites focalizadas y comprueba headers, validez, fidelidad, multipágina, enlaces, estados, no persistencia, solo GET y ausencia de alcance extra; no se apoya únicamente en las declaraciones del Developer. **Depende de**: T017. **Criterio**: informe de QA con PASS/FAIL y evidencia por criterio; si hay FAIL, se devuelve a Implement y no se avanza a validación manual. QA no reemplaza la aceptación humana ni autoriza merge/push.

- [ ] T019 [Validation] **Archivos**: `specs/005-exportacion-pdf/quickstart.md` (solo lectura; no se modifica). **Trabajo**: ejecutar la validación manual de `quickstart.md` con datos de prueba: abrir el PDF en al menos dos lectores estándar; revisar A4 vertical, una columna, márgenes, jerarquía, tipografía, cortes, solapamientos, encabezados huérfanos y páginas en blanco; comprobar foco visible y operación con teclado de «Exportar PDF»; exportar y abrir el PDF sin red; verificar que no queda PDF, historial, versión ni archivo temporal; repetir con «Analista» y «Programador»; comprobar enlaces externos, `tel:` cuando el lector lo admita, ausencia de enlaces internos, comportamiento de un enlace no seguro y ausencia de solapamientos con el texto. Registrar fecha, entorno y resultado de cada escenario. **Depende de**: T018. **Criterio**: escenarios manuales ejecutados y registrados; si alguno no se ejecuta, la tarea permanece pendiente y no se declara aceptación funcional. La aceptación final corresponde al responsable.

**Criterio de salida (pendiente de ejecución)**: regresión de SPEC-004 ajustada y suites automatizadas verdes; QA independiente y validación manual pendientes de ejecución real. La capacidad no se declara cerrada ni aceptada en esta fase.

---

## Dependencies & Execution Order

- **Preparación (T001–T002)**: bloquea todo; sin dependencias aprobadas no se escribe código.
- **Base compartida (T003–T007)**: depende de T002; bloquea US1. T003 conserva el contrato de SPEC-004; T004–T007 construyen los helpers y sus Unit.
- **US1 (T008–T010)**: depende de T007; T008 es test-first, T009 implementa el camino de descarga y T010 la acción UI. Bloquea US2 y US3.
- **US2 (T011–T012)**: depende de T010; primero paginación/textos largos y luego enlaces/anotaciones.
- **US3 (T013–T015)**: depende de T012; primero disponibilidad, luego fallos y luego no persistencia/seguridad/solo GET.
- **Validación (T016–T019)**: T016 depende de T015; T017 de T016; T018 de T017; T019 de T018. La validación manual no sustituye a QA ni a la aceptación humana.

**Secuencia**: un solo Developer ejecuta las tareas en orden. No se declaran oportunidades paralelas `[P]`; la independencia lógica de archivos no autoriza concurrencia ni cambia las dependencias.

---

## Trazabilidad US / FR / SC → tareas

| Historia / resultado | Requisitos y criterios | Tareas |
| --- | --- | --- |
| Preparación y base compartida | FR-013; SC-003, SC-007 | T001–T007 |
| US1 — PDF fiel descargable | FR-001, FR-002, FR-004–FR-006, FR-008, FR-011, FR-012, FR-016; SC-001, SC-003, SC-005, SC-008 | T003–T010, T015, T016, T019 |
| US2 — Multipágina y enlaces | FR-007–FR-010; SC-002, SC-004 | T005, T006, T011, T012, T015, T019 |
| US3 — Disponibilidad, errores y no persistencia | FR-001, FR-003, FR-010, FR-012–FR-015, FR-017; SC-006, SC-007 | T003, T013–T016, T019 |
| Regresión y validación | FR-016, FR-017; SC-008 | T016–T019 |

---

## Risks and Known Limitations

- **Dompdf y CSS 2.1**: no soporta flex/grid ni reproduce Tailwind con exactitud. La fidelidad es de contenido, orden y jerarquía equivalente, **no pixel-perfect**; la revisión visual queda pendiente en T019.
- **Paginación**: los saltos son naturales y `page-break-after: avoid` puede tener soporte parcial. Los encabezados huérfanos, cortes y páginas en blanco se revisan manualmente; si aparece un defecto, se evalúa el fallback de agrupar encabezado + primer elemento corto, sin agrupar elementos largos.
- **Dependencias nuevas**: `dompdf/dompdf:^3.1` es runtime y `smalot/pdfparser:^2.13` es dev-only; el lock y `composer audit` requieren revisión. Licencias observadas: LGPL-2.1 y LGPL-3.0. `smalot/pdfparser` tiene mantenimiento limitado y no se usa en producción.
- **Validación estructural**: `CvPdfValidator` comprueba estructura, no legibilidad ni seguridad completas; el contenido, tamaño, fuentes, anotaciones y ausencia de JS/formularios/adjuntos se verifican con parser en pruebas y con lectores en T019. No se usan búsquedas de subcadenas en runtime para no rechazar URLs legítimas.
- **Validación manual pendiente**: apertura en dos lectores, revisión visual de maquetación, foco/teclado y funcionamiento sin red no pueden sustituirse por pruebas automatizadas. QA independiente y aceptación humana también están pendientes.
- **Excepción heredada**: `CvProfileContent::personalData()` usa `Profile::current()` (`firstOrCreate`) y puede crear el singleton vacío del Maestro en una base pristine; es comportamiento de SPEC-001, no una copia del CV. Las pruebas crean el Maestro antes de medir persistencia.
- **Nombre de archivo**: dos perfiles con el mismo nombre comparten `cv-{slug}.pdf`; no se añade ID ni sufijo, conforme a C5/FR-011.
- **Sin numeración de página** por decisión de Plan; no se añaden cabeceras, pies, logos, marcas de agua ni fechas visibles.

---

## Scope Boundaries

No forman parte de estas tareas: anexos, concatenación de PDFs, OCR, firma digital, publicación, correo, compartición, URL pública, impresión integrada, edición del PDF, historial, versionado, ATS, adaptación a ofertas, IA, plantillas múltiples, personalización gráfica, migraciones, cambios de modelo o cambios en la vista previa de SPEC-004. Tampoco se incluyen commit, merge, push ni despliegue.
