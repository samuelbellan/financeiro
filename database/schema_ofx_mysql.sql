-- ==============================================================================
-- Script SQL Completo de Criação / Migração para Módulo de Extratos OFX
-- Compatibilidade: MySQL 8.x, MySQL 5.7+ e MariaDB 10.x+
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. Tabela: contas
-- Armazena contas bancárias (corrente/poupança) e cartões de crédito importados
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contas` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `nome` VARCHAR(150) NOT NULL COMMENT 'Nome de exibição amigável da conta ou cartão',
    `tipo` ENUM('corrente', 'poupanca', 'cartao_credito', 'investimento', 'outros') NOT NULL DEFAULT 'corrente',
    `banco_id` VARCHAR(20) NULL COMMENT 'Código de compensação COMPE (ex.: 033, 077, 237)',
    `agencia` VARCHAR(20) NULL COMMENT 'Número da agência bancária sem dígito',
    `numero_conta` VARCHAR(50) NULL COMMENT 'Número da conta com dígito ou últimos dígitos do cartão',
    `moeda` VARCHAR(10) NOT NULL DEFAULT 'BRL',
    `saldo_atual` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 COMMENT 'Saldo consolidado conforme último extrato',
    `data_ultimo_saldo` DATETIME NULL COMMENT 'Data do balanço informada na tag <LEDGERBAL>',
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_contas_user_id` (`user_id`),
    INDEX `idx_contas_banco_conta` (`banco_id`, `numero_conta`),
    CONSTRAINT `fk_contas_user_id` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Tabela: transacoes (Garante criação se não existir)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transacoes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `conta_id` BIGINT UNSIGNED NULL COMMENT 'Vínculo com a conta bancária de origem',
    `descricao` VARCHAR(255) NOT NULL,
    `valor` DECIMAL(15, 2) NOT NULL COMMENT 'Valor absoluto positivo da transação',
    `tipo` ENUM('receita', 'despesa') NOT NULL,
    `categoria` VARCHAR(100) NULL,
    `subcategoria` VARCHAR(100) NULL,
    `categoria_id` BIGINT UNSIGNED NULL,
    `subcategoria_id` BIGINT UNSIGNED NULL,
    `data` DATE NOT NULL,
    `data_hora` DATETIME NULL COMMENT 'Data e hora precisa extraída da tag <DTPOSTED>',
    `fitid` VARCHAR(255) NULL COMMENT 'Identificador único emitido pelo banco na tag <FITID>',
    `numero_documento` VARCHAR(64) NULL COMMENT 'Número do documento / cheque / PIX da tag <CHECKNUM>',
    `tipo_transacao_ofx` VARCHAR(32) NULL COMMENT 'Tipo OFX da operação (<TRNTYPE>, ex: DEBIT, CREDIT, PAYMENT)',
    `memo` TEXT NULL COMMENT 'Texto original integral recebido na tag <MEMO>',
    `valor_original_ofx` DECIMAL(15, 2) NULL COMMENT 'Valor com sinal original (+ para crédito, - para débito)',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_transacoes_user_id` (`user_id`),
    INDEX `idx_transacoes_conta_id` (`conta_id`),
    INDEX `idx_transacoes_data` (`data`),
    INDEX `idx_transacoes_fitid` (`fitid`),
    -- Prevenção Rígida de Duplicidade: Garante que uma transação com o mesmo FITID nunca seja duplicada na mesma conta
    UNIQUE KEY `uq_conta_fitid` (`conta_id`, `fitid`),
    CONSTRAINT `fk_transacoes_user_id` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_transacoes_conta_id` 
        FOREIGN KEY (`conta_id`) 
        REFERENCES `contas` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Procedure de Migração Segura (Caso a tabela `transacoes` já exista no banco)
-- Adiciona apenas as colunas e índices que ainda não estiverem presentes
-- ------------------------------------------------------------------------------
DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_upgrade_transacoes_for_ofx`$$
CREATE PROCEDURE `sp_upgrade_transacoes_for_ofx`()
BEGIN
    -- Adicionar coluna conta_id se não existir
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transacoes' AND COLUMN_NAME = 'conta_id'
    ) THEN
        ALTER TABLE `transacoes` ADD COLUMN `conta_id` BIGINT UNSIGNED NULL AFTER `user_id`;
        ALTER TABLE `transacoes` ADD CONSTRAINT `fk_transacoes_conta_id` 
            FOREIGN KEY (`conta_id`) REFERENCES `contas` (`id`) ON DELETE CASCADE;
    END IF;

    -- Adicionar coluna fitid se não existir
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transacoes' AND COLUMN_NAME = 'fitid'
    ) THEN
        ALTER TABLE `transacoes` ADD COLUMN `fitid` VARCHAR(255) NULL AFTER `data`;
    END IF;

    -- Adicionar coluna data_hora se não existir
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transacoes' AND COLUMN_NAME = 'data_hora'
    ) THEN
        ALTER TABLE `transacoes` ADD COLUMN `data_hora` DATETIME NULL AFTER `data`;
    END IF;

    -- Adicionar coluna numero_documento se não existir
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transacoes' AND COLUMN_NAME = 'numero_documento'
    ) THEN
        ALTER TABLE `transacoes` ADD COLUMN `numero_documento` VARCHAR(64) NULL AFTER `fitid`;
    END IF;

    -- Adicionar coluna tipo_transacao_ofx se não existir
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transacoes' AND COLUMN_NAME = 'tipo_transacao_ofx'
    ) THEN
        ALTER TABLE `transacoes` ADD COLUMN `tipo_transacao_ofx` VARCHAR(32) NULL AFTER `numero_documento`;
    END IF;

    -- Adicionar coluna memo se não existir
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transacoes' AND COLUMN_NAME = 'memo'
    ) THEN
        ALTER TABLE `transacoes` ADD COLUMN `memo` TEXT NULL AFTER `tipo_transacao_ofx`;
    END IF;

    -- Adicionar coluna valor_original_ofx se não existir
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transacoes' AND COLUMN_NAME = 'valor_original_ofx'
    ) THEN
        ALTER TABLE `transacoes` ADD COLUMN `valor_original_ofx` DECIMAL(15, 2) NULL AFTER `memo`;
    END IF;

    -- Criar índice UNIQUE uq_conta_fitid se não existir
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transacoes' AND INDEX_NAME = 'uq_conta_fitid'
    ) THEN
        ALTER TABLE `transacoes` ADD UNIQUE KEY `uq_conta_fitid` (`conta_id`, `fitid`);
    END IF;
END$$

DELIMITER ;

CALL `sp_upgrade_transacoes_for_ofx`();
DROP PROCEDURE IF EXISTS `sp_upgrade_transacoes_for_ofx`;

SET FOREIGN_KEY_CHECKS = 1;
