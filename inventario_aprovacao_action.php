<?php
include 'conexao.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$inventario_id = $_POST['inventario_id'] ?? 0;
$msg = "";


if(isset($_POST['aprovar'])){
 
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

    $stmtUpdateItens = $pdo->prepare("
        UPDATE itens
        SET quantidade_atual = ?, valor_unitario = ?
        WHERE id = ?
    ");

    foreach($itens as $item){
        if($item['contagem_atual'] > 0){
            $quantidade = (float)$item['contagem_atual']; 
            $valorTotal = $item['valor_medio_atual'] * $quantidade;


            $stmtMov->execute([
                $item['id_item'],
                $quantidade,
                $valorTotal
            ]);

  
            $stmtUpdateItens->execute([
                $quantidade,
                $item['valor_medio_atual'],
                $item['id_item']
            ]);
        }
    }

    $pdo->prepare("UPDATE inventarios SET status='APROVADO' WHERE id=?")->execute([$inventario_id]);
    $msg = "✅ Inventário aprovado, itens atualizados e movimentações registradas!";
}

if(isset($_POST['reprovar'])){
    $pdo->prepare("UPDATE inventarios SET status='REPROVADO' WHERE id=?")->execute([$inventario_id]);
    $msg = "⚠️ Inventário reprovado.";
}

header("Location: inventario.php?msg=".urlencode($msg));
exit;
