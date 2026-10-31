@echo off
title Conectar Celular - Sistema Financeiro
color 0A
cls

cd /d C:\Users\SaMuB\Documents\financeiro

:: Ler porta configurada no .env (se nao definida, padrao 8001)
set PORT=8001
for /f "tokens=1,2 delims==" %%a in ('findstr /r "^SERVER_PORT=" .env') do (
    set PORT=%%b
)
set PORT=%PORT: =%

set LOCAL_IP=192.168.15.11
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4" ^| findstr /v "172. 169.254"') do (
    for /f "tokens=1" %%b in ("%%a") do (
        set LOCAL_IP=%%b
    )
)

echo ========================================================================
echo          CONECTAR CELULAR NA REDE LOCAL (WI-FI) - PORTA %PORT%
echo ========================================================================
echo.
echo   1. Certifique-se de que o celular esta conectado no mesmo Wi-Fi de casa.
echo.
echo   2. No celular, acesse o link:
echo      http://%LOCAL_IP%:%PORT%
echo.
echo   3. Ou aponte a camera do celular para o QR Code abaixo:
echo.
echo ========================================================================
echo.

curl.exe -s --connect-timeout 3 "https://qrenco.de/http://%LOCAL_IP%:%PORT%?inline=true" 2>nul

echo.
echo ========================================================================
echo Abrindo painel visual de conexao com QR Code em alta resolucao no navegador...
start http://localhost:%PORT%/celular
echo.
echo Pressione qualquer tecla para fechar esta janela...
pause >nul
