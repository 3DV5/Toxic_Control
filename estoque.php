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

// Verificar se a coluna 'ativo' existe na tabela estoque_lotes
$check_column = $conn->query("SHOW COLUMNS FROM estoque_lotes LIKE 'ativo'");
$has_ativo_column = $check_column->num_rows > 0;

// Buscar estoque com informações do produto
if ($has_ativo_column) {
    $query = "SELECT el.*, p.nome_comercial, p.tipo, p.quantidade_minima, p.unidade_minima, p.faixa_cor
              FROM estoque_lotes el
              INNER JOIN produtos p ON el.id_produto = p.id_produto
              WHERE el.ativo = 1
              ORDER BY el.validade ASC, p.nome_comercial ASC";
} else {
    // Se a coluna ativo não existe, buscar todos os registros
    $query = "SELECT el.*, p.nome_comercial, p.tipo, p.quantidade_minima, p.unidade_minima, p.faixa_cor
              FROM estoque_lotes el
              INNER JOIN produtos p ON el.id_produto = p.id_produto
              ORDER BY el.validade ASC, p.nome_comercial ASC";
}
$result = $conn->query($query);
$lotes = [];
$alertas_quantidade = [];
$alertas_validade = [];

$hoje = date('Y-m-d');
$data_limite = date('Y-m-d', strtotime('+30 days'));

