# US-009 — Corregir el tipado estático de perfiles de CV

- **Identificador:** US-009
- **Estado:** APROBADA_PARA_DESARROLLO — aprobación explícita concedida en la solicitud actual.
- **SPEC relacionadas:** [SPEC-003 — Perfiles de CV](../003-perfiles-cv/spec.md) y [SPEC-004 — Generador de CV](../004-generador-de-cv/spec.md).
- **Incidente de origen:** ejecución GitHub Actions `42956799349`; el log completo reporta 16 errores PHPStan.

## Historia de usuario

Como responsable del proyecto, quiero que PHPStan compruebe correctamente las consultas, pivotes y datos de prueba de los perfiles de CV, para mantener los controles de CI sin alterar el contenido ni el orden existente.

## Alcance

- Tipar con precisión las expresiones SQL de orden por relevancia en `CvProfile`.
- Expresar el tipo del pivote y de su atributo `relevance` en las relaciones muchos-a-muchos usadas por `CvProfileContent`.
- Asegurar que el nombre generado por `CvProfileFactory` sea una cadena.
- Conservar el orden por relevancia, los desempates cronológicos y por ID, y el contenido profesional generado para los perfiles y CV.

## Criterios de aceptación

1. `composer types:check` termina sin errores, sin baselines, exclusiones, ignores ni cambios al nivel de PHPStan.
2. `CvProfileContentTest` verifica que las cinco relaciones conservan sus relevancias, selección y orden actuales.
3. `CvGeneratorTest` verifica que el CV conserva el contenido y orden por relevancia y sus criterios de desempate.
4. Los nombres creados por la factory siguen siendo cadenas generadas con las palabras actuales.
5. No cambian esquema, datos, dependencias, versión de PHP 8.5, pnpm ni otros controles de CI.

## Fuera de alcance

- Cambiar reglas funcionales, contenido del CV, criterios de orden, esquema o persistencia.
- Silenciar diagnósticos, rebajar el nivel de PHPStan o modificar controles de CI.
- Corregir otros errores PHPStan no incluidos en la ejecución `42956799349`.
