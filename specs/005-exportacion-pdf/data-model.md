# Fase 1 — Modelo de datos: Exportación PDF

**Feature**: `005-exportacion-pdf` | **Fecha**: 2026-10-23
**Fuentes**: [spec.md](./spec.md) · [research.md](./research.md) · [plan.md](./plan.md) · [SPEC-004 data-model](../004-generador-de-cv/data-model.md) · [SPEC-004 http-contracts](../004-generador-de-cv/contracts/http-contracts.md)

## Visión general

La exportación no introduce ninguna entidad persistente. El **PDF del CV** es un valor derivado, efímero y de un solo uso que se calcula en la petición de descarga a partir de:

1. el perfil de CV activo seleccionado explícitamente;
2. la composición derivada de SPEC-004 (`CvPreviewContent::build()`), sin cambios;
3. una representación Blade A4 de la única plantilla;
4. un renderizador local Dompdf;
5. una validación estructural previa a la entrega.

```text
Perfil Maestro + Perfil de CV (existentes, SPEC-001/003/004)
        │
        │ CvPreviewContent::build(profile)  ← misma composición que /cv
        ▼
CvPreviewData (array en memoria)
        │
        │ Blade cv.pdf + Dompdf (A4, DejaVu Sans)
        ▼
PDF (cadena binaria en memoria)
        │
        │ CvPdfValidator
        ▼
Respuesta HTTP application/pdf (descarga)
        │
        ▼
Se descarta; no hay tabla, archivo, historial ni URL posterior
```

No se modela ninguna entidad de PDF, exportación, plantilla, archivo, historial o versión. No hay tablas, migraciones, seeders ni factories nuevas.

## Entrada: `CvPreviewData`

