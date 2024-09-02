<?php
session_start();
include '../../includes/db_connection.php';
include '../../includes/template_emails.php';

// Verifica se o utilizador é admin ou não
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Administrador") {
    // Se não for, volta ao index
    header("location: ../../index.php");
    exit;
}

// Query para ir buscar TODAS as contas pendentes
$consulta = $ligacao->prepare("SELECT * FROM Utilizadores WHERE estado_conta = 'Pendente'");
$consulta->execute();
$pendentes = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Admin vê todas as contas pendentes e:
// Decide se a 'pendente' passa a 'ativo' ou é eliminada
// Decide o tipo de conta
// Decide o departamento em que trabalha o utilizador
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accao']) && isset($_POST['id_utilizador'])) {
        $id_utilizador = $_POST['id_utilizador'];
        $acao = $_POST['accao'];

        //Query para ir buscar os dados do utilizador antes do admin ativar ou eliminar a conta para, depois, poder enviar e-mail
        //problema: se fizer delete da bd, depois já não consigo enviar email pq ele não existe. entao tem de ser antes
        $consulta = $ligacao->prepare("SELECT * FROM Utilizadores WHERE id_utilizador = :id_utilizador");
        $consulta->bindParam(':id_utilizador', $id_utilizador);
        $consulta->execute();
        $utilizador = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($acao == 'ativar_conta') {
            // Evitar que possa ativar uma conta com campos null. Dps ia ter problemas na lista_utilizadores.php
            if (!isset($_POST['tipo']) || empty($_POST['tipo']) || !isset($_POST['departamento']) || empty($_POST['departamento'])) {
                //toastr
                $_SESSION['error'] = 'Tem de preencher todos os campos para poder ativar a conta do utilizador.';
                header("Location: contas_pendentes.php");
                exit;
            }

            $tipo = $_POST['tipo'];
            $departamento = $_POST['departamento'];

            if ($utilizador) {
                //dá update na db dos dados que o admin alterou/introduziu    
                $consulta = $ligacao->prepare("UPDATE Utilizadores SET estado_conta = 'Ativa', tipo = :tipo, departamento = :departamento WHERE id_utilizador = :id_utilizador");
                $consulta->bindParam(':tipo', $tipo);
                $consulta->bindParam(':departamento', $departamento);
                $consulta->bindParam(':id_utilizador', $id_utilizador);

                //toastr
                if ($consulta->execute()) {
                    $_SESSION['success'] = 'Conta ativada com sucesso.';

                    // Envia email ao utilizador a informar
                    $to = $utilizador['email'];
                    $subject = "Conta Ativada";
                    $conteudo = "
                        <p>Caro(a) {$utilizador['nome']},</p>
                        Serve o presente e-mail para o(a) informar que o Administrador já teve oportunidade de analisar a sua conta.<br>
                        É com enorme prazer que informamos que a mesma foi ativada.<br>
                        A partir deste momento, poderá aceder à plataforma da <strong>OmniHelpDesk.</strong>.<br>
                        Clique abaixo para entrar na plataforma e efetuar o login.</p>";
                    $textoBotao = "Entrar na Plataforma";
                    $linkBotao = 'https://omnihelpdesk.pt/index.php';
                    $message = estrutura_emails($subject, 'Conta Ativada', $conteudo, $textoBotao, $linkBotao);

                    $headers = "From: no-reply@omnihelpdesk.pt\r\n";
                    $headers .= "Content-type: text/html; charset=utf-8\r\n";

                    mail($to, $subject, $message, $headers);

                } else {
                    $_SESSION['error'] = 'Não foi possível ativar a conta.';
                }

            } else {
                //if !$utilizador
                $_SESSION['error'] = 'Conta não encontrada.';
            }


        } elseif ($acao == 'eliminar_conta') {
            if ($utilizador) {
                $consulta = $ligacao->prepare("DELETE FROM Utilizadores WHERE id_utilizador = :id_utilizador");
                $consulta->bindParam(':id_utilizador', $id_utilizador);

                //toastr
                if ($consulta->execute()) {
                    $_SESSION['success'] = 'Conta eliminada com sucesso.';

                    // Envia email ao utilizador a informar
                    $to = $utilizador['email'];
                    $subject = "Conta Eliminada";
                    $conteudo = "
                        <p>Caro(a) {$utilizador['nome']},</p>
                        Serve o presente e-mail para o(a) informar que o Administrador já teve oportunidade de analisar a sua conta.<br>
                        Lamentamos informá-lo(a) que a mesma foi eliminada.<br>
                        Desconhecendo as razões da decusão e, como tal, sugerimos que contacte directamente o administrador através de <strong>admin@omnihelpdesk.pt</strong>.</p><br/>
                       <p>Estamos sempre à sua disposição para esclarecer dúvidas adicionais.</p>";
                    $message = estrutura_emails($subject, 'Conta Eliminada', $conteudo);

                    $headers = "From: no-reply@omnihelpdesk.pt\r\n";
                    $headers .= "Content-type: text/html; charset=utf-8\r\n";

                    mail($to, $subject, $message, $headers);

                } else {
                    $_SESSION['error'] = 'Não foi possível eliminar a conta.';
                }
            } else {
                //if !$utilizador
                $_SESSION['error'] = 'Conta não encontrada.';
            }
        }

        //Limpa os dados do POST e regressa à página sem a conta que foi ativada ou eliminada
        header("Location: contas_pendentes.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contas Pendentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
	<link rel="icon" href="/includes/fotosdiversas/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/includes/estilos.css">
</head>

<body>
    <?php include_once '../navbar_admin.php'; ?>
    <div class="container mt-5">
        <h3 class="text-center titulo-box">Contas Pendentes</h3>
        <br>
            
        <?php if (count($pendentes) > 0): ?>
            <table class="table table-hover mt-3">
                <thead>
                    <tr class="table-dark">
                        <th>Nome Completo</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Código de Funcionário</th>
                        <th>Tipo de Conta</th>
                        <th>Departamento</th>
                        <th>Criada em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendentes as $pendente): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pendente['nome']); ?></td>
                            <td><?php echo htmlspecialchars($pendente['username']); ?></td>
                            <td><?php echo htmlspecialchars($pendente['email']); ?></td>
                            <td><?php echo htmlspecialchars($pendente['codigo_funcionario']); ?></td>
                            <td>
                                <!-- id para os modais -->
                                <form id="form-<?php echo $pendente['id_utilizador']; ?>" action="" method="POST"
                                    class="d-inline">
                                    <input type="hidden" name="id_utilizador" value="<?php echo $pendente['id_utilizador']; ?>">
                                    <select name="tipo" class="form-select d-inline-block w-auto">
                                        <option value="" disabled selected></option>
                                        <option value="Funcionário">Funcionário</option>
                                        <option value="Técnico">Técnico</option>
                                        <option value="Administrador">Administrador</option>
                                    </select>
                            <td>
                                <select name="departamento" class="form-select d-inline-block w-auto">
                                    <option value="" disabled selected></option>
                                    <option value="Informático">Informático</option>
                                    <option value="Recursos Humanos">Recursos Humanos</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Financeiro">Financeiro</option>
                                    <option value="Vendas">Vendas</option>
                                    <option value="Apoio ao Cliente">Apoio ao Cliente</option>
                                    <option value="Administrativo">Administrativo</option>
                                    <option value="Jurídico">Jurídico</option>
                                </select>
                            </td>
                            <td><?php echo htmlspecialchars($pendente['criada_em']); ?></td>

                            <td class="d-flex justify-content-between">
                                <!-- abre o modal e a função js setFormAction (p definir o id do form)-->
                                <button type="button" name="accao" value="ativar_conta" class="btn btn-success me-1"
                                    data-bs-toggle="modal" data-bs-target="#confirmModalAtivar"
                                    onclick="setFormAction(<?php echo $pendente['id_utilizador']; ?>, 'ativar_conta')">Ativar</button>
                                <button type="button" name="accao" value="eliminar_conta" class="btn btn-danger"
                                    data-bs-toggle="modal" data-bs-target="#confirmModalEliminar"
                                    onclick="setFormAction(<?php echo $pendente['id_utilizador']; ?>, 'eliminar_conta')">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <div class="text-center">
                <img src="/includes/fotosdiversas/erro.png" alt="No results">
            </div>
            <div class="text-center error-box">
                <p>De momento, não constam contas pendentes de registo.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para Ativar Conta -->
    <div class="modal fade" id="confirmModalAtivar" tabindex="-1" aria-labelledby="confirmModalAtivarLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalAtivarLabel">Confirmar Ativação da Conta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">Confirma que deseja ativar a conta com os dados inseridos?</div>
                <div class="modal-footer">
                    <!-- é chamada a função js submitForm -->
                    <button type="button" class="btn btn-success" onclick="submitForm()">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Eliminar Conta  -->
    <div class="modal fade" id="confirmModalEliminar" tabindex="-1" aria-labelledby="confirmModalEliminarLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalEliminarLabel">Confirmar Eliminação da Conta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">Confirma que deseja eliminar a conta?</div>
                <div class="modal-footer">
                    <!-- é chamada a função js submitForm -->
                    <button type="button" class="btn btn-danger" onclick="submitForm()">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        //adiciona um campo hidden ao form para definir a acção - ativar ou eliminar
        function setFormAction(id, action) {
            //obtem o form usando o id_utilizador
            var form = document.getElementById('form-' + id);
            // cria um input hidden 
            var actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'accao';
            //define o valor do input como a accao "ativar_conta" ou "eliminar_conta" dos buttons
            actionInput.value = action;
            //append - adiciona como input hidden ao form
            form.appendChild(actionInput);
        }

        function submitForm() {
            //encontra o id do form que já tem um campo hidden (da função anterior)
            var formId = document.querySelector('input[name="accao"][type="hidden"]').parentElement.id;
            //faz submit e é chamada pelos modais
            document.getElementById(formId).submit();
        }
    </script>
    <?php include '../../includes/toastr.php'; ?>
    <?php include '../../includes/rodape.php'; ?>
</body>

</html>