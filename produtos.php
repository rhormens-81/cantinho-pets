<?php
require 'config.php';
$pdo = getConnection();

// Ação de Salvar ou Editar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar'])) {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $valor = $_POST['valor'];
    $data_cadastro = date('Y-m-d');

    if ($id) {
        $stmt = $pdo->prepare("UPDATE produtos SET nome=?, valor=? WHERE id=?");
        $stmt->execute([$nome, $valor, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO produtos (nome, valor, data_cadastro) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $valor, $data_cadastro]);
    }
    header("Location: produtos.php");
    exit;
}

// Ação de Deletar
if (isset($_GET['deletar'])) {
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id=?");
    $stmt->execute([$_GET['deletar']]);
    header("Location: produtos.php");
    exit;
}

// Lógica de Busca
$busca = $_GET['busca'] ?? '';
if ($busca) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE nome LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$busca%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM produtos ORDER BY id DESC");
}
$produtos = $stmt->fetchAll();

// Carregar Dados para Edição
$produtoEdit = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id=?");
    $stmt->execute([$_GET['editar']]);
    $produtoEdit = $stmt->fetch();
}

include 'header.php';
?>

<!-- Estilos específicos para responsividade e empilhamento -->
<style>
    .servicos-container {
        display: flex;
        flex-direction: column;
        gap: 30px;
        width: 100%;
        max-width: 100%;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 15px;
        width: 100%;
        align-items: flex-end; /* Alinha o botão com os inputs */
    }
    
    /* Telas pequenas (Celulares) */
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; align-items: flex-start; }
        .form-grid .btn { width: 100%; margin-top: 10px; }
        .acoes-botoes { display: flex; gap: 5px; }
    }

    /* Cabeçalho da tabela seguindo o layout do cliente */
    .tabela-servicos th {
        background-color: #ff85a2 !important;
        color: white !important;
        font-weight: 500;
        white-space: nowrap;
    }
</style>

<div class="servicos-container">

    <!-- SEÇÃO SUPERIOR: FORMULÁRIO DE CADASTRO -->
    <div>
        <h1 class="page-title">
            <i class="fas fa-cut"></i> <?= $produtoEdit ? 'Editar Serviço' : 'Cadastrar Novo Serviço' ?>
        </h1>
        <form method="POST" class="card">
            <input type="hidden" name="id" value="<?= $produtoEdit['id'] ?? '' ?>">
            
            <div class="form-grid">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Nome do Serviço *</label>
                    <input type="text" name="nome" class="form-control" required placeholder="Ex: Tosa, Banho..." value="<?= $produtoEdit['nome'] ?? '' ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Valor (R$) *</label>
                    <input type="number" step="0.01" name="valor" class="form-control" required value="<?= $produtoEdit['valor'] ?? '' ?>">
                </div>
                <div>
                    <button type="submit" name="salvar" class="btn btn-primary" style="width: 100%; height: 46px;">
                        <i class="fas fa-check"></i> <?= $produtoEdit ? 'Atualizar' : 'Cadastrar' ?>
                    </button>
                </div>
            </div>
            
            <?php if($produtoEdit): ?>
                <div style="margin-top: 15px;">
                    <a href="produtos.php" class="btn btn-warning">Cancelar Edição</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- SEÇÃO INFERIOR: LISTA DE SERVIÇOS -->
    <div>
        <h1 class="page-title">
            <i class="fas fa-clipboard-list"></i> Serviços Cadastrados
        </h1>
        <div class="card">
            <!-- Barra de Pesquisa -->
            <form method="GET" class="form-group" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="text" name="busca" class="form-control" placeholder="Buscar por nome do serviço..." value="<?= htmlspecialchars($busca) ?>" style="flex: 1; min-width: 250px;">
                <button type="submit" class="btn btn-primary">Pesquisar</button>
                <?php if($busca): ?>
                    <a href="produtos.php" class="btn btn-warning">Limpar</a>
                <?php endif; ?>
            </form>

            <div class="table-responsive">
                <table class="tabela-servicos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome do Serviço</th>
                            <th>Valor (R$)</th>
                            <th>Data Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($produtos as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['nome']) ?></td>
                            <td><strong style="color: #4caf50;">R$ <?= number_format($p['valor'], 2, ',', '.') ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($p['data_cadastro'])) ?></td>
                            <td class="acoes-botoes" style="min-width: 100px;">
                                <a href="?editar=<?= $p['id'] ?>" class="btn btn-warning" style="padding: 6px 10px; font-size: 12px;" title="Editar"><i class="fas fa-pen"></i></a>
                                <a href="?deletar=<?= $p['id'] ?>" class="btn btn-danger" style="padding: 6px 10px; font-size: 12px;" onclick="return confirm('Deseja realmente excluir este serviço?')" title="Excluir"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($produtos)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #888; padding: 20px;">Nenhum serviço cadastrado ainda.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>