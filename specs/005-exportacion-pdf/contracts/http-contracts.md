# Contratos HTTP e interfaces: Exportación PDF

**Feature**: `005-exportacion-pdf` | **Fecha**: 2026-10-23
**Referencias**: [spec.md](./spec.md) · [data-model.md](./data-model.md) · [research.md](./research.md) · [SPEC-004 http-contracts](../004-generador-de-cv/contracts/http-contracts.md)

> **Corrección 2026-10-23.** El renderizador pasa a **Browsershot + Chrome headless** y el HTML se produce por **SSR de la página interna `Cv/Print`**, que monta el componente real `CvPreview`. **`GET /cv/pdf` no cambia**: misma ruta, query, headers de éxito, códigos de error, nombre y validación. La página de impresión **no es una ruta pública** y el HTML no incluye app chrome ni `source_links`. Se añade un fallo controlado si SSR o Chrome no están disponibles.

La aplicación es un monolito Laravel + Inertia local y monousuario. La exportación es una operación de **solo lectura** que devuelve un archivo binario, no una página Inertia ni una API JSON. No hay autenticación ni payload de escritura.

## Relación con SPEC-004

- El contrato de `GET /cv` (`cv.index`), sus props, sus estados y sus garantías no cambian. Se reutilizan la composición, la selección del perfil activo y los estados de [SPEC-004](../004-generador-de-cv/contracts/http-contracts.md).
- La única ampliación es una ruta GET de exportación y una acción en la sección, **fuera** de la hoja del CV.
- La exportación reutiliza el componente real `CvPreview` mediante la página interna `Cv/Print`, renderizada por SSR. No se crea una ruta pública de impresión ni se duplica la presentación.
- La hoja sigue siendo de solo lectura y no incorpora controles de exportación, impresión, descarga, edición, plantilla ni guardado.

## Ruta

| Método | URI       | Nombre   | Acción                        | Respuesta                                                  |
| ------ | --------- | -------- | ----------------------------- | ---------------------------------------------------------- |
| GET    | `/cv/pdf` | `cv.pdf` | `CvPdfController` (invocable) | `200 application/pdf` (descarga) o `302` con flash `toast` |

- La ruta se declara explícitamente en `routes/web.php`; no se usa `Route::resource`.
- El perfil se indica con el query **obligatorio** `profile`.
- No existe ninguna otra ruta de exportación: no hay `POST`, `PUT`, `PATCH`, `DELETE`, `cv.export`, `cv.print`, `cv.download`, `cv.template`, `cv.store`, `cv.update`, `cv.destroy`, `cv.regenerate` ni `cv.reorder`.
- Una petición con otro método a `/cv/pdf` responde **405 Method Not Allowed**.
- No se usa route model binding: un perfil inactivo o inexistente no produce 404; se redirige con un toast y sin PDF.

## Página interna `Cv/Print` (no pública)

| Atributo          | Contrato                                                                                                                                            |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| Ruta              | **Ninguna**. No existe `GET /cv/print`, `cv.print` ni un controlador de impresión. `Cv/Print` es un componente Inertia interno.                     |
| Render            | SSR de Inertia (`ssr.tsx` + `bootstrap/ssr/ssr.js`), invocado por `CvPdfRenderer` en la misma petición de exportación.                              |
| Componente        | Solo `<CvPreview preview={preview} />`; sin `AppLayout`, nav, sidebar, selector, botones, `Toaster`, `TooltipProvider` ni `source_links`.           |
| Props             | `preview` con `personal_data`, `professional_title`, `summary`, `content` y `has_data`; sin `source_links` ni nombre del perfil.                    |
| HTML              | Autocontenido: CSS compilado inline, print CSS y `@font-face` Instrument Sans local; sin recursos remotos ni scripts ejecutables.                   |
| Direccionabilidad | No se puede abrir desde el navegador; el SSR escucha en loopback `127.0.0.1:13714`.                                                                 |
| Fallo             | Si el SSR no responde o no devuelve el `<article>`, el renderer lanza una excepción controlada; el controlador redirige con toast y no entrega PDF. |

