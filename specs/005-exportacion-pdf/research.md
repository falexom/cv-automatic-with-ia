# Fase 0 — Investigación y decisiones técnicas: Exportación PDF

**Feature**: `005-exportacion-pdf` | **Fecha**: 2026-10-23
**Referencias**: [spec.md](./spec.md) · [plan.md](./plan.md) · [data-model.md](./data-model.md) · [contracts/http-contracts.md](./contracts/http-contracts.md) · [SPEC-004](../004-generador-de-cv/plan.md)

> **Corrección 2026-10-23 — decisión vigente D12.** El responsable aprobó sustituir Dompdf + Blade por **Browsershot + Chrome headless con SSR de `Cv/Print`**. La comparación y las decisiones D1–D5 de este documento describen la propuesta inicial y quedan **superseded**; se conservan como histórico. D6–D10 se mantienen. La decisión vigente está en [D12](#d12--browsershot--ssr-de-cvprint-seleccionada).

## Alcance y método

Este documento decide el mecanismo local de renderizado, la forma de la ruta y la descarga, el nombre de archivo, la validación previa y la estrategia de pruebas. Respeta las decisiones funcionales cerradas C1–C10 de la spec y no reabre SPEC-004.

**Evidencia**: se contrastó la documentación oficial de Dompdf y su ficha de Packagist, y la ficha de `smalot/pdfparser`, consultadas el 2026-10-23. Tras la corrección se contrastaron Browsershot 5.4.1, Puppeteer 25.16.0 y el soporte SSR de Inertia 3 instalado. **No se instaló ninguna dependencia ni se ejecutó código o pruebas en esta fase.** Las versiones son la propuesta de Plan; el lock de Composer/npm las fijará en Implement. Las afirmaciones de comportamiento son documentales y deberán confirmarse con las pruebas de Implement/QA.

## Decisión resumida (propuesta inicial; superseded por D12)

| Tema         | Decisión                                                                                                                                 |
| ------------ | ---------------------------------------------------------------------------------------------------------------------------------------- |
| Renderizador | **Dompdf 3.1.x** (`dompdf/dompdf:^3.1`), PHP puro, sin binario externo.                                                                  |
| Presentación | **Vista Blade de impresión** `resources/views/cv/pdf.blade.php`, A4, una columna, CSS 2.1, DejaVu Sans embebida.                         |
| Composición  | `CvPreviewContent::build()` sin cambios; el PDF consume exactamente el mismo payload que la vista previa.                                |
| Ruta         | `GET /cv/pdf` (`cv.pdf`), controlador `CvPdfController` invocable.                                                                       |
| Selección    | `CvProfileResolver` compartido por la vista previa y la exportación; sin duplicar la lógica de perfil activo/query.                      |
| Nombre       | `cv-{base-normalizada}.pdf` con `Str::ascii`, minúsculas, guiones, truncado a 80 y fallback `cv.pdf`.                                    |
| Validación   | Estructural en runtime (`%PDF-`, `%%EOF`, páginas, `startxref`) y de contenido/seguridad en pruebas con `smalot/pdfparser` (dev).        |
| Paginación   | Saltos naturales de CSS; sin numeración de página.                                                                                       |
| Pruebas      | Feature con PDF real, Unit de nombre/formato/enlaces/validador y Vitest de la acción; apertura en dos lectores y revisión visual manual. |

## Comparación de alternativas

Criterios: generación local/offline, ausencia de binarios y procesos externos, fidelidad suficiente para una columna A4, fuentes embebidas, enlaces, testabilidad, dependencia y portabilidad, seguridad y complejidad.

| Criterio                 | Dompdf (PHP/HTML-CSS)                                             | Navegador headless (Browsershot/Chrome)                                 | Generación cliente (`@react-pdf`, jsPDF)                     | Otra librería PHP (mPDF/TCPDF)       |
| ------------------------ | ----------------------------------------------------------------- | ----------------------------------------------------------------------- | ------------------------------------------------------------ | ------------------------------------ |
| Local y sin red          | Sí                                                                | Sí, pero depende del binario                                            | Sí, en el navegador                                          | Sí                                   |
| Binario/proceso externo  | No                                                                | **Sí** (Chrome/Chromium, proceso, sandbox)                              | No                                                           | No                                   |
| Fidelidad visual         | Buena para CSS 2.1; no Tailwind completo                          | Muy alta                                                                | Alta si se duplica la plantilla                              | Media/baja según librería            |
| Fuentes embebidas        | DejaVu preinstalada y subsetting                                  | Las del navegador/sistema                                               | Requiere cargar fuentes                                      | Variable                             |
| Enlaces                  | Anotaciones a partir de `<a href>`                                | Muy buena                                                               | Depende de la librería                                       | Variable                             |
| Testabilidad             | PDF real verificable en PHP; parser dev                           | Proceso lento, dependiente del entorno                                  | Verificación en cliente, no en servidor                      | Similar a Dompdf                     |
| Dependencia/portabilidad | Una dependencia PHP; funciona en el entorno actual                | Node + Chromium + Puppeteer/Browsershot; ruta de Chrome.app no portable | Dependencia JS y duplicación de plantilla                    | Dependencia PHP, a menudo más pesada |
| Seguridad                | Opciones restrictivas; requiere cuidar `chroot`, remoto, PHP y JS | Superficie de proceso y sandbox del navegador                           | Datos y lógica en cliente; sin validación previa en servidor | Similar                              |
| Complejidad              | Baja/media                                                        | Alta                                                                    | Alta por duplicación                                         | Media/alta                           |

### 1. Dompdf — seleccionada en la propuesta inicial (superseded por D12)

**Qué aporta**

- Convierte HTML/CSS a PDF en PHP, sin invocar un binario ni un servicio externo.
- Es compatible con la mayor parte de CSS 2.1 e incluye reglas `@page`; no soporta flexbox ni grid, que no son necesarios para una sola columna.
- Incluye las fuentes DejaVu (Sans, Serif y Mono) y embebe/subsetea las fuentes referenciadas; DejaVu Sans cubre acentos, eñes, guiones y signos habituales del contenido.
- Los `<a href>` se convierten en anotaciones de enlace; el adaptador CPDF registra el destino como URI. Esto permite `mailto:`, `tel:` y `http(s)`.
- Opciones relevantes verificadas en la documentación: `isRemoteEnabled` y `isPhpEnabled` son `false` por defecto; `isJavascriptEnabled` es `true` por defecto y **debe desactivarse explícitamente**; `chroot` restringe los archivos locales; `defaultFont`, `defaultPaperSize`, `tempDir` y `fontCache` son configurables.
- El PDF se obtiene como cadena en memoria con `output()`, sin escribir el documento en disco.

**Límites y riesgos**

- No reproduce con exactitud el CSS de Tailwind ni el layout del navegador. La spec exige jerarquía equivalente, no fidelidad pixel-perfect; la vista Blade se diseña con CSS 2.1.
- El soporte de `page-break-inside` es parcial. Se usará `page-break-after: avoid` en encabezados y se revisará manualmente; no se usarán tablas para maquetar porque sus celdas no paginan.
- Las fuentes y su caché requieren un directorio escribible. Se usará un directorio por petición y se eliminará en `finally`.
- Dompdf tiene avisos de seguridad históricos. Se fija la última versión estable, se desactivan remoto/PHP/JS, se restringe `chroot` y no se inserta HTML del usuario.
- La extracción de texto de fuentes subseteadas puede requerir el mapa `ToUnicode`; se verificará con acentos en las pruebas.

**Por qué cumple**: es la opción local más simple que produce un PDF A4 de una columna, con fuentes embebidas y enlaces, sin binarios externos, sin red y con verificación en servidor.

### 2. Navegador headless (Browsershot/Chrome) — seleccionada en la corrección (D12)

> **Histórico**: los motivos de descarte siguientes corresponden a la propuesta inicial. La corrección D12 los revisa porque la fidelidad visual pasó a ser un requisito rechazado, no un extra.

**Ventaja**: fidelidad visual alta si se reutilizara el HTML de la vista previa.

**Motivos de descarte en la propuesta inicial**:

- Requiere Chrome/Chromium y un proceso externo. En el entorno hay `/Applications/Google Chrome.app`, pero no `chromium`/`google-chrome` en `PATH`; depender de una ruta de macOS no es portable.
- Añade Browsershot/Puppeteer/Node y un ciclo de vida de proceso, timeouts, sandbox y manejo de errores difíciles de probar.
- La generación local sería más pesada y con más modos de fallo que Dompdf para una sola plantilla.
- La spec no exige fidelidad pixel-perfect; la complejidad no está justificada (Principios XII–XIII).

### 3. Generación cliente (`@react-pdf/renderer`, jsPDF) — descartada

**Ventaja**: reutilizaría el entorno React.

**Motivos de descarte**:

- Duplicaría la plantilla y, en la práctica, la composición o parte de ella en el cliente, creando una segunda fuente de presentación y riesgo de divergencia con `CvPreview`.
- Las fuentes, los enlaces y la paginación se gestionan de forma distinta; el PDF no podría validarse en el servidor antes de entregarse.
- Añade una dependencia JS y pruebas de otro motor; no es más simple que una vista Blade.

### 4. Otras librerías PHP (mPDF, TCPDF) — descartadas

- mPDF es más pesado y complejo para una sola plantilla A4.
- TCPDF tiene soporte HTML/CSS más limitado para la jerarquía requerida.
- Dompdf cubre el caso con menos superficie y dependencias.

## Decisiones técnicas

### D1 — Renderizador y versión

- Usar `dompdf/dompdf:^3.1` (última estable observada: 3.1.6, 2026-07-20). Requiere PHP `^7.1 || ^8.0`, `ext-dom`, `ext-mbstring`, `dompdf/php-font-lib`, `dompdf/php-svg-lib` y `masterminds/html5`. El entorno cumple PHP 8.5.5 y las extensiones.
- Comando propuesto, **no ejecutado**: `composer require dompdf/dompdf:^3.1`.
- No se usa `barryvdh/laravel-dompdf`: añade un wrapper y configuración que no aportan nada frente a instanciar Dompdf directamente en una clase de soporte.
- Licencias observadas en Packagist: Dompdf LGPL-2.1; `smalot/pdfparser` LGPL-3.0 (dev). Se registran para la revisión humana; este Plan no hace análisis legal.

### D2 — Presentación: una sola plantilla, representación A4

- La composición y las reglas siguen en `CvPreviewContent`/SPEC-004. La vista Blade es la **representación A4 de la única plantilla**, no un segundo diseño seleccionable ni una plantilla alternativa.
- La Blade recibe el payload de contenido y no recompone, filtra, ordena ni selecciona. El renderizador omite `source_links` antes de pasarlo a la vista; la Blade solo presenta los campos ya incluidos.
- No se modifica `CvPreview.tsx`; la acción de exportación vive fuera de la hoja.
- El nombre del perfil no se pasa a la vista: solo se usa para el nombre del archivo. Así se evita que se imprima o aparezca en metadatos.

### D3 — CSS, tipografía, márgenes y paginación

- `@page { size: A4; margin: 18mm; }`; `Dompdf::setPaper('A4', 'portrait')`.
- Una columna, sin flex/grid, sin `float`, sin posiciones absolutas/fijas y sin tablas de maquetación.
- DejaVu Sans: cuerpo 10,5 pt, interlineado 1,4; nombre 22 pt; título 12 pt; encabezados de sección 12,5 pt; metadatos 9,5 pt. Solo pesos normal y negrita, presentes en DejaVu.
- Enlaces subrayados y con color oscuro; la información no depende solo del color.
- `page-break-after: avoid` en encabezados de sección y títulos de elemento; sin `page-break-inside: avoid` en elementos largos para permitir su continuación. Si no se respeta, se evalúa agrupar encabezado + primer elemento corto con `page-break-inside: avoid`; no se agrupan elementos largos.
- Sin numeración de página: no aporta información requerida, evita contenido adicional y evita depender de scripts de paginación de Dompdf. Si el usuario la pidiera, sería una decisión posterior.
- Sin cabeceras/pies, logotipos, marcas de agua, fechas visibles ni textos promocionales.

### D4 — Opciones de Dompdf y limpieza

Configuración propuesta:

```text
isRemoteEnabled        = false
isPhpEnabled           = false
isJavascriptEnabled    = false   (el valor por defecto es true; se desactiva explícitamente)
chroot                 = directorio de trabajo vacío y propio de la petición
tempDir                = directorio de trabajo propio de la petición
fontCache              = directorio de trabajo propio de la petición
defaultFont            = DejaVu Sans
defaultPaperSize       = A4
defaultPaperOrientation= portrait
isFontSubsettingEnabled= true
defaultMediaType       = print
```

- El directorio de trabajo se crea bajo `storage/app/private/cv-pdf/{uuid}` y se elimina en `finally`, tanto en éxito como en error. Solo contiene métricas/temporales de fuente, no el PDF; `output()` devuelve una cadena y el documento no se escribe en disco.
- Si la limpieza falla, no se entrega el PDF; se trata como error controlado para no dejar recursos temporales recuperables (C6).
- No se usan imágenes, CSS remoto, `@font-face` remoto, JavaScript, formularios, `iframe`, `object`, `embed` ni adjuntos.
- El HTML se genera con Blade escapado; no se inserta HTML del usuario.

### D5 — Enlaces y allowlist

- Solo se activan destinos `http:`, `https:`, `mailto:` y `tel:`.
- `http(s)`: `filter_var(..., FILTER_VALIDATE_URL)` y esquema en allowlist; se rechazan credenciales embebidas, esquemas peligrosos (`javascript:`, `data:`, `file:`, `vbscript:`, `phar:`), rutas relativas y protocol-relative `//`.
- `mailto:`: solo si el correo valida; se conserva el texto visible original.
- `tel:`: se normaliza el teléfono a dígitos y `+`; si no queda ningún dígito, el teléfono se muestra como texto sin enlace. El texto visible es el original.
- Si un enlace no es válido o seguro, se conserva el texto visible (etiqueta o valor) pero no se emite la anotación.
- Los enlaces internos de corrección (`source_links`) no se renderizan.

### D6 — Nombre de archivo

- `CvPdfFilename::fromProfileName(?string $name): string`.
- `Str::ascii($name)` (Laravel/`voku/portable-ascii`, ya disponible) → minúsculas → `[^a-z0-9]+` a `-` → colapsar → trim de guiones → truncar a 80 caracteres → quitar guion final → prefijo `cv-` y sufijo `.pdf`.
- Si la base queda vacía: `cv.pdf`.
- No se usa `intl`/`iconv` directamente; `Str::ascii` evita una dependencia de extensión y es determinista para el alfabeto latino.
- El nombre final cumple `^cv(-[a-z0-9]+)*\.pdf$` o es `cv.pdf`; no contiene rutas, `..`, comillas, controles ni secuencias de escape.
- El nombre del perfil no se imprime en el documento.

### D7 — Resolución del perfil compartida

- La selección de perfil (perfiles activos, query, estados `no_active_profiles`/`selection_required`/`unavailable`/`ready`) se extrae a `App\Support\CvProfileResolver`.
- El resolvedor recibe el array completo de query (`$request->query()`), no `$request->query('profile')`: así `?profile[]=1` se trata como inválido y no lanza una excepción de scalar de Symfony. Conserva el comportamiento actual de `parse_str`.
- `CvGeneratorController@index` y `CvPdfController` usan el mismo resolvedor. Esto cumple C2/FR-002 (sin segunda lógica de selección) y evita duplicar 20 líneas de parsing.
- El cambio en `CvGeneratorController` es focalizado: sustituir el bloque de consulta/validación por la llamada al resolvedor y mantener el mapeo de `profiles`, el `try/catch`, el `report()` y los estados. Las pruebas existentes de SPEC-004 son la red de seguridad.
- Alternativa descartada: duplicar la resolución en el controlador de PDF; violaría la unidad de selección y crearía divergencias.
- Alternativa descartada: un servicio/gestor con interfaces y DTO; no aporta para dos llamadas y añade complejidad.

### D8 — Controlador separado

- `CvPdfController` invocable, con responsabilidad única: revalidar, componer, renderizar, validar y descargar.
- Se separa de `CvGeneratorController@index` para no mezclar la página Inertia con una respuesta binaria y para aislar el manejo de errores de exportación.
- `CvGeneratorController` solo cambia para usar el resolvedor; su contrato HTTP y su página no cambian.
- La ruta se declara explícitamente en `routes/web.php`, sin `Route::resource`.

### D9 — Validación antes de entregar

- `CvPdfValidator` comprueba, en runtime, la estructura:
    - no vacía;
    - cabecera `%PDF-` y trailer `%%EOF` (reutiliza `PdfFile::looksLikePdf`, ya existente);
    - presencia de `/Type /Pages` y al menos un `/Type /Page`;
    - `startxref` en el tramo final.
- La legibilidad del texto, el número exacto de páginas, el tamaño A4, las fuentes y las anotaciones se verifican en pruebas con `smalot/pdfparser:^2.13` (dev) y, para la apertura real, en validación manual con dos lectores.
- La ausencia de cifrado, JavaScript, formularios, adjuntos y acciones `Launch` se verifica en las pruebas sobre el PDF real y se garantiza por la configuración del renderizador y la plantilla. No se usan búsquedas de subcadenas en runtime para no rechazar URLs legítimas que contengan esos textos en una ruta.
- No se usa el parser en runtime: la validación de entrega es estructural y la generación ya terminó sin excepción; el parser queda como dependencia de prueba para no añadir superficie ni coste al camino de producción.
- `smalot/pdfparser` declara mantenimiento limitado; es dev-only y su superficie es acotada (texto/páginas/metadatos). Si dejara de ser compatible, se puede sustituir por otra librería de parsing o por `pdftotext` en QA sin afectar al runtime.
- Si la validación falla, se lanza una excepción controlada, se registra con `report()` y se redirige con un toast; no se entrega un archivo parcial.

### D10 — Ruta, descarga y errores

- `GET /cv/pdf` (`cv.pdf`) con query obligatorio `profile=ID`.
- Éxito: `200`, `application/pdf`, `Content-Disposition: attachment; filename="cv-....pdf"`, `Content-Length`, `Cache-Control: no-store, private`, `Pragma: no-cache`, `X-Content-Type-Options: nosniff`. Cuerpo binario, no Inertia.
- Errores esperados: `302` a `/cv` o `/cv?profile=ID` con flash `toast` de tipo `error`; sin PDF. El toast existente (`HandleInertiaRequests`/`useFlashToast`) se muestra fuera del documento.
- Métodos distintos de `GET`: `405`.
- No se redirige a una URL que permita recuperar el PDF; cada exportación se regenera.

### D11 — Estrategia de pruebas

- Feature: ruta/headers/nombre; PDF válido; fidelidad incluidos/excluidos/orden/título/resumen/herencia; multipágina; enlaces; disponibilidad/errores; no persistencia; solo GET; regresión de SPEC-004.
- Unit: nombre; formato de fechas/categorías; allowlist de enlaces; validador con PDFs corruptos/truncados.
- Vitest: acción visible/ausente, href, fuera del artículo, accesibilidad/foco.
- Manual: apertura en dos lectores, revisión visual de paginación/márgenes/tipografía, foco/teclado, funcionamiento sin red.
- La prueba de regresión de SPEC-004 que prohibía `cv.pdf` se actualiza de forma focalizada para reconocer la nueva ruta GET, manteniendo prohibidas las rutas de escritura, impresión, descarga alternativa, plantilla, historial y reordenamiento.

### D12 — Browsershot + SSR de `Cv/Print` (seleccionada)

**Contexto**: la implementación Dompdf + Blade fue rechazada visualmente el 2026-10-23. El PDF era válido y correcto en contenido/orden, pero la Blade reproduce una segunda presentación CSS 2.1 con tipografía, jerarquía, espaciado, contacto y paginación distintos de `CvPreview`. La corrección aprobada exige imprimir el componente real.

**Decisión**:

- **Browsershot 5.4.x + Puppeteer 25.16.x + Chrome headless** convierten a PDF el HTML del componente real. Se usa `newHeadless()` con Chrome instalado, `format('A4')`, `preferCSSPageSize`, `showBackground`, `disableJavascript`, `emulateMedia('print')`, `blockUrls(['http://','https://'])` y `pdf()` en memoria.
- **SSR de Inertia** renderiza la página interna `Cv/Print` en la misma petición: `Inertia::render(...)->rootView('cv.print')->toResponse($request)->getContent()`. `Cv/Print` solo monta `<CvPreview preview={preview} />`; no hay layout, selector, controles, `source_links` ni app chrome.
- **HTML autocontenido**: CSS compilado inline (`Vite::content('resources/css/app.css')`), print CSS mínimo, `@font-face` Instrument Sans 400/500/600 con WOFF2 locales en data URI y sin recursos remotos. Chrome recibe un archivo temporal `file://`, no una URL de Laravel.
- **Sin segunda petición HTTP**: el SSR recibe el page JSON por loopback y devuelve HTML. No se pide `/cv/print` a Laravel, no se comparten sesión/cookies con Chrome y no se puede producir un deadlock por una petición HTTP recursiva.
- **Guardas**: si `SsrState::response` es `null`, si el HTML no contiene el `<article>` o si Chrome falla, el renderer lanza una excepción controlada; el controlador responde `302` + toast y no entrega PDF. **No hay fallback a Dompdf**.
- **Endpoint SSR**: el plugin Inertia se configura con `ssr.host = 127.0.0.1` y `ssr.port = 13714`; `config/inertia.php` apunta a `http://127.0.0.1:13714`. No se expone en `0.0.0.0`.
- **SSR acotado**: para que la mera existencia del bundle no convierta todas las páginas Inertia a SSR, `HandleInertiaRequests` desactiva el gateway salvo en `/cv/pdf` (`HttpGateway::disable(fn () => ! $request->is('cv/pdf'))`). Las páginas normales conservan CSR; solo la exportación usa SSR. Es una línea de middleware, no una ruta nueva.
- **Limpieza**: directorio `storage/app/private/cv-pdf/{uuid}` con permisos `0700`, usado como `setCustomTempPath`; Browsershot escribe ahí el HTML temporal y el renderer lo elimina en `finally`. El PDF no se escribe en disco. Queda el riesgo residual de un `kill -9`, documentado.

**Comparación de las tres alternativas**

| Criterio                      | Browsershot + SSR `Cv/Print` (seleccionada)                                         | Browsershot + ruta pública `/cv/print`                                                             | Dompdf + Blade (actual, rechazada)                           |
| ----------------------------- | ----------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- | ------------------------------------------------------------ |
| Fidelidad visual              | **Máxima**: mismo componente React/Tailwind que la vista previa.                    | Alta, pero la página debe existir fuera del layout y protegerse.                                   | Limitada a CSS 2.1; segunda presentación; rechazada.         |
| Segunda petición HTTP         | **No**: SSR en memoria.                                                             | **Sí**: Chrome pide `/cv/print` a Laravel.                                                         | No.                                                          |
| Sesión/cookies/auth           | No se comparten con Chrome.                                                         | Hay que resolver sesión, cookies, CSRF y auth para Chrome.                                         | No aplica.                                                   |
| Deadlock en dev               | **No**: el SSR es un proceso Node independiente, no una petición a Laravel.         | Riesgo real con servidor PHP de un solo worker: Chrome espera a Laravel y Laravel espera a Chrome. | No aplica.                                                   |
| Ruta pública nueva            | **Ninguna**; `Cv/Print` es un componente interno.                                   | `GET /cv/print` pública o protegida; amplía la superficie y el contrato.                           | No.                                                          |
| Impacto en páginas existentes | **Ninguno**: gateway SSR acotado a `/cv/pdf`; el resto sigue CSR.                   | Ninguno, pero la ruta nueva y su sesión son superficie adicional.                                  | Ninguno, pero mantiene una segunda presentación.             |
| App chrome / layout           | **Excluido por diseño**: `Cv/Print` no monta `AppLayout`.                           | Hay que crear una página sin layout y mantenerla separada.                                         | La Blade no tiene layout, pero es otra presentación.         |
| Duplicación de presentación   | **Ninguna**: `CvPreview` es la única plantilla.                                     | Ninguna si la página monta `CvPreview`, pero añade una ruta y su ciclo.                            | Alta: Blade + CSS 2.1 + helpers de formato/enlaces.          |
| Dependencias                  | PHP Browsershot + Node/Puppeteer + Chrome + SSR.                                    | Las mismas + ruta/controlador de impresión.                                                        | Solo PHP/Dompdf.                                             |
| Complejidad operativa         | Media/alta: build SSR, proceso Node, Chrome, fuentes locales.                       | Media/alta + autenticación de la segunda petición.                                                 | Baja, pero no cumple fidelidad.                              |
| Pruebas                       | PDF real Chrome, HTML SSR, Chrome/SSR caídos, no persistencia; suite más lenta.     | Añade pruebas de ruta pública, sesión y deadlock.                                                  | PDF real Dompdf; no detecta la divergencia visual.           |
| Seguridad                     | Loopback, sin ruta pública, sin JS, sin red externa, allowlist de URL, temp limpio. | Superficie de ruta y sesión; más puntos de exposición.                                             | Local y restrictiva, pero mantiene una segunda presentación. |
| Veredicto                     | **Elegida**: cumple fidelidad y evita la segunda petición.                          | Descartada por ruta/sesión/deadlock.                                                               | Descartada por rechazo visual.                               |

**Por qué se elige SSR y no `/cv/print`**:

- La página de impresión no necesita ser navegable: es un detalle de implementación del PDF. Una ruta pública añadiría un contrato HTTP no solicitado, obligaría a decidir autenticación/sesión y permitiría que un usuario viera una página sin layout.
- Chrome no debe recibir cookies ni sesión de Laravel. El SSR recibe solo el page JSON y devuelve HTML; el HTML se pasa a Chrome como `file://`. Esto reduce la superficie y evita que Chrome pueda desencadenar peticiones autenticadas.
- Con un servidor de desarrollo PHP de un solo worker, una ruta que Chrome solicita mientras Laravel espera a Chrome puede bloquearse. El SSR elimina esa clase de fallo.
- El SSR ya está soportado por Inertia 3 y el proyecto ya tiene `npm run build:ssr`; no se añade un framework nuevo, solo la entrada `ssr.tsx` y el bundle.

**Alternativas descartadas dentro de Browsershot**:

- **`Browsershot::url('/cv/print')`**: segunda petición, sesión/cookies, ruta pública y deadlock; descartada.
- **Generar el HTML con `ReactDOMServer` en un script ad hoc fuera de Inertia**: duplica el bootstrap de páginas/props y pierde la resolución de componentes de Inertia; el SSR oficial ya lo hace.
- **Imprimir la vista previa ya cargada en el navegador del usuario**: requiere JS cliente, no valida en servidor, no es descargable de forma determinista y expone el HTML; descartada.
- **Dompdf con CSS adicional para “parecerse”**: no puede reproducir Tailwind/flex/espaciado del navegador; es la causa del rechazo. Descartada.
- **wkhtmltopdf/mPDF/TCPDF**: mismo problema de segunda presentación y motores CSS antiguos; descartadas.
- **Servicio externo de PDF**: prohibido por FR-013/Constitución XXIII.

**Consecuencias**:

- Se eliminan `CvPdfFormatter`, `CvPdfLink`, la Blade `cv/pdf.blade.php` y sus pruebas; el formato y los enlaces los resuelve `CvPreview`.
- Se conserva una **allowlist de URL en el renderer** antes de SSR porque la validación de entrada admite más esquemas que `http(s)` y FR-010 sigue vigente. No es una segunda presentación; es una barrera de seguridad. `linkedin_url`, `github_url`, `website_url`, `validation_url` y `project.url` solo admiten `http(s)`; `links[].url` admite también `mailto:`/`tel:` válidos. Un valor no seguro se pasa como `null` para que `CvPreview` no emita un `href` activo.
- Se mantienen `CvPdfFilename`, `CvPdfValidator`, `CvProfileResolver`, `CvPdfController`, la ruta `GET /cv/pdf`, los headers, el nombre y la no persistencia.
- El HTML temporal de Browsershot contiene datos del CV; se limpia en `finally` y se documenta el riesgo residual ante terminación forzada. El PDF nunca se escribe en disco.

**Evidencia consultada para D12** (2026-10-23/24; documental, sin instalar ni ejecutar):

- Browsershot 5.4.1 en Packagist, requisitos PHP `^8.2` y MIT: <https://packagist.org/packages/spatie/browsershot>.
- Browsershot, requisitos Node 22+ y Puppeteer 23+, `setNodeModulePath`, `setChromePath`, `setNodeBinary`: <https://spatie.be/docs/browsershot/v4/requirements>.
- Browsershot, PDF: `format`, `margins`, `showBackground`, `pdf()`, `savePdf()`: <https://spatie.be/docs/browsershot/v4/usage/creating-pdfs>.
- Browsershot, opciones arbitrarias (`preferCSSPageSize` vía `setOption`): <https://spatie.be/docs/browsershot/v4/miscellaneous-options/setting-an-arbirary-option>.
- Puppeteer 25.16.0 en npm, `engines.node >=22.12.0`: <https://registry.npmjs.org/puppeteer/latest>.
- Inertia Laravel 3, `SsrState`, `HttpGateway` y `inertia:start-ssr`; el proyecto ya tiene `build:ssr` y `config/inertia.php` con SSR habilitado (código instalado, revisado en esta sesión).
- `@inertiajs/vite` genera el bootstrap `createServer`/`renderToString` y permite `ssr.host`/`ssr.port`; el bundle se genera en `bootstrap/ssr` (código instalado, revisado en esta sesión).

**Conclusión**: D12 supersede D1–D5 y la parte de presentación de D11. D6–D10 se mantienen. La decisión de enfoque está aprobada; la instalación, el build SSR, la ruta de Chrome y la suite son trabajo de Implement/QA y no se declaran ejecutados.

## Alternativas de ruta y nombre consideradas

- `GET /cv/export` (`cv.export`) frente a `GET /cv/pdf` (`cv.pdf`): se elige `/cv/pdf`/`cv.pdf` por claridad y por reflejar el formato; ambas son igual de simples. El cambio de nombre no altera el diseño.
- `POST /cv/pdf`: descartado; la exportación no muta datos y no necesita CSRF. Un `GET` es coherente con la vista previa de solo lectura y con la descarga.
- Nombre con ID, fecha o slug del título: descartado; C5 fija `cv-{perfil}.pdf` y la estabilidad. Si dos perfiles comparten nombre, comparten nombre de archivo, como indica la spec.

## Evidencia consultada

- Dompdf, README oficial: CSS 2.1, `@page`, DejaVu preinstalada, `isRemoteEnabled`, `chroot`, limitaciones (sin flex/grid, tablas no paginables), una instancia por documento. <https://github.com/dompdf/dompdf>
- Dompdf, `Options.php`: valores por defecto de `isRemoteEnabled`, `isPhpEnabled`, `isJavascriptEnabled`, `chroot`, `defaultFont`, `tempDir`, `fontCache`. <https://github.com/dompdf/dompdf/blob/master/src/Options.php>
- Packagist, `dompdf/dompdf` 3.1.6 (2026-07-20) y requisitos. <https://packagist.org/packages/dompdf/dompdf>
- Dompdf, adaptador CPDF: `add_link` registra URI externa; `get_page_count`; `output`. <https://github.com/dompdf/dompdf/blob/master/src/Adapter/CPDF.php>
- Packagist, `smalot/pdfparser` 2.13.1 (2026-10-13), requisitos y extracción de texto/páginas. <https://packagist.org/packages/smalot/pdfparser>
- `smalot/pdfparser`, guía de uso: `parseContent`, `getPages`, `getText`, `getDetails`, `MediaBox`. <https://github.com/smalot/pdfparser/blob/master/doc/Usage.md>

## Preguntas abiertas

- **Ninguna decisión funcional nueva.** C1–C10, FR-001–FR-017 y SC-001–SC-008 se mantienen.
- **Decisión de enfoque aprobada el 2026-10-23**: Browsershot + Chrome + SSR de `Cv/Print`. Queda pendiente de Implement/QA verificar en el entorno concreto la ruta de Chrome, el arranque del SSR en tests, la versión de Node y el comportamiento real de las anotaciones `tel:`/`mailto:` en Chrome. El build `bootstrap/ssr/ssr.js` ya fue verificado: Vite emite `.js`, no `.mjs`. No son decisiones de negocio ni se presentan como validadas.
- **Riesgo a confirmar con evidencia**: que `CvPdfValidator` acepte la estructura del PDF de Chrome; si no, se ajusta con un PDF real y se documenta, sin sustituir la validación por una suposición.
- **Aceptación**: la corrección no declara aceptación funcional ni visual, no cierra la spec y no autoriza commit, merge o push. T019 sigue pendiente y replanteada contra `CvPreview`.
