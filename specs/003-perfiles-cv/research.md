# Fase 0 — Investigación y Decisiones Técnicas: Perfiles de CV

**Feature**: `003-perfiles-cv` | **Fecha**: 2026-10-22
**Fuente**: [spec.md](./spec.md) · [plan.md](./plan.md)

Todas las incógnitas técnicas quedaron resueltas. No quedan `NEEDS CLARIFICATION`. Las decisiones funcionales D1–D3 están aprobadas y no se reabren; las decisiones de este documento son técnicas y se ejecutan dentro de la autorización vigente (Clarify → Plan → Tasks → Implement → Validate, sin merge ni push).

---

## 1. Contexto y restricciones

- Aplicación local monousuario: Laravel 13.17, Inertia 3, React 19/TypeScript 5.7, SQLite y Tailwind 4/shadcn-ui ya instalados.
- El Perfil Profesional Maestro es la única fuente de verdad; SPEC-001 no se reabre.
- SPEC-002 (documentos y evidencias) no es dependencia funcional; no se asocian documentos.
- SPEC-004 (generador de CV) es futuro; esta capacidad solo deja preparado el contrato `is_active` y la configuración de contenido.
- Sin dependencias nuevas, sin generación de CV/PDF, sin IA, sin orden manual y sin eliminación definitiva de perfiles.

---

## 2. Identidad del agregado, tabla y rutas

**Decisión**

- Modelo `App\Models\CvProfile`, tabla `cv_profiles` (nombre explícito en el modelo para no depender de la inferencia del pluralizador).
- Rutas `/profiles` con nombres `profiles.index`, `profiles.create`, `profiles.store`, `profiles.show`, `profiles.edit`, `profiles.update`, `profiles.initial`, `profiles.activate` y `profiles.deactivate`.
- Parámetro de ruta `{cvProfile}` con implicit binding a `CvProfile`.
- Se declaran rutas explícitas, no `Route::resource`, para que la ausencia de `destroy`/`DELETE` sea auditable en `routes/web.php`.
- El orden de declaración coloca `/profiles/create` y `/profiles/initial` antes de `/profiles/{cvProfile}` para evitar que el comodín capture esas rutas.

**Rationale**

- `CvProfile` distingue sin ambigüedad el perfil de CV del `Profile` Maestro existente.
- `/profiles` es la ruta natural en español/inglés coherente con `/profile` (Maestro), `/experiences`, `/education`, `/skills` y `/projects`.
- Las rutas explícitas permiten demostrar que no existe `DELETE /profiles/{id}` ni `profiles.destroy`.

**Compatibilidad con la navegación existente**

`AppLayout` calcula el ítem activo con `isCurrentOrParentUrl`, que actualmente usa `startsWith`. Al agregar `/profiles`, el ítem «Perfil» (`/profile`) quedaría activo también en `/profiles` porque `/profiles` empieza con `/profile`. Se corrige la comparación para que una ruta sea padre solo si coincide exactamente o si el siguiente carácter es `/` (por ejemplo, `/experiences/1/edit` sigue considerando padre a `/experiences`, pero `/profiles` no considera padre a `/profile`). Es un ajuste de integración sin cambio funcional para las secciones existentes, con prueba unitaria del helper.

**Alternativas consideradas**

- `/cv-profiles`: evita la colisión de prefijo, pero es menos natural y hace más ruidosa la ruta de usuario; se prefiere corregir la comparación de navegación, que además elimina una ambigüedad latente.
- Reutilizar el modelo `Profile` con un discriminador: rechazado porque mezclaría el Perfil Maestro con configuraciones derivadas y debilitaría la separación del Principio I.
- `Route::resource(...)->except('destroy')`: rechazado porque sigue generando una superficie REST más amplia y no expresa las acciones explícitas de presets y activación.

---

## 3. Persistencia de asociaciones: pivotes explícitos

**Decisión**

Cinco tablas pivote, una por clase:

- `cv_profile_work_experience` (`cv_profile_id`, `work_experience_id`, `relevance`)
- `cv_profile_education_entry` (`cv_profile_id`, `education_entry_id`, `relevance`)
- `cv_profile_certification` (`cv_profile_id`, `certification_id`, `relevance`)
- `cv_profile_skill` (`cv_profile_id`, `skill_id`, `relevance`)
- `cv_profile_project` (`cv_profile_id`, `project_id`, `relevance`)

