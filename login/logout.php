<?php
session_start();
session_destroy(); // Elimina todas las sesiones
header('Location: login.php'); // Redirige al login
exit;
?>