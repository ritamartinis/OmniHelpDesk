<?php
session_start();
include '../includes/db_connection.php';

// Verifica se o utilizador é admin ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Administrador") {
    // Se não for, volta ao index
    header("location: ../index.php");
    exit;
}

// Consultas para obter os dados necessários
// Contas de utilizadores pendentes
$queryContasPendentes = "SELECT COUNT(*) AS total FROM Utilizadores WHERE estado_conta = 'Pendente'";
$resultContasPendentes = $ligacao->query($queryContasPendentes);
$contasPendentes = $resultContasPendentes->fetch(PDO::FETCH_ASSOC)['total'];

// Tickets pendentes de atribuição
$queryTicketsPendentes = "SELECT COUNT(*) AS total FROM Tickets WHERE id_utilizador_responsavel IS NULL";
$resultTicketsPendentes = $ligacao->query($queryTicketsPendentes);
$ticketsPendentes = $resultTicketsPendentes->fetch(PDO::FETCH_ASSOC)['total'];

// Tempo médio de resolução dos tickets para TODOS os técnicos
$queryTempoMedioResolucao = "SELECT AVG(TIMESTAMPDIFF(HOUR, data_criacao, data_encerramento)) AS tempo_medio FROM Tickets WHERE data_encerramento IS NOT NULL";
$consulta = $ligacao->query($queryTempoMedioResolucao);
$tempoMedioResolucao = round($consulta->fetch(PDO::FETCH_ASSOC)['tempo_medio'], 2);


// Número de tickets fechados por técnico
$queryTicketsFechados = "SELECT u.nome AS tecnico, COUNT(t.id_ticket) AS total FROM Utilizadores u 
                         LEFT JOIN Tickets t ON u.id_utilizador = t.id_utilizador_responsavel AND t.estado = 'Fechado' 
                         WHERE u.tipo = 'Técnico' GROUP BY u.nome";
$resultTicketsFechados = $ligacao->query($queryTicketsFechados);
$ticketsFechados = $resultTicketsFechados->fetchAll(PDO::FETCH_ASSOC);

// Dados para o gráfico de estados dos tickets
$queryEstadoTickets = "SELECT estado, COUNT(*) AS total FROM Tickets GROUP BY estado";
$resultEstadoTickets = $ligacao->query($queryEstadoTickets);
$estadoTickets = $resultEstadoTickets->fetchAll(PDO::FETCH_ASSOC);

// Número de tickets reabertos por técnico
$queryTicketsReabertos = "SELECT u.nome AS tecnico, COUNT(t.id_ticket) AS total 
                          FROM Utilizadores u 
                          LEFT JOIN Tickets t ON u.id_utilizador = t.id_utilizador_responsavel AND t.estado = 'Reaberto' 
                          WHERE u.tipo = 'Técnico' 
                          GROUP BY u.nome";
$resultTicketsReabertos = $ligacao->query($queryTicketsReabertos);
$ticketsReabertos = $resultTicketsReabertos->fetchAll(PDO::FETCH_ASSOC);

// Incluir todos os técnicos, mesmo aqueles sem tickets reabertos
$queryTodosTecnicos = "SELECT nome FROM Utilizadores WHERE tipo = 'Técnico'";
$resultTodosTecnicos = $ligacao->query($queryTodosTecnicos);
$todosTecnicos = $resultTodosTecnicos->fetchAll(PDO::FETCH_ASSOC);

$tecnicosReabertos = [];
$ticketsReabertosPorTecnico = [];

foreach ($todosTecnicos as $tecnico) {
    $tecnicoNome = $tecnico['nome'];
    $tecnicosReabertos[] = $tecnicoNome;
    $ticketsReabertosPorTecnico[] = 0; // Inicializar com 0
}

foreach ($ticketsReabertos as $ticket) {
    $index = array_search($ticket['tecnico'], $tecnicosReabertos);
    if ($index !== false) {
        $ticketsReabertosPorTecnico[$index] = $ticket['total'];
    }
}

// Tickets abertos vs fechados por mês durante um ano
$queryTicketsMensal = "SELECT MONTH(data_criacao) AS mes, COUNT(*) AS total_abertos, 
                        (SELECT COUNT(*) FROM Tickets WHERE MONTH(data_encerramento) = mes AND YEAR(data_encerramento) = YEAR(CURRENT_DATE)) AS total_fechados 
                        FROM Tickets WHERE YEAR(data_criacao) = YEAR(CURRENT_DATE) GROUP BY mes";
$resultTicketsMensal = $ligacao->query($queryTicketsMensal);
$ticketsMensal = $resultTicketsMensal->fetchAll(PDO::FETCH_ASSOC);

// Preparar dados para os gráficos
$tecnicos = [];
$ticketsFechadosPorTecnico = [];
foreach ($ticketsFechados as $ticket) {
    $tecnicos[] = $ticket['tecnico'];
    $ticketsFechadosPorTecnico[] = $ticket['total'];
}

