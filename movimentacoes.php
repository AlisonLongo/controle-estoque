<?php
include 'conexao.php';

$itens = $pdo->query("SELECT id, nome FROM itens WHERE status='ATIVO' ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);


if(isset($_POST['salvar'])){
    $id_item = $_POST['id_item'];
    $tipo = $_POST['tipo_movimentacao'];
    $quantidade = $_POST['quantidade'];
   $valor_unitario = number_format((float)$_POST['valor_unitario'], 2, '.', '');

    $valor_total = $valor_unitario * $quantidade;

    $obs = ($tipo=='saida') ? 'AV' : 'AV';
     $obs = ($tipo=='entrada') ? 'AV' : 'AV';

    $pdo->prepare("INSERT INTO movimentacoes (id_item, tipo_movimentacao, quantidade, valor_total, observacao) VALUES (?,?,?,?,?)")
        ->execute([$id_item,$tipo,$quantidade,$valor_total,$obs]);

    if($tipo=='entrada'){
        $pdo->prepare("UPDATE itens SET quantidade_atual = quantidade_atual + ? WHERE id=?")->execute([$quantidade,$id_item]);
    } else {
        $pdo->prepare("UPDATE itens SET quantidade_atual = quantidade_atual - ? WHERE id=?")->execute([$quantidade,$id_item]);
    }

    $msg = "Movimentação registrada com sucesso!";
}

$movs = []; 

if(isset($_GET['filtrar'])){ 
    $where = [];
    $params = [];

    if(isset($_GET['f_item']) && $_GET['f_item'] != ''){
        $where[] = "m.id_item = ?";
        $params[] = $_GET['f_item'];
    }
    if(isset($_GET['f_tipo']) && $_GET['f_tipo'] != ''){
        $where[] = "m.tipo_movimentacao = ?";
        $params[] = $_GET['f_tipo'];
    }
    if(isset($_GET['f_data_ini']) && $_GET['f_data_ini'] != ''){
        $where[] = "m.data_movimentacao >= ?";
        $params[] = $_GET['f_data_ini'];
    }
    if(isset($_GET['f_data_fim']) && $_GET['f_data_fim'] != ''){
        $data_fim = $_GET['f_data_fim'] . ' 23:59:59';
        $where[] = "m.data_movimentacao <= ?";
        $params[] = $data_fim;
    }
    if(isset($_GET['f_origem']) && $_GET['f_origem'] != ''){
        $where[] = "m.observacao LIKE ?";
        $params[] = $_GET['f_origem'].'%';
    }

    $sql = "SELECT m.*, i.nome FROM movimentacoes m JOIN itens i ON i.id=m.id_item";
    if(count($where) > 0){
        $sql .= " WHERE ".implode(" AND ",$where);
    }
    $sql .= " ORDER BY m.data_movimentacao DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Movimentações</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <h2>Movimentação Avulsa</h2>

    <?php if(isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>

    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label>Item:</label>
            <select name="id_item" id="item-select" class="form-select" required>
    <option value="">Selecione</option>
    <?php foreach($itens as $i): ?>
        <option value="<?= $i['id'] ?>"><?= $i['nome'] ?></option>
    <?php endforeach; ?>
</select>
        </div>

        <div class="mb-3">
            <label>Valor Unitário:</label>
            <input type="number" name="valor_unitario" id="valor-unitario" class="form-control" step="0.01" value="0" required readonly>
        </div>

        <div class="mb-3">
            <label>Tipo:</label>
            <select name="tipo_movimentacao" class="form-select" required>
                <option value="entrada">Entrada</option>
                <option value="saida">Saída</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Quantidade:</label>
            <input type="number" name="quantidade" class="form-control" value="1" min="1" required>
        </div>

        <button type="submit" name="salvar" class="btn btn-primary">Registrar</button>
    </form>

    <h3>Filtrar Histórico</h3>
<form method="GET" class="row g-3 mb-3">

    <div class="col-md-3">
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

    <div class="col-md-2">
        <label for="f_tipo" class="form-label"><strong>Tipo de Movimentação</strong></label>
        <select name="f_tipo" id="f_tipo" class="form-select">
            <option value="">Todos os tipos</option>
            <option value="entrada" <?= (isset($_GET['f_tipo']) && $_GET['f_tipo']=='entrada')?'selected':'' ?>>Entrada</option>
            <option value="saida" <?= (isset($_GET['f_tipo']) && $_GET['f_tipo']=='saida')?'selected':'' ?>>Saída</option>
        </select>
    </div>

    <div class="col-md-2">
        <label for="f_data_ini" class="form-label"><strong>Data Inicial</strong></label>
        <input type="date" name="f_data_ini" id="f_data_ini" class="form-control" value="<?= $_GET['f_data_ini'] ?? '' ?>">
    </div>

    <div class="col-md-2">
        <label for="f_data_fim" class="form-label"><strong>Data Final</strong></label>
        <input type="date" name="f_data_fim" id="f_data_fim" class="form-control" value="<?= $_GET['f_data_fim'] ?? '' ?>">
    </div>

    <div class="col-md-3">
        <label for="f_origem" class="form-label"><strong>Origem</strong></label>
        <select name="f_origem" id="f_origem" class="form-select">
            <option value="">Todas</option>
            <option value="OC" <?= (isset($_GET['f_origem']) && $_GET['f_origem']=='OC')?'selected':'' ?>>Ordem de Compra</option>
            <option value="AV" <?= (isset($_GET['f_origem']) && $_GET['f_origem']=='AV')?'selected':'' ?>>Movimentação Avulsa</option>
            <option value="IN" <?= (isset($_GET['f_origem']) && $_GET['f_origem']=='IN')?'selected':'' ?>>Inventário</option>
        </select>
    </div>

    <div class="col-md-12 mt-2">
       <button class="btn btn-primary" name="filtrar" type="submit">Filtrar</button>
        <a href="movimentacoes.php" class="btn btn-secondary">Limpar</a>
    </div>
</form>


    <h3>Histórico de Movimentações</h3>
    <table class="table table-striped">
        <thead>
            <tr><th>Item</th><th>Tipo</th><th>Quantidade</th><th>Valor Total</th><th>Data</th></tr>
        </thead>
        <tbody>
        <?php foreach($movs as $m): ?>
            <tr>
                <td><?= $m['nome'] ?></td>
               <td><?= strtoupper($m['tipo_movimentacao']) == 'SAIDA' ? 'SAÍDA' : strtoupper($m['tipo_movimentacao']) ?></td>
                <td><?= $m['quantidade'] ?></td>
                <td>R$ <?= number_format($m['valor_total'],2,',','.') ?></td>
                <td><?= date('d/m/Y', strtotime($m['data_movimentacao'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>

document.getElementById('item-select').addEventListener('change', function(){
    const itemId = this.value;
    if(itemId){
        fetch('valor_medio.php?id=' + itemId)
        .then(res => res.json())
        .then(data => {
            document.getElementById('valor-unitario').value = parseFloat(data.valor).toFixed(2);
        });
    } else {
        document.getElementById('valor-unitario').value = "0.00";
    }
});
</script>
</body>
</html>
