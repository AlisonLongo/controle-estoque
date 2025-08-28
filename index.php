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
    <style>
canvas {
    max-height: 350px; /* altura maior */
    height: 350px;
}
</style>

</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <h2>Dashboard Interativo</h2>

    <!-- FILTROS -->
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

    <!-- CARDS RESUMO -->
    <div class="row text-center mb-4" id="cardsResumo"></div>

    <!-- GRÁFICOS ORGANIZADOS -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <h5 class="text-center">Entradas x Saídas</h5>
            <canvas id="graficoTotal"></canvas>
        </div>
        <div class="col-md-4 mb-4">
            <h5 class="text-center">Movimentações Mensais</h5>
            <canvas id="graficoMensal"></canvas>
        </div>
        <div class="col-md-4 mb-4">
            <h5 class="text-center">Entradas x Saídas por Categoria</h5>
            <canvas id="graficoCategoria"></canvas>
        </div>

        <div class="col-md-4 mb-4">
            <h5 class="text-center">Top 5 Itens por Valor</h5>
            <canvas id="graficoTopItens"></canvas>
        </div>
        <div class="col-md-4 mb-4">
            <h5 class="text-center">Valor Total Mensal</h5>
            <canvas id="graficoValorMes"></canvas>
        </div>
        <div class="col-md-4 mb-4">
            <h5 class="text-center">Radar Entradas x Saídas por Categoria</h5>
            <canvas id="graficoRadarCategoria"></canvas>
        </div>
    </div>
</div>

<script>
let chartTotal, chartMensal, chartCategoria, chartTopItens, chartValorMes, chartRadarCategoria;

function carregarDashboard(filtros = {}) {
    $.ajax({
        url: 'dados_dashboard.php',
        type: 'GET',
        data: filtros,
        dataType: 'json',
        success: function(data) {

            // === CARDS RESUMO ===
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

            // === FUNÇÃO PARA CRIAR GRÁFICOS ===
            function criarGrafico(ctx, tipo, labels, datasets, options = {}) {
                if(ctx.chart) ctx.chart.destroy();
                ctx.chart = new Chart(ctx, {type: tipo, data: {labels, datasets}, options});
            }

            // === 1. Entradas x Saídas (Doughnut) ===
            criarGrafico(document.getElementById('graficoTotal').getContext('2d'), 'doughnut', 
                ['Entradas','Saídas'], 
                [{
                    label:'Quantidade de Itens', 
                    data:[data.entradas, data.saidas], 
                    backgroundColor:['#0d6efd','#dc3545'],
                    hoverOffset: 10
                }],
                {
                    responsive:true,
                    maintainAspectRatio: true,
                    plugins:{ legend:{ position:'bottom' } }
                }
            );

            // === 2. Movimentações Mensais (Bar Empilhado) ===
            criarGrafico(document.getElementById('graficoMensal').getContext('2d'), 'bar',
                data.meses, [
                    {label:'Entradas', data:data.entradasMes, backgroundColor:'#0d6efd'},
                    {label:'Saídas', data:data.saidasMes, backgroundColor:'#dc3545'}
                ],
                {
                    indexAxis: 'x',
                    responsive:true,
                    maintainAspectRatio: true,
                    plugins:{ legend:{ position:'top' } },
                    scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true } }
                }
            );

            // === 3. Entradas x Saídas por Categoria (Bar Horizontal) ===
            criarGrafico(document.getElementById('graficoCategoria').getContext('2d'), 'bar',
                data.catNome, [
                    {label:'Entradas', data:data.catEntradas, backgroundColor:'#0d6efd'},
                    {label:'Saídas', data:data.catSaidas, backgroundColor:'#dc3545'}
                ],
                {
                    indexAxis: 'y',
                    responsive:true,
                    maintainAspectRatio: true,
                    scales:{ x:{ beginAtZero:true } },
                    plugins:{ legend:{ position:'top' } }
                }
            );

            // === 4. Top 5 Itens por Valor (Bar Horizontal) ===
            criarGrafico(document.getElementById('graficoTopItens').getContext('2d'), 'bar',
                data.topNomes, [{label:'Valor em Estoque (R$)', data:data.topValores, backgroundColor:'#198754'}],
                {
                    indexAxis: 'y',
                    responsive:true,
                    maintainAspectRatio: true,
                    scales:{ x:{ beginAtZero:true } },
                    plugins:{ legend:{ display:false } }
                }
            );

            // === 5. Valor Total Mensal (Linha) ===
            criarGrafico(document.getElementById('graficoValorMes').getContext('2d'), 'line',
                data.meses, [
                    {label:'Entradas', data:data.entradasMes, borderColor:'#0d6efd', backgroundColor:'rgba(13,110,253,0.2)', fill:true},
                    {label:'Saídas', data:data.saidasMes, borderColor:'#dc3545', backgroundColor:'rgba(220,53,69,0.2)', fill:true}
                ],
                {
                    responsive:true,
                    maintainAspectRatio: true,
                    plugins:{ legend:{ position:'top' } },
                    scales:{ y:{ beginAtZero:true } }
                }
            );

            // === 6. Entradas x Saídas por Categoria (Barras Verticais) ===
criarGrafico(document.getElementById('graficoRadarCategoria').getContext('2d'), 'bar',
    data.catNome, [
        {label:'Entradas', data:data.catEntradas, backgroundColor:'#0d6efd'},
        {label:'Saídas', data:data.catSaidas, backgroundColor:'#dc3545'}
    ],
    {
        indexAxis: 'x', // barras em pé
        responsive:true,
        maintainAspectRatio: true,
        scales:{ y:{ beginAtZero:true } },
        plugins:{ legend:{ position:'top' } }
    }
);


        }
    });
}

// Carrega dashboard ao abrir
carregarDashboard();

// Aplica filtros
$('#formFiltros').submit(function(e){
    e.preventDefault();
    let filtros = $(this).serialize();
    carregarDashboard(filtros);
});
</script>
