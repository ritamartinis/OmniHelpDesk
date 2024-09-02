<style>
footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    height: 50px; 
    background-color: #000; 
    color: #fff; 
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1030; 
    
}

footer .btn {
    margin-left: auto; 
    background-color: #28a745; 
    border: none;
}

footer .content-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: 0 20px;
}

footer .logo-text {
    display: flex;
    justify-content: center;
    align-items: center;
    flex: 1;
}

footer .logo-text img {
    height: 40px;
    margin-right: 10px;
}

footer .btn-container {
    display: flex;
    justify-content: flex-end;
    align-items: center;
}
.espaco-margem {
    padding-bottom: 100px; 
}        
        
</style>

<!-- div para afastar o rodape do body -->
<div class="espaco-margem"></div>

<footer class="bg-dark text-white">
    <div class="content-container">
        <div class="logo-text">
            <img src="<?php echo BASE_PATH; ?>pin.png" alt="Logo">
            <span class="navbar-brand">OmniHelpDesk</span>
        </div>
        <div class="btn-container">
            <button onclick="scrollToTop()" class="btn btn-success">
                <i data-feather="arrow-up"></i>
            </button>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
    // Substitui todos os "data-feather" pelos ícones que escolhi da biblioteca
    feather.replace();

    // Função para rolar até o topo da página
    function scrollToTop() {
        window.scrollTo({top: 0, behavior: 'smooth'});
    }
</script>
