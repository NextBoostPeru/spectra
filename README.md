# API básica en PHP

Proyecto mínimo en PHP puro para exponer la base de datos definida en `mysql.txt` a través de un API REST genérico.

## Requisitos
- PHP 8+ con extensión PDO MySQL
- Base de datos MySQL/MariaDB cargada con las tablas del archivo `mysql.txt`

## Configuración
1. Copia `config/config.php` y actualiza las credenciales de base de datos si es necesario.
2. Asegúrate de que el usuario tenga permisos para consultar `information_schema` y acceder a las tablas del esquema.

## Ejecución
Puedes iniciar el servidor embebido de PHP apuntando al directorio `public`:

```bash
php -S localhost:8000 -t public
```

## Uso del API
Las rutas siguen el patrón `/api/{tabla}`:

- `GET /api/{tabla}`: Lista registros con paginación (`limit` y `offset`).
- `GET /api/{tabla}/{id}`: Obtiene un registro por clave primaria.
- `POST /api/{tabla}`: Crea un registro usando un cuerpo JSON con los campos de la tabla.
- `PUT/PATCH /api/{tabla}/{id}`: Actualiza campos del registro indicado.
- `DELETE /api/{tabla}/{id}`: Elimina el registro.

El API valida que la tabla exista, infiere columnas y llave primaria desde `information_schema`, y omite campos desconocidos en inserciones/actualizaciones.

### Login
- `POST /api/login` con cuerpo JSON `{ "email": "correo", "password": "secreto" }` devuelve el usuario y un token de sesión efímero.
- El endpoint actualiza `last_login_at` del usuario y responde 401 cuando las credenciales no son válidas.

### Frontend de ejemplo
Se incluye `frontend/login.html` con React 18 y Tailwind vía CDN para consumir el login desde un origen separado.

1. Levanta el backend PHP (por ejemplo `php -S localhost:8000 -t public`).
2. Abre el archivo HTML en tu navegador (servido por otro origen o como archivo local) y apunta `API_URL` al backend.
