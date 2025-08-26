<?php
include 'conexao.php';

$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$id_categoria = $_GET['categoria'] ?? '';

$whereData = '';
if($data_inicio && $data_fim){
    $whereData = " AND m.data_movimentacao BETWEEN '$data_inicio' AND '$data_fim' ";
}

$whereCategoria = '';
if($id_categoria){
    $whereCategoria = " AND i.id_categoria = $id_categoria ";
}

$totalItens = $pdo->query("SELECT COUNT(*) FROM itens")->fetchColumn() ?: 0;
$totalValor = number_format($pdo->query("SELECT SUM(quantidade_atual * valor_unitario) FROM itens")->fetchColumn() ?: 0,2,',','.');
$entradas = $pdo->query("SELECT SUM(m.quantidade) FROM movimentacoes m JOIN itens i ON m.id_item=i.id WHERE tipo_movimentacao='entrada' $whereData $whereCategoria")->fetchColumn() ?: 0;
$saidas = $pdo->query("SELECT SUM(m.quantidade) FROM movimentacoes m JOIN itens i ON m.id_item=i.id WHERE tipo_movimentacao='saida' $whereData $whereCategoria")->fetchColumn() ?: 0;

$movMensal = $pdo->query("SELECT TO_CHAR(m.data_movimentacao,'YYYY-MM') AS mes,
    SUM(CASE WHEN m.tipo_movimentacao='entrada' THEN m.quantidade ELSE 0 END) AS entradas,
    SUM(CASE WHEN m.tipo_movimentacao='saida' THEN m.quantidade ELSE 0 END) AS saidas
    FROM movimentacoes m
    JOIN itens i ON m.id_item=i.id
    WHERE 1=1 $whereData $whereCategoria
    GROUP BY TO_CHAR(m.data_movimentacao,'YYYY-MM')
    ORDER BY mes")->fetchAll(PDO::FETCH_ASSOC);

$meses = $entradasMes = $saidasMes = [];
foreach($movMensal as $row){
    $meses[] = $row['mes'];
    $entradasMes[] = $row['entradas'];
    $saidasMes[] = $row['saidas'];
}

$movCategoria = $pdo->query("
    SELECT c.nome AS categoria,
           SUM(CASE WHEN m.tipo_movimentacao='entrada' THEN m.quantidade ELSE 0 END) AS entradas,
           SUM(CASE WHEN m.tipo_movimentacao='saida' THEN m.quantidade ELSE 0 END) AS saidas
    FROM movimentacoes m
    JOIN itens i ON m.id_item=i.id
    LEFT JOIN categorias c ON i.id_categoria=c.id
    WHERE 1=1 $whereData $whereCategoria
    GROUP BY c.nome
")->fetchAll(PDO::FETCH_ASSOC);

$catNome = $catEntradas = $catSaidas = [];
foreach($movCategoria as $row){
    $catNome[] = $row['categoria'];
    $catEntradas[] = $row['entradas'];
    $catSaidas[] = $row['saidas'];
}

$topItens = $pdo->query("SELECT nome, (quantidade_atual*valor_unitario) AS valor_total FROM itens ORDER BY valor_total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$topNomes = $topValores = [];
foreach($topItens as $row){
    $topNomes[] = $row['nome'];
    $topValores[] = $row['valor_total'];
}

$resultado['valMesNome'] = $valMesNome;
$resultado['valMesValor'] = $valMesValor;

$resultado = [
    'totalItens' => $totalItens,
    'totalValor' => $totalValor,
    'entradas' => $entradas,
    'saidas' => $saidas,
    'meses' => $meses,
    'entradasMes' => $entradasMes,
    'saidasMes' => $saidasMes,
    'catNome' => $catNome,
    'catEntradas' => $catEntradas,
    'catSaidas' => $catSaidas,
    'topNomes' => $topNomes,
    'topValores' => $topValores,
    'valCatNome' => $valCatNome,
    'valCatValor' => $valCatValor
];

header('Content-Type: application/json');
echo json_encode($resultado);
