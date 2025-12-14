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

### Registro temporal de administrador
- `POST /api/register` con cuerpo `{ "full_name": "Nombre", "email": "correo", "password": "secreto" }` crea un usuario activo con rol `super_admin`.
- Pensado solo para el arranque inicial; elimina o deshabilita la ruta cuando ya cuentes con cuentas reales.

## Frontend en React
Hay dos opciones disponibles en la carpeta `frontend/`:

1. **SPA en React (recomendada):**
   - Instala dependencias (requiere acceso a npm):
     ```bash
     cd frontend
     npm install
     npm run dev -- --host
     ```
   - Abre el navegador en `http://localhost:5173` y usa el formulario de inicio de sesión o el registro temporal. El color principal del UI es `#006d71` y se usa la tipografía Poppins.
   - Configura la variable `VITE_API_URL` (por ejemplo, `http://localhost:8000`) para apuntar al backend; si no se define, usa ese valor por defecto.
   - Al iniciar sesión se guarda el token y se redirige a un dashboard básico que muestra el nombre, correo y rol del usuario junto con próximos pasos.

2. **HTML estático anterior:**
   - Se mantiene `frontend/login.html` con React + Tailwind vía CDN. Úsalo si no quieres instalar dependencias; indica manualmente la URL del endpoint `/api/login`.
