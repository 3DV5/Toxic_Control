<?php
include('config/db.php');

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Validações
    if (empty($nome)) {
        $erro = "O nome é obrigatório.";
    } elseif (empty($email)) {
        $erro = "O e-mail é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } elseif (empty($senha)) {
        $erro = "A senha é obrigatória.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter no mínimo 6 caracteres.";
    } else {
        // Verificar se o email já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $erro = "Este e-mail já está cadastrado. Por favor, use outro e-mail ou faça login.";
            $stmt->close();
        } else {
            $stmt->close();
            
            // Inserir novo usuário
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nome, $email, $senha_hash);
            
            if ($stmt->execute()) {
                $stmt->close();
                $sucesso = "Conta criada com sucesso! Redirecionando...";
                // Aguardar um pouco antes de redirecionar para mostrar a mensagem
                header("refresh:2;url=login.php");
            } else {
                $erro = "Erro ao cadastrar. Tente novamente mais tarde.";
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/register.css">
</head>
<body>
<a href="index.php" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>
<h2>Criar Conta</h2>
<form method="POST">
    <?php if (!empty($erro)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>
    <?php if (!empty($sucesso)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
    <?php endif; ?>
    <input type="text" name="nome" placeholder="Nome completo" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>" required>
    <input type="email" name="email" placeholder="E-mail" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
    <input type="password" name="senha" placeholder="Senha (mínimo 6 caracteres)" required minlength="6">
    <button type="submit">Cadastrar</button>
    <a href="login.php">Já tem conta? Faça login</a>
</form>
</body>
</html>
