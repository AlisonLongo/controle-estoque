<?php
include "conexao.php";

$msg = "";
$editar_id = $editar_nome = $editar_cnpj = $editar_telefone = $editar_email = "";
$editar_status = "ATIVO";

if(isset($_POST['salvar'])) {

    $cnpj = preg_replace('/\D/', '', $_POST['cnpj']); 
    $nome = strtoupper(trim($_POST['nome']));
    $telefone = trim($_POST['telefone']);
    $email = trim($_POST['email']);

    $telefone = ($telefone === '') ? null : preg_replace('/\D/', '', $telefone);
    $email = ($email === '') ? null : $email;

    if(empty($_POST['id'])){
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fornecedores WHERE cnpj=? OR nome=?");
        $stmt->execute([$cnpj, $nome]);
        if($stmt->fetchColumn() > 0){
            $msg = "CNPJ ou Nome já cadastrado!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO fornecedores (nome, cnpj, telefone, email, status) VALUES (?, ?, ?, ?, 'ATIVO')");
            if ($stmt->execute([$nome, $cnpj, $telefone, $email])) {
                $msg = "Fornecedor cadastrado com sucesso!";
            } else {
                $msg = "Erro ao cadastrar fornecedor.";
            }
        }
    } else {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fornecedores WHERE (cnpj=? OR nome=?) AND id<>?");
        $stmt->execute([$cnpj, $nome, $id]);
        if($stmt->fetchColumn() > 0){
            $msg = "CNPJ ou Nome já cadastrado em outro fornecedor!";
        } else {
            $stmt = $pdo->prepare("UPDATE fornecedores SET nome=?, cnpj=?, telefone=?, email=?, status=? WHERE id=?");
            if($stmt->execute([$nome, $cnpj, $telefone, $email, $_POST['status'], $id])){
                $msg = "Fornecedor atualizado com sucesso!";
                $editar_id = $editar_nome = $editar_cnpj = $editar_telefone = $editar_email = "";
                $editar_status = "ATIVO";
            } else {
                $msg = "Erro ao atualizar fornecedor.";
            }
        }
    }
}

if(isset($_GET['editar'])){
    $id = $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id=?");
    $stmt->execute([$id]);
    $forn = $stmt->fetch(PDO::FETCH_ASSOC);
    if($forn){
        $editar_id = $forn['id'];
        $editar_nome = $forn['nome'];
        $editar_cnpj = $forn['cnpj'];
        $editar_telefone = $forn['telefone'];
        $editar_email = $forn['email'];
        $editar_status = $forn['status'];
    }
}

if(isset($_GET['excluir'])){
    $id_fornecedor = (int) $_GET['excluir'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ordens_compra WHERE id_fornecedor = ?");
    $stmt->execute([$id_fornecedor]);
    $temMov = $stmt->fetchColumn();

    if($temMov > 0){

        $stmt = $pdo->prepare("UPDATE fornecedores SET status='INATIVO' WHERE id = ?");
        $stmt->execute([$id_fornecedor]);
        $msg = "Fornecedor possui ordens de compra. Foi inativado automaticamente.";
    } else {
 
        $stmt = $pdo->prepare("DELETE FROM fornecedores WHERE id = ?");
        $stmt->execute([$id_fornecedor]);
        $msg = "Fornecedor excluído com sucesso!";
    }

    header("Location: fornecedores.php?msg=".urlencode($msg));
    exit;
}

if(isset($_GET['ativar'])){
    $id = $_GET['ativar'];
    $pdo->prepare("UPDATE fornecedores SET status='ATIVO' WHERE id=?")->execute([$id]);
    $msg = "Fornecedor ativado novamente!";
}

$fornecedores = [];
if(isset($_POST['consultar'])){
    $filtro = $_POST['filtro'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE nome ILIKE ? ORDER BY nome");
    $stmt->execute(["%$filtro%"]);
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Fornecedores</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>

<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <h2>Cadastro de Fornecedores</h2>
    <?php if($msg) echo "<div class='alert alert-info'>$msg</div>"; ?>
    
    <form method="POST">
        <input type="hidden" name="id" value="<?= $editar_id ?>">
        <div class="mb-3">
            <label>Nome</label>
            <input type="text" name="nome" class="form-control" value="<?= $editar_nome ?>" required>
        </div>
        <div class="mb-3">
            <label>CNPJ</label>
            <input type="text" name="cnpj" class="form-control" value="<?= $editar_cnpj ?>" required>
        </div>
        <div class="mb-3">
            <label>Telefone</label>
            <input type="text" name="telefone" class="form-control" value="<?= $editar_telefone ?>" >
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= $editar_email ?>">
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

    <h3>Consultar Fornecedores</h3>
    <form method="POST" class="mb-3 d-flex gap-2">
        <input type="text" name="filtro" class="form-control" placeholder="Digite o nome do fornecedor">
        <button type="submit" name="consultar" class="btn btn-info">Consultar</button>
    </form>

    <?php if(!empty($fornecedores)): ?>
    <table class="table table-striped">
        <thead>
            <tr><th>ID</th><th>Nome</th><th>CNPJ</th><th>Telefone</th><th>Email</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
        <?php foreach($fornecedores as $f): ?>
            <tr>
                <td><?= $f['id'] ?></td>
                <td><?= $f['nome'] ?></td>
                <td><?= $f['cnpj'] ?></td>
                <td><?= $f['telefone'] ?></td>
                <td><?= $f['email'] ?></td>
                <td>
                    <?php if($f['status']=="ATIVO"): ?>
                        <span class="badge bg-success">Ativo</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inativo</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?editar=<?= $f['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                    <a href="?excluir=<?= $f['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deseja excluir este fornecedor?')">Excluir</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php elseif(isset($_POST['consultar'])): ?>
        <div class="alert alert-info">Nenhum fornecedor encontrado.</div>
    <?php endif; ?>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
