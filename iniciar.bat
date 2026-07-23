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

echo [1/3] Iniciando tunel ngrok em background...
start /min "ngrok" ngrok http --url=unfrosted-surreal-ducky.ngrok-free.dev 8000

echo [2/3] Abrindo o sistema no navegador...
start http://127.0.0.1:8000/home

echo [3/3] Iniciando o servidor PHP Laravel...
php artisan serve
