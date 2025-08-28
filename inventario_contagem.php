<?php
include 'conexao.php';
$inventario_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM inventarios WHERE id=?");
$stmt->execute([$inventario_id]);
$inventario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inventario) die("Inventário não encontrado!");

$stmtItens = $pdo->prepare("
    SELECT ii.*, i.nome
    FROM inventario_itens ii
    JOIN itens i ON i.id = ii.id_item
    WHERE ii.id_inventario = ?
");
$stmtItens->execute([$inventario_id]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Contagem Inventário</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-4">
        <h2>Inventário <?= $inventario_id ?> - Contagem</h2>
        <h5>Status: <span class="badge bg-secondary"><?= $inventario['status'] ?></span></h5>

        <form method="post" action="inventario_contagem_action.php">
            <input type="hidden" name="inventario_id" value="<?= $inventario_id ?>">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Estoque Sistema</th>
                        <th>Valor Médio Sistema</th>
                        <th>Qtd Contagem</th>
                        <th>Valor Médio Contagem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td><?= $item['nome'] ?></td>
                            <td><?= $item['estoque_sistema'] ?></td>
                            <td><?= number_format($item['valor_medio_sistema'], 2, ',', '.') ?></td>
                            <td><input type="number" name="itens[<?= $item['id_item'] ?>][qtd]" value="<?= $item['contagem_atual'] ?>" class="form-control"></td>
                            <td><input type="number" step="0.01" name="itens[<?= $item['id_item'] ?>][valor]" value="<?= $item['valor_medio_atual'] ?>" class="form-control"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-between mt-3">
                <a href="inventario.php" class="btn btn-secondary">⬅ Voltar</a>
                <div>
                    <button name="salvar" class="btn btn-success">Salvar</button>
                    <button name="aprovar" class="btn btn-warning">Enviar para Aprovação</button>
        </form>

        <!-- Botão Voltar -->

    </div>
</body>

</html>