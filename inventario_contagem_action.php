<?php
include 'conexao.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$inventario_id = $_POST['inventario_id'] ?? 0;
$msg = "";

if(isset($_POST['salvar'])){
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
        $msg = "✅ Inventário salvo com sucesso!";
    } else {
        $msg = "❌ Nenhum item informado.";
    }
}


if(isset($_POST['aprovar'])){
    $pdo->prepare("UPDATE inventarios SET status='AGUARDANDO APROVAÇÃO' WHERE id=?")->execute([$inventario_id]);
    $msg = "📤 Inventário enviado para aprovação!";

    header("Location: inventario.php?msg=".urlencode($msg));
    exit;
}


if (isset($_POST['cancelar'])) {
    $inventario_id = $_POST['inventario_id'];
    $stmt = $pdo->prepare("UPDATE inventarios SET status='Cancelado' WHERE id=?");
    $stmt->execute([$inventario_id]);

    header("Location: inventario.php");
    exit;
}


header("Location: inventario_contagem.php?id=$inventario_id&msg=".urlencode($msg));
exit;
