@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion
set /p TOKEN="Pega aqui el token (sin comillas): "
REM El token tiene | que CMD interpreta como pipe; escapamos para que curl reciba el token completo
set "TOKEN=!TOKEN:|=^|!"
echo.
echo Enviando GET /api/me con tu token...
echo.
curl -X GET http://127.0.0.1:8000/api/me -H "Accept: application/json" -H "Authorization: Bearer !TOKEN!"
echo.
echo.
pause
