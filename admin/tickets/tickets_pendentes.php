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

//Requisição AJAX para getar o departamento
//Se houver o parametro 'departamento' no url, geta os técnicos desse departamento e retorna os dados em json
if (isset($_GET['departamento'])) {
    $departamento = $_GET['departamento'];

    //Só vai buscar os técnicos que tenham conta ativa
    $consulta = $ligacao->prepare("SELECT id_utilizador, nome FROM Utilizadores WHERE tipo = 'Técnico' AND departamento = :departamento AND estado_conta = 'Ativa'");
    $consulta->bindParam(':departamento', $departamento);
    $consulta->execute();
    $tecnicos = $consulta->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['tecnicos' => $tecnicos]);
    exit;
}

//POST - Atribui técnico a um ticket && o departamento_responsavel
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_ticket']) && isset($_POST['tecnico_responsavel']) && isset($_POST['departamento_responsavel'])) {
    $id_ticket = $_POST['id_ticket'];
    $id_utilizador_responsavel = $_POST['tecnico_responsavel'];
    $departamento_responsavel = $_POST['departamento_responsavel'];
    $estado = "Em Progresso"; //o ticket é criado e fica 'Aberto' como default
    //qd é atribuido, fica "Em Progresso" como default, o admin não tem outra opção ao atribuir
    $prioridade = $_POST['prioridade'];

    $consulta = $ligacao->prepare("UPDATE Tickets SET id_utilizador_responsavel = :id_utilizador_responsavel, departamento_responsavel = :departamento_responsavel, estado = :estado, prioridade = :prioridade WHERE id_ticket = :id_ticket");
    $consulta->bindParam(':id_utilizador_responsavel', $id_utilizador_responsavel);
    $consulta->bindParam(':departamento_responsavel', $departamento_responsavel);
    $consulta->bindParam(':estado', $estado);
    $consulta->bindParam(':prioridade', $prioridade);
    $consulta->bindParam(':id_ticket', $id_ticket);


    if ($consulta->execute()) {
        //enviar e-mail para os 3 intervenientes

        //query para obter o id_utilizador_autor
        $consultaAutor = $ligacao->prepare("SELECT nome, email FROM Utilizadores WHERE id_utilizador = (SELECT id_utilizador_autor FROM Tickets WHERE id_ticket = :id_ticket)");
        $consultaAutor->bindParam(':id_ticket', $id_ticket);
        $consultaAutor->execute();
        $autor = $consultaAutor->fetch(PDO::FETCH_ASSOC);

        //query para obter o id_utilizador_responsavel
        $consultaTecnico = $ligacao->prepare("SELECT nome, email FROM Utilizadores WHERE id_utilizador = :id_utilizador_responsavel");
        $consultaTecnico->bindParam(':id_utilizador_responsavel', $id_utilizador_responsavel);
        $consultaTecnico->execute();
        $tecnico = $consultaTecnico->fetch(PDO::FETCH_ASSOC);

        //query para ir buscar detalhes do ticket para enviar no email
        $consultaTicket = $ligacao->prepare("SELECT assunto, descricao FROM Tickets WHERE id_ticket = :id_ticket");
        $consultaTicket->bindParam(':id_ticket', $id_ticket);
        $consultaTicket->execute();
        $ticket = $consultaTicket->fetch(PDO::FETCH_ASSOC);

        //e-mails
        $adminEmail = "admin@omnihelpdesk.pt";
        $autorEmail = $autor['email'];
        $tecnicoEmail = $tecnico['email'];
        $nomeAutor = $autor['nome'];
        $nomeTecnico = $tecnico['nome'];
        $assunto = $ticket['assunto'] ?? 'Sem Assunto';
        $descricao = $ticket['descricao'] ?? 'Sem Descrição';

        $subjectAdmin = "Atribuiu um Ticket a um Técnico";
        $subjectAutor = "O seu Ticket foi Atribuído a um Técnico";
        $subjectTecnico = "Um Novo Ticket foi-lhe Atribuído";

        $conteudoAdmin = "
        <p>Caro Administrador,</p>
        Serve o presente email para o informamos que ticket, com o ID <strong>#$id_ticket</strong> foi, por si, atribuído a um técnico.<p/>
        Consulte os detalhes do tickets abaixo:<p/>
        <ul>
                <li><strong>Assunto:</strong> $assunto</li>
                <li><strong>Descrição:</strong> $descricao</li>
                <li><strong>Departamento Responsável:</strong> $departamento_responsavel</li>
                <li><strong>Técnico Responsável:</strong> $nomeTecnico</li>
                <li><strong>Prioridade:</strong> $prioridade</li>
                <li><strong>Estado:</strong> $estado</li>              
            </ul>
            ";

        $conteudoAutor = "
        <p>Caro(a) $nomeAutor,</p>
        Serve o presente email para o(a) informamos que o seu ticket, com o ID <strong>#$id_ticket</strong> foi atribuído a um técnico.<p/>
        Consulte os detalhes do tickets abaixo:<p/>
        <ul>
                <li><strong>Assunto:</strong> $assunto</li>
                <li><strong>Descrição:</strong> $descricao</li>
                <li><strong>Departamento Responsável:</strong> $departamento_responsavel</li>
                <li><strong>Técnico Responsável:</strong> $nomeTecnico</li>
                <li><strong>Prioridade:</strong> $prioridade</li>
                <li><strong>Estado:</strong> $estado</li>
            </ul>     
        <p>Poderá acompanhar todos os detalhes na nossa plataforma.</p>
    ";

        $conteudoTecnico = "
    <p>Caro(a) $nomeTecnico,</p>
        Serve o presente email para o(a) informamos um novo ticket, com o ID <strong>#$id_ticket</strong> foi-lhe atribuído pelo Administrador.<br>
        Consulte os detalhes do tickets abaixo:<p/>
        <ul>
                <li><strong>Assunto:</strong> $assunto</li>
                <li><strong>Descrição:</strong> $descricao</li>
                <li><strong>Departamento Responsável:</strong> $departamento_responsavel</li>
                <li><strong>Técnico Responsável:</strong> $nomeTecnico</li>
                <li><strong>Prioridade:</strong> $prioridade</li>
                <li><strong>Estado:</strong> $estado</li>    
            </ul>  
        <p>Por favor, aceda à sua plataforma para dar início ao processo de resolução.</p>
    ";

        $textoBotao = "Entrar na Plataforma";
        $linkBotao = 'https://omnihelpdesk.pt/index.php';
        $messageAdmin = estrutura_emails($subjectAdmin, 'Atribuiu um Ticket a um Técnico', $conteudoAdmin, $textoBotao, $linkBotao);
        $messageAutor = estrutura_emails($subjectAutor, 'O seu Ticket foi Atribuído a um Técnico', $conteudoAutor, $textoBotao, $linkBotao);
        $messageTecnico = estrutura_emails($subjectTecnico, 'Um Novo Ticket foi-lhe Atribuído', $conteudoTecnico);

        $headers = "From: no-reply@omnihelpdesk.pt\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";

        mail($adminEmail, $subjectAdmin, $messageAdmin, $headers);
        mail($autorEmail, $subjectAutor, $messageAutor, $headers);
        mail($tecnicoEmail, $subjectTecnico, $messageTecnico, $headers);

        //toastr
        $_SESSION['success'] = 'Técnico atribuído com sucesso ao ticket.';
    } else {
        $_SESSION['error'] = 'Erro ao atribuir o técnico ao ticket.';
    }

    //continua aqui, em vez de ir para a lista_tickets.php porque pode querer atribuir mais
    header("Location: tickets_pendentes.php");
    exit;
}

