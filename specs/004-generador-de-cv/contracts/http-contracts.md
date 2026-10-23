# Contratos HTTP e Interfaces: Generador de CV

**Feature**: `004-generador-de-cv` | **Fecha**: 2026-10-23
**Referencias**: [spec.md](./spec.md) · [data-model.md](./data-model.md) · [research.md](./research.md)

Aplicación Inertia: cada petición devuelve una página Inertia (HTML + props JSON). No existe una API JSON pública separada ni autenticación: la aplicación es local y monousuario. El generador es una operación de **solo lectura**; no hay payload de escritura, validación de formulario ni flash.

## Ruta

| Método | URI   | Nombre     | Acción                        | Respuesta         |
| ------ | ----- | ---------- | ----------------------------- | ----------------- |
| GET    | `/cv` | `cv.index` | `CvGeneratorController@index` | Página `Cv/Index` |

- La ruta se declara explícitamente en `routes/web.php`; no se usa `Route::resource`.
- El perfil se indica con el query param opcional `profile`.
- **No existe** ninguna otra ruta del generador: no hay `POST`, `PUT`, `PATCH`, `DELETE`, ruta de exportación, impresión, descarga, PDF, plantilla, guardado, historial ni reordenamiento.
- Una petición con otro método a `/cv` responde **405 Method Not Allowed**.
- `GET /cv` no usa route model binding: un perfil inactivo o inexistente no produce 404, sino el estado `unavailable`.

## Query param `profile`

| Valor                                                         | Interpretación                                  | Estado resultante       |
| ------------------------------------------------------------- | ----------------------------------------------- | ----------------------- |
| Ausente                                                       | No hay selección.                               | `selection_required`    |
| Cadena vacía (`?profile=`)                                    | No hay selección.                               | `selection_required`    |
| Cadena de dígitos que representa un entero ≥ 1                | Candidato a perfil; se busca entre los activos. | `ready` o `unavailable` |
| Cualquier otro valor (texto, cero, negativo, espacios, array) | Query inválido.                                 | `unavailable`           |

- Si no existe ningún perfil activo, el estado es `no_active_profiles` independientemente del query.
- Si hay perfiles activos y el ID no corresponde a ninguno activo, el estado es `unavailable`.
- No se aceptan arrays (`?profile[]=1`); se consideran inválidos.
- Los parámetros adicionales se ignoran.
- El query no se persiste en sesión, cookie, `localStorage` ni base de datos; la URL es la única fuente de la selección.

## Props de la página `Cv/Index`

```ts
type CvGeneratorStatus =
    | 'no_active_profiles'
    | 'selection_required'
    | 'unavailable'
    | 'ready'
    | 'error';

type CvProfileOption = {
    id: number;
    name: string;
    professional_title: string | null;
};

type CvPreviewWorkExperience = {
    id: number;
    position: string;
    company: string;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    is_current: boolean;
    description: string | null;
    responsibilities: string | null;
    achievements: string | null;
};

type CvPreviewEducationEntry = {
    id: number;
    institution: string;
    program: string;
    degree_level: string | null;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    is_in_progress: boolean;
    description: string | null;
};

type CvPreviewCertification = {
    id: number;
    name: string;
    institution: string | null;
    date_year: number | null;
    date_month: number | null;
    duration: string | null;
    category: string | null;
    code: string | null;
    validation_url: string | null;
};

type CvPreviewSkill = {
    id: number;
    name: string;
    category: string;
};

type CvPreviewProject = {
    id: number;
    name: string;
    role: string | null;
    start_year: number | null;
    start_month: number | null;
    end_year: number | null;
    end_month: number | null;
    description: string | null;
    results: string | null;
    url: string | null;
};

type CvPreviewSections = {
    work_experiences: CvPreviewWorkExperience[];
    education_entries: CvPreviewEducationEntry[];
    certifications: CvPreviewCertification[];
    skills: CvPreviewSkill[];
    projects: CvPreviewProject[];
};

type CvPreviewPersonalData = {
    full_name: string | null;
    phone: string | null;
    email: string | null;
    location: string | null;
    linkedin_url: string | null;
    github_url: string | null;
    website_url: string | null;
    links: Array<{ id: number; label: string; url: string }>;
    has_data: boolean;
};

type CvPreviewSourceLinks = {
    profile: string;
    personal_data: string;
    work_experiences: string;
    education_entries: string;
    certifications: string;
    skills: string;
    projects: string;
};

type CvPreviewData = {
    personal_data: CvPreviewPersonalData;
    professional_title: string | null;
    summary: string | null;
    content: CvPreviewSections;
    source_links: CvPreviewSourceLinks;
    has_data: boolean;
};

type CvGeneratorPageProps = {
    profiles: CvProfileOption[];
    status: CvGeneratorStatus;
    selected_profile_id: number | null;
    preview: CvPreviewData | null;
};
```

