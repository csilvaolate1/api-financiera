# Guía paso a paso – Completar y probar el proyecto API Financiera

Sigue los pasos en orden. No saltes ninguno.

---

## PARTE 1: Entender qué es este proyecto

- Es una **API** (backend). No tiene pantallas ni botones.
- Se prueba con **Postman** (o similar): tú envías datos y la API te responde con JSON.
- Cuando todo funcione aquí, más adelante podrás hacer una página o app que use esta misma API.

---

## PARTE 2: Dejar el proyecto listo en tu PC

### Paso 2.1 – Carpeta del proyecto

Todas las órdenes se ejecutan en esta carpeta:

```
c:\laragon\www\api-financiera
```

Abre **Terminal** de Laragon (clic derecho en Laragon → Terminal) o una consola y escribe:

```bash
cd c:\laragon\www\api-financiera
```

---

### Paso 2.2 – Base de datos (ya lo hiciste)

- En MySQL (Laragon) la base se llama: **api_financiera**
- En el archivo **.env** debe estar:
  - `DB_CONNECTION=mysql`
  - `DB_DATABASE=api_financiera`
  - `DB_USERNAME=root`
  - `DB_PASSWORD=` (vacío)

Si ya creaste la base y cambiaste el .env, está bien. Si no, créala y ajusta el .env.

---

### Paso 2.3 – Migraciones (ya lo hiciste)

En la misma carpeta del proyecto ejecutaste:

```bash
php artisan migrate
```

y salieron todas las migraciones en verde. Con eso las tablas ya están creadas. No hace falta repetirlo.

---

### Paso 2.4 – Levantar el servidor

Cada vez que quieras probar la API, el servidor tiene que estar encendido.

1. Abre la **Terminal de Laragon** (o una consola).
2. Ve a la carpeta del proyecto:
   ```bash
   cd c:\laragon\www\api-financiera
   ```
3. Ejecuta:
   ```bash
   php artisan serve
   ```
4. Debe aparecer algo como: `Server running on [http://127.0.0.1:8000]`.
5. **No cierres esa ventana.** Déjala abierta mientras pruebas.

La API estará en: **http://127.0.0.1:8000/api**

---

## PARTE 3: Instalar Postman (para probar la API)

1. Entra en: https://www.postman.com/downloads/
2. Descarga e instala Postman.
3. Ábrelo. No hace falta crear cuenta; puedes usar “Skip and go to the app” si te lo pregunta.

Postman sirve para enviar peticiones a la API (registro, login, listar usuarios, etc.) y ver las respuestas.

---

## PARTE 4: Probar la API en Postman (paso a paso)

Asegúrate de que **el servidor está corriendo** (Parte 2.4) y Postman está abierto.

---

### Paso 4.1 – Registrar un usuario

1. En Postman, arriba donde dice “Enter URL or paste text”, escribe:
   ```
   http://127.0.0.1:8000/api/register
   ```
2. A la izquierda del cuadro de la URL, cambia **GET** a **POST**.
3. Debajo de la URL, abre la pestaña **Body**.
4. Marca **raw** y en el desplegable de la derecha elige **JSON**.
5. En el cuadro de texto pega exactamente esto (puedes cambiar nombre y email si quieres):

```json
{
  "name": "Maria",
  "email": "maria@test.com",
  "password": "12345678",
  "password_confirmation": "12345678",
  "initial_balance": 1000
}
```

6. Pulsa **Send** (botón azul).
7. Abajo deberías ver **Status: 201 Created** y un JSON con los datos del usuario (name, email, initial_balance, balance, etc.). Si ves eso, el registro funciona.

Si sale error 422, revisa que el JSON esté bien (comillas dobles, comas correctas) y que no exista ya un usuario con ese email.

---

### Paso 4.2 – Hacer login y guardar el token

1. En Postman, cambia la URL a:
   ```
   http://127.0.0.1:8000/api/login
   ```
