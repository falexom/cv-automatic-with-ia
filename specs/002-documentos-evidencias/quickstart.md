# Quickstart — Validación de Documentos y Evidencias

**Feature**: `002-documentos-evidencias` | **Fecha**: 2026-10-15
**Referencias**: [data-model.md](./data-model.md) · [contracts/http-contracts.md](./contracts/http-contracts.md) · [spec.md](./spec.md)

Guía para ejecutar y validar la capacidad de extremo a extremo una vez implementada. No contiene código de implementación.

## Requisitos previos

- PHP 8.4 con extensiones estándar (pdo_sqlite incluida)
- Composer 2.x
- Node.js 22+ y pnpm 11.1.1 (fijado en `package.json`)
- Navegador de escritorio con visor PDF integrado (Chrome, Edge, Firefox o Safari)

### Configuración PHP recomendada para cargas

El límite del producto es 20 MB por archivo. El entorno PHP debe permitir ese tamaño:

```ini
; php.ini (o configuración del SAPI en uso)
upload_max_filesize = 25M
post_max_size = 25M
```

Sin este ajuste, PHP descarta la carga antes de llegar a la aplicación y el formulario mostrará el mensaje específico de configuración.

## Puesta en marcha

```bash
# 1. Dependencias
composer install
pnpm install --frozen-lockfile

# 2. Configuración local (sin secretos en el repositorio)
cp .env.example .env
php artisan key:generate

# 3. Base de datos local SQLite
touch database/database.sqlite   # si no existe
php artisan migrate

# 4. Desarrollo (servidor Laravel + Vite)
composer dev
```

Abrir `http://localhost:8000` y navegar a **Documentos** desde la barra principal.

## Ejecutar pruebas

```bash
php artisan test        # Pest: Feature + Unit (SQLite en memoria, Storage::fake('local'))
pnpm run test                # Vitest + React Testing Library (componentes)
```

## Validación manual por historia de usuario

### US1 — Cargar, conservar y visualizar

1. En **Documentos → Subir documento**, cargar un PDF válido con título opcional. Verificar que aparece en el listado con nombre, tamaño y fecha (SC-002).
2. Abrir **Ver**: el visor muestra el PDF original con todas sus páginas (SC-007). Cerrar y reabrir la aplicación: el documento sigue idéntico y visualizable (SC-001).
3. Subir un archivo que no es PDF (por ejemplo, un `.txt` renombrado como `.pdf`): se rechaza con mensaje claro (SC-003).
4. Subir un PDF truncado o dañado: se rechaza (SC-003).
5. Subir un archivo mayor a 20 MB: se rechaza indicando el límite (SC-003).
6. Subir dos veces el mismo archivo (por ejemplo, a entidades distintas): el sistema informa la reutilización y no almacena una segunda copia (SC-008). Comprobar en `storage/app/private/documents/` que existe un solo archivo para ese contenido.

### US2 — Asociar a cursos y certificaciones

1. En una certificación existente (**Editar**), subir un PDF desde la tarjeta **Documentos**: queda asociado y visible tanto en la certificación como en el repositorio (SC-004).
2. Asociar un segundo documento y verificar que ambos se listan y se visualizan.
3. **Quitar** uno: el documento queda "Sin asociar" en el repositorio y el archivo permanece.
4. Desde el repositorio (**Asociar**), vincular un documento pendiente a otra certificación; verificar la visibilidad en ambos lados.
5. Eliminar la certificación con documentos asociados: el aviso lo anticipa; tras confirmar, los documentos siguen en el repositorio como no asociados (SC-005).
6. Eliminar un documento asociado: la confirmación indica la entidad; tras confirmar, la certificación permanece intacta.

### US3 — Asociar a educación y experiencia

Repetir los pasos de US2 sobre una entrada de educación y sobre una experiencia laboral (subida desde la entidad, desasociación, eliminación de la entidad conserva documentos).

### US4 — Gestionar el repositorio

1. Con documentos asociados y sin asociar, verificar el listado con estado y entidades; probar filtros por estado y por entidad y la búsqueda por título/nombre.
2. Editar título y descripción: los cambios se reflejan sin alterar el archivo (el tamaño y el checksum no cambian).
3. **Verificar integridad**: el reporte indica totales y documentos no disponibles.
4. Mover o borrar manualmente un PDF de `storage/app/private/documents/` y abrir su visor: se marca "No disponible" con motivo, sin regeneración (SC-010).
5. Modificar el contenido de un PDF almacenado y ejecutar la verificación: se detecta como alterado (SC-010).
6. Eliminar un documento: confirmación explícita, indicando asociaciones (SC-006); verificar que el archivo desaparece del disco y las entidades siguen intactas.
7. Repositorio vacío: se muestra el estado vacío con orientación.

## Verificación de la conservación del original (SC-001)

1. Calcular el hash del PDF antes de subirlo: `shasum -a 256 archivo.pdf`.
2. Subirlo, visualizarlo, asociarlo, desasociarlo y editar sus metadatos.
3. Volver a calcular el hash del archivo en `storage/app/private/documents/{checksum}.pdf`: debe coincidir exactamente.
4. Reiniciar la aplicación y repetir la comparación: sin cambios.

## Casos límite a comprobar

- PDF protegido con contraseña: se conserva sin alteración; sin detección avanzada, el visor nativo puede solicitar la contraseña o informar que no puede abrirlo (FR-019).
- Archivos con el mismo nombre y contenido distinto: coexisten y se distinguen sin ambigüedad.
- Nombres de archivo con acentos, espacios y caracteres especiales: se muestran correctamente (FR-030).
- Interrupción de una carga: no queda documento incompleto ni archivo parcial (SC-009).
- Persistencia: tras reiniciar la aplicación, documentos, metadatos y asociaciones permanecen (FR-008).