**Invariantes de forma**

- `profiles` contiene **solo perfiles activos**, ordenados por `name` ascendente, con `id`, `name` y `professional_title` (o `null`). No incluye `is_active`, resumen, contenido, relevancia ni datos personales.
- `selected_profile_id` es el ID del perfil activo cuya vista se muestra o se intentó mostrar; es `null` en `no_active_profiles`, `selection_required` y `unavailable`.
- `preview` es no nulo **solo** en `ready`.
- `preview.content` contiene exactamente las cinco claves, siempre presentes, con arreglos vacíos cuando no hay elementos.
- `preview` **no** contiene el nombre del perfil, `relevance`, `master_path`, `normalized_name` ni identificador de plantilla.
- `source_links` siempre tiene las siete claves cuando `preview` existe.

## Ejemplos de payload

### `selection_required`

```json
{
    "profiles": [
        {
            "id": 1,
            "name": "Analista",
            "professional_title": "Analista de Sistemas"
        },
        {
            "id": 2,
            "name": "Programador",
            "professional_title": "Programador / Desarrollador"
        }
    ],
    "status": "selection_required",
    "selected_profile_id": null,
    "preview": null
}
```

- La página muestra el selector sin preselección y solicita elegir un perfil.
- No hay hoja de CV.

### `no_active_profiles`

```json
{
    "profiles": [],
    "status": "no_active_profiles",
    "selected_profile_id": null,
    "preview": null
}
```

- La página muestra un estado vacío con enlace a `/profiles`.
- No hay selector ni hoja de CV.

### `unavailable`

```json
{
    "profiles": [
        {
            "id": 1,
            "name": "Analista",
            "professional_title": "Analista de Sistemas"
        }
    ],
    "status": "unavailable",
    "selected_profile_id": null,
    "preview": null
}
```

- Ocurre con un `profile` inactivo, inexistente o inválido.
- La página muestra un aviso y el selector con las alternativas activas.
- No hay hoja de CV.

### `ready`

