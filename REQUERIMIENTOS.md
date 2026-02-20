# API Financiera – Requerimientos vs implementación

Este documento muestra **cómo debe quedar el proyecto**: cada requerimiento y dónde está implementado.

---

## Objetivo general

> Desarrollar una API RESTful para la gestión de usuarios y transacciones financieras, con foco en validaciones, seguridad, optimización de consultas y buenas prácticas de desarrollo backend.

**Implementado:** API REST bajo `/api`, controladores en `app/Http/Controllers/Api/`, validaciones en Form Requests, **Laravel Sanctum** para autenticación, middleware para rutas protegidas, consultas optimizadas y tests.

---

## 1. Requerimientos funcionales

### 1.1 Gestión de usuarios – CRUD completo

| Requerimiento | Implementación |
|---------------|----------------|
| **Campos:** nombre, email, saldo inicial (decimal) | Modelo `User`: `name`, `email`, `initial_balance` (decimal). `balance` se actualiza con transferencias. |
| **Crear usuario** | `POST /api/register` (público) y `POST /api/users` (con token). `UserController@store`, `StoreUserRequest`. |
| **Listar usuarios** | `GET /api/users` (paginated). `UserController@index`. |
| **Ver usuario** | `GET /api/users/{id}`. `UserController@show`. |
| **Actualizar usuario** | `PUT/PATCH /api/users/{id}`. `UserController@update`, `UpdateUserRequest`. |
| **Eliminar usuario** | `DELETE /api/users/{id}`. `UserController@destroy`. |

**Archivos:**  
`app/Http/Controllers/Api/UserController.php`, `app/Http/Requests/StoreUserRequest.php`, `app/Http/Requests/UpdateUserRequest.php`, `app/Http/Resources/UserResource.php`, `app/Models/User.php`.

---

### 1.2 Gestión de transacciones

| Requerimiento | Implementación |
|---------------|----------------|
| **Registro de transferencias entre usuarios** | `POST /api/transactions` con `from_user_id`, `to_user_id`, `amount`. `TransactionController@store`. |
| **No permitir transferencias por encima del saldo del emisor** | Validación en `TransactionController@store`: si `balance < amount` → 422 y mensaje personalizado. |
| **Límite diario 5.000 USD** | Suma de montos enviados hoy por el emisor; si `today_total + amount > 5000` → 422 y mensaje. |
| **Evitar transacciones duplicadas** | Campo `idempotency_key` (único). Si se repite la clave → se devuelve la transacción ya creada (200). |
| **Personalización de mensajes de error** | Mensajes en español en `StoreUserRequest`, `UpdateUserRequest`, `StoreTransactionRequest` y respuestas 422 del controlador. |

**Archivos:**  
`app/Http/Controllers/Api/TransactionController.php`, `app/Http/Requests/StoreTransactionRequest.php`, `app/Http/Resources/TransactionResource.php`, `app/Models/Transaction.php`.

---

### 1.3 Autenticación y seguridad

| Requerimiento | Implementación |
|---------------|----------------|
| **Autenticación con Laravel Sanctum (o Passport)** | **Laravel Sanctum.** Login: `POST /api/login` → devuelve token Bearer. Tokens en tabla `personal_access_tokens`. |
| **Middleware para proteger rutas sensibles** | Rutas bajo `Route::middleware('auth:sanctum')`. Solo `/api`, `/api/login`, `/api/register` son públicas. |

**Archivos:**  
`routes/api.php`, `config/auth.php` (guard `sanctum`), `app/Models/User.php` (HasApiTokens), `app/Http/Controllers/Api/AuthController.php`, migración `personal_access_tokens`.

---

### 1.4 Base de datos

| Requerimiento | Implementación |
|---------------|----------------|
| **Migraciones con valores por defecto** | `users`: `initial_balance` y `balance` con `default(0)`. `transactions`: campos según diseño. |
| **CHECK constraints si el motor lo permite** | En MySQL: `users` (initial_balance >= 0, balance >= 0), `transactions` (amount > 0, from_user_id != to_user_id). En SQLite no se añaden (solo en MySQL). |

**Archivos:**  
`database/migrations/0001_01_01_000000_create_users_table.php`, `database/migrations/2024_02_20_000003_add_balance_to_users_table.php`, `database/migrations/2024_02_20_000004_create_personal_access_tokens_table.php`, `database/migrations/2024_02_20_000005_create_transactions_table.php`.

---

### 1.5 Exportación CSV

| Requerimiento | Implementación |
|---------------|----------------|
| **Endpoint para exportar transacciones en CSV** | `GET /api/transactions/export/csv` (protegido con auth). |
| **Punto y coma como delimitador** | Uso de `fputcsv(..., ';')` en `TransactionController@exportCsv`. |

**Archivos:**  
`app/Http/Controllers/Api/TransactionController.php` (método `exportCsv`).

