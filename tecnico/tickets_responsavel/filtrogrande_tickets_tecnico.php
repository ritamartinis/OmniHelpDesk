<?php
include '../../includes/db_connection.php';

//função para remover aspas simples de cada valor ENUM
function removerPlicas($value) {
    return str_replace("'", "", $value);
}

// Obter todos os departamentos do ENUM da tabela Utilizadores
$consultaDepartamentos = $ligacao->query("SHOW COLUMNS FROM Utilizadores LIKE 'departamento'");
$resultado = $consultaDepartamentos->fetch(PDO::FETCH_ASSOC);
$departamentos = array_map('removerPlicas', str_getcsv(trim($resultado['Type'], "enum()")));

// Obter todos os utilizadores (porque todos podem ser id_utilizador_autor) da tabela Utilizadores - mesmo com conta desativa.
$consultaAutores = $ligacao->query("SELECT id_utilizador, nome FROM Utilizadores ORDER BY nome");
$autores = $consultaAutores->fetchAll(PDO::FETCH_ASSOC);

// Obter todos os estados do ENUM da tabela Tickets
$consultaEstados = $ligacao->query("SHOW COLUMNS FROM Tickets LIKE 'estado'");
$resultadoEstados = $consultaEstados->fetch(PDO::FETCH_ASSOC);
$estados = array_map('removerPlicas', str_getcsv(trim($resultadoEstados['Type'], "enum()")));

// Obter todas as prioridades do ENUM da tabela Tickets
$consultaPrioridades = $ligacao->query("SHOW COLUMNS FROM Tickets LIKE 'prioridade'");
$resultadoPrioridades = $consultaPrioridades->fetch(PDO::FETCH_ASSOC);
$prioridades = array_map('removerPlicas', str_getcsv(trim($resultadoPrioridades['Type'], "enum()")));

// Inicializa os parâmetros do FILTRO
$estado = $_GET['estado'] ?? '';
$prioridade = $_GET['prioridade'] ?? '';
$departamento = $_GET['departamento'] ?? '';
$autor = $_GET['autor'] ?? '';
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<div class="filter-icon" onclick="toggleFilter()">
    <i class="fas fa-filter"> Filtrar</i>
</div>
<div class="filter-popup" id="myFilter" style="display: none;">
    <form class="row g-3 mb-4" method="get">
        <div class="col-md-3">
            <label for="estado" class="form-label">Estado</label>
            <select class="form-select" id="estado" name="estado">
                <option value="">Todos</option>
                <?php foreach ($estados as $estadoItem): ?>
                    <option value="<?= htmlspecialchars($estadoItem) ?>"
                        <?= $estado == $estadoItem ? 'selected' : '' ?>>
                        <?= htmlspecialchars($estadoItem) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="prioridade" class="form-label">Prioridade</label>
            <select class="form-select" id="prioridade" name="prioridade">
                <option value="">Todos</option>
                <?php foreach ($prioridades as $prioridadeItem): ?>
                    <option value="<?= htmlspecialchars($prioridadeItem) ?>"
                        <?= $prioridade == $prioridadeItem ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prioridadeItem) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="departamento" class="form-label">Departamento</label>
            <select class="form-select" id="departamento" name="departamento">
                <option value="">Todos</option>
                <?php foreach ($departamentos as $departamentoItem): ?>
                    <option value="<?= htmlspecialchars($departamentoItem) ?>"
                        <?= $departamento == $departamentoItem ? 'selected' : '' ?>>
                        <?= htmlspecialchars($departamentoItem) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="autor" class="form-label">Autor</label>
            <select class="form-select" id="autor" name="autor">
                <option value="">Todos</option>
                <?php foreach ($autores as $autorItem): ?>
                    <option value="<?= htmlspecialchars($autorItem['id_utilizador']) ?>"
                        <?= $autor == $autorItem['id_utilizador'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($autorItem['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-success">Aplicar Filtros</button>
        </div>
    </form>
</div>
<br>
<script>
    function toggleFilter() {
        var filterPopup = document.getElementById("myFilter");
        if (filterPopup.style.display === "none") {
            filterPopup.style.display = "block";
        } else {
            filterPopup.style.display = "none";
        }
    }
</script>
