<?php
require 'config.php';
$pdo = getConnection();

// Salvar Despesa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar'])) {
    $data_despesa = $_POST['data_despesa'];
    $descricao = mb_strtoupper($_POST['descricao'], 'UTF-8');
    $status = $_POST['status'];
    $tipo = $_POST['tipo'];
    $valor = str_replace(',', '.', $_POST['valor']);
    $dono = mb_strtoupper($_POST['dono'], 'UTF-8');
    $fator = $_POST['fator'];

    $stmt = $pdo->prepare("INSERT INTO despesas (data_despesa, descricao, status, tipo, valor, dono, fator) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$data_despesa, $descricao, $status, $tipo, $valor, $dono, $fator]);
    header("Location: home-despesa.php");
    exit;
}

// Deletar
if (isset($_GET['deletar'])) {
    $stmt = $pdo->prepare("DELETE FROM despesas WHERE id=?");
    $stmt->execute([$_GET['deletar']]);
    header("Location: home-despesa.php");
    exit;
}

// Mudar Status (Pago/Pendente)
if (isset($_GET['mudar_status'])) {
    $id = $_GET['mudar_status'];
    $novo_status = $_GET['st'] == 'Pago' ? 'Pendente' : 'Pago';
    $stmt = $pdo->prepare("UPDATE despesas SET status=? WHERE id=?");
    $stmt->execute([$novo_status, $id]);
    header("Location: home-despesa.php");
    exit;
}

// Lista do Mês Atual
$mes_atual = date('m');
$ano_atual = date('Y');
$stmt = $pdo->prepare("SELECT * FROM despesas WHERE MONTH(data_despesa) = ? AND YEAR(data_despesa) = ? ORDER BY data_despesa DESC, id DESC");
$stmt->execute([$mes_atual, $ano_atual]);
$despesas = $stmt->fetchAll();

include 'header.php';
?>

<div style="display: flex; flex-direction: column; gap: 30px; width: 100%;">

    <!-- FORMULÁRIO -->
    <div>
        <h1 class="page-title"><i class="fas fa-file-invoice-dollar"></i> Lançar Despesa (Home-Fabio&Gi)</h1>
        <form method="POST" class="card">
            <div class="row" style="align-items: flex-end;">
                <div class="col form-group" style="min-width: 150px;">
                    <label>Data Vencimento</label>
                    <input type="date" name="data_despesa" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="col form-group" style="flex: 2; min-width: 200px;">
                    <label>Descrição (Selecione ou Digite)</label>
                    <input list="listaDescricao" name="descricao" class="form-control" required placeholder="Ex: CONTA DE LUZ">
                    <datalist id="listaDescricao">
                        <option value="AGUA">
                        <option value="LUZ">
                        <option value="ALUGUEL CASA">
                        <option value="FATURA CARTÃO">
                        <option value="INTERNET">
                        <option value="MERCADO">
                        <option value="AÇOUGUE">
                        <option value="REMEDIO">
                    </datalist>
                </div>
                
                <div class="col form-group">
                    <label>Valor (R$)</label>
                    <input type="number" step="0.01" name="valor" class="form-control" required>
                </div>
                
                <div class="col form-group">
                    <label>Dono (Selecione ou Digite)</label>
                    <input list="listaDono" name="dono" class="form-control" required placeholder="Ex: Home">
                    <datalist id="listaDono">
                        <option value="HOME">
                        <option value="PET">
                        <option value="FABIO">
                        <option value="GI">
                    </datalist>
                </div>
            </div>

            <div class="row">
                <div class="col form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="Pendente">Falta Pagar (Pendente)</option>
                        <option value="Pago">Já Pago</option>
                    </select>
                </div>
                <div class="col form-group">
                    <label>Tipo</label>
                    <select name="tipo" class="form-control">
                        <option value="essencial">Essencial</option>
                        <option value="não essencial">Não Essencial</option>
                    </select>
                </div>
                <div class="col form-group">
                    <label>Fator</label>
                    <select name="fator" class="form-control">
                        <option value="fixa">Fixa</option>
                        <option value="fixa-cartão">Fixa-Cartão</option>
                        <option value="fatura">Fatura</option>
                        <option value="variável">Variável</option>
                    </select>
                </div>
                <div class="col form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" name="salvar" class="btn btn-primary" style="width: 100%; height: 46px;">Lançar Despesa</button>
                </div>
            </div>
        </form>
    </div>

    <!-- TABELA -->
    <div class="card">
        <h3 style="margin-bottom: 15px; color: #555;">Extrato do Mês Atual (<?= date('m/Y') ?>)</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Dia</th>
                        <th>Descrição</th>
                        <th>Status</th>
                        <th>Tipo</th>
                        <th>Valor Total</th>
                        <th>Dono</th>
                        <th>Fator</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($despesas as $d): ?>
                    <tr>
                        <td><strong><?= date('d', strtotime($d['data_despesa'])) ?></strong></td>
                        <td><?= htmlspecialchars($d['descricao']) ?></td>
                        <td>
                            <a href="?mudar_status=<?= $d['id'] ?>&st=<?= $d['status'] ?>" 
                               style="text-decoration:none; padding: 4px 8px; border-radius: 4px; font-size:12px; color:#fff; background: <?= $d['status']=='Pago' ? '#4caf50' : '#e57373' ?>">
                               <?= $d['status'] ?>
                            </a>
                        </td>
                        <td><?= $d['tipo'] ?></td>
                        <td><strong>R$ <?= number_format($d['valor'], 2, ',', '.') ?></strong></td>
                        <td><?= $d['dono'] ?></td>
                        <td><?= $d['fator'] ?></td>
                        <td>
                            <a href="?deletar=<?= $d['id'] ?>" onclick="return confirm('Excluir lançamento?')" style="color: #e57373;"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
