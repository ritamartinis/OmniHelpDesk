<?php
session_start();
include '../../includes/db_connection.php';
include '../../includes/template_emails.php';

// Verifica se o utilizador é admin
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Administrador") {
    // Se não for, volta ao index
    header("location: ../../index.php");
    exit;
}

// Reatribui o ticket a outro técnico
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_ticket']) && isset($_POST['tecnico_responsavel']) && isset($_POST['departamento_responsavel'])) {
    $id_ticket = $_POST['id_ticket'];
    $id_utilizador_responsavel = $_POST['tecnico_responsavel'];
    $departamento_responsavel = $_POST['departamento_responsavel'];
    $prioridade = $_POST['prioridade'];

    $consulta = $ligacao->prepare("UPDATE Tickets SET id_utilizador_responsavel = :id_utilizador_responsavel, departamento_responsavel = :departamento_responsavel, prioridade = :prioridade WHERE id_ticket = :id_ticket");
    $consulta->bindParam(':id_utilizador_responsavel', $id_utilizador_responsavel);
    $consulta->bindParam(':departamento_responsavel', $departamento_responsavel);
    $consulta->bindParam(':prioridade', $prioridade);
    $consulta->bindParam(':id_ticket', $id_ticket);

    if ($consulta->execute()) {
        // Emails
        $consultaAutor = $ligacao->prepare("SELECT nome, email FROM Utilizadores WHERE id_utilizador = (SELECT id_utilizador_autor FROM Tickets WHERE id_ticket = :id_ticket)");
        $consultaAutor->bindParam(':id_ticket', $id_ticket);
        $consultaAutor->execute();
        $autor = $consultaAutor->fetch(PDO::FETCH_ASSOC);

        $consultaTecnico = $ligacao->prepare("SELECT nome, email FROM Utilizadores WHERE id_utilizador = :id_utilizador_responsavel");
        $consultaTecnico->bindParam(':id_utilizador_responsavel', $id_utilizador_responsavel);
        $consultaTecnico->execute();
        $tecnico = $consultaTecnico->fetch(PDO::FETCH_ASSOC);

        $consultaTicket = $ligacao->prepare("SELECT assunto, descricao FROM Tickets WHERE id_ticket = :id_ticket");
        $consultaTicket->bindParam(':id_ticket', $id_ticket);
        $consultaTicket->execute();
        $ticket = $consultaTicket->fetch(PDO::FETCH_ASSOC);

        $adminEmail = "admin@omnihelpdesk.pt";
        $autorEmail = $autor['email'];
        $tecnicoEmail = $tecnico['email'];
        $nomeAutor = $autor['nome'];
        $nomeTecnico = $tecnico['nome'];
        $assunto = $ticket['assunto'] ?? 'Sem Assunto';
        $descricao = $ticket['descricao'] ?? 'Sem Descrição';

        $subjectAdmin = "Reatribuiu um Ticket a um Técnico";
        $subjectAutor = "O seu Ticket foi Reatribuído a um Técnico";
        $subjectTecnico = "Um Ticket foi-lhe Reatribuído";

        $conteudoAdmin = "
            <p>Caro Administrador,</p>
            Serve o presente email para o informamos que o ticket, com o ID <strong>#$id_ticket</strong> foi reatribuído a um técnico.<p/>
            Consulte os detalhes do ticket abaixo:<p/>
            <ul>
                <li><strong>Assunto:</strong> $assunto</li>
                <li><strong>Descrição:</strong> $descricao</li>
                <li><strong>Departamento Responsável:</strong> $departamento_responsavel</li>
                <li><strong>Técnico Responsável:</strong> $nomeTecnico</li>
                <li><strong>Prioridade:</strong> $prioridade</li>
            </ul>
        ";

        $conteudoAutor = "
            <p>Caro(a) $nomeAutor,</p>
            Serve o presente email para o(a) informarmos que o seu ticket, com o ID <strong>#$id_ticket</strong> foi reatribuído a um técnico.<p/>
            Consulte os detalhes do ticket abaixo:<p/>
            <ul>
                <li><strong>Assunto:</strong> $assunto</li>
                <li><strong>Descrição:</strong> $descricao</li>
                <li><strong>Departamento Responsável:</strong> $departamento_responsavel</li>
                <li><strong>Técnico Responsável:</strong> $nomeTecnico</li>
                <li><strong>Prioridade:</strong> $prioridade</li>
            </ul>     
            <p>Poderá acompanhar todos os detalhes na nossa plataforma.</p>
        ";

        $conteudoTecnico = "
            <p>Caro(a) $nomeTecnico,</p>
            Serve o presente email para o(a) informarmos que um ticket, com o ID <strong>#$id_ticket</strong> foi-lhe reatribuído pelo Administrador.<br>
            Consulte os detalhes do ticket abaixo:<p/>
            <ul>
                <li><strong>Assunto:</strong> $assunto</li>
                <li><strong>Descrição:</strong> $descricao</li>
                <li><strong>Departamento Responsável:</strong> $departamento_responsavel</li>
                <li><strong>Prioridade:</strong> $prioridade</li>
            </ul>  
            <p>Por favor, aceda à sua plataforma para dar início ao processo de resolução.</p>
        ";

        $textoBotao = "Entrar na Plataforma";
        $linkBotao = 'https://omnihelpdesk.pt/index.php';
        $messageAdmin = estrutura_emails($subjectAdmin, 'Reatribuiu um Ticket a um Técnico', $conteudoAdmin, $textoBotao, $linkBotao);
        $messageAutor = estrutura_emails($subjectAutor, 'O seu Ticket foi Reatribuído a um Técnico', $conteudoAutor, $textoBotao, $linkBotao);
        $messageTecnico = estrutura_emails($subjectTecnico, 'Um Ticket foi-lhe Reatribuído', $conteudoTecnico, $textoBotao, $linkBotao);

        $headers = "From: no-reply@omnihelpdesk.pt\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";

        mail($adminEmail, $subjectAdmin, $messageAdmin, $headers);
        mail($autorEmail, $subjectAutor, $messageAutor, $headers);
        mail($tecnicoEmail, $subjectTecnico, $messageTecnico, $headers);

        //toastr
        $_SESSION['success'] = 'Técnico reatribuído com sucesso.';
    } else {
        $_SESSION['error'] = 'Erro ao reatribuir um Técnico.';
    }

    header("Location: lista_tickets.php");
    exit;
}

