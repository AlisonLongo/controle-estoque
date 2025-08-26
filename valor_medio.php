<?php
include 'conexao.php';

if(isset($_GET['id'])){
    $id_item = $_GET['id'];
    $stmt = $pdo->prepare("SELECT COALESCE(AVG(valor_unitario),0) as valor FROM ordem_itens WHERE id_item=?");
    $stmt->execute([$id_item]);
    $valor = $stmt->fetchColumn();
    echo json_encode(['valor' => $valor]);
}
?>