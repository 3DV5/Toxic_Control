<?php
// Header fixo reutilizável
// Variável $current_page deve ser definida antes de incluir este arquivo
// Exemplo: $current_page = 'dashboard';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Definir página atual se não foi definida
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
}

// Definir opções do menu baseado na página atual
$menu_items = [];

// Menu padrão para todas as páginas autenticadas
$menu_items[] = ['url' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'fas fa-chart-line'];

// Adicionar opções específicas baseadas na página atual
switch ($current_page) {
    case 'dashboard':
        $menu_items[] = ['url' => 'propriedades.php', 'label' => 'Propriedades', 'icon' => 'fas fa-tractor'];
        $menu_items[] = ['url' => 'estoque.php', 'label' => 'Estoque', 'icon' => 'fas fa-boxes'];
        $menu_items[] = ['url' => 'add_defensivo.php', 'label' => 'Registrar Defensivo', 'icon' => 'fas fa-plus-circle'];
        $menu_items[] = ['url' => 'view_defensivos.php', 'label' => 'Ver Registros', 'icon' => 'fas fa-list'];
        $menu_items[] = ['url' => 'relatorios.php', 'label' => 'Relatórios', 'icon' => 'fas fa-file-alt'];
        break;
    
    case 'propriedades':
    case 'add_propriedade':
    case 'edit_propriedade':
        $menu_items[] = ['url' => 'estoque.php', 'label' => 'Estoque', 'icon' => 'fas fa-boxes'];
        $menu_items[] = ['url' => 'add_defensivo.php', 'label' => 'Registrar Defensivo', 'icon' => 'fas fa-plus-circle'];
        $menu_items[] = ['url' => 'view_defensivos.php', 'label' => 'Ver Registros', 'icon' => 'fas fa-list'];
        $menu_items[] = ['url' => 'relatorios.php', 'label' => 'Relatórios', 'icon' => 'fas fa-file-alt'];
        break;
    
    case 'dashboard_pastos':
    case 'dashboard_pasto':
    case 'add_pasto':
    case 'edit_pasto':
        $menu_items[] = ['url' => 'propriedades.php', 'label' => 'Propriedades', 'icon' => 'fas fa-tractor'];
        $menu_items[] = ['url' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'fas fa-chart-line'];
        break;
    
    case 'estoque':
    case 'add_lote':
    case 'edit_lote':
        $menu_items[] = ['url' => 'propriedades.php', 'label' => 'Propriedades', 'icon' => 'fas fa-tractor'];
        $menu_items[] = ['url' => 'add_defensivo.php', 'label' => 'Registrar Defensivo', 'icon' => 'fas fa-plus-circle'];
        $menu_items[] = ['url' => 'view_defensivos.php', 'label' => 'Ver Registros', 'icon' => 'fas fa-list'];
        $menu_items[] = ['url' => 'relatorios.php', 'label' => 'Relatórios', 'icon' => 'fas fa-file-alt'];
        break;
    
    case 'add_defensivo':
    case 'edit_defensivo':
    case 'view_defensivos':
        $menu_items[] = ['url' => 'propriedades.php', 'label' => 'Propriedades', 'icon' => 'fas fa-tractor'];
        $menu_items[] = ['url' => 'estoque.php', 'label' => 'Estoque', 'icon' => 'fas fa-boxes'];
        $menu_items[] = ['url' => 'relatorios.php', 'label' => 'Relatórios', 'icon' => 'fas fa-file-alt'];
        break;
    
    case 'relatorios':
    case 'gerar_relatorio':
        $menu_items[] = ['url' => 'propriedades.php', 'label' => 'Propriedades', 'icon' => 'fas fa-tractor'];
        $menu_items[] = ['url' => 'estoque.php', 'label' => 'Estoque', 'icon' => 'fas fa-boxes'];
        $menu_items[] = ['url' => 'add_defensivo.php', 'label' => 'Registrar Defensivo', 'icon' => 'fas fa-plus-circle'];
        $menu_items[] = ['url' => 'view_defensivos.php', 'label' => 'Ver Registros', 'icon' => 'fas fa-list'];
        break;
    
    default:
        // Menu padrão para outras páginas
        $menu_items[] = ['url' => 'propriedades.php', 'label' => 'Propriedades', 'icon' => 'fas fa-tractor'];
        $menu_items[] = ['url' => 'estoque.php', 'label' => 'Estoque', 'icon' => 'fas fa-boxes'];
        $menu_items[] = ['url' => 'add_defensivo.php', 'label' => 'Registrar Defensivo', 'icon' => 'fas fa-plus-circle'];
        $menu_items[] = ['url' => 'view_defensivos.php', 'label' => 'Ver Registros', 'icon' => 'fas fa-list'];
        $menu_items[] = ['url' => 'relatorios.php', 'label' => 'Relatórios', 'icon' => 'fas fa-file-alt'];
        break;
}

// Sempre adicionar logout no final
$menu_items[] = ['url' => 'logout.php', 'label' => 'Sair', 'icon' => 'fas fa-sign-out-alt', 'class' => 'logout-btn'];
?>
<header class="main-header">
    <nav class="main-nav">
        <div class="nav-brand">
            <a href="dashboard.php" class="brand-link">
                <i class="fas fa-seedling"></i>
                <span>Toxic Control</span>
            </a>
        </div>
        <div class="nav-menu" id="navMenu">
            <?php foreach ($menu_items as $item): ?>
                <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                   class="nav-link <?php echo isset($item['class']) ? htmlspecialchars($item['class']) : ''; ?>"
                   <?php if (basename($item['url'], '.php') === $current_page): ?>aria-current="page"<?php endif; ?>>
                    <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <i class="fas fa-bars"></i>
        </button>
    </nav>
</header>

