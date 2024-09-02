<?php
session_start();
include '../../includes/db_connection.php';
include '../../includes/template_emails.php';
include '../../includes/toastr.php';

// Verifica se o utilizador é técnico ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Técnico") {
    // Se não for, volta ao index
    header("location: ../../index.php");
    exit;
}

// Verifica se o ID do ticket foi passado
if (isset($_POST['id_ticket']) && is_numeric($_POST['id_ticket'])) {
    $id_ticket = $_POST['id_ticket'];

    // Atualiza o estado do ticket para "Reaberto" e LIMPA a data de encerramento
    $consulta = $ligacao->prepare("UPDATE Tickets SET estado = 'Reaberto', data_encerramento = NULL WHERE id_ticket = :id_ticket");
    $consulta->bindParam(':id_ticket', $id_ticket);

    if ($consulta->execute()) {
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
        $consultaTicketDetalhes = $ligacao->prepare("SELECT assunto, descricao, prioridade FROM Tickets WHERE id_ticket = :id_ticket");
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
        $prioridade = $ticket['prioridade'] ?? 'Sem Prioridade';
        $estado = 'Reaberto';

        $subjectAdmin = "Um Ticket foi Reaberto";
        $subjectAutor = "Reabriu o seu Ticket";
        $subjectTecnico = "Um Ticket foi Reaberto pelo Utilizador";

        $conteudoAdmin = "
        <p>Caro Administrador,</p>
        Serve o presente email para o informamos que o ticket, com o ID <strong>#$id_ticket</strong> foi reaberto.<p/>
        Consulte os detalhes do ticket abaixo:<p/>
        <ul>
            <li><strong>Assunto:</strong> $assunto</li>
            <li><strong>Descrição:</strong> $descricao</li>
            <li><strong>Técnico Responsável:</strong> $nomeTecnico</li>
            <li><strong>Utilizador:</strong> $nomeAutor</li>
            <li><strong>Mensagem do Utilizador:</strong> Ticket reaberto</li>
            <li><strong>Prioridade:</strong> $prioridade</li>
            <li><strong>Estado:</strong> $estado</li>              
        </ul>";

        $conteudoAutor = "
        <p>Caro(a) $nomeAutor,</p>
        Serve o presente email para o(a) informamos que reabriu o seu ticket, com o ID <strong>#$id_ticket</strong>.<p/>
        Consulte os detalhes do ticket abaixo:<p/>
        <ul>
            <li><strong>Assunto:</strong> $assunto</li>
            <li><strong>Descrição:</strong> $descricao</li>
            <li><strong>Técnico Responsável:</strong> $nomeTecnico</li>
            <li><strong>A sua mensagem:</strong> Ticket reaberto</li>      
            <li><strong>Prioridade:</strong> $prioridade</li>
            <li><strong>Estado:</strong> $estado</li>
        </ul>
        <p>Por favor, aceda à sua plataforma para verificar os detalhes.</p>";

        $conteudoTecnico = "
        <p>Caro(a) $nomeTecnico,</p>
        Serve o presente email para o(a) informarmos que o ticket, com o ID <strong>#$id_ticket</strong> foi reaberto pelo funcionário.<p/>
        Consulte os detalhes do ticket abaixo:<p/>
        <ul>
            <li><strong>Assunto:</strong> $assunto</li>
            <li><strong>Descrição:</strong> $descricao</li>
            <li><strong>Utilizador:</strong> $nomeAutor</li>
            <li><strong>Mensagem do Utilizador:</strong> Ticket reaberto</li>
            <li><strong>Prioridade:</strong> $prioridade</li>
            <li><strong>Estado:</strong> $estado</li>
        </ul>
        <p>Por favor, aceda à sua plataforma para verificar os detalhes.</p>";

        $textoBotao = "Entrar na Plataforma";
        $linkBotao = 'https://omnihelpdesk.pt/index.php';
        $messageAdmin = estrutura_emails($subjectAdmin, 'Um Ticket foi Reaberto', $conteudoAdmin, $textoBotao, $linkBotao);
        $messageAutor = estrutura_emails($subjectAutor, 'Reabriu o seu Ticket', $conteudoAutor, $textoBotao, $linkBotao);
        $messageTecnico = estrutura_emails($subjectTecnico, 'Um Ticket foi Reaberto pelo Funcionário', $conteudoTecnico, $textoBotao, $linkBotao);

        $headers = "From: no-reply@omnihelpdesk.pt\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";

        $emailAdminSent = mail($adminEmail, $subjectAdmin, $messageAdmin, $headers);
        $emailAutorSent = mail($autorEmail, $subjectAutor, $messageAutor, $headers);
        $emailTecnicoSent = mail($tecnicoEmail, $subjectTecnico, $messageTecnico, $headers);

        // Verifica se os e-mails foram enviados
        if (!$emailAdminSent || !$emailAutorSent || !$emailTecnicoSent) {
            $_SESSION['warning'] = 'Ticket reaberto, mas houve um problema ao enviar alguns e-mails.';
        } else {
            $_SESSION['success'] = 'Ticket reaberto com sucesso.';
        }
    } else {
        $_SESSION['error'] = 'Não foi possível reabrir o ticket.';
    }

    header('Location: meus_tickets.php');
    exit;
} else {
    $_SESSION['error'] = 'ID do ticket inválido';
    header('Location: meus_tickets.php');
    exit;
}
?>
