<?php

declare(strict_types=1);

/**
 * Script de Importação de Extratos OFX (somente linha de comando)
 *
 * Uso: php importar_ofx.php [caminho_arquivo_ou_pasta] [--user=1]
 *
 * Fica fora de public/ de propósito: não há autenticação aqui. Para enviar extratos pelo
 * navegador use a importação do módulo Finanças (rota autenticada financas.importar-extrato).
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

// Carregar autoloader do Composer
$autoloadPath = __DIR__ . '/vendor/autoload.php';

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Autoload manual das classes do módulo caso o composer não esteja disponível
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/app/';
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
    $envPath = __DIR__ . '/.env';

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
            $dbPath = __DIR__ . '/' . $dbPath;
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
        $defaultDir = __DIR__ . '/extratos';
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
