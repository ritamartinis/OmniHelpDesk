<?php
// default: em progresso
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'em_progresso';
?>

<div class="btn-group" role="group" aria-label="Filtro de Tickets">
    <a href="?filtro=em_progresso" class="btn <?= $filtro == 'em_progresso' ? 'btn-success' : 'btn-secondary' ?> <?= $filtro == 'em_progresso' ? 'active' : '' ?>">Em Progresso</a>
    <a href="?filtro=concluidos" class="btn <?= $filtro == 'concluidos' ? 'btn-success' : 'btn-secondary' ?> <?= $filtro == 'concluidos' ? 'active' : '' ?>">Concluídos</a>
</div>
<p>
