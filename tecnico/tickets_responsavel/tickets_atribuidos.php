<?php
session_start();
include '../../includes/db_connection.php'; 

// Verifica se o utilizador é técnico ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Técnico") {
    // Se não for, volta ao index
    header("location: ../../index.php");
    exit;
}

//var para o id_utilizador especifico
$id_utilizador = $_SESSION["id_utilizador"];

$consultaUtilizador = $ligacao->prepare("SELECT * FROM Utilizadores WHERE id_utilizador = :id_utilizador");
$consultaUtilizador->bindParam(':id_utilizador', $id_utilizador);
$consultaUtilizador->execute();
$utilizador = $consultaUtilizador->fetch(PDO::FETCH_ASSOC);

// Verifica se o filtro foi selecionado
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'em_progresso';

// Inicializa os parâmetros do FILTRO AVANÇADO
$estado = $_GET['estado'] ?? '';
$prioridade = $_GET['prioridade'] ?? '';
$departamento = $_GET['departamento'] ?? '';
$autor = $_GET['autor'] ?? '';

// Ajusta a query com base no filtro selecionado
$sqlBase = "SELECT t.*, u.nome AS nome_autor
            FROM Tickets t
            LEFT JOIN Utilizadores u ON t.id_utilizador_autor = u.id_utilizador
            WHERE t.id_utilizador_responsavel = :id_utilizador";

if ($filtro == 'em_progresso') {
    $sqlBase .= " AND t.estado NOT IN ('Fechado', 'Cancelado')";
} else {
    $sqlBase .= " AND t.estado IN ('Fechado', 'Cancelado')";
}

// Inicializa o array de parâmetros para a consulta
$params = [':id_utilizador' => $id_utilizador];

// Adiciona os filtros avançados à query
if (!empty($estado)) {
    $sqlBase .= " AND t.estado = :estado";
    $params[':estado'] = $estado;
}

if (!empty($prioridade)) {
    $sqlBase .= " AND t.prioridade = :prioridade";
    $params[':prioridade'] = $prioridade;
}

if (!empty($departamento)) {
    $sqlBase .= " AND t.departamento_responsavel = :departamento";
    $params[':departamento'] = $departamento;
}

if (!empty($autor)) {
    $sqlBase .= " AND t.id_utilizador_autor = :autor";
    $params[':autor'] = $autor;
}

// Preparar e executar a consulta
$consulta = $ligacao->prepare($sqlBase);
$consulta->execute($params);
$tickets_atribuidos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets Atribuídos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
</head>
<body>
    <?php include_once '../navbar_tecnico.php'; ?>
    <div class="container mt-5">
        <h3 class="text-center titulo-box">Tickets Atribuídos</h3>
        <?php include 'filtrogrande_tickets_tecnico.php'; ?>    
        <?php include '../../includes/historico_edicoes.php'; ?>
        <?php include '../../includes/filtropequeno_tickets.php'; ?>
        

        <?php if (count($tickets_atribuidos) > 0): ?>
            
            <div class="d-flex flex-wrap justify-content-center">
                <?php foreach ($tickets_atribuidos as $ticket): ?>
                    <!-- css para o card dos tickets que estão fechados e cancelados -->
                <div class="card m-2 <?php echo ($ticket['estado'] === 'Fechado') ? 'ticket-fechado' : ''; ?> <?php echo ($ticket['estado'] === 'Cancelado') ? 'ticket-cancelado' : ''; ?>" style="width: 18rem;">
                        <div class="card-body">
                            <h5 class="card-title">Ticket #<?php echo htmlspecialchars($ticket['id_ticket']); ?></h5>
                            <h5 class="card-subtitle mb-2 text-muted">Assunto: <?php echo htmlspecialchars($ticket['assunto']); ?></h5>
                            <p class="card-text">
                                <strong>Estado: </strong>
                            <span class="estado_ticket <?php echo str_replace(' ', '_', htmlspecialchars($ticket['estado'])); ?>">
                                <?php echo htmlspecialchars($ticket['estado']); ?>
                            </span><br>
                             <strong>Prioridade: </strong>
                        <span class="prioridade_ticket <?php echo str_replace(' ', '_', htmlspecialchars($ticket['prioridade'])); ?>">
                            <?php echo htmlspecialchars($ticket['prioridade']); ?>
                        </span><br>
                                <strong>Aberto a: </strong><?php echo date('d-m-Y H:i', strtotime($ticket['data_criacao'])); ?><br>
                                <strong>Departamento: </strong><?php echo htmlspecialchars($ticket['departamento_responsavel']); ?><br>
                                <strong>Autor: </strong><?php echo htmlspecialchars($ticket['nome_autor']); ?><br>
                                <strong>Fechado a: </strong><?php echo $ticket['data_encerramento'] ? date('d-m-Y H:i', strtotime($ticket['data_encerramento'])) : 'Não Encerrado'; ?><br>
                            </p>
                            <div class="separator"></div>
                            <div class="d-flex justify-content-center">
                                <a href="responder_ticket.php?id_ticket=<?php echo $ticket['id_ticket']; ?>" class="btn btn-secondary btn-sm m-1">Responder</a>
                                <a href="../tickets_pessoais/descarregar_ticket.php?id_ticket=<?php echo $ticket['id_ticket']; ?>" class="btn btn-success btn-sm m-1" target="_blank">Descarregar Ticket</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center">
                <img src="/includes/fotosdiversas/erro.png" alt="No results">
            </div>
            <div class="text-center error-box">
                <p>Boas notícias! De momento, não constam tickets.</p>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../../includes/rodape.php'; ?>
</body>
</html>