La entrada es exactamente el payload de SPEC-004, documentado en [su modelo de datos](../004-generador-de-cv/data-model.md#estructura-de-la-vista). Este documento no redefine sus campos ni sus reglas.

| Campo | Uso en la exportación |
| --- | --- |
| `personal_data` | Cabecera: nombre, teléfono, correo, ubicación y enlaces. Los campos vacíos se omiten. |
| `professional_title` | Título propio del perfil, si existe. Sin fallback al Maestro. |
| `summary` | Resumen propio del perfil, si existe. Sin fallback al Maestro. |
| `content.work_experiences` | Sección «Experiencia laboral», en el orden recibido. |
| `content.education_entries` | Sección «Educación», en el orden recibido. |
| `content.certifications` | Sección «Cursos y certificaciones», en el orden recibido. |
| `content.skills` | Sección «Habilidades», en el orden recibido. |
| `content.projects` | Sección «Proyectos», en el orden recibido. |
| `source_links` | **No se pasa a la vista**. Son enlaces internos de corrección. |
| `has_data` | Condición de disponibilidad: si es `false`, no se genera PDF. |

**Invariantes de entrada**

- El perfil debe seguir activo y existir al resolver la petición.
- `has_data` debe ser `true`; un PDF vacío no es un resultado válido.
- No se añaden, quitan, reordenan ni transforman elementos de contenido. La presentación puede cambiar la forma (párrafos, listas), no los datos.
- `relevance`, `is_active`, `master_path`, el nombre del perfil y cualquier metadato de configuración no forman parte del documento.
- El título y el resumen del Maestro no se usan como respaldo.

## Salida efímera: `CvPdfDocument` (conceptual)

No se implementa necesariamente como clase; se documenta como contrato conceptual del resultado.

| Atributo | Tipo | Origen | Persistencia |
| --- | --- | --- | --- |
| `contents` | `string` binario | `Dompdf::output()` | Ninguna; solo en memoria y en la respuesta HTTP. |
| `filename` | `string` | `CvPdfFilename::fromProfileName($profile->name)` | Ninguna; se usa en `Content-Disposition`. |
| `mime_type` | `string` | Constante `application/pdf` | Ninguna. |
| `pages` | `int` | Observado en pruebas con el parser; el renderizador puede obtenerlo del canvas | Ninguna. |
| `valid` | `bool` | `CvPdfValidator` evalúa `$contents` | Ninguna; si es `false`, no se entrega. |

**Ciclo de vida**

1. Resolver el perfil activo con `CvProfileResolver` (misma lógica que `/cv`).
2. Componer con `CvPreviewContent::build($profile)`.
3. Rechazar si `has_data` es `false`.
4. Renderizar la vista `cv.pdf` con Dompdf en memoria.
5. Validar la estructura y la ausencia de elementos prohibidos.
6. Entregar como descarga con nombre seguro y cabeceras `no-store`.
7. Descartar la cadena; no queda copia en servidor.

## Nombre de archivo

**Algoritmo** (`CvPdfFilename::fromProfileName`):

```text
1. Si el nombre es null o vacío → "cv.pdf".
2. ASCII = Str::ascii(nombre)                 (transliteración a ASCII)
3. minúsculas = Str::lower(ASCII)
4. base = reemplazar toda secuencia [^a-z0-9]+ por "-"
5. base = colapsar guiones repetidos
6. base = quitar guiones al inicio y al final
7. base = truncar a 80 caracteres
8. base = quitar cualquier guion final que haya quedado
9. Si base queda vacía → "cv.pdf".
10. nombre final = "cv-" + base + ".pdf".
```

- Se usa `Illuminate\Support\Str::ascii`, disponible en Laravel; no se añade `ext-intl` ni una dependencia de transliteración.
- El truncado se aplica después de la normalización; si corta en un guion, se elimina.
- La extensión se añade una sola vez.
- El nombre final solo contiene `[a-z0-9-]` y `.pdf`; no contiene rutas, separadores, `..`, comillas, controles ni escapes.
- La misma entrada produce siempre el mismo nombre.
- Si dos perfiles comparten nombre, comparten nombre de archivo; no se añade ID ni sufijo no especificado.

**Ejemplos**

| Nombre del perfil | Base normalizada | Nombre final |
| --- | --- | --- |
| `Analista` | `analista` | `cv-analista.pdf` |
| `Programador / Desarrollador` | `programador-desarrollador` | `cv-programador-desarrollador.pdf` |
| `Análisis de Sistemas` | `analisis-de-sistemas` | `cv-analisis-de-sistemas.pdf` |
| `Ñandú` | `nandu` | `cv-nandu.pdf` |
| `Ada 🚀 Lovelace` | `ada-lovelace` | `cv-ada-lovelace.pdf` |
| `../../etc/passwd` | `etc-passwd` | `cv-etc-passwd.pdf` |
| `a/b\c` | `a-b-c` | `cv-a-b-c.pdf` |
| `CV "Ada" .. <>` | `cv-ada` | `cv-cv-ada.pdf` |
| `   ` | — | `cv.pdf` |
| `🎯🚀` | — | `cv.pdf` |
| `-` | — | `cv.pdf` |
| `....` | — | `cv.pdf` |

- Los ejemplos con emojis o símbolos asumen que `Str::ascii` no deja caracteres alfanuméricos; si dejara alguno, se aplicaría la misma regla.
- El nombre del perfil se usa solo para el archivo; no se imprime ni se incluye en metadatos.

## Validación previa a la entrega

`CvPdfValidator` aplica estas comprobaciones sobre la cadena generada:

| Comprobación | Regla | Motivo |
| --- | --- | --- |
| No vacío | `strlen($pdf) > 0` | Evitar archivo vacío. |
| Cabecera | Empieza por `%PDF-` | Estructura PDF reconocible. |
| Trailer | `%%EOF` en el tramo final (reutiliza `PdfFile::looksLikePdf`) | Documento completo, no truncado. |
| Páginas | Presencia de `/Type /Pages` y al menos un `/Type /Page` | Hay al menos una página. |
| Índice | `startxref` en el tramo final | Cierre estructural coherente. |

- La validación es deliberadamente estructural; no intenta parsear el contenido en producción.
- La legibilidad del texto, el número exacto de páginas, el tamaño A4, las fuentes embebidas y las anotaciones se comprueban en pruebas con `smalot/pdfparser` (dependencia dev) y en la validación manual.
- Un fallo de validación no entrega el archivo; se registra la excepción con `report()` y se redirige con un toast.

**Comprobaciones de seguridad en pruebas (no en runtime)**

| Propiedad | Verificación | Garantía de generación |
| --- | --- | --- |
| Sin cifrado | El PDF generado no contiene `/Encrypt` y el parser lo abre sin contraseña. | Dompdf no cifra; no se configura cifrado. |
| Sin JavaScript | No contiene `/JavaScript` ni acciones de script. | `isJavascriptEnabled=false`; la Blade no incluye scripts. |
| Sin formularios | No contiene `/AcroForm` ni campos. | La Blade no incluye formularios ni campos. |
| Sin adjuntos | No contiene `/EmbeddedFile`. | No se adjuntan archivos. |
| Sin acciones de lanzamiento | No contiene `/Launch`. | La allowlist de enlaces solo permite URI `http(s)`, `mailto` y `tel`. |
| Sin recursos remotos | No hay imágenes, CSS, fuentes ni recursos remotos. | `isRemoteEnabled=false`; la Blade es autocontenida. |

- Estas comprobaciones se hacen sobre el PDF real en las pruebas Feature, no con búsquedas de subcadenas en runtime. Así se evita rechazar una URL legítima que contenga, por ejemplo, `/JavaScript` en su ruta.


## Enlaces

La vista Blade transforma los enlaces del payload con `CvPdfLink`, que devuelve un `href` seguro o `null`:

| Origen | Texto visible | Destino permitido |
| --- | --- | --- |
| `personal_data.phone` | El teléfono original | `tel:` con dígitos y `+`; si no hay dígitos, sin enlace. |
| `personal_data.email` | El correo original | `mailto:` si el correo valida; si no, sin enlace. |
| `linkedin_url`, `github_url`, `website_url` | `LinkedIn`, `GitHub`, `Sitio web` | `http(s)` válido y seguro; si no, texto sin enlace. |
| `links[].label` / `links[].url` | La etiqueta | `http(s)`, `mailto` o `tel` válido y seguro; si no, texto sin enlace. |
| `certifications[].validation_url` | `Validar certificación` | `http(s)` válido y seguro; si no, texto sin enlace. |
| `projects[].url` | `Ver proyecto` | `http(s)` válido y seguro; si no, texto sin enlace. |
| `source_links.*` | No se pasa a la vista | Ninguno; son enlaces internos de corrección. |

- Los esquemas permitidos son `http`, `https`, `mailto` y `tel`; se rechazan `javascript:`, `data:`, `file:`, `vbscript:`, `phar:`, rutas relativas, `//` y credenciales embebidas.
- La allowlist es común a todos los campos; la tabla indica el caso esperado. Un enlace adicional que sea `mailto:` o `tel:` válido conserva su etiqueta y se activa.
- Un enlace inválido conserva su texto visible, pero no se convierte en anotación.
- Las anotaciones no introducen JavaScript, formularios, adjuntos, acciones automáticas ni recursos remotos.

## No persistencia

- **Sin tabla**: no hay entidad, migración, seeder ni factory.
- **Sin archivo PDF**: `Dompdf::output()` devuelve una cadena; no se escribe el documento en disco.
- **Sin recurso temporal recuperable**: Dompdf usa un directorio de trabajo por petición para métricas de fuente y temporales; solo contiene esos artefactos, no el PDF; se elimina en `finally`. Si la limpieza falla, no se entrega el PDF.
- **Sin caché de aplicación**: no se cachea el PDF ni la composición.
- **Sin historial ni versión**: cada descarga se regenera; no hay URL posterior.
- **Sin base de datos**: no se crean ni modifican registros, asociaciones, `updated_at` ni archivos de documentos.
- **Sin sesión de contenido**: solo se usa el flash `toast` existente para mensajes de error; no contiene el PDF ni datos del CV.
- **Sin red**: Dompdf se configura con `isRemoteEnabled=false`, sin recursos remotos ni servicios externos.
- **Excepción heredada**: `CvProfileContent::personalData()` usa `Profile::current()` (`firstOrCreate`) y puede crear el singleton vacío del Maestro en una base pristine sin Maestro. Es comportamiento de SPEC-001, no una copia del CV; en uso normal el Maestro ya existe y no se modifica.

## Invariantes

1. Solo se exporta un perfil **activo** con `has_data = true`.
2. La composición es la de SPEC-004; no hay segunda lógica de selección, relevancia, orden, herencia ni contenido.
3. El PDF contiene exactamente las secciones, campos y elementos incluidos, en el mismo orden; omite excluidos, secciones vacías, campos vacíos, metadatos y enlaces internos.
4. El título y el resumen son los propios del perfil; no hay fallback al Maestro.
5. Los datos personales son los del Maestro vigente en el momento de la exportación.
6. El documento es A4 vertical, una columna, con fuentes embebidas y texto real.
7. Los enlaces externos conservan texto y destino cuando son seguros; los inválidos no se activan.
8. El nombre de archivo es determinista, seguro y con fallback.
9. El PDF no se persiste, no se versiona, no se comparte y no queda recuperable.
10. La vista previa de SPEC-004 no cambia y sigue siendo de solo lectura.
11. Si falta el nombre completo, el PDF no imprime un nombre inventado ni el encabezado sr-only «Currículum» de la vista previa; conserva el resto de la cabecera disponible.

## Trazabilidad FR → modelo

| Requisito | Elemento del modelo / regla |
| --- | --- |
| FR-001 | `CvPdfController` exige perfil activo y `has_data`; la UI solo muestra la acción en `ready` con datos. |
| FR-002 | Entrada única `CvPreviewContent::build()`; sin composición paralela. |
| FR-003 | `CvProfileResolver` revalida el perfil activo; `has_data` se comprueba antes de renderizar. |
| FR-004 | Blade A4, una columna, DejaVu Sans, jerarquía y orden. |
| FR-005 | La Blade solo recorre el payload; no incluye `source_links`, nombre del perfil ni metadatos. |
| FR-006 | Formatos equivalentes de fechas y categorías; datos propios/heredados sin fallback. |
| FR-007 | Saltos CSS naturales; sin truncar; sin numeración que altere contenido. |
| FR-008 | Texto real, `nl2br(e())`, DejaVu, `word-wrap`; sin imágenes rasterizadas. |
| FR-009 | `CvPdfLink` y `<a href>`; teléfono visible y `tel:` cuando se puede. |
| FR-010 | Allowlist de esquemas; sin JS/formularios/adjuntos/acciones; metadatos sin datos internos. |
| FR-011 | `CvPdfFilename` con la regla C5. |
| FR-012 | Descarga en memoria; sin persistencia; `no-store`. |
| FR-013 | Dompdf local; `isRemoteEnabled=false`; fuentes embebidas. |
| FR-014 | `CvPdfValidator` antes de entregar. |
| FR-015 | Excepción controlada, `report()`, redirect con toast, reintento. |
| FR-016 | Sin escrituras, migraciones ni cambios en la vista previa. |
| FR-017 | Sin anexos, concatenación, OCR, firma, publicación, correo, ATS, IA ni plantillas múltiples. |

## Pruebas previstas sobre el modelo

| Riesgo / regla | Prueba |
| --- | --- |
| Nombre seguro y estable | Unit de `CvPdfFilename` con acentos, ñ, separadores, `..`, comillas, controles, emojis, vacío y 100 caracteres; Feature de `Content-Disposition`. |
| Estructura completa | Unit de `CvPdfValidator` con PDF válido, vacío, truncado, sin páginas y sin `startxref`; Feature de ausencia de cifrado, JS, formularios, adjuntos y `Launch` en el PDF real. |
| Fidelidad y orden | Feature: incluir/excluir, otro perfil, título/resumen propios, herencia, orden de secciones; texto extraído con parser. |
| Multipágina | Feature: CV extenso, páginas > 1, todo el texto presente una vez, páginas sin texto vacío; estabilidad al repetir. |
| Enlaces | Feature: anotaciones `/URI` con `mailto:`, `tel:`, `http(s)`; sin enlaces internos; URL insegura sin anotación. |
| Disponibilidad y errores | Feature: sin activos, sin selección, inactivo, inexistente, query array, sin datos (incluido el Maestro), fallo de renderizador y de validación; redirect + toast; sin PDF. |
| No persistencia | Feature: conteos y `updated_at` sin cambios; directorio temporal vacío; sin archivos PDF. |
| Sin recursos remotos | Feature: la Blade renderizada no contiene etiquetas de recurso (`img`, `link`, `script`, `iframe`, `object`, `embed`, `form`) ni `url(http...)`; los `http(s)` solo están en `<a href>`; comprobación manual sin red. |
| Vista previa intacta | Regresión de `CvGeneratorTest` y Vitest de `CvPreview`/`CvGeneratorPanel`. |
