<?php
require_once '../../connection.php';
// Consulta SQL
$fechaInicio = $_POST["fechaInicio"];
$fechaFinal = $_POST["fechaFinal"];
// $vendedor = $_POST["vendedor"];

$filtro = "";
// Consulta SQL
$consulta = "SELECT
r.nombre_vendedor,
r.semana,
sum(r.monto_facturas) as total_cobro
from db_mymsa.adm_recibo r
join db_rmym.clientes cl on r.idcliente = cl.idcliente
join db_rmym.adm_departamentopais dp on cl.iddepartamento = dp.iddepartamento
join db_rmym.adm_municipio m on cl.id_municipio = m.id_municipio 
where r.estado > 0 and  
date(r.fecha_registro) >= ? and date(r.fecha_registro) <= ?";

// if($vendedor != "TODOS")
// {
//     $filtro = $filtro . "and ro.nombre_vendedor = '$vendedor' ";
// }
$grupo = " group by r.nombre_vendedor ";
$orden = " order by r.nombre_vendedor; ";

$consulta = $consulta . $filtro . $grupo . $orden;

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
        //$datos = $resultado->fetch_all(MYSQLI_ASSOC);
        if ($resultado->num_rows > 0) {            
            $return_arr = array();
            $indice = 0;
            while ($row = $resultado->fetch_array()) {
                $return_arr[$indice] = array(                    
                    'nombre_vendedor' => $row['nombre_vendedor'],
                    'semana' => $row['semana'],
                    'total_cobro' => $row['total_cobro'],                    
                    'total_venta' => 0
                );
                $indice++;
            }

            //echo json_encode($return_arr);
            $resultado->close();
        } else {
            //$codigoRespuesta = -3; //no hay registros
            $resultado->close();
        }        
    } else {
        echo "Error en la consulta: " . $mysqli->error;        
        $datos = null;
    }

    /**
     * Consulta para ventas
     */
    $consulta = "SELECT
e.nombre as nombre_vendedor,
sum(v.total) as total_venta
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
        //$datos = $resultado->fetch_all(MYSQLI_ASSOC);
        if ($resultado->num_rows > 0) {      
            $indice = 0;                              
            while ($row = $resultado->fetch_array()) {                
                    //foreach($return_arr as $item)
                    for($i = 0; $i < sizeof($return_arr);$i++)
                        {
                        if($return_arr[$i]["nombre_vendedor"] === $row["nombre_vendedor"]){
                            //$item["total_venta"] = $row["total_venta"];  
                            $return_arr[$i]["total_venta"] = $row["total_venta"];  
                            break;                          
                        }                        
                    }                                                                                         
            }

            //echo json_encode($return_arr);
            $resultado->close();
        } else {
            //$codigoRespuesta = -3; //no hay registros
            $resultado->close();
        }        
    } else {
        echo "Error en la consulta: " . $mysqli->error;        
        $datos = null;
    }




    $mysqli->close();
echo json_encode($return_arr);
?>