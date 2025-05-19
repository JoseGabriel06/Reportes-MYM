$(document).ready(function () {
    $('#filtro-departamento').on('change', function () {
        const departamento = $(this).val();
        if (departamento) {
            // Mostrar "Cargando municipios..." mientras se obtienen municipios
            $('#filtro-municipio').html('<option value="">Cargando municipios...</option>');
            // Hacer la solicitud AJAX para cargar municipios
            $.ajax({
                url: '../peten/data/obtener_municipios.php',
                method: 'POST',
                data: {departamento},
                success: function (data) {
                    console.log(data);
                    $('#filtro-municipio').html(data); // Llenar el selector de municipios
                },
                error: function (err) {
                    console.log(err);
                    alert('Error al cargar los municipios.');
                }
            });

            // Filtrar la tabla por el departamento seleccionado
            const datosFiltrados = data.filter(fila => fila.departamento === departamento);
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
                fila.departamento,
                fila.municipio,
                fila.vendedor,
                fila.cliente,
                fila.seriefactura,
                fila.nofactura,
                formatCurrency(fila.monto),
                formatCurrency(fila.abono),
                formatCurrency(fila.saldo),
                fila.fecha_envio,
                fila.fecha_vencimiento,
                fila.dias_vencidos
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