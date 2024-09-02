<?php
session_start();
include '../../includes/db_connection.php';
include '../../includes/template_emails.php';

// Verifica se o utilizador é funcionário ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Funcionário") {
    // Se não for, volta ao index
    header("location: ../../index.php");
    exit;
}

// Obtém o ID do ticket a partir da URL
$id_ticket = $_GET['id_ticket'] ?? null;

//var para o id_utilizador especifico
$id_utilizador = $_SESSION["id_utilizador"];

//POST das alterações do funcionário
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resposta'])) {
    $resposta = $_POST['resposta'];
    
    // Insere a resposta no histórico
    $consultaHistorico = $ligacao->prepare("INSERT INTO HistoricoTickets (id_ticket, id_utilizador, mensagem) VALUES (:id_ticket, :id_utilizador, :mensagem)");
    $consultaHistorico->bindParam(':id_ticket', $id_ticket);
    $consultaHistorico->bindParam(':id_utilizador', $id_utilizador);
    $consultaHistorico->bindParam(':mensagem', $resposta);
    $consultaHistorico->execute();

    if ($consultaHistorico->rowCount() > 0) {
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
        $respostaUtilizador = $resposta;
            
        $subjectAdmin = "Um Ticket foi Atualizado pelo Utilizador";
        $subjectAutor = "Atualizou o seu Ticket";
        $subjectTecnico = "Um Ticket foi Atualizado pelo Utilizador";
            
        $conteudoAdmin = "
        <p>Caro Administrador,</p>
        Serve o presente email para o informamos que o ticket, com o ID <strong>#$id_ticket</strong> foi atualizado pelo utilizador.<p/>
        Consulte os detalhes do ticket abaixo:<p/>
        <ul>
            <li><strong>Assunto:</strong> $assunto</li>
            <li><strong>Descrição:</strong> $descricao</li>
            <li><strong>Prioridade:</strong> $prioridade</li>
    		<li><strong>Estado:</strong> $estado</li>
            <li><strong>Utilizador:</strong> $nomeAutor</li>
            <li><strong>Mensagem do Utilizador:</strong> $respostaUtilizador</li>
        </ul>";

        $conteudoTecnico = "
        <p>Caro(a) $nomeTecnico,</p>
        Serve o presente email para o(a) informamos que o ticket, com o ID <strong>#$id_ticket</strong> foi atualizado pelo utilizador.<p/>
        Consulte os detalhes do ticket abaixo:<p/>
        <ul>
            <li><strong>Assunto:</strong> $assunto</li>
            <li><strong>Descrição:</strong> $descricao</li>
            <li><strong>Prioridade:</strong> $prioridade</li>
    		<li><strong>Estado:</strong> $estado</li>
            <li><strong>Utilizador:</strong> $nomeAutor</li>
            <li><strong>Mensagem do Utilizador:</strong> $respostaUtilizador</li>
        </ul>
        <p>Por favor, aceda à sua plataforma para responder ao utilizador.</p>";
            
        $conteudoAutor = "
        <p>Caro(a) $nomeAutor,</p>
        Serve o presente email para o(a) informamos que o seu ticket, com o ID <strong>#$id_ticket</strong> foi atualizado.<p/>
        Consulte os detalhes do ticket abaixo:<p/>
        <ul>
            <li><strong>Assunto:</strong> $assunto</li>
            <li><strong>Descrição:</strong> $descricao</li>
            <li><strong>Prioridade:</strong> $prioridade</li>
    		<li><strong>Estado:</strong> $estado</li>
            <li><strong>O Seu Nome:</strong> $nomeAutor</li>
            <li><strong>Mensagem que enviou:</strong> $respostaUtilizador</li> 
        </ul>
       <p>Poderá acompanhar todo o processo na nossa plataforma.<br>
        Assim que o técnico responder, informá-lo(a)-emos via e-mail.</p>";


        $textoBotao = "Entrar na Plataforma";
        $linkBotao = 'https://omnihelpdesk.pt/index.php';
        $messageAdmin = estrutura_emails($subjectAdmin, 'Atualizou o seu Ticket', $conteudoAdmin, $textoBotao, $linkBotao);
        $messageTecnico = estrutura_emails($subjectTecnico, 'Um Ticket foi Atualizado pelo Utilizador', $conteudoTecnico, $textoBotao, $linkBotao);
        $messageAutor = estrutura_emails($subjectAutor, 'Atualizou o seu Ticket', $conteudoAutor, $textoBotao, $linkBotao);
            
        $headers = "From: no-reply@omnihelpdesk.pt\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";

        mail($adminEmail, $subjectAdmin, $messageAdmin, $headers);
        mail($tecnicoEmail, $subjectTecnico, $messageTecnico, $headers);
        mail($autorEmail, $subjectAutor, $messageAutor, $headers);

        //toastr
        $_SESSION['success'] = 'Resposta enviada com sucesso.';
    } else {
        $_SESSION['error'] = 'Não foi possível enviar a resposta.';
    }

    // Depois de inserir o comentário, deixa-se ficar na página, mostrando o comentário
    header("Location: /funcionario/tickets/responder_ticket.php?id_ticket=" . $id_ticket);
    exit;
}

// Query para buscar os dados do utilizador que abriu o ticket
$consultaUtilizador = $ligacao->prepare("SELECT * FROM Utilizadores WHERE id_utilizador = :id_utilizador");
$consultaUtilizador->bindParam(':id_utilizador', $id_utilizador);
$consultaUtilizador->execute();
$utilizador = $consultaUtilizador->fetch(PDO::FETCH_ASSOC);

