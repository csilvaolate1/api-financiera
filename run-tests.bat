@echo off
cd /d "%~dp0"
set PHP_CMD=
if defined PHP_CMD goto run
where php >nul 2>&1 && set PHP_CMD=php
if defined PHP_CMD goto run
if exist "c:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" set PHP_CMD=c:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe
if defined PHP_CMD goto run
if exist "c:\laragon\bin\php\php-8.2.26-Win32-vs16-x64\php.exe" set PHP_CMD=c:\laragon\bin\php\php-8.2.26-Win32-vs16-x64\php.exe
if defined PHP_CMD goto run
for /f "delims=" %%D in ('dir /b /ad "c:\laragon\bin\php\php-*" 2^>nul') do (
  if exist "c:\laragon\bin\php\%%D\php.exe" set "PHP_CMD=c:\laragon\bin\php\%%D\php.exe" & goto run
)
:run
if not defined PHP_CMD (
  echo PHP no encontrado. Anade PHP al PATH o ejecuta desde la terminal de Laragon.
  echo Ejemplo: php artisan test
  pause
  exit /b 1
)
echo Ejecutando tests con: %PHP_CMD%
"%PHP_CMD%" artisan config:clear --ansi
"%PHP_CMD%" artisan test
echo.
pause
exit /b %ERRORLEVEL%