Cada tabla tiene clave primaria compuesta por los dos IDs, claves foráneas en cascada hacia `cv_profiles` y hacia la entidad maestra, y `relevance` con default `media`. No tiene `id` propio ni timestamps.

**Rationale**

- Garantiza integridad referencial real: al eliminar un registro maestro, SQLite elimina solo las asociaciones; el perfil y las demás asociaciones permanecen (FR-002, FR-008, US3 AS6).
- La clase del elemento queda determinada por la tabla, lo que hace verificable la procedencia (FR-004) sin un discriminador textual.
- La clave primaria compuesta impide duplicados a nivel de datos, además de la validación `distinct` y del `sync`.
- El coste es cinco tablas pequeñas, no cinco modelos ni cinco controladores; las relaciones viven en `CvProfile` y el guardado se centraliza en un helper del controlador.

**Alternativas consideradas**

- Tabla polimórfica `cv_profile_items(cv_profile_id, item_type, item_id, relevance)`: rechazada porque `item_id` no puede tener una FK a cinco tablas distintas; quedarían asociaciones huérfanas ante eliminaciones maestras y haría falta limpieza en aplicación o una auditoría periódica. También debilita la verificación de pertenencia al Maestro.
- Columna JSON en `cv_profiles` con las selecciones: rechazada porque no hay integridad referencial, las consultas de orden y pertenencia se vuelven más frágiles y Eloquent no puede expresar las relaciones de forma natural.
- Una tabla por clase con `id` propio y columnas de contenido copiadas: rechazada por el Principio I; el perfil no debe almacenar copias.

**Consecuencia**

Las pruebas deben verificar explícitamente que las pivotes solo contienen IDs y `relevance`, y que eliminar un registro maestro de cada clase elimina sus filas pivote sin eliminar el perfil ni otros registros.

---

## 4. Representación de la relevancia

**Decisión**

- Valores persistidos: `alta`, `media`, `baja`.
- Default en base de datos, en la validación cuando se omite y en la UI al marcar un elemento: `media`.
- Lista canónica en `CvProfile` y validación con `Rule::in`.
- Orden numérico derivado: Alta = 3, Media = 2, Baja = 1. La consulta aplica una expresión `CASE` única para las cinco relaciones.
- La relevancia se muestra en el detalle como etiqueta legible (Alta/Media/Baja).

**Rationale**

- Es legible en la base y en las pruebas, y coincide con el estilo existente de `skills.category` (valores en español).
- El default `media` cumple FR-010 sin obligar al cliente a enviar relevancia en cada elemento.
- Una expresión de orden centralizada evita que cada relación invente su propio criterio.

**Alternativas consideradas**

- Entero `1..3`: hace trivial el `ORDER BY`, pero pierde legibilidad y exige mapear valores en pruebas, contratos y futura SPEC-004. Con solo tres valores, el `CASE` es suficientemente simple.
- Enum PHP nativo: correcto en tipos, pero el proyecto usa constantes y `Rule::in` (por ejemplo, `Skill::CATEGORIES`); introducirlo solo para esta capacidad agrega un patrón nuevo sin beneficio funcional. Puede evaluarse en una refactorización futura.
- Etiquetas visibles como valores persistidos (`Alta`): rechazado porque la comparación y el orden dependerían de mayúsculas/acentos.

---

## 5. Orden por relevancia y orden del Maestro

**Decisión**

El orden se calcula siempre en el servidor, en este orden:

1. Relevancia descendente (Alta, Media, Baja).
2. En empate, el orden del Perfil Maestro:
   - Experiencia, educación, certificaciones y proyectos: `chronological()` existente, de más reciente a más antiguo, con los registros sin fecha al final y desempate estable por `id` descendente.
   - Habilidades: categoría en el orden técnica, funcional, metodológica, herramienta, tecnología; luego nombre ascendente.

Las relaciones de `CvProfile` incorporan la relevancia y el orden, de modo que el controlador recibe colecciones ya ordenadas. El contrato devuelve arreglos ordenados y la UI no vuelve a ordenar.

**Detalle por clase**

