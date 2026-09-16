<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cantinho Pets - Sistema</title>
    <!-- CSS Principal -->
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- FontAwesome para os ícones do menu -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <!-- Botão de Recolher/Expandir -->
        <button class="toggle-btn" id="toggleBtn">
            <i class="fas fa-chevron-left"></i>
        </button>

        <!-- Perfil (Imagem do Dog) -->
        <div class="user-profile">
            <img src="https://images.unsplash.com/photo-1543466835-00a7907e9de1?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80" alt="Perfil Dog" class="profile-img">
            <div class="user-info">
                <p class="user-role">Administrador</p>
                <h4 class="user-name">Cantinho Pets</h4>
            </div>
        </div>

        <!-- Links do Menu -->
        <div class="menu-items">
            <a href="index.php" title="Dashboard">
                <i class="fas fa-th-large"></i> 
                <span class="menu-text">Dashboard</span>
            </a>
            <a href="clientes.php" title="Cadastro de Clientes">
                <i class="fas fa-users"></i> 
                <span class="menu-text">Clientes</span>
            </a>
            <a href="produtos.php" title="Cadastro de Serviços">
                <i class="fas fa-cut"></i> 
                <span class="menu-text">Serviços</span>
            </a>
            <a href="pdv.php" title="PDV / Fiados">
                <i class="fas fa-cash-register"></i> 
                <span class="menu-text">PDV / Fiados</span>
            </a>
        </div>
    </div>
    <div class="main-content" id="mainContent"></div>