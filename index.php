<?php
// Página inicial: se já estiver logado, redireciona ao dashboard
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TOXIC CONTROL</title>
<link rel="stylesheet" href="./assets/css/design-system.css">
<link rel="stylesheet" href="./assets/css/index.css">
</head>
<body>
<header>
<nav class="top-nav">
    <a href="register.php" class="nav-link">Criar Conta</a>
    <a href="login.php" class="nav-link">Entrar</a>
</nav>
</header>

<section>
    <div class="hero-content">
        <h1 class="main-title">Toxic Control</h1>
        <p class="description">Controle simples e acessível do uso de defensivos agrícolas para pequenas e médias fazendas. Registre aplicações, monitore períodos de carência e gere relatórios para garantir a segurança na produção.</p>
    </div>
</section>

<footer class="copyright">2025 Copyright - Desenvolvido por Eduardo Vasconcelos e Gustavo Casanova</footer>
</body>
</html>