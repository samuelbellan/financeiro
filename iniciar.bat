@echo off
title Sistema Financeiro
echo ===========================================
echo   Iniciando o Sistema de Financas de Casa  
echo ===========================================
echo.
echo O OmniRoute, ngrok e servidor local serao iniciados.
echo O navegador sera aberto automaticamente.
echo Para desligar o sistema, feche esta janela preta.
echo.

cd /d C:\Users\SaMuB\Documents\financeiro

:: 1. OmniRoute AI Gateway
netstat -ano | findstr :20128 | findstr LISTENING >nul
if errorlevel 1 (
    echo [1/4] Iniciando o OmniRoute AI Gateway em background...
    start /min "OmniRoute AI Gateway" cmd /c "cd /d C:\Users\SaMuB\Documents\OmniRoute && npm run dev"
) else (
    echo [1/4] OmniRoute AI Gateway ja esta ativo na porta 20128.
)

:: 2. ngrok
tasklist /fi "imagename eq ngrok.exe" 2>nul | find /i "ngrok.exe" >nul
if errorlevel 1 (
    echo [2/4] Iniciando tunel ngrok em background...
    start /min "ngrok" ngrok http --url=unfrosted-surreal-ducky.ngrok-free.dev 8000
) else (
    echo [2/4] Tunel ngrok ja esta em execucao.
)

:: 3. Abrindo navegador com breve delay para dar tempo ao PHP iniciar
echo [3/4] Abrindo o sistema no navegador...
start /min cmd /c "timeout /t 2 /nobreak >nul && start http://127.0.0.1:8000/home"

:: 4. Servidor PHP Laravel
echo [4/4] Iniciando o servidor PHP Laravel...
php -d upload_max_filesize=64M -d post_max_size=64M -d memory_limit=256M -d max_execution_time=0 artisan serve

