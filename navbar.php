<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Estoque</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">

<style>
/* === MODO ESCURO === */
.dark-mode {
    background-color: #121212;
    color: #f5f5f5;
}

/* Navbar escura */
.dark-mode .navbar {
    background-color: #1c1c1c !important;
}
.dark-mode .nav-link,
.dark-mode .navbar-brand {
    color: #f5f5f5 !important;
}

/* Switch com sol/lua customizados */
.switch-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    margin-left: 1rem;
}
.switch {
    position: relative;
    width: 50px;
    height: 25px;
    background: #ccc;
    border-radius: 25px;
    transition: background 0.3s;
}

/* Bolinha */
.switch::before {
    content: '';
    position: absolute;
    width: 23px;
    height: 23px;
    border-radius: 50%;
    top: 1px;
    left: 1px;
    background: #fff; /* Sol */
    box-shadow: 0 0 0 0 #fff;
    transition: all 0.3s;
}

/* Quando ativo - Lua */
.switch.active {
    background: #4d4d4d;
}
.switch.active::before {
    transform: translateX(25px);
    background: #222; /* Lua escura */
    box-shadow: inset -4px -4px 0 0 #555;
}
/* === MODO ESCURO PARA FORM-CONTROL === */
.dark-mode .form-control {
    background-color: #1a1a1a;
    color: #f5f5f5;
    border: 1px solid #444;
}

.dark-mode .form-control:focus {
    background-color: #2a2a2a;
    color: #f5f5f5;
    border-color: #888;
    box-shadow: 0 0 5px #555;
    outline: none;
}
/* === MODO ESCURO PARA FORM-CONTROL E FORM-SELECT === */
.dark-mode .form-control,
.dark-mode .form-select {
    background-color: #1a1a1a;
    color: #f5f5f5;
    border: 1px solid #444;
}

.dark-mode .form-control:focus,
.dark-mode .form-select:focus {
    background-color: #2a2a2a;
    color: #f5f5f5;
    border-color: #888;
    box-shadow: 0 0 5px #555;
    outline: none;
}
/* === MODO ESCURO PARA CARDS === */
.dark-mode .card {
    background-color: #1e1e1e;
    color: #f5f5f5;
    border: 1px solid #333;
    box-shadow: 0 2px 5px rgba(0,0,0,0.5);
}

.dark-mode .card-header,
.dark-mode .card-footer {
    background-color: #2a2a2a;
    color: #f5f5f5;
    border-bottom: 1px solid #333;
}
/* === Placeholder modo escuro === */
.dark-mode ::placeholder {
    color: #aaa; /* cor clara, visível sobre fundo escuro */
    opacity: 1;  /* garante que fique visível em todos os navegadores */
}

/* Para campos específicos */
.dark-mode input::placeholder,
.dark-mode textarea::placeholder,
.dark-mode select::placeholder {
    color: #aaa;
    opacity: 1;
}
/* Remove outline apenas do dropdown 'Cadastros' */
.navbar .nav-item.dropdown > .nav-link:focus {
    outline: none;
    box-shadow: none;
}
.dark-mode table{
    --bs-table-color: #fff;
    --bs-table-bg: #212529;
    --bs-table-border-color: #4d5154;
    --bs-table-striped-bg: #2c3034;
    --bs-table-striped-color: #fff;
    --bs-table-active-bg: #373b3e;
    --bs-table-active-color: #fff;
    --bs-table-hover-bg: #323539;
    --bs-table-hover-color: #fff;
    color: var(--bs-table-color);
    border-color: var(--bs-table-border-color);
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Dashboard</a>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="relatorios.php">Estoque</a></li>
        <li class="nav-item"><a class="nav-link" href="inventario.php">Inventário</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Cadastros
          </a>
          <ul class="dropdown-menu bg-dark">
            <li class="nav-item"><a class="nav-link" href="itens.php">Itens</a></li>
            <li class="nav-item"><a class="nav-link" href="fornecedores.php">Fornecedores</a></li>
            <li class="nav-item"><a class="nav-link" href="cadastro_categoria.php">Categoria</a></li>  
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="movimentacoes.php">Movimentações</a></li>
        <li class="nav-item"><a class="nav-link" href="ordens_compra.php">Ordens de Compra</a></li>
      </ul>
    </div>

    <!-- Switch Modo Escuro -->
    <div class="switch-wrapper">
      <div class="switch" id="darkModeSwitch" tabindex="0"></div>
    </div>
  </div>
</nav>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
// Dropdown hover
document.addEventListener('DOMContentLoaded', function () {
    const dropdowns = document.querySelectorAll('.nav-item.dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('mouseenter', () => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            bootstrap.Dropdown.getOrCreateInstance(toggle).show();
        });
        dropdown.addEventListener('mouseleave', () => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
        });
    });
});

// Modo escuro com ícone sol/lua
const darkSwitch = document.getElementById('darkModeSwitch');

// Inicializa de acordo com localStorage
if(localStorage.getItem('darkMode') === 'on') {
    document.body.classList.add('dark-mode');
    darkSwitch.classList.add('active');
}

// Ao clicar no switch
darkSwitch.addEventListener('click', () => {
    darkSwitch.classList.toggle('active');
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', darkSwitch.classList.contains('active') ? 'on' : 'off');
});
</script>

</body>
</html>
