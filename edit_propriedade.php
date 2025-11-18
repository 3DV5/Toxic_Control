<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$erro = "";
$sucesso = "";
$propriedade = null;

// Buscar o registro a ser editado
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $usuario_id = $_SESSION['usuario_id'];
    
    $stmt = $conn->prepare("SELECT * FROM propriedades WHERE id_propriedade = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $propriedade = $result->fetch_assoc();
    } else {
        $erro = "Propriedade não encontrada ou você não tem permissão para editá-la.";
    }
    $stmt->close();
} else {
    $erro = "ID da propriedade não fornecido.";
}

// Processar atualização
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $area_total = !empty($_POST['area_total']) ? floatval($_POST['area_total']) : null;
    $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    $usuario_id = $_SESSION['usuario_id'];

    // Validações
    if (empty($nome)) {
        $erro = "O nome da propriedade é obrigatório.";
    } elseif ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
        $erro = "A latitude deve estar entre -90 e 90.";
    } elseif ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
        $erro = "A longitude deve estar entre -180 e 180.";
    } else {
        // Verificar se os campos latitude e longitude existem na tabela
        $check_lat = $conn->query("SHOW COLUMNS FROM propriedades LIKE 'latitude'");
        $has_latitude = $check_lat->num_rows > 0;
        $check_lat->free();
        
        if ($has_latitude) {
            // Se os campos existem, usar query completa
            $stmt = $conn->prepare("UPDATE propriedades SET nome = ?, descricao = ?, endereco = ?, cidade = ?, estado = ?, cep = ?, telefone = ?, area_total = ?, latitude = ?, longitude = ? WHERE id_propriedade = ? AND usuario_id = ?");
            $stmt->bind_param("sssssssddddii", $nome, $descricao, $endereco, $cidade, $estado, $cep, $telefone, $area_total, $latitude, $longitude, $id, $usuario_id);
        } else {
            // Se os campos não existem, usar query sem coordenadas
            $stmt = $conn->prepare("UPDATE propriedades SET nome = ?, descricao = ?, endereco = ?, cidade = ?, estado = ?, cep = ?, telefone = ?, area_total = ? WHERE id_propriedade = ? AND usuario_id = ?");
            $stmt->bind_param("sssssssdii", $nome, $descricao, $endereco, $cidade, $estado, $cep, $telefone, $area_total, $id, $usuario_id);
        }

        if ($stmt->execute()) {
            header("Location: propriedades.php?sucesso=Propriedade atualizada com sucesso!");
            exit();
        } else {
            $erro = "Erro ao atualizar propriedade: " . $stmt->error;
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
<title>Editar Propriedade - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/add-defensivo.css">
</head>
<body>
<?php 
$current_page = 'edit_propriedade';
include('includes/header.php'); 
?>
<div class="container">
    <h2>Editar Propriedade</h2>
    <div class="form-container">
        <?php if ($propriedade): ?>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $propriedade['id_propriedade']; ?>">
                
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="nome">Nome da Propriedade <span class="required">*</span></label>
                    <input type="text" id="nome" name="nome" placeholder="Ex: Fazenda São João" value="<?php echo htmlspecialchars($propriedade['nome']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" placeholder="Informações adicionais sobre a propriedade..." rows="3"><?php echo htmlspecialchars($propriedade['descricao'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="area_total">Área Total (hectares)</label>
                        <input type="number" id="area_total" name="area_total" step="0.01" min="0" placeholder="Ex: 150.50" value="<?php echo $propriedade['area_total'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="cep">CEP</label>
                        <input type="text" id="cep" name="cep" placeholder="00000-000" value="<?php echo htmlspecialchars($propriedade['cep'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="endereco">Endereço</label>
                    <input type="text" id="endereco" name="endereco" placeholder="Rua, número, bairro" value="<?php echo htmlspecialchars($propriedade['endereco'] ?? ''); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cidade">Cidade</label>
                        <input type="text" id="cidade" name="cidade" placeholder="Nome da cidade" value="<?php echo htmlspecialchars($propriedade['cidade'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado (UF)</label>
                        <input type="text" id="estado" name="estado" placeholder="Ex: SP" maxlength="2" value="<?php echo htmlspecialchars($propriedade['estado'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($propriedade['telefone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="coordenadas-info">
                        <i class="fas fa-map-marker-alt"></i> Coordenadas do Google Maps
                    </label>
                    <p style="font-size: var(--font-size-sm); color: var(--color-neutral-600); margin-bottom: var(--spacing-3);">
                        Para obter as coordenadas: 1) Abra o Google Maps, 2) Clique com o botão direito no local, 3) Copie a primeira coordenada (latitude) e a segunda (longitude)
                    </p>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude">Latitude</label>
                        <input type="number" id="latitude" name="latitude" step="0.00000001" min="-90" max="90" placeholder="Ex: -7.540673" value="<?php echo $propriedade['latitude'] ?? ''; ?>">
                        <span class="help-text">Exemplo: -7.540673</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="longitude">Longitude</label>
                        <input type="number" id="longitude" name="longitude" step="0.00000001" min="-180" max="180" placeholder="Ex: -50.063126" value="<?php echo $propriedade['longitude'] ?? ''; ?>">
                        <span class="help-text">Exemplo: -50.063126</span>
                    </div>
                </div>
                
                <button type="submit">Atualizar Propriedade</button>
            </form>
        <?php else: ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        
        <div class="back-link-container">
            <a href="propriedades.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar para Propriedades</a>
        </div>
    </div>
</div>
<script src="assets/js/header.js"></script>
</body>
</html>

