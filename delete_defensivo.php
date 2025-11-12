<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$erro = "";
$sucesso = "";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $usuario_id = $_SESSION['usuario_id'];
    
    // Verificar se o registro pertence ao usuário
    $stmt = $conn->prepare("SELECT id FROM defensivos WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Deletar o registro
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM defensivos WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $id, $usuario_id);
        
        if ($stmt->execute()) {
            $sucesso = "Registro excluído com sucesso!";
            header("Location: view_defensivos.php?sucesso=" . urlencode($sucesso));
            exit();
        } else {
            $erro = "Erro ao excluir registro. Tente novamente.";
        }
        $stmt->close();
    } else {
        $erro = "Registro não encontrado ou você não tem permissão para excluí-lo.";
    }
} else {
    $erro = "ID do registro não fornecido.";
}

// Se houver erro, redirecionar com mensagem
if (!empty($erro)) {
    header("Location: view_defensivos.php?erro=" . urlencode($erro));
    exit();
}
?>

