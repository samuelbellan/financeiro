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

echo [1/4] Iniciando o OmniRoute AI Gateway em background...
start /min "OmniRoute AI Gateway" cmd /c "cd /d C:\Users\SaMuB\Documents\OmniRoute && npm run dev"

echo [2/4] Iniciando tunel ngrok em background...
start /min "ngrok" ngrok http --url=unfrosted-surreal-ducky.ngrok-free.dev 8000

echo [3/4] Abrindo o sistema no navegador...
start http://127.0.0.1:8000/home

echo [4/4] Iniciando o servidor PHP Laravel...
php -d upload_max_filesize=64M -d post_max_size=64M -d memory_limit=256M -d max_execution_time=0 artisan serve

