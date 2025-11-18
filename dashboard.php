<?php
session_start();
include('config/db.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Buscar defensivos com prazo de validade próximo (7 dias ou menos) ou já vencidos
$usuario_id = $_SESSION['usuario_id'];
$hoje = date('Y-m-d');
$data_limite = date('Y-m-d', strtotime('+7 days'));

// Buscar defensivos que estão vencendo nos próximos 7 dias ou já venceram
$stmt = $conn->prepare("SELECT id, nome_produto, prazo_validade FROM defensivos WHERE usuario_id = ? AND prazo_validade IS NOT NULL AND prazo_validade <= ? ORDER BY prazo_validade ASC");
$stmt->bind_param("is", $usuario_id, $data_limite);
$stmt->execute();
$result = $stmt->get_result();
$defensivos_proximos_vencimento = [];

while ($row = $result->fetch_assoc()) {
    $validade = new DateTime($row['prazo_validade']);
    $hoje_obj = new DateTime();
    $hoje_obj->setTime(0, 0, 0);
    $validade->setTime(0, 0, 0);
    
    // Calcular diferença em dias
    $diff = $hoje_obj->diff($validade);
    $dias_restantes = (int)$diff->format('%r%a'); // %r para sinal negativo se vencido
    
    // Se a validade é anterior a hoje, o número já será negativo
    // Se é igual ou posterior, será positivo
    if ($validade < $hoje_obj) {
        $dias_restantes = -abs($dias_restantes); // Garantir negativo para vencidos
    }
    
    $defensivos_proximos_vencimento[] = [
        'id' => $row['id'],
        'nome_produto' => $row['nome_produto'],
        'prazo_validade' => $row['prazo_validade'],
        'dias_restantes' => $dias_restantes
    ];
}
$stmt->close();

// ===== DADOS PARA GRÁFICOS =====

// 1. Gráfico de Defensivos por Cultura
$stmt = $conn->prepare("SELECT cultura, COUNT(*) as total FROM defensivos WHERE usuario_id = ? AND cultura IS NOT NULL AND cultura != '' GROUP BY cultura ORDER BY total DESC LIMIT 10");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_cultura = $stmt->get_result();
$dados_cultura = [];
$labels_cultura = [];
while ($row = $result_cultura->fetch_assoc()) {
    $labels_cultura[] = $row['cultura'];
    $dados_cultura[] = (int)$row['total'];
}
$stmt->close();

// 2. Gráfico de Aplicações por Mês (últimos 12 meses)
$stmt = $conn->prepare("SELECT DATE_FORMAT(data_aplicacao, '%Y-%m') as mes, COUNT(*) as total FROM defensivos WHERE usuario_id = ? AND data_aplicacao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY mes ORDER BY mes ASC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_mes = $stmt->get_result();
$dados_mes = [];
$labels_mes = [];
$meses_pt = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
while ($row = $result_mes->fetch_assoc()) {
    $data_obj = DateTime::createFromFormat('Y-m', $row['mes']);
    $mes_num = (int)$data_obj->format('n') - 1;
    $ano = $data_obj->format('Y');
    $mes_formatado = $meses_pt[$mes_num] . '/' . $ano;
    $labels_mes[] = $mes_formatado;
    $dados_mes[] = (int)$row['total'];
}
$stmt->close();

// 3. Gráfico de Produtos Mais Usados
$stmt = $conn->prepare("SELECT nome_produto, COUNT(*) as total FROM defensivos WHERE usuario_id = ? GROUP BY nome_produto ORDER BY total DESC LIMIT 10");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_produto = $stmt->get_result();
$dados_produto = [];
$labels_produto = [];
while ($row = $result_produto->fetch_assoc()) {
    $labels_produto[] = $row['nome_produto'];
    $dados_produto[] = (int)$row['total'];
}
$stmt->close();

// 4. Gráfico de Status de Validade
$stmt = $conn->prepare("SELECT 
    COUNT(CASE WHEN prazo_validade IS NULL THEN 1 END) as sem_validade,
    COUNT(CASE WHEN prazo_validade > CURDATE() AND prazo_validade > DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as valido,
    COUNT(CASE WHEN prazo_validade <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND prazo_validade >= CURDATE() THEN 1 END) as proximo_vencimento,
    COUNT(CASE WHEN prazo_validade < CURDATE() THEN 1 END) as vencido
    FROM defensivos WHERE usuario_id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_status = $stmt->get_result();
$status_data = $result_status->fetch_assoc();
$stmt->close();

// 5. Gráfico de Aplicações por Ano
$stmt = $conn->prepare("SELECT YEAR(data_aplicacao) as ano, COUNT(*) as total FROM defensivos WHERE usuario_id = ? GROUP BY ano ORDER BY ano DESC LIMIT 5");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_ano = $stmt->get_result();
$dados_ano = [];
$labels_ano = [];
while ($row = $result_ano->fetch_assoc()) {
    $labels_ano[] = (string)$row['ano'];
    $dados_ano[] = (int)$row['total'];
}
$stmt->close();

// 6. Gráfico de Aplicações por Dia da Semana
$stmt = $conn->prepare("SELECT DAYNAME(data_aplicacao) as dia_semana, COUNT(*) as total FROM defensivos WHERE usuario_id = ? AND data_aplicacao >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY dia_semana ORDER BY FIELD(dia_semana, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_dia = $stmt->get_result();
$dados_dia = [];
$labels_dia = [];
$dias_pt = ['Sunday' => 'Dom', 'Monday' => 'Seg', 'Tuesday' => 'Ter', 'Wednesday' => 'Qua', 'Thursday' => 'Qui', 'Friday' => 'Sex', 'Saturday' => 'Sáb'];
$dias_ordem = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$dados_dia_temp = [];
while ($row = $result_dia->fetch_assoc()) {
    $dados_dia_temp[$row['dia_semana']] = (int)$row['total'];
}
// Ordenar conforme a ordem dos dias da semana
foreach ($dias_ordem as $dia_en) {
    if (isset($dados_dia_temp[$dia_en])) {
        $labels_dia[] = $dias_pt[$dia_en];
        $dados_dia[] = $dados_dia_temp[$dia_en];
    }
}
$stmt->close();

// 7. Gráfico de Aplicações dos Últimos 30 Dias
$stmt = $conn->prepare("SELECT DATE(data_aplicacao) as data, COUNT(*) as total FROM defensivos WHERE usuario_id = ? AND data_aplicacao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY data ORDER BY data ASC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_30dias = $stmt->get_result();
$dados_30dias = [];
$labels_30dias = [];
while ($row = $result_30dias->fetch_assoc()) {
    $data_obj = DateTime::createFromFormat('Y-m-d', $row['data']);
    $labels_30dias[] = $data_obj->format('d/m');
    $dados_30dias[] = (int)$row['total'];
}
$stmt->close();

// 8. Estatísticas Gerais
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total_registros,
    COUNT(DISTINCT nome_produto) as produtos_unicos,
    COUNT(DISTINCT cultura) as culturas_unicas,
    MIN(data_aplicacao) as primeira_aplicacao,
    MAX(data_aplicacao) as ultima_aplicacao
    FROM defensivos WHERE usuario_id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_stats = $stmt->get_result();
$stats = $result_stats->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Toxic Control</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<?php 
$current_page = 'dashboard';
include('includes/header.php'); 
?>
<div class="container">
    
    <?php if (!empty($defensivos_proximos_vencimento)): ?>
    <!-- Modal de Alerta de Vencimento -->
    <div id="vencimentoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Alertas de Validade</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Os seguintes defensivos estão próximos do vencimento ou já venceram:</p>
                <ul class="lista-vencimentos">
                    <?php foreach ($defensivos_proximos_vencimento as $defensivo): ?>
                        <li class="<?php echo $defensivo['dias_restantes'] < 0 ? 'vencido' : 'proximo'; ?>">
                            <strong><?php echo htmlspecialchars($defensivo['nome_produto']); ?></strong>
                            <br>
                            <span class="data-validade">
                                Validade: <?php echo date('d/m/Y', strtotime($defensivo['prazo_validade'])); ?>
                            </span>
                            <br>
                            <?php if ($defensivo['dias_restantes'] < 0): ?>
                                <span class="badge badge-danger">Vencido há <?php echo abs($defensivo['dias_restantes']); ?> dia(s)</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Faltam <?php echo $defensivo['dias_restantes']; ?> dia(s)</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="modal-footer">
                <button onclick="closeModal()" class="btn-modal">Fechar</button>
                <a href="view_defensivos.php" class="btn-modal btn-primary">Ver Todos os Registros</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Seção de Estatísticas Gerais -->
    <div class="stats-section">
        <h2 style="color: white; text-align: center; margin: var(--spacing-8) 0 var(--spacing-6);">Visão Geral</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-content">
                    <h3><?php echo (int)$stats['total_registros']; ?></h3>
                    <p>Total de Registros</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-flask"></i></div>
                <div class="stat-content">
                    <h3><?php echo (int)$stats['produtos_unicos']; ?></h3>
                    <p>Produtos Diferentes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-seedling"></i></div>
                <div class="stat-content">
                    <h3><?php echo (int)$stats['culturas_unicas']; ?></h3>
                    <p>Culturas Tratadas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-content">
                    <h3><?php echo $stats['ultima_aplicacao'] ? date('d/m/Y', strtotime($stats['ultima_aplicacao'])) : '-'; ?></h3>
                    <p>Última Aplicação</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Seção de Gráficos -->
    <div class="charts-section">
        <h2 style="color: white; text-align: center; margin: var(--spacing-8) 0 var(--spacing-6);">Análises Detalhadas</h2>
        
        <div class="charts-grid charts-grid-large">
            <!-- Gráfico 1: Aplicações por Mês -->
            <div class="chart-card chart-card-large">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-line"></i> Aplicações por Mês (12 meses)</h3>
                </div>
                <div class="chart-body">
                    <canvas id="chartMes"></canvas>
                </div>
            </div>
            
            <!-- Gráfico 2: Aplicações dos Últimos 30 Dias -->
            <div class="chart-card chart-card-large">
                <div class="chart-header">
                    <h3><i class="fas fa-calendar-week"></i> Aplicações dos Últimos 30 Dias</h3>
                </div>
                <div class="chart-body">
                    <canvas id="chart30Dias"></canvas>
                </div>
            </div>
        </div>
        
        <div class="charts-grid">
            <!-- Gráfico 3: Defensivos por Cultura -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-seedling"></i> Defensivos por Cultura</h3>
                </div>
                <div class="chart-body">
                    <canvas id="chartCultura"></canvas>
                </div>
            </div>
            
            <!-- Gráfico 4: Produtos Mais Usados -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-flask"></i> Produtos Mais Usados</h3>
                </div>
                <div class="chart-body">
                    <canvas id="chartProduto"></canvas>
                </div>
            </div>
            
            <!-- Gráfico 5: Status de Validade -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Status de Validade</h3>
                </div>
                <div class="chart-body">
                    <canvas id="chartStatus"></canvas>
                </div>
            </div>
            
            <!-- Gráfico 6: Aplicações por Ano -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-calendar"></i> Aplicações por Ano</h3>
                </div>
                <div class="chart-body">
                    <canvas id="chartAno"></canvas>
                </div>
            </div>
            
            <!-- Gráfico 7: Aplicações por Dia da Semana -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-calendar-day"></i> Aplicações por Dia da Semana</h3>
                </div>
                <div class="chart-body">
                    <canvas id="chartDiaSemana"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Dados dos gráficos
    const dadosCultura = {
        labels: <?php echo json_encode($labels_cultura); ?>,
        data: <?php echo json_encode($dados_cultura); ?>
    };
    
    const dadosMes = {
        labels: <?php echo json_encode($labels_mes); ?>,
        data: <?php echo json_encode($dados_mes); ?>
    };
    
    const dadosProduto = {
        labels: <?php echo json_encode($labels_produto); ?>,
        data: <?php echo json_encode($dados_produto); ?>
    };
    
    const dadosStatus = {
        semValidade: <?php echo (int)$status_data['sem_validade']; ?>,
        valido: <?php echo (int)$status_data['valido']; ?>,
        proximoVencimento: <?php echo (int)$status_data['proximo_vencimento']; ?>,
        vencido: <?php echo (int)$status_data['vencido']; ?>
    };
    
    const dadosAno = {
        labels: <?php echo json_encode($labels_ano); ?>,
        data: <?php echo json_encode($dados_ano); ?>
    };
    
    const dadosDiaSemana = {
        labels: <?php echo json_encode($labels_dia); ?>,
        data: <?php echo json_encode($dados_dia); ?>
    };
    
    const dados30Dias = {
        labels: <?php echo json_encode($labels_30dias); ?>,
        data: <?php echo json_encode($dados_30dias); ?>
    };
    
    // Configuração de cores
    const cores = {
        primary: '#1a5f3f',
        secondary: '#2e7d32',
        warning: '#f59e0b',
        error: '#ef4444',
        info: '#3b82f6',
        success: '#10b981'
    };
    
    // Gráfico 1: Aplicações por Mês (Linha)
    const ctxMes = document.getElementById('chartMes');
    if (ctxMes && dadosMes.labels.length > 0) {
        new Chart(ctxMes, {
            type: 'line',
            data: {
                labels: dadosMes.labels,
                datasets: [{
                    label: 'Aplicações',
                    data: dadosMes.data,
                    borderColor: '#1a5f3f',
                    backgroundColor: 'rgba(26, 95, 63, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#1a5f3f',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    } else if (ctxMes) {
        ctxMes.parentElement.innerHTML = '<p class="no-data">Nenhum dado disponível</p>';
    }
    
    // Gráfico 2: Aplicações dos Últimos 30 Dias
    const ctx30Dias = document.getElementById('chart30Dias');
    if (ctx30Dias && dados30Dias.labels.length > 0) {
        new Chart(ctx30Dias, {
            type: 'bar',
            data: {
                labels: dados30Dias.labels,
                datasets: [{
                    label: 'Aplicações',
                    data: dados30Dias.data,
                    backgroundColor: '#2e7d32',
                    borderColor: '#1a5f3f',
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    } else if (ctx30Dias) {
        ctx30Dias.parentElement.innerHTML = '<p class="no-data">Nenhum dado disponível</p>';
    }
    
    // Gráfico 3: Defensivos por Cultura (Pizza)
    const ctxCultura = document.getElementById('chartCultura');
    if (ctxCultura && dadosCultura.labels.length > 0) {
        new Chart(ctxCultura, {
            type: 'doughnut',
            data: {
                labels: dadosCultura.labels,
                datasets: [{
                    label: 'Quantidade',
                    data: dadosCultura.data,
                    backgroundColor: [
                        '#1a5f3f',
                        '#2e7d32',
                        '#10b981',
                        '#3b82f6',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#ec4899',
                        '#14b8a6',
                        '#f97316'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    } else if (ctxCultura) {
        ctxCultura.parentElement.innerHTML = '<p class="no-data">Nenhum dado disponível</p>';
    }
    
    // Gráfico 4: Produtos Mais Usados (Barras)
    const ctxProduto = document.getElementById('chartProduto');
    if (ctxProduto && dadosProduto.labels.length > 0) {
        new Chart(ctxProduto, {
            type: 'bar',
            data: {
                labels: dadosProduto.labels,
                datasets: [{
                    label: 'Quantidade de Aplicações',
                    data: dadosProduto.data,
                    backgroundColor: '#1a5f3f',
                    borderColor: '#0f4c3a',
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    } else if (ctxProduto) {
        ctxProduto.parentElement.innerHTML = '<p class="no-data">Nenhum dado disponível</p>';
    }
    
    // Gráfico 5: Status de Validade (Pizza)
    const ctxStatus = document.getElementById('chartStatus');
    if (ctxStatus && (dadosStatus.semValidade > 0 || dadosStatus.valido > 0 || dadosStatus.proximoVencimento > 0 || dadosStatus.vencido > 0)) {
        new Chart(ctxStatus, {
            type: 'pie',
            data: {
                labels: ['Sem Validade', 'Válido', 'Próximo Vencimento', 'Vencido'],
                datasets: [{
                    data: [
                        dadosStatus.semValidade,
                        dadosStatus.valido,
                        dadosStatus.proximoVencimento,
                        dadosStatus.vencido
                    ],
                    backgroundColor: [
                        '#6b7280',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    } else if (ctxStatus) {
        ctxStatus.parentElement.innerHTML = '<p class="no-data">Nenhum dado disponível</p>';
    }
    
    // Gráfico 6: Aplicações por Ano
    const ctxAno = document.getElementById('chartAno');
    if (ctxAno && dadosAno.labels.length > 0) {
        new Chart(ctxAno, {
            type: 'bar',
            data: {
                labels: dadosAno.labels,
                datasets: [{
                    label: 'Aplicações',
                    data: dadosAno.data,
                    backgroundColor: '#3b82f6',
                    borderColor: '#2563eb',
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    } else if (ctxAno) {
        ctxAno.parentElement.innerHTML = '<p class="no-data">Nenhum dado disponível</p>';
    }
    
    // Gráfico 7: Aplicações por Dia da Semana
    const ctxDiaSemana = document.getElementById('chartDiaSemana');
    if (ctxDiaSemana && dadosDiaSemana.labels.length > 0) {
        new Chart(ctxDiaSemana, {
            type: 'doughnut',
            data: {
                labels: dadosDiaSemana.labels,
                datasets: [{
                    label: 'Aplicações',
                    data: dadosDiaSemana.data,
                    backgroundColor: [
                        '#1a5f3f',
                        '#2e7d32',
                        '#10b981',
                        '#3b82f6',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    } else if (ctxDiaSemana) {
        ctxDiaSemana.parentElement.innerHTML = '<p class="no-data">Nenhum dado disponível</p>';
    }
    
    <?php if (!empty($defensivos_proximos_vencimento)): ?>
    // Mostrar modal automaticamente ao carregar a página
    window.onload = function() {
        document.getElementById('vencimentoModal').style.display = 'block';
    };
    
    // Fechar modal ao clicar no X
    function closeModal() {
        document.getElementById('vencimentoModal').style.display = 'none';
    }
    
    // Fechar modal ao clicar fora dele
    window.onclick = function(event) {
        const modal = document.getElementById('vencimentoModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    <?php endif; ?>
</script>
<script src="assets/js/header.js"></script>
</body>
</html>
