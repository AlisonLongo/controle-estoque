<?php
include 'conexao.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$inventario_id = $_GET['id'] ?? 0;
$msg = $_GET['msg'] ?? "";

$stmt = $pdo->prepare("SELECT * FROM inventarios WHERE id=?");
$stmt->execute([$inventario_id]);
$inventario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inventario) {
    die("<div class='alert alert-danger'>Inventário não encontrado!</div>");
}

$stmtItens = $pdo->prepare("
    SELECT ii.id_item, i.nome,
           ii.estoque_sistema,
           ii.valor_medio_sistema,
           COALESCE(ii.contagem_atual, 0) AS contagem_atual,
           COALESCE(ii.valor_medio_atual, 0) AS valor_medio_atual
    FROM inventario_itens ii
    JOIN itens i ON i.id = ii.id_item
    WHERE ii.id_inventario = ?
");
$stmtItens->execute([$inventario_id]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Aprovação de Inventário</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">

    <h2>Inventário <?=$inventario_id?> - Aprovação</h2>

    <?php if($msg) echo "<div class='alert alert-info'>$msg</div>"; ?>

    <h4>Status: <span class="badge bg-secondary"><?=$inventario['status']?></span></h4>

    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>Item</th>
                <th>Estoque Sistema</th>
                <th>Qtd Contagem</th>
                <th>Valor Médio Sistema</th>
                <th>Valor Médio Contagem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($itens as $item): ?>
                <tr class="<?=($item['estoque_sistema'] != $item['contagem_atual']) ? 'table-warning' : ''?>">
                    <td><?=$item['nome']?></td>
                    <td><?=$item['estoque_sistema']?></td>
                    <td><?=$item['contagem_atual']?></td>
                    <td><?=number_format($item['valor_medio_sistema'],2,',','.')?></td>
                    <td><?=number_format($item['valor_medio_atual'],2,',','.')?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <form method="post" action="inventario_aprovacao_action.php">
        <input type="hidden" name="inventario_id" value="<?=$inventario_id?>">

        <?php if($inventario['status'] == 'AGUARDANDO APROVAÇÃO'): ?>
            <button type="submit" name="aprovar" class="btn btn-success">✅ Aprovar Inventário</button>
            <button type="submit" name="reprovar" class="btn btn-danger">❌ Reprovar Inventário</button>
        <?php else: ?>
            <div class="alert alert-info mt-3">Este inventário já foi <?=$inventario['status']?>.</div>
            <a href="inventario.php" class="btn btn-secondary mt-2">⬅ Voltar</a>
        <?php endif; ?>
    </form>

</div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
