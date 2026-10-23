# Fase 0 — Investigación: Generador de CV

**Feature**: `004-generador-de-cv` | **Fecha**: 2026-10-23
**Fuentes**: [spec.md](./spec.md) · [plan.md](./plan.md) · [Constitución](../../.specify/memory/constitution.md) · [SPEC-003 plan](../003-perfiles-cv/plan.md) · [SPEC-003 data-model](../003-perfiles-cv/data-model.md) · código real del repositorio.

**Estado**: todas las decisiones resueltas. No quedan `NEEDS CLARIFICATION`. Las decisiones funcionales ya fueron cerradas en la especificación (C1–C7); esta fase resuelve únicamente las decisiones técnicas.

## Criterio de investigación

La investigación se limitó a los archivos necesarios para validar la integración: `CvProfile` (relaciones y orden), `CvProfileContent` (herencia y contenido disponible), `CvProfileController` (convenciones de rutas y props), modelos maestros y migraciones (campos reales de FR-011), `AppLayout`, `cv-profiles.ts`, `dates.ts`, `Profiles/Show`, `Profiles/Index`, `CvProfileContentTest`, `package.json`, `phpunit.xml`, `routes/web.php` y `composer.json`. No se ejecutaron comandos ni pruebas en esta fase.

## Decisiones

### R1 — Vista derivada y efímera, sin persistencia

**Decisión**: la vista se compone en cada `GET /cv` y no se guarda en ninguna forma. No hay tabla, migración, seeder, factory, archivo, caché, sesión ni historial.

**Motivo**: los Principios I y II y FR-015 exigen que el CV sea un resultado de las fuentes, no una fuente ni una copia. Una vista derivada elimina la invalidación, la sincronización manual y el riesgo de datos obsoletos. Es además la solución más simple: no hay esquema que migrar ni estado que limpiar.

**Alternativas descartadas**:

- Tabla `cv_previews` o `cv_documents` con un snapshot: duplica información, se desactualiza y contradice el Principio I.
- Guardar el payload en JSON, caché o sesión: crea una copia persistente y exige invalidación; el volumen local no lo justifica.
- Generar un archivo HTML/PDF temporal: amplía el alcance a exportación y deja artefactos que la spec prohíbe.

### R2 — `GET /cv` con la selección en el query string

**Decisión**: `GET /cv` (`cv.index`) con perfil opcional mediante `?profile=ID`. La URL es el único lugar donde vive la selección. Sin query, con query vacío o con un valor no válido se aplica la máquina de estados del contrato.

**Motivo**: la operación es una lectura idempotente, no una mutación. El query string permite recargar, volver a abrir y reintentar sin perder la selección, y no requiere sesión ni estado de servidor. También permite que el perfil seleccionado sea identificable durante toda la consulta.

**Alternativas descartadas**:

- `POST /cv` o sesión: introduce escritura, estado oculto y problemas de recarga; no hay formulario que justifique una mutación.
- `/cv/{profile}` con route model binding: un perfil inactivo o inexistente produciría un 404 opaco; FR-005 exige alternativas activas y un aviso comprensible.
- Estado solo en React: se pierde al recargar, no es compartible y no permite que el servidor aplique la disponibilidad.

### R3 — Controlador y página dedicados

**Decisión**: `CvGeneratorController@index` renderiza la página Inertia `Cv/Index`. No se extiende `CvProfileController` ni `Profiles/Show`.

**Motivo**: `Profiles/Show` es una pantalla de configuración y revisión de la selección; el generador es el resultado de esa configuración. Separarlos mantiene la responsabilidad de cada pantalla, evita mezclar `relevance` y `master_path` con la plantilla del CV y protege el comportamiento cerrado de SPEC-003.

**Alternativas descartadas**:

- Añadir la vista previa a `Profiles/Show`: mezcla configuración con resultado, duplica el orden de secciones y añade estados de CV a una pantalla ya cerrada.
- Un componente React que componga desde `options`/`selections` de SPEC-003: expondría elementos no incluidos y duplicaría reglas de orden en el cliente.

### R4 — Nueva clase de composición; `CvProfileContent::content()` intacto

**Decisión**: crear `App\Support\CvPreviewContent` con un método `build(CvProfile $profile): array`. La clase reutiliza `CvProfileContent::personalData()` y las cinco relaciones de `CvProfile`. No se modifica `CvProfileContent::content()`.

**Motivo**: `CvProfileContent::content()` no serializa `responsibilities`, `achievements` ni `results`, que FR-011 exige para experiencia y proyectos; además incluye `relevance` y `master_path`, metadatos que la vista no debe mostrar. Modificarlo para agregar campos cambiaría el contrato de `Profiles/Show` y podría romper SPEC-003. Una clase nueva mantiene la composición del generador aislada y testeable.

