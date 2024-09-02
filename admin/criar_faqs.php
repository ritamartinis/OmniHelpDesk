<?php
session_start();
include '../includes/db_connection.php';

// Verifica se o utilizador é admin ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Administrador") {
    // Se não for, volta ao index
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // GRAVA
    $pergunta = $_POST['pergunta']; 
    $resposta = $_POST['resposta'];  
    
    // Verifica se todos os campos obrigatórios estão preenchidos
    if (empty($pergunta) || empty($resposta)) {
        $_SESSION['error'] = 'Para criar uma FAQ, precisa de preencher os campos obrigatórios.';
        header("Location: criar_faqs.php");
        exit;
    }
	
    //insere na bd
    $consulta = $ligacao->prepare("INSERT INTO FAQs (pergunta, resposta) VALUES (:pergunta, :resposta)");
    $consulta->bindParam(':pergunta', $pergunta);
    $consulta->bindParam(':resposta', $resposta);

    if ($consulta->execute()) {
        $_SESSION['success'] = "FAQ criada com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao criar FAQ.";
    }

    header("Location: lista_faqs.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar FAQ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
        <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
</head>

<body>
    <?php include_once 'navbar_admin.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Criar FAQ</h3>
                    </div>
                    <div class="card-body">

                        <!-- Caso haja erros -->
                        <?php if (!empty($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>

                        <form id="faqForm" action="criar_faqs.php" method="POST">
                            <div class="mb-3">
                                <label for="pergunta" class="form-label">Pergunta<span style="color: red;">*</span></label>
                                <input type="text" class="form-control" id="pergunta" name="pergunta" required>
                            </div>
                            <div class="mb-3">
                                <label for="resposta" class="form-label">Resposta<span style="color: red;">*</span></label>
                                <textarea class="form-control" id="resposta" name="resposta" rows="4" required></textarea>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-6 d-grid">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                        data-bs-target="#confirmModal">Criar FAQ</button>
                                </div>
                                <div class="col-sm-6 d-grid">
                                    <a class="btn btn-secondary" href="area_admin.php" role="button">Cancelar</a>
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
                    <h5 class="modal-title" id="confirmModalLabel">Confirmar FAQ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">Tem a certeza que deseja criar esta FAQ?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success"
                        onclick="document.getElementById('faqForm').submit();">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/toastr.php'; ?>
    <?php include '../includes/rodape.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