- Experiencia y educación: año de inicio descendente, mes de inicio efectivo (enero si falta) descendente, `id` descendente. Un registro sin año de inicio queda al final porque SQLite ordena `NULL` al final en `DESC`.
- Certificaciones: año y mes de la certificación con la misma regla; sin fecha al final.
- Proyectos: año y mes de inicio con la misma regla.
- Habilidades: rango de categoría y nombre. La unicidad normalizada por perfil hace que no haya empates de nombre; si los hubiera, el orden de la lista maestra es el criterio.

**Rationale**

- Reutiliza la definición que ya usa el Maestro, evitando una segunda semántica de «más reciente».
- Es determinista y reproducible, como exige FR-012.
- No requiere columna de orden ni drag-and-drop; el orden es una regla derivada, no un dato editado.

**Alternativas consideradas**

- Ordenar en PHP después de cargar: rechazado porque duplica la lógica, dificulta las pruebas de orden y puede divergir de la lista maestra.
- Guardar una posición manual por asociación: rechazado por el alcance aprobado (sin orden manual).
- Usar la fecha de fin para «más reciente»: rechazado porque el Maestro ya define el orden por fecha de inicio y el encargo pide reutilizar `chronological()`; cambiarlo introduciría una semántica nueva.

**Riesgo de divergencia**

La lista de habilidades del Maestro codifica el mismo orden de categorías en `SkillController@index`. Para no modificar el comportamiento ni el controlador existente de SPEC-001, se agrega un scope aditivo `masterOrder()` a `Skill` basado en `Skill::CATEGORIES` y se usa en los perfiles. Una prueba compara el orden del perfil con el orden del listado `/skills` para los mismos datos, de modo que cualquier divergencia se detecte sin refactorizar el controlador existente.

---

## 6. Pertenencia de los elementos al Perfil Maestro

**Decisión**

- Cada `id` enviado se valida con `Rule::exists` contra la tabla de su clase y con `profile_id` igual al del Perfil Maestro actual.
- Se aplica `distinct` a los IDs de cada clase para rechazar duplicados en el mismo payload.
- La clave primaria compuesta de cada pivote es la última garantía contra duplicados.
- No se permite crear, editar ni eliminar registros maestros desde el perfil; la UI solo muestra enlaces de navegación a las secciones del Maestro.
- El Perfil Maestro se obtiene con `Profile::current()`; si todavía no existe la fila, se crea vacía como ya ocurre en el resto de la aplicación.

**Rationale**

- Un ID inexistente, de otra clase o de otro Maestro debe rechazarse antes de tocar los pivotes (FR-008).
- La validación en servidor no depende de que la UI solo muestre opciones válidas.
- La FK y la PK compuesta protegen la integridad ante escrituras directas o condiciones de carrera.

**Alternativas consideradas**

- Confiar solo en las opciones que envía la UI: rechazado; un envío directo podría asociar cualquier ID.
- Validar solo la existencia global: rechazado; no demuestra pertenencia al Maestro actual.
- Limpiar asociaciones huérfanas con un job: innecesario con FKs en cascada y fuera del alcance local.

**Consecuencia**

Las pruebas deben cubrir: ID inexistente, ID de otra clase, ID de un segundo Maestro insertado en la base de pruebas (aunque la aplicación asuma un único Maestro), duplicado en el payload y pertenencia válida.

---

## 7. Nombre, normalización y unicidad

**Decisión**

- `cv_profiles.name`: obligatorio, string, máximo 100 caracteres, con la forma escrita para mostrar (mayúsculas, acentos y espaciado interno).
- `cv_profiles.normalized_name`: calculado al guardar con `NameNormalizer` (minúsculas, recorte y colapso de espacios; acentos preservados), con índice único global.
- La validación de duplicado se hace en el controlador antes de persistir, comparando `normalized_name`, para devolver un error de campo claro. Al actualizar se excluye el propio perfil.
- La unicidad no filtra por estado: incluye perfiles activos e inactivos.
- El middleware estándar de Laravel recorta espacios extremos; el valor mostrado conserva mayúsculas, acentos y espacios internos.

**Rationale**

- Cumple FR-013 exactamente: sin distinguir mayúsculas ni espacios sobrantes, conservando la forma escrita.
- Reutiliza el normalizador ya probado por SPEC-001 y su comportamiento de acentos.
- El índice único garantiza la regla a nivel de datos, no solo de formulario.

**Alternativas consideradas**

- Normalizar también acentos: rechazado; el spec solo exige mayúsculas y espacios, y la transliteración produciría falsos duplicados.
- Validar solo en el FormRequest: insuficiente para una regla de datos; se mantiene el índice único.
- Añadir `unique` de Laravel sobre `name`: rechazado porque compararía la forma escrita, no la normalizada.

