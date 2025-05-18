import './css/filtro.css'

function Filtro(props) {
  return (
    <div className="contenedor_filtro">
      <label htmlFor={props.identificador} className='subtitulo'>{props.nombre}</label>
      <select 
        name={props.identificador} 
        id={props.identificador} 
        className='selector'
        onChange={(e) => props.onChange(e.target.value)} // Manejamos el cambio aquí
      >
        <option value="central">Central</option>
        <option value="peten">Péten</option>
        <option value="xela">Xela</option>
        <option value="todas">Todas</option>
      </select>
    </div>
  );
}

export { Filtro };

