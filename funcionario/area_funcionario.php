<?php
session_start();
include '../includes/db_connection.php'; 

// Verifica se o utilizador é funcionário ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Funcionário") {
    // Se não for, volta ao index
    header("location: ../index.php");
    exit;
}

// Obter o id do funcionário
$id_funcionario = $_SESSION["id_utilizador"];

// Consultas para obter os dados necessários

// Tickets que abriu
$queryTicketsAbertos = "SELECT COUNT(*) AS total FROM Tickets WHERE id_utilizador_autor = :id_funcionario";
$consulta = $ligacao->prepare($queryTicketsAbertos);
$consulta->bindParam(':id_funcionario', $id_funcionario);
$consulta->execute();
$ticketsAbertos = $consulta->fetch(PDO::FETCH_ASSOC)['total'];

// Tickets ainda por resolver
$queryTicketsPendentes = "SELECT COUNT(*) AS total FROM Tickets WHERE id_utilizador_autor = :id_funcionario AND estado NOT IN ('Fechado', 'Cancelado')";
$consulta = $ligacao->prepare($queryTicketsPendentes);
$consulta->bindParam(':id_funcionario', $id_funcionario);
$consulta->execute();
$ticketsPendentes = $consulta->fetch(PDO::FETCH_ASSOC)['total'];

// Tickets já resolvidos
$queryTicketsResolvidos = "SELECT COUNT(*) AS total FROM Tickets WHERE id_utilizador_autor = :id_funcionario AND estado IN ('Fechado', 'Cancelado')";
$consulta = $ligacao->prepare($queryTicketsResolvidos);
$consulta->bindParam(':id_funcionario', $id_funcionario);
$consulta->execute();
$ticketsResolvidos = $consulta->fetch(PDO::FETCH_ASSOC)['total'];

// Tickets abertos por mês durante um ano
$queryTicketsMensal = "SELECT MONTH(data_criacao) AS mes, COUNT(*) AS total_abertos
                        FROM Tickets WHERE YEAR(data_criacao) = YEAR(CURRENT_DATE) AND id_utilizador_autor = :id_funcionario
                        GROUP BY mes";
$consulta = $ligacao->prepare($queryTicketsMensal);
$consulta->bindParam(':id_funcionario', $id_funcionario);
$consulta->execute();
$ticketsMensal = $consulta->fetchAll(PDO::FETCH_ASSOC);

$meses = ['Jan', 'Fev', 'Mar', 'Abril', 'Maio', 'Jun', 'Jul', 'Aug', 'Set', 'Out', 'Nov', 'Dez'];
$ticketsAbertosPorMes = array_fill(0, 12, 0);
foreach ($ticketsMensal as $ticket) {
    $ticketsAbertosPorMes[$ticket['mes'] - 1] = $ticket['total_abertos'];
}

// Tickets por estado
$queryEstadoTickets = "SELECT estado, COUNT(*) AS total FROM Tickets WHERE id_utilizador_autor = :id_funcionario GROUP BY estado";
$consulta = $ligacao->prepare($queryEstadoTickets);
$consulta->bindParam(':id_funcionario', $id_funcionario);
$consulta->execute();
$estadoTickets = $consulta->fetchAll(PDO::FETCH_ASSOC);

$estados = [];
$estadoCounts = [];
foreach ($estadoTickets as $ticket) {
    $estados[] = $ticket['estado'];
    $estadoCounts[] = $ticket['total'];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área de Funcionário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .small-chart {
            max-width: 480px;
            max-height: 480px;
        }
    </style>
</head>

<body>
    <?php include_once 'navbar_funcionario.php'; ?>
    <div class="container mt-5">
        
        <div class="row">
            <div class="col-md-4">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets Abertos</h5>
                        <p class="card-text"><?= $ticketsAbertos ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets Pendentes</h5>
                        <p class="card-text"><?= $ticketsPendentes ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets Resolvidos</h5>
                        <p class="card-text"><?= $ticketsResolvidos ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Tickets Abertos por Ano</h5>
                        <canvas id="ticketsAbertosPorMesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-light p-3 mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Percentagem de Tickets por Estado</h5>
                        <canvas id="ticketsEstadoChart" class="small-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

      <?php include '../includes/toastr.php'; ?> 
    <script>
        // Gráfico de Tickets Abertos por Mês
        const ctxTicketsAbertosPorMes = document.getElementById('ticketsAbertosPorMesChart').getContext('2d');
        const ticketsAbertosPorMesChart = new Chart(ctxTicketsAbertosPorMes, {
            type: 'line',
            data: {
                labels: <?= json_encode($meses) ?>,
                datasets: [{
                    label: 'Tickets Abertos',
                    data: <?= json_encode($ticketsAbertosPorMes) ?>,
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
        const ctxTicketsEstado = document.getElementById('ticketsEstadoChart').getContext('2d');
        const ticketsEstadoChart = new Chart(ctxTicketsEstado, {
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
    </script>
    <?php include '../includes/rodape.php'; ?>
</body>
</html>

