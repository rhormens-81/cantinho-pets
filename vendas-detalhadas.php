<?php
require_once 'config.php';
$pdo = getConnection();

// Captura e validação dos filtros de Mês e Ano
$selected_month = isset($_GET['month']) ? str_pad((int)$_GET['month'], 2, '0', STR_PAD_LEFT) : date('m');
$selected_year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$status_filter  = $_GET['status'] ?? 'todos';

// Garante valores válidos para mês
if ((int)$selected_month < 1 || (int)$selected_month > 12) {
    $selected_month = date('m');
}

// Array auxiliar para nomes dos meses em português
$meses_nome = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
    '04' => 'Abril',   '05' => 'Maio',      '06' => 'Junho',
    '07' => 'Julho',   '08' => 'Agosto',    '09' => 'Setembro',
    '10' => 'Outubro', '11' => 'Novembro',  '12' => 'Dezembro'
];

$vendas = [];
$total_geral = 0.0;
$total_pago  = 0.0;
$total_fiado = 0.0;
$error = null;

try {
    $sql = "SELECT 
                v.id,
                v.data_venda,
                v.valor,
                v.tipo,
                c.nome AS cliente_nome,
                c.telefone AS cliente_telefone,
                c.nome_animal,
                c.raca_animal,
                p.nome AS servico_nome
            FROM vendas v
            INNER JOIN clientes c ON v.cliente_id = c.id
            INNER JOIN produtos p ON v.produto_id = p.id
            WHERE MONTH(v.data_venda) = :mes 
              AND YEAR(v.data_venda) = :ano";

    $params = [
        ':mes' => $selected_month,
        ':ano' => $selected_year
    ];

    if ($status_filter === 'pago' || $status_filter === 'fiado') {
        $sql .= " AND v.tipo = :tipo";
        $params[':tipo'] = $status_filter;
    }

    // Ordenação da data mais recente para a mais antiga (DESC) e pelo ID DESC
    $sql .= " ORDER BY v.data_venda DESC, v.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cálculo dos totalizadores do período filtrado
    foreach ($vendas as $v) {
        $val = (float)$v['valor'];
        $total_geral += $val;
        if ($v['tipo'] === 'pago') {
            $total_pago += $val;
        } else {
            $total_fiado += $val;
        }
    }

} catch (PDOException $e) {
    $error = "Erro ao carregar os lançamentos detalhados: " . $e->getMessage();
}

include 'header.php';
?>

<style>
    .detalhes-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
        width: 100%;
        max-width: 100%;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: 15px;
        align-items: flex-end;
        width: 100%;
    }

    .summary-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        width: 100%;
    }

    .summary-box {
        background: #fff;
        padding: 18px 20px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        border-left: 4px solid var(--accent-color);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .summary-box span {
        font-size: 12px;
        color: #888;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .summary-box strong {
        font-size: 22px;
        color: #2c3e50;
    }

    .tabela-detalhada th {
        background-color: var(--bg-sidebar) !important;
        color: #fff !important;
        white-space: nowrap;
    }

    .badge-status {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        text-transform: uppercase;
    }

    .badge-pago {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    .badge-fiado {
        background-color: #fff3e0;
        color: #ef6c00;
    }

    @media (max-width: 992px) {
        .filter-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 600px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="detalhes-container">

    <!-- Cabeçalho e Filtros -->
    <div>
        <h1 class="page-title">
            <i class="fas fa-list-alt"></i> Extrato Detalhado por Dia — <?= $meses_nome[$selected_month] ?> / <?= $selected_year ?>
        </h1>

        <?php if ($error): ?>
            <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="GET" class="card filter-grid" style="margin-bottom: 0;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="month">Mês de Referência</label>
                <select name="month" id="month" class="form-control">
                    <?php foreach ($meses_nome as $num => $nome): ?>
                        <option value="<?= $num ?>" <?= $selected_month === $num ? 'selected' : '' ?>>
                            <?= $num ?> - <?= $nome ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="year">Ano</label>
                <input type="number" name="year" id="year" class="form-control" min="2020" max="<?= date('Y') + 2 ?>" value="<?= htmlspecialchars($selected_year) ?>" required>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="status">Status do Pagamento</label>
                <select name="status" id="status" class="form-control">
                    <option value="todos" <?= $status_filter === 'todos' ? 'selected' : '' ?>>Todos os Lançamentos</option>
                    <option value="pago" <?= $status_filter === 'pago' ? 'selected' : '' ?>>Somente Pagos</option>
                    <option value="fiado" <?= $status_filter === 'fiado' ? 'selected' : '' ?>>Somente Fiados</option>
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="height: 46px; width: 100%; min-width: 140px;">
                    <i class="fas fa-search"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Faixa de Resumo Rápido do Período -->
    <div class="summary-strip">
        <div class="summary-box">
            <span>Qtd. Lançamentos</span>
            <strong><?= count($vendas) ?></strong>
        </div>
        <div class="summary-box" style="border-left-color: #3f51b5;">
            <span>Total Movimentado</span>
            <strong>R$ <?= number_format($total_geral, 2, ',', '.') ?></strong>
        </div>
        <div class="summary-box" style="border-left-color: #4caf50;">
            <span>Total Recebido (Pago)</span>
            <strong style="color: #2e7d32;">R$ <?= number_format($total_pago, 2, ',', '.') ?></strong>
        </div>
        <div class="summary-box" style="border-left-color: #ff9800;">
            <span>Total a Receber (Fiado)</span>
            <strong style="color: #ef6c00;">R$ <?= number_format($total_fiado, 2, ',', '.') ?></strong>
        </div>
    </div>

    <!-- Tabela Detalhada (Ordenada por Data DESC) -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <h3 style="color: #555; font-size: 16px;">
                <i class="fas fa-calendar-day" style="color: var(--accent-color);"></i> Histórico Diário (Mais recentes primeiro)
            </h3>
            <span style="font-size: 12px; color: #888;">Ordenado por Data (DESC)</span>
        </div>

        <div class="table-responsive">
            <table class="tabela-detalhada">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Pet / Raça</th>
                        <th>Serviço / Produto</th>
                        <th>Status</th>
                        <th>Valor Cobrado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vendas)): ?>
                        <?php foreach ($vendas as $v): ?>
                            <tr>
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($v['data_venda'])) ?></strong>
                                </td>
                                <td style="color: #888;">#<?= (int)$v['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($v['cliente_nome']) ?></strong>
                                    <?php if (!empty($v['cliente_telefone'])): ?>
                                        <div style="font-size: 12px; color: #888;"><?= htmlspecialchars($v['cliente_telefone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($v['nome_animal'] ?: '-') ?>
                                    <?php if (!empty($v['raca_animal'])): ?>
                                        <span style="font-size: 12px; color: #888;">(<?= htmlspecialchars($v['raca_animal']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($v['servico_nome']) ?></td>
                                <td>
                                    <?php if ($v['tipo'] === 'pago'): ?>
                                        <span class="badge-status badge-pago"><i class="fas fa-check"></i> Pago</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-fiado"><i class="fas fa-clock"></i> Fiado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color: #2c3e50;">R$ <?= number_format((float)$v['valor'], 2, ',', '.') ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: #888;">
                                <i class="fas fa-folder-open" style="font-size: 28px; color: #ccc; display: block; margin-bottom: 10px;"></i>
                                Nenhum lançamento encontrado para <?= $meses_nome[$selected_month] ?> de <?= $selected_year ?>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
