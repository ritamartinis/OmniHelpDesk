<?php
session_start();
include '../includes/db_connection.php';

// Verifica se o utilizador é técnico ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Técnico") {
    // Se não for, volta ao index
    header("Location: ../index.php");
    exit;
}

//se houver pesquisa, select da tabela
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
    <title>FAQs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
</head>
<body>
    <?php include 'navbar_tecnico.php'; ?>
    <div class="container mt-5">
        <h3 class="text-center titulo-box">FAQs</h3>
        <br>

          <!-- Barra de Pesquisa -->
        <form method="GET" class="search-container d-flex justify-content-center mb-4">
            <input type="text" class="form-control rounded-start" placeholder="Pesquisar..." name="pesquisa" value="<?= htmlspecialchars($pesquisa) ?>" style="border-color: #32cd32; max-width: 600px;">
            <button class="btn btn-success rounded-end" type="submit" style="border-color: #32cd32;">Pesquisar</button>
        </form>

        <!-- FAQs com accordion do boot -->
        <div class="accordion" id="faqAccordion">
            <?php foreach ($faqs as $index => $faq): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $index ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                            <?= htmlspecialchars($faq['pergunta']) ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <?= htmlspecialchars($faq['resposta']) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php include '../includes/rodape.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
