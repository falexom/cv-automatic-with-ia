# Quickstart — Validación de la Exportación PDF

**Feature**: `005-exportacion-pdf` | **Fecha**: 2026-10-23
**Referencias**: [plan.md](./plan.md) · [research.md](./research.md) · [data-model.md](./data-model.md) · [contracts/http-contracts.md](./contracts/http-contracts.md) · [spec.md](./spec.md)

Guía para implementar, ejecutar las pruebas y validar la capacidad de extremo a extremo. No contiene código de implementación. **Esta fase de Plan no ejecutó los comandos ni los escenarios**: el registro queda pendiente para Implement/QA y la aceptación humana.

## Requisitos previos

- PHP 8.3+ (el entorno observado tiene 8.5.5) con `ext-dom`, `ext-mbstring`, `ext-gd`, `ext-zlib`, `ext-iconv`, `ext-fileinfo` y `pdo_sqlite`.
- Composer 2.x.
- Node.js 22+ y npm.
- Navegador de escritorio y dos lectores de PDF (por ejemplo, Vista Previa de macOS y Google Chrome; Adobe Acrobat si está disponible).
- Base de datos local con el esquema de SPEC-001/002/003/004 ya migrado.
- Un Perfil Maestro y, para los escenarios de selección, dos perfiles de CV activos (por ejemplo, «Analista» y «Programador»).

No se necesita Chrome/Chromium en `PATH`, Puppeteer, Browsershot ni un servicio externo. **No se ejecuta ninguna migración nueva.**

## Instalación propuesta (requiere aprobación humana)

Plan propone, pero **no ejecuta**:

```bash
composer require dompdf/dompdf:^3.1
composer require --dev smalot/pdfparser:^2.13
```

- `dompdf/dompdf` es la dependencia de runtime; arrastra `dompdf/php-font-lib`, `dompdf/php-svg-lib` y `masterminds/html5`.
- `smalot/pdfparser` es **solo dev**, para extraer texto, páginas y metadatos en las pruebas. No se usa en producción.
- Verificar extensiones: `php -m` debe incluir `dom`, `mbstring`, `gd`, `zlib`, `iconv` y `fileinfo`.
- Verificar el lock y el audit: `composer audit` (opcional, tras instalar).
- No se añaden dependencias JS para el PDF.

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
# Feature de la exportación
php artisan test tests/Feature/CvPdfExportTest.php

# Regresión de SPEC-004 (incluye la prueba de rutas actualizada)
php artisan test tests/Feature/CvGeneratorTest.php

# Unit de nombre, formato, enlaces y validador
php artisan test tests/Unit/CvPdfFilenameTest.php
php artisan test tests/Unit/CvPdfFormatterTest.php
php artisan test tests/Unit/CvPdfLinkTest.php
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
composer test
```

- Las pruebas Feature usan SQLite en memoria y `RefreshDatabase`; no tocan la base local.
- Las pruebas Vitest usan `jsdom`; no requieren navegador real.
- Las pruebas de PDF usan Dompdf real y `smalot/pdfparser` para verificar el archivo generado. No dependen de `qpdf`, `pdfinfo` ni `pdftotext`.
- Si una prueba de PDF es lenta, no reducir cobertura: es el riesgo principal de la capacidad (Principio XXI). Se puede ejecutar el archivo Feature aislado durante el desarrollo.

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

1. Iniciar la aplicación con `composer dev` (o el comando local equivalente).
2. Completar el Perfil Maestro con nombre, teléfono, correo, ubicación, LinkedIn, GitHub, sitio web y al menos un enlace adicional.
3. Crear o activar dos perfiles, por ejemplo «Analista» y «Programador», con títulos, resúmenes, selecciones y relevancias distintas.
4. Incluir en cada perfil elementos de las cinco clases, con relevancias Alta/Media/Baja; dejar algún elemento excluido y algún campo opcional vacío.
5. Agregar un elemento largo (descripción de varias líneas), acentos, eñes, signos y una URL larga.

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
4. Verificar A4 vertical, una sola columna, márgenes consistentes y jerarquía equivalente a la vista previa.
5. Comprobar que no hay contraseña, cifrado que impida la apertura, JavaScript, formularios ni adjuntos.

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
7. En todos los casos, verificar que la selección se conserva cuando el perfil sigue siendo válido y que el reintento funciona.

### 8. No persistencia y privacidad (FR-012, FR-013, SC-007)

1. Anotar los conteos de perfiles, registros maestros, pivotes y documentos, y los `updated_at` del Maestro y del perfil.
2. Exportar y volver a comprobar: no debe haber cambios.
3. Verificar que `storage/app/private/cv-pdf` no conserva archivos ni subdirectorios después de la exportación (éxito y fallo).
4. Verificar que no aparece un PDF nuevo en `public`, `storage/app` ni en el repositorio.
5. Comprobar que no hay una URL posterior para recuperar el PDF; cada descarga se regenera.
6. Desconectar la red (Wi-Fi/Ethernet) y exportar: debe funcionar localmente; abrir el PDF sin red y comprobar que no solicita recursos externos.

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
5. Verificar que la única ampliación de rutas es `GET /cv/pdf`; no hay rutas de escritura, historial, versión, anexos, concatenación, OCR, firma, publicación, correo, ATS ni IA.

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

- `php artisan test` en verde, incluidos `CvPdfExportTest` y `CvGeneratorTest`.
- `npm test` en verde, incluidos `CvGeneratorPanel.test.tsx` y `CvPreview.test.tsx`.
- `npm run types:check`, `npm run check` y `composer lint:check` sin errores.
- La acción «Exportar PDF» aparece solo en `ready` con datos, fuera de la hoja, y descarga un PDF con el nombre determinista.
- El PDF es A4 vertical, una columna, con fuentes embebidas, texto real, páginas completas y enlaces externos seguros.
- No hay PDF persistido, historial, versión, registro ni archivo temporal recuperable.
- La base de datos y el Perfil Maestro no cambian; la vista previa de SPEC-004 conserva su comportamiento y su solo lectura.
- Los escenarios manuales 1–10 pasan; la apertura en dos lectores y la revisión visual quedan registradas.

## Registro de validación

**No se ejecutaron pruebas ni escenarios manuales en la fase de Plan.** Este documento define el objetivo de validación para Implement y QA; no declara la capacidad aprobada ni el Plan aprobado. Cualquier evidencia debe registrarse con fecha, entorno, comando/escenario y resultado, distinguiendo prueba ejecutada, pendiente y bloqueada. La aceptación funcional y visual final, el merge a `main` y cualquier push requieren aprobación humana expresa y no forman parte de esta fase.
