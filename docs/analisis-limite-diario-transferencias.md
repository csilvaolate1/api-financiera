# Caso práctico: Límite diario de transferencias superado

## Escenario

Los usuarios reportan que el sistema **permite hacer más de 5.000 USD en transferencias diarias**, cuando el requisito de negocio es un límite de 5.000 USD por día por emisor.

---

## 1. Cómo identificar el origen del problema

### 1.1 Revisión del flujo actual

En `App\Http\Controllers\Api\TransactionController::store()` el flujo es:

1. Validar request (StoreTransactionRequest).
2. Comprobar saldo del emisor.
3. **Calcular `$todayTotal`** (suma de montos enviados hoy por el emisor) con una consulta **fuera** de cualquier transacción de base de datos.
4. Si `$todayTotal + $amount > 5000` → responder 422 y no continuar.
5. Comprobar idempotencia (opcional).
6. **Abrir `DB::transaction()`**: lock de usuarios, actualizar balances, **crear** el registro en `transactions`.

### 1.2 Causa raíz: condición de carrera (race condition)

La verificación del límite diario se hace **antes** y **fuera** de la transacción que escribe en la base de datos. Por tanto:

- **Request A** lee `todayTotal = 3000` (p. ej. 2 transferencias ya existentes).
- **Request B** (casi simultánea) también lee `todayTotal = 3000` porque A aún no ha hecho commit.
- Ambas comprueban: `3000 + 2000 = 5000` → ambas pasan.
- Ambas entran en `DB::transaction()`, crean sus filas y hacen commit.
- Resultado: el usuario ha enviado **7000 USD** en el día (3000 + 2000 + 2000).

Es decir, el límite se valida con un “instantánea” del total del día que puede quedar desactualizada cuando hay concurrencia.

### 1.3 Cómo verificarlo en tu entorno

- **Logs**: Buscar días con un mismo `from_user_id` cuya suma de `amount` en ese día supere 5000.
- **Consulta de diagnóstico** (por usuario y día):

```sql
SELECT from_user_id, DATE(created_at) AS day, SUM(amount) AS total
FROM transactions
GROUP BY from_user_id, DATE(created_at)
HAVING SUM(amount) > 5000;
```

- **Tests de concurrencia**: Ejecutar en paralelo varias peticiones POST a `/api/transactions` con el mismo emisor y montos que en suma superen 5000; si alguna debería ser rechazada y no lo es, se reproduce el bug.
- **Revisión de código**: Confirmar que el chequeo de `todayTotal` está fuera de `DB::transaction()` y que no hay lock que serialice por emisor.

---

## 2. Soluciones posibles

### Opción A: Mover la verificación dentro de la transacción y recalcular con lock (recomendada)

- Incluir dentro de `DB::transaction()` el cálculo de `$todayTotal` (misma lógica: suma de `amount` del emisor desde `Carbon::today()`).
- Asegurar serialización por emisor usando **lock pesimista**: p. ej. bloquear la fila del usuario emisor con `lockForUpdate()` (ya se hace para actualizar balance). Con eso, una segunda petición del mismo usuario esperará hasta que la primera termine, y al recalcular `todayTotal` ya verá la nueva transferencia.
- Si `todayTotal + amount > 5000`, hacer rollback y responder 422 con el mismo mensaje y estructura que hoy.

**Ventajas**: Corrige la condición de carrera sin nuevas tablas ni config; el límite se evalúa con datos consistentes.  
**Desventajas**: Mayor contención bajo mucha concurrencia del mismo usuario (aceptable para un límite por usuario).

### Opción B: Lock explícito por usuario (p. ej. Redis)

- Antes de la transacción DB, adquirir un lock distribuido (Redis, etc.) por `from_user_id` (y opcionalmente por día).
- Dentro del lock: recalcular `todayTotal`, validar límite, ejecutar la misma `DB::transaction()` que ya tienes.
- Soltar el lock al final.

**Ventajas**: Serializa todas las transferencias del mismo emisor en el día.  
**Desventajas**: Dependencia de Redis (o similar), más complejidad operativa y de código.

### Opción C: Constraint a nivel de base de datos

- No es trivial: SQL estándar no permite un CHECK que consulte “suma de filas del mismo usuario en la misma fecha”. En algunos motores se podría acercar con triggers o vistas materializadas; en MySQL/MariaDB suele ser frágil y costoso.
- No recomendado como única solución; si se usa, sería como capa extra tras la lógica en aplicación.

### Recomendación

**Implementar la Opción A**: verificación del límite diario **dentro** de `DB::transaction()`, recalculando `todayTotal` después de haber tomado el lock del usuario emisor (y receptor), y rechazando con 422 si se superan 5000 USD.

---

## 3. Justificación de la estrategia de implementación

1. **Corrige la causa raíz**: La decisión de aceptar o rechazar la transferencia se toma con datos ya actualizados dentro de la misma transacción que escribe, evitando la ventana de carrera.
2. **Reutiliza la infraestructura actual**: No añade Redis ni nuevas tablas; usa la transacción y los locks que ya existen para saldos.
3. **Comportamiento externo estable**: La API sigue devolviendo 422 con el mismo mensaje y `daily_limit` / `used_today`; los clientes no necesitan cambios.
4. **Tests**: El test `test_rejects_when_daily_limit_exceeded` sigue siendo válido; se puede añadir un test de concurrencia (varias peticiones paralelas que en conjunto superen 5000) para asegurar que al menos una falla y que la suma del día no supera el límite.
5. **Rendimiento**: Solo se serializan las peticiones del **mismo** emisor; el resto de usuarios no se ven afectados.

Pasos concretos sugeridos:

1. En `TransactionController::store()`, mover el cálculo de `$todayTotal` y la comprobación `$todayTotal + $amount > DAILY_LIMIT_USD` al **interior** del callback de `DB::transaction()`, después de hacer `lockForUpdate()` del emisor (y receptor).
2. Si se excede el límite dentro de la transacción, lanzar una excepción o hacer `return` de una respuesta 422 desde dentro del callback (manejando el formato actual de mensaje y `daily_limit` / `used_today`). Ajustar si hace falta para que el rollback sea correcto.
3. Añadir (o ejecutar) un test de concurrencia que reproduzca el escenario de múltiples transferencias simultáneas del mismo usuario por encima de 5000 USD y comprobar que el total del día no supera el límite.
4. Desplegar y, si se dispone de datos históricos, ejecutar la consulta SQL de diagnóstico para verificar que no vuelven a aparecer días con total > 5000 por usuario.

Con esto se cierra el caso: identificación del origen (race condition), soluciones posibles (A recomendada) y estrategia de implementación justificada.
