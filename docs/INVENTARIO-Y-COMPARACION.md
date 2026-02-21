# Inventario del proyecto API Financiera y comparación con lo subido

## 1. Resumen del proyecto

**API Financiera** es una API REST en **Laravel 12** con **Sanctum** para:
- Registro y login de usuarios
- Transferencias entre usuarios con límite diario de 5.000 USD
- Estadísticas y exportación CSV de transacciones

---

## 2. Contenido actual del proyecto (árbol relevante)

### 2.1 Raíz
| Archivo | Descripción |
|---------|-------------|
| `README.md` | Documentación de instalación, endpoints y uso |
| `composer.json` / `composer.lock` | Dependencias PHP (Laravel 12, Sanctum, PHPUnit, etc.) |
| `package.json` | Dependencias front (Vite) |
| `phpunit.xml` | Configuración de tests |
| `artisan` | CLI de Laravel |
| `.env.example` | Plantilla de variables de entorno (sin secretos) |
| `.gitignore` / `.gitattributes` / `.editorconfig` | Configuración de repo y editor |
| `analisis-limite-diario-transferencias.txt` | Análisis del caso límite diario (duplicado, ver más abajo) |
| `instalar-sanctum.bat` | Script para instalar Sanctum en Windows |
| `probar-api.bat` / `probar-api-token.bat` | Scripts para probar la API |
| `run-tests.bat` / `ejecutar-tests.bat` | Scripts para ejecutar tests |

### 2.2 Documentación
| Archivo | Descripción |
|---------|-------------|
| `docs/openapi.yaml` | Especificación OpenAPI 3.0.3 de la API |
| `docs/analisis-limite-diario-transferencias.txt` | Análisis del límite diario (causa raíz, soluciones, implementación) |

### 2.3 Aplicación (`app/`)
| Ruta | Descripción |
|------|-------------|
| `Http/Controllers/Controller.php` | Controlador base |
| `Http/Controllers/Api/AuthController.php` | Registro, login, logout, me |
| `Http/Controllers/Api/UserController.php` | CRUD usuarios |
| `Http/Controllers/Api/TransactionController.php` | Transferencias, listado, export CSV, estadísticas (límite 5.000 USD/día dentro de transacción) |
| `Http/Middleware/AuthenticateApiToken.php` | Middleware auth por token |
| `Http/Requests/StoreUserRequest.php` | Validación registro |
| `Http/Requests/UpdateUserRequest.php` | Validación actualización usuario |
| `Http/Requests/StoreTransactionRequest.php` | Validación transferencia |
| `Http/Resources/UserResource.php` | Recurso API usuario |
| `Http/Resources/TransactionResource.php` | Recurso API transacción |
| `Models/User.php` | Modelo usuario (balance, api_token) |
| `Models/Transaction.php` | Modelo transacción |
| `Providers/AppServiceProvider.php` | Proveedor de aplicación |

### 2.4 Rutas
| Archivo | Descripción |
|---------|-------------|
| `routes/api.php` | Todas las rutas de la API (register, login, users, transactions, export, stats) |
| `routes/web.php` | Rutas web (welcome) |
| `routes/console.php` | Comandos de consola |

### 2.5 Base de datos
| Archivo | Descripción |
|---------|-------------|
| `database/migrations/0001_01_01_000000_create_users_table.php` | Tabla users |
| `database/migrations/0001_01_01_000001_create_cache_table.php` | Cache |
| `database/migrations/0001_01_01_000002_create_jobs_table.php` | Jobs |
| `database/migrations/2024_02_20_000003_add_balance_to_users_table.php` | Campo balance en users |
| `database/migrations/2024_02_20_000004_create_personal_access_tokens_table.php` | Sanctum tokens |
| `database/migrations/2024_02_20_000005_create_transactions_table.php` | Tabla transactions |
| `database/migrations/2024_02_20_000006_add_api_token_to_users_table.php` | api_token en users |
| `database/factories/UserFactory.php` | Factory para usuarios |
| `database/seeders/DatabaseSeeder.php` | Seeder (usuario test@example.com) |

