<?php
require 'config.php';
$pdo = getConnection();

$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');
$selected_dono = $_GET['dono'] ?? 'Todos';

// Busca dinâmica de todos os "Donos" que existem no banco para montar o select
$stmt_donos = $pdo->query("SELECT DISTINCT dono FROM despesas WHERE dono IS NOT NULL AND dono != '' ORDER BY dono");
$donos_list = $stmt_donos->fetchAll(PDO::FETCH_COLUMN);

// Construtor de Filtros (Adiciona o Dono na query se não for "Todos")
$where_mes = "MONTH(data_despesa) = ? AND YEAR(data_despesa) = ?";
$params_mes = [$selected_month, $selected_year];

$where_ano = "YEAR(data_despesa) = ?";
$params_ano = [$selected_year];

if ($selected_dono !== 'Todos') {
    $where_mes .= " AND dono = ?";
    $params_mes[] = $selected_dono;
    
    $where_ano .= " AND dono = ?";
    $params_ano[] = $selected_dono;
}

// 1. Cálculos do Mês Selecionado (Cards)
$stmt = $pdo->prepare("SELECT status, tipo, fator, valor FROM despesas WHERE $where_mes");
$stmt->execute($params_mes);
$despesas_mes = $stmt->fetchAll();

$total_mes = 0; $total_pago = 0; $total_pendente = 0;
$total_essencial = 0; $total_nao_essencial = 0;
$total_cartao = 0; $qtd_itens = count($despesas_mes);

foreach($despesas_mes as $d) {
    $val = (float) $d['valor'];
    $total_mes += $val;
    
    if($d['status'] == 'Pago') $total_pago += $val;
    else $total_pendente += $val;
    
    if($d['tipo'] == 'essencial') $total_essencial += $val;
    else $total_nao_essencial += $val;
    
    // Verifica se é cartão (independente de maiúsculas/minúsculas)
    if(mb_stripos($d['fator'], 'cartão', 0, 'UTF-8') !== false || mb_strtolower($d['fator'], 'UTF-8') == 'fatura' || mb_stripos($d['fator'], 'cartao', 0, 'UTF-8') !== false) {
        $total_cartao += $val;
    }
}

// 2. Top 10 Gastos (Gráfico Pizza)
$stmt_top = $pdo->prepare("SELECT descricao, SUM(valor) as total FROM despesas WHERE $where_mes GROUP BY descricao ORDER BY total DESC LIMIT 10");
$stmt_top->execute($params_mes);
$top_gastos = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

// 3. Evolução Anual (Gráfico Linha)
$stmt_ano = $pdo->prepare("SELECT MONTH(data_despesa) as mes, SUM(valor) as total FROM despesas WHERE $where_ano GROUP BY mes ORDER BY mes");
$stmt_ano->execute($params_ano);
$vendas_ano = $stmt_ano->fetchAll(PDO::FETCH_ASSOC);

$ano_map = array_fill(1, 12, 0);
foreach($vendas_ano as $row) {
    $ano_map[(int)$row['mes']] = (float)$row['total'];
}

include 'header.php';
?>

