<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$nome_usuario = $_SESSION['nome'];

// Receber filtros
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$cultura = $_GET['cultura'] ?? '';
$produto = $_GET['produto'] ?? '';
$id_propriedade = !empty($_GET['id_propriedade']) ? (int)$_GET['id_propriedade'] : null;
$id_pasto = !empty($_GET['id_pasto']) ? (int)$_GET['id_pasto'] : null;
$tipo_relatorio = $_GET['tipo_relatorio'] ?? 'completo';

// Verificar se as colunas id_propriedade e id_pasto existem na tabela defensivos
$check_columns = $conn->query("SHOW COLUMNS FROM defensivos LIKE 'id_propriedade'");
$has_id_propriedade = $check_columns->num_rows > 0;
$check_columns = $conn->query("SHOW COLUMNS FROM defensivos LIKE 'id_pasto'");
$has_id_pasto = $check_columns->num_rows > 0;

// Construir query com filtros (usando alias d. para a tabela defensivos se as colunas existirem)
$where_conditions = [];
$params = [];
$types = "";

if ($has_id_propriedade && $has_id_pasto) {
    $where_conditions[] = "d.usuario_id = ?";
    $params[] = $usuario_id;
    $types = "i";
    
    if (!empty($data_inicio)) {
        $where_conditions[] = "d.data_aplicacao >= ?";
        $params[] = $data_inicio;
        $types .= "s";
    }
    
    if (!empty($data_fim)) {
        $where_conditions[] = "d.data_aplicacao <= ?";
        $params[] = $data_fim;
        $types .= "s";
    }
    
    if (!empty($cultura)) {
        $where_conditions[] = "d.cultura = ?";
        $params[] = $cultura;
        $types .= "s";
    }
    
    if (!empty($produto)) {
        $where_conditions[] = "d.nome_produto = ?";
        $params[] = $produto;
        $types .= "s";
    }
    
    if ($id_propriedade !== null) {
        $where_conditions[] = "d.id_propriedade = ?";
        $params[] = $id_propriedade;
        $types .= "i";
    }
    
    if ($id_pasto !== null) {
        $where_conditions[] = "d.id_pasto = ?";
        $params[] = $id_pasto;
        $types .= "i";
    }
} else {
    // Se as colunas não existirem, não usar alias e não incluir filtros de propriedade/pasto
    $where_conditions[] = "usuario_id = ?";
    $params[] = $usuario_id;
    $types = "i";
    
    if (!empty($data_inicio)) {
        $where_conditions[] = "data_aplicacao >= ?";
        $params[] = $data_inicio;
        $types .= "s";
    }
    
    if (!empty($data_fim)) {
        $where_conditions[] = "data_aplicacao <= ?";
        $params[] = $data_fim;
        $types .= "s";
    }
    
    if (!empty($cultura)) {
        $where_conditions[] = "cultura = ?";
        $params[] = $cultura;
        $types .= "s";
    }
    
    if (!empty($produto)) {
        $where_conditions[] = "nome_produto = ?";
        $params[] = $produto;
        $types .= "s";
    }
    
    // Não incluir filtros de propriedade e pasto se as colunas não existirem
}

$where_clause = implode(" AND ", $where_conditions);

// Query base com JOIN para propriedade e pasto (se as colunas existirem)
if ($has_id_propriedade && $has_id_pasto) {
    $query = "SELECT d.*, 
              pr.nome as propriedade_nome, 
              p.nome as pasto_nome 
              FROM defensivos d 
              LEFT JOIN propriedades pr ON d.id_propriedade = pr.id_propriedade 
              LEFT JOIN pastos p ON d.id_pasto = p.id_pasto 
              WHERE $where_clause 
              ORDER BY d.data_aplicacao DESC";
} else {
    // Se as colunas não existirem, fazer query simples sem JOIN
    $query = "SELECT *, NULL as propriedade_nome, NULL as pasto_nome 
              FROM defensivos 
              WHERE $where_clause 
              ORDER BY data_aplicacao DESC";
}

$stmt = $conn->prepare($query);
if (count($params) > 1) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param($types, $usuario_id);
}
$stmt->execute();
$result = $stmt->get_result();

// Calcular estatísticas
$total_registros = $result->num_rows;
$result->data_seek(0); // Resetar ponteiro

$culturas_count = [];
$produtos_count = [];
$total_aplicacoes = 0;
$vencidos = 0;
$proximos_vencimento = 0;
$hoje = new DateTime();
$hoje->setTime(0, 0, 0);

while ($row = $result->fetch_assoc()) {
    $total_aplicacoes++;
    
    // Contar culturas
    if (!empty($row['cultura'])) {
        $culturas_count[$row['cultura']] = ($culturas_count[$row['cultura']] ?? 0) + 1;
    }
    
    // Contar produtos
    $produtos_count[$row['nome_produto']] = ($produtos_count[$row['nome_produto']] ?? 0) + 1;
    
    // Verificar vencimentos
    if (!empty($row['prazo_validade'])) {
        $validade = new DateTime($row['prazo_validade']);
        $validade->setTime(0, 0, 0);
        $diff = $hoje->diff($validade);
        $dias_restantes = (int)$diff->format('%r%a');
        
        if ($validade < $hoje) {
            $vencidos++;
        } elseif ($dias_restantes <= 7 && $dias_restantes >= 0) {
            $proximos_vencimento++;
        }
    }
}