**Alternativas descartadas**:

- Extender `content()` con parámetros o campos opcionales: altera un contrato aprobado y obliga a revisar todas las pruebas de SPEC-003.
- Componer en el controlador: mezcla transporte con reglas de mapeo y dificulta las pruebas unitarias.
- Componer en React: el cliente recibiría datos no incluidos y podría mostrarlos por error; además duplicaría la lógica de orden.

### R5 — Datos personales heredados y datos propios sin fallback

**Decisión**: los datos personales y de contacto se obtienen de `CvProfileContent::personalData()`, que lee el Maestro en vivo y excluye título y resumen del Maestro. `professional_title` y `summary` se leen exclusivamente de `cv_profiles`; si están vacíos se normalizan a `null` y no se sustituyen.

**Motivo**: cumple C3 y FR-009/FR-010 sin duplicar lógica ni reabrir SPEC-003. `personalData()` ya calcula `links`, `has_data` y excluye el título del Maestro, exactamente lo que el generador necesita. El fallback al Maestro está prohibido por la especificación.

**Alternativas descartadas**:

- Copiar los datos personales en el payload del perfil: duplicaría información y quedaría obsoleta.
- Usar el título o el resumen del Maestro cuando el perfil no los tiene: contradice FR-009 y la decisión C3.
- Volver a implementar la lectura de datos personales en `CvPreviewContent`: duplicaría `personalData()` y podría divergir.

**Nota de implementación**: `personalData()` usa `Profile::current()` (`firstOrCreate`) y, en una base recién inicializada sin Maestro, puede crear el singleton vacío. Es comportamiento existente de SPEC-001, no una copia del CV; en el uso normal el Maestro ya existe. No se modifica esa clase.

### R6 — Orden calculado en las relaciones; UI sin reordenamiento

**Decisión**: el generador consume las relaciones `workExperiences`, `educationEntries`, `certifications`, `skills` y `projects` de `CvProfile`, que ya aplican relevancia descendente y, en empate, el orden del Maestro. La UI recorre los arreglos en el orden recibido. Habilidades se muestran como lista plana con la etiqueta de categoría.

**Motivo**: SPEC-003 es la única fuente de la regla de orden (FR-008/FR-011/FR-012). Reutilizarla garantiza que el CV y el detalle del perfil coincidan y evita dos implementaciones que puedan divergir. Una lista plana de habilidades conserva el orden relevancia → categoría → nombre; agrupar por categoría después de ordenar podría alterar la prioridad global.

**Alternativas descartadas**:

- Volver a ordenar en `CvPreviewContent` con SQL propio: duplica la regla y puede desincronizarse de SPEC-003.
- Ordenar en React: el servidor ya entrega el orden y el cliente podría alterarlo por error; además impediría probar el contrato en el backend.
- Agrupar habilidades por categoría: rompe o complica el orden por relevancia y añade una jerarquía no exigida.

### R7 — Payload completo de FR-011, sin metadatos internos

**Decisión**: el payload de cada elemento incluye todos los campos de su clase (incluidos `responsibilities`, `achievements` y `results`) e `id` solo como clave de React. No incluye `relevance`, `master_path`, `normalized_name` ni el nombre del perfil dentro de la hoja.

**Motivo**: FR-011 exige mostrar la información registrada; C7 y FR-008 prohíben exponer la relevancia como contenido. Enviar menos campos obliga a consultas adicionales o a inventar datos; enviar metadatos internos facilita filtraciones visuales. El nombre del perfil es una etiqueta interna: se usa en el selector, no como título del CV.

**Alternativas descartadas**:

- Reutilizar el payload de `Profiles/Show`: omite responsabilidades, logros y resultados, e incluye relevancia y `master_path`.
- Enviar `relevance` para que la UI la oculte: un metadato innecesario en el cliente puede terminar mostrado; la regla es más segura en el servidor.
- Incluir el nombre del perfil en la cabecera: contradice la asunción de la spec de que el nombre es una etiqueta interna.

### R8 — Enlaces a fuentes a nivel de sección, fuera de la hoja

**Decisión**: `source_links` contiene la edición del perfil (`/profiles/{id}/edit`), el Perfil Maestro (`/profile`) y las cinco secciones (`/experiences`, `/education`, `/certifications`, `/skills`, `/projects`). Se renderizan en un bloque «Corregir en las fuentes» fuera del `article` del CV; la hoja solo contiene la vista.

