<?php
session_start(); // Inicia sesión

// Verifica si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: login/login.php'); // Redirige al login si no está autenticado
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://i.imgur.com/RQXNwMZ.png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/menuPrincipal.css">
    <title>Reportes MYM</title>
</head>
<body>
<nav id="sidebar">
  <ul>
    <li>
      <span class="logo">Distruidora MYM</span>
      <button onclick=toggleSidebar() id="toggle-btn">
        <i class='bx bx-chevrons-left' ></i>
      </button>
    </li>
    <li class="active">
      <a href="index.php">
        <i class='bx bx-home'></i>
        <span>Inicio</span>
      </a>
    </li>
    <li>
      <button onclick=toggleSubMenu(this) class="dropdown-btn">
        <i class='bx bxs-report'></i>
        <span class="texto_menu">Reporte</span>
        <i class='bx bx-chevron-down'></i>
      </button>

      <ul class="sub-menu">
        <div>
          <li>
            <button onclick=toggleSubMenu2(this) class="dropdown-btn">
              <span class="texto_menu">Saldos</span>
              <i class='bx bx-chevron-down'></i>
            </button>
            <ul class="sub-menu2">
              <div>
                <li><a href="reportes/saldos/central">Central</a></li>
                <li><a href="reportes/saldos/peten">Peten</a></li>
                <li><a href="reportes/saldos/xela">Xela</a></li>
              </div>
            </ul>
          </li>
          <li><a href="reportes/recibos">Recibos</a></li>
          <li><a href="reportes/cobrosVentas">Resumen CV</a></li>
          <li><a href="reportes/productoClientes">Producto Clientes</a></li>
        </div>
      </ul>
    </li>

    <li class="log_out">
      <a href="login/logout.php">
        <i class='bx bx-log-out'></i>
        <span>Cerrar Sesión</span>
      </a>
    </li>
  </ul>
</nav>
  <main>
    <div class="imagen_fondo">
    </div>
  </main>
    <script src="js/sidebar.js"></script>
</body>
</html>