//Query para ir buscar todos os tickets sem técnico atribuido (aka id_utilizador_responsavel is NULL)
//Só entram na pág dos pendentes aqueles que o admin ainda não atribuiu
//Query conjunta para ir buscar tb aos Utilizadores o nome e o email 

//t.* significa a tabela Tickets e selecciona o campo nome "u.nome" e email "u.email"
//e renomeia para nome_utilizador e email_utilizador
//"join" significa que é uma query conjunta
//u significa a tabela Utilizadores
//on t.id_utilizador_autor = u.id_utilizador significa que iguala o id_utilizador dos Utilizadores ao id_utilizador_autor dos Tickets
//E no fim da query, o mais importante, onde ainda não há técnico atribuido
$consulta = $ligacao->prepare("SELECT t.*, u.nome AS nome_utilizador, u.email AS email_utilizador 
                               FROM Tickets t 
                               JOIN Utilizadores u ON t.id_utilizador_autor = u.id_utilizador 
                               WHERE t.id_utilizador_responsavel IS NULL");
$consulta->execute();
$tickets_pendentes = $consulta->fetchAll(PDO::FETCH_ASSOC);

//Se um ticket tiver pendente mas já tiver CANCELADO
// Deleta um ticket
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_ticket_elimina'])) {
    $id_ticket = $_POST['id_ticket_elimina'];

    $eliminaTicket = $ligacao->prepare("DELETE FROM Tickets WHERE id_ticket = :id_ticket");
    $eliminaTicket->bindParam(':id_ticket', $id_ticket);

    if ($eliminaTicket->execute()) {
        $_SESSION['success'] = 'Ticket eliminado com sucesso.';
    } else {
        $_SESSION['error'] = 'Erro ao eliminar o ticket.';
    }

    header("Location: tickets_pendentes.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets Pendentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">

</head>

<body>
    <?php include_once '../navbar_admin.php'; ?>

    <div class="container mt-5">
        <h3 class="text-center titulo-box">Tickets Pendentes</h3>
        <br>

        <div class="d-flex flex-wrap justify-content-center">
            <?php if (count($tickets_pendentes) > 0): ?>
                
                <?php foreach ($tickets_pendentes as $ticket): ?>
                    <!-- Tickets pendentes em CARDS -->
                    <!-- css se for um card cancelado -->
                    <div class="card m-2 <?php echo ($ticket['estado'] === 'Fechado') ? 'ticket-fechado' : ''; ?> <?php echo ($ticket['estado'] === 'Cancelado') ? 'ticket-cancelado' : ''; ?>"
                        style="width: 18rem;">
                        <div class="card-body">
                            <h5 class="card-title">Ticket #<?php echo htmlspecialchars($ticket['id_ticket']); ?></h5>
                            <h5 class="card-subtitle mb-2 text-muted">Assunto:
                                <?php echo htmlspecialchars($ticket['assunto']); ?>
                            </h5>
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
                            </p>
                            <div class="separator"></div>
                            <div class="d-flex justify-content-center">
                                <button type="button" class="btn btn-success btn-sm m-1" data-bs-toggle="modal"
                                    data-bs-target="#modalDetalhes<?php echo $ticket['id_ticket']; ?>">Ver Detalhes</button>

                                <?php if ($ticket['estado'] !== 'Cancelado'): ?>
                                    <button type="button" class="btn btn-secondary btn-sm m-1" data-bs-toggle="modal"
                                        data-bs-target="#modalAtribuir<?php echo $ticket['id_ticket']; ?>">Atribuir a um
                                        Técnico</button>
                                <?php else: ?> <!-- aka, se o ticket já chegar como cancelado -->
                                    <button type="button" class="btn btn-danger btn-sm m-1" data-bs-toggle="modal"
                                        data-bs-target="#modalEliminar<?php echo $ticket['id_ticket']; ?>">Eliminar Ticket</button>
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
                                    <div class="mb-3">
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

                    <!-- Modal de Atribuição -->
                    <div class="modal fade" id="modalAtribuir<?php echo $ticket['id_ticket']; ?>" tabindex="-1"
                        aria-labelledby="modalAtribuirLabel<?php echo $ticket['id_ticket']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalAtribuirLabel<?php echo $ticket['id_ticket']; ?>">Atribuir
                                        Ticket a um Técnico</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="formAtribuir<?php echo $ticket['id_ticket']; ?>" action="tickets_pendentes.php"
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
                                        data-bs-target="#modalConfirmAtribuir<?php echo $ticket['id_ticket']; ?>">Salvar</button>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Modal de Confirmação de Atribuição -->
                    <div class="modal fade" id="modalConfirmAtribuir<?php echo $ticket['id_ticket']; ?>" tabindex="-1"
                        aria-labelledby="modalConfirmAtribuirLabel<?php echo $ticket['id_ticket']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalConfirmAtribuirLabel<?php echo $ticket['id_ticket']; ?>">
                                        Confirmar Atribuição do Ticket</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">Tem a certeza que pretende atribuir o ticket?</div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-success"
                                        onclick="document.getElementById('formAtribuir<?php echo $ticket['id_ticket']; ?>').submit();">Confirmar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de Eliminação -->
                    <div class="modal fade" id="modalEliminar<?php echo $ticket['id_ticket']; ?>" tabindex="-1"
                        aria-labelledby="modalEliminarLabel<?php echo $ticket['id_ticket']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalEliminarLabel<?php echo $ticket['id_ticket']; ?>">Confirmar
                                        Eliminação do Ticket</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">Tem a certeza que pretende eliminar o ticket?</div>
                                <div class="modal-footer">
                                    <form action="tickets_pendentes.php" method="POST">
                                        <input type="hidden" name="id_ticket_elimina"
                                            value="<?php echo $ticket['id_ticket']; ?>">
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
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
                <p>De momento, não constam tickets pendentes.</p>
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