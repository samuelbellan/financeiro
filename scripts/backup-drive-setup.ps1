# Agenda o backup cruzado PC <-> Google Drive (php artisan backup:drive) de hora em hora:
#   banco SQLite (no PC) -> cópia no Drive; arquivos (no Drive) -> espelho no PC.
# Rodar UMA vez, na pasta do projeto:
#   powershell -ExecutionPolicy Bypass -File scripts\backup-drive-setup.ps1

$ErrorActionPreference = "Stop"
$Projeto = Split-Path -Parent $PSScriptRoot
Set-Location $Projeto

$log = Join-Path $Projeto "storage\logs\backup-drive.log"
$acao = New-ScheduledTaskAction -Execute "cmd.exe" `
    -Argument "/c php artisan backup:drive >> `"$log`" 2>&1" `
    -WorkingDirectory $Projeto
# Começa na próxima hora cheia e repete a cada hora, indefinidamente.
$inicio = (Get-Date).Date.AddHours((Get-Date).Hour + 1)
$gatilho = New-ScheduledTaskTrigger -Once -At $inicio -RepetitionInterval (New-TimeSpan -Hours 1)
# StartWhenAvailable: se o PC estava desligado no horário, roda assim que ligar.
$config = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopIfGoingOnBatteries -AllowStartIfOnBatteries `
    -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 30)
Register-ScheduledTask -TaskName "Financeiro - Backup Drive" -Action $acao -Trigger $gatilho -Settings $config -Force | Out-Null
Write-Host "Backup agendado de hora em hora a partir de $($inicio.ToString('HH:mm')) (tarefa 'Financeiro - Backup Drive')." -ForegroundColor Green
