<?php
session_start();
include '../includes/db_connection.php'; 

// Verifica se o utilizador é técnico ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Técnico") {
    // Se não for, volta ao index
    header("location: ../index.php");
    exit;
}

// Obter o id do técnico
$id_tecnico = $_SESSION["id_utilizador"];


// Tickets pendentes de resolução
$queryTicketsPendentesResolucao = "SELECT COUNT(*) AS total FROM Tickets WHERE id_utilizador_responsavel = :id_tecnico AND estado NOT IN ('Fechado', 'Cancelado')";
$consulta = $ligacao->prepare($queryTicketsPendentesResolucao);
$consulta->bindParam(':id_tecnico', $id_tecnico);
$consulta->execute();
$ticketsPendentesResolucao = $consulta->fetch(PDO::FETCH_ASSOC)['total'];

// Tickets já resolvidos
$queryTicketsResolvidos = "SELECT COUNT(*) AS total FROM Tickets WHERE id_utilizador_responsavel = :id_tecnico AND estado IN ('Fechado', 'Cancelado')";
$consulta = $ligacao->prepare($queryTicketsResolvidos);
$consulta->bindParam(':id_tecnico', $id_tecnico);
$consulta->execute();
$ticketsResolvidos = $consulta->fetch(PDO::FETCH_ASSOC)['total'];

// Tempo médio de resolução dos tickets por mês
$queryTempoMedioResolucaoMes = "SELECT MONTH(data_encerramento) AS mes, AVG(TIMESTAMPDIFF(HOUR, data_criacao, data_encerramento)) AS tempo_medio 
                                FROM Tickets 
                                WHERE id_utilizador_responsavel = :id_tecnico 
                                AND data_encerramento IS NOT NULL 
                                AND YEAR(data_encerramento) = YEAR(CURRENT_DATE) 
                                GROUP BY mes";

$consulta = $ligacao->prepare($queryTempoMedioResolucaoMes);
$consulta->bindParam(':id_tecnico', $id_tecnico);
$consulta->execute();
$tempoMedioResolucaoMes = $consulta->fetchAll(PDO::FETCH_ASSOC);

$tempoMedioPorMes = array_fill(0, 12, 0);
foreach ($tempoMedioResolucaoMes as $resolucao) {
    $tempoMedioPorMes[$resolucao['mes'] - 1] = round($resolucao['tempo_medio'], 2);
}

// Tickets abertos por mês para este técnico
$queryTicketsAbertosMes = "SELECT MONTH(data_criacao) AS mes, COUNT(*) AS total FROM Tickets WHERE id_utilizador_responsavel = :id_tecnico AND YEAR(data_criacao) = YEAR(CURRENT_DATE) GROUP BY mes";
$consulta = $ligacao->prepare($queryTicketsAbertosMes);
$consulta->bindParam(':id_tecnico', $id_tecnico);
$consulta->execute();
$ticketsAbertosMes = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Tickets resolvidos por mês para este técnico
$queryTicketsResolvidosMes = "SELECT MONTH(data_encerramento) AS mes, COUNT(*) AS total FROM Tickets WHERE id_utilizador_responsavel = :id_tecnico AND estado IN ('Fechado', 'Cancelado') AND YEAR(data_encerramento) = YEAR(CURRENT_DATE) GROUP BY mes";
$consulta = $ligacao->prepare($queryTicketsResolvidosMes);
$consulta->bindParam(':id_tecnico', $id_tecnico);
$consulta->execute();
$ticketsResolvidosMes = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Tickets por estado para este técnico
$queryTicketsPorEstado = "SELECT estado, COUNT(*) AS total FROM Tickets WHERE id_utilizador_responsavel = :id_tecnico GROUP BY estado";
$consulta = $ligacao->prepare($queryTicketsPorEstado);
$consulta->bindParam(':id_tecnico', $id_tecnico);
$consulta->execute();
$ticketsPorEstado = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Preparar dados para os gráficos
$meses = ['Jan', 'Fev', 'Mar', 'Abril', 'Maio', 'Jun', 'Jul', 'Aug', 'Set', 'Out', 'Nov', 'Dez'];