2. Sigue siendo **POST**.
3. En **Body** → **raw** → **JSON** pon:

```json
{
  "email": "maria@test.com",
  "password": "12345678"
}
```

(Usa el mismo email y contraseña del usuario que registraste.)

4. Pulsa **Send**.
5. En la respuesta (abajo) deberías ver algo como:

```json
{
  "user": { "id": 1, "name": "Maria", "email": "maria@test.com", ... },
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxx",
  "type": "Bearer"
}
```

6. **Copia solo el valor del token** (el texto largo que está entre comillas después de `"token": "`). Lo usarás en el siguiente paso. No copies la palabra “Bearer”, solo el token.

---

### Paso 4.3 – Probar una ruta protegida (con token)

Ahora vamos a pedir “quién soy” a la API. Esa ruta exige que envíes el token.

1. Cambia la URL a:
   ```
   http://127.0.0.1:8000/api/me
   ```
2. Cambia el método a **GET**.
3. Arriba a la derecha, abre la pestaña **Authorization**.
4. En **Type** elige **Bearer Token**.
5. En el campo **Token** pega el token que copiaste en el Paso 4.2 (sin espacios ni comillas).
6. Pulsa **Send**.
7. Deberías ver **Status: 200 OK** y el JSON con los datos del usuario (Maria). Si ves eso, la autenticación funciona.

---

### Paso 4.4 – Listar usuarios

1. URL:
   ```
   http://127.0.0.1:8000/api/users
   ```
2. Método: **GET**.
3. En **Authorization** sigue siendo **Bearer Token** con el mismo token.
4. **Send**. Deberías ver la lista de usuarios (al menos Maria) con paginación. Status 200.

---

### Paso 4.5 – Crear una transferencia (opcional)

Para esto necesitas **dos usuarios**. Si solo tienes uno (Maria), primero crea otro:

- Repite el **Paso 4.1** con otro email, por ejemplo:
  - name: "Juan", email: "juan@test.com", misma contraseña, initial_balance: 500.

Luego:

1. URL:
   ```
   http://127.0.0.1:8000/api/transactions
   ```
2. Método: **POST**.
3. **Authorization**: Bearer Token (el de Maria o el de Juan).
4. **Body** → raw → JSON:

```json
{
  "from_user_id": 1,
  "to_user_id": 2,
  "amount": 50
}
```

(1 = quien envía, 2 = quien recibe. Ajusta los IDs si tus usuarios tienen otros IDs.)

5. **Send**. Deberías recibir **201 Created** y los datos de la transacción. Si Maria era la 1 y Juan el 2, Maria habrá enviado 50 a Juan.

---

## PARTE 5: Resumen de lo que tienes

Cuando hayas hecho la Parte 2 y la Parte 4 sin errores:

- El proyecto está **completo** a nivel API: registro, login, usuarios, transacciones, validaciones, límites, etc.
- Sabes **cómo probarlo** con Postman antes de implementar cualquier página.
- Para “completar el proyecto” en el sentido de **usar esta API en una página**, el siguiente paso sería crear esa página (frontend) que llame a estas mismas URLs (register, login, users, transactions) desde el navegador. Eso sería otra fase; esta guía termina cuando las pruebas en Postman funcionan.

---

## Si algo falla

- **“Connection refused” o no responde:** ¿Está corriendo `php artisan serve`? ¿La URL es http://127.0.0.1:8000/api/...?
- **401 Unauthenticated:** No enviaste token o está mal. Vuelve a hacer login, copia de nuevo el token y ponlo en Authorization → Bearer Token.
- **422:** Revisa el JSON del Body (comillas, comas) y que los datos cumplan las reglas (por ejemplo contraseña y password_confirmation iguales, email único).
- **500:** Revisa que la base de datos esté creada, el .env correcto y que hayas ejecutado `php artisan migrate`.

Si quieres, en el siguiente mensaje dime en qué paso exacto te quedaste (número y qué ves en Postman o en la consola) y te guío desde ahí.