**Riesgo**

En un entorno multiusuario habría carrera entre la comprobación y la inserción; aquí es monousuario y el índice único sigue siendo la garantía final. No se agrega manejo especial de excepciones de unicidad.

---

## 8. Datos propios frente a datos heredados

**Decisión**

- **Propios del perfil**: `name`, `professional_title`, `summary`, `is_active`.
- **Heredados en lectura**: datos personales y de contacto del Maestro (`full_name`, `phone`, `email`, `location`, `linkedin_url`, `github_url`, `website_url`) y sus enlaces adicionales.
- **No heredados**: el título profesional y el resumen del Maestro. El título del perfil es propio y sustituye al del Maestro en la configuración y en la futura vista; si está vacío, no se muestra ningún título. El resumen del perfil es propio; si está vacío, el perfil sigue siendo válido.
- El detalle del perfil (`Profiles/Show`) compone los datos heredados en vivo; no los guarda ni permite overrides.
- La edición del perfil no incluye campos de datos personales; solo nombre, título, resumen, estado (por acciones) y selecciones.

**Rationale**

- Cumple FR-001, FR-002, FR-014 y FR-015.
- Evita la duplicación y las copias obsoletas: cualquier cambio en el Maestro se refleja al recargar.
- Excluir el título del Maestro del payload de herencia impide mostrarlo accidentalmente junto al título propio.

**Alternativas consideradas**

- Copiar datos personales al crear el perfil: rechazado por el Principio I y por SC-001.
- Permitir overrides por perfil: fuera de alcance y contradice D3.
- Usar el título del Maestro como fallback cuando el perfil no tiene título: rechazado por D3; el perfil debe mostrar su propio título, no el del Maestro.

---

## 9. Estado, perfiles iniciales y ciclo de vida

**Decisión**

- `is_active` booleano, default verdadero; todo perfil nuevo nace activo.
- Acciones explícitas: `POST /profiles/{cvProfile}/activate` y `POST /profiles/{cvProfile}/deactivate`; no hay payload y no se toca el contenido.
- No existe `destroy`, ni botón de eliminación, ni nombre de ruta `profiles.destroy`.
- La acción `POST /profiles/initial` crea exactamente:
  - «Analista», título «Analista de Sistemas», activo, sin resumen ni contenido.
  - «Programador», título «Programador / Desarrollador», activo, sin resumen ni contenido.
- La acción solo escribe si no existe **ningún** perfil; si ya existe alguno, no modifica nada y redirige con un aviso informativo. La creación de ambos se hace en una transacción.
- No hay seeders ni creación automática en migraciones o al abrir la aplicación.
- Los perfiles iniciales, una vez creados, se editan como cualquier otro; renombrarlos o desactivarlos no hace que se vuelvan a crear.

**Rationale**

- Cumple FR-016, FR-017 y FR-018.
- La guarda por «ningún perfil» es la interpretación aprobada en el encargo y evita duplicados, sobrescrituras y recreaciones parciales.
- La transacción evita que quede solo uno de los dos presets si falla el segundo insert.

**Alternativas consideradas**

- Crear presets automáticamente al migrar o al primer acceso: rechazado por D2; la acción debe ser explícita.
- Crear solo el preset que falte: rechazado; podría duplicar o recrear perfiles renombrados y contradice «solo si no existe ninguno».
- Soft delete para retirar perfiles: rechazado; la única retirada es la desactivación reversible y no se implementa eliminación.

**Consecuencia para SPEC-004**

`is_active` es el contrato de disponibilidad: solo los perfiles activos se ofrecerán a la futura generación. Esta capacidad no implementa el filtro ni la generación.

---

## 10. UI, navegación y experiencia

**Decisión**

