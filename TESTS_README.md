# Tests – Qué se prueba

## Cómo ejecutar

En la **Terminal de Laragon** (o CMD con PHP en el PATH):

```bash
cd c:\laragon\www\api-financiera
php artisan test
```

O doble clic en **`ejecutar-tests.bat`** (desde la carpeta del proyecto).

---

## Tests incluidos

### Unit – StoreUserRequestTest
- `initial_balance` no puede ser negativo.
- `initial_balance` es obligatorio.
- Mensaje personalizado para saldo inicial negativo.

### Unit – StoreTransactionRequestTest
- `amount` es obligatorio.
- `amount` debe ser mayor a 0.
- `to_user_id` debe ser distinto de `from_user_id` (no transferir al mismo usuario).
- Datos válidos pasan la validación.
- Mensajes personalizados para monto y mismo usuario.

### Feature – TransactionValidationTest
- **Saldo insuficiente:** rechaza transferencia por encima del saldo del emisor (422, mensaje y balance).
- **Mismo usuario:** rechaza cuando emisor y receptor son el mismo (422).
- **Idempotencia:** misma `idempotency_key` devuelve la transacción ya creada (200) y no crea otra.
- **Límite diario:** rechaza cuando el emisor supera 5.000 USD en el día (422, mensaje y daily_limit).

---

Los tests usan base de datos en memoria (SQLite) y `RefreshDatabase`; no modifican tu base MySQL real.
