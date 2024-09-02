<?php
session_start();
include '../../includes/db_connection.php';
require_once '../../includes/dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Verifica se o utilizador é técnico
if (!isset($_SESSION["utilizador"]) || $_SESSION["utilizador"] !== "Técnico") {
    header("location: ../../index.php");
    exit;
}

// Obtém o ID do ticket a partir da URL
$id_ticket = $_GET['id_ticket'] ?? null;

$id_utilizador = $_SESSION["id_utilizador"];

// Query para buscar os dados do ticket
$consultaTicket = $ligacao->prepare("SELECT * FROM Tickets WHERE id_ticket = :id_ticket");
$consultaTicket->bindParam(':id_ticket', $id_ticket);
$consultaTicket->execute();
$ticket = $consultaTicket->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    print "Ticket não encontrado.";
    exit;
}

// Query para buscar os dados do utilizador que abriu o ticket
$consultaUtilizador = $ligacao->prepare("SELECT * FROM Utilizadores WHERE id_utilizador = :id_utilizador");
$consultaUtilizador->bindParam(':id_utilizador', $ticket['id_utilizador_autor']);
$consultaUtilizador->execute();
$utilizador = $consultaUtilizador->fetch(PDO::FETCH_ASSOC);

// Query para buscar os dados do técnico responsável
$consultaTecnico = $ligacao->prepare("SELECT * FROM Utilizadores WHERE id_utilizador = :id_utilizador");
$consultaTecnico->bindParam(':id_utilizador', $ticket['id_utilizador_responsavel']);
$consultaTecnico->execute();
$tecnico = $consultaTecnico->fetch(PDO::FETCH_ASSOC);

// Query para buscar o histórico do ticket
$consultaHistorico = $ligacao->prepare("SELECT h.*, u.nome as nome_utilizador FROM HistoricoTickets h LEFT JOIN Utilizadores u ON h.id_utilizador = u.id_utilizador WHERE h.id_ticket = :id_ticket ORDER BY h.data_hora ASC");
$consultaHistorico->bindParam(':id_ticket', $id_ticket);
$consultaHistorico->execute();
$historico = $consultaHistorico->fetchAll(PDO::FETCH_ASSOC);

// Cria uma nova instância do Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Caminho da imagem do logotipo
$logoPath = '../../includes/fotosdiversas/logo.png';
$logoData = base64_encode(file_get_contents($logoPath));

// Cria o conteúdo HTML para o PDF
$html = '
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Ticket</title>
    <style>
    body { font-family: DejaVu Sans, sans-serif; }
    .container { margin-top: 20px; }
    .header { display: flex; align-items: center; }
    .header img { max-width: 100px; }
    .header h1 { margin-left: 20px; color: #2e7d32; }
    .card { margin-bottom: 20px; border: 1px solid #ddd; padding: 20px; }
    .card-title { font-size: 24px; margin-bottom: 20px; color: #2e7d32; }
    .card-subtitle { font-size: 18px; color: #666; margin-bottom: 20px; }
    .card-text { margin-bottom: 10px; }
    .separator { border-bottom: 1px solid #ddd; margin: 20px 0; }
    .page-break { page-break-before: always; }
</style>

</head>
<body>
    <div class="container">
        <div class="header">
            <img src="data:image/png;base64,' . $logoData . '" alt="Logotipo da Empresa" />
            <h1>OmniHelpDesk</h1>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Detalhes do Ticket</h5>
                <h5 class="card-subtitle">Ticket #' . htmlspecialchars($ticket['id_ticket']) . '</h5>
                <p class="card-text"><strong>Assunto:</strong> ' . htmlspecialchars($ticket['assunto']) . '</p>
                <p class="card-text"><strong>Descrição:</strong> ' . htmlspecialchars($ticket['descricao']) . '</p>
                <p class="card-text"><strong>Estado:</strong> ' . htmlspecialchars($ticket['estado']) . '</p>
                <p class="card-text"><strong>Prioridade:</strong> ' . htmlspecialchars($ticket['prioridade']) . '</p>
                <p class="card-text"><strong>Data de Criação:</strong> ' . date('d-m-Y H:i', strtotime($ticket['data_criacao'])) . '</p>
                <p class="card-text"><strong>Data de Encerramento:</strong> ' . htmlspecialchars($ticket['data_encerramento'] ? date('d-m-Y H:i', strtotime($ticket['data_encerramento'])) : 'Não Encerrado') . '</p>
                <p class="card-text"><strong>Departamento Responsável:</strong> ' . htmlspecialchars($ticket['departamento_responsavel']) . '</p>
                <p class="card-text"><strong>Técnico Responsável:</strong> ' . htmlspecialchars($tecnico['nome'] ?? 'Por atribuir') . '</p>
                <p class="card-text"><strong>Autor:</strong> ' . htmlspecialchars($utilizador['nome']) . '</p>
                 </div>
        </div>
        <div class="page-break"></div>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Histórico de Comentários</h5>';
                foreach ($historico as $item) {
                    $html .= '<div class="card-text"><small><em>' . date('d/m/Y H:i', strtotime($item['data_hora'])) . '</em></small></div>';
                    $html .= '<div class="card-text"><strong>' . htmlspecialchars($item['nome_utilizador']) . ':</strong> ' . htmlspecialchars($item['mensagem']) . '</div>';
                    $html .= '<div class="separator"></div>';
                }
$html .= '
            </div>
        </div>
    </div>
</body>
</html>';

// Carrega o HTML para o Dompdf
$dompdf->loadHtml($html);

// (Opcional) Configurar o tamanho e orientação do papel
$dompdf->setPaper('A4', 'portrait');

// Renderiza o HTML como PDF
$dompdf->render();

// Envia o PDF para o browser
$dompdf->stream('detalhes_ticket_' . $id_ticket . '.pdf', array('Attachment' => 0));

?>
