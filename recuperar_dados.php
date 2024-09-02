<?php
session_start();
include 'includes/db_connection.php';
include 'includes/template_emails.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["introduzirEmail"];

    // Verifica se o e-mail existe na base de dados
    $consulta = $ligacao->prepare("SELECT * FROM Utilizadores WHERE email = :email");
    $consulta->bindParam(':email', $email);
    $consulta->execute();

    if ($consulta->rowCount() > 0) {
        // Se e-mail existir, obtém os dados do utilizador
        $utilizador = $consulta->fetch(PDO::FETCH_ASSOC);
        $username = $utilizador['username'];
        $tipo = $utilizador['tipo'];
        $estado_conta = $utilizador['estado_conta'];

        // Verifica se a conta está desativada
        if ($estado_conta === 'Desativa') {
            $_SESSION['error'] = "Infelizmente não lhe é possível recuperar os dados pois tem a conta <strong>desativa</strong>.";
        } else {
            //Mail c o link para recuperar a pass
            $to = $email;
            $subject = "Redefinição de Senha";
            $conteudo = "
            <p>Caro(a) $username,</p>
            <p>Serve o presente e-mail para lhe fornecer os seus dados de acesso:</p>
            <ul>
                <li><strong>Username:</strong> $username</li>
                <li><strong>Tipo:</strong> $tipo</li>
            </ul>
            <p>Bem como lhe permitir redefinir a sua password.<br>
            Clique no botão abaixo:</p>
        ";
            $textoBotao = "Redefinir Senha";
            $linkBotao = 'http://omnihelpdesk.pt/redefinir_password.php?email=' . urlencode($email);
            $conclusao = "<p>Caso não tenha solicitado a recuperação de dados, por favor ignore este e-mail.</p>";
            $message = estrutura_emails($subject, 'Redefinição de Senha', $conteudo, $textoBotao, $linkBotao, $conclusao);

            $headers = "From: no-reply@omnihelpdesk.pt\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";

            //toastr    
            if (mail($to, $subject, $message, $headers)) {
                $_SESSION['success'] = "Verifique o seu e-mail para recuperar os dados de acesso.";
            } else {
                $_SESSION['error'] = "Não foi possível enviar e-mail para o seu endereço.";
            }
        }

    } else {
        // O e-mail não foi encontrado na bd
        $_SESSION['error'] = "O e-mail não foi encontrado na base de dados.";
    }
    header("Location: index.php");
    exit;
}
?>