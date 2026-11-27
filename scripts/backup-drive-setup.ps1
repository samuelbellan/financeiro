# Agenda o backup cruzado PC <-> Google Drive (php artisan backup:drive) de hora em hora:
#   banco SQLite (no PC) -> cópia no Drive; arquivos (no Drive) -> espelho no PC;
#   banco de concursos (Postgres) -> pg_dump no Drive (concursos:backup, chamado pelo backup:drive).
# E a conferência (php artisan backup:conferir) a cada 3 h: se o backup atrasou, roda e avisa no Telegram.
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

# A tarefa diária antiga do Postgres virou parte do backup de hora em hora.
if (Get-ScheduledTask -TaskName "Financeiro - Backup Concursos" -ErrorAction SilentlyContinue) {
    Unregister-ScheduledTask -TaskName "Financeiro - Backup Concursos" -Confirm:$false
    Write-Host "Tarefa diária 'Financeiro - Backup Concursos' removida (agora roda junto, de hora em hora)." -ForegroundColor Yellow
}

# Conferência a cada 3 h, nos minutos :30 (meia hora depois do backup).
$logConf = Join-Path $Projeto "storage\logs\backup-conferir.log"
$acaoConf = New-ScheduledTaskAction -Execute "cmd.exe" `
    -Argument "/c php artisan backup:conferir >> `"$logConf`" 2>&1" `
    -WorkingDirectory $Projeto
$inicioConf = $inicio.AddMinutes(30)
$gatilhoConf = New-ScheduledTaskTrigger -Once -At $inicioConf -RepetitionInterval (New-TimeSpan -Hours 3)
Register-ScheduledTask -TaskName "Financeiro - Conferir Backup" -Action $acaoConf -Trigger $gatilhoConf -Settings $config -Force | Out-Null
Write-Host "Conferência do backup a cada 3 h a partir de $($inicioConf.ToString('HH:mm')) (tarefa 'Financeiro - Conferir Backup')." -ForegroundColor Green

# Teste de restauração toda semana (domingo 10:00): restaura as cópias do Drive em bancos
# temporários, confere se estão completas e manda o resultado no Telegram.
$logTeste = Join-Path $Projeto "storage\logs\backup-testar.log"
$acaoTeste = New-ScheduledTaskAction -Execute "cmd.exe" `
    -Argument "/c php artisan backup:testar >> `"$logTeste`" 2>&1" `
    -WorkingDirectory $Projeto
$gatilhoTeste = New-ScheduledTaskTrigger -Weekly -DaysOfWeek Sunday -At "10:00"
Register-ScheduledTask -TaskName "Financeiro - Testar Backup" -Action $acaoTeste -Trigger $gatilhoTeste -Settings $config -Force | Out-Null
Write-Host "Teste de restauração todo domingo às 10:00 (tarefa 'Financeiro - Testar Backup')." -ForegroundColor Green