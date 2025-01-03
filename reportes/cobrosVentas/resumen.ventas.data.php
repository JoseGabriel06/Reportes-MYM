<?php
require_once '../../connection.php';
// Consulta SQL
$fechaInicio = $_POST["fechaInicio"];
$fechaFinal = $_POST["fechaFinal"];
// $vendedor = $_POST["vendedor"];

$filtro = "";
// Consulta SQL
$consulta = "SELECT
e.nombre as vendedor,
sum(v.total) as venta
from db_mymsa.adm_venta v
join db_mymsa.pedido_producto p on v.idpedido = p.idpedido 
join db_mymsa.adm_empleado e on p.id_empleado = e.id_empleado
where 
v.estado = 1
and v.tipo in('E','F')
and v.id_envio = 0
and date(v.fecha_registro) >= ? and date(v.fecha_registro) <= ?
group by e.nombre
order by e.nombre;";

$stmt = $mysqli->prepare($consulta);

    if (!$stmt) {
        echo "<script>alertify.error('Error en la consulta SQL');</script>";
        exit;
    }

    if ($mysqli->connect_error) {
        echo "Hay error";
        die("Error de conexión: " . $mysqli->connect_error);
    }

    //$resultado = $mysqli->query($consulta);

    $stmt->bind_param("ss", $fechaInicio, $fechaFinal);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado) {
        $datos = $resultado->fetch_all(MYSQLI_ASSOC);
        $mysqli->close();
        //return $datos;
    } else {
        echo "Error en la consulta: " . $mysqli->error;
        $mysqli->close();
        $datos = null;
    }

echo json_encode($datos);
?>