- Chrome recibe el HTML como archivo temporal `file://`, no una URL de Laravel; no se comparten sesión ni cookies.
- El endpoint SSR no se expone en `0.0.0.0`; el plugin Inertia se configura con `host: '127.0.0.1'`.
- El gateway SSR se acota a `/cv/pdf`: `/cv` y el resto de páginas conservan el fallback CSR. No se cambia el contrato de SPEC-004 ni se activa SSR global.

## Query param `profile`

| Valor                                                                    | Interpretación        | Resultado de la exportación                                        |
| ------------------------------------------------------------------------ | --------------------- | ------------------------------------------------------------------ |
| Ausente                                                                  | No hay selección.     | `302` a `/cv`; toast de error; sin PDF.                            |
| Cadena vacía (`?profile=`)                                               | No hay selección.     | `302` a `/cv`; toast de error; sin PDF.                            |
| Dígitos que representan un entero ≥ 1 y perfil activo                    | Perfil válido.        | `200` PDF si `has_data`; si no, `302` a `/cv?profile=ID`; sin PDF. |
| Dígitos que representan un entero ≥ 1 pero perfil inactivo o inexistente | Perfil no disponible. | `302` a `/cv?profile=ID`; toast; sin PDF.                          |
| Texto, cero, negativo, espacios, array u otro valor                      | Query inválido.       | `302` a `/cv`; toast; sin PDF.                                     |

- Se aplica la misma interpretación que en `GET /cv` de SPEC-004; el resolvedor es compartido.
- No se aceptan arrays (`?profile[]=1`); se consideran inválidos.
- Los parámetros adicionales se ignoran.
- La selección no se persiste en sesión, cookie, `localStorage` ni base de datos; la URL es la única fuente.
- A diferencia de la vista previa, la exportación no tiene un estado `selection_required` propio: si no hay selección, redirige a la página para que el usuario elija.

## Respuesta de éxito

```http
HTTP/1.1 200 OK
Content-Type: application/pdf
Content-Disposition: attachment; filename="cv-analista.pdf"
Content-Length: <bytes>
Cache-Control: no-store, private
Pragma: no-cache
X-Content-Type-Options: nosniff
```

- El cuerpo es el PDF binario, no JSON ni HTML de Inertia.
- `Content-Disposition` es `attachment`; el nombre sale de `CvPdfFilename` y solo contiene `[a-z0-9-]` y `.pdf`.
- `Content-Length` corresponde a los bytes entregados.
- `Cache-Control: no-store` y `Pragma: no-cache` evitan conservar una copia recuperable en cachés intermedias.
- No se establece `Content-Encoding`; la compresión interna de PDF no es compresión HTTP.
- No se envían cabeceras con datos del perfil, rutas internas, relevancia ni estado.

**Ejemplo de nombre**: perfil `Programador / Desarrollador` → `cv-programador-desarrollador.pdf`.

## Respuestas de error y redirección

Todas las situaciones esperadas devuelven `302` y un flash `toast` de tipo `error`; **nunca** un PDF vacío, parcial o corrupto. El toast se muestra fuera del documento mediante `HandleInertiaRequests`/`useFlashToast`.

| Situación                                                | Respuesta      | Destino          | Mensaje orientativo                                                  | PDF                   |
| -------------------------------------------------------- | -------------- | ---------------- | -------------------------------------------------------------------- | --------------------- |
| Sin `profile` o `profile=`                               | `302`          | `/cv`            | «Seleccioná un perfil para exportar el PDF.»                         | No                    |
| Sin perfiles activos                                     | `302`          | `/cv`            | «No hay perfiles activos para exportar.»                             | No                    |
| Query inválido                                           | `302`          | `/cv`            | «El perfil seleccionado no está disponible. Elegí un perfil activo.» | No                    |
| Perfil inactivo o inexistente                            | `302`          | `/cv?profile=ID` | «El perfil seleccionado no está disponible. Elegí un perfil activo.» | No                    |
| Perfil activo con `has_data=false`                       | `302`          | `/cv?profile=ID` | «Este perfil no tiene datos para exportar.»                          | No                    |
| Error al componer                                        | `302`          | `/cv?profile=ID` | «No se pudo generar el PDF. Volvé a intentar.»                       | No                    |
| Error del renderizador                                   | `302`          | `/cv?profile=ID` | «No se pudo generar el PDF. Volvé a intentar.»                       | No                    |
| SSR no disponible, bundle ausente o HTML sin `CvPreview` | `302`          | `/cv?profile=ID` | «No se pudo generar el PDF. Volvé a intentar.»                       | No                    |
| Chrome/Puppeteer no disponible o timeout                 | `302`          | `/cv?profile=ID` | «No se pudo generar el PDF. Volvé a intentar.»                       | No                    |
| Fallo de validación                                      | `302`          | `/cv?profile=ID` | «No se pudo generar el PDF. Volvé a intentar.»                       | No                    |
| Método distinto de GET                                   | `405`          | —                | No aplica.                                                           | No                    |
| Error no controlado fuera del `try/catch`                | `500` estándar | —                | Página/modal de error.                                               | No se inicia descarga |

