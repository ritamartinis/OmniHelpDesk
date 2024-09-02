<?php
session_start();

//Como o caminho para a db não é sempre o mesmo...
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/db_connection.php');

// Obter dados do técnico - para ter acesso ao nome e à foto de perfil
$id_utilizador = $_SESSION["id_utilizador"];
$consultaUtilizador = $ligacao->prepare("SELECT * FROM Utilizadores WHERE id_utilizador = :id_utilizador");
$consultaUtilizador->bindParam(':id_utilizador', $id_utilizador);
$consultaUtilizador->execute();
$utilizador = $consultaUtilizador->fetch(PDO::FETCH_ASSOC);

//Como o caminho para as fotos não é sempre o mesmo
// Definir o caminho base para as imagens
define('BASE_PATH', '/includes/fotosdiversas/');
// Definir o caminho para as fotos de perfil
define('UPLOAD_PATH', '/includes/upload_fotosperfil/');
?>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/includes/estilos.css">
  <style>
    .offcanvas-start {
      width: 300px;
      /* Define a largura desejada para o offcanvas */
    }
         
  </style>


  <!-- Navbar -->
  <nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <a class="navbar-brand" href="#">Área de Técnico</a>

      <a class="nav-link active d-flex align-items-right" aria-current="page" href="/tecnico/area_tecnico.php">
        <img src="<?php echo BASE_PATH; ?>pin.png" alt="Logo">
      </a>
    </div>
  </nav>

  <!-- Offcanvas Sidebar -->
  <div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title text-white" id="offcanvasNavbarLabel">
        <a class="nav-link" href="/tecnico/area_tecnico.php">
          <i data-feather="home"></i>
          Dashboard
        </a>
      </h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- Inicio da lista -->
    <div class="offcanvas-body d-flex flex-column h-100">
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link" href="/tecnico/tickets_responsavel/tickets_atribuidos.php">
            <i data-feather="clipboard"></i>
            Tickets Atribuídos
          </a>
        </li>
        
        <!-- Separador -->
        <hr class="bg-light">
        
        <li class="nav-item">
          <a class="nav-link" href="/tecnico/tickets_pessoais/abrir_ticket.php">
            <i data-feather="bookmark"></i>
            Abrir um Ticket
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link" href="/tecnico/tickets_pessoais/meus_tickets.php">
            <i data-feather="clipboard"></i>
            Os Meus Tickets Pessoais
          </a>
        </li>   
        <li class="nav-item">
          <a class="nav-link" href="/tecnico/dados_conta.php">
            <i data-feather="user"></i>
            Detalhes da Conta
          </a>
        </li>
        <!-- Separador -->
      <hr class="bg-light">      
      <li class="nav-item">
        <a class="nav-link" href="/tecnico/lista_faqs.php">
          <i data-feather="help-circle"></i>
          FAQs
        </a>
      </li>
      </ul>
      <div class="mt-auto">
        <ul class="nav flex-column mb-2">
          <li class="nav-item d-flex align-items-center justify-content-between">
            <a class="nav-link d-flex align-items-center p-0" href="#">
              <!-- Verifica se o técnico já tem foto, senão atribui a default -->
              <?php 
              $foto_perfil = !empty($utilizador['foto_perfil']) ? UPLOAD_PATH . basename($utilizador['foto_perfil']) : BASE_PATH . 'default.jpg';
              ?>
              <img src="<?= $foto_perfil ?>" alt="Foto de Perfil" class="img-thumbnail user-img">
                   <!-- vai buscar o nome do técnico à bd -->
              <span class="ms-2 username"><?= $utilizador['username'] ?></span>
            </a>
            <a href="../../logout.php" class="nav-link d-flex align-items-center p-0">
              <i data-feather="power" class="logout-icon"></i>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
  <script>
    <!-- substitui todos os "data-feather" pelos icones que escolhi da biblioteca -->
    feather.replace();
  </script>

