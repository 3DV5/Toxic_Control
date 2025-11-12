<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_produto = trim($_POST['nome_produto'] ?? '');
    $cultura = trim($_POST['cultura'] ?? '');
    $data_aplicacao = $_POST['data_aplicacao'] ?? '';
    $dosagem = trim($_POST['dosagem'] ?? '');
    $carencia = $_POST['carencia'] ?? null;
    $prazo_validade = $_POST['prazo_validade'] ?? null;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];

    // Validações
    if (empty($nome_produto)) {
        $erro = "O nome do produto é obrigatório.";
    } elseif (empty($data_aplicacao)) {
        $erro = "A data de aplicação é obrigatória.";
    } elseif ($carencia !== null && $carencia !== '' && (!is_numeric($carencia) || $carencia < 0)) {
        $erro = "A carência deve ser um número positivo.";
    } else {
        // Converter carência vazia para NULL
        if ($carencia === '' || $carencia === null) {
            $carencia_int = null;
        } else {
            $carencia_int = (int)$carencia;
        }
        
        // Converter prazo_validade vazia para NULL
        if ($prazo_validade === '' || $prazo_validade === null) {
            $prazo_validade_final = null;
        } else {
            $prazo_validade_final = $prazo_validade;
        }
        
        // Preparar statement baseado nos valores NULL
        if ($carencia_int === null && $prazo_validade_final === null) {
            $stmt = $conn->prepare("INSERT INTO defensivos (usuario_id, nome_produto, cultura, data_aplicacao, dosagem, carencia, prazo_validade, observacoes) VALUES (?, ?, ?, ?, ?, NULL, NULL, ?)");
            $stmt->bind_param("isssss", $usuario_id, $nome_produto, $cultura, $data_aplicacao, $dosagem, $observacoes);
        } elseif ($carencia_int === null && $prazo_validade_final !== null) {
            $stmt = $conn->prepare("INSERT INTO defensivos (usuario_id, nome_produto, cultura, data_aplicacao, dosagem, carencia, prazo_validade, observacoes) VALUES (?, ?, ?, ?, ?, NULL, ?, ?)");
            $stmt->bind_param("issssss", $usuario_id, $nome_produto, $cultura, $data_aplicacao, $dosagem, $prazo_validade_final, $observacoes);
        } elseif ($carencia_int !== null && $prazo_validade_final === null) {
            $stmt = $conn->prepare("INSERT INTO defensivos (usuario_id, nome_produto, cultura, data_aplicacao, dosagem, carencia, prazo_validade, observacoes) VALUES (?, ?, ?, ?, ?, ?, NULL, ?)");
            $stmt->bind_param("issssiss", $usuario_id, $nome_produto, $cultura, $data_aplicacao, $dosagem, $carencia_int, $observacoes);
        } else {
            $stmt = $conn->prepare("INSERT INTO defensivos (usuario_id, nome_produto, cultura, data_aplicacao, dosagem, carencia, prazo_validade, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssiss", $usuario_id, $nome_produto, $cultura, $data_aplicacao, $dosagem, $carencia_int, $prazo_validade_final, $observacoes);
        }

        if ($stmt->execute()) {
            $sucesso = "Defensivo registrado com sucesso!";
            // Limpar dados do POST para não manter valores no formulário
            unset($_POST);
        } else {
            $erro = "Erro ao registrar defensivo. Tente novamente.";
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
<title>Registrar Defensivo - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/add-defensivo.css">
</head>
<body>
<div class="container">
    <h2>Registrar Uso de Defensivo</h2>
    <div class="form-container">
        <form method="POST">
            <?php if (!empty($sucesso)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
            <?php endif; ?>
            <?php if (!empty($erro)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="nome_produto">Nome do Produto <span class="required">*</span></label>
                <input type="text" id="nome_produto" name="nome_produto" placeholder="Ex: Glifosato 480" value="<?php echo (!empty($sucesso) || !isset($_POST['nome_produto'])) ? '' : htmlspecialchars($_POST['nome_produto']); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="cultura">Cultura</label>
                    <input type="text" id="cultura" name="cultura" placeholder="Ex: Milho, Soja" value="<?php echo (!empty($sucesso) || !isset($_POST['cultura'])) ? '' : htmlspecialchars($_POST['cultura']); ?>">
                    <span class="help-text">Tipo de cultura tratada</span>
                </div>
                
                <div class="form-group">
                    <label for="data_aplicacao">Data de Aplicação <span class="required">*</span></label>
                    <input type="date" id="data_aplicacao" name="data_aplicacao" value="<?php echo (!empty($sucesso) || !isset($_POST['data_aplicacao'])) ? '' : htmlspecialchars($_POST['data_aplicacao']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="dosagem">Dosagem</label>
                    <input type="text" id="dosagem" name="dosagem" placeholder="Ex: 2L/ha" value="<?php echo (!empty($sucesso) || !isset($_POST['dosagem'])) ? '' : htmlspecialchars($_POST['dosagem']); ?>">
                    <span class="help-text">Quantidade aplicada</span>
                </div>
                
                <div class="form-group">
                    <label for="carencia">Carência (dias)</label>
                    <input type="number" id="carencia" name="carencia" placeholder="Ex: 30" min="0" value="<?php echo (!empty($sucesso) || !isset($_POST['carencia'])) ? '' : htmlspecialchars($_POST['carencia']); ?>">
                    <span class="help-text">Período de carência em dias</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="prazo_validade">Prazo de Validade</label>
                <input type="date" id="prazo_validade" name="prazo_validade" value="<?php echo (!empty($sucesso) || !isset($_POST['prazo_validade'])) ? '' : htmlspecialchars($_POST['prazo_validade']); ?>">
                <span class="help-text">Data de validade do produto (alerta será exibido 7 dias antes)</span>
            </div>
            
            <div class="form-group">
                <label for="observacoes">Observações</label>
                <textarea id="observacoes" name="observacoes" placeholder="Informações adicionais sobre a aplicação..." rows="4"><?php echo (!empty($sucesso) || !isset($_POST['observacoes'])) ? '' : htmlspecialchars($_POST['observacoes']); ?></textarea>
            </div>
            
            <button type="submit">Salvar Registro</button>
        </form>
        
        <div class="back-link-container">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
