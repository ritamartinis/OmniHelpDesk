<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
 <style>
         /* para o toastr não ficar por cima da navbar */
        #toast-container {
            top: 100px !important; 
        }
    </style>
<script>
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "showDuration": "500"
    }

    $(document).ready(function () {
            
        <?php if (isset($_SESSION['success'])): ?>
            toastr.success("<?php echo $_SESSION['success']; ?>");
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            toastr.error("<?php echo $_SESSION['error']; ?>");
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
       
        <?php if (isset($_SESSION['warning'])): ?>
            toastr.warning("<?php echo $_SESSION['warning']; ?>");
            <?php unset($_SESSION['warning']); ?>
        <?php endif; ?>
    });
</script>
