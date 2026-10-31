# Script de Correcao do Acesso ao Gemini no Chrome
# Desativa a interceptacao de rede legada (LegendasBrasil / NetFilterSDK) que quebra o HTTP/2 no Chrome

Write-Host "=== 1. Interrompendo e desativando o servico ComLegService ===" -ForegroundColor Cyan
try {
    Stop-Service ComLegService -Force -ErrorAction SilentlyContinue
    Set-Service ComLegService -StartupType Disabled -ErrorAction SilentlyContinue
    Write-Host "[OK] Servico ComLegService desativado com sucesso." -ForegroundColor Green
} catch {
    Write-Host "[!] Erro ao desativar ComLegService: $_" -ForegroundColor Yellow
}

Write-Host "`n=== 2. Interrompendo e desativando o driver legendasdrv ===" -ForegroundColor Cyan
cmd.exe /c "sc.exe stop legendasdrv"
cmd.exe /c "sc.exe config legendasdrv start=disabled"
Write-Host "[OK] Driver legendasdrv desativado." -ForegroundColor Green

Write-Host "`n=== 3. Removendo certificados raiz NetFilterSDK (MITM) ===" -ForegroundColor Cyan
$certsLM = Get-ChildItem Cert:\LocalMachine\Root | Where-Object { $_.Subject -match 'NetFilter' }
$countLM = $certsLM.Count
$certsLM | Remove-Item -ErrorAction SilentlyContinue
Write-Host "[OK] $countLM certificados NetFilterSDK removidos de LocalMachine\Root." -ForegroundColor Green

$certsCU = Get-ChildItem Cert:\CurrentUser\Root | Where-Object { $_.Subject -match 'NetFilter' }
$countCU = $certsCU.Count
$certsCU | Remove-Item -ErrorAction SilentlyContinue
Write-Host "[OK] $countCU certificados NetFilterSDK removidos de CurrentUser\Root." -ForegroundColor Green

Write-Host "`n=== 4. Reiniciando os processos do Google Chrome ===" -ForegroundColor Cyan
Get-Process chrome -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Write-Host "[OK] Processos antigos do Chrome finalizados." -ForegroundColor Green

Write-Host "`n========================================================" -ForegroundColor Magenta
Write-Host " CONCLUIDO COM SUCESSO! " -ForegroundColor Green
Write-Host " Abra o Google Chrome normalmente e acesse https://gemini.google.com/" -ForegroundColor White
Write-Host "========================================================" -ForegroundColor Magenta
Start-Sleep -Seconds 3