### 2.6 Tests
| Archivo | Descripción |
|---------|-------------|
| `tests/TestCase.php` | Base para tests |
| `tests/Feature/ExampleTest.php` | Test de ejemplo Laravel |
| `tests/Feature/TransactionValidationTest.php` | Tests de validación y reglas de negocio (saldo, límite diario, idempotencia) |
| `tests/Unit/ExampleTest.php` | Test unitario de ejemplo |
| `tests/Unit/StoreUserRequestTest.php` | Validación StoreUserRequest |
| `tests/Unit/StoreTransactionRequestTest.php` | Validación StoreTransactionRequest |

### 2.7 Configuración, bootstrap, public, resources
- `config/*` (app, auth, cache, database, filesystems, logging, mail, queue, services, session)
- `bootstrap/app.php`, `bootstrap/providers.php`, `bootstrap/cache/.gitignore`
- `public/index.php`, `public/.htaccess`, `public/favicon.ico`, `public/robots.txt`
- `resources/views/welcome.blade.php`, `resources/css/app.css`, `resources/js/app.js`, `resources/js/bootstrap.js`
- `vite.config.js`
- Carpetas `storage/*` con `.gitignore` (logs, cache, sessions, views no se versionan)

---

## 3. Lo que está subido (rastreado por Git)

Git tiene **84 archivos** rastreados. Incluyen:

- Todo lo listado en las secciones 2.1–2.7 (README, composer, package.json, phpunit.xml, artisan, .env.example, .gitignore, .editorconfig, .gitattributes).
- **Ambos** archivos de análisis:
  - `analisis-limite-diario-transferencias.txt` (raíz)
  - `docs/analisis-limite-diario-transferencias.txt`
- Toda la app (Controllers, Models, Requests, Resources, Middleware).
- Rutas, migraciones, seeders, factories.
- Tests (Feature y Unit).
- Config, bootstrap, public, resources (views, css, js), vite.config.js.
- Archivos `.gitignore` de `bootstrap/cache`, `database`, `storage/*`.

**No están en el repositorio (y es correcto):**
- `.env` (secretos; solo existe `.env.example`)
- `vendor/` (se instala con `composer install`)
- `node_modules/`
- `storage/logs/*.log`, `storage/framework/views/*`, etc.
- `.phpunit.result.cache`

---

## 4. Comparación: ¿falta algo?

| Revisión | Estado |
|----------|--------|
| Código de la API (auth, usuarios, transacciones, límite diario) | Todo está en el repo |
| Límite diario 5.000 USD | Implementado dentro de `DB::transaction()` con `lockForUpdate()` (correcto) |
| Documentación (README, OpenAPI) | En el repo |
| Análisis límite diario | En el repo (en raíz y en `docs/`) |
| Tests (Feature + Unit) | En el repo |
| Scripts .bat y configuración (composer, phpunit, vite) | En el repo |
| Migraciones y seeders | En el repo |

**Conclusión:** No falta ningún archivo de proyecto que deba estar versionado. Lo que debe estar subido, está.

---

## 5. Detalle a tener en cuenta

- **Duplicado:** El archivo `analisis-limite-diario-transferencias.txt` está **dos veces**: en la **raíz** y en **docs/**. El contenido es el mismo. Para evitar confusión se puede dejar solo `docs/analisis-limite-diario-transferencias.txt` y eliminar el de la raíz (opcional).

---

## 6. Estado actual de Git (resumen)

- **Rama:** `main`
- **vs origin:** `ahead 1, behind 1` (tienes 1 commit local que no está en origin y origin tiene 1 commit que no tienes).
- **Archivos en staging:** Los dos análisis (`analisis-limite-diario-transferencias.txt` y `docs/analisis-limite-diario-transferencias.txt`) aparecen como añadidos (A).

**Recomendación:** Si quieres sincronizar con `origin/main`, haz `git pull --rebase` (o `git pull`) y luego `git push`. Si prefieres dejar solo el análisis en `docs/`, quita el de la raíz y haz commit de ese cambio.

---

## 7. Resumen ejecutivo

- El **proyecto** incluye la API completa (Laravel 12 + Sanctum), límite diario bien implementado, tests, documentación (README + OpenAPI) y el análisis del límite diario.
- **Todo** lo que debe estar versionado **está** en el repositorio; no falta nada crítico.
- Opcional: unificar el análisis en `docs/analisis-limite-diario-transferencias.txt` y sincronizar la rama con `origin/main`.

---
*Documento generado para inventario y comparación proyecto vs repositorio.*
