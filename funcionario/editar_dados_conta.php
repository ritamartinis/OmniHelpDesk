<?php
session_start();
include '../includes/db_connection.php';


// Verifica se o utilizador é funcionário
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Funcionário") {
    header("location: ../index.php");
    exit;
}

$id_utilizador = $_SESSION['id_utilizador'];

// POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Verificação de dados repetidos
    // Nome
    $verificaRepete = $ligacao->prepare("SELECT COUNT(*) FROM Utilizadores WHERE nome = :nome AND id_utilizador != :id_utilizador");
    $verificaRepete->bindParam(':nome', $nome);
    $verificaRepete->bindParam(':id_utilizador', $id_utilizador);
    $verificaRepete->execute();
    $existeNome = $verificaRepete->fetchColumn();
    if ($existeNome > 0) {
        $_SESSION['error'] = "Não é possível atualizar o seu <strong>Nome</strong> pois este já pertence a outro utilizador.";
        header("Location: dados_conta.php");
        exit;
    }

    // Username
    $verificaRepete = $ligacao->prepare("SELECT COUNT(*) FROM Utilizadores WHERE username = :username AND id_utilizador != :id_utilizador");
    $verificaRepete->bindParam(':username', $username);
    $verificaRepete->bindParam(':id_utilizador', $id_utilizador);
    $verificaRepete->execute();
    $existeUsername = $verificaRepete->fetchColumn();
    if ($existeUsername > 0) {
        $_SESSION['error'] = "Não é possível atualizar o seu <strong>Username</strong> pois este já pertence a outro utilizador.";
        header("Location: dados_conta.php");
        exit;
    }

    // Email
    // Verificação adicional do domínio
    if (!str_ends_with($email, '@omnihelpdesk.pt')) {
        $_SESSION['error'] = "Não é possível atualizar seu <strong>Email</strong> pois este não termina em @omnihelpdesk.pt.";
        header("Location: dados_conta.php");
        exit;
    }
    $verificaRepete = $ligacao->prepare("SELECT COUNT(*) FROM Utilizadores WHERE email = :email AND id_utilizador != :id_utilizador");
    $verificaRepete->bindParam(':email', $email);
    $verificaRepete->bindParam(':id_utilizador', $id_utilizador);
    $verificaRepete->execute();
    $existeEmail = $verificaRepete->fetchColumn();
    if ($existeEmail > 0) {
        $_SESSION['error'] = "Não é possível atualizar seu <strong>Email</strong> pois este já pertence a outro utilizador.";
        header("Location: dados_conta.php");
        exit;
    }
    
    // Tipos de ficheiros permitidos
	$tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];

    // Upload da nova foto
    if ($_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $nome_arquivo = $_FILES['foto_perfil']['name'];
        $caminho_temporario = $_FILES['foto_perfil']['tmp_name'];
        $tipo_arquivo = $_FILES['foto_perfil']['type']; 
        $diretorio_destino = "../includes/upload_fotosperfil/";
            
           // Verifica se o tipo de ficheiro é permitido
    		if (!in_array($tipo_arquivo, $tiposPermitidos)) {
        		$_SESSION['error'] = 'Tipo de ficheiro não permitido! Apenas JPEG, PNG e GIF são aceites.';
        		header("Location: dados_conta.php");
        		exit;
    			}

        // Cria uma pasta chamada "upload_fotosperfil" se ela não existir
        if (!is_dir($diretorio_destino)) {
            mkdir($diretorio_destino, 0777, true);
        }

        // Cria um nome único para o ficheiro
        $nome_arquivo_unico = $id_utilizador . '_' . $nome_arquivo;
        $caminho_destino = $diretorio_destino . $nome_arquivo_unico;

        // Coloca o ficheiro na pasta
        if (move_uploaded_file($caminho_temporario, $caminho_destino)) {
            // Coloca o path da foto na bd
            $caminho_foto = $caminho_destino;
        } else {
            echo "Erro ao enviar o arquivo.";
            exit;
        }
    } else {
        // Se nenhum ficheiro foi enviado, mantem-se a que estava antes
        $caminho_foto = $_POST['foto_perfil_atual'];
    }

    // Se não houver dados repetidos, atualiza a BD
    if (!empty($password)) {
        // SE O UTILIZADOR ALTERAR A PASS
        $consulta = $ligacao->prepare("UPDATE Utilizadores SET nome = :nome, username = :username, email = :email, password = :password, foto_perfil = :foto_perfil WHERE id_utilizador = :id_utilizador");
        $consulta->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
    } else {
        // SE O UTILIZADOR NÃO ALTERA A PASS
        $consulta = $ligacao->prepare("UPDATE Utilizadores SET nome = :nome, username = :username, email = :email, foto_perfil = :foto_perfil WHERE id_utilizador = :id_utilizador");
    }

    $consulta->bindParam(':nome', $nome);
    $consulta->bindParam(':username', $username);
    $consulta->bindParam(':email', $email);
    $consulta->bindParam(':foto_perfil', $caminho_foto);
    $consulta->bindParam(':id_utilizador', $id_utilizador);

    // Toastr
    if ($consulta->execute()) {
        $_SESSION['success'] = 'Dados atualizados com sucesso';
    } else {
        $_SESSION['error'] = 'Erro ao atualizar os dados';
    }
    // Depois de atualizar, volta
    header("location: dados_conta.php");
    exit;

} else {
    $_SESSION['error'] = 'Requisição inválida';
    header("location: dados_conta.php");
    exit;
}
include '../includes/toastr.php';
?>