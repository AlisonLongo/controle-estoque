<?php
include "conexao.php";


$fornecedores = $pdo->query("SELECT id, nome FROM fornecedores WHERE status='ATIVO' ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$itens = $pdo->query("SELECT id, nome, valor_unitario FROM itens WHERE status='ATIVO' ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);


function getItensOrdem($pdo, $ordem_id){
    $stmt = $pdo->prepare("SELECT oi.id_item, oi.quantidade, oi.valor_unitario, i.nome 
                           FROM ordem_itens oi 
                           JOIN itens i ON oi.id_item=i.id 
                           WHERE oi.id_ordem=?");
    $stmt->execute([$ordem_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$filtros_ativos = [
    'f_fornecedor'=>$_GET['f_fornecedor'] ?? '',
    'f_item'=>$_GET['f_item'] ?? '',
    'f_status'=>$_GET['f_status'] ?? '',
    'f_data_ini'=>$_GET['f_data_ini'] ?? '',
    'f_data_fim'=>$_GET['f_data_fim'] ?? ''
];

if(isset($_GET['acao'], $_GET['id'])){
    $ordem_id = $_GET['id'];
    if($_GET['acao']=='aprovar'){
        $pdo->prepare("UPDATE ordens_compra SET status='aprovado' WHERE id=?")->execute([$ordem_id]);
        $itens_ordem = getItensOrdem($pdo,$ordem_id);
        foreach($itens_ordem as $item){
            $pdo->prepare("INSERT INTO movimentacoes (id_item, tipo_movimentacao, quantidade, valor_total, observacao) 
                VALUES (?, 'entrada', ?, ?, ?)")->execute([
                    $item['id_item'],$item['quantidade'],$item['quantidade']*$item['valor_unitario'],"OC"
                ]);
        }
    } elseif($_GET['acao']=='reprovar'){
        $pdo->prepare("UPDATE ordens_compra SET status='reprovado' WHERE id=?")->execute([$ordem_id]);
    }
    $query_filtros = http_build_query($filtros_ativos);
    header("Location: ?$query_filtros&filtrar=1");
    exit;
}

if($_SERVER['REQUEST_METHOD']=='POST'){
    $ordem_id = $_POST['ordem_id'] ?? null;
    $fornecedor_id = $_POST['fornecedor'];
    $item_id = $_POST['item'];
    $quantidade = $_POST['quantidade'];
    $valor_unitario = $_POST['valor_unitario'];

    if(isset($_POST['criar_ordem'])){
        $stmt = $pdo->prepare("INSERT INTO ordens_compra (id_fornecedor) VALUES (?) RETURNING id");
        $stmt->execute([$fornecedor_id]);
        $ordem_id = $stmt->fetchColumn();

        if($quantidade>0){
            $pdo->prepare("INSERT INTO ordem_itens (id_ordem, id_item, quantidade, valor_unitario) VALUES (?,?,?,?)")
                ->execute([$ordem_id,$item_id,$quantidade,$valor_unitario]);
        }
        $msg = "Ordem criada com sucesso!";
    }

    if(isset($_POST['salvar_ordem'])){
        $pdo->prepare("UPDATE ordens_compra SET id_fornecedor=? WHERE id=?")->execute([$fornecedor_id,$ordem_id]);
        $pdo->prepare("UPDATE ordem_itens SET id_item=?, quantidade=?, valor_unitario=? WHERE id_ordem=?")
            ->execute([$item_id, $quantidade, $valor_unitario, $ordem_id]);
        $msg = "Ordem atualizada com sucesso!";
    }

    $query_filtros = http_build_query($filtros_ativos);
    header("Location: ?$query_filtros&filtrar=1");
    exit;
}

$ordens = [];
$where = [];
$params = [];

if(isset($_GET['filtrar'])){
    if($filtros_ativos['f_fornecedor'] != ''){
        $where[] = "oc.id_fornecedor = ?";
        $params[] = $filtros_ativos['f_fornecedor'];
    }
    if($filtros_ativos['f_item'] != ''){
        $where[] = "oi.id_item = ?";
        $params[] = $filtros_ativos['f_item'];
    }
    if($filtros_ativos['f_status'] != ''){
        $where[] = "oc.status = ?";
        $params[] = $filtros_ativos['f_status'];
    }
    if($filtros_ativos['f_data_ini'] != ''){
        $where[] = "oc.data_ordem::date >= ?";
        $params[] = $filtros_ativos['f_data_ini'];
    }
    if($filtros_ativos['f_data_fim'] != ''){
        $where[] = "oc.data_ordem::date <= ?";
        $params[] = $filtros_ativos['f_data_fim'];
    }

    $sql = "SELECT oc.id,
                   oc.id_fornecedor,
                   f.nome AS fornecedor_nome,
                   oc.data_ordem::date AS data_ordem,
                   oc.status
            FROM ordens_compra oc
            JOIN fornecedores f ON oc.id_fornecedor=f.id
            LEFT JOIN ordem_itens oi ON oc.id=oi.id_ordem";

    if(count($where)>0){
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " GROUP BY oc.id, oc.id_fornecedor, f.nome, oc.data_ordem, oc.status ORDER BY oc.data_ordem DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ordens = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Ordens de Compra</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">

<h2>Ordem de Compra</h2>
<?php if(isset($msg)) echo "<div class='alert alert-info'>$msg</div>"; ?>

<form method="POST" id="form-ordem" class="mb-4">
    <input type="hidden" name="ordem_id" id="ordem_id">

    <?php foreach($filtros_ativos as $key=>$val): ?>
        <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($val) ?>">
    <?php endforeach; ?>

    <div class="mb-3">
        <label>Fornecedor:</label>
        <select name="fornecedor" id="fornecedor" class="form-control" required>
    <option value="">Selecione o fornecedor</option>
    <?php foreach($fornecedores as $f): ?>
        <option value="<?= $f['id'] ?>"><?= $f['nome'] ?></option>
    <?php endforeach; ?>
</select>
    </div>

    <h5>Adicionar/Editar Item</h5>
    <div class="row mb-3">
        <div class="col-md-4">
            <label>Item:</label>
            <select name="item" id="item-select" class="form-control" required>
    <option value="">Selecione um item</option>
    <?php foreach($itens as $i): ?>
        <option value="<?= $i['id'] ?>" data-valor="<?= $i['valor_unitario'] ?>"><?= $i['nome'] ?></option>
    <?php endforeach; ?>
</select>
        </div>
        <div class="col-md-3">
            <label>Quantidade:</label>
            <input type="number" name="quantidade" id="quantidade" class="form-control" value="1" min="1" required>
        </div>
        <div class="col-md-3">
            <label>Valor Unitário:</label>
            <input type="number" name="valor_unitario" id="valor-unitario" class="form-control" step="0.01" required>
        </div>
    </div>

    <button type="submit" name="criar_ordem" id="btn-criar" class="btn btn-primary">Criar Ordem</button>
    <button type="submit" name="salvar_ordem" id="btn-salvar" class="btn btn-success d-none">Salvar Alterações</button>
    <button type="button" id="btn-cancelar" class="btn btn-secondary d-none">Cancelar</button>
</form>

<h4>Filtrar Ordens</h4>
<form method="GET" id="form-filtro" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="f_fornecedor" class="form-control">
            <option value="">Todos os fornecedores</option>
            <?php foreach($fornecedores as $f): ?>
                <option value="<?= $f['id'] ?>" <?= ($filtros_ativos['f_fornecedor']==$f['id'])?'selected':'' ?>><?= $f['nome'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select name="f_item" class="form-control">
            <option value="">Todos os itens</option>
            <?php foreach($itens as $i): ?>
                <option value="<?= $i['id'] ?>" <?= ($filtros_ativos['f_item']==$i['id'])?'selected':'' ?>><?= $i['nome'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="f_status" class="form-control">
            <option value="">Todos os status</option>
            <option value="pendente" <?= ($filtros_ativos['f_status']=='pendente')?'selected':'' ?>>Pendente</option>
            <option value="aprovado" <?= ($filtros_ativos['f_status']=='aprovado')?'selected':'' ?>>Aprovado</option>
            <option value="reprovado" <?= ($filtros_ativos['f_status']=='reprovado')?'selected':'' ?>>Reprovado</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="date" name="f_data_ini" class="form-control" value="<?= $filtros_ativos['f_data_ini'] ?>">
    </div>
    <div class="col-md-2">
        <input type="date" name="f_data_fim" class="form-control" value="<?= $filtros_ativos['f_data_fim'] ?>">
    </div>
    <div class="col-md-12 mt-2">
        <button class="btn btn-primary" type="submit" name="filtrar">Filtrar</button>
        <button type="button" class="btn btn-secondary" id="btn-limpar">Limpar</button>
    </div>
</form>


<table class="table table-striped">
<thead>
<tr>
<th>Ordem</th><th>Fornecedor</th><th>Data</th><th>Item</th><th>Qtd</th><th>Valor Unit.</th><th>Total</th><th>Status</th><th>Ações</th>
</tr>
</thead>
<tbody>
<?php 
if(isset($_GET['filtrar'])){
    foreach($ordens as $o):
        $itens_ordem = getItensOrdem($pdo,$o['id']);
        foreach($itens_ordem as $item):
            $query_aprovar = http_build_query(array_merge($filtros_ativos, ['acao'=>'aprovar','id'=>$o['id'],'filtrar'=>1]));
            $query_reprovar = http_build_query(array_merge($filtros_ativos, ['acao'=>'reprovar','id'=>$o['id'],'filtrar'=>1]));
?>
<tr>
<td><?= $o['id'] ?></td>
<td><?= $o['fornecedor_nome'] ?></td>
<td><?= date('d/m/Y', strtotime($o['data_ordem'])) ?></td>
<td><?= $item['nome'] ?></td>
<td><?= $item['quantidade'] ?></td>
<td><?= number_format($item['valor_unitario'],2,',','.') ?></td>
<td><?= number_format($item['quantidade']*$item['valor_unitario'],2,',','.') ?></td>
<td>
<?php 
    $status = strtoupper($o['status']);
    $cor = '';
    switch($o['status']){
        case 'pendente': $cor='bg-warning'; break;
        case 'aprovado': $cor='bg-success'; break;
        case 'reprovado': $cor='bg-danger'; break;
    }
?>
<span class="badge <?= $cor ?> me-1">&nbsp;</span> <?= $status ?>
</td>
<td>
<?php if($o['status']=='pendente'): ?>
    <button class="btn btn-warning btn-sm btn-editar" 
            data-id="<?= $o['id'] ?>"
            data-fornecedor="<?= $o['id_fornecedor'] ?>"
            data-item="<?= $item['id_item'] ?>"
            data-quantidade="<?= $item['quantidade'] ?>"
            data-valor="<?= $item['valor_unitario'] ?>">Editar</button>
    <a href="?<?= $query_aprovar ?>" class="btn btn-success btn-sm">Aprovar</a>
    <a href="?<?= $query_reprovar ?>" class="btn btn-danger btn-sm">Reprovar</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; endforeach; } ?>
</tbody>
</table>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('item-select').addEventListener('change', function(){
    document.getElementById('valor-unitario').value = this.selectedOptions[0].dataset.valor || 0;
});

document.querySelectorAll('.btn-editar').forEach(btn=>{
    btn.addEventListener('click', function(){
        document.getElementById('ordem_id').value = this.dataset.id;
        document.getElementById('fornecedor').value = this.dataset.fornecedor;
        document.getElementById('item-select').value = this.dataset.item;
        document.getElementById('quantidade').value = this.dataset.quantidade;
        document.getElementById('valor-unitario').value = this.dataset.valor;

        document.getElementById('btn-criar').classList.add('d-none');
        document.getElementById('btn-salvar').classList.remove('d-none');
        document.getElementById('btn-cancelar').classList.remove('d-none');
    });
});

document.getElementById('btn-cancelar').addEventListener('click', function(){
    document.getElementById('form-ordem').reset();
    document.getElementById('btn-criar').classList.remove('d-none');
    document.getElementById('btn-salvar').classList.add('d-none');
    this.classList.add('d-none');
});

document.getElementById('btn-limpar').addEventListener('click', function(){
    document.getElementById('form-filtro').reset();
    const tbody = document.querySelector('table.table tbody');
    tbody.innerHTML = '';
});
</script>
</body>
</html>