- `Profiles/Index`: listado de todos los perfiles (activos e inactivos), con nombre, título propio, estado visual y acciones Ver/Editar/Activar o Desactivar. Estado vacío con «Crear perfil» y, solo cuando no hay ninguno, «Crear perfiles iniciales». Sin acción de eliminación.
- `Profiles/Create` y `Profiles/Edit`: formulario compartido `CvProfileForm` con nombre, título, resumen y cinco secciones de selección. Cada elemento del Maestro se muestra con un checkbox; al marcarlo aparece un selector de relevancia con «Media» por defecto. Las secciones sin elementos maestros muestran orientación y un enlace a la sección correspondiente.
- `Profiles/Show`: datos propios, datos personales heredados en modo lectura, resumen propio y contenido agrupado por clase con relevancia y enlace de procedencia al Maestro. Cada sección vacía muestra orientación. Los perfiles inactivos muestran un aviso y la acción Reactivar.
- Navegación: ítem «Perfiles» en `AppLayout` después de «Documentos».
- Componentes existentes: `Button`, `Input`, `Label`, `Card`, `Checkbox`, `Select`, `Badge`, `EmptyState` y `Alert`/toast. No se agregan librerías.
- Se reutilizan `formatRange`/`formatYearMonth` para los períodos y `SKILL_CATEGORY_LABELS` para las categorías; no se duplica el formato de fechas ni las etiquetas.
- El formulario no permite editar datos maestros ni reordenar; solo seleccionar, asignar relevancia y editar los datos propios.
- Tras un error de validación, `useForm` conserva los valores y muestra los errores por campo, incluidos los índices anidados.

**Rationale**

- Cubre FR-005, FR-006, FR-007, FR-009, FR-010 y FR-020 con una UI proporcional al MVP local.
- Evita el drag-and-drop y cualquier editor visual, fuera de alcance.
- Reutilizar componentes mantiene consistencia y no agrega dependencias.

**Alternativas consideradas**

- Una tabla con edición inline: rechazada; más superficie y riesgo de editar maestros desde el perfil.
- Drag-and-drop: fuera de alcance.
- Selector múltiple con búsqueda: innecesario para cientos de registros y no requerido; puede evaluarse si el volumen crece.
- Mostrar el título del Maestro como subtítulo: rechazado por D3.

**Ajuste de navegación**

La corrección de `use-current-url` se prueba con casos `/profile` vs `/profiles` y subrutas como `/experiences/1/edit`. No cambia el aspecto ni las rutas existentes.

---

## 11. Persistencia, transacciones y manejo de errores

**Decisión**

- Guardar perfil y sincronizar las cinco clases dentro de una transacción de base de datos.
- Validar antes de escribir; los errores de validación vuelven con el bolsillo `errors` de Inertia (compatible con 422) y el formulario conserva lo ingresado.
- No usar actualizaciones optimistas en activar/desactivar: la UI refleja el estado que devuelve el servidor.
- SQLite local con FKs activadas; sin caché de datos maestros ni sincronización manual.
- No se envían datos a servicios externos.

**Rationale**

- Cumple FR-019 y FR-020; evita asociaciones parciales si falla una clase.
- Inertia ya preserva el estado del formulario ante errores de validación en el resto de la aplicación.

**Alternativas consideradas**

- Guardar cada clase en una petición separada: rechazado; multiplica estados parciales y complica el manejo de errores.
- Guardar primero y validar después: rechazado; puede dejar datos inconsistentes.
- Reintentos automáticos: innecesarios en local y podrían duplicar efectos.

---

## 12. Estrategia de pruebas

**Decisión**

- **Pest Feature** (`tests/Feature/CvProfileTest.php`): creación, nombre obligatorio/único/normalizado, edición de datos propios, activación/desactivación, perfil sin contenido, herencia de datos personales, título propio, ausencia de `DELETE`, persistencia entre peticiones y acentos.
- **Pest Feature** (`tests/Feature/CvProfileContentTest.php`): selección de las cinco clases, relevancia por defecto y validación, orden por relevancia y orden del Maestro en cada clase (fechas, sin fecha, categorías y nombre), reutilización en dos perfiles, aislamiento, actualización de asociaciones existentes, rechazo de IDs ajenos/duplicados, actualización y eliminación de maestros con preservación de perfiles, y esquema mínimo de los pivotes.
- **Pest Feature** (`tests/Feature/CvProfileInitialProfilesTest.php`): presets exactos, activos, sin resumen/contenido, segunda ejecución sin duplicados, renombrado/desactivado sin recreación, no sobrescritura y acción inerte si ya existe un perfil personalizado.
- **Vitest/RTL** (`resources/js/components/__tests__/CvProfileForm.test.tsx`): las cinco secciones se renderizan, nada está preseleccionado, marcar un elemento muestra relevancia «Media» y desmarcarlo lo excluye; sección vacía muestra orientación.
- **Vitest** (`resources/js/hooks/__tests__/use-current-url.test.ts`): `isCurrentOrParentUrl` distingue `/profile` de `/profiles` y sigue reconociendo subrutas.
- **Manual** (`quickstart.md`): flujo completo, persistencia tras reiniciar, navegación, acentos y límites de alcance.

