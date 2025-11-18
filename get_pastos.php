<?php
session_start();
include('config/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([]);
    exit();
}

$id_propriedade = isset($_GET['id_propriedade']) ? (int)$_GET['id_propriedade'] : 0;
$usuario_id = $_SESSION['usuario_id'];

// Verificar se a propriedade pertence ao usuário
$stmt = $conn->prepare("SELECT id_propriedade FROM propriedades WHERE id_propriedade = ? AND usuario_id = ? AND ativo = 1");
$stmt->bind_param("ii", $id_propriedade, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Buscar pastos da propriedade
    $stmt->close();
    $stmt = $conn->prepare("SELECT id_pasto, nome FROM pastos WHERE id_propriedade = ? AND ativo = 1 ORDER BY nome ASC");
    $stmt->bind_param("i", $id_propriedade);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pastos = [];
    while ($row = $result->fetch_assoc()) {
        $pastos[] = $row;
    }
    
    echo json_encode($pastos);
} else {
    echo json_encode([]);
}

$stmt->close();
?>

