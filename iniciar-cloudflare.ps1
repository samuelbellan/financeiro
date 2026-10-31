# PowerShell script para gerenciar o Cloudflare Tunnel com captura automatica da URL
$Host.UI.RawUI.WindowTitle = "Cloudflare Tunnel - Acesso Remoto"

param(
    [int]$Port = 0
)

# Se nao foi passada porta por parametro, ler do .env ou usar 8001
if ($Port -eq 0) {
    if (Test-Path ".env") {
        $envPort = (Get-Content ".env" | Select-String "^SERVER_PORT=(\d+)")
        if ($envPort -and $envPort.Matches[0].Groups[1].Value) {
            $Port = [int]$envPort.Matches[0].Groups[1].Value
        }
    }
    if ($Port -eq 0) { $Port = 8001 }
}

Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host "         INICIANDO CLOUDFLARE TUNNEL (ACESSO REMOTO / 4G)             " -ForegroundColor Cyan
Write-Host "========================================================================" -ForegroundColor Cyan
Write-Host "Porta local configurada: $Port" -ForegroundColor Gray
Write-Host ""

# 1. Verificar se a porta local está ativa
$portOpen = $false
try {
    $tcp = New-Object System.Net.Sockets.TcpClient
    $iar = $tcp.BeginConnect("127.0.0.1", $Port, $null, $null)
    $success = $iar.AsyncWaitHandle.WaitOne(1000)
    if ($success -and $tcp.Connected) {
        $portOpen = $true
        $tcp.EndConnect($iar)
    }
    $tcp.Close()
} catch {}

if (-not $portOpen) {
    Write-Host "[AVISO] O servidor local na porta $Port nao respondeu." -ForegroundColor Yellow
    Write-Host "        Certifique-se de que o 'iniciar.bat' esta aberto e rodando!" -ForegroundColor Yellow
    Write-Host ""
}

Write-Host "Conectando aos servidores da Cloudflare, aguarde alguns segundos..." -ForegroundColor Gray

# 2. Iniciar cloudflared e capturar a URL em tempo real
$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName = "cloudflared"
$psi.Arguments = "tunnel --url http://localhost:$Port"
$psi.RedirectStandardError = $true
$psi.UseShellExecute = $false
$psi.CreateNoWindow = $true

try {
    $proc = [System.Diagnostics.Process]::Start($psi)
    $url = $null
    $startTime = Get-Date

    while (-not $proc.StandardError.EndOfStream) {
        $line = $proc.StandardError.ReadLine()
        if ($line -match '(https://[a-zA-Z0-9-]+\.trycloudflare\.com)') {
            $url = $matches[1]
            break
        }
        if (((Get-Date) - $startTime).TotalSeconds -gt 30) {
            break
        }
    }

    if ($url) {
        # Copiar automaticamente para a area de transferencia
        try {
            Set-Clipboard -Value $url
            $copied = $true
        } catch {
            $copied = $false
        }

        Clear-Host
        Write-Host "========================================================================" -ForegroundColor Green
        Write-Host "                 CLOUDFLARE TUNNEL ATIVO COM SUCESSO!                  " -ForegroundColor Green
        Write-Host "========================================================================" -ForegroundColor Green
        Write-Host ""
        Write-Host "  SUA URL PARA ACESSAR DE QUALQUER LUGAR (FORA DA REDE / 4G / TRABALHO):" -ForegroundColor White
        Write-Host ""
        Write-Host "  >>>  $url  <<<" -ForegroundColor Yellow
        Write-Host ""
        if ($copied) {
            Write-Host "  [OK] URL ja foi COPIADA para a sua Area de Transferencia!" -ForegroundColor Green
            Write-Host "       (Basta dar Ctrl+V no WhatsApp para mandar para o celular)" -ForegroundColor Gray
        }
        Write-Host ""
        Write-Host "  Aponte a camera do seu celular para o QR Code abaixo:" -ForegroundColor Cyan
        Write-Host ""
        
        # Gerar QR code no terminal
        try {
            & curl.exe -s --connect-timeout 3 "https://qrenco.de/$url?inline=true"
        } catch {}
        
        Write-Host ""
        Write-Host "========================================================================" -ForegroundColor Green
        Write-Host "  IMPORTANTE: Mantenha esta janela aberta!" -ForegroundColor Yellow
        Write-Host "  Enquanto esta janela estiver aberta, o sistema estara acessivel pelo link." -ForegroundColor Gray
        Write-Host "  Para encerrar o tunel externo, feche esta janela ou pressione Ctrl+C." -ForegroundColor Gray
        Write-Host "========================================================================" -ForegroundColor Green
        Write-Host ""
        Write-Host "Logs de trafego do tunel:" -ForegroundColor DarkGray

        # Continuar exibindo os logs
        while (-not $proc.StandardError.EndOfStream) {
            $line = $proc.StandardError.ReadLine()
            if ($line -notmatch 'precheck|curve|metric') {
                Write-Host $line -ForegroundColor DarkGray
            }
        }
    } else {
        Write-Host ""
        Write-Host "[ERRO] Nao foi possivel obter a URL da Cloudflare." -ForegroundColor Red
        Write-Host "       Verifique sua conexao de internet ou tente novamente." -ForegroundColor Red
        Read-Host "Pressione Enter para sair..."
    }
} finally {
    if ($proc -and -not $proc.HasExited) {
        $proc.Kill()
    }
}
