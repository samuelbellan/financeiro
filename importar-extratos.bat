@echo off
title Importador de Extratos Bancarios - OFX
color 0B
cls

cd /d C:\Users\SaMuB\Documents\financeiro

echo ========================================================================
echo         IMPORTADOR DE EXTRATOS BANCARIOS (.OFX) - FINANCEIRO
echo ========================================================================
echo.
echo Procurando arquivos .ofx na pasta:
echo   C:\Users\SaMuB\Documents\financeiro\extratos\
echo.

php artisan ofx:import

echo.
echo ========================================================================
echo Processamento concluido!
echo Pressione qualquer tecla para fechar esta janela...
pause >nul