<style>
    .kpi-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px; }
    .kpi-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border: 1px solid #eee; position: relative; }
    .kpi-card h4 { font-size: 13px; color: #888; text-transform: uppercase; margin-bottom: 10px; }
    .kpi-card h2 { font-size: 28px; color: #333; }
    .badge { position: absolute; top: 20px; right: 20px; font-size: 10px; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
    
    .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px; }
    @media (max-width: 992px) { .charts-row { grid-template-columns: 1fr; } }
</style>

<div style="width: 100%;">
    <!-- TÍTULO E ÁREA DE FILTROS -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <h1 class="page-title" style="margin: 0;"><i class="fas fa-chart-pie"></i> Home-Fabio&Gi v1.0</h1>
        
        <form method="GET" style="display: flex; gap: 10px; background: #fff; padding: 10px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); flex-wrap: wrap;">
            
            <!-- Novo Filtro de Dono -->
            <select name="dono" class="form-control" style="padding: 8px; margin: 0; min-width: 130px; cursor: pointer;">
                <option value="Todos" <?= $selected_dono == 'Todos' ? 'selected' : '' ?>>Todos os Donos</option>
                <?php foreach($donos_list as $d): ?>
                    <option value="<?= htmlspecialchars($d) ?>" <?= $selected_dono === $d ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Filtro de Mês -->
            <select name="month" class="form-control" style="padding: 8px; margin: 0; min-width: 80px; cursor: pointer;">
                <?php for($i=1; $i<=12; $i++): $m = str_pad($i,2,'0',STR_PAD_LEFT); ?>
                    <option value="<?= $m ?>" <?= $selected_month == $m ? 'selected' : '' ?>><?= $m ?></option>
                <?php endfor; ?>
            </select>
            
            <!-- Filtro de Ano -->
            <input type="number" name="year" class="form-control" value="<?= htmlspecialchars($selected_year) ?>" style="padding: 8px; margin: 0; width: 90px;">
            
            <button type="submit" class="btn btn-primary" style="padding: 8px 15px;">Filtrar</button>
        </form>
    </div>

    <!-- CARDS PRINCIPAIS -->
    <div class="kpi-row">
        <div class="kpi-card" style="border-left: 5px solid #3f51b5;">
            <h4>Total do Mês <?= $selected_dono !== 'Todos' ? "($selected_dono)" : "" ?></h4>
            <h2>R$ <?= number_format($total_mes, 2, ',', '.') ?></h2>
        </div>
        <div class="kpi-card" style="border-left: 5px solid #4caf50;">
            <span class="badge" style="background: #e8f5e9; color: #4caf50;">CONCLUÍDO</span>
            <h4 style="color: #4caf50;">Já Pago</h4>
            <h2 style="color: #4caf50;">R$ <?= number_format($total_pago, 2, ',', '.') ?></h2>
        </div>
        <div class="kpi-card" style="border-left: 5px solid #e57373;">
            <span class="badge" style="background: #ffebee; color: #e57373;">FALTA PAGAR</span>
            <h4 style="color: #e57373;">Pendente</h4>
            <h2 style="color: #e57373;">R$ <?= number_format($total_pendente, 2, ',', '.') ?></h2>
        </div>
    </div>

    <!-- CARDS SECUNDÁRIOS -->
    <div class="kpi-row" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="kpi-card">
            <h4><i class="fas fa-bullseye" style="color: #e91e63;"></i> Essencial</h4>
            <h2 style="font-size: 20px; color: #3f51b5;">R$ <?= number_format($total_essencial, 2, ',', '.') ?></h2>
        </div>
        <div class="kpi-card">
            <h4><i class="fas fa-gem" style="color: #00bcd4;"></i> Não Essencial</h4>
            <h2 style="font-size: 20px; color: #555;">R$ <?= number_format($total_nao_essencial, 2, ',', '.') ?></h2>
        </div>
        <div class="kpi-card">
            <h4><i class="fas fa-credit-card" style="color: #2196F3;"></i> Total Cartão</h4>
            <h2 style="font-size: 20px; color: #555;">R$ <?= number_format($total_cartao, 2, ',', '.') ?></h2>
        </div>
        <div class="kpi-card">
            <h4><i class="fas fa-clipboard-list" style="color: #ff9800;"></i> Qtd Itens</h4>
            <h2 style="font-size: 20px; color: #555;"><?= $qtd_itens ?> despesas</h2>
        </div>
    </div>

    <!-- GRÁFICOS -->
    <div class="charts-row">
        <div class="card" style="margin: 0;">
            <h3 style="color: #555; margin-bottom: 15px;">Evolução Anual (<?= htmlspecialchars($selected_year) ?>)</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="chartEvo"></canvas>
            </div>
        </div>
        <div class="card" style="margin: 0;">
            <h3 style="color: #555; margin-bottom: 15px;">Top 10 Maiores Gastos</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="chartTop"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    // Gráfico 1: Evolução Anual
    new Chart(document.getElementById('chartEvo').getContext('2d'), {
        type: 'line',
        data: {
            labels: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
            datasets: [{
                label: 'Gastos (R$)',
                data: <?= json_encode(array_values($ano_map)) ?>,
                borderColor: '#6c5ce7',
                backgroundColor: 'rgba(108, 92, 231, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Gráfico 2: Top 10 Gastos
    const labelsTop = <?= json_encode(array_column($top_gastos, 'descricao'), JSON_UNESCAPED_UNICODE) ?>;
    const dataTop = <?= json_encode(array_map('floatval', array_column($top_gastos, 'total'))) ?>;
    
    new Chart(document.getElementById('chartTop').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: labelsTop,
            datasets: [{
                data: dataTop,
                backgroundColor: ['#e91e63', '#9c27b0', '#3f51b5', '#2196f3', '#00bcd4', '#009688', '#4caf50', '#cddc39', '#ffc107', '#ff9800']
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            cutout: '50%',
            plugins: { 
                legend: { 
                    position: 'bottom', 
                    labels: { boxWidth: 12, font: {size: 10} } 
                } 
            }
        }
    });
</script>

<?php include 'footer.php'; ?>
