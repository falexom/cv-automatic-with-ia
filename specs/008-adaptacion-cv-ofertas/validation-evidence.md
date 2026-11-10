# Evidencia — Validación técnica interna de US-008

**Fecha:** 2026-11-06  
**Historia:** [US-008 — Adaptar CV a una oferta laboral](../stories/US-008-adaptacion-cv-ofertas.md)  
**SPEC:** [SPEC-008 — Adaptación de CV a una oferta laboral](spec.md)  
**Resultado:** SUPERADA — habilita los pasos funcionales dependientes.

## Entorno y autenticación

- Codex CLI `0.166.0`, ejecutado como proceso Node de vida corta.
- Autenticación verificada con `codex login status`: sesión de ChatGPT.
- Modelo solicitado: `gpt-6-luna`.
- Esfuerzo de razonamiento: `xhigh` (Extra High).
- Sesión de Codex efímera (`--ephemeral`), sin persistencia de la sesión.
- Ejecución aislada con directorio temporal vacío, sandbox `read-only`, sin reglas de proyecto ni configuración de usuario (salvo autenticación).
- Herramientas de shell, ejecución unificada, navegador, computer use, navegador integrado, generación de imágenes y apps deshabilitadas. Una prueba de intento de lectura de `/etc/hosts` con estas opciones devolvió `sin herramientas`, sin acceder al archivo.

## Datos utilizados

- Oferta real: **Senior PHP Engineer (Laravel + AWS) — Improving**, publicada el 2026-10-27 en Get on Board (3.700 caracteres).
    - Fuente: `https://www.getonbrd.com/jobs/programming/senior-php-engineer-laravel-aws-improving-remote`
- Datos profesionales existentes de la base local: 6 experiencias, 1 estudio, 32 cursos/certificaciones, 73 habilidades, 5 proyectos y 3 perfiles de CV.
- El catálogo enviado usó referencias opacas por solicitud y solo los campos autorizados por US-008.
- Comprobación estructural: sin claves de datos personales, sin identificadores de base de datos, sin archivos/rutas, sin códigos ni URL de validación.
- No se enviaron nombre personal, teléfono, correo, ubicación personal, enlaces personales, fotografía ni documentos.

## Contrato y ejecución

- Se usó el esquema cerrado de `CvAdaptationSchema` (`schema_version: 1`, `additionalProperties: false` en raíz y objetos anidados).
- La salida fue validada con `CvAdaptationValidator` antes de cualquier uso.
- El proceso no tuvo acceso de escritura ni una operación de negocio para modificar perfiles o registros.
- No se guardó oferta, prompt, respuesta ni propuesta en la aplicación. Las cuentas de la base de datos permanecieron sin cambios antes y después de la prueba.

## Resultado observado

| Comprobación                            | Resultado                                                                 |
| --------------------------------------- | ------------------------------------------------------------------------- |
| Autenticación ChatGPT                   | Correcta                                                                  |
| Acceso a `gpt-6-luna`                   | Correcto                                                                  |
| Esfuerzo `xhigh`                        | Correcto                                                                  |
| Salida estructurada válida              | Correcta                                                                  |
| Versión del contrato                    | `1`                                                                       |
| Operación recomendada                   | `duplicate`                                                               |
| Perfil de origen                        | Perfil activo existente                                                   |
| Requisitos identificados                | 15                                                                        |
| Brechas explicadas                      | 5                                                                         |
| Recomendaciones de desarrollo separadas | 3                                                                         |
| Preguntas aclaratorias opcionales       | 3                                                                         |
| Selecciones propuestas                  | 2 experiencias, 1 estudio, 5 certificaciones, 14 habilidades, 3 proyectos |
| Persistencia durante la validación      | Ninguna                                                                   |
| Latencia observada                      | 82,11 s                                                                   |

## Límites observados

- La latencia corresponde a una única ejecución con `xhigh`; no es una medición de rendimiento ni una garantía de tiempos.
- La prueba valida el acceso y el contrato, no la calidad editorial de cada propuesta.
- La sesión de Codex no se conserva; la aplicación tampoco guarda la oferta, el prompt ni la propuesta.
- No se sustituyó el modelo, el esfuerzo, la autenticación ni la facturación. Se usó la sesión de ChatGPT disponible; no se utilizó una API key.
- La validación se ejecutó con un script temporal de línea de comandos, sin pasar por la interfaz ni agregar persistencia a la aplicación.

## Conclusión

El primer paso interno de US-008 (CA-01) queda superado. Los pasos funcionales dependientes pueden implementarse con el modelo y el esfuerzo aprobados, sin fallback automático ante errores.
