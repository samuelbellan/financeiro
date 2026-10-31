<?php

declare(strict_types=1);

/**
 * Script & Endpoint de Importação de Extratos OFX
 *
 * Pode ser executado de 3 formas:
 * 1. Via Navegador (GET): Exibe interface amigável para envio de arquivos OFX
 * 2. Via HTTP POST (API/Form): Recebe arquivo enviado via multipart/form-data e retorna JSON estruturado
 * 3. Via Linha de Comando (CLI): php importar_ofx.php [caminho_arquivo_ou_pasta] [--user=1]
 */

// Carregar autoloader do Composer
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
}

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Autoload manual das classes do módulo caso o composer não esteja disponível
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/../app/';
        if (!is_dir($baseDir)) {
            $baseDir = __DIR__ . '/app/';
        }
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

use App\Services\Ofx\OfxImportService;
use App\Services\Ofx\OfxParserService;

/**
 * Função auxiliar para obter conexão PDO resiliente (via Laravel ou lendo .env puro).
 */
function getPdoConnection(): PDO
{
    // 1. Tentar obter PDO pelo Laravel caso o framework já esteja carregado
    if (class_exists(\Illuminate\Support\Facades\DB::class)) {
        try {
            return \Illuminate\Support\Facades\DB::connection()->getPdo();
        } catch (\Throwable) {
            // Continua para o fallback nativo
        }
    }

    // 2. Fallback nativo: Ler arquivo .env na raiz do projeto
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        $envPath = __DIR__ . '/.env';
    }

    $env = [];
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim(trim($v), "\"'");
            }
        }
    }

    $connection = $env['DB_CONNECTION'] ?? 'mysql';

    if ($connection === 'sqlite') {
        $dbPath = $env['DB_DATABASE'] ?? 'database/database.sqlite';
        if (!str_starts_with($dbPath, '/') && !preg_match('/^[A-Za-z]:/', $dbPath)) {
            $dbPath = __DIR__ . '/../' . $dbPath;
        }
        $pdo = new PDO("sqlite:" . realpath($dbPath));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $dbname = $env['DB_DATABASE'] ?? 'financeiro';
    $user = $env['DB_USERNAME'] ?? 'root';
    $password = $env['DB_PASSWORD'] ?? '';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    return new PDO($dsn, $user, $password, $options);
}

