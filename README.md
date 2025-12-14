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
2. Ajusta credenciales y seguridad en `.env` (dominios CORS, rate limiting, CSP, `FORCE_HTTPS`, `TENANT_HEADER`).
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

## Autenticación y sesiones
- **Password hashing**: `PASSWORD_ARGON2ID` (PHP 8.2+) en `PasswordHasher`.
- **JWT**: `JwtTokenManager` emite *access tokens* con `iss`, `aud`, `sub` y `sid`; configura `JWT_SECRET`, `JWT_ACCESS_TTL` y `JWT_REFRESH_TTL_DAYS` en `.env`.
- **Refresh tokens**: valores opacos de 128 caracteres, almacenados como `sha256` en `user_sessions` y rotados en cada `refresh`.
- **Lockout**: `LoginAttemptLimiter` bloquea temporalmente tras `AUTH_MAX_ATTEMPTS` fallidos en ventanas de `AUTH_ATTEMPT_WINDOW`; persistencia en `storage/cache/login-locks`.
- **Rate limiting**: `AuthController` protege `login` por IP usando la política de `config/security.php`.
- **Logout y revocación**: `LogoutUserUseCase` marca la sesión como `revoked` por hash de refresh token.
- **SSO preparado**: `OidcLoginUseCase` valida `state`, `nonce`, `aud`, `iss`, `exp` y usa `user_identities` para vincular Google/Microsoft.
- **Multi-empresa**: los tokens incluyen `company_id` (reclamación configurable) y el middleware `ActiveCompanyResolver` valida que el usuario pertenezca a la empresa indicada en el token o en el header `TENANT_HEADER`. El cambio de contexto emite nuevas credenciales mediante `SwitchActiveCompanyUseCase` (rota refresh token) y `PdoRepository::withCompanyScope()` permite filtrar consultas tenant por `company_id`.

## Convenciones de API (v1)
- **Versionado**: todas las rutas públicas deben colgar de `/api/v1/...`.
- **Respuesta única**: siempre devolver `{ data, meta, errors }`. En éxito `errors` es `[]` y `meta.status` refleja el código HTTP.
- **Errores**: `meta.error_code` y `meta.message` acompañan los códigos `400/401/403/404/409/422/429/500`. Usa `ApiResponse::error()`
  para mantener consistencia y evitar exponer trazas.
- **Paginación estándar**: `PaginationResult` entrega `data` y `meta` con `page`, `page_size`, `total`, `has_next`.
- **Filtros y sorting con whitelist**: valida campos de filtro/orden con `RequestValidator::whitelistFilters()` y `whitelistSort()`
  para evitar inyecciones en consultas.
- **Validación**: todos los endpoints deben validar entrada antes de invocar casos de uso y responder `422` cuando corresponda.

## Autorización (RBAC)
- **Permisos por módulo**: los seeds cargan permisos agrupados (usuarios, roles, proyectos, procurement, timesheets, payroll, soporte) y crean roles por empresa (`Owner`, `Approver`, `Viewer`, `Support`).
- **Middleware can()**: `AuthorizationMiddleware->can('permission.code')` resuelve el `company_user_id` activo y usa caché en memoria para validar permisos por rol; tokens portan `platform_role` para respetar super administradores.
- **Política de proyectos**: `ProjectPolicy::canView()` permite leer proyectos solo si el usuario tiene `projects.view_all`/`projects.manage` o está en `project_members`.
- **Repositorios de control de acceso**: `PermissionRepository` obtiene permisos efectivos por `company_user_id`; `ProjectMemberRepository` verifica membresías en proyectos.

## Core (Fase 1)
- **Companies**: CRUD básico con ajustes fiscales/idioma en `CompanyController` (`store`, `update`, `destroy`, `updateSettings`) y contactos (`addContact`, `removeContact`), respaldado por `CompanyRepository`, `CompanySettingsRepository` y `CompanyContactRepository`.
- **Usuarios y roles**: `UserController` permite crear usuarios con membresía activa en empresa (`CreateUserUseCase`), listar miembros (`ListCompanyUsersUseCase`), sincronizar roles (`SyncUserRolesUseCase`) y crear roles por empresa (`CreateRoleUseCase`). Los repositorios `RoleRepository` y `UserRoleRepository` gestionan catálogos y asignaciones.
- **Auditoría global**: `AuditLoggerMiddleware` persiste en `audit_logs` cada solicitud procesada (actor, company, acción, IP, user-agent) con saneamiento de metadatos sensibles.

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
