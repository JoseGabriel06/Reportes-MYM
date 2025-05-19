<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/default.min.css"/>
    <link rel="shortcut icon" href="https://i.imgur.com/RQXNwMZ.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="login-form">
        <form action="" method="POST" class="contenedor_login">
            <div class="logo">
            <img src="https://i.imgur.com/yr8rhld.png" alt="Logo Ditribuidora MYM">
            </div>
            <input type="text" name="username" placeholder="Usuario" class="campo" required>
            <input type="password" name="password" placeholder="Contraseña" class="campo" required>
            <button type="submit" class="btn_ingresar">INGRESAR</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>
</body>
</html>
<?php
session_start(); // Inicia sesión

// Activa el reporte de errores para depurar
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Si ya está logueado, redirige al index.php
if (isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../includes/db_connect.php';
$conexion = connectToDatabase('central');
// Verifica si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // $servername = "localhost"; 
    // $username = "root"; 
    // $password = "MyG4b0QL2023**@##"; 
    // $database = "db_mymsa"; 

    if ($conexion !== null && $conexion->connect_errno === 0) {
    // if ($mys->connect_error) {
    //     echo "<script>alertify.error('Error al conectar con la base de datos');</script>";
    //     exit;
    // }

    $user = $_POST['username'];
    $pass = $_POST['password'];

    $sql = "SELECT * FROM adm_usuario 
            WHERE usuario = ? AND AES_DECRYPT(clave, '$0fTM1M') = ?";
    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        echo "<script>alertify.error('Error en la consulta SQL');</script>";
        exit;
    }

    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['usuario'] = $row['usuario'];
        $_SESSION['nombre'] = $row['nombres'];

        echo "<script>
            alertify.success('Inicio de sesión exitoso. Bienvenido " . $row['nombres'] . "');
            setTimeout(() => { window.location.href = '../index.php'; }, 2000);
        </script>";
    } else {
        echo "<script>alertify.error('Usuario o contraseña incorrectos');</script>";
    }

    $stmt->close();
    $conexion->close();
}else{
    echo "<script>alertify.error('Error al conectar con la base de datos');</script>";
    exit;
}
}
?>