// ==============================================================================
// MODO 1: EXECUÇÃO VIA LINHA DE COMANDO (CLI)
// ==============================================================================
if (php_sapi_name() === 'cli') {
    echo "========================================================\n";
    echo "  IMPORTADOR OFX - SISTEMA DE CONTROLE FINANCEIRO\n";
    echo "========================================================\n\n";

    global $argv;
    $targetPath = null;
    $userId = 1;

    foreach ($argv as $i => $arg) {
        if ($i === 0) continue;
        if (str_starts_with($arg, '--user=')) {
            $userId = (int) substr($arg, 7);
        } elseif (!str_starts_with($arg, '--')) {
            $targetPath = $arg;
        }
    }

    if (empty($targetPath)) {
        $defaultDir = __DIR__ . '/../extratos';
        if (is_dir($defaultDir)) {
            $targetPath = $defaultDir;
            echo "Pasta padrao detectada: extratos/\n\n";
        } else {
            echo "Uso:\n";
            echo "  php importar_ofx.php [arquivo_ou_diretorio.ofx] [--user=1]\n\n";
            echo "Exemplo:\n";
            echo "  php importar_ofx.php extratos/extrato_santander.ofx --user=1\n";
            exit(1);
        }
    }

    try {
        $pdo = getPdoConnection();
        $service = new OfxImportService($pdo);

        $filesToProcess = [];
        if (is_dir($targetPath)) {
            $files = glob(rtrim($targetPath, '/\\') . '/*.{ofx,OFX}', GLOB_BRACE);
            $filesToProcess = $files ?: [];
        } elseif (is_file($targetPath)) {
            $filesToProcess[] = $targetPath;
        } else {
            echo "Erro: Arquivo ou diretório não encontrado: {$targetPath}\n";
            exit(1);
        }

        if (empty($filesToProcess)) {
            echo "Nenhum arquivo OFX encontrado para processar.\n";
            exit(0);
        }

        foreach ($filesToProcess as $file) {
            echo "Processando: " . basename($file) . "...\n";
            $result = $service->import($file, $userId);

            if ($result->sucesso) {
                echo "  Status: SUCESSO\n";
                echo "  Conta: {$result->contaNome} (Tipo: {$result->tipoConta})\n";
                echo "  Total Encontrados: {$result->totalEncontrados}\n";
                echo "  Novos Inseridos: {$result->inseridos}\n";
                echo "  Duplicados Ignorados: {$result->duplicadosIgnorados}\n";
                echo "  Total Entradas: R$ " . number_format($result->totalCreditos, 2, ',', '.') . "\n";
                echo "  Total Saídas: R$ " . number_format($result->totalDebitos, 2, ',', '.') . "\n";
                if ($result->saldoFinal !== null) {
                    echo "  Saldo Final no Extrato: R$ " . number_format($result->saldoFinal, 2, ',', '.') . "\n";
                }
            } else {
                echo "  Status: FALHA\n";
                echo "  Erro: {$result->mensagem}\n";
            }
            echo "--------------------------------------------------------\n";
        }

        echo "\nImportação finalizada.\n";
        exit(0);
    } catch (\Throwable $e) {
        echo "Erro fatal: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// ==============================================================================
// MODO 2: EXECUÇÃO VIA WEB (HTTP)
// ==============================================================================

// Se a requisição for POST (Upload de arquivo)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $userId = (int) ($_POST['user_id'] ?? 1);
    $forcedContaId = isset($_POST['conta_id']) && (int)$_POST['conta_id'] > 0 ? (int)$_POST['conta_id'] : null;

    // Verificar se arquivo foi enviado
    $uploadedFile = $_FILES['ofx_file'] ?? $_FILES['file'] ?? $_FILES['extrato'] ?? null;

    if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $uploadedFile['error'] ?? 'Nenhum arquivo recebido';
        http_response_code(400);
        echo json_encode([
            'sucesso'  => false,
            'mensagem' => "Falha no envio do arquivo (Código de erro do upload: {$errorCode}).",
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tmpPath = $uploadedFile['tmp_name'];
    $fileName = $uploadedFile['name'];

    // Validar extensão
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($ext !== 'ofx' && !str_ends_with(strtolower($fileName), '.ofx')) {
        http_response_code(422);
        echo json_encode([
            'sucesso'  => false,
            'mensagem' => 'Extensão de arquivo inválida. Apenas arquivos .ofx são aceitos.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo = getPdoConnection();
        $service = new OfxImportService($pdo);

        $result = $service->import($tmpPath, $userId, $forcedContaId);

        http_response_code($result->sucesso ? 200 : 422);
        echo json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'sucesso'  => false,
            'mensagem' => 'Erro interno durante a importação: ' . $e->getMessage(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Se for requisição GET (Exibe UI simples para teste direto no navegador)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador de Extrato OFX</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .card {
            background-color: #1e293b;
            border-radius: 12px;
            padding: 32px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            border: 1px solid #334155;
        }
        h1 {
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 8px;
            color: #38bdf8;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        p {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #cbd5e1;
        }
        .file-dropzone {
            border: 2px dashed #475569;
            border-radius: 8px;
            padding: 28px 16px;
            text-align: center;
            background-color: #0f172a;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .file-dropzone:hover {
            border-color: #38bdf8;
        }
        input[type="file"] {
            display: block;
            width: 100%;
            color: #94a3b8;
            font-size: 14px;
        }
        button {
            width: 100%;
            padding: 12px 20px;
            background-color: #0284c7;
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            transition: background-color 0.2s;
        }
        button:hover {
            background-color: #0369a1;
        }
        .badges {
            display: flex;
            gap: 8px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .badge {
            background-color: #334155;
            color: #cbd5e1;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 9999px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>📑 Importar Extrato OFX</h1>
        <p>Envie extratos bancários em formato <strong>.OFX</strong> de Conta Corrente ou Cartão de Crédito (Santander, Inter, Bradesco, Itaú, etc.). Os lançamentos serão deduplicados automaticamente via FITID.</p>

        <form action="importar_ofx.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="1">

            <div class="form-group">
                <label for="ofx_file">Selecione o arquivo OFX</label>
                <div class="file-dropzone">
                    <input type="file" id="ofx_file" name="ofx_file" accept=".ofx,.OFX" required>
                </div>
            </div>

            <button type="submit">Processar e Importar Lançamentos</button>
        </form>

        <div class="badges">
            <span class="badge">🛡️ Prevenção Rígida de Duplicidade (FITID)</span>
            <span class="badge">🏦 Detecção Automática Conta/Cartão</span>
            <span class="badge">⚡ Transações PDO Atômicas</span>
        </div>
    </div>
</body>
</html>