$result->data_seek(0); // Resetar ponteiro novamente
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Relatório - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #333;
        line-height: 1.6;
        padding: 20px;
        background: #f5f5f5;
    }
    
    .report-container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        padding: 40px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    
    .report-header {
        border-bottom: 3px solid #1a5f3f;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }
    
    .report-header h1 {
        color: #1a5f3f;
        font-size: 28px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .report-header h1 i,
    .report-section h2 i {
        display: inline-block;
    }
    
    .report-section h2 {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .report-header .meta {
        color: #666;
        font-size: 14px;
    }
    
    .report-section {
        margin-bottom: 30px;
    }
    
    .report-section h2 {
        color: #1a5f3f;
        font-size: 20px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 3px;
        border-left: 4px solid #1a5f3f;
    }
    
    .stat-card h3 {
        font-size: 14px;
        color: #666;
        margin-bottom: 10px;
        text-transform: uppercase;
    }
    
    .stat-card .value {
        font-size: 32px;
        font-weight: bold;
        color: #1a5f3f;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    
    table th {
        background: #1a5f3f;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: 600;
    }
    
    table td {
        padding: 10px 12px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    table tr:hover {
        background: #f8f9fa;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-danger {
        background: #ef4444;
        color: white;
    }
    
    .badge-warning {
        background: #f59e0b;
        color: white;
    }
    
    .badge-success {
        background: #10b981;
        color: white;
    }
    
    .print-actions {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #e0e0e0;
        text-align: center;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: #1a5f3f;
        color: white;
        text-decoration: none;
        border-radius: 3px;
        margin: 0 10px;
        font-weight: 600;
        cursor: pointer;
        border: none;
    }
    
    .btn i {
        display: inline-block;
    }
    
    .btn:hover {
        background: #0f4c3a;
    }
    
    .no-data {
        text-align: center;
        padding: 40px;
        color: #999;
        font-size: 18px;
    }
    
    @media print {
        body {
            background: white;
            padding: 0;
        }
        
        .print-actions {
            display: none;
        }
        
        .report-container {
            box-shadow: none;
            padding: 0;
        }
    }
</style>
</head>
<body>
<div class="report-container">
    <div class="report-header">
        <h1><i class="fas fa-chart-bar"></i> Relatório de Defensivos Agrícolas</h1>
        <div class="meta">
            <p><strong>Usuário:</strong> <?php echo htmlspecialchars($nome_usuario); ?></p>
            <p><strong>Data de Geração:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
            <?php if (!empty($data_inicio) || !empty($data_fim)): ?>
                <p><strong>Período:</strong> 
                    <?php echo !empty($data_inicio) ? date('d/m/Y', strtotime($data_inicio)) : 'Início'; ?> 
                    até 
                    <?php echo !empty($data_fim) ? date('d/m/Y', strtotime($data_fim)) : 'Fim'; ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($cultura)): ?>
                <p><strong>Cultura:</strong> <?php echo htmlspecialchars($cultura); ?></p>
            <?php endif; ?>
            <?php if (!empty($produto)): ?>
                <p><strong>Produto:</strong> <?php echo htmlspecialchars($produto); ?></p>
            <?php endif; ?>
            <?php if ($id_propriedade !== null): 
                $stmt_prop = $conn->prepare("SELECT nome FROM propriedades WHERE id_propriedade = ?");
                $stmt_prop->bind_param("i", $id_propriedade);
                $stmt_prop->execute();
                $result_prop = $stmt_prop->get_result();
                if ($row_prop = $result_prop->fetch_assoc()):
            ?>
                <p><strong>Propriedade:</strong> <?php echo htmlspecialchars($row_prop['nome']); ?></p>
            <?php 
                endif;
                $stmt_prop->close();
            endif; ?>
            <?php if ($id_pasto !== null): 
                $stmt_pasto = $conn->prepare("SELECT nome FROM pastos WHERE id_pasto = ?");
                $stmt_pasto->bind_param("i", $id_pasto);
                $stmt_pasto->execute();
                $result_pasto = $stmt_pasto->get_result();
                if ($row_pasto = $result_pasto->fetch_assoc()):
            ?>
                <p><strong>Pasto/Área:</strong> <?php echo htmlspecialchars($row_pasto['nome']); ?></p>
            <?php 
                endif;
                $stmt_pasto->close();
            endif; ?>
        </div>
    </div>

    <?php if ($total_registros > 0): ?>
        <!-- Estatísticas -->
        <div class="report-section">
            <h2><i class="fas fa-chart-line"></i> Estatísticas Gerais</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total de Registros</h3>
                    <div class="value"><?php echo $total_registros; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Produtos Diferentes</h3>
                    <div class="value"><?php echo count($produtos_count); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Culturas Diferentes</h3>
                    <div class="value"><?php echo count($culturas_count); ?></div>
                </div>
                <?php if ($vencidos > 0 || $proximos_vencimento > 0): ?>
                <div class="stat-card">
                    <h3>Vencidos</h3>
                    <div class="value" style="color: #ef4444;"><?php echo $vencidos; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Próximos Vencimentos</h3>
                    <div class="value" style="color: #f59e0b;"><?php echo $proximos_vencimento; ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($tipo_relatorio == 'resumido'): ?>
            <!-- Relatório Resumido -->
            <div class="report-section">
                <h2><i class="fas fa-seedling"></i> Produtos Mais Utilizados</h2>
                <?php
                arsort($produtos_count);
                $top_produtos = array_slice($produtos_count, 0, 10, true);
                ?>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Quantidade de Aplicações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_produtos as $prod => $count): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prod); ?></td>
                                <td><?php echo $count; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section">
                <h2><i class="fas fa-leaf"></i> Culturas Mais Tratadas</h2>
                <?php
                arsort($culturas_count);
                $top_culturas = array_slice($culturas_count, 0, 10, true);
                ?>
                <table>
                    <thead>
                        <tr>
                            <th>Cultura</th>
                            <th>Quantidade de Aplicações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_culturas as $cult => $count): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cult); ?></td>
                                <td><?php echo $count; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($tipo_relatorio == 'vencimentos'): ?>
            <!-- Relatório de Vencimentos -->
            <div class="report-section">
                <h2><i class="fas fa-exclamation-triangle"></i> Produtos com Vencimento Próximo ou Vencidos</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Propriedade</th>
                            <th>Pasto/Área</th>
                            <th>Cultura</th>
                            <th>Data de Aplicação</th>
                            <th>Prazo de Validade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result->data_seek(0);
                        $tem_vencimentos = false;
                        while ($row = $result->fetch_assoc()):
                            if (!empty($row['prazo_validade'])):
                                $validade = new DateTime($row['prazo_validade']);
                                $validade->setTime(0, 0, 0);
                                $diff = $hoje->diff($validade);
                                $dias_restantes = (int)$diff->format('%r%a');
                                
                                if ($validade < $hoje || ($dias_restantes <= 7 && $dias_restantes >= 0)):
                                    $tem_vencimentos = true;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nome_produto']); ?></td>
                                <td><?php echo htmlspecialchars($row['propriedade_nome'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['pasto_nome'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['cultura'] ?? '-'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['data_aplicacao'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['prazo_validade'])); ?></td>
                                <td>
                                    <?php if ($validade < $hoje): ?>
                                        <span class="badge badge-danger">Vencido há <?php echo abs($dias_restantes); ?> dia(s)</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Faltam <?php echo $dias_restantes; ?> dia(s)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
                                endif;
                            endif;
                        endwhile;
                        if (!$tem_vencimentos):
                        ?>
                            <tr>
                                <td colspan="7" class="no-data">Nenhum produto com vencimento próximo ou vencido encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- Relatório Completo -->
            <div class="report-section">
                <h2><i class="fas fa-clipboard-list"></i> Registros Detalhados</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Propriedade</th>
                            <th>Pasto/Área</th>
                            <th>Cultura</th>
                            <th>Data Aplicação</th>
                            <th>Dosagem</th>
                            <th>Carência (dias)</th>
                            <th>Prazo Validade</th>
                            <th>Status</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result->data_seek(0);
                        while ($row = $result->fetch_assoc()):
                            $prazo_validade_formatado = '-';
                            $status_badge = '';
                            
                            if (!empty($row['prazo_validade'])) {
                                $prazo_validade_formatado = date('d/m/Y', strtotime($row['prazo_validade']));
                                $validade = new DateTime($row['prazo_validade']);
                                $validade->setTime(0, 0, 0);
                                $diff = $hoje->diff($validade);
                                $dias_restantes = (int)$diff->format('%r%a');
                                
                                if ($validade < $hoje) {
                                    $status_badge = '<span class="badge badge-danger">Vencido</span>';
                                } elseif ($dias_restantes <= 7 && $dias_restantes >= 0) {
                                    $status_badge = '<span class="badge badge-warning">Próximo</span>';
                                } else {
                                    $status_badge = '<span class="badge badge-success">Válido</span>';
                                }
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nome_produto']); ?></td>
                                <td><?php echo htmlspecialchars($row['propriedade_nome'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['pasto_nome'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['cultura'] ?? '-'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['data_aplicacao'])); ?></td>
                                <td><?php echo htmlspecialchars($row['dosagem'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['carencia'] ?? '-'); ?></td>
                                <td><?php echo $prazo_validade_formatado; ?></td>
                                <td><?php echo $status_badge; ?></td>
                                <td><?php echo htmlspecialchars($row['observacoes'] ?? '-'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-data">
            <p>Nenhum registro encontrado com os filtros selecionados.</p>
        </div>
    <?php endif; ?>

    <div class="print-actions">
        <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Imprimir</button>
        <button onclick="window.close()" class="btn"><i class="fas fa-times"></i> Fechar</button>
    </div>
</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>

