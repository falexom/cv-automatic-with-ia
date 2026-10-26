# Quickstart — Validación de la Exportación PDF

**Feature**: `005-exportacion-pdf` | **Fecha**: 2026-10-23
**Referencias**: [plan.md](./plan.md) · [research.md](./research.md) · [data-model.md](./data-model.md) · [contracts/http-contracts.md](./contracts/http-contracts.md) · [spec.md](./spec.md)

Guía para implementar, ejecutar las pruebas y validar la capacidad de extremo a extremo. No contiene código de implementación. **Esta fase de Plan no ejecutó los comandos ni los escenarios**: el registro queda pendiente para Implement/QA y la aceptación humana. **Corrección 2026-10-23**: el renderizador es Browsershot + Chrome headless con SSR de `Cv/Print`; se retiran Dompdf, la Blade, `CvPdfFormatter` y `CvPdfLink`.

## Requisitos previos

- PHP 8.3+ (el entorno observado tiene 8.5.5) con `ext-dom`, `ext-mbstring`, `ext-gd`, `ext-zlib`, `ext-iconv`, `ext-fileinfo` y `pdo_sqlite`.
- Composer 2.x.
- Node.js **>=22.12.0** y npm.
- Google Chrome (o Chromium completo) instalado y accesible por ruta absoluta; `chrome-headless-shell` no es el objetivo de `newHeadless()`.
- Navegador de escritorio y dos lectores de PDF (por ejemplo, Vista Previa de macOS y Google Chrome; Adobe Acrobat si está disponible).
- Base de datos local con el esquema de SPEC-001/002/003/004 ya migrado.
- Un Perfil Maestro y, para los escenarios de selección, dos perfiles de CV activos (por ejemplo, «Analista» y «Programador»).

Chrome, Puppeteer y el SSR son necesarios para exportar y para las pruebas de PDF. En desarrollo, `composer dev` ya inicia `npm run dev` y el SSR lo sirve el plugin Inertia de Vite; no arrancar `inertia:start-ssr` en paralelo. Para una build local o para tests, usar `npm run build:ssr` + `php artisan inertia:start-ssr` (o el helper de test). El CSS de impresión se lee del manifiesto de `public/build`: en desarrollo hay que ejecutar `npm run build` (o `build:ssr`) al menos una vez antes de exportar; el servidor Vite en caliente no genera ese manifiesto. El SSR está acotado a `/cv/pdf`: `/cv` y el resto de páginas siguen usando CSR; no se debe activar SSR global. **No se ejecuta ninguna migración nueva.**

## Instalación propuesta (requiere aprobación humana)

Plan propone, pero **no ejecuta**:

```bash
# Retirar el renderizador anterior y añadir el nuevo runtime PHP
composer remove dompdf/dompdf
composer require spatie/browsershot:^5.4

# Puppeteer como dependencia de runtime Node; usar el Chrome instalado
PUPPETEER_SKIP_DOWNLOAD=true npm install puppeteer@^25.16

# Mantener el parser solo para pruebas
composer require --dev smalot/pdfparser:^2.13

# Generar assets cliente y bundle SSR
npm run build:ssr
```

