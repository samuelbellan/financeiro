@echo off
title Sistema Financeiro
echo ===========================================
echo   Iniciando o Sistema de Financas de Casa  
echo ===========================================
echo.
echo O ngrok e o servidor local serao iniciados.
echo O navegador sera aberto automaticamente.
echo Para desligar o sistema, feche esta janela preta.
echo.

cd /d C:\Users\SaMuB\Documents\financeiro

:: 1. ngrok
tasklist /fi "imagename eq ngrok.exe" 2>nul | find /i "ngrok.exe" >nul
if errorlevel 1 (
    echo [1/3] Iniciando tunel ngrok em background...
    start /min "ngrok" ngrok http --url=unfrosted-surreal-ducky.ngrok-free.dev 8000
) else (
    echo [1/3] Tunel ngrok ja esta em execucao.
)

:: 2. Abrindo navegador com breve delay para dar tempo ao PHP iniciar
echo [2/3] Abrindo o sistema no navegador...
start /min cmd /c "timeout /t 2 /nobreak >nul && start http://127.0.0.1:8000/home"

:: 3. Servidor PHP Laravel
echo [3/3] Iniciando o servidor PHP Laravel...
php -d upload_max_filesize=64M -d post_max_size=64M -d memory_limit=256M -d max_execution_time=0 artisan serve


