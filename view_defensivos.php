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

// Verificar mensagens de sucesso/erro da URL
if (isset($_GET['sucesso'])) {
    $sucesso = htmlspecialchars($_GET['sucesso']);
}
if (isset($_GET['erro'])) {
    $erro = htmlspecialchars($_GET['erro']);
}

// Verificar se as colunas id_propriedade e id_pasto existem na tabela defensivos
$check_columns = $conn->query("SHOW COLUMNS FROM defensivos LIKE 'id_propriedade'");
$has_id_propriedade = $check_columns->num_rows > 0;
$check_columns = $conn->query("SHOW COLUMNS FROM defensivos LIKE 'id_pasto'");
$has_id_pasto = $check_columns->num_rows > 0;

// Buscar registros com informações de propriedade e pasto usando JOIN (se as colunas existirem)
if ($has_id_propriedade && $has_id_pasto) {
    $stmt = $conn->prepare("SELECT d.*, 
                            pr.nome as propriedade_nome, 
                            p.nome as pasto_nome 
                            FROM defensivos d 
                            LEFT JOIN propriedades pr ON d.id_propriedade = pr.id_propriedade 
                            LEFT JOIN pastos p ON d.id_pasto = p.id_pasto 
                            WHERE d.usuario_id = ? 
                            ORDER BY d.data_aplicacao DESC");
} else {
    // Se as colunas não existirem, fazer query simples sem JOIN
    $stmt = $conn->prepare("SELECT *, NULL as propriedade_nome, NULL as pasto_nome 
                            FROM defensivos 
                            WHERE usuario_id = ? 
                            ORDER BY data_aplicacao DESC");
}
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    $erro = "Erro ao carregar registros.";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registros - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/view-defensivos.css">
</head>
<body>
<?php 
$current_page = 'view_defensivos';
include('includes/header.php'); 
?>
<div class="container">
    <h2>Meus Registros de Defensivos</h2>
    <?php if (!empty($sucesso)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
    <?php endif; ?>
    <?php if (!empty($erro)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>
    <div class="table-container">
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
                <th>Observações</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $data_formatada = date('d/m/Y', strtotime($row['data_aplicacao']));
                    $id = $row['id'];
                    $prazo_validade_formatado = '';
                    $prazo_validade_class = '';
                    
                    if (!empty($row['prazo_validade'])) {
                        $prazo_validade_formatado = date('d/m/Y', strtotime($row['prazo_validade']));
                        $hoje = new DateTime();
                        $hoje->setTime(0, 0, 0);
                        $validade = new DateTime($row['prazo_validade']);
                        $validade->setTime(0, 0, 0);
                        
                        $diff = $hoje->diff($validade);
                        $dias_restantes = (int)$diff->format('%r%a');
                        
                        if ($validade < $hoje) {
                            $dias_vencido = abs($dias_restantes);
                            $prazo_validade_class = 'vencido';
                            $prazo_validade_formatado .= ' <span class="badge badge-danger">Vencido há ' . $dias_vencido . ' dia(s)</span>';
                        } elseif ($dias_restantes <= 7 && $dias_restantes >= 0) {
                            $prazo_validade_class = 'proximo-vencimento';
                            $prazo_validade_formatado .= ' <span class="badge badge-warning">' . $dias_restantes . ' dia(s)</span>';
                        }
                    } else {
                        $prazo_validade_formatado = '-';
                    }
                    
                    $propriedade_nome = !empty($row['propriedade_nome']) ? htmlspecialchars($row['propriedade_nome']) : '-';
                    $pasto_nome = !empty($row['pasto_nome']) ? htmlspecialchars($row['pasto_nome']) : '-';
                    
                    echo "<tr>
                            <td>" . htmlspecialchars($row['nome_produto'] ?? '') . "</td>
                            <td>" . $propriedade_nome . "</td>
                            <td>" . $pasto_nome . "</td>
                            <td>" . htmlspecialchars($row['cultura'] ?? '-') . "</td>
                            <td>" . htmlspecialchars($data_formatada) . "</td>
                            <td>" . htmlspecialchars($row['dosagem'] ?? '-') . "</td>
                            <td>" . htmlspecialchars($row['carencia'] ?? '-') . "</td>
                            <td class='" . $prazo_validade_class . "'>" . $prazo_validade_formatado . "</td>
                            <td>" . htmlspecialchars($row['observacoes'] ?? '-') . "</td>
                            <td class='actions-cell'>
                                <a href='edit_defensivo.php?id=" . htmlspecialchars($id) . "' class='btn-action btn-edit' title='Editar'><i class='fas fa-edit'></i></a>
                                <a href='delete_defensivo.php?id=" . htmlspecialchars($id) . "' class='btn-action btn-delete' title='Excluir' onclick='return confirm(\"Tem certeza que deseja excluir este registro?\");'><i class='fas fa-trash'></i></a>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='10' class='empty-state'>Nenhum registro encontrado.</td></tr>";
            }
            if (isset($stmt)) {
                $stmt->close();
            }
            ?>
        </tbody>
    </table>
    </div>
    <div class="back-link-container">
        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>
        <a href="relatorios.php" class="back-link"><i class="fas fa-chart-bar"></i> Gerar Relatórios</a>
    </div>
</div>
<script src="assets/js/header.js"></script>
</body>
</html>
