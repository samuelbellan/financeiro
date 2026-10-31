@echo off
title Sistema Financeiro
cd /d C:\Users\SaMuB\Documents\financeiro

:: Ler porta configurada no .env (se nao definida, padrao 8001)
set PORT=8001
for /f "tokens=1,2 delims==" %%a in ('findstr /r "^SERVER_PORT=" .env') do (
    set PORT=%%b
)
set PORT=%PORT: =%

:: Detectar IP local ativo na rede Wi-Fi / Ethernet
set LOCAL_IP=192.168.15.11
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4" ^| findstr /v "172. 169.254"') do (
    for /f "tokens=1" %%b in ("%%a") do (
        set LOCAL_IP=%%b
    )
)

echo ========================================================================
echo                 SISTEMA DE FINANCAS DE CASA (Porta %PORT%)
echo ========================================================================
echo.
echo   [NO COMPUTADOR] Acesse: http://localhost:%PORT%
echo   [NO CELULAR]    Acesse: http://%LOCAL_IP%:%PORT%
echo.
echo   * O celular deve estar conectado no mesmo Wi-Fi deste computador.
echo   * Acesso direto pela rede local: super rapido, sem limites de consumo!
echo.
echo ========================================================================
echo   Aponte a camera do celular para conectar diretamente:
echo.
curl.exe -s --connect-timeout 2 "https://qrenco.de/http://%LOCAL_IP%:%PORT%?inline=true" 2>nul
echo.
echo ========================================================================
echo O navegador sera aberto automaticamente.
echo Para desligar o sistema, feche esta janela preta.
echo ========================================================================
echo.

:: 1. ngrok (para webhooks externos caso ativo)
tasklist /fi "imagename eq ngrok.exe" 2>nul | find /i "ngrok.exe" >nul
if errorlevel 1 (
    echo [1/4] Iniciando tunel ngrok em background na porta %PORT%...
    start /min "ngrok" ngrok http --url=unfrosted-surreal-ducky.ngrok-free.dev %PORT%
) else (
    echo [1/4] Tunel ngrok ja esta em execucao.
)

:: 2. Configurar webhook do Telegram
echo [2/4] Configurando webhook do Telegram...
php artisan telegram:set-webhook https://unfrosted-surreal-ducky.ngrok-free.dev/webhook/telegram >nul 2>&1

:: 3. Abrindo navegador com breve delay para dar tempo ao PHP iniciar
echo [3/4] Abrindo o sistema no navegador...
start /min cmd /c "timeout /t 2 /nobreak >nul && start http://127.0.0.1:%PORT%/home"

:: 4. Servidor PHP Laravel escutando em toda a rede (0.0.0.0:%PORT%)
echo [4/4] Iniciando o servidor PHP Laravel escutando em 0.0.0.0:%PORT%...
set PHP_CLI_SERVER_WORKERS=8
php -d upload_max_filesize=128M -d post_max_size=128M -d memory_limit=512M -d max_execution_time=0 artisan serve --host=0.0.0.0 --port=%PORT%
