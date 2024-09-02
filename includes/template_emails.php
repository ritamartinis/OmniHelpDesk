<?php
function estrutura_emails($titulo, $cabecalho, $conteudo, $textoBotao = '', $linkBotao = '', $conclusao = '') {
    $logoURL = 'http://omnihelpdesk.pt/includes/fotosdiversas/logo.png';

    return "
    <html>
    <head>
      <title>$titulo</title>
      <style>
        body {
          font-family: Arial, sans-serif;
          line-height: 1.6;
          color: #333;
        }
        .container {
          width: 80%;
          margin: 0 auto;
          padding: 20px;
          border: 1px solid #ddd;
          border-radius: 10px;
          background-color: #f9f9f9;
        }
        .header {
          text-align: center;
          padding: 10px 0;
          background-color: #014421;
          color: white;
          border-radius: 10px 10px 0 0;
        }
        .header img {
          max-width: 150px; /* Tamanho do logo */
          margin-bottom: 10px;
        }
        .content {
          padding: 20px;
        }
        .footer {
          text-align: center;
          padding: 10px 0;
          background-color: #014421;
          color: white;
          border-radius: 0 0 10px 10px;
        }
        .button {
          display: inline-block;
          padding: 10px 20px;
          color: white;
          background-color: #014421;
          text-decoration: none;
          border-radius: 5px;
        }
      </style>
    </head>
    <body>
      <div class='container'>
        <div class='header'>
          <img src='$logoURL' alt='Logo'>
          <h1>$cabecalho</h1>
        </div>
        <div class='content'>
          $conteudo
          ".(!empty($textoBotao) && !empty($linkBotao) ? "<p><a href='$linkBotao' class='button'>$textoBotao</a></p>" : "")."
          ".(!empty($conclusao) ? $conclusao : "")."
        </div>
        <div class='footer'>
          <p>Atentamente,<br>
          A equipa da OmniHelpDesk</p>
        </div>
      </div>
    </body>
    </html>
    ";
}
?>
