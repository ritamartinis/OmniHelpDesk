<?php
session_start();
include '../includes/db_connection.php';

// Verifica se o utilizador é admin ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Administrador") {
    header("Location: ../index.php");
    exit;
}

// Verifica se foi seleccionada a edição e dá post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editFAQ'])) {
    $id_faq = $_POST['id_faq'];
    $pergunta = $_POST['pergunta'];
    $resposta = $_POST['resposta'];
    $data_atualizacao = date('Y-m-d H:i:s');

    $consulta = $ligacao->prepare("UPDATE FAQs SET pergunta = :pergunta, resposta = :resposta, data_atualizacao = :data_atualizacao WHERE id_faq = :id_faq");
    $consulta->bindParam(':pergunta', $pergunta);
    $consulta->bindParam(':resposta', $resposta);
    $consulta->bindParam(':data_atualizacao', $data_atualizacao);
    $consulta->bindParam(':id_faq', $id_faq);

    if ($consulta->execute()) {
        $_SESSION['success'] = 'FAQ atualizada com sucesso.';
    } else {
        $_SESSION['error'] = 'Erro ao atualizar a FAQ.';
    }
    header("Location: lista_faqs.php");
    exit;
}

// Verifica se foi seleccionada a edição e dá post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deleteFAQ'])) {
    $id_faq = $_POST['id_faq'];

    $consulta = $ligacao->prepare("DELETE FROM FAQs WHERE id_faq = :id_faq");
    $consulta->bindParam(':id_faq', $id_faq);

    if ($consulta->execute()) {
        $_SESSION['success'] = 'FAQ eliminada com sucesso.';
    } else {
        $_SESSION['error'] = 'Erro ao eliminar a FAQ.';
    }
    header("Location: lista_faqs.php");
    exit;
}

//se houver uma pesquisa, realiza
$pesquisa = '';
if (isset($_GET['pesquisa'])) {
    $pesquisa = $_GET['pesquisa'];
    $consulta = $ligacao->prepare("SELECT * FROM FAQs WHERE pergunta LIKE :pesquisa OR resposta LIKE :pesquisa");
    $pesquisaParam = '%' . $pesquisa . '%';
    $consulta->bindParam(':pesquisa', $pesquisaParam);
} else {
    $consulta = $ligacao->prepare("SELECT * FROM FAQs");
}

$consulta->execute();
$faqs = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de FAQs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include 'navbar_admin.php'; ?>
    <div class="container mt-5">
        <h3 class="text-center titulo-box">Lista de FAQs</h3>
        <br>

        <!-- Barra de Pesquisa -->
        <form method="GET" class="search-container d-flex justify-content-center mb-4">
            <input type="text" class="form-control rounded-start" placeholder="Pesquisar..." name="pesquisa" value="<?= htmlspecialchars($pesquisa) ?>" style="border-color: #32cd32; max-width: 600px;">
            <button class="btn btn-success rounded-end" type="submit" style="border-color: #32cd32;">Pesquisar</button>
        </form>

        <!-- FAQs com accordion do bootstrap -->
        <div class="accordion" id="faqAccordion">
            <?php foreach ($faqs as $index => $faq): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header d-flex justify-content-between align-items-center" id="heading<?= $index ?>">
                        <button class="accordion-button collapsed flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                            <?= htmlspecialchars($faq['pergunta']) ?>
                        </button>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#editModal" onclick="editFAQ(<?= htmlspecialchars(json_encode($faq)) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#deleteModal" onclick="deleteFAQ(<?= htmlspecialchars(json_encode($faq)) ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </h2>
                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <?= htmlspecialchars($faq['resposta']) ?>
                            <div class="accordion-footer text-muted mt-3">
                                <small>Data de Criação: <?= htmlspecialchars($faq['data_criacao']) ?></small><br>
                                <small>Última Atualização: <?= htmlspecialchars($faq['data_atualizacao']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php include '../includes/toastr.php'; ?>
    <?php include '../includes/rodape.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modal editar-->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar FAQ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editFAQForm" method="POST" action="">
                        <input type="hidden" name="id_faq" id="editFAQId">
                        <input type="hidden" name="editFAQ" value="true">
                        <div class="mb-3">
                            <label for="editPergunta" class="form-label">Pergunta</label>
                            <input type="text" class="form-control" id="editPergunta" name="pergunta" required>
                        </div>
                        <div class="mb-3">
                            <label for="editResposta" class="form-label">Resposta</label>
                            <textarea class="form-control" id="editResposta" name="resposta" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Confirmar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal eliminar-->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Eliminar FAQ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem a certeza que deseja eliminar esta FAQ?
                </div>
                <div class="modal-footer">
                    <form id="deleteFAQForm" method="POST" action="">
                        <input type="hidden" name="id_faq" id="deleteFAQId">
                        <input type="hidden" name="deleteFAQ" value="true">
                        <button type="submit" class="btn btn-success">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editFAQ(faq) {
            document.getElementById('editFAQId').value = faq.id_faq;
            document.getElementById('editPergunta').value = faq.pergunta;
            document.getElementById('editResposta').value = faq.resposta;
        }

        function deleteFAQ(faq) {
            document.getElementById('deleteFAQId').value = faq.id_faq;
        }
    </script>
</body>
</html>
