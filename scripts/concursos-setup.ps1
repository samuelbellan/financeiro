# Prepara o PC servidor para o módulo de concursos (rodar UMA vez, na pasta do projeto):
#   1. Cria o usuário e o banco "concursos" no Postgres local (pede a senha do superusuário "postgres")
#   2. Grava as variáveis CONCURSOS_* no .env (a senha do usuário "concursos" é gerada aqui)
#   3. Agenda o backup (de hora em hora, conferência e teste semanal) via scripts\backup-drive-setup.ps1
#
# Uso:  powershell -ExecutionPolicy Bypass -File scripts\concursos-setup.ps1
#       (opcional) -BackupDir "G:\Meu Drive\Backups\concursos"  -PgBin "C:\Program Files\PostgreSQL\18\bin"

param(
    [string]$BackupDir = "G:\Meu Drive\Backups\concursos",
    [string]$PgBin = "C:\Program Files\PostgreSQL\18\bin",
    [string]$Horario = "03:00"
)

$ErrorActionPreference = "Stop"
$Projeto = Split-Path -Parent $PSScriptRoot
Set-Location $Projeto
$EnvFile = Join-Path $Projeto ".env"
if (-not (Test-Path $EnvFile)) { throw ".env não encontrado em $Projeto" }

$psql = Join-Path $PgBin "psql.exe"
if (-not (Test-Path $psql)) { throw "psql.exe não encontrado em $PgBin" }

# --- 1. Banco -----------------------------------------------------------------
# Comandos nativos (psql): o código de saída é conferido à mão. Dentro destas chamadas o
# ErrorActionPreference fica em Continue, porque no PowerShell 5.1 qualquer linha no stderr
# com "Stop" vira exceção antes de dar tempo de ler $LASTEXITCODE.
function Invoke-Psql([string]$Sql, [switch]$Valor) {
    $ErrorActionPreference = "Continue"
    $saida = if ($Valor) {
        & $psql -h 127.0.0.1 -U postgres -d postgres -v ON_ERROR_STOP=1 -tAc $Sql 2>&1
    } else {
        & $psql -h 127.0.0.1 -U postgres -d postgres -v ON_ERROR_STOP=1 -c $Sql 2>&1
    }
    if ($LASTEXITCODE -ne 0) { throw "psql falhou: $saida" }
    return ($saida | Out-String).Trim()
}

# Confere a senha do superusuário antes de qualquer coisa (até 3 tentativas).
$ok = $false
for ($tentativa = 1; $tentativa -le 3 -and -not $ok; $tentativa++) {
    $super = Read-Host "Senha do superusuário 'postgres' do Postgres local" -AsSecureString
    $env:PGPASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($super))
    $ErrorActionPreference = "Continue"
    & $psql -h 127.0.0.1 -U postgres -d postgres -tAc "SELECT 1" 2>&1 | Out-Null
    $ErrorActionPreference = "Stop"
    if ($LASTEXITCODE -eq 0) { $ok = $true } else { Write-Host "Senha recusada pelo Postgres ($tentativa/3)." -ForegroundColor Red }
}
if (-not $ok) {
    Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
    throw "Não foi possível entrar como 'postgres'. Nada foi alterado no banco nem no .env."
}

$chars = [char[]]'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'
$senha = -join (1..32 | ForEach-Object { $chars | Get-Random })

try {
    if ((Invoke-Psql "SELECT 1 FROM pg_roles WHERE rolname='concursos'" -Valor) -eq "1") {
        Invoke-Psql "ALTER ROLE concursos WITH LOGIN PASSWORD '$senha'" | Out-Null
        Write-Host "Usuário 'concursos' já existia: senha redefinida." -ForegroundColor Yellow
    } else {
        Invoke-Psql "CREATE ROLE concursos WITH LOGIN PASSWORD '$senha'" | Out-Null
        Write-Host "Usuário 'concursos' criado." -ForegroundColor Green
    }

    if ((Invoke-Psql "SELECT 1 FROM pg_database WHERE datname='concursos'" -Valor) -ne "1") {
        Invoke-Psql "CREATE DATABASE concursos OWNER concursos ENCODING 'UTF8' TEMPLATE template0" | Out-Null
        Write-Host "Banco 'concursos' criado." -ForegroundColor Green
    } else {
        Write-Host "Banco 'concursos' já existia." -ForegroundColor Yellow
    }
} finally {
    Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
}

# --- 2. .env ------------------------------------------------------------------
$valores = [ordered]@{
    "CONCURSOS_ENABLED"       = "true"
    "CONCURSOS_DB_HOST"       = "127.0.0.1"
    "CONCURSOS_DB_PORT"       = "5432"
    "CONCURSOS_DB_DATABASE"   = "concursos"
    "CONCURSOS_DB_USERNAME"   = "concursos"
    "CONCURSOS_DB_PASSWORD"   = $senha
    # Aspas simples: no .env, aspas duplas tratam "\" como escape e quebram caminhos do Windows.
    "CONCURSOS_BACKUP_DIR"    = "'$BackupDir'"
    "CONCURSOS_PG_BIN"        = "'$PgBin'"
}
$linhas = [System.Collections.Generic.List[string]](Get-Content $EnvFile -Encoding UTF8)
foreach ($k in $valores.Keys) {
    $nova = "$k=$($valores[$k])"
    $idx = $linhas.FindIndex([Predicate[string]]{ param($l) $l -match "^$k=" })
    if ($idx -ge 0) { $linhas[$idx] = $nova } else { $linhas.Add($nova) }
}
[System.IO.File]::WriteAllLines($EnvFile, $linhas, (New-Object System.Text.UTF8Encoding($false)))
Write-Host ".env atualizado com CONCURSOS_*." -ForegroundColor Green

New-Item -ItemType Directory -Force -Path $BackupDir | Out-Null
php artisan config:clear | Out-Null
if ($LASTEXITCODE -ne 0) { throw "O Laravel não conseguiu ler o .env depois da alteração. Confira as linhas CONCURSOS_*." }

# --- 2b. Python para ler os PDFs do TEC (pdfplumber num venv do projeto) -------
$venvPy = Join-Path $Projeto ".venv-concursos\Scripts\python.exe"
if (-not (Test-Path $venvPy)) {
    python -m venv (Join-Path $Projeto ".venv-concursos")
    if ($LASTEXITCODE -ne 0) { throw "Não consegui criar o venv Python (.venv-concursos)." }
}
& $venvPy -m pip install -q -r (Join-Path $Projeto "scripts\requirements-concursos.txt")
if ($LASTEXITCODE -ne 0) { throw "Falha ao instalar o pdfplumber no .venv-concursos." }
Write-Host "Leitor de PDF (pdfplumber) pronto." -ForegroundColor Green

# --- 3. Backup -----------------------------------------------------------------
# O pg_dump roda de hora em hora dentro do backup:drive (scripts\backup-drive-setup.ps1).
& (Join-Path $PSScriptRoot "backup-drive-setup.ps1")

Write-Host ""
Write-Host "Testando conexão e backup agora..." -ForegroundColor Cyan
php artisan concursos:backup
if ($LASTEXITCODE -ne 0) { throw "O backup de teste falhou (veja a mensagem acima)." }
Write-Host "Tudo pronto." -ForegroundColor Green
