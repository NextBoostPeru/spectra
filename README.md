# Spectra Backend

Base de backend PHP con arquitectura limpia/hexagonal, estándares PSR y medidas de seguridad iniciales.

## Requerimientos previos
- PHP 8.1+
- Composer
- Extensión PDO habilitada para el motor elegido (MySQL por defecto)

## Estructura del proyecto
- `app/` código de dominio, aplicación, infraestructura y capa HTTP.
- `config/` bootstrap de entorno y configuración de seguridad.
- `database/` scripts utilitarios para migraciones.
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
- `composer migrate`: stub para ejecutar migraciones desde `database/migrate.php`.
- `composer format`: aplica formato automáticamente.

## Seguridad desde el día uno
- **HTTPS forzado en producción**: redirección automática si `FORCE_HTTPS=true` y la petición no llega por HTTPS.
- **Cabeceras seguras**: `Strict-Transport-Security` (en HTTPS), `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`.
- **CORS explícito**: define dominios permitidos en `CORS_ALLOWED_ORIGINS` y métodos/headers asociados.
- **Rate limiting**: protección básica por IP/clave sobre endpoints sensibles (`app/Interface/Http/Middleware/RateLimiter`).
- **Logging seguro**: valores sensibles (tokens, contraseñas, documentos) se redactan antes de escribir en logs.
- **Validación estricta**: usar `RequestValidator` en controladores para no confiar en la entrada del cliente.

## Flujo de desarrollo recomendado
1. Implementa casos de uso en `app/Application` y entidades/value objects en `app/Domain`.
2. Añade adaptadores de infraestructura (repositorios, mailer, storage) bajo `app/Infrastructure`.
3. Expone endpoints HTTP en `app/Interface/Http` aplicando validación, CORS y rate limiting donde corresponda.
4. Ejecuta `composer test` antes de commitear.

## Migraciones
El archivo `database/migrate.php` sirve como punto central para disparar migraciones. Integra aquí tu herramienta preferida (Doctrine Migrations, Phinx, Flyway, etc.) y ejecuta mediante `composer migrate`.
