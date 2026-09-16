<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema - Cantinho Pets & Home</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <button class="toggle-btn" id="toggleBtn">
            <i class="fas fa-chevron-left"></i>
        </button>

        <div class="user-profile">
            <img src="https://images.unsplash.com/photo-1543466835-00a7907e9de1?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80" alt="Perfil Dog" class="profile-img">
            <div class="user-info">
                <p class="user-role">Administrador</p>
                <h4 class="user-name">Cantinho Pets</h4>
            </div>
        </div>

        <div class="menu-items">
            <!-- Módulo Pet -->
            <p style="color: #ffd6e0; font-size: 11px; margin: 10px 15px 5px; text-transform: uppercase;">Módulo Pet</p>
            <a href="index.php" title="Dashboard Pet">
                <i class="fas fa-th-large"></i> <span class="menu-text">Dash Pet</span>
            </a>
            <a href="clientes.php" title="Cadastro de Clientes">
                <i class="fas fa-users"></i> <span class="menu-text">Clientes</span>
            </a>
            <a href="produtos.php" title="Cadastro de Serviços">
                <i class="fas fa-cut"></i> <span class="menu-text">Serviços</span>
            </a>
            <a href="pdv.php" title="PDV / Fiados">
                <i class="fas fa-cash-register"></i> <span class="menu-text">PDV / Fiados</span>
            </a>

            <!-- Módulo Casa -->
            <p style="color: #ffd6e0; font-size: 11px; margin: 20px 15px 5px; text-transform: uppercase;">Home-Fabio&Gi</p>
            <a href="dash-despesa.php" title="Dashboard Despesas">
                <i class="fas fa-chart-pie"></i> <span class="menu-text">Dash Despesas</span>
            </a>
            <a href="home-despesa.php" title="Lançar Despesas">
                <i class="fas fa-file-invoice-dollar"></i> <span class="menu-text">Lançamentos</span>
            </a>
        </div>
    </div>
    <div class="main-content" id="mainContent">
        