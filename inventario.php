<?php
include 'conexao.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$msg = "";


if(isset($_POST['iniciar'])){
    $data = $_POST['data'] ?? '';

    if(empty($data) || $data > date('Y-m-d')){
        $msg = "❌ A data não pode ser superior à data de hoje.";
    } else {

     
        $stmtCheck = $pdo->query("SELECT COUNT(*) FROM inventarios WHERE status NOT IN ('APROVADO','REPROVADO')");
        $inventarioEmAberto = $stmtCheck->fetchColumn();

        if($inventarioEmAberto > 0){
            $msg = "⚠️ Já existe um inventário em andamento. Finalize ou aprove antes de iniciar um novo.";
        } else {

            $stmt = $pdo->prepare("INSERT INTO inventarios (data_realizado, status) VALUES (?, 'CONTAGEM') RETURNING id");
            $stmt->execute([$data]);
            $inventario_id = $stmt->fetchColumn();

            $itens = $pdo->query("
                SELECT i.id AS id_item, i.nome,
                       COALESCE(SUM(m.quantidade),0) AS estoque_sistema,
                       COALESCE(AVG(m.valor_total),0) AS valor_medio_sistema
                FROM itens i
                LEFT JOIN movimentacoes m ON m.id_item = i.id
                WHERE i.status='ATIVO'
                GROUP BY i.id, i.nome
            ")->fetchAll(PDO::FETCH_ASSOC);

            $stmtInsert = $pdo->prepare("
                INSERT INTO inventario_itens (id_inventario, id_item, estoque_sistema, valor_medio_sistema, status)
                VALUES (?, ?, ?, ?, 'CONTAGEM')
            ");
            foreach($itens as $item){
                $stmtInsert->execute([
                    $inventario_id,
                    $item['id_item'],
                    $item['estoque_sistema'],
                    $item['valor_medio_sistema']
                ]);
            }

            header("Location: inventario_contagem.php?id=".$inventario_id);
            exit;
        }
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


$where = [];
$params = [];

if(isset($_GET['f_status']) && $_GET['f_status'] != ''){
    $where[] = "status = ?";
    $params[] = $_GET['f_status'];
}
if(isset($_GET['f_data_ini']) && $_GET['f_data_ini'] != ''){
    $where[] = "data_realizado >= ?";
    $params[] = $_GET['f_data_ini'];
}
if(isset($_GET['f_data_fim']) && $_GET['f_data_fim'] != ''){
    $where[] = "data_realizado <= ?";
    $params[] = $_GET['f_data_fim'];
}

$sql = "SELECT * FROM inventarios";
if(count($where) > 0){
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY criado_em DESC"; 
$stmtConf = $pdo->prepare($sql);
$stmtConf->execute($params);
$inventariosConferencia = $stmtConf->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Inventário</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>

<?php
include 'navbar.php';

$msg = $_GET['msg'] ?? '';
if($msg){
    echo "
    <div class='alert alert-info alert-dismissible fade show mt-3' role='alert'>
        $msg
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>
    </div>
    ";
}
?>




<div class="container mt-4">
    <h2>Iniciar Inventário</h2>


    <form method="post" class="card p-3 shadow-sm mb-4">
        <div class="mb-3">
            <label for="data" class="form-label">Data do Inventário</label>
            <input type="date" name="data" id="data" class="form-control" max="<?=date('Y-m-d')?>" required>
        </div>
        <button type="submit" name="iniciar" class="btn btn-primary">Iniciar Inventário</button>
    </form>

  <h3>Filtrar Inventários</h3>
<form method="GET" class="row g-3 mb-3">
    <div class="col-md-3">
        <label for="f_status" class="form-label"><strong>Status</strong></label>
        <select name="f_status" id="f_status" class="form-select">
            <option value="">Todos os status</option>
            <option value="CONTAGEM" <?= (isset($_GET['f_status']) && $_GET['f_status']=='CONTAGEM')?'selected':'' ?>>CONTAGEM</option>
            <option value="CONFERENCIA" <?= (isset($_GET['f_status']) && $_GET['f_status']=='CONFERENCIA')?'selected':'' ?>>CONFERÊNCIA</option>
             <option value="AGUARDANDO APROVAÇÃO" <?= (isset($_GET['f_status']) && $_GET['f_status']=='AGUARDANDO APROVAÇÃO')?'selected':'' ?>>AGUARDANDO APROVAÇÃO</option>
            <option value="APROVADO" <?= (isset($_GET['f_status']) && $_GET['f_status']=='APROVADO')?'selected':'' ?>>APROVADO</option>
            <option value="REPROVADO" <?= (isset($_GET['f_status']) && $_GET['f_status']=='REPROVADO')?'selected':'' ?>>REPROVADO</option>
        </select>
    </div>

    <div class="col-md-3">
        <label for="f_data_ini" class="form-label"><strong>Data Inicial</strong></label>
        <input type="date" name="f_data_ini" id="f_data_ini" class="form-control" value="<?= $_GET['f_data_ini'] ?? '' ?>">
    </div>

    <div class="col-md-3">
        <label for="f_data_fim" class="form-label"><strong>Data Final</strong></label>
        <input type="date" name="f_data_fim" id="f_data_fim" class="form-control" value="<?= $_GET['f_data_fim'] ?? '' ?>">
    </div>

    <div class="col-md-3 d-flex align-items-end">
        <button type="submit" class="btn btn-primary me-2">Filtrar</button>
        <button type="button" id="btn-limpar" class="btn btn-secondary">Limpar</button>
    </div>
</form>


    <?php if(count($inventariosConferencia) > 0): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data Realizado</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($inventariosConferencia as $inv): ?>
                    <tr>
                        <td><?=$inv['id']?></td>
                        <td><?=date('d/m/Y', strtotime($inv['data_realizado']))?></td>
                        <td><?=$inv['status']?></td>
                        
<td>
    <?php if($inv['status'] == 'APROVADO'): ?>
        <a href="inventario_visualizar.php?id=<?=$inv['id']?>" class="btn btn-primary btn-sm">
            Visualizar
        </a>
    <?php elseif($inv['status'] == 'REPROVADO'): ?>

    <?php elseif($inv['status'] == 'AGUARDANDO APROVAÇÃO'): ?>
        <a href="inventario_aprovacao_rel.php?id=<?=$inv['id']?>" class="btn btn-warning btn-sm">
    Ir para Aprovação
</a>

    <?php else: ?>
        <a href="inventario_contagem.php?id=<?=$inv['id']?>" class="btn btn-primary btn-sm">
            Visualizar / Editar
        </a>

        <form method="post" style="display:inline-block;" onsubmit="return confirm('Deseja realmente excluir este inventário?');">
            <input type="hidden" name="inventario_id" value="<?=$inv['id']?>">
            <button type="submit" name="excluir" class="btn btn-danger btn-sm">Excluir</button>
        </form>
    <?php endif; ?>
</td>



                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">Não há inventários em conferência.</div>
    <?php endif; ?>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>

   document.getElementById('btn-limpar').addEventListener('click', function(){
    document.querySelector('select[name="f_status"]').value = '';
    document.querySelector('input[name="f_data_ini"]').value = '';
    document.querySelector('input[name="f_data_fim"]').value = '';
    window.location.href = 'inventario.php';
    });
</script>
</body>
</html>
