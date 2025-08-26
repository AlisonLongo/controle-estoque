<?php
include 'conexao.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$msg = "";

if(isset($_POST['aprovar'])){
    $inventario_id = $_POST['inventario_id'] ?? 0;
    if($inventario_id){

        $pdo->prepare("UPDATE inventarios SET status='APROVADO' WHERE id=?")
            ->execute([$inventario_id]);

        $stmtItens = $pdo->prepare("
            SELECT id_item, contagem_atual, valor_medio_atual 
            FROM inventario_itens 
            WHERE id_inventario=?
        ");
        $stmtItens->execute([$inventario_id]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        $stmtMov = $pdo->prepare("
            INSERT INTO movimentacoes (id_item, tipo_movimentacao, quantidade, valor_total, data_movimentacao, observacao, id_ordem)
            VALUES (?, 'entrada', ?, ?, NOW(), 'IN', NULL)
        ");

        foreach($itens as $item){
            $quantidade = (int)$item['contagem_atual'];
            if($quantidade > 0){
                $valorTotal = (float)$item['valor_medio_atual'] * $quantidade;

                $stmtMov->execute([
                    $item['id_item'],
                    $quantidade,
                    $valorTotal
                ]);

                $pdo->prepare("
                    UPDATE itens 
                    SET quantidade_atual = ?, 
                        valor_unitario = ?
                    WHERE id = ?
                ")->execute([
                    $quantidade,
                    $item['valor_medio_atual'],
                    $item['id_item']
                ]);
            }
        }

        $msg = "✅ Inventário $inventario_id aprovado, movimentações e estoque atualizados!";
    }
}



if(isset($_POST['excluir'])){
    $inventario_id = $_POST['inventario_id'] ?? 0;
    if($inventario_id){

        $pdo->prepare("DELETE FROM inventario_itens WHERE id_inventario=?")->execute([$inventario_id]);

        $pdo->prepare("DELETE FROM inventarios WHERE id=?")->execute([$inventario_id]);
        $msg = "❌ Inventário $inventario_id excluído com sucesso!";
    }
}

$stmt = $pdo->prepare("SELECT * FROM inventarios WHERE status='AGUARDANDO APROVAÇÃO' ORDER BY criado_em DESC");
$stmt->execute();
$inventarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Aprovação de Inventários</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <h2>Aprovação de Inventários</h2>

    <?php if($msg) echo "<div class='alert alert-info'>$msg</div>"; ?>

    <?php if(count($inventarios) > 0): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data Realizado</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($inventarios as $inv): ?>
                    <tr>
                        <td><?=$inv['id']?></td>
                        <td><?=date('d/m/Y', strtotime($inv['data_realizado']))?></td>
                        <td><?=$inv['status']?></td>
                        <td>
                            <form method="post" style="display:inline-block;">
                                <input type="hidden" name="inventario_id" value="<?=$inv['id']?>">
                                <button type="submit" name="aprovar" class="btn btn-success btn-sm">Aprovar</button>
                            </form>

                            <a href="inventario_contagem.php?id=<?=$inv['id']?>" class="btn btn-primary btn-sm">Ver Contagem</a>

                            <form method="post" style="display:inline-block;" onsubmit="return confirm('Deseja realmente excluir este inventário?');">
                                <input type="hidden" name="inventario_id" value="<?=$inv['id']?>">
                                <button type="submit" name="excluir" class="btn btn-danger btn-sm">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">Não há inventários pendentes de aprovação.</div>
    <?php endif; ?>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