- El mensaje de error no incluye el texto de la excepción, rutas internas, stack traces ni datos del perfil.
- `report($exception)` registra la excepción para diagnóstico; no registra el PDF, el payload ni el contenido del CV.
- El reintento consiste en volver a pulsar «Exportar PDF»; la selección se conserva en la URL cuando el perfil sigue siendo válido.
- Si el perfil se desactivó o perdió los datos, la página de destino muestra el estado `unavailable` o el estado vacío de SPEC-004 y las alternativas disponibles.
- No hay redirecciones para el caso exitoso.

## Contrato del documento PDF

| Atributo      | Requisito                                                                                                                                                                                                                          |
| ------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Formato       | PDF válido, no vacío, completo, sin contraseña ni restricciones de apertura.                                                                                                                                                       |
| Tamaño        | A4 vertical (210 × 297 mm; MediaBox aproximado 595,28 × 841,89 pt).                                                                                                                                                                |
| Columnas      | Una sola columna.                                                                                                                                                                                                                  |
| Márgenes      | Consistentes; 18 mm en los cuatro lados como valor de Plan.                                                                                                                                                                        |
| Tipografía    | Instrument Sans 400/500/600 embebida y subseteada; texto real y seleccionable, no imagen rasterizada.                                                                                                                              |
| Contenido     | Exactamente las secciones, campos y elementos incluidos en la vista previa, en el mismo orden.                                                                                                                                     |
| Exclusiones   | Sin elementos no incluidos, secciones vacías, campos vacíos, contenido de otro perfil, relevancia, estado, nombre del perfil, `source_links`, controles, logotipos, marcas de agua, fechas visibles ni textos promocionales.       |
| Paginación    | Una o más páginas; saltos naturales de Chrome; sin truncar, ocultar, solapar ni duplicar; sin páginas en blanco evitables; sin encabezados huérfanos.                                                                              |
| Numeración    | Sin numeración de página.                                                                                                                                                                                                          |
| Enlaces       | Anotaciones de URI para los enlaces externos seguros; sin enlaces internos de corrección.                                                                                                                                          |
| Seguridad     | Sin JavaScript, formularios, adjuntos, acciones automáticas, cifrado ni recursos remotos; allowlist de URL antes de SSR.                                                                                                           |
| Metadatos     | Sin relevancia, estado, rutas internas, identificadores de configuración, contenido de otros perfiles ni nombre del perfil. Chrome/Skia puede incluir `Producer`/`CreationDate`/`ModDate` técnicos, que no contienen datos del CV. |
| Autocontenido | Fuentes embebidas; CSS inline; se abre sin conexión y sin solicitar recursos externos.                                                                                                                                             |

## Contrato de enlaces

| Elemento de la vista previa | Texto visible en el PDF | Destino permitido                                                     |
| --------------------------- | ----------------------- | --------------------------------------------------------------------- |
| Teléfono                    | El teléfono original    | `tel:` con dígitos y `+`; si no hay dígitos, sin enlace.              |
| Correo                      | El correo original      | `mailto:` si el correo valida; si no, sin enlace.                     |
| LinkedIn                    | `LinkedIn`              | `http(s)` válido y seguro; si no, texto sin enlace.                   |
| GitHub                      | `GitHub`                | `http(s)` válido y seguro; si no, texto sin enlace.                   |
| Sitio web                   | `Sitio web`             | `http(s)` válido y seguro; si no, texto sin enlace.                   |
| Enlaces adicionales         | La etiqueta del enlace  | `http(s)`, `mailto` o `tel` válido y seguro; si no, texto sin enlace. |
| Validación de certificación | `Validar certificación` | `http(s)` válido y seguro; si no, texto sin enlace.                   |
| URL de proyecto             | `Ver proyecto`          | `http(s)` válido y seguro; si no, texto sin enlace.                   |
| `source_links`              | No se pasa a la vista   | Ninguno.                                                              |

