<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$id_pasto = isset($_GET['id_pasto']) ? (int)$_GET['id_pasto'] : 0;

// Buscar informações do pasto e propriedade
$stmt = $conn->prepare("SELECT p.*, pr.nome as propriedade_nome, pr.id_propriedade 
                        FROM pastos p 
                        INNER JOIN propriedades pr ON p.id_propriedade = pr.id_propriedade 
                        WHERE p.id_pasto = ? AND pr.usuario_id = ? AND p.ativo = 1");
$stmt->bind_param("ii", $id_pasto, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$pasto = $result->fetch_assoc();
$stmt->close();

if (!$pasto) {
    header("Location: propriedades.php?erro=Pasto/área não encontrado.");
    exit();
}

// Verificar se a coluna id_pasto existe na tabela defensivos
$check_column = $conn->query("SHOW COLUMNS FROM defensivos LIKE 'id_pasto'");
$has_id_pasto_column = $check_column->num_rows > 0;

// Buscar aplicações de defensivos relacionadas a este pasto
if ($has_id_pasto_column) {
    $stmt = $conn->prepare("SELECT * FROM defensivos WHERE id_pasto = ? AND usuario_id = ? ORDER BY data_aplicacao DESC LIMIT 10");
    $stmt->bind_param("ii", $id_pasto, $usuario_id);
} else {
    // Se a coluna não existe, buscar todas as aplicações do usuário
    $stmt = $conn->prepare("SELECT * FROM defensivos WHERE usuario_id = ? ORDER BY data_aplicacao DESC LIMIT 10");
    $stmt->bind_param("i", $usuario_id);
}
$stmt->execute();
$result = $stmt->get_result();
$aplicacoes = [];
while ($row = $result->fetch_assoc()) {
    $aplicacoes[] = $row;
}
$stmt->close();

// Estatísticas do pasto
$total_aplicacoes = count($aplicacoes);
$hoje = date('Y-m-d');
$data_limite = date('Y-m-d', strtotime('+7 days'));

// Buscar aplicações próximas do vencimento
if ($has_id_pasto_column) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM defensivos 
                            WHERE id_pasto = ? AND usuario_id = ? 
                            AND prazo_validade IS NOT NULL 
                            AND prazo_validade <= ? AND prazo_validade >= CURDATE()");
    $stmt->bind_param("iis", $id_pasto, $usuario_id, $data_limite);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM defensivos 
                            WHERE usuario_id = ? 
                            AND prazo_validade IS NOT NULL 
                            AND prazo_validade <= ? AND prazo_validade >= CURDATE()");
    $stmt->bind_param("is", $usuario_id, $data_limite);
}
$stmt->execute();
$result = $stmt->get_result();
$proximos_vencimento = $result->fetch_assoc()['total'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - <?php echo htmlspecialchars($pasto['nome']); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/map.css">
<style>
    .pasto-header {
        background: white;
        border-radius: var(--radius-xl);
        padding: var(--spacing-6);
        box-shadow: var(--shadow-xl);
        margin-bottom: var(--spacing-6);
    }
    
    .pasto-header h2 {
        color: var(--color-primary-700);
        margin-bottom: var(--spacing-4);
    }
    
    .pasto-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-4);
        margin-top: var(--spacing-4);
    }
    
    .info-item {
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        color: var(--color-neutral-600);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-5);
        margin-top: var(--spacing-6);
    }
    
    .stat-card {
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-xl);
        padding: var(--spacing-6);
        display: flex;
        align-items: center;
        gap: var(--spacing-4);
        transition: transform var(--transition-base), box-shadow var(--transition-base);
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-2xl);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-lg);
        background: linear-gradient(135deg, var(--color-primary-500) 0%, var(--color-primary-700) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: var(--font-size-2xl);
        flex-shrink: 0;
    }
    
    .stat-content h3 {
        margin: 0;
        font-size: var(--font-size-3xl);
        font-weight: var(--font-weight-bold);
        color: var(--color-primary-700);
        line-height: 1.2;
    }
    
    .stat-content p {
        margin: var(--spacing-1) 0 0 0;
        font-size: var(--font-size-sm);
        color: var(--color-neutral-600);
        font-weight: var(--font-weight-medium);
    }
    
    .aplicacoes-section {
        margin-top: var(--spacing-8);
    }
    
    .table-container {
        overflow-x: auto;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-base);
        background: white;
        margin-top: var(--spacing-6);
    }
    
    .btn-add-aplicacao {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-2);
        padding: var(--spacing-3) var(--spacing-6);
        background: linear-gradient(135deg, var(--color-primary-500) 0%, var(--color-primary-700) 100%);
        color: white;
        border-radius: var(--radius-sm);
        text-decoration: none;
        font-weight: var(--font-weight-semibold);
        box-shadow: var(--shadow-md);
        transition: all var(--transition-base);
        margin-bottom: var(--spacing-4);
    }
    
    .btn-add-aplicacao:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
        text-decoration: none;
    }
