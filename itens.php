<?php 
include 'conexao.php'; 
session_start();

$msg = "";
$editar_id = "";
$editar_nome = "";
$editar_descricao = "";
$editar_valor = "";
$editar_qtd = "";
$editar_id_categoria = "";
$editar_status = "ATIVO";

$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

if(isset($_SESSION['msg_item'])){
    $msg = $_SESSION['msg_item'];
    unset($_SESSION['msg_item']);
}

if(isset($_POST['salvar'])) {
    $nome = strtoupper(trim($_POST['nome'])); 
    $descricao = $_POST['descricao'];
    $id_categoria = $_POST['id_categoria'];
    $status = $_POST['status'];

    $valor_unitario = $_POST['valor_unitario'];
    $quantidade_atual = $_POST['quantidade_atual'];

    $sqlCheck = "SELECT COUNT(*) FROM itens WHERE nome = ?";
    $paramsCheck = [$nome];

    if(!empty($_POST['id'])) {
        $sqlCheck .= " AND id <> ?";
        $paramsCheck[] = $_POST['id'];
    }

    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute($paramsCheck);
    $existe = $stmtCheck->fetchColumn();

    if($existe > 0){
        $_SESSION['msg_item'] = "Já existe um item cadastrado com este nome.";
        header("Location: itens.php");
        exit;
    } else {
        if(!empty($_POST['id'])) {

            $id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE itens 
                SET nome=?, descricao=?, valor_unitario=?, quantidade_atual=?, id_categoria=?, status=? 
                WHERE id=?");
            if($stmt->execute([$nome,$descricao,$valor_unitario,$quantidade_atual,$id_categoria,$status,$id])){
                $_SESSION['msg_item'] = "Item atualizado com sucesso!";
                header("Location: itens.php");
                exit;
            } else {
                $msg = "Erro ao atualizar item.";
            }
        } else {

            $stmt = $pdo->prepare("INSERT INTO itens 
                (nome, descricao, valor_unitario, quantidade_atual, id_categoria, status) 
                VALUES (?, ?, ?, ?, ?, ?)");
            if($stmt->execute([$nome,$descricao,$valor_unitario,$quantidade_atual,$id_categoria,$status])){
                $_SESSION['msg_item'] = "Item cadastrado com sucesso!";
                header("Location: itens.php");
                exit;
            } else {
                $msg = "Erro ao cadastrar item.";
            }
        }
    }
}

if(isset($_GET['editar'])){
    $id = $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM itens WHERE id=?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if($item){
        $editar_id = $item['id'];
        $editar_nome = $item['nome'];
        $editar_descricao = $item['descricao'];
        $editar_valor = $item['valor_unitario'];
        $editar_qtd = $item['quantidade_atual'];
        $editar_id_categoria = $item['id_categoria'];
        $editar_status = $item['status'];
    }
}

if(isset($_GET['excluir'])){
    $id = $_GET['excluir'];

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) AS total FROM movimentacoes WHERE id_item=?");
    $stmtCheck->execute([$id]);
    $temMov = $stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];

    if($temMov > 0){
        $pdo->prepare("UPDATE itens SET status='INATIVO' WHERE id=?")->execute([$id]);
        $_SESSION['msg_item'] = "O item já possui movimentações e foi inativado.";
    } else {
        $pdo->prepare("DELETE FROM itens WHERE id=?")->execute([$id]);
        $_SESSION['msg_item'] = "Item excluído com sucesso!";
    }
    header("Location: itens.php");
    exit;
}

if(isset($_GET['ativar'])){
    $id = $_GET['ativar'];
    $pdo->prepare("UPDATE itens SET status='ATIVO' WHERE id=?")->execute([$id]);
    $_SESSION['msg_item'] = "Item ativado!";
    header("Location: itens.php");
    exit;
}

$itens = [];
if(isset($_POST['consultar'])){
    $filtro = $_POST['filtro'] ?? '';
    $stmt = $pdo->prepare("
        SELECT i.*, c.nome as categoria_nome 
        FROM itens i 
        LEFT JOIN categorias c ON i.id_categoria = c.id 
        WHERE i.nome ILIKE ? 
        ORDER BY i.nome
    ");
    $stmt->execute(["%$filtro%"]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Itens</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<?php include 'navbar.php'; ?>
<body>
<div class="container mt-4">
    <h2>Cadastro de Itens</h2>
    <?php if($msg) echo "<div class='alert alert-info'>$msg</div>"; ?>
    
    <form method="POST">
        <input type="hidden" name="id" value="<?= $editar_id ?>">
        <div class="mb-3">
            <label>Nome</label>
            <input type="text" name="nome" class="form-control" value="<?= $editar_nome ?>" required>
        </div>
        <div class="mb-3">
            <label>Descrição</label>
            <textarea name="descricao" class="form-control"><?= $editar_descricao ?></textarea>
        </div>
        <div class="mb-3">
            <label>Valor Unitário</label>
            <input type="number" step="0.01" name="valor_unitario" class="form-control" value="<?= $editar_valor ?>" readonly>
        </div>
        <div class="mb-3">
            <label>Quantidade Atual</label>
            <input type="number" name="quantidade_atual" class="form-control" value="<?= $editar_qtd ?: 0 ?>" readonly>
        </div>
        <div class="mb-3">
            <label>Categoria</label>
            <select name="id_categoria" class="form-control" required>
                <option value="">Selecione a categoria</option>
                <?php foreach($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $editar_id_categoria) ? 'selected' : '' ?>><?= $cat['nome'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-control" required>
                <option value="ATIVO" <?= ($editar_status=="ATIVO") ? "selected" : "" ?>>Ativo</option>
                <option value="INATIVO" <?= ($editar_status=="INATIVO") ? "selected" : "" ?>>Inativo</option>
            </select>
        </div>
        <button type="submit" name="salvar" class="btn btn-primary"><?= $editar_id ? 'Atualizar' : 'Cadastrar' ?></button>
    </form>

    <hr>

    <h3>Consultar Itens</h3>
    <form method="POST" class="mb-3 d-flex gap-2">
        <input type="text" name="filtro" class="form-control" placeholder="Digite o nome do item">
        <button type="submit" name="consultar" class="btn btn-info">Consultar</button>
    </form>

    <?php if(!empty($itens)): ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th><th>Nome</th><th>Descrição</th>
                <th>Valor</th><th>Qtd</th><th>Categoria</th>
                <th>Status</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($itens as $i): ?>
            <tr>
                <td><?= $i['id'] ?></td>
                <td><?= $i['nome'] ?></td>
                <td><?= $i['descricao'] ?></td>
                <td>R$ <?= number_format($i['valor_unitario'],2,',','.') ?></td>
                <td><?= $i['quantidade_atual'] ?></td>
                <td><?= $i['categoria_nome'] ?></td>
                <td>
                    <?php if($i['status']=="ATIVO"): ?>
                        <span class="badge bg-success">Ativo</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inativo</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?editar=<?= $i['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                    <?php if($i['status']=="ATIVO"): ?>
                        <a href="?excluir=<?= $i['id'] ?>" class="btn btn-sm btn-danger" 
                           onclick="return confirm('Deseja excluir este item? Se já tiver movimentações, será apenas inativado.')">Excluir/Inativar</a>
                    <?php else: ?>
                        <a href="?ativar=<?= $i['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Deseja ativar este item?')">Ativar</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php elseif(isset($_POST['consultar'])): ?>
        <div class="alert alert-info">Nenhum item encontrado.</div>
    <?php endif; ?>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
