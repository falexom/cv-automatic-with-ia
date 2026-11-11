# Quickstart — Validación del Perfil Profesional Maestro

**Feature**: `001-perfil-profesional-maestro` | **Fecha**: 2026-10-15
**Referencias**: [data-model.md](./data-model.md) · [contracts/http-contracts.md](./contracts/http-contracts.md) · [spec.md](./spec.md)

Guía para ejecutar y validar la capacidad de extremo a extremo una vez implementada. No contiene código de implementación.

## Requisitos previos

- PHP 8.4 con extensiones estándar (pdo_sqlite incluida)
- Composer 2.x
- Node.js 22+ y pnpm 11.1.1 (fijado en `package.json`)
- Navegador de escritorio

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

Abrir `http://localhost:8000`: la raíz redirige a `/profile`.

## Ejecutar pruebas

```bash
php artisan test        # Pest: Feature + Unit (SQLite en memoria)
pnpm run test                # Vitest + React Testing Library (componentes)
```

## Escenarios de validación manual

Cada escenario deriva de los criterios de aceptación del spec y debe pasar antes de dar la capacidad por terminada.

### 1. Persistencia del perfil (FR-027, SC-002)

1. Ir a **Perfil**, completar nombre, título, correo y resumen; guardar.
2. Detener `composer dev` y volver a iniciarlo.
3. Recargar `/profile` ⇒ los datos aparecen idénticos y editables.

### 2. Validaciones del perfil (FR-005, FR-006)

1. Intentar guardar sin nombre ⇒ error "El nombre completo es obligatorio".
2. Ingresar correo `abc@` o enlace `no-es-url` ⇒ errores de formato por campo; no se guarda.

### 3. Experiencia laboral con fechas (FR-007–FR-011, FR-034)

1. Crear experiencia con empresa, cargo; fecha de inicio "mar 2022"; sin fin ⇒ aparece en la lista (orden descendente).
2. Crear segunda experiencia con inicio "2024" (solo año) ⇒ se acepta y se ordena como enero 2024 (por encima de mar 2022).
3. Intentar guardar fin "2021" con inicio "2022" ⇒ bloqueo con mensaje explicando el motivo.
4. Marcar como actual ⇒ no exige fecha de fin; agregar fecha de fin ⇒ deja de mostrarse como actual; quitarla ⇒ vuelve a mostrarse actual.

### 4. Habilidades: catálogo, duplicados y relaciones (FR-019–FR-021)

1. Crear habilidad "PHP" categoría Tecnología y "Análisis de procesos" categoría Funcional.
2. Intentar crear "php " (minúsculas/espacio) ⇒ duplicado bloqueado sugiriendo la existente.
3. Asociar "PHP" a una experiencia, una certificación y un proyecto.
4. Abrir la habilidad "PHP" ⇒ muestra los tres usos (experiencia, certificación, proyecto).
5. Eliminar "PHP" desde el catálogo ⇒ confirmación que indica en cuántos elementos se usa; tras confirmar, los tres registros permanecen intactos sin el vínculo.

### 5. Cursos y certificaciones (FR-015–FR-018)

1. Crear certificación con nombre, institución, fecha "jun 2023", código y URL de validación válida.
2. Crear otra con la misma combinación nombre+institución ⇒ aparece advertencia con el registro existente; "Guardar de todos modos" persiste; cancelar no guarda.
3. Crear una certificación sin fecha ⇒ se guarda y se muestra sin fecha (no se marca incompleta).
4. URL de validación con formato inválido ⇒ error y no guarda.

### 6. Confirmaciones de eliminación (FR-026, SC-007)

1. Intentar eliminar una experiencia ⇒ se muestra diálogo de confirmación con el nombre.
2. Cancelar ⇒ el registro permanece. Confirmar ⇒ desaparece y no reaparece tras recargar.

### 7. Estados vacíos y navegación (FR-028, FR-029, SC-006)

1. En una instalación limpia, abrir cada sección ⇒ estado vacío con orientación y botón para crear.
2. Desde el menú principal, llegar a cualquier sección en ≤ 2 clics.

### 8. Acentos y texto extenso (FR-030, caso límite)

1. Guardar "Ingeniería en Sistemas", "Análisis y diseño — ñandú" ⇒ se muestra correctamente.
2. Pegar una descripción de ~5.000 caracteres en responsabilidades ⇒ se guarda y muestra completa.

### 9. Sin dependencia externa (FR-031)

1. Desconectar la red y repetir los pasos anteriores ⇒ todo funciona igual (sin llamadas externas).

## Resultado esperado

- `php artisan test` y `pnpm run test` en verde.
- Los 9 escenarios manuales pasan.
- El flujo queda listo para la siguiente capacidad (perfiles objetivo y reglas de relevancia).