**Motivo**: FR-014 pide enlaces identificables al perfil y a las secciones del Maestro, no un editor por elemento. Mantenerlos fuera de la hoja preserva la lectura del CV, evita que parezca editable y permite orientar también las secciones vacías. Los índices de cada sección ya ofrecen la edición de cada registro.

**Alternativas descartadas**:

- Enlaces «Editar» por cada elemento dentro del CV: saturan la lectura y sugieren edición directa; el contrato de SPEC-003 ya resuelve la procedencia en la pantalla de configuración.
- Enlaces solo al perfil sin enlaces al Maestro: no permitiría corregir datos personales ni registros maestros.
- Generar URLs en React con literales dispersos: el servidor las centraliza y las pruebas pueden verificar el contrato.

### R9 — Máquina de estados explícita y `unavailable` con HTTP 200

**Decisión**: la página recibe `status` con cinco valores: `no_active_profiles`, `selection_required`, `unavailable`, `ready` y `error`. Un perfil inactivo, inexistente o un query inválido produce `unavailable` con HTTP 200 y la lista de perfiles activos; no produce 404 ni redirección.

**Motivo**: FR-005 y US4 exigen que el usuario vea un aviso y alternativas, no una página de error. Un 404 sería opaco y perdería el selector; una redirección borraría la causa y ocultaría el problema. El estado `unavailable` es un resultado de negocio esperado, no un fallo del servidor.

**Alternativas descartadas**:

- 404 para perfil inactivo/inexistente: no ofrece alternativas ni explica el estado.
- Redirección a `/cv`: pierde el aviso y puede entrar en un ciclo percibido como pérdida de selección.
- 422: se reserva para validación de formularios; aquí no hay payload de escritura.

### R10 — Fallo de composición controlado y sin vista parcial

**Decisión**: el controlador envuelve la carga y composición en `try/catch (Throwable)`. Ante un fallo, registra con `report($e)` y devuelve `status = error`, `preview = null` y los perfiles/ID seleccionado que haya podido resolver, con un botón de reintento que ejecuta `router.reload()` (conserva el query string). Un fallo de renderizado no controlado sigue produciendo el 500 estándar de Laravel.

**Motivo**: FR-019 exige informar, no presentar una vista parcial y permitir reintentar. El `try/catch` acota el fallo a la composición, que es la parte con lectura de datos; al devolver `preview: null` no puede existir una hoja a medias. El `report()` conserva el diagnóstico en logs. El reintento por recarga mantiene la selección en la URL sin almacenarla.

**Alternativas descartadas**:

- Dejar que la excepción llegue al manejador estándar: no hay estado de reintento dentro de la aplicación y el mensaje es genérico.
- Devolver una vista parcial con las secciones que sí cargaron: viola FR-019 y puede mostrar datos incompletos como válidos.
- Un error boundary de React: la composición ocurre en el servidor; el boundary no puede recuperar el payload y añade complejidad.
- Silenciar la excepción sin registrar: oculta defectos y dificulta el diagnóstico.

**Riesgo aceptado**: un `catch` amplio puede ocultar un error de programación detrás del estado `error`. Se mitiga con `report()`, con pruebas que ejercitan el camino feliz y con la revisión de logs; no se capturan errores de renderizado ni de infraestructura fuera del bloque.

### R11 — Selector nativo con envío explícito, sin preselección

**Decisión**: el selector es un `<select>` nativo dentro de un formulario `GET /cv`, con una opción vacía «Seleccioná un perfil» y un botón «Ver vista previa». Las opciones son solo perfiles activos, con etiqueta `name — professional_title` cuando existe el título. No se selecciona nada automáticamente. El valor mostrado se deriva de `selected_profile_id` (o vacío) en cada respuesta; el control se remonta cuando cambia el estado para no conservar una selección inválida.

**Motivo**: el proyecto ya usa `<select>` nativo para la relevancia en `CvProfileForm`; es accesible por teclado, no requiere un portal ni dependencias adicionales y evita navegar en cada cambio de opción con las flechas. El envío explícito hace visible la acción y permite que el formulario funcione como `GET` estándar si el JS no interviene.

**Alternativas descartadas**:

- Radix/shadcn `Select`: está disponible, pero no se usa en el proyecto, requiere portal y complica las pruebas; no aporta valor para una lista corta.
- Navegar en `onChange`: puede disparar varias peticiones al recorrer opciones con el teclado y no deja claro cuándo se regenera la vista.
- Preseleccionar el primer perfil activo: contradice FR-003 y la asunción de selección explícita.

### R12 — Una sola plantilla, solo lectura, sin acciones de exportación

