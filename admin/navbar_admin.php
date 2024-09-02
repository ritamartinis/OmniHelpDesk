<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- biblioteca de icones -->
<link href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css" rel="stylesheet">
<link rel='stylesheet' href='/includes/estilos.css'>

<!-- Como o caminho para as fotos não é sempre o mesmo -->
<?php
  // Definir o caminho base para as imagens
  define('BASE_PATH', '/includes/fotosdiversas/');
  ?>

<!-- Navbar -->
<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-brand" href="#">Área de Administrador</a>

    <a class="nav-link active d-flex align-items-right" aria-current="page" href="/admin/area_admin.php">
        <img src="<?php echo BASE_PATH; ?>pin.png" alt="Logo">
      </a>
    </div>
  </nav>

<!-- Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title text-white" id="offcanvasNavbarLabel">
      <a class="nav-link" href="/admin/area_admin.php">
        <i data-feather="activity"></i>
        Dashboard
      </a>
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  
  <!-- Inicio da lista -->
  <div class="offcanvas-body d-flex flex-column h-100">
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link" href="/admin/utilizadores/contas_pendentes.php">
          <i data-feather="user-check"></i>
          Contas Pendentes
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/admin/utilizadores/lista_utilizadores.php">
          <i data-feather="users"></i>
          Lista de Utilizadores
        </a>
      </li>

      <!-- Separador -->
      <hr class="bg-light">
      
      <li class="nav-item">
        <a class="nav-link" href="/admin/tickets/tickets_pendentes.php">
          <i data-feather="alert-triangle"></i>
          Tickets Pendentes
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/admin/tickets/lista_tickets.php">
          <i data-feather="clipboard"></i>
          Lista de Tickets
        </a>
      </li>
      
       <!-- Separador -->
      <hr class="bg-light">
      <li class="nav-item">
        <a class="nav-link" href="/admin/criar_faqs.php">
          <i data-feather="plus-square"></i>
          Adicionar FAQ
        </a>
      </li>      
      <li class="nav-item">
        <a class="nav-link" href="/admin/lista_faqs.php">
          <i data-feather="help-circle"></i>
          Lista de FAQs
        </a>
      </li>

      <!-- Separador -->
      <hr class="bg-light">
      
      <li class="nav-item">
        <a class="nav-link" href="/admin/tickets/abrir_ticket.php">
          <i data-feather="bookmark"></i>
          Abrir um Ticket
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/admin/tickets/meus_tickets.php">
          <i data-feather="clipboard"></i>
          Os Meus Tickets Pessoais
        </a>
      </li>
    </ul>
    <div class="mt-auto">
      <ul class="nav flex-column mb-2">
        <li class="nav-item d-flex align-items-center justify-content-between">
          <a class="nav-link d-flex align-items-center p-0" href="#">
            <img src="<?php echo BASE_PATH; ?>_admin.png" alt="Foto de Perfil do Admin" class="img-thumbnail user-img">
            <span class="ms-2 username">Administrador</span>
          </a>
          <a href="../../logout.php" class="nav-link d-flex align-items-center p-0">
            <i data-feather="power" class="logout-icon"></i>
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
  <!-- substitui todos os "data-feather" pelos icones que escolhi da biblioteca -->
  <!-- o data-feather" file text é uma lista -->
  feather.replace();
</script>
