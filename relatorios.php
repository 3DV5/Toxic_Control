<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Buscar culturas únicas para o filtro
$stmt_culturas = $conn->prepare("SELECT DISTINCT cultura FROM defensivos WHERE usuario_id = ? AND cultura IS NOT NULL AND cultura != '' ORDER BY cultura ASC");
$stmt_culturas->bind_param("i", $usuario_id);
$stmt_culturas->execute();
$result_culturas = $stmt_culturas->get_result();
$culturas = [];
while ($row = $result_culturas->fetch_assoc()) {
    $culturas[] = $row['cultura'];
}
$stmt_culturas->close();

// Buscar produtos únicos para o filtro
$stmt_produtos = $conn->prepare("SELECT DISTINCT nome_produto FROM defensivos WHERE usuario_id = ? ORDER BY nome_produto ASC");
$stmt_produtos->bind_param("i", $usuario_id);
$stmt_produtos->execute();
$result_produtos = $stmt_produtos->get_result();
$produtos = [];
while ($row = $result_produtos->fetch_assoc()) {
    $produtos[] = $row['nome_produto'];
}
$stmt_produtos->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Relatórios - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/relatorios.css">
</head>
<body>
<div class="container">
    <div class="header-section">
        <h2><i class="fas fa-chart-bar"></i> Gerar Relatórios</h2>
        <p class="subtitle">Selecione os filtros desejados para gerar seu relatório personalizado</p>
    </div>

    <div class="report-form-card">
        <form method="GET" action="gerar_relatorio.php" target="_blank">
            <div class="form-section">
                <h3><i class="fas fa-calendar-alt"></i> Período</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_inicio">Data Inicial:</label>
                        <input type="date" id="data_inicio" name="data_inicio" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="data_fim">Data Final:</label>
                        <input type="date" id="data_fim" name="data_fim" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-filter"></i> Filtros Adicionais</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="cultura">Cultura:</label>
                        <select id="cultura" name="cultura" class="form-control">
                            <option value="">Todas as culturas</option>
                            <?php foreach ($culturas as $cultura): ?>
                                <option value="<?php echo htmlspecialchars($cultura); ?>"><?php echo htmlspecialchars($cultura); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="produto">Produto:</label>
                        <select id="produto" name="produto" class="form-control">
                            <option value="">Todos os produtos</option>
                            <?php foreach ($produtos as $produto): ?>
                                <option value="<?php echo htmlspecialchars($produto); ?>"><?php echo htmlspecialchars($produto); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-clipboard-list"></i> Tipo de Relatório</h3>
                <div class="form-group">
                    <label for="tipo_relatorio">Selecione o formato:</label>
                    <select id="tipo_relatorio" name="tipo_relatorio" class="form-control" required>
                        <option value="completo">Relatório Completo</option>
                        <option value="resumido">Relatório Resumido</option>
                        <option value="vencimentos">Relatório de Vencimentos</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-file-alt"></i> Gerar Relatório
                </button>
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>
            </div>
        </form>
    </div>

    <div class="info-card">
        <h3><i class="fas fa-info-circle"></i> Informações</h3>
        <ul>
            <li><strong>Relatório Completo:</strong> Exibe todos os dados dos registros filtrados</li>
            <li><strong>Relatório Resumido:</strong> Exibe apenas estatísticas e resumo dos dados</li>
            <li><strong>Relatório de Vencimentos:</strong> Foca em produtos próximos do vencimento ou vencidos</li>
            <li>Os relatórios são gerados em uma nova aba e podem ser impressos ou salvos como PDF</li>
        </ul>
    </div>
</div>
</body>
</html>

