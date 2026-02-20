@echo off
chcp 65001 >nul
echo Instalando Laravel Sanctum...
echo.
cd /d "c:\laragon\www\api-financiera"

REM Laragon suele tener PHP y Composer en el PATH si abres "Terminal" desde Laragon
composer update laravel/sanctum --with-dependencies

if errorlevel 1 (
    echo.
    echo Si "composer" no se reconoce, abre Laragon, clic derecho - Terminal, y ahi ejecuta:
    echo   cd c:\laragon\www\api-financiera
    echo   composer update
    echo.
) else (
    echo.
    echo Listo. Ahora ejecuta: .\probar-api.bat
    echo.
)
pause
