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
<body onload="cargarListaDepartamentos(),cargarListaVendedores(),cargarFechas()">
<!-- ---------- -->
 <input type="hidden" id="inputDatos" name="inputDatos"/>
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
          <li><a href="#">Recibos</a></li>
          <li><a href="../cobrosVentas">Resumen CV</a></li>
          <li><a href="../productoClientes">Producto Clientes</a></li>
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
        <h2>RECIBOS VENTAS - OPERADOS</h2>
    </div>

    <div class="contenedor_tabla">
    <div class="filtros">
    <select id="filtro-departamento" class="selectores"  onchange="cargarListaMunicipios()">       
    </select>

    <select id="filtro-municipio" class="selectores">       
    <option value=TODOS>TODOS</option> 
    </select>

    <select id="filtro-vendedor" class="selectores">        
    </select>
</div>
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
                <th>ID Recibo</th>
                <th>ID Detalle Recibo</th>
                <th>Fecha Registro</th>
                <th>Semana</th>
                <th>Ejecutivo</th>
                <th>No.Envío</th>
                <th>Monto</th>
                <th>Abono</th>
                <th>Saldo</th>
                <th>Pago</th>
                <th>Fecha Recibo</th>
                <th>Año</th>
                <th>Recibo</th>
                <th>Cobro</th>
                <th>Recibo Operado</th>
                <th>Cobro Operado</th>
                <th>Concepto Operado</th>
                <th>Usuario Que Opera</th>
                <th>Vendedor Operado</th>
                <th>Observación</th>
                <th>No.Deposito</th>
                <th>No.Cheque</th>
                <th>Pago Total</th>
                <th>Banco</th>
                <th>Cobrado</th>
                <th>Nombre Cliente</th>
                <th>Departamento</th>
                <th>Municipio</th>
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
                title: 'Reporte de Ventas MYM',
                exportOptions: {
                    columns: ':visible'
                },
                customizeData: function (data) {
                    // Agregar totales al exportar
                    const totalMonto = table.column(6, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                    const totalAbono = table.column(7, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                    const totalSaldo = table.column(8, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

                    const totalRow = ['', '', '', '', '', 'Totales:', totalMonto.toFixed(2), totalAbono.toFixed(2), totalSaldo.toFixed(2), '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''];
                    data.body.push(totalRow);
                }
            }
        ]
    });

    function CargarListaRecibos(){
    let datosInput = document.getElementById("inputDatos");
    let departamentoSelect = document.getElementById("filtro-departamento");
    let municipioSelect = document.getElementById("filtro-municipio");    
    let vendedorSelect = document.getElementById("filtro-vendedor");

    let departamento = departamentoSelect.options[departamentoSelect.selectedIndex].text;
    let municipio = municipioSelect.options[municipioSelect.selectedIndex].text;
    let vendedor = vendedorSelect.options[vendedorSelect.selectedIndex].text;

    let campoFechaInicio = document.getElementById("fecha_inicio").value;
    let campoFechaFinal = document.getElementById("fecha_final").value;

   // let datos;
    $.ajax({
    url: "recibos.data.php",
    dataType: 'json',
    type: "post",
    data: {
      'fechaInicio': campoFechaInicio,
      'fechaFinal': campoFechaFinal, 
      'departamento':departamento,     
      'municipio':municipio,
      'vendedor':vendedor,
    },
    success: function (object) {               
       //const datos = object;
        // const datos = JSON.stringify(object);             
        // datosInput.value = object;  
        table.clear();
        
        object.forEach(fila => {
            table.row.add([
                fila.id_recibo,
                fila.id_detalle_recibo,
                fila.fecha_registro,
                fila.semana,
                fila.ejecutivo,
                fila.no_envio,
                parseFloat(fila.monto).toFixed(2),
                parseFloat(fila.abono).toFixed(2),
                parseFloat(fila.saldo).toFixed(2),
                parseFloat(fila.pago).toFixed(2),
                fila.fecha_recibo,
                fila.anio,
                fila.recibo,
                parseFloat(fila.cobro).toFixed(2),
                fila.recibo_operado,
                fila.cobro_operado,
                fila.concepto_operado,
                fila.usuario_opera,
                fila.vendedor_operado,
                fila.observacion,
                fila.no_deposito,
                fila.no_cheque,
                fila.pago_total,
                fila.banco,
                fila.cobrado,
                fila.nombre_cliente,
                fila.departamento,
                fila.municipio
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
    

        // Actualizar gráfica al redibujar la tabla
        // table.on('draw', function () {
        //     actualizarGrafica(table);
        // });

        // Escuchar filtros dinámicos
        // $('#filtro-departamento, #filtro-municipio, #filtro-vendedor').on('change', function () {
        //     const departamento = $('#filtro-departamento').val();
        //     const municipio = $('#filtro-municipio').val();
        //     const vendedor = $('#filtro-vendedor').val();
            
        //     table.column(0).search(departamento || '');
        //     table.column(1).search(municipio || '');
        //     table.column(2).search(vendedor || '');
        //     table.draw();
        // });



    // // Escuchar el evento `draw` para recalcular los totales dinámicamente
    // table.on('draw', function () {
    //     calculateTotals(table);
    // });


// // Escuchar el evento `draw` para actualizar la gráfica
// table.on('draw', function () {
//     actualizarGraficaDesdeTabla(table);
// });




    // Filtros dinámicos: Departamento, Municipio, Vendedor
    // $('#filtro-departamento, #filtro-municipio, #filtro-vendedor').on('change', function () {
    //     // Obtener valores de los filtros
    //     const departamento = $('#filtro-departamento').val();
    //     const municipio = $('#filtro-municipio').val();
    //     const vendedor = $('#filtro-vendedor').val();

    //     // Aplicar filtros
    //     table.column(26).search(departamento || ''); // Filtrar por Departamento (Columna 0)
    //     table.column(27).search(municipio || '');   // Filtrar por Municipio (Columna 1)
    //     table.column(18).search(vendedor || '');   // Filtrar por Vendedor (Columna 2)

    //     // Redibujar tabla
    //     table.draw();
    // });

    </script>

    <!-- <script src="js/selectDinamico.js"></script> -->

    <script>
        function cargarListaDepartamentos()
        {
        var $select = $("#filtro-departamento");

        $.ajax({
    url: "filtros.data.php",
    dataType: 'json',
    type: "post",
    data: {
      'campo': 'nombre',
      'tabla': 'db_rmym.adm_departamentopais',      
    },
    success: function (object) {  
        $select.append("<option value=TODOS>TODOS</option>");     
        object.forEach(departamento => {
            $select.append("<option value=" + departamento + ">" + departamento + "</option>");          
        });                  
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log("Status: " + textStatus);
      console.log("Error: " + errorThrown);
      alertify.error("Ocurrió un error al guardar el registro");
    },
  });
}

function cargarListaMunicipios()
        {            
        let selectMunicipio = $("#filtro-municipio");
        let selectDepartamento = document.getElementById("filtro-departamento");
        let nombreDepartamento = selectDepartamento.value; 
        
        $.ajax({
    url: "filtro.municipios.data.php",
    dataType: 'json',
    type: "post",
    data: {
      'departamento': nombreDepartamento,      
    },
    success: function (object) {  
        selectMunicipio.append("<option value=TODOS>TODOS</option>");     
        object.forEach(municipio => {
            selectMunicipio.append("<option value=" + municipio + ">" + municipio + "</option>");          
        });                  
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log("Status: " + textStatus);
      console.log("Error: " + errorThrown);
      alertify.error("Ocurrió un error al guardar el registro");
    },
  });
}

function cargarListaVendedores()
        {
        var selectVendedor = $("#filtro-vendedor");

        $.ajax({
    url: "filtro.vendedor.data.php",
    dataType: 'json',
    type: "post",    
    success: function (object) {  
        selectVendedor.append("<option value=TODOS>TODOS</option>");     
        object.forEach(vendedor => {
            selectVendedor.append("<option value=" + vendedor + ">" + vendedor + "</option>");          
        });                  
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log("Status: " + textStatus);
      console.log("Error: " + errorThrown);
      alertify.error("Ocurrió un error al guardar el registro");
    },
  });
}
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
</body>
</html>