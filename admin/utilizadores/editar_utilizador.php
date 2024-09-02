<?php
session_start();
include '../../includes/db_connection.php';

// Verifica se o utilizador é admin ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Administrador") {
    // Se não for, volta ao index
    header("location: ../../index.php");
    exit;
}

//GET
// Verifica o ID é válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    //toastr
    $_SESSION['error'] = "ID inválido.";
    header("Location: lista_utilizadores.php");
    exit;
}

// Consulta os dados do utilizador
$id_utilizador = $_GET['id'];
$consulta = $ligacao->prepare("SELECT * FROM Utilizadores WHERE id_utilizador = :id_utilizador");
$consulta->bindParam(':id_utilizador', $id_utilizador);
$consulta->execute();
$utilizador = $consulta->fetch(PDO::FETCH_ASSOC);

// Verifica se o utilizador existe
if (!$utilizador) {
    //toastr
    $_SESSION['error'] = "Utilizador não encontrado.";
    header("Location: lista_utilizadores.php");
    exit;
}

// POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_utilizador"])) {
    $id_utilizador = $_POST['id_utilizador'];
    $nome = $_POST['nome'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $codigo_funcionario = $_POST['codigo_funcionario'];
    $tipo = $_POST['tipo'];
    $departamento = $_POST['departamento'];
        
    //É preciso verificar se os dados para os quais o admin vai editar, se não pertencem já a outro utilizador
    // ou seja, vamos à tabela, onde o id_utilizador for != deste, ver se os dados já existem
    //Nome
    $VerificaRepete = $ligacao->prepare("SELECT COUNT(*) FROM Utilizadores WHERE nome = :nome AND id_utilizador != :id_utilizador");
    $VerificaRepete->bindParam(':nome', $nome);
    $VerificaRepete->bindParam(':id_utilizador', $id_utilizador);
    $VerificaRepete->execute();
    $existeNome = $VerificaRepete->fetchColumn();
    if ($existeNome > 0) {
        //toastr
        $_SESSION['error'] = "Não é possível atualizar o <strong>Nome</strong> deste Utilizador pois este já pertence a outro utilizador.";
        header("Location: editar_utilizador.php?id=" . $id_utilizador);
        exit;
    }

    //Username
    $VerificaRepete = $ligacao->prepare("SELECT COUNT(*) FROM Utilizadores WHERE username = :username AND id_utilizador != :id_utilizador");
    $VerificaRepete->bindParam(':username', $username);
    $VerificaRepete->bindParam(':id_utilizador', $id_utilizador);
    $VerificaRepete->execute();
    $existeUsername = $VerificaRepete->fetchColumn();
    if ($existeUsername > 0) {
        //toastr
        $_SESSION['error'] = "Não é possível atualizar o <strong>Username</strong> do desde Utilizador pois este já pertence a outro utilizador.";
        header("Location: editar_utilizador.php?id=" . $id_utilizador);
        exit;
    }

    //Email
    $VerificaRepete = $ligacao->prepare("SELECT COUNT(*) FROM Utilizadores WHERE email = :email AND id_utilizador != :id_utilizador");
    $VerificaRepete->bindParam(':email', $email);
    $VerificaRepete->bindParam(':id_utilizador', $id_utilizador);
    $VerificaRepete->execute();
    $existeEmail = $VerificaRepete->fetchColumn();
    if ($existeEmail > 0) {
        //toastr
        $_SESSION['error'] = "Não é possível atualizar o <strong>Email</strong> do desde Utilizador pois este já pertence a outro utilizador.";
        header("Location: editar_utilizador.php?id=" . $id_utilizador);
        exit;
    }

    //Código de Funcionário
    $VerificaRepete = $ligacao->prepare("SELECT COUNT(*) FROM Utilizadores WHERE codigo_funcionario = :codigo_funcionario AND id_utilizador != :id_utilizador");
    $VerificaRepete->bindParam(':codigo_funcionario', $codigo_funcionario);
    $VerificaRepete->bindParam(':id_utilizador', $id_utilizador);
    $VerificaRepete->execute();
    $existeCodigoFuncionario = $VerificaRepete->fetchColumn();
    if ($existeCodigoFuncionario > 0) {
        //toastr
        $_SESSION['error'] = "Não é possível atualizar o <strong>Código de Funcionário</strong> deste Utilizador pois este já pertence a outro utilizador.";
        header("Location: editar_utilizador.php?id=" . $id_utilizador);
        exit;
    }

    //Se não houver dados repetidos, atualiza a BD
    $consulta = $ligacao->prepare("UPDATE Utilizadores SET nome = :nome, username = :username, email = :email, codigo_funcionario = :codigo_funcionario, tipo = :tipo, departamento = :departamento WHERE id_utilizador = :id_utilizador");
    $consulta->bindParam(':nome', $nome);
    $consulta->bindParam(':username', $username);
    $consulta->bindParam(':email', $email);
    $consulta->bindParam(':codigo_funcionario', $codigo_funcionario);
    $consulta->bindParam(':tipo', $tipo);
    $consulta->bindParam(':departamento', $departamento);
    $consulta->bindParam(':id_utilizador', $id_utilizador);
    $consulta->execute();
    
    //toastr
    if ($consulta->execute()) {
        $_SESSION['success'] = "Os dados do utilizador foram atualizados com sucesso!";
    } else {
        $_SESSION['error'] = "Ocorreu um erro ao atualizar os dados do utilizador.";
    }
        
    // Depois de atualizar, volta à lista
    header("Location: lista_utilizadores.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Utilizador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
</head>
        
<body>
    <?php include_once '../navbar_admin.php'; ?>
    <div class="container mt-5">
        <h3 class="text-center  titulo-box">Editar Utilizador</h3>
        <br>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form id="editForm" action="" method="POST">
                            <input type="hidden" name="id_utilizador" value="<?php echo $id_utilizador; ?>">
                            
                                <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($utilizador['nome']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($utilizador['username']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($utilizador['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="codigo_funcionario" class="form-label">Código de Funcionário</label>
                                <input type="text" class="form-control" id="codigo_funcionario" name="codigo_funcionario" value="<?php echo htmlspecialchars($utilizador['codigo_funcionario']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipo" class="form-label">Tipo de Conta</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="Funcionário" <?php echo $utilizador['tipo'] == 'Funcionário' ? 'selected' : ''; ?>>Funcionário</option>
                                    <option value="Técnico" <?php echo $utilizador['tipo'] == 'Técnico' ? 'selected' : ''; ?>>Técnico</option>
                                    <option value="Administrador" <?php echo $utilizador['tipo'] == 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="departamento" class="form-label">Departamento</label>
                                <select class="form-select" id="departamento" name="departamento" required>
                                    <option value="Informático" <?php echo $utilizador['departamento'] == 'Informático' ? 'selected' : ''; ?>>Informático</option>
                                    <option value="Recursos Humanos" <?php echo $utilizador['departamento'] == 'Recursos Humanos' ? 'selected' : ''; ?>>Recursos Humanos</option>
                                    <option value="Marketing" <?php echo $utilizador['departamento'] == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                    <option value="Financeiro" <?php echo $utilizador['departamento'] == 'Financeiro' ? 'selected' : ''; ?>>Financeiro</option>
                                    <option value="Vendas" <?php echo $utilizador['departamento'] == 'Vendas' ? 'selected' : ''; ?>>Vendas</option>
                                    <option value="Apoio ao Cliente" <?php echo $utilizador['departamento'] == 'Apoio ao Cliente' ? 'selected' : ''; ?>>Apoio ao Cliente</option>
                                    <option value="Administrativo" <?php echo $utilizador['departamento'] == 'Administrativo' ? 'selected' : ''; ?>>Administrativo</option>
                                    <option value="Jurídico" <?php echo $utilizador['departamento'] == 'Jurídico' ? 'selected' : ''; ?>>Jurídico</option>
                                </select>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-6 d-grid">
                                     <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmModal">Atualizar</button>
                                </div>
                                <div class="col-sm-6 d-grid">
                                    <a class="btn btn-secondary" href="lista_utilizadores.php" role="button">Cancelar</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirmar Atualização</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">Tem a certeza que deseja atualizar os dados do utilizador?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="document.getElementById('editForm').submit();">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
	<?php include '../../includes/toastr.php'; ?>
    <?php include '../../includes/rodape.php'; ?>
</body>
</html>
