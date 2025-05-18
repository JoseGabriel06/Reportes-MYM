import './css/filtros.css'

function Filtros({ancho, children, icono}) {
  
  return (
    <div className="contenedor_filtros" style={{
      width: ancho
    }}>
      <h3 className="titulo_filtros">Filtros</h3>
      <i className={'bx ' + icono +' icono_card'}></i>
      <div className='contenedor_campos_filtros'>
        {children}
      </div>
    </div>
  );
}

export { Filtros };