$(document).ready(function () {
    $('#filtro-departamento').on('change', function () {
        const departamento = $(this).val();

        if (departamento) {
            // Mostrar "Cargando municipios..." mientras se obtienen municipios
            $('#filtro-municipio').html('<option value="">Cargando municipios...</option>');

            // Hacer la solicitud AJAX para cargar municipios
            $.ajax({
                url: '../obtener_municipios.php',
                method: 'POST',
                data: { departamento },
                success: function (data) {
                    $('#filtro-municipio').html(data); // Llenar el selector de municipios
                },
                error: function () {
                    alert('Error al cargar los municipios.');
                }
            });

            // Filtrar la tabla por el departamento seleccionado
            const datosFiltrados = datos.filter(fila => fila.departamento === departamento);
            
            actualizarTabla(datosFiltrados);
        } else {
            // Si se selecciona "Todos los Departamentos"
            $('#filtro-municipio').html('<option value="">Todos los Municipios</option>');

            // Restaurar la tabla con los datos originales
            actualizarTabla(datosOriginales);
        }
    });

    // Función para actualizar la tabla con los datos especificados
    function actualizarTabla(data) {
        table.clear(); // Limpiar la tabla
        data.forEach(fila => {
            table.row.add([
                fila.id_recibo,
                fila.id_detalle_recibo,
                fila.fecha_registro,
                fila.semana,
                fila.ejecutivo,
                fila.no_envio,
                formatCurrency(fila.monto),
                formatCurrency(fila.abono),
                formatCurrency(fila.saldo),
                formatCurrency(fila.pago),
                fila.fecha_recibo,
                fila.anio,
                fila.recibo,
                formatCurrency(fila.cobro),
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
        table.draw(); // Redibujar la tabla
    }

    // Función para formatear moneda
    function formatCurrency(value) {
        return `Q ${Number(value).toLocaleString('es-GT', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })}`;
    }
});