- `spatie/browsershot:^5.4` es runtime y usa `puppeteer:^25.16` desde `node_modules`; `smalot/pdfparser` es **solo dev** para extraer texto, páginas y metadatos.
- `PUPPETEER_SKIP_DOWNLOAD=true` evita descargar Chromium cuando se usa un Chrome/Chromium completo. `BROWSERSHOT_CHROME_PATH` debe apuntar al ejecutable completo (no a `chrome-headless-shell`); se configura en `.env` o en el entorno del proceso, nunca en el código. Si no se configura, las pruebas autodetectan Chrome for Testing.
- **Entorno macOS observado**: el Chrome estándar (`/Applications/Google Chrome.app/Contents/MacOS/Google Chrome`) es un symlink roto y no sirve para exportar. Usar el Chrome for Testing instalado por Playwright (`/Users/aom/Library/Caches/ms-playwright/chromium-1243/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing`) o instalar/reparar Chrome. Esa ruta es específica de esta máquina: no se versiona ni se hardcodea. `BROWSERSHOT_TIMEOUT=30` es opcional (segundos).
- Fallback sin Chrome del sistema: `npx puppeteer browsers install chrome` (Chrome completo) o instalar Chrome; no usar `chrome-headless-shell` con `newHeadless()`.
- Verificar `node -v` >= 22.12, `php -m` con `dom`, `mbstring`, `gd`, `zlib`, `iconv` y `fileinfo`, y `composer audit`.
- No se añaden dependencias JS de UI. El bundle real es `bootstrap/ssr/ssr.js` (Vite emite `.js`, no `.mjs`); está en `.gitignore` y se regenera con `npm run build:ssr`.
- `vite.config.ts` debe compilar el SSR con `host: '127.0.0.1'` y puerto 13714; `config/inertia.php` debe apuntar a `http://127.0.0.1:13714` y a `base_path('bootstrap/ssr/ssr.js')`.

## Base de datos: no hay migración nueva

```bash
# Solo si hay migraciones pendientes de especificaciones anteriores
php artisan migrate
php artisan migrate:status
```

- Verificar que el esquema existente esté aplicado.
- **No ejecutar** `php artisan migrate:fresh`, `migrate:refresh` ni `db:wipe`: destruyen el Perfil Maestro, los perfiles, los documentos y las asociaciones existentes.
- No hay rollback específico de SPEC-005 porque no hay migración de SPEC-005.

## Ejecutar pruebas

```bash
# Requisito previo de la suite PDF: bundle SSR y servidor Inertia activo
npm run build:ssr
php artisan inertia:start-ssr   # en otra terminal; el helper de test puede arrancarlo

# Feature de la exportación
php artisan test tests/Feature/CvPdfExportTest.php

# HTML de impresión (CvPreview real, sin Chrome)
php artisan test tests/Feature/CvPrintHtmlTest.php

# Regresión de SPEC-004 (incluye la prueba de rutas actualizada)
php artisan test tests/Feature/CvGeneratorTest.php

# Unit de nombre y validador
php artisan test tests/Unit/CvPdfFilenameTest.php
php artisan test tests/Unit/CvPdfValidatorTest.php

# Suite completa de backend
php artisan test

# Acción de exportación en el panel
npm test -- resources/js/components/__tests__/CvGeneratorPanel.test.tsx

# La hoja sigue sin controles de exportación
npm test -- resources/js/components/__tests__/CvPreview.test.tsx

# Suite completa de frontend
npm test

# Tipos y formato/lint
npm run types:check
npm run check
composer lint:check

# Chequeo integral definido por Composer (lint + tipos + tests)
# Solo es válido para SPEC-005 si antes se ejecutaron build:ssr y el SSR;
# composer test no arranca ni construye el SSR por sí mismo.
composer test
```

- Las pruebas Feature usan SQLite en memoria y `RefreshDatabase`; no tocan la base local.
- Las pruebas Vitest usan `jsdom`; no requieren navegador real.
- Las pruebas de PDF usan Chrome real, `CvPdfRenderer` con SSR y `smalot/pdfparser` para verificar el archivo generado. No dependen de `qpdf`, `pdfinfo` ni `pdftotext`.
- `CvPdfExportTest`/`CvPrintHtmlTest` necesitan `bootstrap/ssr/ssr.js`. El helper `cvPdfStartSsr()` reutiliza un servidor sano en `127.0.0.1:13714` o lanza `node bootstrap/ssr/ssr.js` y lo detiene al final; si el bundle falta, la prueba falla con el mensaje «Ejecutá `npm run build:ssr`». Si hay un `npm run dev` activo, el SSR puede servirse desde Vite; no dejar `public/hot` apuntando a un Vite caído.
- Estas pruebas no deben usar el fake de `withoutVite()`: el fake devuelve CSS vacío y `Vite::content('resources/css/app.css')` no encontraría el CSS compilado. `tests/Pest.php` aplica `withoutVite()` a todo Feature; `CvPdfExportTest` y `CvPrintHtmlTest` deben llamar a `$this->withVite()` en su `beforeEach`, después del global, (o quedar fuera de ese fake) y usar el manifiesto real de `public/build` generado por `npm run build:ssr`.
- Si una prueba de PDF es lenta, no reducir cobertura: es el riesgo principal de la capacidad (Principio XXI). Se puede ejecutar el archivo Feature aislado durante el desarrollo. La suite completa será más lenta que con Dompdf por el arranque de Chrome y el SSR.
- No se conservan `CvPdfFormatterTest` ni `CvPdfLinkTest`; su formato/enlaces los cubre `CvPreview` y la allowlist del renderer se prueba en Feature.

