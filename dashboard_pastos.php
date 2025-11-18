<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$id_propriedade = isset($_GET['id_propriedade']) ? (int)$_GET['id_propriedade'] : 0;

// Verificar se a propriedade pertence ao usuário
$stmt = $conn->prepare("SELECT * FROM propriedades WHERE id_propriedade = ? AND usuario_id = ? AND ativo = 1");
$stmt->bind_param("ii", $id_propriedade, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$propriedade = $result->fetch_assoc();
$stmt->close();

if (!$propriedade) {
    header("Location: propriedades.php?erro=Propriedade não encontrada.");
    exit();
}

$erro = "";
$sucesso = "";

// Verificar mensagens
if (isset($_GET['sucesso'])) {
    $sucesso = htmlspecialchars($_GET['sucesso']);
}
if (isset($_GET['erro'])) {
    $erro = htmlspecialchars($_GET['erro']);
}

// Buscar pastos/áreas da propriedade
$stmt = $conn->prepare("SELECT * FROM pastos WHERE id_propriedade = ? AND ativo = 1 ORDER BY nome ASC");
$stmt->bind_param("i", $id_propriedade);
$stmt->execute();
$result = $stmt->get_result();
$pastos = [];
while ($row = $result->fetch_assoc()) {
    $pastos[] = $row;
}
$stmt->close();

// Calcular área total dos pastos
$area_total_pastos = 0;
foreach ($pastos as $pasto) {
    $area_total_pastos += floatval($pasto['area_hectares']);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gerenciar Pastos/Áreas - <?php echo htmlspecialchars($propriedade['nome']); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/dashboard-pastos.css">
<link rel="stylesheet" href="assets/css/map.css">
</head>
<body>
<?php 
$current_page = 'dashboard_pastos';
include('includes/header.php'); 
?>
<div class="container">

    
    <div class="propriedade-header">
        <div class="propriedade-header-top">
            <h2><i class="fas fa-tractor"></i> <?php echo htmlspecialchars($propriedade['nome']); ?></h2>
            <?php if (!empty($propriedade['cidade']) || !empty($propriedade['estado'])): ?>
                <div class="propriedade-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>
                        <?php 
                        $localizacao = [];
                        if (!empty($propriedade['cidade'])) $localizacao[] = htmlspecialchars($propriedade['cidade']);
                        if (!empty($propriedade['estado'])) $localizacao[] = htmlspecialchars($propriedade['estado']);
                        echo implode(' - ', $localizacao);
                        ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($propriedade['descricao'])): ?>
            <p class="propriedade-descricao-header"><?php echo htmlspecialchars($propriedade['descricao']); ?></p>
        <?php endif; ?>
        <div class="propriedade-info-grid">
            <?php if (!empty($propriedade['area_total'])): ?>
                <div class="info-item">
                    <i class="fas fa-ruler-combined"></i>
                    <span><strong>Área Total:</strong> <?php echo number_format($propriedade['area_total'], 2, ',', '.'); ?> ha</span>
                </div>
            <?php endif; ?>
            <div class="info-item">
                <i class="fas fa-map"></i>
                <span><strong>Área Cadastrada:</strong> <?php echo number_format($area_total_pastos, 2, ',', '.'); ?> ha</span>
            </div>
            <div class="info-item">
                <i class="fas fa-list"></i>
                <span><strong>Total de Pastos/Áreas:</strong> <?php echo count($pastos); ?></span>
            </div>
        </div>
    </div>
    
    <?php if (!empty($sucesso)): ?>
        <div class="alert alert-success"><?php echo $sucesso; ?></div>
    <?php endif; ?>
    <?php if (!empty($erro)): ?>
        <div class="alert alert-error"><?php echo $erro; ?></div>
    <?php endif; ?>
    
    <div class="header-actions">
        <a href="add_pasto.php?id_propriedade=<?php echo $id_propriedade; ?>" class="btn-add-pasto">
            <i class="fas fa-plus"></i> Novo Pasto/Área
        </a>
    </div>
    
    <?php if (empty($pastos)): ?>
        <div class="empty-state">
            <i class="fas fa-seedling"></i>
            <h3>Nenhum pasto/área cadastrado</h3>
            <p>Comece cadastrando o primeiro pasto/área desta propriedade.</p>
            <a href="add_pasto.php?id_propriedade=<?php echo $id_propriedade; ?>" class="btn-add-pasto" style="margin-top: var(--spacing-4);">
                <i class="fas fa-plus"></i> Cadastrar Pasto/Área
            </a>
        </div>
    <?php else: ?>
        <div class="pastos-grid">
            <?php foreach ($pastos as $pasto): ?>
                <div class="pasto-card-wrapper">
                    <a href="dashboard_pasto.php?id_pasto=<?php echo $pasto['id_pasto']; ?>" class="pasto-card">
                        <div class="pasto-card-header">
                            <h3><?php echo htmlspecialchars($pasto['nome']); ?></h3>
                        </div>
                        <?php if (!empty($pasto['descricao'])): ?>
                            <p class="pasto-descricao"><?php echo htmlspecialchars($pasto['descricao']); ?></p>
                        <?php endif; ?>
                        <div class="pasto-info">
                            <div class="pasto-info-item">
                                <i class="fas fa-ruler-combined"></i>
                                <span><strong>Área:</strong> <?php echo number_format($pasto['area_hectares'], 2, ',', '.'); ?> ha</span>
                            </div>
                            <?php if (!empty($pasto['tipo'])): ?>
                                <div class="pasto-info-item">
                                    <i class="fas fa-tag"></i>
                                    <span><strong>Tipo:</strong> <?php echo htmlspecialchars($pasto['tipo']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($pasto['capacidade_lotacao'])): ?>
                                <div class="pasto-info-item">
                                    <i class="fas fa-cow"></i>
                                    <span><strong>Capacidade:</strong> <?php echo $pasto['capacidade_lotacao']; ?> animais</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="pasto-card-footer">
                            <i class="fas fa-arrow-right"></i>
                            <span>Ver Detalhes</span>
                        </div>
                    </a>
                    <div class="pasto-card-actions" onclick="event.preventDefault(); event.stopPropagation();">
                        <a href="edit_pasto.php?id=<?php echo $pasto['id_pasto']; ?>" class="btn-action btn-edit" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete_pasto.php?id=<?php echo $pasto['id_pasto']; ?>" class="btn-action btn-delete" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este pasto/área?');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php if (!empty($propriedade['latitude']) && !empty($propriedade['longitude'])): ?>
<div class="propriedade-map">
    <iframe 
        src="https://www.google.com/maps?q=<?php echo htmlspecialchars($propriedade['latitude']); ?>,<?php echo htmlspecialchars($propriedade['longitude']); ?>&hl=pt-BR&z=15&output=embed" 
        width="600" 
        height="450" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</div>
<?php else: ?>
<div class="propriedade-map" style="display: flex; align-items: center; justify-content: center; background: var(--color-neutral-100); color: var(--color-neutral-600);">
    <div style="text-align: center; padding: var(--spacing-8);">
        <i class="fas fa-map-marker-alt" style="font-size: var(--font-size-4xl); margin-bottom: var(--spacing-4); color: var(--color-neutral-400);"></i>
        <p style="font-size: var(--font-size-lg); margin: 0;">Coordenadas não cadastradas</p>
        <p style="font-size: var(--font-size-sm); margin-top: var(--spacing-2);">Edite a propriedade para adicionar as coordenadas do Google Maps</p>
    </div>
</div>
<?php endif; ?>
    <div class="back-link-container">
        <a href="propriedades.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar para Propriedades</a>
    </div>
</div>
<script src="assets/js/header.js"></script>
</body>
</html>

