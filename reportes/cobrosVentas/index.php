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
<body onload="cargarFechas()">
<nav id="sidebar">
    <ul>
      <li>
        <span class="logo">Distruidora MYM</span>
        <button onclick=toggleSidebar() id="toggle-btn">
        <i class='bx bx-chevrons-left' ></i>
        </button>
      </li>
      <li class="active">
        <a href="../../index.php">
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
            <li><a href="../ventas">Saldos</a></li>
            <li><a href="../recibos">Recibos</a></li>
            <li><a href="#">Resumen CV</a></li>
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
        <h2>RESUMEN COBROS Y VENTAS</h2>
    </div>

    <div class="contenedor_tabla">
    <div class="fechas">
        <div class="fecha">
            <label for="fecha_inicio" class="subtitulo_fecha">Fecha Inicio</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" class="campo_fecha">
        </div>
        <div class="fecha">
            <label for="fecha_final" class="subtitulo_fecha">Fecha Final</label>
            <input type="date" id="fecha_final" name="fecha_final" class="campo_fecha">
        </div>
    </div>
    <div class="contenedor_btn">
    <button type="button" id="btnCarga" onclick="CargarListaRecibos()" class="btn_cargar">APLICAR FILTROS</button>
    </div>
 


            <table id="tabla-ventas" class="display" style=" z-index: 1;">
        <thead>
            <tr>
                <th>Vendedor</th>
                <th>Semana</th>
                <th>Cobro Total</th>
                <th>Venta Total</th>
            </tr>
        </thead>
        <tbody id="cuerpoTabla">
            <!-- Se llenará dinámicamente con JavaScript -->
        </tbody>
        <tfoot>
        <tr>
            <th colspan="2">Totales:</th>
            <th id="total-cobro"></th>
            <th id="total-venta"></th>
        </tr>
    </tfoot>
    </table>
    </div>
  </main>
    <script src="../../js/sidebar.js"></script>

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
                title: 'RESUMEN COBROS Y VENTAS',
                exportOptions: {
                    columns: ':visible'
                },
                customizeData: function (data) {
                    // Agregar totales al exportar
                    const totalCobro = table.column(2, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                    const totalVenta = table.column(3, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

                    const totalRow = ['', 'Totales:', totalCobro.toFixed(2), totalVenta.toFixed(2)];
                    data.body.push(totalRow);
                }
            }
        ],
    columnDefs: [
        {
            // Formatea la columna "Total Cobro" (índice 2)
            targets: 2,
            render: function (data, type, row) {
                if (type === 'display' || type === 'filter') {
                    return new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' }).format(data);
                }
                return data; // Devuelve el número original para cálculos
            }
        },
        {
            // Formatea la columna "Total Venta" (índice 3)
            targets: 3,
            render: function (data, type, row) {
                if (type === 'display' || type === 'filter') {
                    return new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' }).format(data);
                }
                return data; // Devuelve el número original para cálculos
            }
        }
    ]
    });

    function CargarListaRecibos(){
    let campoFechaInicio = document.getElementById("fecha_inicio").value;
    let campoFechaFinal = document.getElementById("fecha_final").value;

   // let datos;
    $.ajax({
    url: "cv.data.php",
    dataType: 'json',
    type: "post",
    data: {
      'fechaInicio': campoFechaInicio,
      'fechaFinal': campoFechaFinal
    },
    success: function (object) {               
        //const datos = object;
        // const datos = JSON.stringify(object);             
        // datosInput.value = object;  
        table.clear();
        
        object.forEach(fila => {
            table.row.add([
                fila.nombre_vendedor,
                fila.semana,
                parseFloat(fila.total_cobro).toFixed(2),
                parseFloat(fila.total_venta).toFixed(2)
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
    // Función para calcular y actualizar los totales
function calcularTotales() {
    // Calcula los totales
    const totalCobro = table.column(2, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
    const totalVenta = table.column(3, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

      // Aplica formato de moneda quetzal
      const formatoMoneda = new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' });

      // Actualiza el footer con los totales formateados
      $('#total-cobro').html(formatoMoneda.format(totalCobro));
      $('#total-venta').html(formatoMoneda.format(totalVenta));
  }


  // Ajusta columnas cuando se redibuje la tabla
  table.on('draw', function () {
      calcularTotales();
  });
    </script>

    <script>
function cargarFechas(){
  var fecha = new Date(); //Fecha actual
  var mes = fecha.getMonth()+1; //obteniendo mes
  var dia = fecha.getDate(); //obteniendo dia
  var ano = fecha.getFullYear(); //obteniendo año
  if(dia<10)
    dia='0'+dia; //agrega cero si el menor de 10
  if(mes<10)
    mes='0'+mes //agrega cero si el menor de 10
  document.getElementById('fecha_inicio').value=ano+"-"+mes+"-"+dia;
  document.getElementById('fecha_final').value=ano+"-"+mes+"-"+dia;
}
    </script>

  <!-- Creación de Grafica -->
   <!-- Chart.js -->
<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->


</body>
</html>