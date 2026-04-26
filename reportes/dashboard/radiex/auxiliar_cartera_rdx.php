<?php
?>
<!DOCTYPE html>
<html>

<style>
    body {
        font-family: Roboto, Arial;
        background: #f5f7fb;
        margin: 0;
        padding: 25px;
    }

    .header-box {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, .08);
        margin-bottom: 20px;
    }

    .titulo {
        font-size: 30px;
        font-weight: 700;
        color: #16355c;
        margin-bottom: 15px;
    }

    .filtros {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }

    .filtros select,
    .filtros input {
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 6px;
    }

    .btn {
        background: #16355c;
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn:hover {
        background: #204c84;
    }

    .btn-volver {
        background: #198754;
    }

    .kpis {
        display: flex;
        gap: 20px;
        margin-top: 20px;
    }

    .kpi {
        background: #eef4ff;
        padding: 18px;
        border-radius: 10px;
        min-width: 220px;
        font-weight: 600;
    }

    #tabla {
        background: white;
    }

    tfoot {
        font-weight: bold;
        background: #eaf1fb;
    }

    .dt-center {
        text-align: center;
    }

    table.dataTable tbody tr:hover {
        background: #eef5ff;
    }
</style>

<head>

    <title>Auxiliar Cartera Radiex</title>

    <link rel="stylesheet"
        href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <link rel="stylesheet"
        href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>



</head>

<body>

    <div class="header-box">

        <div style="
