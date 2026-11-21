# Deixa o sistema acessível de fora com endereço FIXO (Tailscale Funnel) e subindo sozinho com o Windows.
# Rodar UMA vez, em um PowerShell ABERTO COMO ADMINISTRADOR, na pasta do projeto:
#   powershell -ExecutionPolicy Bypass -File scripts\servidor-setup.ps1
#
# Pré-requisitos (feitos por você, uma vez):
#   1. Instalar o Tailscale:  winget install --id Tailscale.Tailscale
#   2. Entrar na conta:       tailscale up   (abre o navegador para login)
#
# O que este script faz:
#   - Cria a tarefa "Financeiro - Servidor", que sobe o php artisan serve na inicialização do Windows
#     (mesmo sem ninguém fazer login) e o reinicia se cair.
#   - Liga o Funnel apontando para o servidor e grava o endereço público em APP_PUBLIC_URL no .env.

param([int]$Porta = 0)

$ErrorActionPreference = "Stop"
$Projeto = Split-Path -Parent $PSScriptRoot
Set-Location $Projeto
$EnvFile = Join-Path $Projeto ".env"

$admin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $admin) { throw "Abra o PowerShell como Administrador e rode de novo." }

if ($Porta -eq 0) {
    $linha = Select-String -Path $EnvFile -Pattern '^SERVER_PORT=(\d+)' | Select-Object -First 1
    $Porta = if ($linha) { [int]$linha.Matches[0].Groups[1].Value } else { 8000 }
}

$tailscale = "C:\Program Files\Tailscale\tailscale.exe"
if (-not (Test-Path $tailscale)) {
    throw "Tailscale não encontrado. Instale com:  winget install --id Tailscale.Tailscale  e depois rode:  tailscale up"
}

$ErrorActionPreference = "Continue"
$estado = & $tailscale status --json 2>&1 | Out-String
$ErrorActionPreference = "Stop"
if ($LASTEXITCODE -ne 0 -or $estado -notmatch '"BackendState":\s*"Running"') {
    throw "O Tailscale não está conectado. Rode:  tailscale up  (e faça login no navegador)."
}
$dns = (($estado | ConvertFrom-Json).Self.DNSName).TrimEnd('.')
if (-not $dns) { throw "Não consegui descobrir o nome deste PC no Tailscale (MagicDNS desligado?)." }
$UrlPublica = "https://$dns"

# --- 1. Servidor PHP como tarefa de inicialização ----------------------------------
$php = (Get-Command php -ErrorAction Stop).Source
$log = Join-Path $Projeto "storage\logs\servidor.log"
$argumentos = "/c cd /d `"$Projeto`" && `"$php`" -d upload_max_filesize=128M -d post_max_size=128M -d memory_limit=512M -d max_execution_time=0 artisan serve --host=0.0.0.0 --port=$Porta >> `"$log`" 2>&1"
$acao = New-ScheduledTaskAction -Execute "cmd.exe" -Argument $argumentos -WorkingDirectory $Projeto
$gatilho = New-ScheduledTaskTrigger -AtStartup
$conta = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
$config = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries `
    -ExecutionTimeLimit ([TimeSpan]::Zero) -RestartCount 999 -RestartInterval (New-TimeSpan -Minutes 1)
Register-ScheduledTask -TaskName "Financeiro - Servidor" -Action $acao -Trigger $gatilho -Principal $conta -Settings $config -Force | Out-Null

$ocupada = Get-NetTCPConnection -LocalPort $Porta -State Listen -ErrorAction SilentlyContinue
if (-not $ocupada) {
    Start-ScheduledTask -TaskName "Financeiro - Servidor"
    Start-Sleep -Seconds 3
}
Write-Host "Servidor agendado na inicialização do Windows (porta $Porta)." -ForegroundColor Green

# --- 2. Funnel ------------------------------------------------------------------
# --bg: a configuração fica salva no serviço do Tailscale e volta sozinha após reiniciar.
# Sem capturar a saída: na primeira vez o Tailscale mostra um link para liberar o Funnel
# e FICA ESPERANDO até você liberar no navegador; depois segue sozinho.
Write-Host "Ligando o Funnel. Se aparecer um link abaixo, abra-o no navegador e libere o Funnel; o script continua sozinho." -ForegroundColor Cyan
$ErrorActionPreference = "Continue"
& $tailscale funnel --bg $Porta
$ErrorActionPreference = "Stop"
if ($LASTEXITCODE -ne 0) { throw "O Funnel não foi ligado (veja a mensagem acima)." }

$linhas = [System.Collections.Generic.List[string]](Get-Content $EnvFile -Encoding UTF8)
$idx = $linhas.FindIndex([Predicate[string]]{ param($l) $l -match '^APP_PUBLIC_URL=' })
if ($idx -ge 0) { $linhas[$idx] = "APP_PUBLIC_URL=$UrlPublica" } else { $linhas.Add("APP_PUBLIC_URL=$UrlPublica") }
[System.IO.File]::WriteAllLines($EnvFile, $linhas, (New-Object System.Text.UTF8Encoding($false)))
php artisan config:clear | Out-Null

Write-Host ""
Write-Host "Endereço público fixo: $UrlPublica" -ForegroundColor Cyan
Write-Host ""
Write-Host "Falta 1 passo seu no Google Cloud Console (APIs e serviços > Credenciais > seu cliente OAuth):" -ForegroundColor Yellow
Write-Host "  URIs de redirecionamento autorizados  ->  adicionar:  $UrlPublica/auth/google/callback"
Write-Host "  Origens JavaScript autorizadas        ->  adicionar:  $UrlPublica"
