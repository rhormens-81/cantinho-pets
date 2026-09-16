<?php
require 'config.php';
$pdo = getConnection();

// Converter fiado para pago
if (isset($_GET['pagar_fiado'])) {
    $stmt = $pdo->prepare("UPDATE vendas SET tipo='pago' WHERE id=?");
    $stmt->execute([$_GET['pagar_fiado']]);
    header("Location: pdv.php");
    exit;
}

// Lançamento de Venda
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['lancar'])) {
    $cliente_id = $_POST['cliente_id'];
    $produto_id = $_POST['produto_id'];
    $valor = $_POST['valor'];
    $tipo = $_POST['tipo'];
    $data_venda = date('Y-m-d');

    $stmt = $pdo->prepare("INSERT INTO vendas (cliente_id, produto_id, valor, tipo, data_venda) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$cliente_id, $produto_id, $valor, $tipo, $data_venda]);
    header("Location: pdv.php");
    exit;
}

// Consultas para popular selects de lançamento
$clientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome")->fetchAll();
$produtos = $pdo->query("SELECT id, nome, valor FROM produtos ORDER BY nome")->fetchAll();

// Lógica de Busca para Fiados
$busca = $_GET['busca'] ?? '';
$queryFiados = "SELECT v.id, c.nome as cliente, c.telefone, p.nome as produto, v.valor, v.data_venda 
                FROM vendas v 
                JOIN clientes c ON v.cliente_id = c.id 
                JOIN produtos p ON v.produto_id = p.id 
                WHERE v.tipo = 'fiado'";

$params = [];
if ($busca) {
    $queryFiados .= " AND (c.nome LIKE ? OR p.nome LIKE ?)";
    $params = ["%$busca%", "%$busca%"];
}
$queryFiados .= " ORDER BY v.data_venda DESC";

$stmtFiados = $pdo->prepare($queryFiados);
$stmtFiados->execute($params);
$fiados = $stmtFiados->fetchAll();

include 'header.php';
?>

<!-- Estilos específicos para responsividade e empilhamento -->
<style>
    .pdv-container {
        display: flex;
        flex-direction: column;
        gap: 30px;
        width: 100%;
        max-width: 100%;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        width: 100%;
        align-items: flex-end;
    }
    
    /* Telas médias (Tablets) */
    @media (max-width: 1200px) {
        .form-grid { grid-template-columns: repeat(2, 1fr); }
    }
    
    /* Telas pequenas (Celulares) */
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; align-items: flex-start; }
        .acoes-botoes { display: flex; gap: 5px; }
    }

    /* Cabeçalho da tabela seguindo o layout do cliente */
    .tabela-fiados th {
        background-color: #ff85a2 !important;
        color: white !important;
        font-weight: 500;
        white-space: nowrap;
    }

    .btn-whatsapp {
        background-color: #25D366;
        color: white;
    }
    .btn-whatsapp:hover {
        background-color: #1ebe57;
    }
</style>

<div class="pdv-container">

    <!-- SEÇÃO SUPERIOR: FORMULÁRIO DE LANÇAMENTO (PDV) -->
    <div>
        <h1 class="page-title">
            <i class="fas fa-cash-register"></i> PDV - Lançar Venda
        </h1>
        <form method="POST" class="card">
            <div class="form-grid">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Cliente *</label>
                    <select name="cliente_id" class="form-control" required>
                        <option value="">Selecione o Cliente...</option>
                        <?php foreach($clientes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Serviço / Produto *</label>
                    <select name="produto_id" id="produto_id" class="form-control" required onchange="atualizaValor()">
                        <option value="" data-valor="0">Selecione o Serviço...</option>
                        <?php foreach($produtos as $p): ?>
                            <option value="<?= $p['id'] ?>" data-valor="<?= $p['valor'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Valor Cobrado (R$) *</label>
                    <input type="number" step="0.01" name="valor" id="valor" class="form-control" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Status do Pagamento *</label>
                    <select name="tipo" class="form-control">
                        <option value="pago">Pago Agora</option>
                        <option value="fiado">Fiado (Ficar Devendo)</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <button type="submit" name="lancar" class="btn btn-primary">
                    <i class="fas fa-check-circle"></i> Lançar Venda no Sistema
                </button>
            </div>
        </form>
    </div>

    <!-- SEÇÃO INFERIOR: LISTA DE FIADOS -->
    <div>
        <h1 class="page-title" style="color: #e57373;">
            <i class="fas fa-exclamation-triangle"></i> Gestão de Fiados (Valores a Receber)
        </h1>
        <div class="card">
            <!-- Barra de Pesquisa -->
            <form method="GET" class="form-group" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="text" name="busca" class="form-control" placeholder="Buscar por nome do cliente ou serviço..." value="<?= htmlspecialchars($busca) ?>" style="flex: 1; min-width: 250px;">
                <button type="submit" class="btn btn-primary">Pesquisar</button>
                <?php if($busca): ?>
                    <a href="pdv.php" class="btn btn-warning">Limpar</a>
                <?php endif; ?>
            </form>

            <div class="table-responsive">
                <table class="tabela-fiados">
                    <thead>
                        <tr>
                            <th>Data da Venda</th>
                            <th>Cliente</th>
                            <th>Serviço/Produto</th>
                            <th>Valor Devido</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($fiados as $f): 
                            // Limpar telefone para o link do WhatsApp (apenas números)
                            $whatsapp_num = preg_replace("/[^0-9]/", "", $f['telefone'] ?? '');
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($f['data_venda'])) ?></td>
                            <td><strong><?= htmlspecialchars($f['cliente']) ?></strong></td>
                            <td><?= htmlspecialchars($f['produto']) ?></td>
                            <td><strong style="color: #e57373;">R$ <?= number_format($f['valor'], 2, ',', '.') ?></strong></td>
                            <td class="acoes-botoes" style="min-width: 150px;">
                                <a href="?pagar_fiado=<?= $f['id'] ?>" class="btn btn-warning" style="padding: 6px 10px; font-size: 12px;" onclick="return confirm('Confirmar que o cliente pagou este valor?')" title="Marcar como Pago">
                                    <i class="fas fa-hand-holding-usd"></i> Pagar
                                </a>
                                
                                <?php if($whatsapp_num): ?>
                                <a href="https://wa.me/55<?= $whatsapp_num ?>?text=Olá,<?= urlencode(' tudo bem? Consta em nosso sistema um valor em aberto de R$ '.number_format($f['valor'], 2, ',', '.').' referente ao serviço de '.$f['produto'].'. Poderia verificar por gentileza?') ?>" target="_blank" class="btn btn-whatsapp" style="padding: 6px 10px; font-size: 12px;" title="Cobrar via WhatsApp">
                                    <i class="fab fa-whatsapp"></i> Cobrar
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($fiados)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #888; padding: 20px;">
                                <i class="fas fa-smile" style="font-size: 24px; color: #ff85a2; display: block; margin-bottom: 10px;"></i>
                                Nenhum cliente está devendo no momento! 🎉
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Script para buscar automaticamente o valor do serviço selecionado no dropdown
function atualizaValor() {
    let select = document.getElementById('produto_id');
    let valor = select.options[select.selectedIndex].getAttribute('data-valor');
    document.getElementById('valor').value = valor;
}
</script>

<?php include 'footer.php'; ?>