## Verificación de rutas

```bash
php artisan route:list --name=cv
```

Debe mostrar exactamente:

- `GET /cv` → `cv.index`
- `GET /cv/pdf` → `cv.pdf`

No debe mostrar `POST`, `PUT`, `PATCH` ni `DELETE` para ninguna de las dos. Las rutas `cv.export`, `cv.print`, `cv.download`, `cv.template`, `cv.store`, `cv.update`, `cv.destroy`, `cv.regenerate` y `cv.reorder` no deben existir.

## Flujo manual de validación

Cada escenario deriva de los criterios de aceptación y debe pasar antes de dar la capacidad por terminada. Registrar fecha, entorno y resultado; distinguir lo ejecutado de lo pendiente. Usar datos de prueba o un entorno local respaldado; no ejecutar migraciones destructivas ni modificar datos reales sin copia de seguridad.

### 1. Preparación

1. Iniciar la aplicación con `composer dev` (o el comando local equivalente) y confirmar que el SSR responde: en desarrollo lo sirve Vite; para una build local, `npm run build:ssr` + `php artisan inertia:start-ssr`.
2. Confirmar que Chrome está disponible en `BROWSERSHOT_CHROME_PATH` (o que la autodetección de Chrome for Testing funciona) y que `node -v` es >=22.12.
3. Completar el Perfil Maestro con nombre, teléfono, correo, ubicación, LinkedIn, GitHub, sitio web y al menos un enlace adicional.
4. Crear o activar dos perfiles, por ejemplo «Analista» y «Programador», con títulos, resúmenes, selecciones y relevancias distintas.
5. Incluir en cada perfil elementos de las cinco clases, con relevancias Alta/Media/Baja; dejar algún elemento excluido y algún campo opcional vacío.
6. Agregar un elemento largo (descripción de varias líneas), acentos, eñes, signos y una URL larga.

### 2. Vista previa y exportación (FR-001, FR-002, FR-012)

1. Abrir `/cv`, seleccionar «Analista» y comprobar la vista previa.
2. Verificar que «Exportar PDF» aparece **fuera** de la hoja, visible solo con la vista `ready` y datos.
3. Pulsar la acción; comprobar que se descarga `cv-analista.pdf` sin abandonar la página.
4. Repetir con «Programador»; el nombre debe ser `cv-programador-desarrollador.pdf` y el contenido debe corresponder a ese perfil.
5. Comprobar que la hoja no contiene el botón ni ningún control de exportación, impresión, edición o plantilla.

### 3. Apertura y legibilidad (FR-004, FR-014, SC-003)

1. Abrir el PDF descargado en **dos lectores** estándar (por ejemplo, Vista Previa y Chrome).
2. Verificar que muestra todas las páginas, que las fuentes se ven correctamente y que no hay páginas en blanco.
3. Comprobar que el texto se puede seleccionar y copiar; no debe ser una imagen.
4. Verificar A4 vertical, una sola columna, márgenes de 18 mm y jerarquía/tipografía Instrument Sans equivalente a la vista previa.
5. Comparar el PDF con `CvPreview` en pantalla: la card no debe conservar borde, sombra, esquinas redondeadas, `max-width` ni padding de pantalla; el contenido interno y los separadores deben mantenerse.
6. Comprobar que no hay contraseña, cifrado que impida la apertura, JavaScript, formularios ni adjuntos.

### 4. Fidelidad de contenido y orden (FR-005, FR-006, SC-001)