**Decisión**: `CvPreview` es la única plantilla, de una columna, y no recibe ningún identificador de plantilla. No incluye formularios, botones, campos, drag-and-drop, guardado, impresión, descarga, PDF, CV documentado, selector de plantilla, ATS ni acciones de publicación. Tampoco se añaden estilos `@media print` ni utilidades de exportación.

**Motivo**: FR-012, FR-013 y FR-022, y los Principios XVIII y XXVI. La vista previa debe ser un resultado legible y no una superficie de edición o exportación; añadir impresión o PDF corresponde a SPEC-005 y requeriría una decisión de alcance.

**Alternativas descartadas**:

- Un selector de plantilla o variantes por perfil: fuera del MVP y del spec.
- Un botón «Imprimir» que use `window.print()`: se acerca a la exportación y confunde el alcance; SPEC-005 es la capacidad prevista.
- Estilos de impresión «por si acaso»: implican soporte no solicitado y pueden dar una falsa garantía de PDF.

### R13 — Fechas y estados de vigencia reutilizados

**Decisión**: se usan `formatRange` y `formatYearMonth` de `resources/js/lib/dates.ts`. Experiencia pasa `is_current` con la etiqueta «actual»; educación pasa `is_in_progress` con «en curso»; proyectos no pasan un estado de vigencia y no muestran «actual». Si no hay fechas, no se renderiza el período.

**Motivo**: C6 y FR-011 exigen usar la precisión disponible sin inventar fechas ni normalizarlas. Las utilidades ya implementan año solo, mes abreviado y rangos, y son las mismas que usa SPEC-003. Los proyectos no tienen `is_current` en el modelo; inventarlo violaría la fidelidad.

**Alternativas descartadas**:

- Formatear fechas en el servidor: duplicaría la lógica y mezclaría presentación con composición.
- Añadir un estado «actual» a proyectos sin campo que lo respalde: inventaría información.
- Completar meses o años faltantes: contradice la precisión parcial de SPEC-001.

### R14 — Accesibilidad y robustez textual

**Decisión**: jerarquía `h1` de página → `h2` del CV → `h3` de secciones; `article` con nombre accesible; selector con `<label>`; enlaces con texto identificable; foco visible en controles y enlaces; sin dependencia exclusiva del color; textos con `whitespace-pre-line` y `break-words`, sin truncado ni alturas fijas; la página permite el desplazamiento vertical natural.

**Motivo**: FR-020 y FR-021, y SC-007. La estructura semántica y el texto completo son la forma más simple de cumplir accesibilidad básica sin introducir una librería ni componentes adicionales. El CV puede ser extenso; una hoja de altura natural evita scroll interno anidado y recortes.

**Alternativas descartadas**:

- Altura fija tipo A4 con scroll interno: oculta contenido y complica la lectura; la exportación es otra capacidad.
- `line-clamp` o truncado: oculta información y contradice FR-020.
- Indicar estados solo con color: excluye a usuarios con daltonismo y no cumple FR-021.

### R15 — Pruebas proporcionales y costura para el estado de error

**Decisión**: pruebas Feature en `tests/Feature/CvGeneratorTest.php` para el contrato del servidor (opciones activas, estados, composición, exclusividad, orden, herencia, vigencia, no persistencia, error y método HTTP). Pruebas Vitest/RTL en `CvPreview.test.tsx` y `CvGeneratorPanel.test.tsx` para plantilla, selector, estados vacíos y ausencia de controles. `CvPreviewContent` y su método `build` no son `final`, para permitir un doble de prueba que lance una excepción; no se introduce una interfaz para una sola implementación.

**Motivo**: el Principio XXI considera crítica la generación del CV. Las pruebas del generador deben verificar su contrato, no repetir las pruebas de persistencia, validación y cascadas ya cubiertas por SPEC-003. La costura de error permite probar FR-019 sin depender de un fallo real de SQLite.

**Alternativas descartadas**:

- Probar todo desde Vitest: la composición, la vigencia y las relaciones viven en el servidor.
- Duplicar las pruebas de `CvProfileContentTest`: aumentarían el costo sin cubrir el contrato nuevo.
- Introducir una interfaz `CvPreviewComposer` solo para el test: abstracción innecesaria para una única implementación.
- Forzar el error borrando tablas en un test: puede contaminar el estado y no representa un fallo de composición.

### R16 — Sin dependencias, migraciones ni caché

**Decisión**: no se agregan paquetes, tablas, migraciones, seeders ni factories. No se usa caché de aplicación ni almacenamiento del navegador. La única persistencia es la que ya existe en SPEC-001/SPEC-003.

