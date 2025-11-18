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
    
    // Buscar id_propriedade antes de deletar
    $stmt = $conn->prepare("SELECT p.id_propriedade FROM pastos p 
                            INNER JOIN propriedades pr ON p.id_propriedade = pr.id_propriedade 
                            WHERE p.id_pasto = ? AND pr.usuario_id = ? AND p.ativo = 1");
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pasto = $result->fetch_assoc();
    $stmt->close();
    
    if ($pasto) {
        // Soft delete - marcar como inativo
        $stmt = $conn->prepare("UPDATE pastos SET ativo = 0 WHERE id_pasto = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: dashboard_pastos.php?id_propriedade=" . $pasto['id_propriedade'] . "&sucesso=Pasto/área excluído com sucesso!");
        } else {
            header("Location: dashboard_pastos.php?id_propriedade=" . $pasto['id_propriedade'] . "&erro=Erro ao excluir pasto/área: " . $stmt->error);
        }
        $stmt->close();
    } else {
        header("Location: propriedades.php?erro=Pasto/área não encontrado ou você não tem permissão para excluí-lo.");
    }
} else {
    header("Location: propriedades.php?erro=ID não fornecido.");
}
exit();
?>

