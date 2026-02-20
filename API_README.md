# API Financiera - Documentación

Para ver **cómo debe quedar el proyecto** y el detalle de cada requerimiento implementado, abre **`REQUERIMIENTOS.md`**.

## Requisitos

- PHP 8.2+
- Composer
- Laravel Sanctum (autenticación API)

## Instalación

```bash
# Instalar dependencias PHP (incluye Laravel Sanctum)
composer install

# Copiar entorno y generar clave
cp .env.example .env
php artisan key:generate

# Migraciones
php artisan migrate
```

## Autenticación

La API usa **Laravel Sanctum** con tokens Bearer.

1. **Login** – `POST /api/login`
   - Body: `{ "email": "user@example.com", "password": "password" }`
   - Respuesta: `{ "user": {...}, "token": "...", "type": "Bearer" }`

2. **Rutas protegidas** – Incluir header:
   - `Authorization: Bearer {token}`

3. **Logout** – `POST /api/logout` (con token)

## Endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | /api | Info y lista de endpoints |
| POST | /api/register | Registrar usuario (público) |
| POST | /api/login | Iniciar sesión |
| POST | /api/logout | Cerrar sesión (auth) |
| GET | /api/me | Usuario actual (auth) |
| GET | /api/users | Listar usuarios (auth) |
| POST | /api/users | Crear usuario (auth) |
| GET | /api/users/{id} | Ver usuario (auth) |
| PUT/PATCH | /api/users/{id} | Actualizar usuario (auth) |
| DELETE | /api/users/{id} | Eliminar usuario (auth) |
| GET | /api/transactions | Listar transacciones (auth) |
| POST | /api/transactions | Crear transferencia (auth) |
| GET | /api/transactions/export/csv | Exportar CSV (auth) |
| GET | /api/transactions/stats/total-by-sender | Total transferido por emisor (auth) |
| GET | /api/transactions/stats/average-by-user | Promedio por usuario (auth) |

## Validaciones de transacciones

- **Saldo**: No se puede transferir más del saldo del emisor.
- **Límite diario**: 5.000 USD por usuario emisor por día.
- **Duplicados**: Enviar `idempotency_key` (string único) para evitar transacciones duplicadas; si se repite la petición con la misma clave, se devuelve la transacción ya creada.
- **Mismo usuario**: No se permite from_user_id = to_user_id.

## Exportación CSV

- **GET** `/api/transactions/export/csv`
- Delimitador: **punto y coma** (`;`)
- Codificación: UTF-8 con BOM

## Documentación OpenAPI (Swagger)

El esbozo de la API está en **`docs/openapi.yaml`** (OpenAPI 3.0).

Para ver la documentación interactiva:

1. Usar [Swagger Editor](https://editor.swagger.io) y cargar `docs/openapi.yaml`, o
2. Instalar Swagger UI y apuntar a ese archivo, o
3. Usar herramientas como Postman/Insomnia que importan OpenAPI.

## Tests

```bash
php artisan test
# o
./vendor/bin/phpunit
```

Incluye:

- **Unit**: Validaciones de `StoreUserRequest` y `StoreTransactionRequest` (incluyendo mensajes personalizados).
- **Feature**: Reglas de negocio de transacciones (saldo insuficiente, límite diario, idempotencia).

## Base de datos

- **Migraciones**: Incluyen valores por defecto y, en MySQL 8.0.16+, CHECK constraints (saldo ≥ 0, amount > 0, from_user_id ≠ to_user_id).
- **Usuarios**: `name`, `email`, `password`, `initial_balance`, `balance` (este se actualiza con cada transferencia).
- **Transacciones**: `from_user_id`, `to_user_id`, `amount`, `idempotency_key` (opcional, único).
