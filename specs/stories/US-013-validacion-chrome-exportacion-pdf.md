# US-013 — Validación y reutilización de Chrome para la exportación PDF

**Identificador:** US-013
**Estado:** IMPLEMENTADA_ACEPTADA — aceptación funcional y visual registrada el 2026-11-25; integración en `main` mediante fast-forward.
**Aprobación humana:** 2026-11-25 (contrato y aceptación funcional/visual).
**SPEC relacionada:** [SPEC-005 — Exportación PDF](../005-exportacion-pdf/spec.md), como corrección operativa posterior a su cierre.
**Rama de implementación:** `fix/us-013-validacion-chrome-exportacion-pdf`, creada desde `main` en `88fb7d5`.
**Ejecución:** COMPLETADA — implementada, verificada y aceptada; commit de cierre en esta rama; integración en `main` mediante fast-forward. Sin push ni tag.

## Historia de usuario

Como responsable de una instalación de CV Automático, quiero que la aplicación reutilice y valide el Chrome que Browsershot/Puppeteer ya usa para exportar el CV normal a PDF, para no acumular copias ni descargar navegadores sin una decisión explícita.

## Alcance funcional

- Extraer la resolución del ejecutable de Chrome de `CvPdfRenderer` a un componente reutilizable que usen la exportación normal y la validación.
- Respetar `BROWSERSHOT_CHROME_PATH` cuando esté definido y apunte a un ejecutable válido.
- Sin configuración explícita, reutilizar en este orden el Chrome de Puppeteer, un Chrome/Chromium del sistema y un Chrome for Testing ya presente en la caché de Playwright. No descargar ni borrar navegadores en ninguno de esos pasos.
- Añadir un comando de validación que muestre el ejecutable y su origen, compruebe que puede iniciar y generar un PDF de prueba, y devuelva un mensaje accionable si falta, está dañado o es incompatible.
- Hacer explícita la única descarga admitida: `pnpm exec puppeteer browsers install chrome`, ejecutada manualmente una sola vez.
- Integrar una validación no bloqueante en `composer setup` para que una instalación nueva muestre el estado de Chrome sin impedir el arranque de la aplicación.
- Actualizar el README con la dependencia de Chrome en el equipo que ejecuta Laravel, la verificación, la configuración de una ruta existente y el momento de instalar.

## Criterios de aceptación

1. **CA-01 — Resolución única:** la exportación normal y la validación usan la misma resolución de Chrome; `BROWSERSHOT_CHROME_PATH` válido tiene prioridad y una ruta configurada inválida produce un error explícito sin sustituirla silenciosamente.
2. **CA-02 — Reutilización sin descargas:** si existe un Chrome utilizable en la caché de Puppeteer, se reutiliza antes que otras copias; la resolución, el arranque de la aplicación, la validación y cada exportación no ejecutan descargas ni instalaciones.
3. **CA-03 — Mensaje accionable:** si no hay un Chrome válido, o no puede iniciar/generar un PDF, el comando muestra una instrucción concreta para configurar `BROWSERSHOT_CHROME_PATH` o ejecutar una vez `pnpm exec puppeteer browsers install chrome`, y termina con código de error (salvo el modo de aviso usado por el setup).
4. **CA-04 — Verificación real:** el comando valida el ejecutable encontrado generando un PDF de prueba estructuralmente válido; dos ejecuciones consecutivas no crean otra copia ni otro directorio de navegador en la caché.
5. **CA-05 — Documentación:** el README distingue la caché de Puppeteer de la de Playwright, explica que Chrome solo es necesario en el equipo que ejecuta Laravel y aclara que los equipos que solo acceden desde el navegador no necesitan instalarlo.
6. **CA-06 — Compatibilidad:** no se modifican versiones de dependencias, no se borran cachés, no se altera el PDF documentado ni la importación de certificados, y se conservan los contratos y errores actuales de la exportación normal.

## Exclusiones y restricciones

- No automatizar la descarga de Chrome ni ejecutar `puppeteer browsers install` desde el setup, la aplicación o los tests.
- No borrar ni modificar las cachés de Puppeteer o Playwright.
- No cambiar versiones de PHP, Node, Puppeteer, Browsershot, pnpm o Chrome.
- No modificar el CV documentado, la importación de certificados ni la presentación del CV.
- El cierre Git (commit y merge) fue autorizado expresamente el 2026-11-25; no se autoriza push ni tag.
- La aprobación de contenido no autoriza por sí sola la publicación.

## Aceptación humana y verificaciones

- **Aceptación funcional y visual:** 2026-11-25. El responsable probó la exportación normal del CV a PDF y confirmó que funcionó; autorizó el cierre, el commit y el merge a `main`.
- **Validación de Chrome:** comprobada por separado con `php artisan cv:pdf:chrome`, ejecutado dos veces. Generó un PDF de prueba de 30.082 bytes desde el Chrome de Puppeteer y no creó una nueva copia ni cambió el tamaño de la caché.
- **Pruebas focalizadas:** `CvPdfChromeTest` 8/8 (21 aserciones); exportación normal 1/1 (12 aserciones); CV documentado 1/1 (39 aserciones) para comprobar que el renderer compartido no se alteró.
- **Verificaciones técnicas:** Pint y PHPStan focalizados sin errores; `git diff --check` sin observaciones.
- **Setup:** no se ejecutó `composer setup` completo. Su paso de validación de Chrome se comprobó directamente con `php artisan cv:pdf:chrome --warn-only`.
- **Cierre Git:** commit en la rama de implementación y merge por fast-forward a `main`; sin push y sin tag.
