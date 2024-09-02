<?php
session_start();
include 'includes/db_connection.php';
include 'includes/template_emails.php';

// Iniciar a var erro com uma string vazia para, depois, mostrar cada possível erro.
$erro = '';

// Verifica o preenchimento do formulário de registo
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // GRAVA
    $nome = $_POST["nome"];
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $passwordrep = $_POST['passwordrep'];
    $codigo_funcionario = $_POST["codigo_funcionario"];
    $tipo = 'Funcionário';  // regista a nova conta como funcionário por default. depois o admin, na sua area, muda se quiser.

    // Verifica se as senhas coincidem
    if ($password !== $passwordrep) {
        $_SESSION['error'] = "As <strong>passwords</strong> não coincidem. Por favor, tente novamente.";
    } else {
        // Verifica se o e-mail é do domínio @omnihelpdesk.pt ou não
        if (strpos($email, '@omnihelpdesk.pt') === false) {
            $_SESSION['error'] = "Por favor, utilize um <strong>email corporativo</strong> (@omnihelpdesk.pt).";
        } else {
            // Verifica se o código de funcionário já pertence a outro funcionário, aka, se já está na bd
            $consulta = $ligacao->prepare("SELECT * FROM Utilizadores WHERE codigo_funcionario = :codigo_funcionario");
            $consulta->bindParam(':codigo_funcionario', $codigo_funcionario);
            $consulta->execute();

            if ($consulta->rowCount() > 0) {
                $_SESSION['error'] = "Já existe um utlizador registado com o <strong>código</strong> que inseriu.";
            } else {
                // Verifica se o username já existe
                $consulta = $ligacao->prepare("SELECT * FROM Utilizadores WHERE username = :username");
                $consulta->bindParam(':username', $username);
                $consulta->execute();

                if ($consulta->rowCount() > 0) {
                    $_SESSION['error'] = "Já existe um utilizador registado com o <strong>username</strong> que inseriu.";
                } else {
                    // Verifica se o email já existe
                    $consulta = $ligacao->prepare("SELECT * FROM Utilizadores WHERE email = :email");
                    $consulta->bindParam(':email', $email);
                    $consulta->execute();

                    if ($consulta->rowCount() > 0) {
                        $_SESSION['error'] = "Já existe um utilizador registado com o <strong>e-mail</strong> que inseriu.";
                    } else {
                        // Hash da senha
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        // Inserir novo utilizador
                        $consulta = $ligacao->prepare("INSERT INTO Utilizadores (nome, username, email, password, codigo_funcionario, tipo, estado_conta) VALUES (:nome, :username, :email, :password, :codigo_funcionario, :tipo, 'pendente')");
                        $consulta->bindParam(':nome', $nome);
                        $consulta->bindParam(':username', $username);
                        $consulta->bindParam(':email', $email);
                        $consulta->bindParam(':password', $hashed_password);
                        $consulta->bindParam(':codigo_funcionario', $codigo_funcionario);
                        $consulta->bindParam(':tipo', $tipo);

                        if ($consulta->execute()) {
                            //Email
                            $to = "admin@omnihelpdesk.pt";
                            $subject = "Nova Conta Pendente de Aprovação";

                            $conteudo = "
                                <p>Caro Administrador,</p>
                                <p>Serve o presente e-mail para o informar que uma nova conta foi registada e encontra-se pendente. Seguem, abaixo, os detalhes da mesma:</p>
                                <ul>
                                    <li><strong>Nome Completo:</strong> $nome</li>
                                    <li><strong>Username:</strong> $username</li>
                                    <li><strong>Email:</strong> $email</li>
                                    <li><strong>Código de Funcionário:</strong> $codigo_funcionario</li>
                                    <li><strong>Tipo de Conta:</strong> Funcionário</li>
                                </ul>
                                <p>Por favor, entre na sua área de administrador para a aceitar ou eliminar.</p>
                            ";
                            $textoBotao = "Contas Pendentes";
                            $linkBotao = 'https://omnihelpdesk.pt/admin/utilizadores/contas_pendentes.php';
                            $message = estrutura_emails($subject, 'Conta Pendente de Aprovação', $conteudo, $textoBotao, $linkBotao);

                            $headers = "From: no-reply@omnihelpdesk.pt\r\n";
                            $headers .= "Content-type: text/html; charset=utf-8\r\n";

                            mail($to, $subject, $message, $headers);

                            // toastr
                            // Manda o utilizador, se ele for bem-sucedido, para o index onde lhe aparece o toastr que aguarda por aprovação
                            $_SESSION['success'] = "Conta foi registada com sucesso! <br/>Aguarde pela aprovação do Administrador.";
                        } else {
                            $_SESSION['error'] = "Erro ao registar a conta. Por favor, tente novamente.";
                        }

                        // Depois de registar a conta, redirecciona de volta para o index
                        header("Location: index.php");
                        exit;
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Nova Conta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel='stylesheet' href='includes/estilos.css'>
</head>

<body class="flex-body">
    <div class="login-container">
        <div class="login-logo">
            <img src="/includes/fotosdiversas/logo.png" alt="Logo da Empresa">
        </div>
        <div class="login-form">
            <h3 class="text-center">Registar Nova Conta</h3>
            <form action="" method="POST">
                <div class="input-group">
                    <input type="text" name="nome" id="nome" class="form-control" placeholder="Nome Completo" required>
                </div>
                <p></p>
                <div class="input-group">
                    <input type="text" name="username" id="username" class="form-control" placeholder="Username"
                        required>
                </div>
                <p></p>
                <div class="input-group">
                    <input type="email" name="email" id="email" class="form-control" placeholder="Email" required>
                </div>
                <p></p>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password"
                        required>
                </div>
                <p></p>
                <div class="input-group">
                    <input type="password" id="passwordrep" name="passwordrep" class="form-control"
                        placeholder="Repetir Password" required>
                </div>
                <p></p>
                <div class="input-group">
                    <input type="text" name="codigo_funcionario" id="codigo_funcionario" class="form-control"
                        placeholder="Código de Funcionário" required>
                </div>
                <p></p>
                <input type="submit" name="registar" id="registar" value="REGISTAR" class="login-button">
            </form>
            <p />
            <p class="text-center">Já tem uma conta?<span class="arrow">↓</span><a href="index.php">Iniciar Sessão</a>
            </p>



        </div>
        <!-- Caso haja erros -->
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger mt-3" role="alert">
                <?php echo $erro; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php include 'includes/toastr.php'; ?>
</body>

</html>