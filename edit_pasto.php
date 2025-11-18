<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$erro = "";
$pasto = null;

// Buscar o registro a ser editado
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT p.*, pr.usuario_id FROM pastos p 
                            INNER JOIN propriedades pr ON p.id_propriedade = pr.id_propriedade 
                            WHERE p.id_pasto = ? AND pr.usuario_id = ? AND p.ativo = 1");
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $pasto = $result->fetch_assoc();
    } else {
        $erro = "Pasto/área não encontrado ou você não tem permissão para editá-lo.";
    }
    $stmt->close();
} else {
    $erro = "ID do pasto/área não fornecido.";
}

// Processar atualização
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $area_hectares = !empty($_POST['area_hectares']) ? floatval($_POST['area_hectares']) : 0;
    $tipo = trim($_POST['tipo'] ?? '');
    $capacidade_lotacao = !empty($_POST['capacidade_lotacao']) ? (int)$_POST['capacidade_lotacao'] : null;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;

    // Validações
    if (empty($nome)) {
        $erro = "O nome do pasto/área é obrigatório.";
    } elseif ($area_hectares <= 0) {
        $erro = "A área em hectares deve ser maior que zero.";
    } elseif ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
        $erro = "A latitude deve estar entre -90 e 90.";
    } elseif ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
        $erro = "A longitude deve estar entre -180 e 180.";
    } else {
        // Verificar se os campos latitude e longitude existem na tabela
        $check_lat = $conn->query("SHOW COLUMNS FROM pastos LIKE 'latitude'");
        $has_latitude = $check_lat->num_rows > 0;
        $check_lat->free();
        
        if ($has_latitude) {
            // Se os campos existem, usar query completa
            $stmt = $conn->prepare("UPDATE pastos SET nome = ?, descricao = ?, area_hectares = ?, tipo = ?, capacidade_lotacao = ?, observacoes = ?, latitude = ?, longitude = ? WHERE id_pasto = ?");
            $stmt->bind_param("ssdissddi", $nome, $descricao, $area_hectares, $tipo, $capacidade_lotacao, $observacoes, $latitude, $longitude, $id);
        } else {
            // Se os campos não existem, usar query sem coordenadas
            $stmt = $conn->prepare("UPDATE pastos SET nome = ?, descricao = ?, area_hectares = ?, tipo = ?, capacidade_lotacao = ?, observacoes = ? WHERE id_pasto = ?");
            $stmt->bind_param("ssdissi", $nome, $descricao, $area_hectares, $tipo, $capacidade_lotacao, $observacoes, $id);
        }

        if ($stmt->execute()) {
            header("Location: dashboard_pastos.php?id_propriedade=" . $pasto['id_propriedade'] . "&sucesso=Pasto/área atualizado com sucesso!");
            exit();
        } else {
            $erro = "Erro ao atualizar pasto/área: " . $stmt->error;
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
<title>Editar Pasto/Área - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/add-defensivo.css">
</head>
<body>
<?php 
$current_page = 'edit_pasto';
include('includes/header.php'); 
?>
<div class="container">
    <h2>Editar Pasto/Área</h2>
    <div class="form-container">
        <?php if ($pasto): ?>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $pasto['id_pasto']; ?>">
                
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="nome">Nome do Pasto/Área <span class="required">*</span></label>
                    <input type="text" id="nome" name="nome" placeholder="Ex: Pasto 1, Área de Milho" value="<?php echo htmlspecialchars($pasto['nome']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" placeholder="Informações adicionais sobre o pasto/área..." rows="3"><?php echo htmlspecialchars($pasto['descricao'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="area_hectares">Área (hectares) <span class="required">*</span></label>
                        <input type="number" id="area_hectares" name="area_hectares" step="0.01" min="0.01" placeholder="Ex: 25.50" value="<?php echo $pasto['area_hectares']; ?>" required>
                        <span class="help-text">Tamanho da área/pasto em hectares</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <input type="text" id="tipo" name="tipo" placeholder="Ex: Pasto, Lavoura, Reserva" value="<?php echo htmlspecialchars($pasto['tipo'] ?? ''); ?>">
                        <span class="help-text">Tipo de pasto/área</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="capacidade_lotacao">Capacidade de Lotação</label>
                    <input type="number" id="capacidade_lotacao" name="capacidade_lotacao" min="0" placeholder="Ex: 50" value="<?php echo $pasto['capacidade_lotacao'] ?? ''; ?>">
                    <span class="help-text">Número de animais que a área suporta (opcional)</span>
                </div>
                
                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" placeholder="Observações adicionais..." rows="4"><?php echo htmlspecialchars($pasto['observacoes'] ?? ''); ?></textarea>
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
                        <input type="number" id="latitude" name="latitude" step="0.00000001" min="-90" max="90" placeholder="Ex: -7.540673" value="<?php echo $pasto['latitude'] ?? ''; ?>">
                        <span class="help-text">Exemplo: -7.540673</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="longitude">Longitude</label>
                        <input type="number" id="longitude" name="longitude" step="0.00000001" min="-180" max="180" placeholder="Ex: -50.063126" value="<?php echo $pasto['longitude'] ?? ''; ?>">
                        <span class="help-text">Exemplo: -50.063126</span>
                    </div>
                </div>
                
                <button type="submit">Atualizar Pasto/Área</button>
            </form>
        <?php else: ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        
        <div class="back-link-container">
            <a href="dashboard_pastos.php?id_propriedade=<?php echo $pasto['id_propriedade'] ?? ''; ?>" class="back-link"><i class="fas fa-arrow-left"></i> Voltar para Pastos/Áreas</a>
        </div>
    </div>
</div>
<script src="assets/js/header.js"></script>
</body>
</html>

