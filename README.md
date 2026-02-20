# API Financiera

API REST en Laravel para gestión de usuarios y transacciones financieras (registro, login, transferencias, estadísticas y exportación CSV).

## Requisitos

- **PHP** 8.2 o superior
- **Composer**
- **MySQL** 5.7+ / 8.x (o MariaDB)
- **Node.js** y npm (opcional; solo si vas a compilar assets)

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/TU_USUARIO/api-financiera.git
cd api-financiera
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Configurar el archivo de entorno

El proyecto incluye un archivo de ejemplo **`.env.example`** con todas las variables necesarias (sin datos sensibles). Para compartir la configuración se usa este archivo; **nunca** se sube `.env` al repositorio.

Copia el ejemplo y edita los valores según tu entorno:

```bash
# En Linux/macOS:
cp .env.example .env

# En Windows (CMD):
copy .env.example .env
```

Variables principales a revisar:

| Variable       | Descripción                    | Ejemplo           |
|----------------|--------------------------------|-------------------|
| `APP_NAME`     | Nombre de la aplicación        | API Financiera    |
| `APP_KEY`      | Se genera en el paso siguiente | —                 |
| `APP_URL`      | URL base de la API             | http://localhost  |
| `DB_DATABASE`  | Nombre de la base de datos     | api_financiera    |
| `DB_USERNAME`  | Usuario MySQL                  | root              |
| `DB_PASSWORD`  | Contraseña MySQL               | (vacío o tu pass) |

### 4. Generar clave de aplicación

```bash
php artisan key:generate
```

### 5. Crear la base de datos

Crea en MySQL una base de datos con el nombre que definiste en `DB_DATABASE` (por ejemplo `api_financiera`):

```sql
CREATE DATABASE api_financiera CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Ejecutar migraciones

```bash
php artisan migrate
```

(Opcional) Si hay seeders para datos de prueba:

```bash
php artisan db:seed
```

### 7. Servidor de desarrollo

```bash
php artisan serve
```

La API quedará disponible en `http://localhost:8000`. Los endpoints están bajo el prefijo `/api` (por ejemplo: `http://localhost:8000/api/register`, `http://localhost:8000/api/login`).

## Compartir el archivo ENV

- En el repositorio se incluye **`.env.example`** con la estructura de variables y valores de ejemplo (sin secretos).
- Cada desarrollador o entorno debe:
  1. Copiar `.env.example` a `.env`.
  2. Completar `APP_KEY` (con `php artisan key:generate`) y los datos de base de datos (`DB_*`).
- El archivo `.env` está en `.gitignore` y **no** debe subirse a GitHub.

## Tests

```bash
php artisan test
```

En Windows: `run-tests.bat`. Se prueban validaciones (usuarios, transacciones), reglas de negocio (saldo, límite diario, idempotencia) y mensajes en español.

## Endpoints principales

- `GET /api` — Información de la API y lista de endpoints
- `POST /api/register` — Registro de usuario (name, email, password, password_confirmation, initial_balance)
- `POST /api/login` — Login (email, password); devuelve token
- `POST /api/logout` — Cerrar sesión (requiere token)
- `GET /api/me` — Usuario autenticado (requiere token)
- `GET /api/users` — Listar usuarios (requiere token)
- `POST /api/transactions` — Crear transferencia (requiere token)
- `GET /api/transactions` — Listar transacciones (requiere token)
- `GET /api/transactions/export/csv` — Exportar transacciones en CSV (requiere token)
- `GET /api/transactions/stats/total-by-sender` — Estadísticas por remitente (requiere token)
- `GET /api/transactions/stats/average-by-user` — Promedio por usuario (requiere token)

Las rutas protegidas requieren el header: `Authorization: Bearer <token>`. Especificación OpenAPI en `docs/openapi.yaml`.

## Licencia

MIT.
