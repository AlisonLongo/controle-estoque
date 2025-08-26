<?php
include 'conexao.php';

$categoriasFiltro = $pdo->query("SELECT id, nome FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Estoque</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <h2>Dashboard Interativa</h2>

    <form id="formFiltros" class="row g-3 mb-4">
        <div class="col-md-3">
            <label>Data Início</label>
            <input type="date" name="data_inicio" class="form-control">
        </div>
        <div class="col-md-3">
            <label>Data Fim</label>
            <input type="date" name="data_fim" class="form-control">
        </div>
        <div class="col-md-3">
            <label>Categoria</label>
            <select name="categoria" class="form-select">
                <option value="">Todas</option>
                <?php foreach($categoriasFiltro as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= $cat['nome'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 align-self-end">
            <button type="submit" class="btn btn-primary w-100">Aplicar Filtros</button>
        </div>
    </form>

    <div class="row text-center mb-4" id="cardsResumo">

    </div>

    <div class="row" id="graficos">
        <div class="col-md-6 mb-4">
            <h5>Entradas x Saídas (Total)</h5>
            <canvas id="graficoTotal" height="150"></canvas>
        </div>
        <div class="col-md-6 mb-4">
            <h5>Movimentações Mensais</h5>
            <canvas id="graficoMensal" height="150"></canvas>
        </div>
        <div class="col-md-6 mb-4">
            <h5>Entradas x Saídas por Categoria</h5>
            <canvas id="graficoCategoria" height="150"></canvas>
        </div>
        <div class="col-md-6 mb-4">
            <h5>Top 5 Itens por Valor</h5>
            <canvas id="graficoTopItens" height="150"></canvas>
        </div>

    </div>
</div>

<script>
let chartTotal, chartMensal, chartCategoria, chartTopItens, chartValorCat;

function carregarDashboard(filtros = {}) {
    $.ajax({
        url: 'dados_dashboard.php',
        type: 'GET',
        data: filtros,
        dataType: 'json',
        success: function(data) {
   
            let cardsHtml = `
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white p-2">Itens Cadastrados<h4>${data.totalItens}</h4></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white p-2">Valor Total<h4>R$ ${data.totalValor}</h4></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white p-2">Entradas<h4>${data.entradas}</h4></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-danger text-white p-2">Saídas<h4>${data.saidas}</h4></div>
                </div>
            `;
            $('#cardsResumo').html(cardsHtml);

 
            function criarGrafico(ctx, tipo, labels, datasets, options = {}) {
                if(ctx.chart) ctx.chart.destroy();
                ctx.chart = new Chart(ctx, {type: tipo, data: {labels, datasets}, options});
            }

            criarGrafico(document.getElementById('graficoTotal').getContext('2d'), 'bar',
                ['Entradas','Saídas'], [{label:'Quantidade de Itens', data:[data.entradas,data.saidas], backgroundColor:['#0d6efd','#dc3545']}],
                {responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}
            );

 
criarGrafico(document.getElementById('graficoMensal').getContext('2d'), 'bar',
    data.meses, [
        {label:'Entradas', data:data.entradasMes, backgroundColor:'#0d6efd'},
        {label:'Saídas', data:data.saidasMes, backgroundColor:'#dc3545'}
    ],
    {
        responsive:true,
        plugins: {
            legend: { position: 'top' },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            x: { stacked: false },
            y: { beginAtZero:true }
        }
    }
);

            criarGrafico(document.getElementById('graficoCategoria').getContext('2d'), 'bar',
                data.catNome, [
                    {label:'Entradas', data:data.catEntradas, backgroundColor:'#0d6efd'},
                    {label:'Saídas', data:data.catSaidas, backgroundColor:'#dc3545'}
                ],
                {responsive:true, scales:{y:{beginAtZero:true}}}
            );

            criarGrafico(document.getElementById('graficoTopItens').getContext('2d'), 'bar',
                data.topNomes, [{label:'Valor em Estoque (R$)', data:data.topValores, backgroundColor:'#198754'}],
                {responsive:true, scales:{y:{beginAtZero:true}}}
            );

        }
    });
}

carregarDashboard();

$('#formFiltros').submit(function(e){
    e.preventDefault();
    let filtros = $(this).serialize();
    carregarDashboard(filtros);
});
</script>
</body>
</html>
