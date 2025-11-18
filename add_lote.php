<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$erro = "";
$sucesso = "";

// Verificar se a coluna 'ativo' existe na tabela produtos
$check_column_produtos = $conn->query("SHOW COLUMNS FROM produtos LIKE 'ativo'");
$has_ativo_produtos = $check_column_produtos->num_rows > 0;

// Buscar produtos para o select
if ($has_ativo_produtos) {
    $produtos_query = "SELECT id_produto, nome_comercial, tipo FROM produtos WHERE ativo = 1 ORDER BY nome_comercial ASC";
} else {
    $produtos_query = "SELECT id_produto, nome_comercial, tipo FROM produtos ORDER BY nome_comercial ASC";
}
$produtos_result = $conn->query($produtos_query);
$produtos = [];
while ($row = $produtos_result->fetch_assoc()) {
    $produtos[] = $row;
}

// Verificar se a coluna 'ativo' existe na tabela propriedades
$check_column_propriedades = $conn->query("SHOW COLUMNS FROM propriedades LIKE 'ativo'");
$has_ativo_propriedades = $check_column_propriedades->num_rows > 0;

// Buscar propriedades para o select
if ($has_ativo_propriedades) {
    $propriedades_query = "SELECT id_propriedade, nome FROM propriedades WHERE usuario_id = ? AND ativo = 1 ORDER BY nome ASC";
} else {
    $propriedades_query = "SELECT id_propriedade, nome FROM propriedades WHERE usuario_id = ? ORDER BY nome ASC";
}
$stmt = $conn->prepare($propriedades_query);
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$propriedades_result = $stmt->get_result();
$propriedades = [];
while ($row = $propriedades_result->fetch_assoc()) {
    $propriedades[] = $row;
}
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_produto = !empty($_POST['id_produto']) ? (int)$_POST['id_produto'] : null;
    $id_propriedade = !empty($_POST['id_propriedade']) ? (int)$_POST['id_propriedade'] : null;
    $numero_lote = trim($_POST['numero_lote'] ?? '');
    $data_compra = $_POST['data_compra'] ?? '';
    $validade = !empty($_POST['validade']) ? $_POST['validade'] : null;
    $quantidade_inicial = !empty($_POST['quantidade_inicial']) ? floatval($_POST['quantidade_inicial']) : 0;
    $quantidade_atual = !empty($_POST['quantidade_atual']) ? floatval($_POST['quantidade_atual']) : 0;
    $unidade = trim($_POST['unidade'] ?? '');
    $custo_unitario = !empty($_POST['custo_unitario']) ? floatval($_POST['custo_unitario']) : null;
    $local_armazenagem = trim($_POST['local_armazenagem'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    // Validações
    if (empty($id_produto)) {
        $erro = "Selecione um produto.";
    } elseif (empty($numero_lote)) {
        $erro = "O número do lote é obrigatório.";
    } elseif (empty($data_compra)) {
        $erro = "A data de compra é obrigatória.";
    } elseif ($quantidade_inicial <= 0) {
        $erro = "A quantidade inicial deve ser maior que zero.";
    } elseif (empty($unidade)) {
        $erro = "A unidade é obrigatória.";
    } else {
        // Se quantidade_atual não foi informada, usar quantidade_inicial
        if ($quantidade_atual == 0) {
            $quantidade_atual = $quantidade_inicial;
        }
        
        $stmt = $conn->prepare("INSERT INTO estoque_lotes (id_produto, id_propriedade, numero_lote, data_compra, validade, quantidade_inicial, quantidade_atual, unidade, custo_unitario, local_armazenagem, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssddssss", $id_produto, $id_propriedade, $numero_lote, $data_compra, $validade, $quantidade_inicial, $quantidade_atual, $unidade, $custo_unitario, $local_armazenagem, $observacoes);

        if ($stmt->execute()) {
            header("Location: estoque.php?sucesso=Lote adicionado ao estoque com sucesso!");
            exit();
        } else {
            $erro = "Erro ao adicionar lote. Verifique se o número do lote já existe para este produto.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Adicionar Lote - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/add-defensivo.css">
</head>
<body>
<div class="container">
    <h2>Adicionar Lote ao Estoque</h2>
    <div class="form-container">
        <form method="POST">
            <?php if (!empty($sucesso)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
            <?php endif; ?>
            <?php if (!empty($erro)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="id_produto">Produto <span class="required">*</span></label>
                <select id="id_produto" name="id_produto" required>
                    <option value="">Selecione um produto</option>
                    <?php foreach ($produtos as $produto): ?>
                        <option value="<?php echo $produto['id_produto']; ?>" <?php echo (isset($_POST['id_produto']) && $_POST['id_produto'] == $produto['id_produto']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($produto['nome_comercial'] . ' (' . $produto['tipo'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="id_propriedade">Propriedade (opcional)</label>
                <select id="id_propriedade" name="id_propriedade">
                    <option value="">Nenhuma (estoque geral)</option>
                    <?php foreach ($propriedades as $propriedade): ?>
                        <option value="<?php echo $propriedade['id_propriedade']; ?>" <?php echo (isset($_POST['id_propriedade']) && $_POST['id_propriedade'] == $propriedade['id_propriedade']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($propriedade['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="numero_lote">Número do Lote <span class="required">*</span></label>
                    <input type="text" id="numero_lote" name="numero_lote" placeholder="Ex: LOTE-2024-001" value="<?php echo (!empty($sucesso) || !isset($_POST['numero_lote'])) ? '' : htmlspecialchars($_POST['numero_lote']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="data_compra">Data de Compra <span class="required">*</span></label>
                    <input type="date" id="data_compra" name="data_compra" value="<?php echo (!empty($sucesso) || !isset($_POST['data_compra'])) ? '' : htmlspecialchars($_POST['data_compra']); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="validade">Data de Validade</label>
                <input type="date" id="validade" name="validade" value="<?php echo (!empty($sucesso) || !isset($_POST['validade'])) ? '' : htmlspecialchars($_POST['validade']); ?>">
                <span class="help-text">Data de validade do lote (opcional)</span>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="quantidade_inicial">Quantidade Inicial <span class="required">*</span></label>
                    <input type="number" id="quantidade_inicial" name="quantidade_inicial" step="0.01" min="0.01" placeholder="Ex: 100.00" value="<?php echo (!empty($sucesso) || !isset($_POST['quantidade_inicial'])) ? '' : htmlspecialchars($_POST['quantidade_inicial']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="quantidade_atual">Quantidade Atual</label>
                    <input type="number" id="quantidade_atual" name="quantidade_atual" step="0.01" min="0" placeholder="Deixe vazio para usar quantidade inicial" value="<?php echo (!empty($sucesso) || !isset($_POST['quantidade_atual'])) ? '' : htmlspecialchars($_POST['quantidade_atual']); ?>">
                    <span class="help-text">Deixe vazio para usar a quantidade inicial</span>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="unidade">Unidade <span class="required">*</span></label>
                    <input type="text" id="unidade" name="unidade" placeholder="Ex: L, kg, frascos, sacos" value="<?php echo (!empty($sucesso) || !isset($_POST['unidade'])) ? '' : htmlspecialchars($_POST['unidade']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="custo_unitario">Custo Unitário</label>
                    <input type="number" id="custo_unitario" name="custo_unitario" step="0.01" min="0" placeholder="Ex: 25.50" value="<?php echo (!empty($sucesso) || !isset($_POST['custo_unitario'])) ? '' : htmlspecialchars($_POST['custo_unitario']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="local_armazenagem">Local de Armazenagem</label>
                <input type="text" id="local_armazenagem" name="local_armazenagem" placeholder="Ex: Depósito 1, Galpão A" value="<?php echo (!empty($sucesso) || !isset($_POST['local_armazenagem'])) ? '' : htmlspecialchars($_POST['local_armazenagem']); ?>">
            </div>
            
            <div class="form-group">
                <label for="observacoes">Observações</label>
                <textarea id="observacoes" name="observacoes" placeholder="Observações adicionais sobre o lote..." rows="4"><?php echo (!empty($sucesso) || !isset($_POST['observacoes'])) ? '' : htmlspecialchars($_POST['observacoes']); ?></textarea>
            </div>
            
            <button type="submit">Adicionar Lote</button>
        </form>
        
        <div class="back-link-container">
            <a href="estoque.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar para Estoque</a>
        </div>
    </div>
</div>
</body>
</html>