</style>
</head>
<body>
<?php 
$current_page = 'dashboard_pasto';
include('includes/header.php'); 
?>
<div class="container">
    
    <div class="pasto-header">
        <h2><i class="fas fa-seedling"></i> <?php echo htmlspecialchars($pasto['nome']); ?></h2>
        <p style="color: var(--color-neutral-600); margin-bottom: var(--spacing-4);">
            Propriedade: <strong><?php echo htmlspecialchars($pasto['propriedade_nome']); ?></strong>
        </p>
        <div class="pasto-info-grid">
            <div class="info-item">
                <i class="fas fa-ruler-combined"></i>
                <span><strong>Área:</strong> <?php echo number_format($pasto['area_hectares'], 2, ',', '.'); ?> ha</span>
            </div>
            <?php if (!empty($pasto['tipo'])): ?>
                <div class="info-item">
                    <i class="fas fa-tag"></i>
                    <span><strong>Tipo:</strong> <?php echo htmlspecialchars($pasto['tipo']); ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($pasto['capacidade_lotacao'])): ?>
                <div class="info-item">
                    <i class="fas fa-cow"></i>
                    <span><strong>Capacidade:</strong> <?php echo $pasto['capacidade_lotacao']; ?> animais</span>
                </div>
            <?php endif; ?>
            <?php if (!empty($pasto['descricao'])): ?>
                <div class="info-item" style="grid-column: 1 / -1;">
                    <i class="fas fa-info-circle"></i>
                    <span><?php echo htmlspecialchars($pasto['descricao']); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Estatísticas -->
    <div class="stats-section">
        <h2 style="color: white; text-align: center; margin: var(--spacing-8) 0 var(--spacing-6);">Estatísticas do Pasto/Área</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-content">
                    <h3><?php echo $total_aplicacoes; ?></h3>
                    <p>Total de Aplicações</p>
                </div>
            </div>
            <?php if ($proximos_vencimento > 0): ?>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, var(--color-warning) 0%, #f59e0b 100%);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $proximos_vencimento; ?></h3>
                        <p>Próximos Vencimentos</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Aplicações Recentes -->
    <div class="aplicacoes-section">
        <h2 style="color: white; text-align: center; margin: var(--spacing-8) 0 var(--spacing-6);">Aplicações Recentes</h2>
        
        <a href="add_defensivo.php?id_pasto=<?php echo $id_pasto; ?>" class="btn-add-aplicacao">
            <i class="fas fa-plus"></i> Registrar Nova Aplicação
        </a>
        
        <?php if (empty($aplicacoes)): ?>
            <div class="table-container" style="padding: var(--spacing-8); text-align: center;">
                <p style="color: var(--color-neutral-600);">Nenhuma aplicação registrada para este pasto/área ainda.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Cultura</th>
                            <th>Data Aplicação</th>
                            <th>Dosagem</th>
                            <th>Carência</th>
                            <th>Validade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aplicacoes as $aplicacao): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($aplicacao['nome_produto'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($aplicacao['cultura'] ?? '-'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($aplicacao['data_aplicacao'])); ?></td>
                                <td><?php echo htmlspecialchars($aplicacao['dosagem'] ?? '-'); ?></td>
                                <td><?php echo $aplicacao['carencia'] ? $aplicacao['carencia'] . ' dias' : '-'; ?></td>
                                <td>
                                    <?php 
                                    if (!empty($aplicacao['prazo_validade'])) {
                                        echo date('d/m/Y', strtotime($aplicacao['prazo_validade']));
                                        $validade = new DateTime($aplicacao['prazo_validade']);
                                        $hoje_obj = new DateTime();
                                        if ($validade < $hoje_obj) {
                                            echo ' <span class="badge badge-danger">Vencido</span>';
                                        } elseif ($validade <= new DateTime($data_limite)) {
                                            echo ' <span class="badge badge-warning">Próximo</span>';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- Contêiner do mapa -->
<?php if (!empty($pasto['latitude']) && !empty($pasto['longitude'])): ?>
<div class="map-container">
    <iframe 
        src="https://www.google.com/maps?q=<?php echo htmlspecialchars($pasto['latitude']); ?>,<?php echo htmlspecialchars($pasto['longitude']); ?>&hl=pt-BR&z=15&output=embed" 
        width="600" 
        height="450" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</div>
<?php else: ?>
<div class="map-container" style="display: flex; align-items: center; justify-content: center; background: var(--color-neutral-100); color: var(--color-neutral-600); border: 2px solid var(--color-neutral-200);">
    <div style="text-align: center; padding: var(--spacing-8);">
        <i class="fas fa-map-marker-alt" style="font-size: var(--font-size-4xl); margin-bottom: var(--spacing-4); color: var(--color-neutral-400);"></i>
        <p style="font-size: var(--font-size-lg); margin: 0;">Coordenadas não cadastradas</p>
        <p style="font-size: var(--font-size-sm); margin-top: var(--spacing-2);">Edite o pasto/área para adicionar as coordenadas do Google Maps</p>
    </div>
</div>
<?php endif; ?>
<script src="assets/js/header.js"></script>
</body>
</html>

