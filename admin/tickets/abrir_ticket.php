<?php
session_start();
include '../../includes/db_connection.php';
include '../../includes/template_emails.php';

// Verifica se o utilizador é admin
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Administrador") {
    // Se não for, volta ao index
    header("location: ../../index.php");
    exit;
}

// Var para mostrar os erros
$erro = '';

// POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $assunto = $_POST["assunto"] ?? '';
    $descricao = $_POST["descricao"] ?? '';
    $departamento_responsavel = $_POST["departamento_responsavel"] ?? '';
    $prioridade = $_POST["prioridade"] ?? '';

    // Verifica se todos os campos obrigatórios estão preenchidos
    if (empty($assunto) || empty($descricao) || empty($departamento_responsavel) || empty($prioridade)) {
        //toastr
        $_SESSION['error'] = 'Para criar o ticket, precisa de preencher os campos obrigatórios.';
        header("Location: abrir_ticket.php");
        exit;
    }

    $id_utilizador_autor = $_SESSION["id_utilizador"]; //o autor é quem abre o ticket, aka, é quem está logado

    // Para enviar e-mail ao id_utilizador_autor é preciso fazer a query para ir buscar o mail
    // E o nome para poder colocar a var na estrutura do email a enviar.
    $consultaAutor = $ligacao->prepare("SELECT nome, email FROM Utilizadores WHERE id_utilizador = :id_utilizador");
    $consultaAutor->bindParam(':id_utilizador', $id_utilizador_autor);
    $consultaAutor->execute();
    $autor = $consultaAutor->fetch(PDO::FETCH_ASSOC);

    // Extensões permitidas para o ficheiro 
    $extensoes_permitidas = array('jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx');

    // Verifica se o utilizador fez upload de um ficheiro ou não
    // Se sim: 
    if ($_FILES['uploaded_ficheiro']['error'] === UPLOAD_ERR_OK) {
        // uploaded_ficheiro é o nome do campo do input
        // upload_err_ok é uma constante predefinida no php que tem o valor 0
        // significa que o upload foi bem sucedido, sem erros.
        $nome_arquivo = $_FILES['uploaded_ficheiro']['name'];
        $caminho_temporario = $_FILES['uploaded_ficheiro']['tmp_name'];
        $diretorio_destino = "../../includes/uploaded_ficheiros/";

        // Obtém a extensão do ficheiro
        $extensao_arquivo = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));

        // Verifica se a extensão do ficheiro é permitida
        if (in_array($extensao_arquivo, $extensoes_permitidas)) {

            // Cria uma pasta chamada "uploaded_ficheiros" se ela não existir já
            if (!is_dir($diretorio_destino)) {
                mkdir($diretorio_destino, 0777, true);
            }

            // Cria um nome único para o ficheiro
            $nome_arquivo_unico = $id_utilizador_autor . '_' . time() . '_' . $nome_arquivo;
            $caminho_destino = $diretorio_destino . $nome_arquivo_unico;

            // Coloca o ficheiro na pasta
            if (move_uploaded_file($caminho_temporario, $caminho_destino)) {
                // Coloca o path do ficheiro na bd
                $uploaded_ficheiro = $caminho_destino;
            } else {
                $erro = "Erro ao enviar o arquivo.";
            }
        } else {
            // Se fizer upload de uma extensão não permitida, mostra-lhe quais é que são.
            $erro = "Extensão de arquivo não permitida. Apenas são permitidos arquivos com as seguintes extensões: " . implode(', ', $extensoes_permitidas) . ".";
        }

        // Se não foi feito upload de nenhum ficheiro
    } else {
        $uploaded_ficheiro = NULL;
    }


    if (empty($erro)) {
        // Se não houver erros, insere o ticket na bd
        $consulta = $ligacao->prepare("INSERT INTO Tickets (assunto, descricao, departamento_responsavel, id_utilizador_autor, prioridade, uploaded_ficheiro) VALUES (:assunto, :descricao, :departamento_responsavel, :id_utilizador_autor, :prioridade, :uploaded_ficheiro)");
        $consulta->bindParam(':assunto', $assunto);
        $consulta->bindParam(':descricao', $descricao);
        $consulta->bindParam(':departamento_responsavel', $departamento_responsavel);
        $consulta->bindParam(':id_utilizador_autor', $id_utilizador_autor);
        $consulta->bindParam(':prioridade', $prioridade);
        $consulta->bindParam(':uploaded_ficheiro', $uploaded_ficheiro);

        if ($consulta->execute()) {
            // Enviar email para o admin - email fixo - e para o id_utilizador_autor + nome dele
            $adminEmail = "admin@omnihelpdesk.pt";
            $autorEmail = $autor['email'];
            $nomeAutor = $autor['nome'];

            $subjectAdmin = "Novo Ticket Criado";
            $subjectAutor = "Ticket Criado com Sucesso";

            $conteudoAdmin = "
                <p>Caro Administrador,</p>
                <p>Serve o presente e-mail para o informar que um novo ticket foi criado. Com os detalhes seguintes:</p>
                <ul>
                    <li><strong>Assunto:</strong> $assunto</li>
                    <li><strong>Descrição:</strong> $descricao</li>
                    <li><strong>Departamento Responsável:</strong> $departamento_responsavel</li>
                    <li><strong>Prioridade:</strong> $prioridade</li>
                </ul>
                <p>Por favor, aceda sua à plataforma para ver mais detalhes e atribuir o ticket a um técnico.</p>
            ";
            $conteudoAutor = "
                <p>Caro(a) $nomeAutor,</p>
                <p>Serve o presente e-mail para o informar que o seu ticket foi criado com sucesso. Com os seguintes detalhes:</p>
                <ul>
                    <li><strong>Assunto:</strong> $assunto</li>
                    <li><strong>Descrição:</strong> $descricao</li>
                    <li><strong>Departamento Responsável:</strong> $departamento_responsavel</li>
                    <li><strong>Prioridade:</strong> $prioridade</li>
                </ul>
                <p>Enviaremos novo e-mail assim que um técnico for designado para resolver o seu ticket.<br>
                Por favor, pedimos que aguarde.<br>
                No entretanto, poderá consultar, na sua plataforma, os detalhes do mesmo.</p>
            ";

            $textoBotao = "Entrar na Plataforma";
            $linkBotao = 'https://omnihelpdesk.pt/index.php';
            $messageAdmin = estrutura_emails($subjectAdmin, 'Novo Ticket Criado', $conteudoAdmin, $textoBotao, $linkBotao);
            $messageAutor = estrutura_emails($subjectAutor, 'Ticket Criado com Sucesso', $conteudoAutor, $textoBotao, $linkBotao);

            $headers = "From: no-reply@omnihelpdesk.pt\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";

            mail($adminEmail, $subjectAdmin, $messageAdmin, $headers);
            mail($autorEmail, $subjectAutor, $messageAutor, $headers);

            //toastr
            $_SESSION['success'] = 'Ticket criado com sucesso.';
            header("Location: meus_tickets.php");
            exit;

        } else {
            //erros que podem ocorrer ao tentar inserir os dados na bd, mesmo que os campos sejam preenchidos
            $erro = 'Erro ao criar o ticket. Por favor, tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrir Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
</head>

<body>
    <?php include_once '../navbar_admin.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Abrir Ticket</h3>
                    </div>
                    <div class="card-body">

                        <!-- Caso haja erros -->
                        <?php if (!empty($erro)): ?>
                            <div class="alert alert-danger"><?php echo $erro; ?></div>
                        <?php endif; ?>

                        <!-- para poder fazer upload de um ficheiro no form -->
                        <form id="ticketForm" action="" method="POST" enctype="multipart/form-data">

                            <div class="mb-3">
                                <label for="assunto" class="form-label">Assunto<span style="color: red;">*</span></label>
                                <input type="text" class="form-control" id="assunto" name="assunto" required>
                            </div>
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição<span style="color: red;">*</span></label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="departamento_responsavel" class="form-label">Departamento Responsável<span style="color: red;">*</span></label>
                                <select class="form-select" id="departamento_responsavel" name="departamento_responsavel" required>
                                    <option value="" disabled selected>Escolha um departamento</option>
                                    <option value="Informático">Informático</option>
                                    <option value="Recursos Humanos">Recursos Humanos</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Financeiro">Financeiro</option>
                                    <option value="Vendas">Vendas</option>
                                    <option value="Apoio ao Cliente">Apoio ao Cliente</option>
                                    <option value="Administrativo">Administrativo</option>
                                    <option value="Jurídico">Jurídico</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="prioridade" class="form-label">Prioridade<span style="color: red;">*</span></label>
                                <select class="form-select" id="prioridade" name="prioridade" required>
                                    <option value="Normal" selected>Normal</option>
                                    <option value="Baixa">Baixa</option>
                                    <option value="Alta">Alta</option>
                                    <option value="Urgente">Urgente</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="uploaded_ficheiro" class="form-label">Upload de Ficheiro</label>
                                <input type="file" class="form-control" id="uploaded_ficheiro" name="uploaded_ficheiro">
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-6 d-grid">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                        data-bs-target="#confirmModal">Abrir Ticket</button>
                                </div>
                                <div class="col-sm-6 d-grid">
                                    <a class="btn btn-secondary" href="../area_admin.php" role="button">Cancelar</a>
                                </div>
                            </div>
                        </form>
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
                    <h5 class="modal-title" id="confirmModalLabel">Confirmar Ticket</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">Tem a certeza que deseja abrir o ticket?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="document.getElementById('ticketForm').submit();">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    <?php include '../../includes/toastr.php'; ?>
    <?php include '../../includes/rodape.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>