```json
{
    "profiles": [
        {
            "id": 1,
            "name": "Analista",
            "professional_title": "Analista de Sistemas"
        }
    ],
    "status": "ready",
    "selected_profile_id": 1,
    "preview": {
        "personal_data": {
            "full_name": "Ada Lovelace",
            "phone": "+54 11 5555 1234",
            "email": "ada@example.com",
            "location": "Buenos Aires, Argentina",
            "linkedin_url": "https://www.linkedin.com/in/ada",
            "github_url": "https://github.com/ada",
            "website_url": "https://ada.example.com",
            "links": [
                {
                    "id": 1,
                    "label": "Blog",
                    "url": "https://blog.example.com"
                }
            ],
            "has_data": true
        },
        "professional_title": "Analista de Sistemas",
        "summary": "Resumen propio del perfil.",
        "content": {
            "work_experiences": [
                {
                    "id": 12,
                    "position": "Analista funcional",
                    "company": "Acme",
                    "start_year": 2022,
                    "start_month": 3,
                    "end_year": null,
                    "end_month": null,
                    "is_current": true,
                    "description": "Relevamiento y documentación de procesos.",
                    "responsibilities": "Relevamiento de requerimientos.\nDocumentación funcional.",
                    "achievements": "Reducción de tiempos de proceso en un 30%."
                }
            ],
            "education_entries": [
                {
                    "id": 3,
                    "institution": "Universidad",
                    "program": "Sistemas",
                    "degree_level": "Licenciatura",
                    "start_year": 2015,
                    "start_month": 2,
                    "end_year": 2020,
                    "end_month": 12,
                    "is_in_progress": false,
                    "description": "Formación en análisis y diseño de sistemas."
                }
            ],
            "certifications": [
                {
                    "id": 4,
                    "name": "Curso de Laravel",
                    "institution": "Academy",
                    "date_year": 2023,
                    "date_month": 6,
                    "duration": "40 horas",
                    "category": "Desarrollo web",
                    "code": "LAR-2023-001",
                    "validation_url": "https://academy.example.com/validar/LAR-2023-001"
                }
            ],
            "skills": [
                {
                    "id": 7,
                    "name": "PHP",
                    "category": "tecnologia"
                }
            ],
            "projects": [
                {
                    "id": 9,
                    "name": "Portal interno",
                    "role": "Desarrolladora",
                    "start_year": 2021,
                    "start_month": 1,
                    "end_year": 2021,
                    "end_month": 9,
                    "description": "Migración del portal corporativo.",
                    "results": "Reducción del tiempo de carga en un 40%.",
                    "url": "https://example.com/proyecto"
                }
            ]
        },
        "source_links": {
            "profile": "/profiles/1/edit",
            "personal_data": "/profile",
            "work_experiences": "/experiences",
            "education_entries": "/education",
            "certifications": "/certifications",
            "skills": "/skills",
            "projects": "/projects"
        },
        "has_data": true
    }
}
```

### `ready` con `has_data: false`

```json
{
    "profiles": [
        {
            "id": 1,
            "name": "Analista",
            "professional_title": "Analista de Sistemas"
        }
    ],
    "status": "ready",
    "selected_profile_id": 1,
    "preview": {
        "personal_data": {
            "full_name": null,
            "phone": null,
            "email": null,
            "location": null,
            "linkedin_url": null,
            "github_url": null,
            "website_url": null,
            "links": [],
            "has_data": false
        },
        "professional_title": null,
        "summary": null,
        "content": {
            "work_experiences": [],
            "education_entries": [],
            "certifications": [],
            "skills": [],
            "projects": []
        },
        "source_links": {
            "profile": "/profiles/1/edit",
            "personal_data": "/profile",
            "work_experiences": "/experiences",
            "education_entries": "/education",
            "certifications": "/certifications",
            "skills": "/skills",
            "projects": "/projects"
        },
        "has_data": false
    }
}
```

- La página muestra un estado vacío fuera de la hoja, con enlaces al perfil y al Maestro.
- **No** se renderiza una hoja en blanco.

### `error`

```json
{
    "profiles": [
        {
            "id": 1,
            "name": "Analista",
            "professional_title": "Analista de Sistemas"
        }
    ],
    "status": "error",
    "selected_profile_id": 1,
    "preview": null
}
```

- `profiles` y `selected_profile_id` son **best effort**: se incluyen si pudieron resolverse antes del fallo; de lo contrario, `[]` y `null`.
- La página muestra una alerta y un botón de reintento; no muestra hoja ni datos parciales.
- El reintento recarga la URL actual, conservando `?profile=ID`.

## Significado de los estados