- Los enlaces los renderiza el componente real `CvPreview`; antes de SSR, el renderer aplica una allowlist de URL: `linkedin_url`, `github_url`, `website_url`, `validation_url` y `project.url` solo `http(s)`; `links[].url` también `mailto:`/`tel:` válidos. Un valor no seguro se pasa como `null` para que no se emita un `href` activo.
- `mailto:`/`tel:` de los campos de contacto los construye `CvPreview` desde email/teléfono ya validados; el teléfono permanece visible aunque Chrome o el lector no exporten la anotación.

Reglas de seguridad:

- Esquemas permitidos: `http`, `https`, `mailto`, `tel`.
- La allowlist es común a todos los campos; la tabla indica el caso esperado. Un enlace adicional que sea `mailto:` o `tel:` válido conserva su etiqueta y se activa.
- Se rechazan `javascript:`, `data:`, `file:`, `vbscript:`, `phar:`, rutas relativas, `//`, credenciales embebidas y valores con caracteres de control.
- Un enlace inválido conserva su texto visible, pero no se activa.
- No se emiten anotaciones con JavaScript, formularios, adjuntos, acciones `Launch` ni recursos remotos.
- Las URLs largas pueden aparecer solo en la anotación; el texto visible es una etiqueta corta, por lo que no desbordan la caja.

## Contrato de interfaz

La página `Cv/Index` y sus props no cambian. La acción se añade en `CvGeneratorPanel`:

| Condición                                                                   | Acción «Exportar PDF» |
| --------------------------------------------------------------------------- | --------------------- |
| `status = ready`, `preview.has_data = true`, `selected_profile_id != null`  | Visible.              |
| `status = ready`, `preview.has_data = false`                                | Ausente.              |
| `status = no_active_profiles`, `selection_required`, `unavailable`, `error` | Ausente.              |
| `preview = null` o `selected_profile_id = null`                             | Ausente.              |

- Es un **anchor real** (`<a>`), no un `Inertia Link` ni un botón con `onClick`.
- Usa `Button asChild` para conservar los estilos y el foco visible del sistema de componentes.
- `href="/cv/pdf?profile={selected_profile_id}"`.
- Nombre accesible: «Exportar PDF». No se necesita `aria-label` adicional.
- No lleva `target="_blank"` ni atributo `download`; el nombre y la descarga los controla `Content-Disposition`.
- Está **fuera** del `article` de `CvPreview`; la hoja no contiene la acción.
- Es operable por teclado (Tab + Enter/Espacio) y muestra foco visible.
- No hay controles de plantilla, impresión, edición, guardado, historial ni reordenamiento.
- No se modifica `CvPreview` para añadir la acción.

## Garantías de no mutación y no persistencia

- `GET /cv/pdf` es de solo lectura.
- No escribe en `cv_profiles`, en las cinco pivotes de SPEC-003, en `profiles`, `profile_links`, `work_experiences`, `education_entries`, `certifications`, `skills`, `projects` ni `documents`.
- No actualiza `updated_at` de perfiles ni de registros maestros.
- No crea tablas, columnas, índices, migraciones, seeders, factories, archivos PDF, imágenes, documentos ni históricos.
- No usa caché de aplicación para el PDF ni para la composición.
- No conserva una copia temporal recuperable tras la respuesta: Browsershot escribe un HTML temporal en `storage/app/private/cv-pdf/{uuid}` (permisos `0700`), no el PDF, y el renderer elimina el directorio en `finally`; si la limpieza falla, no se entrega el PDF. El perfil temporal de Chrome se cierra al terminar. Un `kill -9` puede dejar restos; riesgo residual documentado, no una URL recuperable.
- No envía datos a servicios externos ni usa IA.
- No registra el contenido del CV ni el PDF; solo reporta excepciones.
- No expone una URL posterior para recuperar el archivo; cada descarga se regenera.
- El flash `toast` solo contiene el mensaje de error, no el PDF ni datos del CV.
- La única copia es la que recibe el usuario.