while ($row = $result->fetch_assoc()) {
    $lotes[] = $row;
    
    // Verificar alerta de quantidade mínima
    if (!empty($row['quantidade_minima']) && $row['quantidade_atual'] <= $row['quantidade_minima']) {
        $alertas_quantidade[] = $row;
    }
    
    // Verificar alerta de validade
    if (!empty($row['validade'])) {
        $validade = new DateTime($row['validade']);
        $hoje_obj = new DateTime($hoje);
        if ($validade <= $hoje_obj || $validade <= new DateTime($data_limite)) {
            $alertas_validade[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gerenciar Estoque - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/view-defensivos.css">
<style>
    .alertas-section {
        margin-bottom: var(--spacing-6);
    }
    
    .alerta-card {
        background: white;
        border-radius: var(--radius-xl);
        padding: var(--spacing-6);
        box-shadow: var(--shadow-xl);
        margin-bottom: var(--spacing-4);
        border-left: 4px solid;
    }
    
    .alerta-card.quantidade {
        border-left-color: var(--color-warning);
    }
    
    .alerta-card.validade {
        border-left-color: var(--color-error);
    }
    
    .alerta-card h3 {
        margin: 0 0 var(--spacing-4) 0;
        color: var(--color-neutral-900);
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
    }
    
    .alerta-item {
        padding: var(--spacing-3);
        background: var(--color-neutral-50);
        border-radius: var(--radius-base);
        margin-bottom: var(--spacing-2);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .btn-add-lote {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-2);
        padding: var(--spacing-4) var(--spacing-6);
        background: linear-gradient(135deg, var(--color-primary-500) 0%, var(--color-primary-700) 100%);
        color: white;
        border-radius: var(--radius-sm);
        text-decoration: none;
        font-weight: var(--font-weight-semibold);
        box-shadow: var(--shadow-md);
        transition: all var(--transition-base);
        margin-bottom: var(--spacing-6);
    }
    
    .btn-add-lote:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
        text-decoration: none;
    }
    
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-6);
        flex-wrap: wrap;
        gap: var(--spacing-4);
    }
    
    .badge {
        display: inline-block;
        padding: var(--spacing-1) var(--spacing-3);
        border-radius: var(--radius-sm);
        font-size: var(--font-size-xs);
        font-weight: var(--font-weight-semibold);
    }
    
    .badge-danger {
        background-color: var(--color-error);
        color: white;
    }
    
    .badge-warning {
        background-color: var(--color-warning);
        color: white;
    }
    
    .badge-success {
        background-color: var(--color-success);
        color: white;
    }
    
    .quantidade-baixa {
        color: var(--color-error);
        font-weight: var(--font-weight-bold);
    }
</style>
</head>
<body>
<?php 
$current_page = 'estoque';
include('includes/header.php'); 
?>
<div class="container">
    <h2>Gerenciar Estoque</h2>
    
    <?php if (!empty($sucesso)): ?>
        <div class="alert alert-success"><?php echo $sucesso; ?></div>
    <?php endif; ?>
    <?php if (!empty($erro)): ?>
        <div class="alert alert-error"><?php echo $erro; ?></div>
    <?php endif; ?>
    
    <!-- Alertas -->
    <?php if (!empty($alertas_quantidade) || !empty($alertas_validade)): ?>
        <div class="alertas-section">
            <?php if (!empty($alertas_quantidade)): ?>
                <div class="alerta-card quantidade">
                    <h3><i class="fas fa-exclamation-triangle"></i> Alertas de Quantidade Mínima</h3>
                    <?php foreach ($alertas_quantidade as $alerta): ?>
                        <div class="alerta-item">
                            <div>
                                <strong><?php echo htmlspecialchars($alerta['nome_comercial']); ?></strong> - 
                                Lote: <?php echo htmlspecialchars($alerta['numero_lote']); ?><br>
                                <small>Quantidade atual: <span class="quantidade-baixa"><?php echo number_format($alerta['quantidade_atual'], 2, ',', '.'); ?> <?php echo htmlspecialchars($alerta['unidade']); ?></span> | 
                                Mínima: <?php echo number_format($alerta['quantidade_minima'], 2, ',', '.'); ?> <?php echo htmlspecialchars($alerta['unidade_minima']); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($alertas_validade)): ?>
                <div class="alerta-card validade">
                    <h3><i class="fas fa-calendar-times"></i> Alertas de Validade</h3>
                    <?php foreach ($alertas_validade as $alerta): ?>
                        <div class="alerta-item">
                            <div>
                                <strong><?php echo htmlspecialchars($alerta['nome_comercial']); ?></strong> - 
                                Lote: <?php echo htmlspecialchars($alerta['numero_lote']); ?><br>
                                <small>Validade: <?php echo date('d/m/Y', strtotime($alerta['validade'])); ?></small>
                            </div>
                            <?php 
                            $validade = new DateTime($alerta['validade']);
                            $hoje_obj = new DateTime($hoje);
                            if ($validade < $hoje_obj):
                            ?>
                                <span class="badge badge-danger">Vencido</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Próximo</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="header-actions">
        <a href="add_lote.php" class="btn-add-lote">
            <i class="fas fa-plus"></i> Adicionar Lote ao Estoque
        </a>
    </div>
    
    <?php if (empty($lotes)): ?>
        <div class="table-container" style="padding: var(--spacing-12); text-align: center;">
            <i class="fas fa-box-open" style="font-size: 4rem; color: var(--color-neutral-400); margin-bottom: var(--spacing-4);"></i>
            <h3>Nenhum lote em estoque</h3>
            <p style="color: var(--color-neutral-600);">Comece adicionando lotes ao estoque.</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Tipo</th>
                        <th>Nº Lote</th>
                        <th>Quantidade</th>
                        <th>Validade</th>
                        <th>Local</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lotes as $lote): ?>
                        <?php
                        $status_class = '';
                        $status_text = '';
                        $validade_status = '';
                        
                        if (!empty($lote['validade'])) {
                            $validade = new DateTime($lote['validade']);
                            $hoje_obj = new DateTime($hoje);
                            if ($validade < $hoje_obj) {
                                $status_class = 'vencido';
                                $status_text = 'Vencido';
                                $validade_status = '<span class="badge badge-danger">Vencido</span>';
                            } elseif ($validade <= new DateTime($data_limite)) {
                                $status_class = 'proximo';
                                $status_text = 'Próximo';
                                $validade_status = '<span class="badge badge-warning">Próximo</span>';
                            } else {
                                $validade_status = '<span class="badge badge-success">OK</span>';
                            }
                        }
                        
                        $quantidade_status = '';
                        if (!empty($lote['quantidade_minima']) && $lote['quantidade_atual'] <= $lote['quantidade_minima']) {
                            $quantidade_status = '<span class="badge badge-warning">Baixo</span>';
                        }
                        ?>
                        <tr class="<?php echo $status_class; ?>">
                            <td><strong><?php echo htmlspecialchars($lote['nome_comercial']); ?></strong></td>
                            <td><?php echo htmlspecialchars($lote['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($lote['numero_lote']); ?></td>
                            <td>
                                <?php echo number_format($lote['quantidade_atual'], 2, ',', '.'); ?> <?php echo htmlspecialchars($lote['unidade']); ?>
                                <?php if ($lote['quantidade_atual'] <= $lote['quantidade_minima']): ?>
                                    <br><?php echo $quantidade_status; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $lote['validade'] ? date('d/m/Y', strtotime($lote['validade'])) : '-'; ?>
                                <?php if ($lote['validade']): ?>
                                    <br><?php echo $validade_status; ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($lote['local_armazenagem'] ?? '-'); ?></td>
                            <td>
                                <?php echo $validade_status; ?>
                                <?php if ($quantidade_status): ?>
                                    <?php echo $quantidade_status; ?>
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <a href="edit_lote.php?id=<?php echo $lote['id_lote']; ?>" class="btn-action btn-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete_lote.php?id=<?php echo $lote['id_lote']; ?>" class="btn-action btn-delete" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este lote?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <div class="back-link-container">
        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>
    </div>
</div>
<script src="assets/js/header.js"></script>
</body>
</html>

