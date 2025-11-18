
-- Tabela de Propriedades/Fazendas
CREATE TABLE IF NOT EXISTS propriedades (
    id_propriedade INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    endereco VARCHAR(500),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    cep VARCHAR(10),
    telefone VARCHAR(20),
    area_total DECIMAL(10, 2) COMMENT 'Área total da propriedade em hectares',
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Pastos/Áreas específicas dentro de cada propriedade
CREATE TABLE IF NOT EXISTS pastos (
    id_pasto INT AUTO_INCREMENT PRIMARY KEY,
    id_propriedade INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    area_hectares DECIMAL(10, 2) NOT NULL COMMENT 'Tamanho da área/pasto em hectares',
    tipo VARCHAR(100) COMMENT 'Tipo de pasto/área (ex: Pasto, Lavoura, Área de Reserva)',
    capacidade_lotacao INT COMMENT 'Capacidade de lotação (número de animais)',
    observacoes TEXT,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_propriedade) REFERENCES propriedades(id_propriedade) ON DELETE CASCADE,
    INDEX idx_propriedade (id_propriedade),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Adicionar campos de quantidade mínima se a tabela produtos já existir
ALTER TABLE produtos 
ADD COLUMN IF NOT EXISTS quantidade_minima DECIMAL(10, 2) DEFAULT 0 COMMENT 'Quantidade mínima para alerta de estoque',
ADD COLUMN IF NOT EXISTS unidade_minima VARCHAR(20) DEFAULT 'L' COMMENT 'Unidade da quantidade mínima (L, kg, etc)';

-- Tabela de Estoque de Lotes (já mencionada pelo usuário)
CREATE TABLE IF NOT EXISTS estoque_lotes (
    id_lote INT AUTO_INCREMENT PRIMARY KEY,
    id_produto INT NOT NULL,
    id_propriedade INT COMMENT 'Lote pode estar associado a uma propriedade específica',
    numero_lote VARCHAR(100) NOT NULL,
    data_compra DATE NOT NULL,
    validade DATE,
    quantidade_inicial DECIMAL(10, 2) NOT NULL,
    quantidade_atual DECIMAL(10, 2) NOT NULL,
    unidade VARCHAR(20) NOT NULL COMMENT 'L, kg, frascos, sacos, etc',
    custo_unitario DECIMAL(10, 2),
    local_armazenagem VARCHAR(255),
    observacoes TEXT,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_produto) REFERENCES produtos(id_produto) ON DELETE RESTRICT,
    FOREIGN KEY (id_propriedade) REFERENCES propriedades(id_propriedade) ON DELETE SET NULL,
    INDEX idx_produto (id_produto),
    INDEX idx_propriedade (id_propriedade),
    INDEX idx_validade (validade),
    INDEX idx_ativo (ativo),
    UNIQUE KEY unique_lote_produto (id_produto, numero_lote)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE APLICAÇÕES (Atualização para vincular com propriedade e pasto)
-- ============================================

-- Se a tabela defensivos já existir, adicionar campos de relacionamento
-- Verificar e adicionar campo 'id_propriedade' se não existir
SET @dbname = DATABASE();
SET @tablename = 'defensivos';
SET @columnname = 'id_propriedade';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            (TABLE_SCHEMA = @dbname)
            AND (TABLE_NAME = @tablename)
            AND (COLUMN_NAME = @columnname)
    ) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar e adicionar campo 'id_pasto' se não existir
SET @columnname = 'id_pasto';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            (TABLE_SCHEMA = @dbname)
            AND (TABLE_NAME = @tablename)
            AND (COLUMN_NAME = @columnname)
    ) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Adicionar foreign keys se não existirem (verificar antes)
SET @fk_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'defensivos' 
    AND CONSTRAINT_NAME = 'defensivos_ibfk_propriedade'
    AND COLUMN_NAME = 'id_propriedade'
);

SET @sql_fk1 = IF(@fk_exists = 0,
    'ALTER TABLE defensivos ADD CONSTRAINT defensivos_ibfk_propriedade FOREIGN KEY (id_propriedade) REFERENCES propriedades(id_propriedade) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt_fk1 FROM @sql_fk1;
EXECUTE stmt_fk1;
DEALLOCATE PREPARE stmt_fk1;

SET @fk_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'defensivos' 
    AND CONSTRAINT_NAME = 'defensivos_ibfk_pasto'
    AND COLUMN_NAME = 'id_pasto'
);

SET @sql_fk2 = IF(@fk_exists = 0,
    'ALTER TABLE defensivos ADD CONSTRAINT defensivos_ibfk_pasto FOREIGN KEY (id_pasto) REFERENCES pastos(id_pasto) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt_fk2 FROM @sql_fk2;
EXECUTE stmt_fk2;
DEALLOCATE PREPARE stmt_fk2;

-- Adicionar índices se não existirem
SET @index_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'defensivos' 
    AND INDEX_NAME = 'idx_defensivos_propriedade'
);

SET @sql_idx1 = IF(@index_exists = 0,
    'CREATE INDEX idx_defensivos_propriedade ON defensivos(id_propriedade)',
    'SELECT 1'
);
PREPARE stmt_idx1 FROM @sql_idx1;
EXECUTE stmt_idx1;
DEALLOCATE PREPARE stmt_idx1;

SET @index_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'defensivos' 
    AND INDEX_NAME = 'idx_defensivos_pasto'
);

SET @sql_idx2 = IF(@index_exists = 0,
    'CREATE INDEX idx_defensivos_pasto ON defensivos(id_pasto)',
    'SELECT 1'
);
PREPARE stmt_idx2 FROM @sql_idx2;
EXECUTE stmt_idx2;
DEALLOCATE PREPARE stmt_idx2;

-- ============================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================

-- Verificar e adicionar campo 'ativo' na tabela estoque_lotes se não existir
-- (caso a tabela já exista sem esse campo)
SET @dbname = DATABASE();
SET @tablename = 'estoque_lotes';
SET @columnname = 'ativo';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            (TABLE_SCHEMA = @dbname)
            AND (TABLE_NAME = @tablename)
            AND (COLUMN_NAME = @columnname)
    ) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TINYINT(1) DEFAULT 1')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Índices para consultas frequentes
-- Nota: MySQL não suporta IF NOT EXISTS em CREATE INDEX em versões antigas
-- Usando verificação individual para cada índice

-- Índice composto para estoque (produto + ativo)
SET @index_exists1 = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'estoque_lotes' 
    AND INDEX_NAME = 'idx_estoque_produto_ativo'
);

SET @sql1 = IF(@index_exists1 = 0,
    'CREATE INDEX idx_estoque_produto_ativo ON estoque_lotes(id_produto, ativo)',
    'SELECT 1'
);
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- Índice composto para estoque (validade + ativo)
SET @index_exists2 = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'estoque_lotes' 
    AND INDEX_NAME = 'idx_estoque_validade_ativo'
);

SET @sql2 = IF(@index_exists2 = 0,
    'CREATE INDEX idx_estoque_validade_ativo ON estoque_lotes(validade, ativo)',
    'SELECT 1'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Índice composto para pastos (propriedade + ativo)
SET @index_exists3 = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'pastos' 
    AND INDEX_NAME = 'idx_pastos_propriedade_ativo'
);

SET @sql3 = IF(@index_exists3 = 0,
    'CREATE INDEX idx_pastos_propriedade_ativo ON pastos(id_propriedade, ativo)',
    'SELECT 1'
);
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

