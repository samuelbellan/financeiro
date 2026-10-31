@echo off
title Liberar Porta no Firewall do Windows
cd /d C:\Users\SaMuB\Documents\financeiro

:: Ler porta configurada no .env (padrao 8001)
set PORT=8001
for /f "tokens=1,2 delims==" %%a in ('findstr /r "^SERVER_PORT=" .env') do (
    set PORT=%%b
)
set PORT=%PORT: =%

echo ========================================================================
echo         LIBERAR PORTA %PORT% NO FIREWALL DO WINDOWS (WI-FI LOCAL)
echo ========================================================================
echo.
echo Tentando adicionar regra para a porta TCP %PORT%...
echo.

netsh advfirewall firewall add rule name="Laravel Dev %PORT%" dir=in action=allow protocol=TCP localport=%PORT%

if errorlevel 1 (
    echo.
    echo ========================================================================
    echo ATENCAO: Para liberar o firewall, este arquivo precisa ser executado
    echo como ADMINISTRADOR!
    echo.
    echo Clique com o botao direito em "liberar-firewall.bat" e selecione:
    echo "Executar como administrador"
    echo ========================================================================
) else (
    echo.
    echo ========================================================================
    echo SUCESSO! A porta %PORT% foi liberada no Firewall do Windows.
    echo Celulares no mesmo Wi-Fi poderao acessar normalmente!
    echo ========================================================================
)

echo.
pause
