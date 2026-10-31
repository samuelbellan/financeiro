<?php

declare(strict_types=1);

namespace App\Services\Ofx;

use App\Services\Ofx\DTO\OfxAccountDTO;
use App\Services\Ofx\DTO\OfxImportResultDTO;
use App\Services\Ofx\DTO\OfxStatementDTO;
use App\Services\Ofx\DTO\OfxTransactionDTO;
use PDO;
use PDOException;
use Throwable;

/**
 * Serviço de importação e persistência de extratos OFX via PDO nativo.
 *
 * Responsável por:
 * - Parsing e normalização do arquivo OFX
 * - Localização ou criação automática da Conta ou Cartão no banco
 * - Inserção atômica com transação PDO
 * - Prevenção rígida de duplicidade de lançamentos via FITID
 * - Contabilização precisa de inseridos, duplicados ignorados e erros
 */
class OfxImportService
{
    protected OfxParserService $parser;

    public function __construct(
        protected PDO $pdo,
        ?OfxParserService $parser = null
    ) {
        $this->parser = $parser ?? new OfxParserService();
        // Garantir que PDO lance exceções em erros de SQL
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Importa um extrato OFX (a partir de caminho de arquivo ou string de conteúdo) para um usuário.
     *
     * @param string $contentOrPath Conteúdo do OFX ou caminho absoluto do arquivo no disco
     * @param int $userId ID do usuário dono dos lançamentos
     * @param int|null $forcedContaId ID de conta pré-selecionada manualmente (opcional)
     * @return OfxImportResultDTO
     */
    public function import(string $contentOrPath, int $userId, ?int $forcedContaId = null): OfxImportResultDTO
    {
        $erros = [];

        try {
            // 1. Fazer o parse do arquivo OFX
            $statement = $this->parser->parse($contentOrPath);
        } catch (Throwable $e) {
            return new OfxImportResultDTO(
                sucesso: false,
                mensagem: "Erro ao processar arquivo OFX: " . $e->getMessage(),
                erros: [$e->getMessage()]
            );
        }

        $accountDTO = $statement->account;
        $transactions = $statement->transactions;
        $totalEncontrados = count($transactions);

        if ($totalEncontrados === 0) {
            return new OfxImportResultDTO(
                sucesso: true,
                mensagem: "O arquivo OFX foi lido com sucesso, porém não contém lançamentos de transações (<STMTTRN>).",
                tipoConta: $accountDTO->tipoOfx,
                totalEncontrados: 0,
                saldoFinal: $accountDTO->saldoFinal,
                periodoInicio: $accountDTO->dataInicio,
                periodoFim: $accountDTO->dataFim
            );
        }

        // 2. Iniciar transação PDO para garantir atomicidade (se já não houver transação aberta)
        $ownsTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $ownsTransaction = true;
        }

        try {
            // 3. Localizar ou criar a Conta / Cartão
            $conta = $this->resolveConta($userId, $accountDTO, $forcedContaId);
            $contaId = (int) $conta['id'];
            $contaNome = (string) $conta['nome'];

            // 4. Preparar statements otimizados
            $driver = strtolower((string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
            $isSqlite = ($driver === 'sqlite');

            // Query de verificação de duplicidade existente
            $stmtCheck = $this->pdo->prepare("
                SELECT id FROM transacoes 
                WHERE conta_id = :conta_id AND fitid = :fitid 
                LIMIT 1
            ");

            // Query de inserção (com IGNORE nativo como salvaguarda contra concorrência)
            $insertSql = $isSqlite
                ? "INSERT OR IGNORE INTO transacoes (
                    user_id, conta_id, descricao, valor, tipo, data, data_hora,
                    fitid, numero_documento, tipo_transacao_ofx, memo, valor_original_ofx,
                    created_at, updated_at
                ) VALUES (
                    :user_id, :conta_id, :descricao, :valor, :tipo, :data, :data_hora,
                    :fitid, :numero_documento, :tipo_transacao_ofx, :memo, :valor_original_ofx,
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )"
                : "INSERT IGNORE INTO transacoes (
                    user_id, conta_id, descricao, valor, tipo, data, data_hora,
                    fitid, numero_documento, tipo_transacao_ofx, memo, valor_original_ofx,
                    created_at, updated_at
                ) VALUES (
                    :user_id, :conta_id, :descricao, :valor, :tipo, :data, :data_hora,
                    :fitid, :numero_documento, :tipo_transacao_ofx, :memo, :valor_original_ofx,
                    NOW(), NOW()
                )";

            $stmtInsert = $this->pdo->prepare($insertSql);

            $inseridos = 0;
            $duplicadosIgnorados = 0;
            $totalCreditos = 0.0;
            $totalDebitos = 0.0;
            $transacoesInseridas = [];

            // 5. Processar cada transação do extrato
            foreach ($transactions as $index => $trx) {
                try {
                    // Verificação explícita de duplicidade pelo FITID na mesma conta
                    $stmtCheck->execute([
                        ':conta_id' => $contaId,
                        ':fitid'    => $trx->fitid,
                    ]);
                    $exists = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                    if ($exists) {
                        $duplicadosIgnorados++;
                        continue;
                    }

                    // Extrair data pura (YYYY-MM-DD) para compatibilidade com o campo `data`
                    $dataPura = substr($trx->data, 0, 10);

                    // Executar inserção
                    $stmtInsert->execute([
                        ':user_id'            => $userId,
                        ':conta_id'           => $contaId,
                        ':descricao'          => $trx->descricao,
                        ':valor'              => $trx->valor,
                        ':tipo'               => $trx->tipo,
                        ':data'               => $dataPura,
                        ':data_hora'          => $trx->data,
                        ':fitid'              => $trx->fitid,
                        ':numero_documento'   => $trx->checknum,
                        ':tipo_transacao_ofx' => $trx->trntype,
                        ':memo'               => $trx->memo,
                        ':valor_original_ofx' => $trx->valorOriginal,
                    ]);

                    if ($stmtInsert->rowCount() > 0) {
                        $inseridos++;
                        $lastId = (int) $this->pdo->lastInsertId();

                        if ($trx->tipo === 'receita') {
                            $totalCreditos += $trx->valor;
                        } else {
                            $totalDebitos += $trx->valor;
                        }

                        $transacoesInseridas[] = array_merge($trx->toArray(), ['id' => $lastId]);
                    } else {
                        // Ignorado pelo índice UNIQUE
                        $duplicadosIgnorados++;
                    }
                } catch (Throwable $trxError) {
                    $erros[] = "Lançamento #{$index} (FITID: {$trx->fitid}): " . $trxError->getMessage();
                }
            }

            // 6. Atualizar saldo da conta caso fornecido no extrato (<LEDGERBAL>)
            if ($accountDTO->saldoFinal !== null) {
                $stmtUpdateSaldo = $this->pdo->prepare("
                    UPDATE contas 
                    SET saldo_atual = :saldo, 
                        data_ultimo_saldo = :data_saldo,
                        updated_at = " . ($isSqlite ? "CURRENT_TIMESTAMP" : "NOW()") . "
                    WHERE id = :id
                ");
                $stmtUpdateSaldo->execute([
                    ':saldo'      => $accountDTO->saldoFinal,
                    ':data_saldo' => $accountDTO->dataSaldo,
                    ':id'         => $contaId,
                ]);
            }

            // Confirmar transação no banco de dados se nós a abrimos
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            $msg = sprintf(
                "Extrato importado com sucesso! %d lançamentos novos inseridos e %d ignorados por duplicidade.",
                $inseridos,
                $duplicadosIgnorados
            );

            return new OfxImportResultDTO(
                sucesso: true,
                mensagem: $msg,
                contaId: $contaId,
                contaNome: $contaNome,
                tipoConta: $accountDTO->tipoOfx,
                totalEncontrados: $totalEncontrados,
                inseridos: $inseridos,
                duplicadosIgnorados: $duplicadosIgnorados,
                totalCreditos: round($totalCreditos, 2),
                totalDebitos: round($totalDebitos, 2),
                saldoFinal: $accountDTO->saldoFinal,
                periodoInicio: $accountDTO->dataInicio,
                periodoFim: $accountDTO->dataFim,
                erros: $erros,
                transacoesInseridas: $transacoesInseridas
            );
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return new OfxImportResultDTO(
                sucesso: false,
                mensagem: "Falha na transação de importação: " . $e->getMessage(),
                erros: array_merge($erros, [$e->getMessage()])
            );
        }
    }

    /**
     * Localiza a conta bancária existente do usuário ou cadastra uma nova de forma autônoma.
     *
     * @param int $userId
     * @param OfxAccountDTO $accountDTO
     * @param int|null $forcedContaId
     * @return array<string, mixed>
     */
    protected function resolveConta(int $userId, OfxAccountDTO $accountDTO, ?int $forcedContaId = null): array
    {
        // 1. Se o usuário forneceu explicitamente um ID de conta
        if ($forcedContaId !== null && $forcedContaId > 0) {
            $stmt = $this->pdo->prepare("SELECT * FROM contas WHERE id = :id AND user_id = :user_id LIMIT 1");
            $stmt->execute([':id' => $forcedContaId, ':user_id' => $userId]);
            $conta = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($conta) {
                return $conta;
            }
        }

        // 2. Busca por Banco + Número de Conta
        if (!empty($accountDTO->bancoId) && !empty($accountDTO->numeroConta)) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM contas 
                WHERE user_id = :user_id AND banco_id = :banco_id AND numero_conta = :numero_conta 
                LIMIT 1
            ");
            $stmt->execute([
                ':user_id'      => $userId,
                ':banco_id'     => $accountDTO->bancoId,
                ':numero_conta' => $accountDTO->numeroConta,
            ]);
            $conta = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($conta) {
                return $conta;
            }
        }

        // 3. Busca por Número da Conta / Cartão
        if (!empty($accountDTO->numeroConta)) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM contas 
                WHERE user_id = :user_id AND numero_conta = :numero_conta 
                LIMIT 1
            ");
            $stmt->execute([
                ':user_id'      => $userId,
                ':numero_conta' => $accountDTO->numeroConta,
            ]);
            $conta = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($conta) {
                return $conta;
            }
        }

        // 3.5. Se não houver identificação explícita de conta/cartão no OFX, reaproveita conta genérica do mesmo tipo
        if (empty($accountDTO->bancoId) && empty($accountDTO->numeroConta)) {
            $tipoContaGen = ($accountDTO->tipoOfx === 'cartao_credito') ? 'cartao_credito' : 'corrente';
            $stmt = $this->pdo->prepare("
                SELECT * FROM contas 
                WHERE user_id = :user_id 
                  AND (banco_id IS NULL OR banco_id = '') 
                  AND (numero_conta IS NULL OR numero_conta = '') 
                  AND tipo = :tipo 
                LIMIT 1
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':tipo'    => $tipoContaGen,
            ]);
            $conta = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($conta) {
                return $conta;
            }
        }