// Query para buscar os dados do técnico responsável
$consultaTecnico = $ligacao->prepare("SELECT nome, email, username, codigo_funcionario, tipo, departamento FROM Utilizadores WHERE id_utilizador = (SELECT id_utilizador_responsavel FROM Tickets WHERE id_ticket = :id_ticket)");
$consultaTecnico->bindParam(':id_ticket', $id_ticket);
$consultaTecnico->execute();
$tecnico = $consultaTecnico->fetch(PDO::FETCH_ASSOC);

//o ticket que o id_utilizador_autor está a ver pode ou n ter técnico atribuido
$tecnicoAtribuido = $tecnico ? true : false;

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
    <?php include_once '../navbar_funcionario.php'; ?>
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
                        <p><strong>Departamento Responsável:</strong> <?php echo htmlspecialchars($ticket['departamento_responsavel']); ?></p>
                        <p><strong>Estado:</strong> <?php echo htmlspecialchars($ticket['estado']); ?></p>
                        <p><strong>Prioridade:</strong> <?php echo htmlspecialchars($ticket['prioridade']); ?></p>
                        <p><strong>Data de Criação:</strong> <?php echo date('d-m-Y H:i', strtotime($ticket['data_criacao'])); ?></p>   
                         <p><strong>Data de Encerramento:</strong>
                                <?php echo htmlspecialchars($ticket['data_encerramento'] ? date('d-m-Y H:i', strtotime($ticket['data_encerramento'])) : 'Não Encerrado'); ?></p>
                            
                        <div class="mb-3">
                            <label for="uploaded_ficheiro" class="form-label"><strong>Ficheiro:</strong></label>
                            <?php if (!empty($ticket['uploaded_ficheiro'])): ?>
                                <a href="../../uploads/<?php echo htmlspecialchars($ticket['uploaded_ficheiro']); ?>" target="_blank" class="btn btn-secondary btn-sm">Abrir noutro separador</a>
                            <?php else: ?>
                                <p>Não anexou nenhum ficheiro.</p>
                            <?php endif; ?>
                        </div>
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
                            
                        <form method="POST" id="respostaForm" class="chat-input-container">
                            <div class="mb-2">
                                <label for="resposta" class="form-label">Adicionar Resposta:</label>
                                                            <textarea class="form-control custom-textarea" id="resposta" name="resposta" rows="4" placeholder="Adicione a resposta para o técnico..." required></textarea>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-6 d-grid">
                                     <button type="button" class="btn btn-success" onclick="validarResposta()">Submeter Resposta</button>
                                </div>
                                <div class="col-sm-6 d-grid">
                                    <a href="meus_tickets.php" class="btn btn-secondary">Voltar</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Informações do Técnico -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informações do Técnico</h5>
                        <?php if ($tecnico): ?>
                            <p><strong>Nome:</strong> <?php echo htmlspecialchars($tecnico['nome']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($tecnico['email']); ?></p>
                            <p><strong>Username:</strong> <?php echo htmlspecialchars($tecnico['username']); ?></p>
                            <p><strong>Código de Funcionário:</strong> <?php echo htmlspecialchars($tecnico['codigo_funcionario']); ?></p>
                            <p><strong>Tipo:</strong> <?php echo htmlspecialchars($tecnico['tipo']); ?></p>
                            <p><strong>Departamento:</strong> <?php echo htmlspecialchars($tecnico['departamento']); ?></p>
                        <?php else: ?>
                            <p>Ainda não se encontra nenhum técnico atribuído a este ticket.</p>
                        <?php endif; ?>
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
                    <button type="button" class="btn btn-success" onclick="document.getElementById('respostaForm').submit();">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    <?php include '../../includes/toastr.php'; ?>
    <?php include '../../includes/rodape.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para garantir que o campo texto da resposta está preenchido 
        // Apesar do campo HTML ser required, o modal ignora essa validação
        // Por isso, criei esta função para garantir que o técnico preenche
        function validarResposta() {
            var resposta = document.getElementById('resposta').value;
            // Para ver se o ticket já tem técnico atribuído e proibir que o id_utilizador_autor possa responder se não há
            var tecnicoAtribuido = <?php echo $tecnicoAtribuido ? 'true' : 'false'; ?>;
            if (!tecnicoAtribuido) {
                toastr.error('Não é possível responder a este ticket pois ainda não tem técnico atribuído.');
                return;
            }
            if (resposta.trim() === '') {
                // Toastr
                toastr.error('Por favor, preencha o campo da resposta.');
            } else {
                var myModal = new bootstrap.Modal(document.getElementById('confirmModal'));
                myModal.show();
            }
        }
       
        // Minimizar o histórico com um ícone
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

        // Função para verificar o estado
        function checkEstado() {
            var estado = '<?php echo $ticket['estado']; ?>';
            var elements = document.querySelectorAll('#respostaForm input, #respostaForm select, #respostaForm textarea, #respostaForm button');
            
            elements.forEach(function(element) {
                if (estado === 'Fechado') {
                    element.disabled = true;
                    element.style.backgroundColor = '#e9ecef';
                }
            });
        }

        // Verificar o estado ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            checkEstado();
        });

    </script>
    <?php include '../../includes/rodape.php'; ?>
</body>
</html>