---

## 2. Requerimientos técnicos adicionales

### 2.1 Optimización de consultas

| Requerimiento | Implementación |
|---------------|----------------|
| **Total transferido por cada usuario emisor** | `GET /api/transactions/stats/total-by-sender`. Consulta con `SUM(amount)` y `GROUP BY from_user_id`. |
| **Promedio de monto por usuario (emisor)** | `GET /api/transactions/stats/average-by-user`. Consulta con `AVG(amount)` y `GROUP BY from_user_id`. |

**Archivos:**  
`app/Http/Controllers/Api/TransactionController.php` (`statsTotalBySender`, `statsAverageByUser`).

---

### 2.2 Testing

| Requerimiento | Implementación |
|---------------|----------------|
| **Pruebas unitarias mínimas para validaciones personalizadas** | Tests para reglas y mensajes de `StoreUserRequest` y `StoreTransactionRequest`. Tests de negocio: saldo insuficiente, mismo usuario, idempotencia, límite diario. |

**Archivos:**  
`tests/Unit/StoreUserRequestTest.php`, `tests/Unit/StoreTransactionRequestTest.php`, `tests/Feature/TransactionValidationTest.php`.

---

### 2.3 Documentación

| Requerimiento | Implementación |
|---------------|----------------|
| **Esbozo de documentación con Swagger o Laravel Scribe** | OpenAPI 3.0 en `docs/openapi.yaml` (compatible con Swagger UI / Editor). Resumen de uso en `API_README.md`. |

**Archivos:**  
`docs/openapi.yaml`, `API_README.md`.

---

## 3. Cómo debe quedar la estructura del proyecto

```
api-financiera/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php      # register, login, logout, me
│   │   │   │   ├── UserController.php      # CRUD usuarios
│   │   │   │   └── TransactionController.php # transacciones, CSV, stats
│   │   │   └── Controller.php
│   │   ├── Requests/
│   │   │   ├── StoreUserRequest.php
│   │   │   ├── UpdateUserRequest.php
│   │   │   └── StoreTransactionRequest.php
│   │   └── Resources/
│   │       ├── UserResource.php
│   │       └── TransactionResource.php
│   ├── Models/
│   │   ├── User.php          # name, email, initial_balance, balance, HasApiTokens
│   │   └── Transaction.php
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/
│   └── app.php               # api: routes/api.php
├── config/
│   └── auth.php              # guard sanctum
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 2024_02_20_000003_add_balance_to_users_table.php
│   │   ├── 2024_02_20_000004_create_personal_access_tokens_table.php
│   │   └── 2024_02_20_000005_create_transactions_table.php
│   └── factories/
│       └── UserFactory.php   # initial_balance, balance
├── docs/
│   └── openapi.yaml          # Esbozo Swagger/OpenAPI
├── routes/
│   └── api.php               # Todas las rutas /api/*
├── tests/
│   ├── Unit/
│   │   ├── StoreUserRequestTest.php
│   │   └── StoreTransactionRequestTest.php
│   └── Feature/
│       └── TransactionValidationTest.php
├── API_README.md             # Uso de la API
├── REQUERIMIENTOS.md        # Este archivo
└── composer.json            # laravel/sanctum en require
```

---

## 4. Endpoints – cómo debe quedar

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET | /api | No | Info de la API |
| POST | /api/register | No | Crear usuario (nombre, email, password, initial_balance) |
| POST | /api/login | No | Login → token Bearer |
| GET | /api/login | No | Mensaje “usar POST” |
| POST | /api/logout | Sí | Invalidar token |
| GET | /api/me | Sí | Usuario autenticado |
| GET | /api/users | Sí | Listar usuarios |
| POST | /api/users | Sí | Crear usuario (admin) |
| GET | /api/users/{id} | Sí | Ver usuario |
| PUT/PATCH | /api/users/{id} | Sí | Actualizar usuario |
| DELETE | /api/users/{id} | Sí | Eliminar usuario |
| GET | /api/transactions | Sí | Listar transacciones |
| POST | /api/transactions | Sí | Crear transferencia |
| GET | /api/transactions/export/csv | Sí | Exportar CSV (;) |
| GET | /api/transactions/stats/total-by-sender | Sí | Total por emisor |
| GET | /api/transactions/stats/average-by-user | Sí | Promedio por usuario |

---

## 5. Flujo recomendado para probar

1. **Registro:** `POST /api/register` con `{ "name", "email", "password", "password_confirmation", "initial_balance" }`.
2. **Login:** `POST /api/login` con `{ "email", "password" }` → guardar el `token`.
3. **Rutas protegidas:** Incluir header `Authorization: Bearer {token}` en todas las peticiones a usuarios, transacciones, CSV y stats.

Con esto el proyecto queda alineado a los requerimientos y se puede ver y probar tal como se describe aquí.
