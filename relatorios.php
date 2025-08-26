<?php 
include 'conexao.php'; 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Estoque</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<script src="assets/js/chart.min.js"></script>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <h2>Estoque</h2>

    <form method="GET" class="row g-3 mb-3 align-items-end">
        <div class="col-md-4">
            <label for="f_item" class="form-label"><strong>Item</strong></label>
            <input type="text" name="f_item" id="f_item" class="form-control"
                   value="<?= htmlspecialchars($_GET['f_item'] ?? '') ?>" placeholder="Digite parte do nome do item">
        </div>

        <div class="col-md-3">
            <label for="f_status" class="form-label"><strong>Status</strong></label>
            <select name="f_status" id="f_status" class="form-select">
                <option value="">Todos</option>
                <option value="ATIVO" <?= (isset($_GET['f_status']) && $_GET['f_status']=='ATIVO')?'selected':'' ?>>Ativo</option>
                <option value="INATIVO" <?= (isset($_GET['f_status']) && $_GET['f_status']=='INATIVO')?'selected':'' ?>>Inativo</option>
            </select>
        </div>

        <div class="col-md-5 d-flex gap-2 align-items-end">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a href="relatorios.php" class="btn btn-secondary">Limpar</a>
        </div>
    </form>

<?php

$where = [];
$params = [];

if(!empty($_GET['f_item'])){
    $where[] = "i.nome ILIKE ?";
    $params[] = "%" . $_GET['f_item'] . "%";
}

if(!empty($_GET['f_status'])){
    $where[] = "i.status = ?";
    $params[] = $_GET['f_status'];
}

$sql = "
    SELECT i.nome,
           i.status,
           SUM(CASE WHEN m.tipo_movimentacao='entrada' THEN m.quantidade ELSE 0 END) AS total_entrada,
           SUM(CASE WHEN m.tipo_movimentacao='saida' THEN m.quantidade ELSE 0 END) AS total_saida,
           SUM(CASE WHEN m.tipo_movimentacao='entrada' THEN m.valor_total ELSE 0 END) AS gasto_total,
           SUM(CASE WHEN m.tipo_movimentacao='entrada' THEN m.quantidade ELSE 0 END)
           - SUM(CASE WHEN m.tipo_movimentacao='saida' THEN m.quantidade ELSE 0 END) AS qtd_estoque
    FROM itens i
    LEFT JOIN movimentacoes m ON i.id = m.id_item
";

if($where){
    $sql .= " WHERE ".implode(" AND ", $where);
}

$sql .= " GROUP BY i.nome, i.status ORDER BY i.nome";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$relatorios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$entradas = [];
$saidas = [];
$total_qtd_estoque = 0;
$total_valor_estoque = 0;

foreach($relatorios as $r){
    $labels[] = $r['nome'];
    $entradas[] = (int)$r['total_entrada'];
    $saidas[] = (int)$r['total_saida'];
    $total_qtd_estoque += $r['qtd_estoque'];
    $total_valor_estoque += $r['gasto_total'];
}
?>

<h3>Resumo Geral</h3>
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Quantidade Total em Estoque</h5>
                <p class="card-text"><?= $total_qtd_estoque ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Valor Total em Estoque (R$)</h5>
                <p class="card-text"><?= number_format($total_valor_estoque,2,',','.') ?></p>
            </div>
        </div>
    </div>
</div>

<h3>Resumo por Item</h3>
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Item</th>
            <th>Status</th>
            <th>Total Entradas</th>
            <th>Total Saídas</th>
            <th>Quantidade em Estoque</th>
            <th>Valor Total (R$)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($relatorios as $r): ?>
        <tr>
            <td><?= $r['nome'] ?></td>
            <td>
                <?php if($r['status']=='ATIVO'): ?>
                    <span class="badge bg-success">Ativo</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Inativo</span>
                <?php endif; ?>
            </td>
            <td><?= $r['total_entrada'] ?></td>
            <td><?= $r['total_saida'] ?></td>
            <td><?= $r['qtd_estoque'] ?></td>
            <td><?= number_format($r['gasto_total'],2,',','.') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('graficoItens').getContext('2d');
const graficoItens = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            {
                label: 'Entradas',
                data: <?= json_encode($entradas) ?>,
                backgroundColor: '#0d6efd'
            },
            {
                label: 'Saídas',
                data: <?= json_encode($saidas) ?>,
                backgroundColor: '#dc3545'
            }
        ]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>