| Estado               | Condición                                                   | `preview` | Hoja de CV                             | Acciones de la página                                      |
| -------------------- | ----------------------------------------------------------- | --------- | -------------------------------------- | ---------------------------------------------------------- |
| `no_active_profiles` | No existe ningún perfil activo.                             | `null`    | No                                     | Enlace a `/profiles` para crear o activar.                 |
| `selection_required` | Hay activos y no hay perfil seleccionado.                   | `null`    | No                                     | Selector sin preselección y envío explícito.               |
| `unavailable`        | Hay activos y el query es inválido, inactivo o inexistente. | `null`    | No                                     | Aviso y selector con alternativas activas.                 |
| `ready`              | Hay un perfil activo seleccionado y la composición terminó. | Objeto    | Sí, si `has_data`; si no, estado vacío | Selector, hoja/estado vacío, enlaces a fuentes y perfil.   |
| `error`              | Falló la carga o la composición.                            | `null`    | No                                     | Alerta y reintento; sin pérdida de la selección en la URL. |

**Regla de `has_data`**

`has_data` es `true` si existe al menos uno de: datos personales o enlaces del Maestro (`personal_data.has_data`), título propio no vacío, resumen propio no vacío, o alguna de las cinco colecciones con elementos. El nombre del perfil y los metadatos no cuentan.

## Orden garantizado

- El servidor devuelve las cinco colecciones **ya ordenadas** por la regla de SPEC-003:
    - relevancia descendente: Alta → Media → Baja;
    - empate: orden del Perfil Maestro (fecha de inicio más reciente primero, sin fecha al final, `id` descendente como desempate estable; habilidades por categoría y luego nombre).
- La UI **no debe** ordenar, agrupar por relevancia, invertir ni reordenar los arreglos. Solo los recorre.
- Habilidades se muestran como lista plana con etiqueta de categoría; no se agrupan para no romper el orden por relevancia.
- El orden de las secciones es fijo: datos personales y contacto, título, resumen, experiencia, educación, cursos y certificaciones, habilidades, proyectos.
- No hay parámetros de orden, endpoint de reordenamiento, drag-and-drop ni controles de orden.
- Repetir la misma petición con los mismos datos produce el mismo orden y el mismo payload.

## Garantías de no mutación

- `GET /cv` es de solo lectura.
- No escribe en `cv_profiles`, en las cinco pivotes de SPEC-003, en `profiles`, `profile_links`, `work_experiences`, `education_entries`, `certifications`, `skills` ni `projects`.
- No actualiza `updated_at` de perfiles ni de registros maestros existentes.
- No crea, elimina ni modifica asociaciones, relevancias, títulos, resúmenes ni datos personales.
- No crea tablas, columnas, índices, archivos, documentos, PDFs, imágenes ni históricos.
- No usa caché de aplicación, sesión, cookies, `localStorage`, `sessionStorage` ni IndexedDB para la vista o la selección.
- No envía datos a servicios externos ni ejecuta IA.
- No registra un CV en logs; el payload no se persiste.
- El query string es el único estado de selección; no hay historial de servidor ni versionado.
- La respuesta no incluye `relevance`, `is_active`, `master_path` ni el nombre del perfil dentro de la hoja.

**Única excepción heredada**: `CvProfileContent::personalData()` usa `Profile::current()` (`firstOrCreate`) y puede crear el singleton vacío del Maestro en una base pristine sin Maestro. Es comportamiento de SPEC-001, no una copia del CV; no se modifica en esta capacidad.

## Errores y fallos

