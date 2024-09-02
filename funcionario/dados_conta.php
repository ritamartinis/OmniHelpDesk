<?php
session_start();
include '../includes/db_connection.php';

// Verifica se o utilizador é funcionário ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Funcionário") {
    header("location: ../index.php");
    exit;
}

// Var para o id_utilizador especifico
$id_utilizador = $_SESSION["id_utilizador"];

// Query para buscar os dados do utilizador
$consultaUtilizador = $ligacao->prepare("SELECT * FROM Utilizadores WHERE id_utilizador = :id_utilizador");
$consultaUtilizador->bindParam(':id_utilizador', $id_utilizador);
$consultaUtilizador->execute();
$utilizador = $consultaUtilizador->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Conta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
</head>
        
<body>
    <?php include_once 'navbar_funcionario.php'; ?>
    <div class="container-fluid">
        <div class="row">
                <div class="container mt-5">
        <h3 class="text-center titulo-box">Dados da Conta</h3>
                <br>
                </div>
                <!-- card dos dados pessoais -->    
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <div class="profile-image mb-3">
                                    <!-- Se tiver foto -->
                                    <?php if (!empty($utilizador['foto_perfil'])): ?>
                                        <img src="<?= $utilizador['foto_perfil'] ?>" alt="Foto de Perfil" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px;">
                                    <?php else: ?>
                                        <!-- Senão é atribuida a default -->
                                        <img src="../includes/fotosdiversas/default.jpg" alt="Foto de Perfil Padrão" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px;">
                                    <?php endif; ?>
                                </div>
                                <h4 class="card-title mb-3"><?php echo htmlspecialchars($utilizador['nome']); ?></h4>
                                <p class="card-text"><strong>Username:</strong> <?php echo htmlspecialchars($utilizador['username']); ?></p>
                                <p class="card-text"><strong>Email:</strong> <?php echo htmlspecialchars($utilizador['email']); ?></p>
                                <p class="card-text"><strong>Código de Funcionário:</strong> <?php echo htmlspecialchars($utilizador['codigo_funcionario']); ?></p>
                                <p class="card-text"><strong>Tipo de Conta:</strong> <?php echo htmlspecialchars($utilizador['tipo']); ?></p>
                                <p class="card-text"><strong>Departamento:</strong> <?php echo htmlspecialchars($utilizador['departamento']); ?></p>
                                <p class="card-text"><strong>Data de Criação:</strong> <?php echo date('d-m-Y H:i', strtotime($utilizador['criada_em'])); ?></p>
                                <p class="card-text"><strong>Estado da Conta:</strong> <?php echo htmlspecialchars($utilizador['estado_conta']); ?></p>
                                <div class="separator"></div>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editarDadosModal">
                                    Editar Dados Pessoais
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
            
    <!-- Modal de Edição -->
    <div class="modal fade" id="editarDadosModal" tabindex="-1" aria-labelledby="editarDadosModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarDadosModalLabel">Editar Dados Pessoais</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editarDadosForm" action="editar_dados_conta.php" method="post" enctype="multipart/form-data">

                        <input type="hidden" name="id_utilizador" value="<?= $utilizador['id_utilizador'] ?>">
                        <!-- Campo para upload de foto -->
                        <div class="mb-3">
                            <label for="foto_perfil" class="form-label">Foto de Perfil:</label>
                            <input type="file" class="form-control" id="foto_perfil" name="foto_perfil">
                            <!-- Mostra a foto atual -->
                            <img src="<?= $utilizador['foto_perfil'] ?>" alt="Foto de Perfil Atual" class="img-thumbnail mt-2" style="width: 100px; height: auto;">
                            <!-- input hidden para manter a foto atual -->
                            <input type="hidden" name="foto_perfil_atual" value="<?= $utilizador['foto_perfil'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo:</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= $utilizador['nome'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username:</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= $utilizador['username'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= $utilizador['email'] ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password:</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="d-flex justify-content-center">
                            <button type="button" class="btn btn-success" onclick="confirmarEdicao()">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
        
    <!-- Modal de Confirmação -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirmar Alterações</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem a certeza que deseja alterar os seus dados pessoais?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="confirmSaveBtn">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/toastr.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarEdicao() {
            var confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            confirmModal.show();
        }

        // Submeter o formulário após confirmação
        document.getElementById('confirmSaveBtn').addEventListener('click', function () {
            document.getElementById('editarDadosForm').submit();
        });
    </script>
</body>
</html>