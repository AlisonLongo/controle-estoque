<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Estoque</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">

  <style>

.navbar-dark .dropdown-menu {
    background-color: #000000ff; 
    border: none;
}
.navbar-dark .dropdown-menu .dropdown-item {
    color: #fff;
}
.navbar-dark .dropdown-menu .dropdown-item:hover,
.navbar-dark .dropdown-menu .dropdown-item:focus {
    background-color: #000000ff;
    color: #fff;
}

.navbar-dark .dropdown-toggle:focus {
    outline: none;
    box-shadow: none;
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

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button"
             data-bs-toggle="dropdown" aria-expanded="false">
            Inventário
          </a>
          <ul class="dropdown-menu bg-dark">
            <li><a class="dropdown-item text-white" href="inventario.php">Inventário</a></li>
            <li><a class="dropdown-item text-white" href="inventario_aprovacao.php">Aprovação</a></li>
          </ul>
        </li>


          <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button"
             data-bs-toggle="dropdown" aria-expanded="false">
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
  </div>
</nav>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropdowns = document.querySelectorAll('.nav-item.dropdown');

    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('mouseenter', () => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = bootstrap.Dropdown.getOrCreateInstance(toggle);
            menu.show();
        });
        dropdown.addEventListener('mouseleave', () => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = bootstrap.Dropdown.getOrCreateInstance(toggle);
            menu.hide();
        });
    });
});
</script>