# Informe de Pruebas - API Financiera

**Fecha:** Febrero 2025  
**Proyecto:** API Financiera  
**Entorno:** Laravel, PHPUnit, SQLite (in-memory para tests)

---

## Resumen ejecutivo

Se realizó una revisión y ampliación completa de la suite de pruebas del proyecto. Se corrigieron errores existentes, se ampliaron los tests para cubrir nuevas funcionalidades y se documentaron las soluciones aplicadas.

| Métrica | Valor |
|---------|-------|
| Total de tests | 56 |
| Tests que pasan | 56 |
| Tests unitarios | 32 |
| Tests de integración (feature) | 24 |
| Archivos de test | 9 |

---

## 1. Errores encontrados y soluciones

### 1.1 Error: "no such table: users" en StoreUserRequestTest

**Tests afectados:**
- `test_validates_initial_balance_non_negative`
- `test_validates_initial_balance_required`

**Mensaje de error:**
```
QueryException: SQLSTATE[HY000]: General error: 1 no such table: users
(Connection: sqlite, Database: :memory:,
SQL: select count(*) as aggregate from "users" where "email" = test@example.com)
```

**Causa raíz:**  
La regla de validación `unique:users,email` en `StoreUserRequest` realiza una consulta a la base de datos para comprobar la unicidad del email. Los tests unitarios usan SQLite en memoria sin ejecutar migraciones, por lo que la tabla `users` no existía.

**Solución:**  
Se agregó el trait `RefreshDatabase` a la clase `StoreUserRequestTest` para que se ejecuten las migraciones antes de cada test y la tabla `users` esté disponible.

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class StoreUserRequestTest extends TestCase
{
    use RefreshDatabase;
    // ...
}
```

---

### 1.2 Error: "100 is identical to 100.0" en TransactionValidationTest

**Test afectado:**
- `test_rejects_transfer_above_sender_balance`

**Mensaje de error:**
```
Failed asserting that 100 is identical to 100.0.
at tests\Feature\TransactionValidationTest.php:46
$response->assertJsonPath('balance', 100.0);
```

**Causa raíz:**  
El controlador devuelve `balance` como float (`(float) $fromUser->balance`), pero al codificar la respuesta en JSON, los números enteros como 100 se serializan sin decimales. Al decodificar el JSON en el test, PHP devuelve el número como entero (100), no como float (100.0). La aserción `assertJsonPath('balance', 100.0)` usa comparación estricta y falla.

**Solución:**  
Se cambió el valor esperado de `100.0` a `100` para coincidir con el tipo que devuelve la decodificación JSON.

```php
$response->assertJsonPath('balance', 100);
```

---

### 1.3 Error: "125.0 is identical to 125" en TransactionValidationTest

**Test afectado:**
- `test_successful_transfer_updates_balances`

**Mensaje de error:**
```
Failed asserting that 125.0 is identical to 125.
at tests\Feature\TransactionValidationTest.php:50
$this->assertSame(125, (float) $sender->balance);
```

**Causa raíz:**  
El campo `balance` del modelo User tiene cast `decimal:2`, por lo que al leer de la base de datos devuelve un valor numérico de tipo float (125.0). La función `assertSame()` compara tanto valor como tipo; 125 (int) no es idéntico a 125.0 (float).

**Solución:**  
Se reemplazó `assertSame` por `assertEquals` para que la comparación sea por valor, aceptando tanto enteros como flotantes.

```php
$this->assertEquals(125, (float) $sender->balance);
$this->assertEquals(125, (float) $receiver->balance);
```

---

## 2. Tests ampliados y nuevos

### 2.1 StoreUserRequestTest

**Tests agregados:**
- `test_validates_name_required` — Nombre obligatorio
- `test_validates_email_required` — Email obligatorio
- `test_validates_email_format` — Formato de email válido
- `test_validates_email_unique` — Email único en la tabla users
- `test_validates_password_required` — Contraseña obligatoria
- `test_validates_password_confirmed` — Contraseña confirmada
- `test_passes_with_valid_data` — Datos válidos pasan la validación
- `test_custom_message_for_email_unique` — Mensaje personalizado para email duplicado

### 2.2 StoreTransactionRequestTest

**Tests agregados:**
- `test_validates_from_user_id_required` — Usuario emisor obligatorio
- `test_validates_to_user_id_required` — Usuario receptor obligatorio
- `test_validates_from_user_id_exists` — Usuario emisor debe existir
- `test_validates_to_user_id_exists` — Usuario receptor debe existir
- `test_accepts_valid_idempotency_key` — Clave de idempotencia válida
- `test_validates_idempotency_key_max_length` — Longitud máxima de la clave
- `test_custom_message_for_from_user_id_exists` — Mensaje para emisor inexistente
- `test_custom_message_for_to_user_id_exists` — Mensaje para receptor inexistente

**Ajustes:**  
Se actualizaron tests existentes para crear usuarios reales con `User::factory()` en lugar de IDs fijos, ya que la regla `exists:users,id` requiere usuarios en la base de datos.

### 2.3 TransactionValidationTest

**Tests agregados:**
- `test_successful_transfer_updates_balances` — Transferencia exitosa y actualización de saldos
- `test_rejects_transfer_without_auth_token` — Rechazo con 401 sin token de autenticación

### 2.4 Nuevos archivos de test

| Archivo | Descripción |
|---------|-------------|
| `UserApiTest.php` | CRUD de usuarios, autenticación requerida |
| `AuthApiTest.php` | Registro, login, logout, me, credenciales inválidas |
| `TransactionApiTest.php` | Listado paginado de transacciones, autenticación requerida |
| `UpdateUserRequestTest.php` | Validación de UpdateUserRequest (email único ignorando usuario actual, saldo inicial) |

### 2.5 ExampleTest

**Cambios:**
- Se actualizó el test de feature para validar `GET /api` (documentación de la API).
- Se eliminó `tests/Unit/ExampleTest.php` por ser un placeholder sin valor (`assertTrue(true)`).

---

## 3. Estructura actual de tests

```
tests/
├── TestCase.php
├── Unit/
│   ├── StoreUserRequestTest.php      (11 tests)
│   ├── StoreTransactionRequestTest.php (14 tests)
│   └── UpdateUserRequestTest.php     (5 tests)
└── Feature/
    ├── ExampleTest.php               (1 test)
    ├── AuthApiTest.php               (7 tests)
    ├── UserApiTest.php               (9 tests)
    ├── TransactionApiTest.php        (2 tests)
    └── TransactionValidationTest.php (7 tests)
```

---

## 4. Recomendaciones futuras

1. **Cobertura adicional:** Considerar tests para endpoints de estadísticas (`/api/transactions/stats/*`) y exportación CSV.
2. **Tests de modelo:** Añadir tests unitarios para el modelo User (p. ej. booted/balance inicial).
3. **Integración continua:** Configurar CI/CD para ejecutar los tests en cada commit.
4. **Tipos numéricos:** Usar `assertEquals` en lugar de `assertSame` al comparar saldos o montos por posibles diferencias entre int y float.

---

## 5. Cómo ejecutar los tests

```bash
# Todos los tests
php artisan test

# Solo tests unitarios
php artisan test --testsuite=Unit

# Solo tests de feature
php artisan test --testsuite=Feature

# Un archivo concreto
php artisan test tests/Feature/TransactionValidationTest.php

# Un test concreto
php artisan test --filter=test_successful_transfer_updates_balances
```