display:flex;
justify-content:space-between;
align-items:center;
">

            <div class="titulo">
                Auxiliar de Cartera Radiex
            </div>

            <a href="../../../index.php">
                <button class="btn btn-volver">
                    ← Menú Principal
                </button>
            </a>

        </div>

        <div class="filtros">
            <div style="margin-bottom:20px">
                <?php
                date_default_timezone_set('America/Guatemala');
                
                $primerDiaMes = date('Y-m-01');
                $hoy = date('Y-m-d');
                ?>
                Fecha inicio
                <input
                    type="date"
                    id="fechaInicio"
                    value="<?= $primerDiaMes ?>">

                Fecha fin
                <input
                    type="date"
                    id="fechaFinal"
                    value="<?= $hoy ?>">

                Sucursal
                <select id="sucursal">
                    <option value="1">Central</option>
                    <option value="2">Petén</option>
                    <option value="3">Xela</option>
                </select>

                Tipo Pago
                <select id="tipoPago">
                    <option value="TODOS">Todos</option>
                    <option value="CONTADO">Contado</option>
                    <option value="CREDITO">Crédito</option>
                </select>

                Departamento
                <select id="departamento">
                    <option value="">Todos</option>
                </select>

                Municipio
                <select id="municipio">
                    <option value="">Todos</option>
                </select>

                <button id="buscar">
                    Buscar
                </button>

            </div>
        </div>

        <div class="kpis">

            <div class="kpi">
                Clientes con saldo:
                <span id="kpiClientes">0</span>
            </div>

            <div class="kpi">
                Saldo cartera:
                Q <span id="kpiSaldo">0.00</span>
            </div>

        </div>

    </div>

    <table id="tabla" class="display">

        <thead>
            <tr>
                <th>Fecha</th>
                <th>Documentos</th>
                <th>Cliente</th>
                <th>Departamento</th>
                <th>Municipio</th>
                <th>Tipo Pago</th>
                <th>Pendiente</th>
                <th>Días</th>
                <th>Vendedor</th>
            </tr>
        </thead>

        <tfoot>
            <tr>
                <th colspan="6"
                    style="text-align:right">
                    TOTAL:
                </th>

                <th id="totalFooter"></th>

                <th></th>
                <th></th>

            </tr>
        </tfoot>

    </table>


    <script>
        function cargarDepartamentos() {

            $.post(
                'get_departamentos.php', {
                    sucursal: $("#sucursal").val()
                },
                function(data) {

                    $("#departamento")
                        .empty()
                        .append('<option value="">Todos</option>');

                    data.forEach(function(r) {

                        $("#departamento").append(
                            '<option value="' + r.iddepartamento + '">' +
                            r.nombre +
                            '</option>'
                        );

                    });

                },
                'json'
            );

        }



        function cargarMunicipios() {

            $.post(
                'get_municipios.php', {
                    sucursal: $("#sucursal").val(),
                    departamento: $("#departamento").val()
                },
                function(data) {

                    $("#municipio")
                        .empty()
                        .append('<option value="">Todos</option>');

                    data.forEach(function(r) {

                        $("#municipio").append(
                            '<option value="' + r.id_municipio + '">' +
                            r.nombre +
                            '</option>'
                        );

                    });

                },
                'json'
            );

        }



        $("#sucursal").change(function() {

            cargarDepartamentos();

        });


        $("#departamento").change(function() {

            cargarMunicipios();

        });



        var tabla = null;

        $("#buscar").click(function() {

            if (tabla) {
                tabla.destroy();
            }

            tabla =
                $('#tabla').DataTable({

                    processing: true,

                    ajax: {
                        url: 'get_auxiliar_cartera_rdx.php',
                        type: 'POST',

                        data: function(d) {

                            d.fechaInicio = $("#fechaInicio").val();
                            d.fechaFinal = $("#fechaFinal").val();

                            d.sucursal = $("#sucursal").val();

                            d.tipoPago = $("#tipoPago").val();

                            d.departamento = $("#departamento").val();

                            d.municipio = $("#municipio").val();

                        },

                        dataSrc: function(json) {

                            $("#kpiClientes").text(
                                json.totalClientes
                            );

                            $("#kpiSaldo").text(
                                parseFloat(
                                    json.totalPendiente
                                ).toLocaleString()
                            );

                            $("#totalFooter").html(
                                'Q ' +
                                parseFloat(
                                    json.totalPendiente
                                ).toLocaleString()
                            );

                            return json.data;

                        }

                    },

                    dom: 'Bfrtip',

                    buttons: [

                        {
                            extend: 'excelHtml5',

                            title: 'Auxiliar_Cartera_Radiex',

                            footer: false,

                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                            },

                            customize: function(xlsx) {

                                var sheet =
                                    xlsx.xl.worksheets['sheet1.xml'];

                                var lastRow =
                                    $('row', sheet).length + 1;

                                $('sheetData', sheet).append(
                                    '<row r="' + lastRow + '">' +
                                    '<c r="F' + lastRow + '" t="inlineStr">' +
                                    '<is><t>TOTAL CARTERA</t></is>' +
                                    '</c>' +
                                    '<c r="G' + lastRow + '" t="inlineStr">' +
                                    '<is><t>' +
                                    $("#totalFooter").text() +
                                    '</t></is>' +
                                    '</c>' +
                                    '</row>'
                                );

                            }

                        },

                        {
                            extend: 'pdfHtml5',

                            title: 'Auxiliar de Cartera Radiex',

                            orientation: 'landscape',

                            pageSize: 'A4',

                            footer: false,

                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                            },

                            customize: function(doc) {

                                doc.pageMargins = [20, 40, 20, 35];

                                doc.defaultStyle.fontSize = 10;

                                doc.styles.tableHeader.fontSize = 10;

                                doc.content[1].table.widths = [
                                    65,
                                    45,
                                    120,
                                    90,
                                    110,
                                    55,
                                    65,
                                    35,
                                    110
                                ];


                                /* quitar footer automático */
                                if (doc.content[doc.content.length - 1].text === "TOTAL") {
                                    doc.content.pop();
                                }


                                doc.content.unshift({
                                    text: 'Distribuidora MYM\nAuxiliar de Cuentas por Cobrar Radiex',
                                    alignment: 'center',
                                    fontSize: 16,
                                    bold: true
                                });


                                doc.content.push({
                                    text: '\nTOTAL CARTERA: ' + $("#totalFooter").text(),
                                    alignment: 'right',
                                    fontSize: 11,
                                    bold: true
                                });

                            }

                        }

                    ],

                    columns: [

                        {
                            data: 'fecha',
                            render: function(data) {

                                let f = new Date(data);

                                return f.toLocaleDateString('es-GT');

                            }
                        },

                        {
                            data: 'documento'
                        },

                        {
                            data: 'cliente'
                        },

                        {
                            data: 'departamento'
                        },

                        {
                            data: 'municipio'
                        },

                        {
                            data: 'tipo_pago'
                        },

                        {
                            data: 'pendiente',
                            render: function(data) {
                                return 'Q ' +
                                    parseFloat(data).toLocaleString(
                                        undefined, {
                                            minimumFractionDigits: 2
                                        }
                                    );
                            }
                        },

                        {
                            data: 'dias_vencidos',
                            className: 'dt-center'
                        },

                        {
                            data: 'vendedor'
                        }

                    ]

                });

        });


        cargarDepartamentos();
    </script>


</body>

</html>