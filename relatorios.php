<?php 
include 'conexao.php'; 

// Buscar todos os itens ativos
$itens = $pdo->query("SELECT id, nome FROM itens WHERE status='ATIVO' ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Estoque</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <h2>Estoque</h2>

    <form method="GET" class="row g-3 mb-3 align-items-end">
        <div class="col-md-4">
            <label for="f_item" class="form-label"><strong>Item</strong></label>
            <select name="f_item" id="f_item" class="form-select">
                <option value="">Todos os itens</option>
                <?php foreach($itens as $i): ?>
                    <option value="<?= $i['id'] ?>" <?= (isset($_GET['f_item']) && $_GET['f_item']==$i['id'])?'selected':'' ?>>
                        <?= $i['nome'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
    $where[] = "id = ?";
    $params[] = $_GET['f_item'];
}

if(!empty($_GET['f_status'])){
    $where[] = "status = ?";
    $params[] = $_GET['f_status'];
}

$sql = "SELECT id, nome, status, quantidade_atual, valor_unitario FROM itens";

if($where){
    $sql .= " WHERE ".implode(" AND ", $where);
}

$sql .= " ORDER BY nome";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$relatorios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_qtd_estoque = 0;
$total_valor_estoque = 0;

foreach($relatorios as $r){
    $total_qtd_estoque += $r['quantidade_atual'];
    $total_valor_estoque += $r['quantidade_atual'] * $r['valor_unitario'];
}
?>

<h3>Resumo Geral</h3>
<div class="row mb-3">
    <div class="col-md-6">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Quantidade Total em Estoque</h5>
                <p class="card-text"><?= $total_qtd_estoque ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
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
            <th>Quantidade em Estoque</th>
            <th>Valor Médio Unitário (R$)</th>
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
            <td><?= $r['quantidade_atual'] ?></td>
            <td><?= number_format($r['valor_unitario'],2,',','.') ?></td>
            <td><?= number_format($r['quantidade_atual'] * $r['valor_unitario'],2,',','.') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