1. Comparar campo a campo la vista previa y el PDF del mismo perfil.
2. Verificar las mismas secciones, campos, elementos y orden: experiencia, educación, cursos y certificaciones, habilidades, proyectos.
3. Confirmar que no aparecen elementos excluidos, contenido del otro perfil, relevancia (Alta/Media/Baja), estado, nombre del perfil ni enlaces internos de corrección.
4. Verificar el título y el resumen propios; si están vacíos, no debe usarse el título ni el resumen del Maestro.
5. Comprobar que los datos personales y de contacto son los del Maestro vigente.
6. Verificar que los campos opcionales vacíos no dejan etiquetas, separadores, guiones, «N/D» ni espacios reservados.
7. Confirmar que el nombre del perfil no se imprime en el documento.

### 5. Multipágina, textos largos y acentos (FR-007, FR-008, SC-002)

1. Usar un CV que supere una página (muchas experiencias, descripciones largas).
2. Verificar que el PDF tiene varias páginas, que todo el contenido aparece completo y una sola vez, sin truncamientos, solapamientos ni contenido fuera de márgenes.
3. Comprobar que los encabezados de sección no quedan aislados al final de una página sin su primer elemento.
4. Verificar que no hay páginas en blanco evitables.
5. Comprobar acentos, eñes, signos, guiones y cadenas sin espacios: se muestran completos y se ajustan sin desbordar.
6. Repetir la exportación con los mismos datos: el número de páginas y el texto deben ser estables (el archivo puede diferir en metadatos técnicos de fecha).

### 6. Enlaces (FR-009, FR-010, SC-004)

1. Verificar que correo, LinkedIn, GitHub, sitio web, enlaces adicionales, validación de certificación y URL de proyecto conservan el mismo texto visible y destino.
2. Comprobar que el teléfono se ve como texto y, si el lector lo admite, como enlace `tel:`.
3. Verificar que no aparece ningún enlace a `/profiles`, `/profile`, `/experiences`, `/education`, `/certifications`, `/skills` o `/projects`.
4. Probar un valor no seguro (por ejemplo, `javascript:...` o `file://...`) en un enlace adicional: debe conservarse el texto, pero no debe activarse.
5. Comprobar que los enlaces no se solapan con el texto.

### 7. Disponibilidad y errores (FR-001, FR-003, FR-015, SC-006)

1. Desactivar todos los perfiles: la acción no debe ofrecerse; una petición directa a `/cv/pdf` no debe descargar PDF y debe mostrar un toast fuera del documento.
2. Abrir `/cv` sin selección: la acción no debe ofrecerse; `/cv/pdf` sin `profile` debe redirigir con toast.
3. Abrir `/cv/pdf?profile=999999` y `/cv/pdf?profile=abc`: sin PDF, redirect y toast; sin 404.
4. Generar la vista de un perfil, desactivarlo y volver a exportar: sin PDF, toast y alternativas activas.
5. Generar la vista de un perfil con datos, eliminar todos los datos que la componen (incluidos los personales del Maestro) y exportar: sin PDF y toast; la página muestra el estado vacío.
6. Simular un fallo de renderizador o de validación mediante las pruebas Feature con dobles; comprobar redirect, toast, ausencia de archivo parcial y reintento.
7. Detener el SSR (o apuntar `BROWSERSHOT_CHROME_PATH` a una ruta inválida) y exportar: debe aparecer el toast genérico, no debe descargarse PDF y no debe usarse Dompdf como fallback. Restaurar el entorno y comprobar que el reintento funciona.
8. En todos los casos, verificar que la selección se conserva cuando el perfil sigue siendo válido y que el reintento funciona.

### 8. No persistencia y privacidad (FR-012, FR-013, SC-007)

