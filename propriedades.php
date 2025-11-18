<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$erro = "";
$sucesso = "";

// Verificar mensagens
if (isset($_GET['sucesso'])) {
    $sucesso = htmlspecialchars($_GET['sucesso']);
}
if (isset($_GET['erro'])) {
    $erro = htmlspecialchars($_GET['erro']);
}

// Buscar propriedades do usuário
$stmt = $conn->prepare("SELECT * FROM propriedades WHERE usuario_id = ? AND ativo = 1 ORDER BY nome ASC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$propriedades = [];
while ($row = $result->fetch_assoc()) {
    $propriedades[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gerenciar Propriedades - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/propriedades.css">
</head>
<body>
<?php 
$current_page = 'propriedades';
include('includes/header.php'); 
?>
<div class="container">
    <h2>Gerenciar Propriedades</h2>
    
    <?php if (!empty($sucesso)): ?>
        <div class="alert alert-success"><?php echo $sucesso; ?></div>
    <?php endif; ?>
    <?php if (!empty($erro)): ?>
        <div class="alert alert-error"><?php echo $erro; ?></div>
    <?php endif; ?>
    
    <div class="header-actions">
        <a href="add_propriedade.php" class="btn-add-propriedade">
            <i class="fas fa-plus"></i> Nova Propriedade
        </a>
    </div>
    
    <?php if (empty($propriedades)): ?>
        <div class="empty-state">
            <i class="fas fa-tractor"></i>
            <h3>Nenhuma propriedade cadastrada</h3>
            <p>Comece cadastrando sua primeira propriedade/fazenda.</p>
            <a href="add_propriedade.php" class="btn-add-propriedade" style="margin-top: var(--spacing-4);">
                <i class="fas fa-plus"></i> Cadastrar Propriedade
            </a>
        </div>
    <?php else: ?>
        <div class="propriedades-grid">
            <?php foreach ($propriedades as $propriedade): ?>
                <div class="propriedade-card-wrapper">
                    <a href="dashboard_pastos.php?id_propriedade=<?php echo $propriedade['id_propriedade']; ?>" class="propriedade-card">
                        <div class="propriedade-card-header">
                            <h3><?php echo htmlspecialchars($propriedade['nome']); ?></h3>
                        </div>
                    <?php if (!empty($propriedade['descricao'])): ?>
                        <p class="propriedade-descricao"><?php echo htmlspecialchars($propriedade['descricao']); ?></p>
                    <?php endif; ?>
                    <div class="propriedade-info">
                        <?php if (!empty($propriedade['area_total'])): ?>
                            <div class="propriedade-info-item">
                                <i class="fas fa-ruler-combined"></i>
                                <span><?php echo number_format($propriedade['area_total'], 2, ',', '.'); ?> ha</span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($propriedade['cidade'])): ?>
                            <div class="propriedade-info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($propriedade['cidade']); ?><?php echo !empty($propriedade['estado']) ? ' - ' . htmlspecialchars($propriedade['estado']) : ''; ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="propriedade-info-item">
                            <i class="fas fa-calendar"></i>
                            <span>Cadastrada em <?php echo date('d/m/Y', strtotime($propriedade['data_cadastro'])); ?></span>
                        </div>
                    </div>
                        <div class="propriedade-card-footer">
                            <i class="fas fa-arrow-right"></i>
                            <span>Gerenciar Pastos/Áreas</span>
                        </div>
                    </a>
                    <div class="propriedade-card-actions" onclick="event.preventDefault(); event.stopPropagation();">
                        <a href="edit_propriedade.php?id=<?php echo $propriedade['id_propriedade']; ?>" class="btn-action btn-edit" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete_propriedade.php?id=<?php echo $propriedade['id_propriedade']; ?>" class="btn-action btn-delete" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta propriedade?');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="back-link-container">
        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard Principal</a>
    </div>
</div>
<script src="assets/js/header.js"></script>
</body>
</html>

