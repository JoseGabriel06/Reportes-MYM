<?php
session_start(); // Inicia sesión

// Verifica si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../login/login.php'); // Redirige al login si no está autenticado
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://i.imgur.com/RQXNwMZ.png">
    <!-- Fuentes de iconos -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Fuentes para letra -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <!-- Menu sidebar -->
    <link rel="stylesheet" href="../../css/menuPrincipal.css">
    <!-- Tabla de data table -->
     <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="../../css/datatables.css" />
    <link rel="stylesheet" href="../../css/datatables.min.css" />
    <link rel="stylesheet" href="css/estilos.css">
    <title>Reporte</title>
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
                <li><a href="../saldos/central">Central</a></li>
                <li><a href="../saldos/peten">Peten</a></li>
                <li><a href="../saldos/xela">Xela</a></li>
              </div>
            </ul>
          </li>
          <li><a href="../recibos">Recibos</a></li>
          <li><a href="../cobrosVentas">Resumen CV</a></li>
          <li><a href="#">Producto Clientes</a></li>
        </div>
      </ul>
    </li>

    <li class="log_out">
      <a href="../../login/logout.php">
        <i class='bx bx-log-out'></i>
        <span>Cerrar Sesión</span>
      </a>
    </li>
  </ul>
</nav>
  <main id="contenedorContenido">
  <div class="contenedor_titulo">
        <h2>PRODUCTOS POR CLIENTE</h2>
    </div>

    <div class="contenedor_tabla">
    <div class="contenedor_codigo">
      <label for="codigo" class="codigo_producto_titulo">Código Producto</label>
      <input type="text" id="codigo" name="codigo" placeholder="Código..." class="codigo_producto"/>
    </div>
      <div class="contenedor_btn">
    <button type="button" id="btnCarga" onclick="CargarListaRecibos()" class="btn_cargar">APLICAR FILTROS</button>
    </div>

            <table id="tabla-ventas" class="display" style=" z-index: 1;">
        <thead>
            <tr>
                <th>Departamento</th>
                <th>Municipio</th>
                <th>Cliente</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Subtotal</th>
                <th>Última Compra</th>
                <th>Cantidad Devuelta</th>
                <th>Precio Unitario Venta</th>
                <th>Subtotal Devolución</th>
                <th>Sobre</th>
                <th>Marca</th>
            </tr>
        </thead>
        <tbody id="cuerpoTabla">
            <!-- Se llenará dinámicamente con JavaScript -->
        </tbody>
    </table>
    </div>
  </main>
    <script src="../../js/sidebar.js"></script>
<!-- -------- -->

<!-- Data Table -->
<script src="../../js/jquery-3.7.1.js"></script>
<script src="../../js/datatables.js"></script>
<script src="../../js/datatables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- JSZip para exportar a Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<!-- pdfmake para exportar a PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<!-- Botones de exportación -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

   <script>
    // Inicializar DataTable
    const table = $("#tabla-ventas").DataTable({
        paging: false, // Desactivar paginación
        scrollCollapse: true, // Permitir que colapse si el contenido es menor
        scrollX: true,
        scrollY: '400px', // Altura fija del scroll
        autoWidth: false, // Prevenir anchos incorrectos
        responsive: true,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Exportar a Excel',
                title: 'Ventas de Producto',
                exportOptions: {
                    columns: ':visible'
                }
            }
        ]
    });

    function CargarListaRecibos(){
    let codigoProducto = document.getElementById("codigo").value;

   // let datos;
    $.ajax({
    url: "productoCliente.data.php",
    dataType: 'json',
    type: "post",
    data: {
      'codigo': codigoProducto
    },
    success: function (object) {               
       //const datos = object;
        // const datos = JSON.stringify(object);             
        // datosInput.value = object;  
        table.clear();
        
        object.forEach(fila => {
            table.row.add([
                fila.departamento,
                fila.municipio,
                fila.cliente,
                fila.cantidad,
                parseFloat(fila.precio).toFixed(2),
                parseFloat(fila.subtotal).toFixed(2),
                fila.ultima_compra,
                fila.cantidad_devuelta,
                parseFloat(fila.precio_unitario_venta).toFixed(2),
                parseFloat(fila.subtotal_devolucion).toFixed(2),
                fila.sobre,
                fila.marca
            ]);            
    });    
     // Dibuja la tabla nuevamente con los datos actualizados
     table.draw();            
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log("Status: " + textStatus);
      console.log("Error: " + errorThrown);
      alertify.error("Ocurrió un error al guardar el registro");
    },
  });
// Ajustar las columnas después de inicializar
table.columns.adjust();
    }

    </script>
</body>
</html>