| Situación                                                | Respuesta                                                                     | UI                                                                      |
| -------------------------------------------------------- | ----------------------------------------------------------------------------- | ----------------------------------------------------------------------- |
| Perfil inactivo, inexistente o query inválido            | **200** con `status = unavailable`, `preview = null` y alternativas.          | Aviso; selector de activos; sin hoja.                                   |
| Sin perfiles activos                                     | **200** con `status = no_active_profiles`.                                    | Estado vacío; enlace a `/profiles`; sin hoja.                           |
| Fallo de carga o composición (excepción capturada)       | **200** con `status = error`, `preview = null`; `report()` registra el error. | Alerta; reintento; sin vista parcial.                                   |
| Fallo de renderizado o error no controlado del framework | **500** estándar de Laravel/Inertia.                                          | Página/modal de error; no se genera una hoja parcial desde el servidor. |
| Método distinto de `GET` en `/cv`                        | **405 Method Not Allowed**.                                                   | No aplica.                                                              |
| `DELETE /cv`                                             | **405**.                                                                      | No aplica.                                                              |
| Perfil inexistente en el query                           | **200** `unavailable`; **no** 404.                                            | Aviso y alternativas.                                                   |
| Error de validación de formulario                        | No aplica: no hay payload de escritura.                                       | No aplica.                                                              |

- El estado `error` no presenta una vista parcial ni una versión anterior como válida.
- El reintento usa la URL actual, de modo que `?profile=ID` se conserva cuando existe.
- No hay redirecciones para los estados esperados.
- No hay flash `toast` en esta capacidad: no se completan acciones de escritura.

## Límites de alcance del contrato

Este contrato **no** incluye:

- Exportación a PDF, impresión, descarga, publicación, envío ni postulaciones.
- CV documentado, concatenación o asociación de documentos y evidencias.
- Múltiples plantillas, selector de plantilla, variantes visuales o personalización por perfil.
- Edición directa, guardado, historial, versionado, drag-and-drop o reordenamiento.
- ATS, adaptación a ofertas, cartas de presentación o generación por IA.
- Cambios en el Perfil Maestro, en los perfiles de CV, en sus entidades o en sus datos.
- Autenticación, multiusuario, sincronización externa, servicios cloud o analítica.
- Nuevas clases de contenido, campos del Maestro o migraciones.

Cualquier necesidad de ampliar este contrato debe escalarse y no implementarse incidentalmente (Principios XXV–XXVI).

## Obligaciones de prueba del contrato

Las pruebas Feature deben verificar, como mínimo:

1. `Route::has('cv.index') === true` y `GET /cv` responde con el componente `Cv/Index`.
2. `profiles` contiene solo activos, ordenados por `name`, con `id`, `name` y `professional_title`; ninguna clave adicional.
3. Sin selección: `selection_required`, `preview = null`; no se compone la vista.
4. Perfil activo: `ready`, `selected_profile_id` correcto y `preview` con las cinco colecciones.
5. Solo elementos incluidos; sin contenido de otro perfil; arreglos vacíos para secciones sin elementos.
6. `preview.content.*` no contiene `relevance` ni `master_path`; `preview` no contiene el nombre del perfil, `is_active` ni identificador de plantilla.
7. Orden por relevancia y desempate del Maestro; habilidades en orden relevancia → categoría → nombre.
8. Título y resumen propios; sin fallback al Maestro cuando son `null`.
9. `personal_data` sin `professional_title`/`summary` del Maestro; enlaces y `has_data` correctos.
10. Campos FR-011 presentes, incluidos `responsibilities`, `achievements` y `results`.
11. `source_links` exactos y fuera de la hoja en la UI.
12. Perfil inactivo/inexistente/query inválido: `unavailable`, `preview = null`, alternativas activas.
13. Sin activos: `no_active_profiles`, `profiles = []`.
14. Perfil sin datos: `ready` con `has_data = false`; sin hoja en la UI.
15. Error de carga o composición: `status = error`, `preview = null`, sin vista parcial.
16. No persistencia: conteos y `updated_at` sin cambios; ausencia de migraciones nuevas verificada por revisión.
17. Solo `GET`; otros métodos responden 405.
18. Textos largos, acentos y caracteres especiales llegan completos.

Las pruebas Vitest/RTL deben verificar la plantilla y los estados: jerarquía de encabezados, campos y omisiones, fechas, enlaces, ausencia de controles de edición/orden/plantilla/exportación, selector sin preselección, estados vacíos y reintento.
