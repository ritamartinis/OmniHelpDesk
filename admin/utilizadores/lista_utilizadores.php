<?php
session_start();
include '../../includes/db_connection.php';

// Verifica se o utilizador é admin ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Administrador") {
    // Se não for, volta ao index
    header("location: ../../index.php");
    exit;
}

//FILTROS

//Função para ir buscar todos os departamentos, que é um ENUM 
// Se fizesse select, como no tipo, só ia buscar os departamentos que têm colaboradores.
//Com a função, vai buscar todos.
function getEnumValues($ligacao, $table, $field) {
    $consulta = $ligacao->query("SHOW COLUMNS FROM $table LIKE '$field'");
    $result = $consulta->fetch(PDO::FETCH_ASSOC);
    // extrai a string ENUM do campo 'Type' do resultado e remove os apóstrofos
    // substr($result['Type'], 5, (strlen($result['Type']) - 6)) remove os primeiros 5 caracteres ('enum(')
    // e os últimos 6 caracteres (')') da string para obter apenas os valores internos
    // str_replace("'", "", ...) remove os apóstrofos simples da string
    //se não fizesse isso, apareceriam com os '' em cada opção do dropdown
    $enumValues = str_replace("'", "", substr($result['Type'], 5, (strlen($result['Type']) - 6)));
    // divide a string de valores ENUM em um array usando a vírgula como delimitador
    // explode(",", $enumValues) divide a string em um array, onde cada elemento é um dos valores do ENUM   
    return explode(",", $enumValues);
}

// Obter os departamentos diretamente do campo ENUM
$departamentos = getEnumValues($ligacao, 'Utilizadores', 'departamento');

//tipo
$consultaTipos = $ligacao->query("SELECT DISTINCT tipo FROM Utilizadores ORDER BY tipo");
$tipos = $consultaTipos->fetchAll(PDO::FETCH_ASSOC);

//parâmetros do filtro começam vazios
$tipo = $_GET['tipo'] ?? '';
$departamento = $_GET['departamento'] ?? '';
$estado_conta = $_GET['estado_conta'] ?? '';

// Query para ir buscar todas as contas de utilizadores que estejam ativas e desativas
$sql = "SELECT * FROM Utilizadores WHERE estado_conta IN ('Ativa', 'Desativa')";
$params = [];

if (!empty($tipo)) {
    $sql .= " AND tipo = ?";
    $params[] = $tipo;
}

if (!empty($departamento)) {
    $sql .= " AND departamento = ?";
    $params[] = $departamento;
}

if (!empty($estado_conta)) {
    $sql .= " AND estado_conta = ?";
    $params[] = $estado_conta;
}