        // 4. Se não existir, cadastrar a conta automaticamente
        $nomeConta = $accountDTO->getIdentificadorAmigavel();
        $tipoConta = ($accountDTO->tipoOfx === 'cartao_credito') ? 'cartao_credito' : 'corrente';

        $driver = strtolower((string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        $now = ($driver === 'sqlite') ? 'CURRENT_TIMESTAMP' : 'NOW()';

        $stmtInsert = $this->pdo->prepare("
            INSERT INTO contas (
                user_id, nome, tipo, banco_id, agencia, numero_conta, moeda,
                saldo_atual, data_ultimo_saldo, ativo, created_at, updated_at
            ) VALUES (
                :user_id, :nome, :tipo, :banco_id, :agencia, :numero_conta, :moeda,
                :saldo_atual, :data_ultimo_saldo, 1, {$now}, {$now}
            )
        ");

        $stmtInsert->execute([
            ':user_id'           => $userId,
            ':nome'              => $nomeConta,
            ':tipo'              => $tipoConta,
            ':banco_id'          => $accountDTO->bancoId,
            ':agencia'           => $accountDTO->agencia,
            ':numero_conta'      => $accountDTO->numeroConta,
            ':moeda'             => $accountDTO->moeda,
            ':saldo_atual'       => $accountDTO->saldoFinal ?? 0.00,
            ':data_ultimo_saldo' => $accountDTO->dataSaldo,
        ]);

        $newId = (int) $this->pdo->lastInsertId();

        $stmtGet = $this->pdo->prepare("SELECT * FROM contas WHERE id = :id LIMIT 1");
        $stmtGet->execute([':id' => $newId]);
        return (array) $stmtGet->fetch(PDO::FETCH_ASSOC);
    }
}
