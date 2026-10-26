# Validation Evidence — Exportación PDF (SPEC-005)

**Fecha de cierre:** 2026-10-26

**Rama:** `005-exportacion-pdf`

**Estado:** MVP cerrado funcional y visualmente por decisión del responsable; sin nuevas mejoras visuales; sin push.

## Decisión humana

El responsable autorizó el cierre funcional del MVP con la presentación actual y aceptó funcional y visualmente el PDF generado por **Browsershot + Chrome + SSR** sobre el componente real `CvPreview`. La aceptación no declara ejecutados los escenarios manuales que no tienen evidencia real.

## Evidencia automatizada

- `php artisan test`: **212/212**, 2.533 aserciones, 11,235 s.
- Focalizadas: **69/69**, 1.278 aserciones, 11,914 s:
    - `CvPdfExportTest`: 16/16.
    - `CvPrintHtmlTest`: 2/2.
    - `CvGeneratorTest`: 26/26.
    - `CvPdfFilenameTest`: 16/16.
    - `CvPdfValidatorTest`: 9/9.
- `npm test -- --run`: **53/53**, 15 archivos, 4,74 s.
- `npm run types:check`: sin errores.
- `composer lint:check`: Pint OK.
- `composer audit`: sin avisos en la última ejecución de QA.
- `npx vp check`: 2 fallos de formato preexistentes en `docs/roadmap.md` y `specs/004-generador-de-cv/tasks.md`; no pertenecen a SPEC-005.

## Exportaciones reales

| Perfil      | HTTP | Archivo              | Tamaño   | Páginas | Formato                                                     |
| ----------- | ---- | -------------------- | -------- | ------- | ----------------------------------------------------------- |
| Analista    | 200  | `cv-analista.pdf`    | 20.262 B | 1       | A4 594,96 × 841,92; InstrumentSans embebida; sin JS/cifrado |
| Programador | 200  | `cv-programador.pdf` | 20.127 B | 1       | A4; InstrumentSans embebida; sin JS/cifrado                 |

Generadas desde `/cv/pdf?profile=1` y `/cv/pdf?profile=2`, con la app en `127.0.0.1:8000` y SSR en `127.0.0.1:13714`. La página 1 del PDF de Analista se renderizó a PNG para inspección visual; el documento usa el `CvPreview` real, tipografía Instrument Sans y márgenes A4.

## Cobertura manual

**Ejecutado:**

- Exportación real de los perfiles Analista y Programador.
- Validación estructural con `pdfinfo`, `pdftotext` y `pdffonts`.
- Inspección visual de la página renderizada.
- No persistencia y seguridad cubiertas por pruebas automatizadas.

**No ejecutado individualmente:**

- Apertura en dos lectores estándar.
- Exportación y apertura sin red.
- Recorrido de foco/teclado de «Exportar PDF».
- Todos los escenarios manuales de `quickstart.md` uno por uno.

La aceptación humana del responsable se registra como decisión de cierre con **cobertura manual parcial**. T019 se cierra por aceptación humana, no por ejecución completa de esos escenarios.

## Limpieza y estado

- Eliminados los 5 archivos legacy de Dompdf/Blade:
    - `resources/views/cv/pdf.blade.php`
    - `app/Support/CvPdfFormatter.php`
    - `app/Support/CvPdfLink.php`
    - `tests/Unit/CvPdfFormatterTest.php`
    - `tests/Unit/CvPdfLinkTest.php`
- `php artisan view:clear` ejecutado; sin referencias activas en `app`, `routes`, `resources` ni `tests`.
- Dependencias: `spatie/browsershot` 5.4.1 y `puppeteer` 25.16.0; `dompdf/dompdf` retirado; `smalot/pdfparser` dev.
- Operación local: `npm run build:ssr`, `php artisan inertia:start-ssr`, `php artisan serve`.
- `BROWSERSHOT_CHROME_PATH` es opcional si la autodetección encuentra Chrome for Testing; se recomienda configurarla para estabilidad.
- Integración en `main` mediante merge `--no-ff` autorizado y ejecutado en el cierre; sin push.
