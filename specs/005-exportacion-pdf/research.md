# Fase 0 — Investigación y decisiones técnicas: Exportación PDF

**Feature**: `005-exportacion-pdf` | **Fecha**: 2026-10-23
**Referencias**: [spec.md](./spec.md) · [plan.md](./plan.md) · [data-model.md](./data-model.md) · [contracts/http-contracts.md](./contracts/http-contracts.md) · [SPEC-004](../004-generador-de-cv/plan.md)

## Alcance y método

Este documento decide el mecanismo local de renderizado, la forma de la ruta y la descarga, el nombre de archivo, la validación previa y la estrategia de pruebas. Respeta las decisiones funcionales cerradas C1–C10 de la spec y no reabre SPEC-004.

**Evidencia**: se contrastó la documentación oficial de Dompdf y su ficha de Packagist, y la ficha de `smalot/pdfparser`, consultadas el 2026-10-23. **No se instaló ninguna dependencia ni se ejecutó código o pruebas en esta fase.** Las versiones son la propuesta de Plan; el lock de Composer las fijará en Implement. Las afirmaciones sobre comportamiento de Dompdf son documentales y deberán confirmarse con las pruebas de Implement/QA.

## Decisión resumida

| Tema | Decisión |
| --- | --- |
| Renderizador | **Dompdf 3.1.x** (`dompdf/dompdf:^3.1`), PHP puro, sin binario externo. |
| Presentación | **Vista Blade de impresión** `resources/views/cv/pdf.blade.php`, A4, una columna, CSS 2.1, DejaVu Sans embebida. |
| Composición | `CvPreviewContent::build()` sin cambios; el PDF consume exactamente el mismo payload que la vista previa. |
| Ruta | `GET /cv/pdf` (`cv.pdf`), controlador `CvPdfController` invocable. |
| Selección | `CvProfileResolver` compartido por la vista previa y la exportación; sin duplicar la lógica de perfil activo/query. |
| Nombre | `cv-{base-normalizada}.pdf` con `Str::ascii`, minúsculas, guiones, truncado a 80 y fallback `cv.pdf`. |
| Validación | Estructural en runtime (`%PDF-`, `%%EOF`, páginas, `startxref`) y de contenido/seguridad en pruebas con `smalot/pdfparser` (dev). |
| Paginación | Saltos naturales de CSS; sin numeración de página. |
| Pruebas | Feature con PDF real, Unit de nombre/formato/enlaces/validador y Vitest de la acción; apertura en dos lectores y revisión visual manual. |

## Comparación de alternativas

Criterios: generación local/offline, ausencia de binarios y procesos externos, fidelidad suficiente para una columna A4, fuentes embebidas, enlaces, testabilidad, dependencia y portabilidad, seguridad y complejidad.

| Criterio | Dompdf (PHP/HTML-CSS) | Navegador headless (Browsershot/Chrome) | Generación cliente (`@react-pdf`, jsPDF) | Otra librería PHP (mPDF/TCPDF) |
| --- | --- | --- | --- | --- |
| Local y sin red | Sí | Sí, pero depende del binario | Sí, en el navegador | Sí |
| Binario/proceso externo | No | **Sí** (Chrome/Chromium, proceso, sandbox) | No | No |
| Fidelidad visual | Buena para CSS 2.1; no Tailwind completo | Muy alta | Alta si se duplica la plantilla | Media/baja según librería |
| Fuentes embebidas | DejaVu preinstalada y subsetting | Las del navegador/sistema | Requiere cargar fuentes | Variable |
| Enlaces | Anotaciones a partir de `<a href>` | Muy buena | Depende de la librería | Variable |
| Testabilidad | PDF real verificable en PHP; parser dev | Proceso lento, dependiente del entorno | Verificación en cliente, no en servidor | Similar a Dompdf |
| Dependencia/portabilidad | Una dependencia PHP; funciona en el entorno actual | Node + Chromium + Puppeteer/Browsershot; ruta de Chrome.app no portable | Dependencia JS y duplicación de plantilla | Dependencia PHP, a menudo más pesada |
| Seguridad | Opciones restrictivas; requiere cuidar `chroot`, remoto, PHP y JS | Superficie de proceso y sandbox del navegador | Datos y lógica en cliente; sin validación previa en servidor | Similar |
| Complejidad | Baja/media | Alta | Alta por duplicación | Media/alta |

### 1. Dompdf — seleccionada

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

### 2. Navegador headless (Browsershot/Chrome) — descartada

**Ventaja**: fidelidad visual alta si se reutilizara el HTML de la vista previa.

**Motivos de descarte**:

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

- **Ninguna decisión funcional nueva.** Las decisiones de presentación delegadas por C10 quedan resueltas en este documento y en `plan.md`.
- **Aprobación humana pendiente**: renderizador, dependencias, ruta/nombre y actualización focalizada de la prueba de regresión. El Plan no se declara aprobado y no se inician Tasks/Implement.