$estados = [];
$estadoCounts = [];
foreach ($estadoTickets as $ticket) {
    $estados[] = $ticket['estado'];
    $estadoCounts[] = $ticket['total'];
}

$meses = ['Jan', 'Fev', 'Mar', 'Abril', 'Maio', 'Jun', 'Jul', 'Aug', 'Set', 'Out', 'Nov', 'Dez'];
$ticketsAbertosPorMes = array_fill(0, 12, 0);
$ticketsFechadosPorMes = array_fill(0, 12, 0);
foreach ($ticketsMensal as $ticket) {
    $ticketsAbertosPorMes[$ticket['mes'] - 1] = $ticket['total_abertos'];
    $ticketsFechadosPorMes[$ticket['mes'] - 1] = $ticket['total_fechados'];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área de Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/locale/pt.js"></script>
    <style>
        .small-chart {
            max-width: 480px;
            max-height: 480px;
        }
    </style>
</head>
<body>
    <?php include_once 'navbar_admin.php'; ?>
    <div class="container mt-5">
        <br>
            
         <div class="row">
            <div class="col-md-4">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Contas de Utilizadores Pendentes</h5>
                        <p class="card-text"><?= $contasPendentes ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets Pendentes de Atribuição</h5>
                        <p class="card-text"><?= $ticketsPendentes ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tempo Médio de Resolução de Tickets</h5>
                        <p class="card-text"><?= $tempoMedioResolucao ?> Horas</p>
                    </div>
                </div>
            </div>
        </div>          
        
        <div class="row">
            <div class="col-md-6">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets Fechados por cada Técnico</h5>
                        <canvas id="ticketsFechadosChart"></canvas>
                    </div>
                </div>
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets Reabertos a Técnico</h5>
                        <canvas id="ticketsReabertosChart"></canvas>
                    </div>
                </div>
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets Abertos vs Fechados por Ano</h5>
                        <canvas id="ticketsMensalChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Percentagem de Tickets por Estado</h5>
                        <canvas id="estadoTicketsChart" class="small-chart"></canvas>
                    </div>
                </div>
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Calendário</h5>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Verificar se o código está a ser executado
        console.log("Documento pronto. Inicializando gráficos e calendário.");

        // Gráfico de Tickets Fechados por Técnico
        const ctxTicketsFechados = document.getElementById('ticketsFechadosChart').getContext('2d');
        const ticketsFechadosChart = new Chart(ctxTicketsFechados, {
            type: 'bar',
            data: {
                labels: <?= json_encode($tecnicos) ?>,
                datasets: [{
                    label: 'Tickets Fechados',
                    data: <?= json_encode($ticketsFechadosPorTecnico) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) { return Number(value).toFixed(0); }
                        }
                    }
                }
            }
        });

        // Gráfico de Estado dos Tickets
        const ctxEstadoTickets = document.getElementById('estadoTicketsChart').getContext('2d');
        const estadoTicketsChart = new Chart(ctxEstadoTickets, {
            type: 'pie',
            data: {
                labels: <?= json_encode($estados) ?>,
                datasets: [{
                    label: 'Estado dos Tickets',
                    data: <?= json_encode($estadoCounts) ?>,
                    backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 206, 86, 0.2)', 'rgba(75, 192, 192, 0.2)', 'rgba(153, 102, 255, 0.2)', 'rgba(255, 159, 64, 0.2)'],
                    borderColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)', 'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)', 'rgba(255, 159, 64, 1)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'left',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw + ' (' + Math.round((tooltipItem.raw / <?= array_sum($estadoCounts) ?>) * 100) + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Tickets Reabertos por Técnico
        const ctxTicketsReabertos = document.getElementById('ticketsReabertosChart').getContext('2d');
        const ticketsReabertosChart = new Chart(ctxTicketsReabertos, {
            type: 'bar',
            data: {
                labels: <?= json_encode($tecnicosReabertos) ?>,
                datasets: [{
                    label: 'Tickets Reabertos',
                    data: <?= json_encode($ticketsReabertosPorTecnico) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) { return Number(value).toFixed(0); }
                        }
                    }
                }
            }
        });

        // Gráfico de Tickets Abertos vs Fechados por Mês
        const ctxTicketsMensal = document.getElementById('ticketsMensalChart').getContext('2d');
        const ticketsMensalChart = new Chart(ctxTicketsMensal, {
            type: 'line',
            data: {
                labels: <?= json_encode($meses) ?>,
                datasets: [{
                    label: 'Tickets Abertos',
                    data: <?= json_encode($ticketsAbertosPorMes) ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Tickets Fechados',
                    data: <?= json_encode($ticketsFechadosPorMes) ?>,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Inicializar calendário
        $(document).ready(function() {
            console.log("Inicializando FullCalendar");
            $('#calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                editable: true,
                events: [
                ],
                eventRender: function(event, element) {
                    console.log("Renderizando evento:", event);
                }
            });
        });
    </script>
    <?php include '../includes/rodape.php'; ?>
</body>
</html>
