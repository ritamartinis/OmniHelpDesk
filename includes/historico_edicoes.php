<?php
// Adiciona consulta para buscar o histórico de edições do utilizador
$queryHistorico = $ligacao->prepare("
    SELECT t.id_ticket, t.assunto, h.mensagem, h.data_hora
    FROM HistoricoTickets h
    JOIN Tickets t ON h.id_ticket = t.id_ticket
    WHERE h.id_utilizador = :id_utilizador
    ORDER BY h.data_hora DESC
    LIMIT 5
");
$queryHistorico->bindParam(':id_utilizador', $id_utilizador);
$queryHistorico->execute();
$historico = $queryHistorico->fetchAll(PDO::FETCH_ASSOC);
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
    #historico {
        display: none;
        position: absolute;
        width: 300px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        z-index: 1000;
        border-radius: 5px;
    }

    .historico-box {
        border-radius: 8px;
        padding: 10px;
        background-color: rgba(255, 255, 255, 0.7);
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        color: #000;
        display: inline-block;
        font-size: 18px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 15px;
    }

    .list {
        border-radius: 8px;

    }

    .list-group-item {
        background-color: #ffffffc4 !important;
    }

    .historico-wrapper {
        display: block;
        background: linear-gradient(to bottom, rgb(33, 37, 41), transparent);
        height: 100%;
        padding-right: 20px;
        padding-top: 20px;
        right: 0px;
        top: 75px;
    }
</style>

<div class="filter-icon" onclick="toggleHistorico()">
    <i class="fa-regular fa-hourglass"> Historico</i>
</div>
<div id="historico" class="historico-wrapper">
    <h5 class="historico-box">Histórico de Tickets Editados</h5>
    <div class="list group">
        <?php if (count($historico) > 0): ?>
            <?php foreach ($historico as $registro): ?>
                <a href="responder_ticket.php?id_ticket=<?php echo htmlspecialchars($registro['id_ticket']); ?>"
                    class="list-group-item list-group-item-action">
                    <strong>Ticket #<?php echo htmlspecialchars($registro['id_ticket']); ?>:</strong>
                    <?php echo htmlspecialchars($registro['assunto']); ?><br>
                    <small>Editado em: <?php echo date('d-m-Y H:i', strtotime($registro['data_hora'])); ?></small>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Não há histórico de edições.</p>
        <?php endif; ?>
    </div>
</div>
<br>
<script>
    function toggleHistorico() {
        var historicoDiv = document.getElementById('historico');
        if (historicoDiv.style.display === 'none' || historicoDiv.style.display === '') {
            historicoDiv.style.display = 'block';
        } else {
            historicoDiv.style.display = 'none';
        }
    }
</script>