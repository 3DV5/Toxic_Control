<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $usuario_id = $_SESSION['usuario_id'];
    
    // Verificar se a propriedade existe e pertence ao usuário
    $check_stmt = $conn->prepare("SELECT id_propriedade FROM propriedades WHERE id_propriedade = ? AND usuario_id = ? AND ativo = 1");
    $check_stmt->bind_param("ii", $id, $usuario_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        
        // Soft delete - marcar como inativo
        $stmt = $conn->prepare("UPDATE propriedades SET ativo = 0 WHERE id_propriedade = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $id, $usuario_id);
        
        if ($stmt->execute()) {
            header("Location: propriedades.php?sucesso=Propriedade excluída com sucesso!");
        } else {
            header("Location: propriedades.php?erro=Erro ao excluir propriedade: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $check_stmt->close();
        header("Location: propriedades.php?erro=Propriedade não encontrada ou você não tem permissão para excluí-la.");
    }
} else {
    header("Location: propriedades.php?erro=ID não fornecido.");
}
exit();
?>