**Rationale**

- Cubre los puntos críticos del Principio XXI (persistencia, relaciones entre perfiles y contenido, conservación de datos) sin introducir E2E ni Playwright en un MVP local.
- Los Feature tests verifican ruta + validación + base de datos en una pasada.
- Las pruebas de orden comparan el perfil con el listado maestro para detectar divergencias.

**Alternativas consideradas**

- Solo pruebas unitarias de modelos: insuficiente; no cubren validación HTTP, Inertia ni cascadas.
- E2E con navegador: coste/beneficio desfavorable para una app local de un usuario.
- Pruebas de UI para cada página: se prioriza el formulario y la navegación, que concentran el riesgo de interacción.

---

## 13. Riesgos y mitigaciones

| Riesgo                                                           | Probabilidad | Impacto | Mitigación                                                                                                      |
| ---------------------------------------------------------------- | ------------ | ------- | --------------------------------------------------------------------------------------------------------------- |
| Divergencia entre el orden del perfil y el orden del Maestro      | Media        | Alto    | Reutilizar `chronological()`; scope `masterOrder()`; prueba que compara ambos listados.                          |
| «Perfil» activo también en `/profiles`                            | Alta si no se corrige | Medio | Corrección de límite de segmento en `use-current-url` y prueba unitaria.                                        |
| Boilerplate de cinco pivotes                                      | Media        | Bajo    | Nombres consistentes, relaciones en `CvProfile`, un helper de sincronización y pruebas de esquema.               |
| IDs de otro Maestro o inexistentes                                | Baja         | Alto    | `Rule::exists` restringido a `Profile::current()`, `distinct`, PK compuesta y prueba con un segundo Maestro.     |
| Carrera de nombre duplicado                                       | Baja         | Bajo    | Índice único en `normalized_name`; app monousuario.                                                              |
| Asociaciones huérfanas al eliminar un maestro                     | Baja         | Alto    | FKs en cascada en las cinco pivotes y prueba de eliminación por clase.                                           |
| Pérdida de selección por actualización parcial del formulario     | Media        | Medio   | Contrato de reemplazo completo por clase, `sync`, pruebas de desmarcado y errores de validación con `useForm`.   |
| Perfil Maestro vacío                                              | Media        | Medio   | Opciones vacías, estados de orientación y pruebas de perfil sin datos heredados ni contenido.                     |
| Listas maestras extensas sin paginación                           | Baja         | Bajo    | Escala local de cientos de registros; no se introduce paginación en el MVP.                                      |
| Acoplamiento futuro con SPEC-004                                  | Baja         | Medio   | Contrato explícito `is_active` y contenido ordenado; no se implementa generación.                                |
| Eliminación accidental de perfiles                                | Baja         | Alto    | No existe ruta/método/botón de borrado; prueba de ausencia de `profiles.destroy` y respuesta 405 a `DELETE`.     |
| Cambios en datos existentes                                       | Baja         | Alto    | Migración aditiva y reversible; no se modifican tablas ni filas del Maestro; rollback solo afecta datos nuevos.   |

---

## 14. Dependencias

No se agregan dependencias de Composer ni de npm. Se reutilizan Laravel, Inertia, React, Tailwind, shadcn/ui, Pest, Vitest, React Testing Library, `NameNormalizer`, `chronological()` y los componentes existentes. La única incorporación de código compartido es un scope aditivo de orden en `Skill` y la corrección de límite de segmento en `use-current-url`.

---

## 15. Decisiones pendientes de intervención humana

Ninguna. Las decisiones funcionales D1–D3 están aprobadas; las decisiones de este documento son técnicas, proporcionales y quedan dentro de la autorización de ejecución autónoma. No se identifican contradicciones funcionales entre el spec, la Constitución y el código existente.

Si durante Tasks o Implement apareciera una necesidad de cambiar el alcance (por ejemplo, eliminación de perfiles, orden manual, generación de CV o asociación documental), deberá detenerse el trabajo y escalarse; no forma parte de esta capacidad.
