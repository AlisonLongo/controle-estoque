<?php
require 'conexao.php';

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    $nome = trim($_POST['nome']);
    $nome_maiusculo = strtoupper($nome);

    if (!empty($nome_maiusculo)) {
        try {
 
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE UPPER(nome) = :nome");
            $stmtCheck->bindParam(':nome', $nome_maiusculo);
            $stmtCheck->execute();
            $count = $stmtCheck->fetchColumn();

            if ($count > 0) {
                $mensagem = "Já existe uma categoria com esse nome.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO categorias (nome) VALUES (:nome)");
                $stmt->bindParam(':nome', $nome_maiusculo);
                $stmt->execute();
                $mensagem = "Categoria cadastrada com sucesso!";
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao cadastrar categoria: " . $e->getMessage();
        }
    } else {
        $mensagem = "O campo nome é obrigatório.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

    try {

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE id_categoria = :id");
        $stmtCheck->bindParam(':id', $delete_id);
        $stmtCheck->execute();
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            $mensagem = "Não é possível deletar esta categoria, ela está vinculada a um item.";
        } else {
            $stmtDelete = $pdo->prepare("DELETE FROM categorias WHERE id = :id");
            $stmtDelete->bindParam(':id', $delete_id);
            $stmtDelete->execute();
            $mensagem = "Categoria deletada com sucesso!";
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao deletar categoria: " . $e->getMessage();
    }
}

try {
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY id ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar categorias: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Categorias</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">
        <h1>Cadastro de Categorias</h1>

        <?php if ($mensagem): ?>
            <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome da Categoria</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
            </div>
            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </form>

        <h2>Categorias Cadastradas</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $cat): ?>
                <tr>
                    <td><?= $cat['id'] ?></td>
                    <td><?= htmlspecialchars($cat['nome']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?= $cat['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
