-- Export SQL gerado a partir do projeto Toxic Control
-- Use: importar este arquivo em um servidor MySQL/MariaDB (por ex. via phpMyAdmin ou mysql CLI)

CREATE DATABASE IF NOT EXISTS `toxiccontrol` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `toxiccontrol`;

-- Remover tabelas existentes (ordem para evitar erro de FK)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `defensivos`;
DROP TABLE IF EXISTS `estoque_lotes`;
DROP TABLE IF EXISTS `pastos`;
DROP TABLE IF EXISTS `propriedades`;
DROP TABLE IF EXISTS `produtos`;
DROP TABLE IF EXISTS `usuarios`;
SET FOREIGN_KEY_CHECKS = 1;

-- Tabela de usuários
CREATE TABLE `usuarios` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `data_cadastro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de produtos (referenciada pelo estoque)
CREATE TABLE `produtos` (
  `id_produto` INT NOT NULL AUTO_INCREMENT,
  `nome_comercial` VARCHAR(255) NOT NULL,
  `tipo` VARCHAR(100) DEFAULT NULL,
  `descricao` TEXT DEFAULT NULL,
  `quantidade_minima` DECIMAL(10,2) DEFAULT 0,
  `unidade_minima` VARCHAR(20) DEFAULT 'L',
  `faixa_cor` VARCHAR(50) DEFAULT NULL,
  `data_cadastro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_produto`),
  INDEX `idx_produtos_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de propriedades/fazendas
CREATE TABLE `propriedades` (
    `id_propriedade` INT NOT NULL AUTO_INCREMENT,
    `usuario_id` INT NOT NULL,
    `nome` VARCHAR(255) NOT NULL,
    `descricao` TEXT DEFAULT NULL,
    `endereco` VARCHAR(500) DEFAULT NULL,
    `cidade` VARCHAR(100) DEFAULT NULL,
    `estado` VARCHAR(2) DEFAULT NULL,
    `cep` VARCHAR(10) DEFAULT NULL,
    `telefone` VARCHAR(20) DEFAULT NULL,
    `area_total` DECIMAL(10,2) DEFAULT NULL,
    `latitude` DECIMAL(10,8) DEFAULT NULL,
    `longitude` DECIMAL(11,8) DEFAULT NULL,
    `data_cadastro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `data_atualizacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_propriedade`),
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    INDEX `idx_usuario` (`usuario_id`),
    INDEX `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pastos/áreas dentro de propriedades
CREATE TABLE `pastos` (
    `id_pasto` INT NOT NULL AUTO_INCREMENT,
    `id_propriedade` INT NOT NULL,
    `nome` VARCHAR(255) NOT NULL,
    `descricao` TEXT DEFAULT NULL,
    `area_hectares` DECIMAL(10,2) DEFAULT NULL,
    `tipo` VARCHAR(100) DEFAULT NULL,
    `capacidade_lotacao` INT DEFAULT NULL,
    `observacoes` TEXT DEFAULT NULL,
    `latitude` DECIMAL(10,8) DEFAULT NULL,
    `longitude` DECIMAL(11,8) DEFAULT NULL,
    `data_cadastro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `data_atualizacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_pasto`),
    FOREIGN KEY (`id_propriedade`) REFERENCES `propriedades`(`id_propriedade`) ON DELETE CASCADE,
    INDEX `idx_propriedade` (`id_propriedade`),
    INDEX `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de defensivos/aplicações
CREATE TABLE `defensivos` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `usuario_id` INT NOT NULL,
    `nome_produto` VARCHAR(255) NOT NULL,
    `cultura` VARCHAR(255) DEFAULT NULL,
    `data_aplicacao` DATE NOT NULL,
    `dosagem` VARCHAR(100) DEFAULT NULL,
    `carencia` INT DEFAULT NULL,
    `prazo_validade` DATE DEFAULT NULL,
    `observacoes` TEXT DEFAULT NULL,
    `id_propriedade` INT DEFAULT NULL,
    `id_pasto` INT DEFAULT NULL,
    `data_cadastro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`id_propriedade`) REFERENCES `propriedades`(`id_propriedade`) ON DELETE SET NULL,
    FOREIGN KEY (`id_pasto`) REFERENCES `pastos`(`id_pasto`) ON DELETE SET NULL,
    INDEX `idx_defensivos_usuario` (`usuario_id`),
    INDEX `idx_defensivos_propriedade` (`id_propriedade`),
    INDEX `idx_defensivos_pasto` (`id_pasto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de estoque / lotes
CREATE TABLE `estoque_lotes` (
    `id_lote` INT NOT NULL AUTO_INCREMENT,
    `id_produto` INT NOT NULL,
    `id_propriedade` INT DEFAULT NULL,
    `numero_lote` VARCHAR(100) NOT NULL,
    `data_compra` DATE NOT NULL,
    `validade` DATE DEFAULT NULL,
    `quantidade_inicial` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `quantidade_atual` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `unidade` VARCHAR(20) NOT NULL DEFAULT 'L',
    `custo_unitario` DECIMAL(10,2) DEFAULT NULL,
    `local_armazenagem` VARCHAR(255) DEFAULT NULL,
    `observacoes` TEXT DEFAULT NULL,
    `data_cadastro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `data_atualizacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_lote`),
    FOREIGN KEY (`id_produto`) REFERENCES `produtos`(`id_produto`) ON DELETE RESTRICT,
    FOREIGN KEY (`id_propriedade`) REFERENCES `propriedades`(`id_propriedade`) ON DELETE SET NULL,
    INDEX `idx_produto` (`id_produto`),
    INDEX `idx_propriedade` (`id_propriedade`),
    INDEX `idx_validade` (`validade`),
    INDEX `idx_ativo` (`ativo`),
    UNIQUE KEY `unique_lote_produto` (`id_produto`, `numero_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices adicionais sugeridos
CREATE INDEX IF NOT EXISTS `idx_estoque_produto_ativo` ON `estoque_lotes` (`id_produto`, `ativo`);
CREATE INDEX IF NOT EXISTS `idx_estoque_validade_ativo` ON `estoque_lotes` (`validade`, `ativo`);
CREATE INDEX IF NOT EXISTS `idx_pastos_propriedade_ativo` ON `pastos` (`id_propriedade`, `ativo`);

-- Observações:
-- - As estruturas acima foram consolidadas a partir dos arquivos SQL e referências encontradas nos arquivos PHP do projeto.
-- - Se já possuir dados ou quiser manter mudanças incrementais, execute manualmente os arquivos de alteração em vez de executar os DROP acima.
-- - Ajuste nomes de banco/usuário/senha conforme ambiente antes de importar.

-- Fim do arquivo