**Motivo**: la capacidad es una lectura derivada y el stack cubre todo lo necesario. Cada dependencia o tabla nueva aumenta el costo de mantenimiento y el alcance de la revisión. La ausencia de caché garantiza que la siguiente consulta refleje los cambios (FR-016).

**Alternativas descartadas**:

- Cachear el payload por perfil: introduciría invalidación y riesgo de datos obsoletos.
- Guardar la selección en `localStorage`: crearía una selección persistente y podría mostrar una vista que el usuario no eligió en esa sesión.
- Añadir una tabla de metadatos de CV: no hay ninguna necesidad funcional.

### R17 — Integración en la navegación existente

**Decisión**: añadir `{ label: 'Generador de CV', href: '/cv' }` al final de `navigation` en `AppLayout`, después de «Perfiles». No se modifica `useCurrentUrl`: compara el `pathname` y `/cv` no colisiona con `/profiles` ni con `/profile`.

**Motivo**: FR-001 y el Principio XV. La navegación ya resuelve el estado activo por ruta, y `/cv?profile=1` conserva el `pathname` `/cv`, por lo que el ítem queda activo sin cambios adicionales.

**Alternativas descartadas**:

- Un submenú o una ruta anidada bajo `/profiles`: oculta la capacidad y mezcla gestión con generación.
- Modificar `useCurrentUrl` para contemplar el query: no es necesario y afectaría a toda la navegación.

## Riesgos y límites registrados

1. **`Profile::current()` en base pristine**: `personalData()` puede crear el singleton vacío del Maestro si no existe. Es comportamiento previo de SPEC-001; no persiste un CV ni copia contenido. Las pruebas deben crear el Maestro explícitamente para no depender de este efecto.
2. **`catch` amplio en el controlador**: puede ocultar un defecto de programación tras `status = error`. Se mitiga con `report()` y con la revisión de logs; el camino feliz y los estados esperados tienen pruebas propias.
3. **Error de renderizado**: si falla la renderización de Inertia o de React después de la composición, el `try/catch` no lo cubre; Laravel/Inertia muestran el error estándar y no se presenta una hoja parcial. El contrato lo distingue del estado `error`.
4. **Sin validación manual ejecutada**: esta fase es de diseño. `quickstart.md` define el flujo que deberá ejecutarse en Implement/QA; no se declara aprobado.
5. **Datos heredados de otra maestría**: el generador confía en la integridad de SPEC-003 (solo se asocian IDs del Maestro actual). No se revalida la pertenencia en la lectura para no duplicar reglas ni añadir consultas; la base es monousuario y las pivotes se validan al escribir.
6. **Historial del navegador**: Inertia conserva props de navegación en memoria del cliente como cualquier SPA. No es almacenamiento de la aplicación, no se usa como fuente y no se añade caché ni `localStorage`; cada `GET` recompone desde el servidor.

## Evidencia de código consultada

- `CvProfile` define las cinco relaciones `belongsToMany` con `withPivot('relevance')` y orden por relevancia descendente y desempate del Maestro; habilidades usa categoría y nombre. No requiere cambios.
- `CvProfileContent::personalData()` lee `Profile::current()` y `profile_links`, excluye título y resumen del Maestro y calcula `has_data`.
- `CvProfileContent::content()` no incluye `responsibilities`, `achievements` ni `results`; incluye `relevance` y `master_path`. Por eso no se reutiliza para el generador.
- Las migraciones confirman los campos de FR-011: experiencia (`description`, `responsibilities`, `achievements`), educación (`degree_level`, `description`, `is_in_progress`), certificaciones (`duration`, `category`, `code`, `validation_url`), habilidades (`name`, `category`) y proyectos (`role`, `description`, `results`, `url`).
- `resources/js/lib/dates.ts` expone `formatRange` y `formatYearMonth`; `resources/js/lib/cv-profiles.ts` ya tipa datos personales y contenido de SPEC-003.
- `AppLayout` centraliza la navegación; `useCurrentUrl` compara `pathname`, por lo que `/cv?profile=ID` no requiere cambios.
- `routes/web.php` declara rutas explícitas y no usa `Route::resource` para perfiles; se sigue la misma convención para `/cv`.
- `composer.json` y `package.json` confirman PHP 8.3/Laravel 13.17, Pest 4.7, React 19.2, Inertia 3, Vitest/RTL y ausencia de dependencias adicionales necesarias.

## Resultado

No quedan decisiones pendientes ni `NEEDS CLARIFICATION`. El diseño es proporcional: una ruta de lectura, una clase de composición, una página y dos componentes, sin tablas ni dependencias nuevas. Las decisiones de orden, herencia y disponibilidad se delegan en los contratos ya cerrados de SPEC-001 y SPEC-003.
