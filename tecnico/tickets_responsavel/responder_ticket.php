<?php
session_start();
include '../../includes/db_connection.php';
include '../../includes/template_emails.php';

// Verifica se o utilizador é técnico
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Técnico") {
    header("location: ../../index.php");
    exit;
}

// Obtém o ID do ticket a partir da URL
$id_ticket = $_GET['id_ticket'] ?? null;

//var para obter o id do utilizador
$id_utilizador = $_SESSION["id_utilizador"];

//var auxiliar para verificar se o ticket está fechado ou não, que inicia como falsa.
$ticket_fechado = false;

// POST das alterações do técnico
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resposta'])) {
    $resposta = $_POST['resposta'];
    $estado = $_POST['estado'];
    $prioridade = $_POST['prioridade'];

    // Insere a resposta no histórico
    $consultaHistorico = $ligacao->prepare("INSERT INTO HistoricoTickets (id_ticket, id_utilizador, mensagem) VALUES (:id_ticket, :id_utilizador, :mensagem)");
    $consultaHistorico->bindParam(':id_ticket', $id_ticket);
    $consultaHistorico->bindParam(':id_utilizador', $id_utilizador);
    $consultaHistorico->bindParam(':mensagem', $resposta);
    $consultaHistorico->execute();

    // Atualiza o estado e a prioridade na tabela Tickets
    $consultaTicket = $ligacao->prepare("UPDATE Tickets SET estado = :estado, prioridade = :prioridade WHERE id_ticket = :id_ticket");
    $consultaTicket->bindParam(':estado', $estado);
    $consultaTicket->bindParam(':prioridade', $prioridade);
    $consultaTicket->bindParam(':id_ticket', $id_ticket);

    if ($consultaTicket->execute()) {
        if ($estado === 'Fechado') {
            // guarda a data_encerramento do ticket
            $data_encerramento = date('Y-m-d H:i:s');
            $consultaEncerramento = $ligacao->prepare("UPDATE Tickets SET data_encerramento = :data_encerramento WHERE id_ticket = :id_ticket");
            $consultaEncerramento->bindParam(':data_encerramento', $data_encerramento);
            $consultaEncerramento->bindParam(':id_ticket', $id_ticket);
            $consultaEncerramento->execute();
            $ticket_fechado = true;
        }

        // Enviar e-mail para os 3 intervenientes
        // Query para obter o id_utilizador_autor
        $consultaAutor = $ligacao->prepare("SELECT nome, email FROM Utilizadores WHERE id_utilizador = (SELECT id_utilizador_autor FROM Tickets WHERE id_ticket = :id_ticket)");
        $consultaAutor->bindParam(':id_ticket', $id_ticket);
        $consultaAutor->execute();
        $autor = $consultaAutor->fetch(PDO::FETCH_ASSOC);

        // Query para obter o id_utilizador_responsavel
        $consultaTecnico = $ligacao->prepare("SELECT nome, email FROM Utilizadores WHERE id_utilizador = (SELECT id_utilizador_responsavel FROM Tickets WHERE id_ticket = :id_ticket)");
        $consultaTecnico->bindParam(':id_ticket', $id_ticket);
        $consultaTecnico->execute();
        $tecnico = $consultaTecnico->fetch(PDO::FETCH_ASSOC);

       // Query para ir buscar detalhes do ticket para enviar no email
		$consultaTicketDetalhes = $ligacao->prepare("SELECT assunto, descricao, estado, prioridade FROM Tickets WHERE id_ticket = :id_ticket");
		$consultaTicketDetalhes->bindParam(':id_ticket', $id_ticket);
		$consultaTicketDetalhes->execute();
		$ticket = $consultaTicketDetalhes->fetch(PDO::FETCH_ASSOC);

        // E-mails
        $adminEmail = "admin@omnihelpdesk.pt";
        $autorEmail = $autor['email'];
        $tecnicoEmail = $tecnico['email'];
        $nomeAutor = $autor['nome'];
        $nomeTecnico = $tecnico['nome'];
        $assunto = $ticket['assunto'] ?? 'Sem Assunto';
        $descricao = $ticket['descricao'] ?? 'Sem Descrição';
        $estado = $ticket['estado'] ?? 'Sem Estado';
		$prioridade = $ticket['prioridade'] ?? 'Sem Prioridade';

        $subjectAdmin = "Um Ticket foi Atualizado";
        $subjectAutor = "O Seu Ticket foi Atualizado";
        $subjectTecnico = "Atualizou um Ticket";

        $conteudoAdmin = "
        <p>Caro Administrador,</p>
        Serve o presente email para o informamos que o ticket, com o ID <strong>#$id_ticket</strong> foi atualizado.<p/>
        Consulte os detalhes do ticket abaixo:<p/>
        <ul>
            <li><strong>Assunto:</strong> $assunto</li>
            <li><strong>Descrição:</strong> $descricao</li>
            <li><strong>Prioridade:</strong> $prioridade</li>
    		<li><strong>Estado:</strong> $estado</li>
            <li><strong>Técnico Responsável:</strong> $nomeTecnico</li>
            <li><strong>Mensagem do Técnico:</strong> $resposta</li>
            <li><strong>Prioridade:</strong> $prioridade</li>
            <li><strong>Estado:</strong> $estado</li>              
        </ul>";

        $conteudoAutor = "
        <p>Caro(a) $nomeAutor,</p>
        Serve o presente email para o(a) informamos que o seu ticket, com o ID <strong>#$id_ticket</strong> foi atualizado.<p/>
        Consulte os detalhes do ticket abaixo:<p/>
        <ul>
            <li><strong>Assunto:</strong> $assunto</li>
            <li><strong>Descrição:</strong> $descricao</li>
            <li><strong>Prioridade:</strong> $prioridade</li>
    		<li><strong>Estado:</strong> $estado</li>
            <li><strong>Técnico Responsável:</strong> $nomeTecnico</li>
            <li><strong>Mensagem do Técnico:</strong> $resposta</li>      
            <li><strong>Prioridade:</strong> $prioridade</li>
            <li><strong>Estado:</strong> $estado</li>
        </ul>
        <p>Por favor, aceda à sua plataforma para responder ao técnico.</p>";

        $conteudoTecnico = "
        <p>Caro(a) $nomeTecnico,</p>
        Serve o presente email para o(a) informamos que o ticket, com o ID <strong>#$id_ticket</strong> foi atualizado.<p/>
        Consulte os detalhes do ticket abaixo:<p/>
        <ul>
            <li><strong>Assunto:</strong> $assunto</li>
            <li><strong>Descrição:</strong> $descricao</li>
            <li><strong>Prioridade:</strong> $prioridade</li>
    		<li><strong>Estado:</strong> $estado</li>
            <li><strong>O Seu Nome:</strong> $nomeTecnico</li>
            <li><strong>Mensagem que enviou:</strong> $resposta</li>
            <li><strong>Prioridade:</strong> $prioridade</li>
            <li><strong>Estado:</strong> $estado</li>
        </ul>
        <p>Poderá acompanhar todo o processo na nossa plataforma.<br>
        Assim que o utilizador responder, informá-lo(a)-emos via e-mail.</p>
        ";

        $textoBotao = "Entrar na Plataforma";
        $linkBotao = 'https://omnihelpdesk.pt/index.php';
        $messageAdmin = estrutura_emails($subjectAdmin, 'Um Ticket foi Atualizado', $conteudoAdmin, $textoBotao, $linkBotao);
        $messageAutor = estrutura_emails($subjectAutor, 'O Seu Ticket foi Atualizado', $conteudoAutor, $textoBotao, $linkBotao);
        $messageTecnico = estrutura_emails($subjectTecnico, 'Atualizou um Ticket', $conteudoTecnico, $textoBotao, $linkBotao);

        $headers = "From: no-reply@omnihelpdesk.pt\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";

        mail($adminEmail, $subjectAdmin, $messageAdmin, $headers);
        mail($autorEmail, $subjectAutor, $messageAutor, $headers);
        mail($tecnicoEmail, $subjectTecnico, $messageTecnico, $headers);

        // Toastr
        $_SESSION['success'] = 'Ticket atualizado com sucesso';
    } else {
        $_SESSION['error'] = 'Não foi possível atualizar o ticket';
    }

    // Depois de inserir o comentário, deixa-se ficar na página
    header("Location: responder_ticket.php?id_ticket=" . $id_ticket);
    exit;
}

