<?php
require 'config.php';
$pdo = getConnection();

// Ação de Salvar ou Editar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar'])) {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];
    $cep = $_POST['cep'];
    $endereco = $_POST['endereco'];
    $bairro = $_POST['bairro'];
    $nome_animal = $_POST['nome_animal'];
    $raca_animal = $_POST['raca_animal'];
    $data_aniversario = !empty($_POST['data_aniversario']) ? $_POST['data_aniversario'] : null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE clientes SET nome=?, telefone=?, cep=?, endereco=?, bairro=?, nome_animal=?, raca_animal=?, data_aniversario=? WHERE id=?");
        $stmt->execute([$nome, $telefone, $cep, $endereco, $bairro, $nome_animal, $raca_animal, $data_aniversario, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, telefone, cep, endereco, bairro, nome_animal, raca_animal, data_aniversario) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $telefone, $cep, $endereco, $bairro, $nome_animal, $raca_animal, $data_aniversario]);
    }
    header("Location: clientes.php");
    exit;
}

// Ação de Deletar
if (isset($_GET['deletar'])) {
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id=?");
    $stmt->execute([$_GET['deletar']]);
    header("Location: clientes.php");
    exit;
}

// Lógica de Busca
$busca = $_GET['busca'] ?? '';
if ($busca) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE nome LIKE ? OR telefone LIKE ? OR nome_animal LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$busca%", "%$busca%", "%$busca%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM clientes ORDER BY id DESC");
}
$clientes = $stmt->fetchAll();

// Carregar Dados para Edição
$clienteEdit = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id=?");
    $stmt->execute([$_GET['editar']]);
    $clienteEdit = $stmt->fetch();
}

include 'header.php';
?>

<!-- Estilos específicos para forçar a responsividade e o empilhamento correto nesta página -->
<style>
    .clientes-container {
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
    }
    
    /* Telas médias (Tablets) */
    @media (max-width: 1200px) {
        .form-grid { grid-template-columns: repeat(2, 1fr); }
    }
    
    /* Telas pequenas (Celulares) */
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .acoes-botoes { display: flex; flex-direction: column; gap: 5px; }
    }

    /* Estilização idêntica à foto de referência */
    .tabela-clientes th {
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

<div class="clientes-container">

    <!-- SEÇÃO SUPERIOR: FORMULÁRIO DE CADASTRO -->
    <div>
        <h1 class="page-title">
            <i class="fas fa-edit"></i> <?= $clienteEdit ? 'Editar Cliente' : 'Cadastrar Novo Cliente' ?>
        </h1>
        <form method="POST" class="card">
            <input type="hidden" name="id" value="<?= $clienteEdit['id'] ?? '' ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nome Completo *</label>
                    <input type="text" name="nome" class="form-control" required value="<?= $clienteEdit['nome'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Celular (DDD) *</label>
                    <input type="text" name="telefone" class="form-control" required placeholder="(00) 00000-0000" value="<?= $clienteEdit['telefone'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Nome do Animal</label>
                    <input type="text" name="nome_animal" class="form-control" value="<?= $clienteEdit['nome_animal'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Raça do Animal</label>
                    <input type="text" name="raca_animal" class="form-control" value="<?= $clienteEdit['raca_animal'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>CEP</label>
                    <input type="text" name="cep" id="cep" class="form-control" onblur="buscaCep()" value="<?= $clienteEdit['cep'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Endereço</label>
                    <input type="text" name="endereco" id="endereco" class="form-control" value="<?= $clienteEdit['endereco'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Bairro</label>
                    <input type="text" name="bairro" id="bairro" class="form-control" value="<?= $clienteEdit['bairro'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Data de Aniversário 🎂</label>
                    <input type="date" name="data_aniversario" class="form-control" value="<?= $clienteEdit['data_aniversario'] ?? '' ?>">
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <button type="submit" name="salvar" class="btn btn-primary">
                    <i class="fas fa-check"></i> <?= $clienteEdit ? 'Atualizar Dados' : 'Cadastrar Cliente' ?>
                </button>
                <?php if($clienteEdit): ?>
                    <a href="clientes.php" class="btn btn-warning" style="margin-left: 10px;">Cancelar Edição</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- SEÇÃO INFERIOR: LISTA DE CLIENTES -->
    <div>
        <h1 class="page-title">
            <i class="fas fa-clipboard-list"></i> Clientes Cadastrados
        </h1>
        <div class="card">
            <form method="GET" class="form-group" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="text" name="busca" class="form-control" placeholder="Buscar por nome, telefone ou nome do animal..." value="<?= htmlspecialchars($busca) ?>" style="flex: 1; min-width: 250px;">
                <button type="submit" class="btn btn-primary">Pesquisar</button>
                <?php if($busca): ?>
                    <a href="clientes.php" class="btn btn-warning">Limpar</a>
                <?php endif; ?>
            </form>
            
            <div class="table-responsive">
                <table class="tabela-clientes">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Celular</th>
                            <th>Animal</th>
                            <th>Raça</th>
                            <th>Bairro</th>
                            <th>Aniversário</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($clientes as $c): 
                            // Formatar data de aniversário
                            $niver = $c['data_aniversario'] ? date('d/m/Y', strtotime($c['data_aniversario'])) : '-';
                            
                            // Limpar telefone para link do WhatsApp funcionar direto (apenas números)
                            $whatsapp_num = preg_replace("/[^0-9]/", "", $c['telefone']);
                        ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td><?= htmlspecialchars($c['nome']) ?></td>
                            <td><?= htmlspecialchars($c['telefone']) ?></td>
                            <td><?= htmlspecialchars($c['nome_animal']) ?></td>
                            <td><?= htmlspecialchars($c['raca_animal']) ?></td>
                            <td><?= htmlspecialchars($c['bairro']) ?></td>
                            <td><?= $niver ?></td>
                            <td class="acoes-botoes" style="min-width: 140px;">
                                <a href="?editar=<?= $c['id'] ?>" class="btn btn-warning" style="padding: 6px 10px; font-size: 12px;" title="Editar"><i class="fas fa-pen"></i></a>
                                
                                <?php if($whatsapp_num): ?>
                                <a href="https://wa.me/55<?= $whatsapp_num ?>" target="_blank" class="btn btn-whatsapp" style="padding: 6px 10px; font-size: 12px;" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                <?php endif; ?>
                                
                                <a href="?deletar=<?= $c['id'] ?>" class="btn btn-danger" style="padding: 6px 10px; font-size: 12px;" onclick="return confirm('Confirma a exclusão deste cliente?')" title="Excluir"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($clientes)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #888; padding: 20px;">Nenhum cliente cadastrado ainda.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function buscaCep() {
    let cep = document.getElementById('cep').value.replace(/\D/g, '');
    if (cep !== "" && /^[0-9]{8}$/.test(cep)) {
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (!data.erro) {
                document.getElementById('endereco').value = data.logradouro;
                document.getElementById('bairro').value = data.bairro;
            } else {
                alert("CEP não encontrado!");
            }
        }).catch(err => console.error(err));
    }
}
</script>

<?php include 'footer.php'; ?>