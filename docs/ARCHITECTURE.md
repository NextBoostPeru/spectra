# Arquitectura limpia y hexagonal

Este backend sigue principios de Clean Architecture/Hexagonal, separando responsabilidades en cuatro capas claramente definidas. Las dependencias solo apuntan hacia adentro (Interface → Application → Domain), mientras que Infrastructure implementa detalles para los puertos definidos por Application/Domain.

## Capas

- **Domain** (`app/Domain`):
  - Entidades, Value Objects y reglas de negocio.
  - Excepciones de dominio (`DomainException`).
  - Contratos de repositorio que describen *qué* se necesita, no *cómo* se persiste.
- **Application** (`app/Application`):
  - Casos de uso (`Contracts/UseCase`) y DTOs de entrada/salida.
  - Políticas y validaciones específicas de aplicación.
  - Formatos comunes como `PaginationRequest` y `PaginationResult`.
  - Excepciones de aplicación (`ApplicationException`).
- **Infrastructure** (`app/Infrastructure`):
  - Implementaciones técnicas de contratos: persistencia, colas, mailer, storage, logging.
  - `Persistence/PdoRepository` provee manejo consistente de errores SQL.
  - `Logging/Logger` centraliza la escritura a `error_log` con metadatos JSON.
- **Interface/HTTP** (`app/Interface/Http`):
  - Controladores finos y adaptadores HTTP.
  - Validación de requests (`RequestValidator`), middleware y formateo de respuestas (`ApiResponse`).

## Convenciones

- **Nombres**: clases con verbo-sustantivo (`CreateAssignmentUseCase`), repositorios con sufijo `Repository`, controladores con sufijo `Controller`.
- **Excepciones**: usar `DomainException` para reglas de negocio y `ApplicationException` para errores de caso de uso o infraestructura traducida.
- **Errores**: los adaptadores HTTP devuelven cuerpos JSON `{ data, meta, errors }`; `meta.status` refleja el código HTTP y `meta.error_code` sigue la convención `bad_request/unauthorized/forbidden/not_found/conflict/validation_failed/too_many_requests/server_error`.
- **Logging**: usar `App\Infrastructure\Logging\Logger` con canales por módulo (`new Logger('billing')`). Los contextos se serializan a JSON.
- **Paginación**: `PaginationRequest` define entrada estándar (`page`, `pageSize`, `sortBy`, `direction`) y `PaginationResult` entrega `data` + `meta` (`total`, `page`, `page_size`, `has_next`).
- **Filtros y sorting**: controla campos permitidos con `RequestValidator::whitelistFilters()` y `whitelistSort()` para evitar inyección de columnas.
- **Soft delete**: cuando la tabla incluye `deleted_at`, aplica el helper `withSoftDeleteScope()` de `PdoRepository` para excluir registros eliminados lógicamente en cada consulta.
- **Multi-tenant**: toda consulta a datos de compañía debe incluir el helper `withCompanyScope()` usando el `company_id` resuelto por `ActiveCompanyResolver` o derivado del token JWT.

## Flujo de dependencias

1. Los controladores reciben y validan requests (Interface/HTTP).
2. Invocan casos de uso (`UseCase`) con DTOs o arreglos simples (Application).
3. Los casos de uso orquestan entidades y repositorios definidos en Domain.
4. Infrastructure implementa los contratos usando tecnología concreta (PDO, colas, storage) y traduce errores a excepciones de Application/Domain.

## Recomendaciones operativas

- Mantener los Value Objects inmutables y comparar con `equals()`.
- Las respuestas HTTP deben usar `ApiResponse::success()` o `ApiResponse::error()` para uniformidad.
- Registrar eventos relevantes vía `Logger` y complementar con auditorías en Infraestructura.

## Módulos de operación

- **Onboarding**: checklists e ítems viven en `onboarding_checklists` y `onboarding_items`; las asignaciones a empleados/freelancers quedan en `onboarding_assignments` con progreso por ítem en `onboarding_assignment_items`. Si un ítem requiere acceso, el alta se traza en `onboarding_access_provisions` para auditar quién otorgó el acceso y a qué recurso.
- **Entregables**: los entregables por proyecto/asignación se persisten en `deliverables` con reseñas en `deliverable_reviews`. Las respuestas de satisfacción (NPS 0–10) van a `nps_responses` y dependen de permisos `deliverables.*` y `nps.*` sembrados por `ReferenceDataSeeder`.