// Consulta TODOS os dados dos utilizadores para os cards
$consulta = $ligacao->prepare($sql);
$consulta->execute($params);
$utilizadores_ativos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Utilizadores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
</head>
<body>
    <?php include_once '../navbar_admin.php'; ?>
    <div class="container mt-5">
        <h3 class="text-center titulo-box">Lista de Utilizadores</h3>

        <!-- Formulário de Filtros -->
        <div class="filter-icon" onclick="toggleFilter()">
            <i class="fas fa-filter"> Filtrar</i>
        </div>
        <div class="filter-popup" id="myFilter" style="display: none;">
            <form class="row g-3 mb-4" method="get">
                <div class="col-md-2">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select class="form-select" id="tipo" name="tipo">
                        <option value="">Todos</option>
                        <?php foreach ($tipos as $tipoItem): ?>
                            <option value="<?= htmlspecialchars($tipoItem['tipo'] ?? '') ?>"
                                <?= $tipo == $tipoItem['tipo'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tipoItem['tipo'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
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

                <div class="col-md-2">
                    <label for="estado_conta" class="form-label">Estado da Conta</label>
                    <select class="form-select" id="estado_conta" name="estado_conta">
                        <option value="">Todos</option>
                        <option value="Ativa" <?= $estado_conta == 'Ativa' ? 'selected' : '' ?>>Ativa</option>
                        <option value="Desativa" <?= $estado_conta == 'Desativa' ? 'selected' : '' ?>>Desativa</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-success">Aplicar Filtros</button>
                </div>
            </form>
        </div>
        <br>

        <?php if (count($utilizadores_ativos) > 0): ?>
            <div class="d-flex flex-wrap justify-content-center">
                <?php foreach ($utilizadores_ativos as $utilizador): ?>
                    <div class="card m-2 <?php echo ($utilizador['estado_conta'] === 'Desativa') ? 'card-desativa' : ''; ?>" style="width: 18rem;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($utilizador['nome'] ?? ''); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">Username: <?php echo htmlspecialchars($utilizador['username'] ?? ''); ?></h6>
                            <p class="card-text">
                                <strong>Email: </strong><?php echo htmlspecialchars($utilizador['email'] ?? ''); ?><br>
                                <strong>Tipo: </strong><?php echo htmlspecialchars($utilizador['tipo'] ?? ''); ?><br>
                                <strong>Departamento: </strong><?php echo htmlspecialchars($utilizador['departamento'] ?? ''); ?><br>
                                <strong>Código de Funcionário: </strong><?php echo htmlspecialchars($utilizador['codigo_funcionario'] ?? ''); ?><br>
                                <strong>Criada em: </strong><?php echo date('d-m-Y H:i', strtotime($utilizador['criada_em'] ?? '')); ?><br>
                                <strong>Estado da Conta: </strong>
                                <span class="estado_conta <?php echo htmlspecialchars($utilizador['estado_conta'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($utilizador['estado_conta'] ?? ''); ?>
                                </span>
                            <div class="separator"></div>
                            <div class="d-flex justify-content-center">
                                <a href="editar_utilizador.php?id=<?= $utilizador['id_utilizador']; ?>" class="btn btn-primary m-1">Editar</a>
                                <?php if ($utilizador['estado_conta'] !== 'Desativa'): ?>
                                    <button type="button" class="btn btn-warning m-1" data-bs-toggle="modal" data-bs-target="#modalDesativar<?= $utilizador['id_utilizador']; ?>">Desativar</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-success m-1" data-bs-toggle="modal" data-bs-target="#modalReativar<?= $utilizador['id_utilizador']; ?>">Reativar</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de Confirmação de Desativação -->
                    <div class="modal fade" id="modalDesativar<?= $utilizador['id_utilizador']; ?>" tabindex="-1" aria-labelledby="modalDesativarLabel<?= $utilizador['id_utilizador']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalDesativarLabel<?= $utilizador['id_utilizador']; ?>">Confirmar Desativação da Conta</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php
                                    // Verifica se o utilizador é um autor com tickets abertos
                                    $consultaTicketsAutor = $ligacao->prepare("SELECT COUNT(*) FROM Tickets WHERE id_utilizador_autor = :id_utilizador AND estado NOT IN ('Fechado', 'Cancelado')");
                                    $consultaTicketsAutor->bindParam(':id_utilizador', $utilizador['id_utilizador']);
                                    $consultaTicketsAutor->execute();
                                    $ticketsAutor = $consultaTicketsAutor->fetchColumn();

                                    if ($ticketsAutor > 0): ?>
                                        <p>O utilizador tem <strong><?= $ticketsAutor; ?></strong> tickets abertos. Se prosseguir, estes serão colocados no estado 'Cancelado'.</p>
                                    <?php else: ?>
                                        <p>Tem a certeza que pretende desativar a conta de <strong><?= htmlspecialchars($utilizador['nome'] ?? ''); ?></strong>?</p>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <a href="desativar_utilizador.php?id=<?= $utilizador['id_utilizador']; ?>" class="btn btn-success">Confirmar</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de Confirmação de Reativação -->
                    <div class="modal fade" id="modalReativar<?= $utilizador['id_utilizador']; ?>" tabindex="-1" aria-labelledby="modalReativarLabel<?= $utilizador['id_utilizador']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalReativarLabel<?= $utilizador['id_utilizador']; ?>">Confirmar Reativação da Conta</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">Tem a certeza que pretende reativar a conta de <strong><?= htmlspecialchars($utilizador['nome'] ?? ''); ?></strong>?</div>
                                <div class="modal-footer">
                                    <a href="reativar_utilizador.php?id=<?= $utilizador['id_utilizador']; ?>" class="btn btn-success">Confirmar</a>
                                </div>
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
                <p>De momento, não se constatam utilizadores.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../../includes/toastr.php'; ?>
    <?php include '../../includes/rodape.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
</body>
</html>