**Excepción heredada**: `CvProfileContent::personalData()` usa `Profile::current()` (`firstOrCreate`) y puede crear el singleton vacío del Maestro en una base pristine sin Maestro. Es comportamiento de SPEC-001, no una copia del CV; en uso normal el Maestro ya existe. No se modifica en esta capacidad.

## Límites de alcance del contrato

Este contrato **no** incluye:

- Impresión integrada, descarga alternativa, publicación, envío por correo, compartición o URL pública.
- Certificados anexos, concatenación de PDFs, OCR, CV documentado o selección de evidencias.
- Múltiples diseños, plantillas, variantes por perfil, personalización gráfica o selector de plantilla.
- Edición del PDF, historial, versionado, firma digital, ATS, adaptación a ofertas, postulaciones o generación de contenido por IA.
- Autenticación, multiusuario, sincronización externa, servicios cloud o analítica.
- Cambios en el Perfil Maestro, en los perfiles de CV, en sus entidades, datos o documentos.
- Nuevas clases de contenido, campos, relaciones, migraciones o cambios en SPEC-001/003/004.

Cualquier necesidad de ampliar este contrato debe escalarse y no implementarse incidentalmente (Principios XXV–XXVI).

## Obligaciones de prueba del contrato

Las pruebas Feature deben verificar, como mínimo:

1. `Route::has('cv.pdf') === true`, `GET /cv/pdf` existe y solo acepta GET; otros métodos responden 405.
2. Perfil activo con datos: `200`, `Content-Type: application/pdf`, `Content-Disposition: attachment` con el nombre esperado, `Content-Length` correcto, cuerpo no vacío y `no-store`.
3. PDF válido: `%PDF-`, `%%EOF`, páginas presentes, A4 vertical, fuentes embebidas, sin cifrado, JavaScript, formularios, adjuntos ni acciones `Launch`.
4. Fidelidad: mismas secciones, campos, elementos y orden que la composición de SPEC-004; sin excluidos, otro perfil, relevancia, estado, nombre del perfil ni `source_links`.
5. Título y resumen propios; sin fallback al Maestro; datos personales heredados.
6. Multipágina: todo el contenido completo y una sola vez; sin páginas sin texto; estable al repetir.
7. Enlaces: anotaciones `/URI` con `mailto:`, `tel:` y `http(s)`; mismo texto visible; sin enlaces internos; URL insegura sin anotación.
8. Nombre: regla C5, ausencia de rutas/caracteres peligrosos, estabilidad y seguridad del encabezado.
9. Disponibilidad: sin activos, sin selección, inactivo, inexistente, query array (`?profile[]=1`), sin datos (incluido el Maestro), fallo de renderizador y fallo de validación; redirect + toast; sin PDF.
10. No persistencia: conteos y `updated_at` sin cambios; directorio temporal vacío; sin archivos PDF; el HTML de impresión no contiene etiquetas de recurso remoto ni `url(http...)` (los `http(s)` solo en `<a href>`); configuración sin red y comprobación manual sin conexión.
11. HTML de impresión: contiene el `<article>` real de `CvPreview`, no app chrome ni `source_links`, e incluye `@page` A4/18 mm y `@font-face` local; si SSR está deshabilitado, el renderer falla de forma controlada.
12. Regresión: SPEC-004 sigue pasando; `/cv` y las páginas normales conservan el fallback CSR y no se activa SSR global; la hoja no contiene controles; la acción está fuera del `article`; no hay rutas de escritura, impresión, plantilla, historial ni reordenamiento.

Las pruebas Unit deben cubrir:

- `CvPdfFilename` con la tabla de nombres límite.
- `CvPdfValidator` con PDF válido, vacío, truncado, sin páginas y sin `startxref`. Las ausencias de cifrado, JS, formularios, adjuntos y `Launch` se verifican en Feature sobre el PDF real de Chrome.
- La allowlist de URL del renderer se cubre en Feature con `javascript:`, `data:`, `file:` y una URL `http(s)` válida; no se conserva `CvPdfLink`.

Las pruebas Vitest/RTL deben cubrir:

- Acción visible solo en `ready` con `has_data` y `selected_profile_id`.
- `href` correcto, sin `target`/`download`, fuera del `article`, con foco visible.
- Ausencia en `selection_required`, `unavailable`, `error` y `ready` sin datos.
- La hoja sigue sin controles de exportación.
