<?php
session_start();
include('config/db.php');

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Validações
    if (empty($email)) {
        $erro = "Por favor, informe seu e-mail.";
    } elseif (empty($senha)) {
        $erro = "Por favor, informe sua senha.";
    } else {
        // Buscar usuário usando prepared statement
        $stmt = $conn->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            if (password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nome'] = $usuario['nome'];
                $stmt->close();
                header("Location: dashboard.php");
                exit();
            } else {
                $erro = "Senha incorreta.";
            }
        } else {
            $erro = "E-mail não encontrado. Verifique se digitou corretamente ou crie uma conta.";
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
<title>Login - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
<a href="index.php" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>
<h2>Entrar</h2>
<form method="POST">
    <?php if (!empty($erro)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>
    <input type="email" name="email" placeholder="E-mail" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
    <input type="password" name="senha" placeholder="Senha" required>
    <button type="submit">Entrar</button>
    <a href="register.php">Criar nova conta</a>
</form>
</body>
</html>
