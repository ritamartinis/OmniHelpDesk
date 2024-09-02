<?php
session_start();
include 'includes/db_connection.php';

// Verifica o preenchimento
if (isset($_POST["login"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];

    try {
        // Só se pode logar quem tem a conta no estado 'ativo', aka, já foi aprovada pelo admin
        // Então, primeiro verifica se o utilizador está na bd ou não
        $consulta = $ligacao->prepare("SELECT * FROM Utilizadores WHERE username = :username");
        $consulta->bindParam(':username', $username);
        $consulta->execute();

        if ($consulta->rowCount() > 0) {
            // Verifica o tipo de utilizador
            $utilizador = $consulta->fetch(PDO::FETCH_ASSOC);

            // Verifica o estado da conta: pq só a 'Ativa' pode fazer login
            if ($utilizador['estado_conta'] === 'Ativa') {

                // Verifica a palavra-passe
                if (password_verify($password, $utilizador['password'])) {
                    $_SESSION["utilizador"] = $utilizador["tipo"];
                    $_SESSION["username"] = $utilizador["username"];
                    //dependendo do tipo, e antes de reencaminhar para a area, verifica o id_utilizador
                    $_SESSION["id_utilizador"] = $utilizador["id_utilizador"];

                    //Se for administrador, vai para a página: area_admin
                    if ($_SESSION["utilizador"] === "Administrador") {
                        header("location: admin/area_admin.php");
                        exit;

                        //Se for técnico, vai para a página: area_tecnico       
                    } elseif ($_SESSION["utilizador"] === "Técnico") {
                        header("location: tecnico/area_tecnico.php");
                        exit;

                        //Se for um funcionário, vai para a página: area_funcionario        
                    } else {
                        header("location: funcionario/area_funcionario.php");
                        exit;
                    }
                    //Se for utilizador && se tiver conta ativa MAS enganou-se no username ou password         
                } else {
                    $_SESSION['error'] = "Dados de acesso incorretos. Por favor, tente novamente.";
                }

                //Se for utilizador MAS n tem conta ativa        
            } elseif ($utilizador['estado_conta'] === 'Pendente') {
                $_SESSION['error'] = "A sua conta encontra-se pendente, aguarde aprovação para poder fazer login.";

            } elseif ($utilizador['estado_conta'] === 'Desativa') {
                $_SESSION['error'] = "Infelizmente a sua conta foi desativada, não pode fazer login.";
            }

            //Não é um utilizador válido        
        } else {
            $_SESSION['error'] = "Lamentamos mas a sua conta não existe na nossa base de dados.";
        }
    } catch (PDOException $e) {
        $erro = "Ocorreu um erro no login: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OmniHelpDesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel='stylesheet' href='includes/estilos.css'>
</head>

<body class="flex-body">
    <div class="login-container">
        <div class="login-logo">
            <img src="includes/fotosdiversas/logo.png" alt="Logo da Empresa">
        </div>

        <div class="login-form">
            <h3 class="text-center">Autenticação do Utilizador</h3>
            <form action="" method="POST">
                <div class="input-group">
                    <input type="text" name="username" id="username" placeholder="Username" required>
                </div>
                <p></p>
                <div class="input-group">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                </div>
                <p></p>
                <input type="submit" name="login" id="login" value="LOGIN" class="login-button">
            </form>
            <p class="text-center"><a href="#" data-bs-toggle="modal" data-bs-target="#recuperarDadosModal">Recuperar
                    dados de acesso →</a></p>
            <p class="text-center"><a href="registar.php">Criar uma conta →</a></p>
        </div>

        <!-- Caso haja erros -->
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger mt-3" role="alert">
                <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <!-- Modal de Recuperação dos Dados de Login -->
        <div class="modal fade" id="recuperarDadosModal" tabindex="-1" aria-labelledby="recuperarDadosModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header">
                        <h5 class="modal-title" id="recuperarDadosModalLabel">Recuperar dados de acesso</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="recuperarDadosForm" method="POST" action="recuperar_dados.php">
                            <div class="mb-3">
                                <label for="introduzirEmail" class="form-label">Insira o seu e-mail:</label>
                                <input type="email" class="form-control" id="introduzirEmail" name="introduzirEmail"
                                    required>
                            </div>
                            <button type="submit" class="btn btn-success">Recuperar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- para o modal de recuperar dados de acesso -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <?php include 'includes/toastr.php'; ?>
</body>
</html>