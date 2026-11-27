# Cria (uma vez) o banco "concursos_restauracao", onde o backup:testar restaura o dump do Drive
# toda semana para provar que a cópia funciona. Não mexe no banco "concursos" em uso.
# Rodar na pasta do projeto (pede a senha do superusuário "postgres"):
#   powershell -ExecutionPolicy Bypass -File scripts\backup-teste-setup.ps1

param(
    [string]$PgBin = "C:\Program Files\PostgreSQL\18\bin",
    [string]$Banco = "concursos_restauracao"
)

$ErrorActionPreference = "Stop"
$Projeto = Split-Path -Parent $PSScriptRoot
Set-Location $Projeto
$psql = Join-Path $PgBin "psql.exe"
if (-not (Test-Path $psql)) { throw "psql.exe não encontrado em $PgBin" }

function Invoke-Psql([string]$Sql) {
    $ErrorActionPreference = "Continue"
    $saida = & $psql -h 127.0.0.1 -U postgres -d postgres -v ON_ERROR_STOP=1 -tAc $Sql 2>&1
    if ($LASTEXITCODE -ne 0) { throw "psql falhou: $saida" }
    return ($saida | Out-String).Trim()
}

$ok = $false
for ($tentativa = 1; $tentativa -le 3 -and -not $ok; $tentativa++) {
    $super = Read-Host "Senha do superusuário 'postgres' do Postgres local" -AsSecureString
    $env:PGPASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($super))
    $ErrorActionPreference = "Continue"
    & $psql -h 127.0.0.1 -U postgres -d postgres -tAc "SELECT 1" 2>&1 | Out-Null
    $ErrorActionPreference = "Stop"
    if ($LASTEXITCODE -eq 0) { $ok = $true } else { Write-Host "Senha recusada pelo Postgres ($tentativa/3)." -ForegroundColor Red }
}
if (-not $ok) { Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue; throw "Não foi possível entrar como 'postgres'. Nada foi alterado." }

try {
    if ((Invoke-Psql "SELECT 1 FROM pg_database WHERE datname='$Banco'") -eq "1") {
        Write-Host "O banco '$Banco' já existe." -ForegroundColor Yellow
    } else {
        Invoke-Psql "CREATE DATABASE $Banco OWNER concursos ENCODING 'UTF8' TEMPLATE template0" | Out-Null
        Write-Host "Banco '$Banco' criado (dono: concursos)." -ForegroundColor Green
    }
} finally {
    Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
}

Write-Host "Testando a restauração agora..." -ForegroundColor Cyan
php artisan backup:testar
if ($LASTEXITCODE -ne 0) { throw "O teste de restauração falhou (veja acima)." }
Write-Host "Pronto: o teste semanal agora restaura o banco de concursos de verdade." -ForegroundColor Green