// Inicializa os parâmetros do FILTRO AVANÇADO
$estado = $_GET['estado'] ?? '';
$prioridade = $_GET['prioridade'] ?? '';
$departamento = $_GET['departamento'] ?? '';
$tecnico = $_GET['tecnico'] ?? '';

// Query base para buscar todos os tickets com id_utilizador_responsavel atribuído
$sqlBase = "SELECT t.*, u.nome AS nome_utilizador, u.email AS email_utilizador, u2.nome AS tecnico_nome, t.departamento_responsavel, t.data_encerramento
            FROM Tickets t
            JOIN Utilizadores u ON t.id_utilizador_autor = u.id_utilizador
            JOIN Utilizadores u2 ON t.id_utilizador_responsavel = u2.id_utilizador
            WHERE t.id_utilizador_responsavel IS NOT NULL";

//Determina o estado do filtro de progresso
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'em_progresso';
if ($filtro == 'em_progresso') {
    $sqlBase .= " AND t.estado NOT IN ('Fechado', 'Cancelado')";
} else {
    $sqlBase .= " AND t.estado IN ('Fechado', 'Cancelado')";
}

// Inicializa o array de parâmetros para a consulta
$params = [];

// Adiciona os filtros avançados à query
if (!empty($estado)) {
    $sqlBase .= " AND t.estado = ?";
    $params[] = $estado;
}

if (!empty($prioridade)) {
    $sqlBase .= " AND t.prioridade = ?";
    $params[] = $prioridade;
}

if (!empty($departamento)) {
    $sqlBase .= " AND t.departamento_responsavel = ?";
    $params[] = $departamento;
}

if (!empty($tecnico)) {
    $sqlBase .= " AND t.id_utilizador_responsavel = ?";
    $params[] = $tecnico;
}

// prepara e executar a consulta
$consulta = $ligacao->prepare($sqlBase);
$consulta->execute($params);
$tickets = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Tickets Atribuídos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
</head>

