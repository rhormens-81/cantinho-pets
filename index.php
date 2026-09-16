<?php
require 'config.php';
$pdo = getConnection();

// Filtros de Mês e Ano
$selected_month = $_GET['month'] ?? date('Y-m');
$selected_year = $_GET['year'] ?? date('Y');
$month_label = date('m/Y', strtotime($selected_month . '-01'));

try {
    // 1. Resumo Geral dos Cards Superiores
    $stmt_totais = $pdo->prepare("SELECT tipo, COUNT(*) as qtd, SUM(valor) as total FROM vendas WHERE DATE_FORMAT(data_venda, '%Y-%m') = ? GROUP BY tipo");
    $stmt_totais->execute([$selected_month]);
    $status_data = $stmt_totais->fetchAll();
    
    $qtd_pagos = 0; $valor_pagos = 0;
    $qtd_fiados = 0; $valor_fiados = 0;
    
    foreach($status_data as $row) {
        if($row['tipo'] == 'pago') { $qtd_pagos = $row['qtd']; $valor_pagos = $row['total']; }
        if($row['tipo'] == 'fiado') { $qtd_fiados = $row['qtd']; $valor_fiados = $row['total']; }
    }
    
    $total_mes = $valor_pagos + $valor_fiados;
    $qtd_servicos = $qtd_pagos + $qtd_fiados;

    // 2. Vendas por dia do mês
    $stmt = $pdo->prepare("SELECT DATE(data_venda) as dia, SUM(valor) as total FROM vendas WHERE DATE_FORMAT(data_venda, '%Y-%m') = ? AND tipo != 'fiado' GROUP BY dia ORDER BY dia");
    $stmt->execute([$selected_month]);
    $vendas_por_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Atendimentos/Serviços por dia do mês
    $stmt = $pdo->prepare("SELECT DATE(data_venda) as dia, COUNT(id) as atendimentos FROM vendas WHERE DATE_FORMAT(data_venda, '%Y-%m') = ? GROUP BY dia ORDER BY dia");
    $stmt->execute([$selected_month]);
    $atendimentos_dia_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estruturação das datas
    $date_start = new DateTime($selected_month . '-01');
    $date_end = new DateTime($selected_month . '-01');
    $date_end->modify('last day of this month');
    $days = [];
    $totais_por_dia = [];
    $atendimentos_por_dia = [];

    $period = new DatePeriod($date_start, new DateInterval('P1D'), $date_end->modify('+1 day'));
    
    $map_vendas = [];
    foreach ($vendas_por_dia as $row) { $map_vendas[$row['dia']] = (float) $row['total']; }
    
    $map_atendimentos = [];
    foreach ($atendimentos_dia_raw as $row) { $map_atendimentos[$row['dia']] = (int) $row['atendimentos']; }

    foreach ($period as $dt) {
        $d = $dt->format('Y-m-d');
        $days[] = $dt->format('d');
        $totais_por_dia[] = isset($map_vendas[$d]) ? round($map_vendas[$d], 2) : 0.00;
        $atendimentos_por_dia[] = isset($map_atendimentos[$d]) ? $map_atendimentos[$d] : 0;
    }

    // 4. Vendas por mês do ano selecionado
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(data_venda, '%m') as mes, COALESCE(SUM(valor), 0) as total FROM vendas WHERE DATE_FORMAT(data_venda, '%Y') = ? GROUP BY mes ORDER BY mes");
    $stmt->execute([$selected_year]);
    $vendas_por_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $monthly_map = [];
    foreach ($vendas_por_mes as $row) { $monthly_map[$row['mes']] = (float) $row['total']; }

    $monthly_labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $monthly_totals = [];
    for ($i = 1; $i <= 12; $i++) {
        $mes = str_pad($i, 2, '0', STR_PAD_LEFT);
        $monthly_totals[] = isset($monthly_map[$mes]) ? round($monthly_map[$mes], 2) : 0.00;
    }

    // 5. Top Serviços do Mês
    $stmt = $pdo->prepare("
        SELECT p.id, p.nome, COUNT(v.id) as qtd, SUM(v.valor) as total_servico
        FROM vendas v
        JOIN produtos p ON v.produto_id = p.id
        WHERE DATE_FORMAT(v.data_venda, '%Y-%m') = ?
        GROUP BY p.id
        ORDER BY qtd DESC, total_servico DESC
        LIMIT 10
    ");
    $stmt->execute([$selected_month]);
    $top_servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Erro ao buscar dados: " . $e->getMessage();
    $days = []; $totais_por_dia = []; $atendimentos_por_dia = [];
    $monthly_labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $monthly_totals = array_fill(0, 12, 0.00);
    $top_servicos = [];
}

include 'header.php';
?>

<!-- Estilos para a nova estrutura de gráficos flexível -->
<style>
    .dashboard-container { display: flex; flex-direction: column; gap: 20px; width: 100%; max-width: 100%; }
    
    .form-grid { display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; width: 100%; align-items: flex-end; }
    
    /* NOVA REGRA: Grid fluido para os gráficos lado a lado */
    .charts-grid-fluid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); /* Organiza lado a lado até caber, depois joga pra baixo */
        gap: 20px; 
        width: 100%; 
        margin-bottom: 40px;
    }
    
    @media (max-width: 992px) {
        .form-grid { grid-template-columns: 1fr; }
    }
    
    /* Previne que em celulares o mínimo de 400px quebre a tela */
    @media (max-width: 480px) {
        .charts-grid-fluid { grid-template-columns: 1fr; }
    }

    .dash-card { position: relative; overflow: hidden; }
    .dash-card .card-icon { position: absolute; top: 20px; right: 20px; font-size: 40px; opacity: 0.15; }
    
    .chart-container { 
        background: #fff; 
        padding: 20px; 
        border-radius: 15px; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.03); 
        border-top: 4px solid var(--accent-color); 
        width: 100%; 
        display: flex;
        flex-direction: column;
    }
    .chart-container h3 { font-size: 16px; color: #555; margin-bottom: 5px; }
    .chart-container p { font-size: 12px; color: #999; margin-bottom: 15px; }
    
    /* A div flex-grow: 1 garante que o canvas preencha bem o espaço disponível */
    .canvas-wrap { position: relative; height: 300px; width: 100%; flex-grow: 1; }
</style>

<div class="dashboard-container">

    <!-- SEÇÃO 1: FILTROS E ALERTAS -->
    <div>
        <h1 class="page-title"><i class="fas fa-chart-line"></i> Dashboard Interativo</h1>
        
        <?php if (isset($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="GET" class="card form-grid" style="margin-bottom: 15px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Análise Mensal (Gráficos Diários)</label>
                <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($selected_month) ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Análise Anual (Gráfico de Meses)</label>
                <input type="number" name="year" class="form-control" min="2020" max="<?= date('Y') + 1 ?>" step="1" value="<?= htmlspecialchars($selected_year) ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-primary" style="height: 46px; width: 100%; min-width: 150px;">
                    <i class="fas fa-filter"></i> Aplicar Filtros
                </button>
            </div>
        </form>
    </div>

    <!-- SEÇÃO 2: CARDS DE RESUMO -->
    <div class="dash-cards" style="margin-bottom: 10px;">
        <div class="dash-card">
            <i class="fas fa-money-bill-wave card-icon" style="color: var(--accent-color);"></i>
            <h3>Faturamento Total (Mês)</h3>
            <h2>R$ <?= number_format($total_mes, 2, ',', '.') ?></h2>
        </div>
        
        <div class="dash-card" style="border-left-color: #2196F3;">
            <i class="fas fa-cut card-icon" style="color: #2196F3;"></i>
            <h3>Atendimentos (Mês)</h3>
            <h2><?= $qtd_servicos ?></h2>
        </div>
        
        <div class="dash-card" style="border-left-color: #4caf50;">
            <i class="fas fa-check-circle card-icon" style="color: #4caf50;"></i>
            <h3>Qtd. Pagos</h3>
            <h2><?= $qtd_pagos ?> <span style="font-size: 14px; color: #888;">(R$ <?= number_format($valor_pagos, 2, ',', '.') ?>)</span></h2>
        </div>
        
        <div class="dash-card" style="border-left-color: #ff9800;">
            <i class="fas fa-exclamation-triangle card-icon" style="color: #ff9800;"></i>
            <h3>Qtd. Fiados</h3>
            <h2><?= $qtd_fiados ?> <span style="font-size: 14px; color: #888;">(R$ <?= number_format($valor_fiados, 2, ',', '.') ?>)</span></h2>
        </div>
    </div>

    <!-- SEÇÃO 3: GRÁFICOS FLUIDOS LADO A LADO -->
    <div class="charts-grid-fluid">
        
        <!-- Gráfico 1 -->
        <div class="chart-container">
            <h3>Vendas Diárias (Pagos) — <?= htmlspecialchars($month_label) ?></h3>
            <p>Acompanhamento de entradas reais no caixa por dia.</p>
            <div class="canvas-wrap">
                <canvas id="chartVendasDia"></canvas>
            </div>
        </div>

        <!-- Gráfico 2 -->
        <div class="chart-container" style="border-top-color: #2196F3;">
            <h3>Volume de Atendimentos — <?= htmlspecialchars($month_label) ?></h3>
            <p>Quantidade de serviços executados diariamente.</p>
            <div class="canvas-wrap">
                <canvas id="chartAtendimentosDia"></canvas>
            </div>
        </div>

        <!-- Gráfico 3 -->
        <div class="chart-container" style="border-top-color: #9c27b0;">
            <h3>Top Serviços — <?= htmlspecialchars($month_label) ?></h3>
            <p>Os serviços mais populares do mês.</p>
            <div class="canvas-wrap">
                <canvas id="chartTopServicos"></canvas>
            </div>
        </div>
        
        <!-- Gráfico 4 -->
        <div class="chart-container" style="border-top-color: #ff9800;">
            <h3>Comparativo Mensal — Ano <?= htmlspecialchars($selected_year) ?></h3>
            <p>Visão geral de faturamento ao longo dos meses.</p>
            <div class="canvas-wrap">
                <canvas id="chartVendasMes"></canvas>
            </div>
        </div>

    </div>

</div>

<!-- PREPARAÇÃO DOS DADOS PARA O CHART.JS -->
<script>
    const labelsDias = <?= json_encode($days, JSON_UNESCAPED_UNICODE) ?>;
    const totaisDias = <?= json_encode($totais_por_dia) ?>;
    const atendimentosDias = <?= json_encode($atendimentos_por_dia) ?>;
    
    const servicosLabels = <?= json_encode(array_column($top_servicos, 'nome'), JSON_UNESCAPED_UNICODE) ?>;
    const servicosQtd = <?= json_encode(array_map('intval', array_column($top_servicos, 'qtd'))) ?>;
    
    const monthlyLabels = <?= json_encode($monthly_labels, JSON_UNESCAPED_UNICODE) ?>;
    const monthlyTotals = <?= json_encode($monthly_totals) ?>;

    const colorPrimary = '#ff6b8b';
    const colorBlue = '#2196F3';
    const colorBlueLight = 'rgba(33, 150, 243, 0.2)';
    const colorOrange = '#ff9800';

    new Chart(document.getElementById('chartVendasDia').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labelsDias,
            datasets: [{
                label: 'Faturamento (R$)',
                data: totaisDias,
                backgroundColor: colorPrimary,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    new Chart(document.getElementById('chartAtendimentosDia').getContext('2d'), {
        type: 'line',
        data: {
            labels: labelsDias,
            datasets: [{
                label: 'Atendimentos',
                data: atendimentosDias,
                borderColor: colorBlue,
                backgroundColor: colorBlueLight,
                tension: 0.4,
                fill: true,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    new Chart(document.getElementById('chartTopServicos').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: servicosLabels,
            datasets: [{
                data: servicosQtd,
                backgroundColor: ['#e91e63', '#2196F3', '#4caf50', '#ff9800', '#9c27b0', '#00bcd4', '#ffeb3b', '#3f51b5', '#cddc39', '#795548']
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'right' } },
            cutout: '65%'
        }
    });

    new Chart(document.getElementById('chartVendasMes').getContext('2d'), {
        type: 'bar',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Faturamento Mensal (R$)',
                data: monthlyTotals,
                backgroundColor: colorOrange,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

<?php include 'footer.php'; ?>