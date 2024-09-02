<?php
session_start();
session_destroy();	//termina a sessão
header("location: index.php");
?>