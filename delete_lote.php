<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Verificar se a coluna 'ativo' existe
    $check_column = $conn->query("SHOW COLUMNS FROM estoque_lotes LIKE 'ativo'");
    $has_ativo_column = $check_column->num_rows > 0;
    
    if ($has_ativo_column) {
        // Soft delete - marcar como inativo
        $stmt = $conn->prepare("UPDATE estoque_lotes SET ativo = 0 WHERE id_lote = ?");
        $stmt->bind_param("i", $id);
    } else {
        // Se a coluna não existe, fazer DELETE real
        $stmt = $conn->prepare("DELETE FROM estoque_lotes WHERE id_lote = ?");
        $stmt->bind_param("i", $id);
    }
    
    if ($stmt->execute()) {
        header("Location: estoque.php?sucesso=Lote excluído com sucesso!");
    } else {
        header("Location: estoque.php?erro=Erro ao excluir lote.");
    }
    $stmt->close();
} else {
    header("Location: estoque.php?erro=ID não fornecido.");
}
exit();
?>