$ticketsAbertosPorMes = array_fill(0, 12, 0);
foreach ($ticketsAbertosMes as $ticket) {
    $ticketsAbertosPorMes[$ticket['mes'] - 1] = $ticket['total'];
}

$ticketsResolvidosPorMes = array_fill(0, 12, 0);
foreach ($ticketsResolvidosMes as $ticket) {
    $ticketsResolvidosPorMes[$ticket['mes'] - 1] = $ticket['total'];
}

$estados = [];
$estadoCounts = [];
foreach ($ticketsPorEstado as $ticket) {
    $estados[] = $ticket['estado'];
    $estadoCounts[] = $ticket['total'];
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área de Técnico</title>
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
    <?php include_once 'navbar_tecnico.php'; ?>
    <div class="container mt-5">

        <div class="row">
            <div class="col-md-6">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets Pendentes de Resolução</h5>
                        <p class="card-text"><?= $ticketsPendentesResolucao ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets Já Resolvidos</h5>
                        <p class="card-text"><?= $ticketsResolvidos ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tempo Médio de Resolução (Horas)</h5>
                        <canvas id="tempoMedioResolucaoChart"></canvas>
                    </div>
                </div>
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets Abertos por Mês</h5>
                        <canvas id="ticketsAbertosMesChart"></canvas>
                    </div>
                </div>
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets Resolvidos por Mês</h5>
                        <canvas id="ticketsResolvidosMesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets por Estado</h5>
                        <canvas id="ticketsPorEstadoChart" class="small-chart"></canvas>
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
        // Gráfico de Tempo Médio de Resolução
        const ctxTempoMedioResolucao = document.getElementById('tempoMedioResolucaoChart').getContext('2d');
        const tempoMedioResolucaoChart = new Chart(ctxTempoMedioResolucao, {
            type: 'line',
            data: {
                labels: <?= json_encode($meses) ?>,
                datasets: [{
                    label: 'Tempo Médio de Resolução (Horas)',
                    data: <?= json_encode($tempoMedioPorMes) ?>,
                    borderColor: 'rgba(255, 159, 64, 1)',
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
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

        // Gráfico de Tickets Abertos por Mês
        const ctxTicketsAbertosMes = document.getElementById('ticketsAbertosMesChart').getContext('2d');
        const ticketsAbertosMesChart = new Chart(ctxTicketsAbertosMes, {
            type: 'bar',
            data: {
                labels: <?= json_encode($meses) ?>,
                datasets: [{
                    label: 'Tickets Abertos',
                    data: <?= json_encode($ticketsAbertosPorMes) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
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

        // Gráfico de Tickets Resolvidos por Mês
        const ctxTicketsResolvidosMes = document.getElementById('ticketsResolvidosMesChart').getContext('2d');
        const ticketsResolvidosMesChart = new Chart(ctxTicketsResolvidosMes, {
            type: 'bar',
            data: {
                labels: <?= json_encode($meses) ?>,
                datasets: [{
                    label: 'Tickets Resolvidos',
                    data: <?= json_encode($ticketsResolvidosPorMes) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
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

        // Gráfico de Tickets por Estado
        const ctxTicketsPorEstado = document.getElementById('ticketsPorEstadoChart').getContext('2d');
        const ticketsPorEstadoChart = new Chart(ctxTicketsPorEstado, {
            type: 'pie',
            data: {
                labels: <?= json_encode($estados) ?>,
                datasets: [{
                    label: 'Tickets por Estado',
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

		//jquery para o calendário
        $(document).ready(function() {
            $('#calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                editable: true,
                events: [
                    {
                        title: 'Tickets Pendentes de Resolução',
                        start: '<?= date("Y-m-d") ?>'
                    },
                    {
                        title: 'Tickets Já Resolvidos',
                        start: '<?= date("Y-m-d") ?>'
                    }
                ]
            });
        });
    </script>
    <?php include '../includes/rodape.php'; ?>
</body>
</html>