// Query para buscar os dados do utilizador que abriu o ticket
$consultaUtilizador = $ligacao->prepare("SELECT * FROM Utilizadores WHERE id_utilizador = (SELECT id_utilizador_autor FROM Tickets WHERE id_ticket = :id_ticket)");
$consultaUtilizador->bindParam(':id_ticket', $id_ticket);
$consultaUtilizador->execute();
$utilizadorAutor = $consultaUtilizador->fetch(PDO::FETCH_ASSOC);

// Query para buscar os dados do ticket
$consultaTicket = $ligacao->prepare("SELECT * FROM Tickets WHERE id_ticket = :id_ticket");
$consultaTicket->bindParam(':id_ticket', $id_ticket);
$consultaTicket->execute();
$ticket = $consultaTicket->fetch(PDO::FETCH_ASSOC);

// Query para buscar o histórico do ticket
$consultaHistorico = $ligacao->prepare("
    SELECT h.*, u.nome as nome_utilizador, u.foto_perfil, u.id_utilizador
    FROM HistoricoTickets h
    LEFT JOIN Utilizadores u ON h.id_utilizador = u.id_utilizador
    WHERE h.id_ticket = :id_ticket
    ORDER BY h.data_hora ASC
");
$consultaHistorico->bindParam(':id_ticket', $id_ticket);
$consultaHistorico->execute();
$historico = $consultaHistorico->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responder Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css"> 
</head>

<body>
    <?php include_once '../navbar_tecnico.php'; ?>
    <div class="container mt-5">
        <div class="row">

            <!-- Informações do Ticket -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informações do Ticket</h5>
                        <h5 class="card-title"><u>Ticket #<?php echo htmlspecialchars($ticket['id_ticket']); ?></u></h5>
                        <p><strong>Assunto:</strong> <?php echo htmlspecialchars($ticket['assunto']); ?></p>
                        <p><strong>Descrição:</strong> <?php echo htmlspecialchars($ticket['descricao']); ?></p>
                        <p><strong>Departamento Responsável:</strong>
                            <?php echo htmlspecialchars($ticket['departamento_responsavel']); ?></p>

                        <form method="POST" id="respostaForm" class="chat-input-container">
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado:</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="Aguarda Resposta" <?php if ($ticket['estado'] == 'Aguarda Resposta') echo 'selected'; ?>>Aguarda Resposta</option>
                                    <option value="Fechado" <?php if ($ticket['estado'] == 'Fechado') echo 'selected'; ?>>Fechado</option>
                                    <!-- a opção aparece disabled a não ser que o estado seja Reaberto -->
                                    <option value="Reaberto" <?php if ($ticket['estado'] == 'Reaberto') echo 'selected'; ?> <?php if ($ticket['estado'] != 'Reaberto') echo 'disabled'; ?>>Reaberto</option>
                                    <option value="Cancelado" <?php if ($ticket['estado'] == 'Cancelado') echo 'selected'; ?>>Cancelado</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="prioridade" class="form-label">Prioridade:</label>
                                <select class="form-select" id="prioridade" name="prioridade" required>
                                    <option value="Urgente" <?php echo $ticket['prioridade'] == 'Urgente' ? 'selected' : ''; ?>>Urgente</option>
                                    <option value="Alta" <?php echo $ticket['prioridade'] == 'Alta' ? 'selected' : ''; ?>>Alta</option>
                                    <option value="Normal" <?php echo $ticket['prioridade'] == 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                    <option value="Baixa" <?php echo $ticket['prioridade'] == 'Baixa' ? 'selected' : ''; ?>>Baixa</option>
                                </select>
                            </div>
                            <p><strong>Data de Criação:</strong>
                                <?php echo date('d-m-Y H:i', strtotime($ticket['data_criacao'])); ?></p>
                            <p><strong>Data de Encerramento:</strong>
                                <?php echo htmlspecialchars($ticket['data_encerramento'] ? date('d-m-Y H:i', strtotime($ticket['data_encerramento'])) : 'Não Encerrado'); ?>
                            </p>

                            <div class="mb-3">
                                <label for="uploaded_ficheiro" class="form-label">Ficheiro:</label>
                                <?php if (!empty($ticket['uploaded_ficheiro'])): ?>
                                    <a href="../../uploads/<?php echo htmlspecialchars($ticket['uploaded_ficheiro']); ?>" target="_blank" class="btn btn-secondary btn-sm">Abrir noutro separador</a>
                                <?php else: ?>
                                    <p>O Utilizador não anexou nenhum ficheiro.</p>
                                <?php endif; ?>
                            </div>
                            <!-- input escondido para verificar se a alterar do estado é para fechado ou não -->
                            <input type="hidden" id="ticket_fechado" value="<?php echo $ticket['estado'] === 'Fechado' ? 'true' : 'false'; ?>">
                    </div>
                </div>
            </div>

            <!-- Histórico de Comentários e Adicionar Resposta -->
           <div class="col-md-5">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            Histórico de Comentários 
                            <i id="toggleIcon" class="fas fa-chevron-up" onclick="toggleHistorico()" style="cursor: pointer; margin-left: 10px;"></i>
                        </h5>
                        <div id="historicoComentarios" class="chat-container">
                            <?php foreach ($historico as $item): ?>
                                <div class="chat-message <?php echo ($item['id_utilizador'] == $id_utilizador) ? 'right' : ''; ?>">
                                    <img src="<?= !empty($item['foto_perfil']) ? '../../includes/upload_fotosperfil/' . htmlspecialchars(basename($item['foto_perfil'])) : '../../includes/fotosdiversas/default.jpg'; ?>" alt="Foto de Perfil">
                                    <div>
                                        <p class="chat-timestamp"><?php echo date('d/m/Y H:i', strtotime($item['data_hora'])); ?></p>
                                        <div class="chat-bubble <?php echo ($item['id_utilizador'] == $id_utilizador) ? 'right' : ''; ?>">
                                            <p><strong><?php echo htmlspecialchars($item['nome_utilizador']); ?></strong>
                                            <p><?php echo htmlspecialchars($item['mensagem']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-2">
                            <label for="resposta" class="form-label">Adicionar Resposta:</label>
                            <textarea class="form-control custom-textarea" id="resposta" name="resposta" rows="4" placeholder="Adicione a resposta para o utilizador..." required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6 d-grid">
                                <button type="button" class="btn btn-success" onclick="validarResposta()">Submeter Resposta</button>
                            </div>
                            <div class="col-sm-6 d-grid">
                                <a href="tickets_atribuidos.php" class="btn btn-secondary">Voltar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações do Utilizador -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informações do Utilizador</h5>
                        <p><strong>Nome:</strong> <?php echo htmlspecialchars($utilizadorAutor['nome']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($utilizadorAutor['email']); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($utilizadorAutor['username']); ?></p>
                        <p><strong>Código de Funcionário:</strong>
                            <?php echo htmlspecialchars($utilizadorAutor['codigo_funcionario']); ?></p>
                        <p><strong>Tipo:</strong> <?php echo htmlspecialchars($utilizadorAutor['tipo']); ?></p>
                        <p><strong>Departamento:</strong>
                            <?php echo htmlspecialchars($utilizadorAutor['departamento']); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirmar Resposta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">Tem a certeza que deseja submeter esta resposta?</div>
                <div class="modal-footer">
                    <!-- submitForm é a função js abaixo -->
                    <button type="button" class="btn btn-success" onclick="submitForm();">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    <?php include '../../includes/toastr.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        //função para garantir que o campo text da resposta está preenchido 
        //apesar do campo html ser required, o modal ignora essa validação
        //por isso, criei esta função para garantir que o técnico preenche    
        function validarResposta() {
            var resposta = document.getElementById('resposta').value;
            if (resposta.trim() === '') {
                //toastr
                toastr.error('Por favor, preencha o campo da resposta.');
            } else {
                var myModal = new bootstrap.Modal(document.getElementById('confirmModal'));
                myModal.show();
            }
        }

        //submete o form quando o botão do modal de confirmação é confirmado.
        function submitForm() {
            document.getElementById('respostaForm').submit();
        }

        //minimizar o histórico com um icone
        function toggleHistorico() {
            var historico = document.getElementById('historicoComentarios');
            var toggleIcon = document.getElementById('toggleIcon');

            if (historico.style.display === 'none') {
                historico.style.display = 'block';
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-up');
            } else {
                historico.style.display = 'none';
                toggleIcon.classList.remove('fa-chevron-up');
                toggleIcon.classList.add('fa-chevron-down');
            }
        }

        //função para verificar o estado
        function checkEstado() {
            //geta o estado que o técnico escolhe no dropdown
            var estado = document.getElementById('estado').value;
            //verifica pelo id se o ticket está fechado
            var ticketFechado = document.getElementById('ticket_fechado').value === 'true';
            //selecciona alguns elementos 
            var elements = document.querySelectorAll('#respostaForm input, #respostaForm select, #respostaForm textarea, #respostaForm button');

            // Verifica se o ticket está fechado já ou se passa para o estado "Fechado"
            if (ticketFechado || estado === 'Fechado') {
                elements.forEach(function (element) {
                    //dá disabled to elements da linha 342
                    element.disabled = true;
                    element.style.backgroundColor = '#e9ecef';
                });
                // e dá disabled a todos os outros (excepto o botão voltar)
                document.getElementById('resposta').disabled = true;
                document.getElementById('resposta').style.backgroundColor = '#e9ecef';
                document.querySelector('.btn-success').disabled = true;

            } else {
                elements.forEach(function (element) {
                    element.disabled = false;
                    element.style.backgroundColor = '';
                });
            }
        }
        //função para que a função checkEstado é executada assim que o doc HTML estiver carregado
        //porque vamos assentar toda a lógica no estado em que se encontra o ticket
        document.addEventListener('DOMContentLoaded', function () {
            checkEstado();
        });
    </script>
    <?php include '../../includes/rodape.php'; ?>
</body>

</html>
