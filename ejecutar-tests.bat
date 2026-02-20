@echo off
chcp 65001 >nul
cd /d "c:\laragon\www\api-financiera"

set "PHPEXE="
if exist "C:\laragon\bin\php\php.exe" set "PHPEXE=C:\laragon\bin\php\php.exe"
if "%PHPEXE%"=="" for /d %%d in ("C:\laragon\bin\php\php-*") do (
    if exist "%%d\php.exe" set "PHPEXE=%%d\php.exe"
)
if not "%PHPEXE%"=="" goto :run

set "PHPEXE="
for /d %%d in ("D:\laragon\bin\php\php-*") do (
    if exist "%%d\php.exe" set "PHPEXE=%%d\php.exe"
)
if not "%PHPEXE%"=="" goto :run

echo No se encontro php.exe en C:\laragon\bin\php ni en D:\laragon\bin\php
echo.
echo Abre Laragon, clic derecho en el icono - Terminal, y ahi ejecuta:
echo   cd c:\laragon\www\api-financiera
echo   php artisan test
echo.
pause
exit /b 1

:run
echo ============================================
echo   EJECUTANDO TODOS LOS TESTS
echo ============================================
echo.
"%PHPEXE%" artisan test
echo.
pause
