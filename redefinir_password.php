<?php
session_start(); // Inicia a sessão
include 'includes/db_connection.php';

// Se tentarem aceder ao link directamente, n vai dar.
// Porque pode redefinir a password se tiver recebido o email. email não foi getado, volta para o index c/toastr
if (!isset($_GET['email']) || empty($_GET['email'])) {
    $_SESSION['error'] = "Só poderá redefinir a sua password se, primeiramente, solicitar a recuperação dos seus dados.";
    header("location: index.php");
    exit;
}

// POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $nova_password = $_POST["nova_password"];
    $confirmar_password = $_POST["confirmar_password"];

    if ($nova_password === $confirmar_password) {
        // Atualizar a senha
        $hash_password = password_hash($nova_password, PASSWORD_DEFAULT);
        $consulta = $ligacao->prepare("UPDATE Utilizadores SET password = :password WHERE email = :email");
        $consulta->bindParam(':password', $hash_password);
        $consulta->bindParam(':email', $email);

        // Toastr
        if ($consulta->execute()) {
            $_SESSION['success'] = "Password redefinida com sucesso.";
        } else {
            $_SESSION['error'] = "Infelizmente não foi possível alterar a password.";
        }
        // Reencaminha para o index para, lá, mostrar o toastr
        header("location: index.php");
        exit;

    } else {
        $_SESSION['error'] = "As passwords não coincidem. Por favor, tente novamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel='stylesheet' href='includes/estilos.css'>
</head>

<body class="flex-body">
    <div class="login-container">
        <div class="login-logo">
            <img src="/includes/fotosdiversas/logo.png" alt="Logo da Empresa">
        </div>
        <div class="login-form">
            <h3 class="text-center">Redefinir Password</h3>
            <form action="" method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">
                <div class="input-group">
                    <input type="password" name="nova_password" id="nova_password" placeholder="Nova Password" required>
                </div>
                <p></p>
                <div class="input-group">
                    <input type="password" name="confirmar_password" id="confirmar_password"
                        placeholder="Repita Password" required>
                </div>
                <p></p>
                <input type="submit" value="Redefinir" class="login-button">
            </form>
        </div>
    </div>

    <!-- caso haja erros -->
    <?php if (isset($erro)): ?>
        <div class="alert alert-danger mt-3" role="alert">
            <?php echo $erro; ?>
        </div>
    <?php endif; ?>
    <?php include 'includes/toastr.php'; ?>
</body>

</html>