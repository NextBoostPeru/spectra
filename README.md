# Spectra Backend

Base de backend PHP con arquitectura limpia/hexagonal, estándares PSR y medidas de seguridad iniciales.

## Requerimientos previos
- PHP 8.1+
- Composer
- Extensión PDO habilitada para el motor elegido (MySQL por defecto)

## Estructura del proyecto
- `app/` código de dominio, aplicación, infraestructura y capa HTTP.
- `config/` bootstrap de entorno y configuración de seguridad.
- `database/` migraciones Phinx, seeds y utilitarios.
- `public/` punto de entrada HTTP.
- `routes/` definición futura de rutas/controladores.
- `storage/` cachés, rate limiting y logs (no versionado).
- `tests/` pruebas automatizadas.
- `docs/` documentación funcional y técnica.

## Primeros pasos
1. Copia el archivo de entorno:
   ```bash
   cp .env.example .env
   ```
2. Ajusta credenciales y seguridad en `.env` (dominios CORS, rate limiting, CSP, `FORCE_HTTPS`).
3. Instala dependencias:
   ```bash
   composer install
   ```
4. Ejecuta chequeos locales:
   ```bash
   composer test
   ```

## Scripts de Composer
- `composer lint`: valida PSR-12 con PHP-CS-Fixer en modo *dry-run*.
- `composer analyse`: análisis estático estricto con PHPStan.
- `composer test`: ejecuta `lint` + `analyse`.
- `composer migrate`: ejecuta migraciones Phinx usando `config/phinx.php`.
- `composer seed`: corre los *seeders* de datos base (países, monedas, permisos y roles por empresa existente).
- `composer format`: aplica formato automáticamente.

## Seguridad desde el día uno
- **HTTPS forzado en producción**: redirección automática si `FORCE_HTTPS=true` y la petición no llega por HTTPS.
- **Cabeceras seguras**: `Strict-Transport-Security` (en HTTPS), `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`.
- **CORS explícito**: define dominios permitidos en `CORS_ALLOWED_ORIGINS` y métodos/headers asociados.
- **Rate limiting**: protección básica por IP/clave sobre endpoints sensibles (`app/Interface/Http/Middleware/RateLimiter`).
- **Logging seguro**: valores sensibles (tokens, contraseñas, documentos) se redactan antes de escribir en logs.
- **Validación estricta**: usar `RequestValidator` en controladores para no confiar en la entrada del cliente.
- **Soft delete**: tablas con columna `deleted_at` deben consultarse usando el alcance `withSoftDeleteScope()` en repositorios PDO.

## Flujo de desarrollo recomendado
1. Implementa casos de uso en `app/Application` y entidades/value objects en `app/Domain`.
2. Añade adaptadores de infraestructura (repositorios, mailer, storage) bajo `app/Infrastructure`.
3. Expone endpoints HTTP en `app/Interface/Http` aplicando validación, CORS y rate limiting donde corresponda.
4. Ejecuta `composer test` antes de commitear.

## Migraciones y base de datos
- Las migraciones usan **Phinx** con configuración en `config/phinx.php`, heredando `utf8mb4` y `utf8mb4_unicode_ci`.
- La base inicial está dividida en módulos: creación de tablas (`database/sql/00_schema_tables.sql`) y llaves/índices (`database/sql/01_indexes_constraints.sql`).
- Ejecuta migraciones y seeds:
  ```bash
  composer migrate
  composer seed
  ```
- Los seeds incluyen países, monedas, permisos base y crean roles por cada compañía existente.
