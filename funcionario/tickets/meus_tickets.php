<?php
session_start();
include '../../includes/db_connection.php';

// Verifica se o utilizador é funcionário ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Funcionário") {
    // Se não for, volta ao index
    header("location: ../../index.php");
    exit;
}

//var para o id_utilizador especifico
$id_utilizador = $_SESSION["id_utilizador"];

// Verifica se o filtro foi selecionado
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'em_progresso';

// Inicializa os parâmetros do FILTRO AVANÇADO
$estado = $_GET['estado'] ?? '';
$prioridade = $_GET['prioridade'] ?? '';
$departamento = $_GET['departamento'] ?? '';
$tecnico = $_GET['tecnico'] ?? '';

// Query para buscar todos os tickets abertos por ESTE funcionário
$sqlBase = "SELECT t.*, u.nome AS nome_tecnico, t.data_encerramento
            FROM Tickets t
            LEFT JOIN Utilizadores u ON t.id_utilizador_responsavel = u.id_utilizador
            WHERE t.id_utilizador_autor = :id_utilizador";

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

if (!empty($tecnico)) {
    $sqlBase .= " AND t.id_utilizador_responsavel = :tecnico";
    $params[':tecnico'] = $tecnico;
}

$consulta = $ligacao->prepare($sqlBase);
$consulta->execute($params);
$tickets_funcionario = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
   
</head>
<body>
    <?php include_once '../navbar_funcionario.php'; ?>
    
        <div class="container mt-5">
        <h3 class="text-center titulo-box">Meus Tickets</h3>
        <?php include '../../includes/filtrogrande_tickets.php'; ?>
        <?php include '../../includes/historico_edicoes.php'; ?>
        <?php include '../../includes/filtropequeno_tickets.php'; ?>
        
        
        <?php if (count($tickets_funcionario) > 0): ?>
            <div class="d-flex flex-wrap justify-content-center">
                <?php foreach ($tickets_funcionario as $ticket): ?>
                    <!-- css para o card dos tickets que estão fechados e cancelados -->
                <div class="card m-2 <?php echo ($ticket['estado'] === 'Fechado') ? 'ticket-fechado' : ''; ?> <?php echo ($ticket['estado'] === 'Cancelado') ? 'ticket-cancelado' : ''; ?>" style="width: 18rem;">
                        <div class="card-body">
                          <div class="d-flex justify-content-between">
                            <h5 class="card-title">Ticket #<?php echo htmlspecialchars($ticket['id_ticket']); ?></h5>
                                  
                            <!-- botão que só aparece se o estado do ticket for Fechado -->
                                <?php if ($ticket['estado'] === 'Fechado'): ?>
                                    <button type="button" class="btn btn-warning btn-sm m-1" onclick="showReabrirTicketModal(<?php echo $ticket['id_ticket']; ?>)">Reabrir</button>
                                <?php endif; ?>
                            </div>
                                
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
                                <strong>Técnico Responsável: </strong><?php echo htmlspecialchars($ticket['nome_tecnico'] ?? 'Por atribuir'); ?><br>
                                <strong>Fechado a: </strong><?php echo $ticket['data_encerramento'] ? date('d-m-Y H:i', strtotime($ticket['data_encerramento'])) : 'Não Encerrado'; ?><br>
                                 
                            </p>
                            <div class="separator"></div>
                            <div class="d-flex justify-content-center">
                                <a href="responder_ticket.php?id_ticket=<?php echo $ticket['id_ticket']; ?>" class="btn btn-secondary btn-sm m-1">Responder</a>
                                <a href="descarregar_ticket.php?id_ticket=<?php echo $ticket['id_ticket']; ?>" class="btn btn-success btn-sm m-1"  target="_blank">Descarregar Ticket</a>
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
                <p>De momento, não consta nenhum ticket.</p>
            </div>
        <?php endif; ?>
    </div>
        
   <!-- Modal para Reabrir o Ticket -->
    <div class="modal fade" id="confirmModalReabrirTicket" tabindex="-1" aria-labelledby="confirmModalReabrirTicketLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalReabrirTicketLabel">Reabrir Ticket</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">Tem a certeza que deseja reabrir este ticket?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" id="confirmReabrirBtn">Confirmar</button>
                </div>
            </div>
        </div>
    </div>  
        
     <?php include '../../includes/toastr.php'; ?> 
     <?php include '../../includes/rodape.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
        <script>
        //var para guardar o id do ticket que será reaberto
        let ticketIdToReopen;

        function showReabrirTicketModal(idTicket) {
            //idTicket seleccionado é o ticketIdToReopen
            ticketIdToReopen = idTicket;
            var reabrirModal = new bootstrap.Modal(document.getElementById('confirmModalReabrirTicket'));
            reabrirModal.show();
        }
		
        //Evento do botão de confirmação - qd é clicado
        document.getElementById('confirmReabrirBtn').addEventListener('click', function() {
            // Realiza uma requisição POST para reabrir o ticket
            var xhr = new XMLHttpRequest();
            //manda para reabir_ticket.php
            xhr.open('POST', 'reabrir_ticket.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        // Sucesso - redireciona para meus_tickets.php
                        window.location.href = 'meus_tickets.php';
                    } else {
                        // Erro - exibe uma mensagem
                        toastr.error('Não foi possível reabrir o ticket.');
                    }
                }
            };
            xhr.send('id_ticket=' + ticketIdToReopen);
        });
    </script>
</body>
</html>