<body>
    <?php include_once '../navbar_admin.php'; ?>
    <div class="container mt-5">
        <h3 class="text-center titulo-box">Lista de Tickets</h3>

        <!-- filtros -->
        <?php include '../../includes/filtrogrande_tickets.php'; ?>
        <?php include '../../includes/filtropequeno_tickets.php'; ?>

        <?php if (count($tickets) > 0): ?>
            <!-- Lista de Tickets em CARDS -->
            <div class="d-flex flex-wrap justify-content-center">
                <?php foreach ($tickets as $ticket): ?>
                    <!-- css para o card dos tickets que estão fechados e cancelados -->
                    <div class="card m-2 <?php echo ($ticket['estado'] === 'Fechado') ? 'ticket-fechado' : ''; ?> <?php echo ($ticket['estado'] === 'Cancelado') ? 'ticket-cancelado' : ''; ?>"
                        style="width: 18rem;">
                        <div class="card-body">
                            <h5 class="card-title">Ticket #<?php echo htmlspecialchars($ticket['id_ticket']); ?></h5>
                            <h5 class="card-subtitle mb-2 text-muted">Assunto:
                                <?php echo htmlspecialchars($ticket['assunto']); ?></h5>
                            <p class="card-text">
                                <strong>Estado: </strong>
                                <span
                                    class="estado_ticket <?php echo str_replace(' ', '_', htmlspecialchars($ticket['estado'])); ?>">
                                    <?php echo htmlspecialchars($ticket['estado']); ?>
                                </span><br>
                                <strong>Prioridade: </strong>
                                <span
                                    class="prioridade_ticket <?php echo str_replace(' ', '_', htmlspecialchars($ticket['prioridade'])); ?>">
                                    <?php echo htmlspecialchars($ticket['prioridade']); ?>
                                </span><br>
                                <strong>Aberto a:
                                </strong><?php echo date('d-m-Y H:i', strtotime($ticket['data_criacao'])); ?><br>
                                <strong>Departamento:
                                </strong><?php echo htmlspecialchars($ticket['departamento_responsavel']); ?><br>
                                <strong>Técnico Responsável:
                                </strong><?php echo htmlspecialchars($ticket['tecnico_nome']); ?><br>
                                <strong>Fechado a:
                                </strong><?php echo htmlspecialchars($ticket['data_encerramento'] ? date('d-m-Y H:i', strtotime($ticket['data_encerramento'])) : 'Não Encerrado'); ?><br>
                            </p>
                            <div class="separator"></div>
                            <div class="d-flex justify-content-center">
                                <button type="button" class="btn btn-success btn-sm m-1" data-bs-toggle="modal"
                                    data-bs-target="#modalDetalhes<?php echo $ticket['id_ticket']; ?>">Ver Detalhes</button>
                                <!-- botão que só aparece em tickets "Em Progresso" -->
                                <?php if ($ticket['estado'] !== 'Fechado' && $ticket['estado'] !== 'Cancelado'): ?>
                                    <button type="button" class="btn btn-secondary btn-sm m-1" data-bs-toggle="modal"
                                        data-bs-target="#modalReatribuir<?php echo $ticket['id_ticket']; ?>">Reatribuir a outro
                                        Técnico</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de Detalhes -->
                    <div class="modal fade" id="modalDetalhes<?php echo $ticket['id_ticket']; ?>" tabindex="-1"
                        aria-labelledby="modalDetalhesLabel<?php echo $ticket['id_ticket']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalDetalhesLabel<?php echo $ticket['id_ticket']; ?>">Detalhes
                                        do Ticket</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Dados do Utilizador -->
                                    <h6>Dados do Utilizador</h6>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nome_utilizador" class="form-label">Nome Completo</label>
                                            <input type="text" class="form-control" id="nome_utilizador" name="nome_utilizador"
                                                value="<?php echo htmlspecialchars($ticket['nome_utilizador']); ?>" disabled>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email_utilizador" class="form-label">Email </label>
                                            <input type="email" class="form-control" id="email_utilizador"
                                                name="email_utilizador"
                                                value="<?php echo htmlspecialchars($ticket['email_utilizador']); ?>" disabled>
                                        </div>
                                    </div>
                                    <hr>
                                    <!-- Dados do Ticket -->
                                    <h6>Dados do Ticket</h6>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="assunto" class="form-label">Assunto</label>
                                            <input type="text" class="form-control" id="assunto" name="assunto"
                                                value="<?php echo htmlspecialchars($ticket['assunto']); ?>" disabled>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="data_criacao" class="form-label">Data de Criação</label>
                                            <input type="text" class="form-control" id="data_criacao" name="data_criacao"
                                                value="<?php echo htmlspecialchars($ticket['data_criacao']); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="estado" class="form-label">Estado</label>
                                            <input type="text" class="form-control" id="estado" name="estado"
                                                value="<?php echo htmlspecialchars($ticket['estado']); ?>" disabled>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="prioridade" class="form-label">Prioridade</label>
                                            <input type="text" class="form-control" id="prioridade" name="prioridade"
                                                value="<?php echo htmlspecialchars($ticket['prioridade']); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="descricao" class="form-label">Descrição</label>
                                        <textarea class="form-control" id="descricao" name="descricao" rows="4"
                                            disabled><?php echo htmlspecialchars($ticket['descricao']); ?></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="departamento_responsavel" class="form-label">Departamento
                                                Responsável</label>
                                            <input type="text" class="form-control" id="departamento_responsavel"
                                                name="departamento_responsavel"
                                                value="<?php echo htmlspecialchars($ticket['departamento_responsavel']); ?>"
                                                disabled>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="tecnico_responsavel" class="form-label">Técnico Responsável</label>
                                            <input type="text" class="form-control" id="tecnico_responsavel"
                                                name="tecnico_responsavel"
                                                value="<?php echo htmlspecialchars($ticket['tecnico_nome']); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="data_encerramento" class="form-label">Data de Encerramento</label>
                                            <input type="text" class="form-control" id="data_encerramento"
                                                name="data_encerramento"
                                                value="<?php echo htmlspecialchars($ticket['data_encerramento'] ? date('d-m-Y H:i', strtotime($ticket['data_encerramento'])) : 'Não Encerrado'); ?>"
                                                disabled>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="uploaded_ficheiro" class="form-label">Ficheiro</label>
                                            <?php if (!empty($ticket['uploaded_ficheiro'])): ?>
                                                <a href="../../uploads/<?php echo htmlspecialchars($ticket['uploaded_ficheiro']); ?>"
                                                    target="_blank" class="btn btn-secondary btn-sm">Abrir noutro separador</a>

                                            <?php else: ?>
                                                <p>O Utilizador não anexou nenhum ficheiro.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de Reatribuição -->
                    <div class="modal fade" id="modalReatribuir<?php echo $ticket['id_ticket']; ?>" tabindex="-1"
                        aria-labelledby="modalReatribuirLabel<?php echo $ticket['id_ticket']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalReatribuirLabel<?php echo $ticket['id_ticket']; ?>">
                                        Reatribuir Ticket a um Técnico</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="formReatribuir<?php echo $ticket['id_ticket']; ?>" action="lista_tickets.php"
                                        method="POST">
                                        <input type="hidden" name="id_ticket" value="<?php echo $ticket['id_ticket']; ?>">
                                        <div class="mb-3">
                                            <label for="departamento_responsavel" class="form-label">Departamento
                                                Responsável</label>
                                            <select class="form-select"
                                                id="departamento_responsavel<?php echo $ticket['id_ticket']; ?>"
                                                name="departamento_responsavel" required>
                                                <option value="" disabled selected>Escolha um departamento</option>
                                                <option value="Informático" <?php echo $ticket['departamento_responsavel'] == 'Informático' ? 'selected' : ''; ?>>
                                                    Informático</option>
                                                <option value="Recursos Humanos" <?php echo $ticket['departamento_responsavel'] == 'Recursos Humanos' ? 'selected' : ''; ?>>Recursos Humanos</option>
                                                <option value="Marketing" <?php echo $ticket['departamento_responsavel'] == 'Marketing' ? 'selected' : ''; ?>>
                                                    Marketing</option>
                                                <option value="Financeiro" <?php echo $ticket['departamento_responsavel'] == 'Financeiro' ? 'selected' : ''; ?>>
                                                    Financeiro</option>
                                                <option value="Vendas" <?php echo $ticket['departamento_responsavel'] == 'Vendas' ? 'selected' : ''; ?>>Vendas</option>
                                                <option value="Apoio ao Cliente" <?php echo $ticket['departamento_responsavel'] == 'Apoio ao Cliente' ? 'selected' : ''; ?>>Apoio ao Cliente</option>
                                                <option value="Administrativo" <?php echo $ticket['departamento_responsavel'] == 'Administrativo' ? 'selected' : ''; ?>>
                                                    Administrativo</option>
                                                <option value="Jurídico" <?php echo $ticket['departamento_responsavel'] == 'Jurídico' ? 'selected' : ''; ?>>
                                                    Jurídico</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="tecnico_responsavel<?php echo $ticket['id_ticket']; ?>"
                                                class="form-label">Técnico Responsável</label>
                                            <select class="form-select"
                                                id="tecnico_responsavel<?php echo $ticket['id_ticket']; ?>"
                                                name="tecnico_responsavel" required>
                                                <option value="" disabled selected>Escolha um técnico</option>
                                                <!-- Os técnicos serão carregados via AJAX em função do departamento escolhido -->
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="prioridade" class="form-label">Prioridade</label>
                                            <select class="form-select" id="prioridade" name="prioridade" required>
                                                <option value="Urgente" <?php echo $ticket['prioridade'] == 'Urgente' ? 'selected' : ''; ?>>Urgente</option>
                                                <option value="Alta" <?php echo $ticket['prioridade'] == 'Alta' ? 'selected' : ''; ?>>Alta</option>
                                                <option value="Normal" <?php echo $ticket['prioridade'] == 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                                <option value="Baixa" <?php echo $ticket['prioridade'] == 'Baixa' ? 'selected' : ''; ?>>Baixa</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                        data-bs-target="#modalConfirmReatribuir<?php echo $ticket['id_ticket']; ?>">Confirmar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de Confirmação de Reatribuição -->
                    <div class="modal fade" id="modalConfirmReatribuir<?php echo $ticket['id_ticket']; ?>" tabindex="-1"
                        aria-labelledby="modalConfirmReatribuirLabel<?php echo $ticket['id_ticket']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalConfirmReatribuirLabel<?php echo $ticket['id_ticket']; ?>">
                                        Confirmar Reatribuição do Ticket</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">Tem a certeza que pretende reatribuir o ticket?</div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-success"
                                        onclick="document.getElementById('formReatribuir<?php echo $ticket['id_ticket']; ?>').submit();">Confirmar</button>
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
                <p>De momento, não constam tickets.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/toastr.php'; ?>
    <?php include '../../includes/rodape.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- js e fetch para carregar dinamicamente os técnicos de acordo c/o departamento seleccionado pelo admin sem ser necessário recarregar a página inteira -->
    <script>
        // Vai seleccionar todos os elementos cujo id começa com "departamento_responsavel"
        document.querySelectorAll('[id^="departamento_responsavel"]').forEach(function (departamentoSelect) {
            // adiciona um "eventolistener" p/ o evento 'change' em cada um dos seleccionados
            departamentoSelect.addEventListener('change', function () {
                // extrai o id do ticket a partir do id do select
                const idTicket = this.id.replace('departamento_responsavel', '');
                // selecciona o elemento select correspondente p/os tecnicos
                const tecnicoSelect = document.getElementById('tecnico_responsavel' + idTicket);
                // enquanto está a carregar apareceu este html
                tecnicoSelect.innerHTML = '<option value="" disabled selected>A carregar...</option>';

                // Faz uma requisição AJAX, obtendo o departamento seleccionado 
                fetch('tickets_pendentes.php?departamento=' + this.value)
                    .then(response => response.json()) // converte a resposta em json
                    .then(data => {
                        // mensagem que aparece inicialmente no dropdown
                        tecnicoSelect.innerHTML = '<option value="" disabled selected>Escolha um técnico</option>';
                        // para cada técnico recebido na resposta, cria uma nova opção no dropdown dos técnicos
                        data.tecnicos.forEach(tecnico => {
                            // const option é para guardar um novo element option
                            const option = document.createElement('option');
                            option.value = tecnico.id_utilizador;
                            option.textContent = tecnico.nome;
                            // Adiciona a nova opção ao select dos técnicos
                            tecnicoSelect.appendChild(option);
                        });
                    });
            });
        });
    </script>
</body>

</html>