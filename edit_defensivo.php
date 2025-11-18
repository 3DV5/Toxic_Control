<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$erro = "";
$sucesso = "";
$defensivo = null;
$usuario_id = $_SESSION['usuario_id'];

// Buscar propriedades e pastos para os selects
$propriedades_query = "SELECT id_propriedade, nome FROM propriedades WHERE usuario_id = ? AND ativo = 1 ORDER BY nome ASC";
$stmt = $conn->prepare($propriedades_query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$propriedades_result = $stmt->get_result();
$propriedades = [];
while ($row = $propriedades_result->fetch_assoc()) {
    $propriedades[] = $row;
}
$stmt->close();

// Buscar o registro a ser editado
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM defensivos WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $defensivo = $result->fetch_assoc();
    } else {
        $erro = "Registro não encontrado ou você não tem permissão para editá-lo.";
    }
    $stmt->close();
} else {
    $erro = "ID do registro não fornecido.";
}

// Processar atualização
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $nome_produto = trim($_POST['nome_produto'] ?? '');
    $cultura = trim($_POST['cultura'] ?? '');
    $data_aplicacao = $_POST['data_aplicacao'] ?? '';
    $dosagem = trim($_POST['dosagem'] ?? '');
    $carencia = $_POST['carencia'] ?? null;
    $prazo_validade = $_POST['prazo_validade'] ?? null;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $id_propriedade_post = !empty($_POST['id_propriedade']) ? (int)$_POST['id_propriedade'] : null;
    $id_pasto_post = !empty($_POST['id_pasto']) ? (int)$_POST['id_pasto'] : null;
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
        
        // Preparar statement baseado nos valores NULL (incluindo propriedade e pasto)
        if ($carencia_int === null && $prazo_validade_final === null) {
            $stmt = $conn->prepare("UPDATE defensivos SET nome_produto = ?, cultura = ?, data_aplicacao = ?, dosagem = ?, carencia = NULL, prazo_validade = NULL, observacoes = ?, id_propriedade = ?, id_pasto = ? WHERE id = ? AND usuario_id = ?");
            $stmt->bind_param("sssssiiii", $nome_produto, $cultura, $data_aplicacao, $dosagem, $observacoes, $id_propriedade_post, $id_pasto_post, $id, $usuario_id);
        } elseif ($carencia_int === null && $prazo_validade_final !== null) {
            $stmt = $conn->prepare("UPDATE defensivos SET nome_produto = ?, cultura = ?, data_aplicacao = ?, dosagem = ?, carencia = NULL, prazo_validade = ?, observacoes = ?, id_propriedade = ?, id_pasto = ? WHERE id = ? AND usuario_id = ?");
            $stmt->bind_param("ssssssiiii", $nome_produto, $cultura, $data_aplicacao, $dosagem, $prazo_validade_final, $observacoes, $id_propriedade_post, $id_pasto_post, $id, $usuario_id);
        } elseif ($carencia_int !== null && $prazo_validade_final === null) {
            $stmt = $conn->prepare("UPDATE defensivos SET nome_produto = ?, cultura = ?, data_aplicacao = ?, dosagem = ?, carencia = ?, prazo_validade = NULL, observacoes = ?, id_propriedade = ?, id_pasto = ? WHERE id = ? AND usuario_id = ?");
            $stmt->bind_param("ssssissiiii", $nome_produto, $cultura, $data_aplicacao, $dosagem, $carencia_int, $observacoes, $id_propriedade_post, $id_pasto_post, $id, $usuario_id);
        } else {
            $stmt = $conn->prepare("UPDATE defensivos SET nome_produto = ?, cultura = ?, data_aplicacao = ?, dosagem = ?, carencia = ?, prazo_validade = ?, observacoes = ?, id_propriedade = ?, id_pasto = ? WHERE id = ? AND usuario_id = ?");
            $stmt->bind_param("ssssissiiii", $nome_produto, $cultura, $data_aplicacao, $dosagem, $carencia_int, $prazo_validade_final, $observacoes, $id_propriedade_post, $id_pasto_post, $id, $usuario_id);
        }

        if ($stmt->execute()) {
            $sucesso = "Defensivo atualizado com sucesso!";
            // Buscar dados atualizados
            $stmt2 = $conn->prepare("SELECT * FROM defensivos WHERE id = ? AND usuario_id = ?");
            $stmt2->bind_param("ii", $id, $usuario_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $defensivo = $result2->fetch_assoc();
            $stmt2->close();
        } else {
            $erro = "Erro ao atualizar defensivo. Tente novamente.";
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
<title>Editar Defensivo - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/add-defensivo.css">
</head>
<body>
<?php 
$current_page = 'edit_defensivo';
include('includes/header.php'); 
?>
<div class="container">
    <h2>Editar Defensivo</h2>
    <div class="form-container">
        <?php if (!empty($erro) && !$defensivo): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
            <div class="back-link-container">
                <a href="view_defensivos.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar aos Registros</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($defensivo['id'] ?? ''); ?>">
                
                <?php if (!empty($sucesso)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
                <?php endif; ?>
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="nome_produto">Nome do Produto <span class="required">*</span></label>
                    <input type="text" id="nome_produto" name="nome_produto" placeholder="Ex: Glifosato 480" value="<?php echo htmlspecialchars($defensivo['nome_produto'] ?? ''); ?>" required>
                </div>
                
                <?php if (!empty($propriedades)): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="id_propriedade">Propriedade (opcional)</label>
                        <select id="id_propriedade" name="id_propriedade" onchange="loadPastos(this.value)">
                            <option value="">Selecione uma propriedade</option>
                            <?php foreach ($propriedades as $prop): ?>
                                <option value="<?php echo $prop['id_propriedade']; ?>" <?php echo (isset($defensivo['id_propriedade']) && $defensivo['id_propriedade'] == $prop['id_propriedade']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prop['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_pasto">Pasto/Área (opcional)</label>
                        <select id="id_pasto" name="id_pasto">
                            <option value="">Selecione um pasto/área</option>
                            <?php 
                            if (isset($defensivo['id_propriedade']) && $defensivo['id_propriedade']):
                                $stmt = $conn->prepare("SELECT id_pasto, nome FROM pastos WHERE id_propriedade = ? AND ativo = 1 ORDER BY nome ASC");
                                $stmt->bind_param("i", $defensivo['id_propriedade']);
                                $stmt->execute();
                                $pastos_result = $stmt->get_result();
                                while ($pasto = $pastos_result->fetch_assoc()):
                            ?>
                                <option value="<?php echo $pasto['id_pasto']; ?>" <?php echo (isset($defensivo['id_pasto']) && $defensivo['id_pasto'] == $pasto['id_pasto']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pasto['nome']); ?>
                                </option>
                            <?php 
                                endwhile;
                                $stmt->close();
                            endif;
                            ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cultura">Cultura</label>
                        <input type="text" id="cultura" name="cultura" placeholder="Ex: Milho, Soja" value="<?php echo htmlspecialchars($defensivo['cultura'] ?? ''); ?>">
                        <span class="help-text">Tipo de cultura tratada</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="data_aplicacao">Data de Aplicação <span class="required">*</span></label>
                        <input type="date" id="data_aplicacao" name="data_aplicacao" value="<?php echo htmlspecialchars($defensivo['data_aplicacao'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="dosagem">Dosagem</label>
                        <input type="text" id="dosagem" name="dosagem" placeholder="Ex: 2L/ha" value="<?php echo htmlspecialchars($defensivo['dosagem'] ?? ''); ?>">
                        <span class="help-text">Quantidade aplicada</span>
                    </div>
                    
                <div class="form-group">
                    <label for="carencia">Carência (dias)</label>
                    <input type="number" id="carencia" name="carencia" placeholder="Ex: 30" min="0" value="<?php echo htmlspecialchars($defensivo['carencia'] ?? ''); ?>">
                    <span class="help-text">Período de carência em dias</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="prazo_validade">Prazo de Validade</label>
                <input type="date" id="prazo_validade" name="prazo_validade" value="<?php echo htmlspecialchars($defensivo['prazo_validade'] ?? ''); ?>">
                <span class="help-text">Data de validade do produto (alerta será exibido 7 dias antes)</span>
            </div>
            
            <div class="form-group">
                <label for="observacoes">Observações</label>
                <textarea id="observacoes" name="observacoes" placeholder="Informações adicionais sobre a aplicação..." rows="4"><?php echo htmlspecialchars($defensivo['observacoes'] ?? ''); ?></textarea>
            </div>
                
                <button type="submit">Atualizar Registro</button>
            </form>
            
            <div class="back-link-container">
                <a href="view_defensivos.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar aos Registros</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function loadPastos(propriedadeId) {
    const pastoSelect = document.getElementById('id_pasto');
    pastoSelect.innerHTML = '<option value="">Carregando...</option>';
    
    if (!propriedadeId) {
        pastoSelect.innerHTML = '<option value="">Selecione um pasto/área</option>';
        return;
    }
    
    fetch('get_pastos.php?id_propriedade=' + propriedadeId)
        .then(response => response.json())
        .then(data => {
            pastoSelect.innerHTML = '<option value="">Selecione um pasto/área</option>';
            data.forEach(pasto => {
                const option = document.createElement('option');
                option.value = pasto.id_pasto;
                option.textContent = pasto.nome;
                pastoSelect.appendChild(option);
            });
            // Restaurar valor selecionado se existir
            <?php if (isset($defensivo['id_pasto']) && $defensivo['id_pasto']): ?>
            pastoSelect.value = <?php echo $defensivo['id_pasto']; ?>;
            <?php endif; ?>
        })
        .catch(error => {
            console.error('Erro ao carregar pastos:', error);
            pastoSelect.innerHTML = '<option value="">Erro ao carregar</option>';
        });
}
</script>
<script src="assets/js/header.js"></script>
</body>
</html>

