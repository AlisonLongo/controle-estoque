<?php
include 'conexao.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$msg = "";
$inventario_id = $_GET['id'] ?? 0;

if(isset($_POST['salvar'])){
    $inventario_id = $_POST['inventario_id'] ?? 0;
    if(!empty($_POST['itens'])){
        $stmtUpdate = $pdo->prepare("
            UPDATE inventario_itens
            SET contagem_atual = ?, valor_medio_atual = ?, status='CONFERENCIA'
            WHERE id_inventario = ? AND id_item = ?
        ");
        foreach($_POST['itens'] as $item_id => $dados){
            $stmtUpdate->execute([
                $dados['qtd'],
                $dados['valor'],
                $inventario_id,
                $item_id
            ]);
        }
        $pdo->prepare("UPDATE inventarios SET status='CONFERENCIA' WHERE id=?")->execute([$inventario_id]);
        $msg = "✅ Inventário salvo e enviado para conferência.";
    } else {
        $msg = "❌ Nenhum item para salvar.";
    }
}

if(isset($_POST['aprovar'])){
    $inventario_id = $_POST['inventario_id'] ?? 0;
    $status_atual = $_POST['status_atual'] ?? '';

    if($status_atual == 'AGUARDANDO APROVAÇÃO'){

        $stmtItens = $pdo->prepare("
            SELECT id_item, contagem_atual, valor_medio_atual 
            FROM inventario_itens 
            WHERE id_inventario=?
        ");
        $stmtItens->execute([$inventario_id]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        $stmtMov = $pdo->prepare("
            INSERT INTO movimentacoes (id_item, tipo_movimentacao, quantidade, valor_total, observacao, id_ordem)
            VALUES (?, 'entrada', ?, ?, 'IN', NULL)
        ");

        foreach($itens as $item){
            if($item['contagem_atual'] > 0){
                $valorTotal = $item['valor_medio_atual'] * $item['contagem_atual'];
                $stmtMov->execute([
                    $item['id_item'],
                    $item['contagem_atual'],
                    $valorTotal
                ]);
            }
        }

        $pdo->prepare("UPDATE inventarios SET status='APROVADO' WHERE id=?")->execute([$inventario_id]);
        $msg = "✅ Inventário aprovado e movimentações registradas!";
    } else {

        $pdo->prepare("UPDATE inventarios SET status='AGUARDANDO APROVAÇÃO' WHERE id=?")->execute([$inventario_id]);

        header("Location: inventario_aprovacao.php");
        exit;
    }
}

if(isset($_POST['cancelar'])){
    $inventario_id = $_POST['inventario_id'] ?? 0;

    $pdo->prepare("DELETE FROM inventario_itens WHERE id_inventario=?")->execute([$inventario_id]);
    $pdo->prepare("DELETE FROM inventarios WHERE id=?")->execute([$inventario_id]);
    $msg = "❌ Inventário $inventario_id cancelado e excluído.";
}

$stmt = $pdo->prepare("SELECT * FROM inventarios WHERE id=?");
$stmt->execute([$inventario_id]);
$inventario = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$inventario){
    die("<div class='alert alert-danger'>Inventário não encontrado!</div>");
}

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
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Contagem do Inventário</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <h2>Contagem do Inventário <?=$inventario_id?></h2>

    <?php if($msg) echo "<div class='alert alert-info'>$msg</div>"; ?>

    <h4>Status: <span class="badge bg-secondary"><?=$inventario['status']?></span></h4>

    <form method="post" class="card p-3 shadow-sm">
        <input type="hidden" name="inventario_id" value="<?=$inventario_id?>">
        <input type="hidden" name="status_atual" value="<?=$inventario['status']?>">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Estoque (Sistema)</th>
                    <th>Valor Médio (Sistema)</th>
                    <th>Qtd Contagem</th>
                    <th>Valor Médio Contagem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($itens as $item): ?>
                    <tr>
                        <td><?=$item['nome']?></td>
                        <td><input type="text" class="form-control" value="<?=$item['estoque_sistema']?>" disabled></td>
                        <td><input type="text" class="form-control" value="<?=number_format($item['valor_medio_sistema'],2,',','.')?>" disabled></td>
                        <td><input type="number" step="0.01" name="itens[<?=$item['id_item']?>][qtd]" class="form-control" value="<?=$item['contagem_atual']?>" required></td>
                        <td><input type="number" step="0.01" name="itens[<?=$item['id_item']?>][valor]" class="form-control" value="<?=$item['valor_medio_atual']?>" required></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="d-flex gap-2">

            <?php if(in_array($inventario['status'], ['CONTAGEM', 'CONFERENCIA', 'AGUARDANDO APROVAÇÃO'])): ?>
                <button type="submit" name="cancelar" class="btn btn-danger" onclick="return confirm('Deseja realmente cancelar este inventário?');">
                    Cancelar Inventário
                </button>
            <?php endif; ?>

            <?php if($inventario['status'] == 'CONTAGEM'): ?>
                <button type="submit" name="salvar" class="btn btn-success">Salvar Contagem</button>
            <?php endif; ?>

            <?php if(in_array($inventario['status'], ['CONTAGEM', 'CONFERENCIA'])): ?>
                <button type="submit" name="aprovar" class="btn btn-warning">Enviar para Aprovação</button>
            <?php endif; ?>

            <?php if($inventario['status'] == 'AGUARDANDO APROVAÇÃO'): ?>
                <button type="submit" name="aprovar" class="btn btn-success">Aprovar</button>
            <?php endif; ?>
        </div>
    </form>
</div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