1. Anotar los conteos de perfiles, registros maestros, pivotes y documentos, y los `updated_at` del Maestro y del perfil.
2. Exportar y volver a comprobar: no debe haber cambios.
3. Verificar que `storage/app/private/cv-pdf` no conserva el HTML temporal, archivos ni subdirectorios después de la exportación (éxito y fallo); el PDF no se escribe en disco.
4. Verificar que no aparece un PDF nuevo en `public`, `storage/app` ni en el repositorio.
5. Comprobar que no hay una URL posterior para recuperar el PDF; cada descarga se regenera.
6. Desconectar la red (Wi-Fi/Ethernet) y exportar: debe funcionar localmente; el SSR es loopback y Chrome carga `file://`; abrir el PDF sin red y comprobar que no solicita recursos externos.

### 9. Accesibilidad de la acción (SC-008)

1. Recorrer la página solo con teclado hasta «Exportar PDF»; el foco debe ser visible.
2. Activar con Enter/Espacio; la descarga debe iniciarse.
3. Verificar que el nombre accesible es «Exportar PDF» y que la acción está fuera del `article` del CV.
4. Comprobar que la hoja sigue siendo de solo lectura y que no incorpora controles.

### 10. Regresión de SPEC-004 (SC-008)

1. Ejecutar `CvGeneratorTest` y la suite completa de backend.
2. Ejecutar `CvPreview.test.tsx` y `CvGeneratorPanel.test.tsx`.
3. Verificar que la vista previa sigue mostrando los mismos datos, orden y estados.
4. Verificar que la hoja no contiene botones, campos, enlaces de exportación, impresión, edición, plantilla ni guardado.
5. Verificar que `/cv` sigue sirviendo el fallback CSR de Inertia y que el SSR solo se usa en `/cv/pdf`; el resto de páginas no cambia.
6. Verificar que la única ampliación de rutas es `GET /cv/pdf`; no hay rutas de escritura, historial, versión, anexos, concatenación, OCR, firma, publicación, correo, ATS ni IA.

## Comprobaciones opcionales con binarios locales

No forman parte de la suite automatizada ni son un requisito de CI; se usan solo como apoyo manual si están disponibles:

```bash
pdfinfo cv-analista.pdf          # tamaño, páginas, metadatos
pdftotext cv-analista.pdf -      # texto extraído
qpdf --check cv-analista.pdf     # estructura y errores
```

- `pdfinfo`/`pdftotext`/`qpdf` están presentes en el entorno actual, pero la suite no debe depender de ellos (portabilidad).
- Si `qpdf --check` reporta advertencias, revisar si son recuperables o si indican un PDF inválido; no ignorar errores.

## Resultado esperado

- `php artisan test` en verde, incluidos `CvPdfExportTest`, `CvPrintHtmlTest` y `CvGeneratorTest`, con `npm run build:ssr` y SSR disponible.
- `npm test` en verde, incluidos `CvGeneratorPanel.test.tsx` y `CvPreview.test.tsx`.
- `npm run types:check`, `npm run check` y `composer lint:check` sin errores.
- La acción «Exportar PDF» aparece solo en `ready` con datos, fuera de la hoja, y descarga un PDF con el nombre determinista.
- El PDF es A4 vertical, una columna, con Instrument Sans embebida, texto real, páginas completas y enlaces externos seguros.
- El HTML de impresión contiene el `CvPreview` real, sin app chrome ni `source_links`, con CSS/fuentes autocontenidos.
- No hay PDF persistido, historial, versión, registro ni HTML temporal recuperable; el PDF no se escribe en disco.
- La base de datos y el Perfil Maestro no cambian; la vista previa de SPEC-004 conserva su comportamiento y su solo lectura.
- Los escenarios manuales 1–10 pasan; la comparación visual con `CvPreview`, la apertura en dos lectores y el fallo controlado de SSR/Chrome quedan registrados.
- No se declara aceptación visual: la decide el responsable tras revisar el PDF y el plan de corrección.

## Registro de validación

**No se ejecutaron pruebas ni escenarios manuales en la fase de Plan.** Este documento define el objetivo de validación para Implement y QA; no declara la capacidad aprobada ni el Plan aprobado. Cualquier evidencia debe registrarse con fecha, entorno, comando/escenario y resultado, distinguiendo prueba ejecutada, pendiente y bloqueada. La aceptación funcional y visual final, el merge a `main` y cualquier push requieren aprobación humana expresa y no forman parte de esta fase.
