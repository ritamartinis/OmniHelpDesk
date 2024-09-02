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

// Verifica se a conta já está ativa
if ($utilizador['estado_conta'] === 'Ativa') {
    $_SESSION['error'] = 'Conta já se encontra ativa.';
    header("location: lista_utilizadores.php");
    exit;
}

// Reativa a conta do utilizador
$reativaConta = $ligacao->prepare("UPDATE Utilizadores SET estado_conta = 'Ativa' WHERE id_utilizador = :id_utilizador");
$reativaConta->bindParam(':id_utilizador', $id_utilizador);
$reativaConta->execute();

//Enviar email ao id_utilizador e ao admin
$adminEmail = "admin@omnihelpdesk.pt";
$utilizadorEmail = $utilizador['email'];
$nomeUtilizador = $utilizador['nome'];

$subjectAdmin = "Reativou uma Conta";
$subjectUtilizador = "A sua conta foi Reativada";

$conteudoAdmin = "
<p>Caro Administrador,</p>
Serve o presente email para o informar que procedeu à reativação da conta do utilizador <strong>$nomeUtilizador</strong>.<p/>";

$conteudoUtilizador = "
<p>Caro(a) $nomeUtilizador,</p>
Serve o presente email para o(a) informarmos que sua conta foi, felizmente, reativada.
É com enorme agrado que comunicamos que, a partir deste momento, já pode fazer login na sua conta.<p/>";

$textoBotao = "Entrar na Plataforma";
$linkBotao = 'https://omnihelpdesk.pt/index.php';
$messageAdmin = estrutura_emails($subjectAdmin, 'Procedeu à Reativação de uma Conta', $conteudoAdmin, $textoBotao, $linkBotao);
$messageUtilizador = estrutura_emails($subjectUtilizador, 'A sua conta foi Reativada', $conteudoUtilizador, $textoBotao, $linkBotao);

$headers = "From: no-reply@omnihelpdesk.pt\r\n";
$headers .= "Content-type: text/html; charset=utf-8\r\n";

mail($adminEmail, $subjectAdmin, $messageAdmin, $headers);
mail($utilizadorEmail, $subjectUtilizador, $messageUtilizador, $headers);

$_SESSION['success'] = 'Conta Reativada com sucesso.';
header("location: lista_utilizadores.php");
exit;
?>
