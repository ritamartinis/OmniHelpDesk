<?php
session_start();
include '../../includes/db_connection.php';
include '../../includes/template_emails.php';

// Verifica se o utilizador é admin
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Administrador") {
    header("location: ../../index.php");
    exit;
}

// Obtém o ID do utilizador a partir da URL
$id_utilizador = $_GET['id'] ?? null;

// Verifica se o utilizador existe
$consultaUtilizador = $ligacao->prepare("SELECT * FROM Utilizadores WHERE id_utilizador = :id_utilizador");
$consultaUtilizador->bindParam(':id_utilizador', $id_utilizador);
$consultaUtilizador->execute();
$utilizador = $consultaUtilizador->fetch(PDO::FETCH_ASSOC);

if (!$utilizador) {
    $_SESSION['error'] = 'Utilizador não encontrado.';
    header("location: lista_utilizadores.php");
    exit;
}

// Verifica se a conta já está desativada
if ($utilizador['estado_conta'] === 'Desativa') {
    $_SESSION['error'] = 'Conta já desativada anteriormente.';
    header("location: lista_utilizadores.php");
    exit;
}

// Verifica se o utilizador é um técnico com tickets atribuídos
$consultaTicketsTecnico = $ligacao->prepare("SELECT COUNT(*) FROM Tickets WHERE id_utilizador_responsavel = :id_utilizador AND estado != 'Fechado'");
$consultaTicketsTecnico->bindParam(':id_utilizador', $id_utilizador);
$consultaTicketsTecnico->execute();
$ticketsTecnico = $consultaTicketsTecnico->fetchColumn();

if ($ticketsTecnico > 0) {
    $_SESSION['error'] = 'Técnico com Tickets! Só poderá desativar a conta depois de reatribuir os tickets.';
    header("location: lista_utilizadores.php");
    exit;
}

// Verifica se o utilizador é um autor com tickets abertos
$consultaTicketsAutor = $ligacao->prepare("SELECT COUNT(*) FROM Tickets WHERE id_utilizador_autor = :id_utilizador AND estado NOT IN ('Fechado', 'Cancelado')");
$consultaTicketsAutor->bindParam(':id_utilizador', $id_utilizador);
$consultaTicketsAutor->execute();
$ticketsAutor = $consultaTicketsAutor->fetchColumn();

// Se for, cancela os tickets dele e insere a data_encerramento
if ($ticketsAutor > 0) {
    $atualizaTickets = $ligacao->prepare("UPDATE Tickets SET estado = 'Cancelado', data_encerramento = NOW() WHERE id_utilizador_autor = :id_utilizador AND estado NOT IN ('Fechado', 'Cancelado')");
    $atualizaTickets->bindParam(':id_utilizador', $id_utilizador);
    $atualizaTickets->execute();
}

// Desativa a conta do utilizador
$desativaConta = $ligacao->prepare("UPDATE Utilizadores SET estado_conta = 'Desativa' WHERE id_utilizador = :id_utilizador");
$desativaConta->bindParam(':id_utilizador', $id_utilizador);
$desativaConta->execute();

// Enviar e-mails
$adminEmail = "admin@omnihelpdesk.pt";
$utilizadorEmail = $utilizador['email'];
$nomeUtilizador = $utilizador['nome'];

$subjectAdmin = "Desativou uma Conta";
$subjectUtilizador = "A sua Conta foi Desativada";

$conteudoAdmin = "
<p>Caro Administrador,</p>
Serve o presente email para o informar que procedeu à desativação da conta do utilizador <strong>$nomeUtilizador</strong>.<p/>";

$conteudoUtilizador = "
<p>Caro(a) $nomeUtilizador,</p>
Serve o presente email para o(a) informarmos que sua conta foi, infelizmente, desativada.<br/>
A partir deste momento, deixará de ter acesso à plataforma. <br>
Lamentamos o sucedido e comumicamos que, se precisar de mais informações, por favor entre em contato com o Administrador.<p/>
";

$textoBotao = "Entrar na Plataforma";
$linkBotao = 'https://omnihelpdesk.pt/index.php';
$messageAdmin = estrutura_emails($subjectAdmin, 'Desativou uma Conta', $conteudoAdmin, $textoBotao, $linkBotao);
$messageUtilizador = estrutura_emails($subjectUtilizador, 'A sua Conta foi Desativada', $conteudoUtilizador);

$headers = "From: no-reply@omnihelpdesk.pt\r\n";
$headers .= "Content-type: text/html; charset=utf-8\r\n";

mail($adminEmail, $subjectAdmin, $messageAdmin, $headers);
mail($utilizadorEmail, $subjectUtilizador, $messageUtilizador, $headers);

$_SESSION['success'] = 'Conta desativada com sucesso.';
header("location: lista_utilizadores.php");
exit;
?>
