@echo off
chcp 65001 >nul
echo ============================================
echo   PROBAR API FINANCIERA (desde terminal)
echo ============================================
echo.
echo 1. REGISTRAR usuario
echo    Ejecutando: POST /api/register
echo.
curl -X POST http://127.0.0.1:8000/api/register -H "Content-Type: application/json" -H "Accept: application/json" -d "{\"name\":\"Maria\",\"email\":\"maria@test.com\",\"password\":\"12345678\",\"password_confirmation\":\"12345678\",\"initial_balance\":1000}"
echo.
echo.
echo 2. LOGIN (obtener token)
echo    Ejecutando: POST /api/login
echo.
curl -X POST http://127.0.0.1:8000/api/login -H "Content-Type: application/json" -H "Accept: application/json" -d "{\"email\":\"maria@test.com\",\"password\":\"12345678\"}"
echo.
echo.
echo Si ves un "token" en la respuesta del login, copialo.
echo Luego ejecuta probar-api-token.bat y pega el token cuando te lo pida.
echo.
pause
