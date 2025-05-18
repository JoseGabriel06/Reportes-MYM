$.ajax({
    url: "/consulta_saldo.php",
    dataType: 'json',
    type: "post",    
    success: function (object) {  
        localStorage.setItem('ajaxData', JSON.stringify(object));        
        console.log(JSON.parse(object));
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log("Status: " + textStatus);
      console.log("Error: " + errorThrown);
      alertify.error("Ocurrió un error al guardar el registro");
    },
  });

// const saldo = JSON.parse(localStorage.getItem('ajaxData'));
// console.log(saldo);
// export {saldo}