// PRODUCTOS_PROMO: lista de productos para usar en los combos/promos, viene del HTML
const PRODUCTOS_PROMO = JSON.parse(document.getElementById('productos-json')?.textContent || '[]');

// _esc: escapa valores nulos o indefinidos, para no mostrar "undefined"
function _esc(v) { return String(v ?? ''); }

// obtenerJson: hace fetch y devuelve JSON, maneja errores tipo red o HTTP
// ojo que si falla devuelve null y loguea por consola
async function obtenerJson(url, opts = {}) {
  try {
    const res = await fetch(url, { cache: 'no-cache', ...opts });
    if (!res.ok) {
      const txt = await res.text().catch(() => null);
      throw new Error(`HTTP ${res.status} ${res.statusText}${txt ? ' — ' + txt : ''}`);
    }
    return await res.json();
  } catch (e) {
    console.error('Error fetch:', url, e);
    return null;
  }
}

// debounce: limita la frecuencia de ejecución de una función (para búsquedas, inputs)
// cuidado: si cambian la demora, puede sentirse más lento o más agresivo
function debounce(fn, ms = 200) {
  let t = null;
  return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
}

// --- Inicialización de eventos al cargar la página ---
document.addEventListener('DOMContentLoaded', () => {
  const btnVer = document.getElementById('ver');
  const btnAgregar = document.getElementById('agregar');
  const btnVolver = document.getElementById('volver');
  const inputBuscar = document.getElementById('buscarInput');
  const btnLimpiar = document.getElementById('limpiarFiltros');

  // botones principales
  if (btnVer) btnVer.addEventListener('click', mostrarListadoPromos);
  if (btnAgregar) btnAgregar.addEventListener('click', () => mostrarFormularioPromo());
  if (btnVolver) btnVolver.addEventListener('click', () => location.href = '../menu.php');

  // input búsqueda con debounce
  if (inputBuscar) inputBuscar.addEventListener('input', debounce(mostrarListadoPromos, 220));

  // limpiar filtros y recargar listado
  if (btnLimpiar) btnLimpiar.addEventListener('click', () => {
    if (inputBuscar) inputBuscar.value = '';
    mostrarListadoPromos();
  });

  mostrarListadoPromos(); // al cargar, mostramos la lista
});

// mostrarListadoPromos: pinta la tabla con todas las promociones
async function mostrarListadoPromos() {
  const datos = await obtenerJson('listar_promociones.php');
  const cont = document.getElementById('lista');
  const contForm = document.getElementById('formulario');
  if (!cont) return console.error('#lista no existe');
  cont.innerHTML = '';
  if (contForm) contForm.innerHTML = ''; // limpiamos formulario si estaba

  if (!Array.isArray(datos) || !datos.length) {
    cont.textContent = 'No hay promociones';
    return;
  }

  // filtrado por búsqueda
  const q = (document.getElementById('buscarInput')?.value || '').toLowerCase().trim();

  // armamos tabla
  const tabla = document.createElement('table');
  tabla.className = 'tabla-promos';

  // encabezados
  const thead = tabla.createTHead();
  const filaH = thead.insertRow();
  ['ID', 'Imagen', 'Nombre', 'Precio', 'Descripción', 'Productos', 'Activo', 'Acciones'].forEach(h => {
    const th = document.createElement('th');
    th.textContent = h;
    filaH.appendChild(th);
  });

  const tbody = tabla.createTBody();

  // filas con datos
  datos.forEach(p => {
    if (q && !(String(p.id_promocion).includes(q) || String(p.nombre).toLowerCase().includes(q))) return;

    const tr = tbody.insertRow();
    tr.insertCell().textContent = p.id_promocion;

    // imagen de la promo
    const tdImg = tr.insertCell();
    tdImg.className = 'col-img';
    if (p.imagen) {
      const img = document.createElement('img');
      img.src = p.imagen;
      img.alt = p.nombre ? `Imagen de ${p.nombre}` : 'Imagen promo';
      img.className = 'img-preview-form';
      tdImg.appendChild(img);
    }

    tr.insertCell().textContent = p.nombre ?? '';
    tr.insertCell().textContent = p.precio ?? '';
    tr.insertCell().textContent = p.descripcion ?? '';

    // productos incluidos en la promo
    const tdProds = tr.insertCell();
    tdProds.textContent = p.productos?.length
      ? p.productos.map(x => `${x.nombre} x ${x.cantidad}`).join(', ')
      : '';

    // checkbox para activar/desactivar promo
    const tdActivo = tr.insertCell();
    tdActivo.style.textAlign = 'center';
    const chk = document.createElement('input');
    chk.type = 'checkbox';
    chk.checked = Number(p.activo) === 1;
    chk.addEventListener('change', async () => {
      const body = new URLSearchParams();
      body.append('id_promocion', p.id_promocion);
      body.append('activo', chk.checked ? 1 : 0);
      await fetch('editar_promocion.php', { method: 'POST', body });
    });
    tdActivo.appendChild(chk);

    // botones de acción
    const tdAcc = tr.insertCell();
    tdAcc.style.textAlign = 'center';

    const btnEditar = document.createElement('button');
    btnEditar.type = 'button';
    btnEditar.className = 'btn-editar';
    btnEditar.textContent = 'Editar';
    btnEditar.addEventListener('click', () => mostrarFormularioPromo(p));
    tdAcc.appendChild(btnEditar);

    const btnBorrar = document.createElement('button');
    btnBorrar.type = 'button';
    btnBorrar.className = 'btn-borrar';
    btnBorrar.textContent = 'Borrar';
    btnBorrar.addEventListener('click', async () => {
      if (!confirm('¿Borrar promoción?')) return;
      btnBorrar.disabled = true;
      const fd = new FormData();
      fd.append('id_promocion', p.id_promocion);
      const res = await obtenerJson('borrar_promocion.php', { method: 'POST', body: fd });
      btnBorrar.disabled = false;
      if (res?.ok) mostrarListadoPromos();
      else alert(res?.error || 'Error al borrar');
    });
    tdAcc.appendChild(btnBorrar);
  });

  cont.appendChild(tabla);
}

// crearSelectProdDom: arma el select de productos para agregar a la promo
function crearSelectProdDom(idSeleccionado = '') {
  const sel = document.createElement('select');
  sel.name = 'producto';
  sel.className = 'form-select';
  const placeholder = document.createElement('option');
  placeholder.value = '';
  placeholder.textContent = 'Seleccionar producto';
  sel.appendChild(placeholder);
  PRODUCTOS_PROMO.forEach(pr => {
    const opt = document.createElement('option');
    opt.value = pr.id_producto;
    opt.textContent = pr.nombre;
    if (String(pr.id_producto) === String(idSeleccionado)) opt.selected = true;
    sel.appendChild(opt);
  });
  return sel;
}

// mostrarFormularioPromo: muestra formulario de alta/edición de promo
function mostrarFormularioPromo(promo = null) {
  const cont = document.getElementById('formulario');
  if (!cont) return console.error('#formulario no existe');
  cont.innerHTML = '';

  // productos seleccionados inicialmente
  let seleccionados = [];
  if (promo?.productos) {
    seleccionados = promo.productos.map(x => ({ id: String(x.id_producto), nombre: x.nombre, cantidad: Number(x.cantidad) }));
  }

  // armado del form
  const form = document.createElement('form');
  form.id = 'formPromo';
  form.enctype = 'multipart/form-data';
  form.className = 'form-grid';

  if (promo) {
    const hid = document.createElement('input');
    hid.type = 'hidden';
    hid.name = 'id_promocion';
    hid.value = promo.id_promocion;
    form.appendChild(hid);
  }

  // columna de datos principales
  const colCampos = document.createElement('div');
  colCampos.className = 'col-datos';

  const inpNombre = document.createElement('input');
  inpNombre.name = 'nombre';
  inpNombre.placeholder = 'Nombre';
  inpNombre.required = true;
  inpNombre.value = promo?.nombre ?? '';
  inpNombre.className = 'form-input';
  colCampos.appendChild(inpNombre);

  const inpPrecio = document.createElement('input');
  inpPrecio.name = 'precio';
  inpPrecio.placeholder = 'Precio';
  inpPrecio.required = true;
  inpPrecio.type = 'number';
  inpPrecio.min = '0';
  inpPrecio.step = '0.01';
  inpPrecio.value = promo?.precio ?? '';
  inpPrecio.className = 'form-input';
  colCampos.appendChild(inpPrecio);

  const inpDesc = document.createElement('input');
  inpDesc.name = 'descripcion';
  inpDesc.placeholder = 'Descripción';
  inpDesc.value = promo?.descripcion ?? '';
  inpDesc.className = 'form-input';
  colCampos.appendChild(inpDesc);

  // controles para agregar productos
  const contProdWrap = document.createElement('div');
  contProdWrap.className = 'agregar-prod-cont';
  const selProd = crearSelectProdDom('');
  selProd.id = 'selProd';
  contProdWrap.appendChild(selProd);
  const inputCant = document.createElement('input');
  inputCant.type = 'number';
  inputCant.id = 'cantProd';
  inputCant.min = '1';
  inputCant.value = '1';
  inputCant.className = 'form-input-cant';
  contProdWrap.appendChild(inputCant);
  const btnAdd = document.createElement('button');
  btnAdd.type = 'button';
  btnAdd.id = 'btnAddProd';
  btnAdd.className = 'btn-agregar-prod';
  btnAdd.textContent = 'Agregar producto';
  contProdWrap.appendChild(btnAdd);
  colCampos.appendChild(contProdWrap);

  // lista visual de productos elegidos
  const listaElegidos = document.createElement('div');
  listaElegidos.id = 'listaProdSeleccionados';
  colCampos.appendChild(listaElegidos);
  form.appendChild(colCampos);

  // columna lateral con imagen
  const colImagen = document.createElement('div');
  colImagen.className = 'col-datos';
  const labelImg = document.createElement('label');
  labelImg.htmlFor = 'inputImagen';
  labelImg.className = 'custom-file-label';
  labelImg.textContent = 'Seleccionar imagen';
  const inpImagen = document.createElement('input');
  inpImagen.type = 'file';
  inpImagen.id = 'inputImagen';
  inpImagen.name = 'imagen';
  inpImagen.accept = 'image/*';
  colImagen.appendChild(labelImg);
  colImagen.appendChild(inpImagen);
  const preview = document.createElement('img');
  preview.id = 'previewPromo';
  preview.className = 'img-preview-form';
  preview.style.display = promo?.imagen ? '' : 'none';
  preview.src = promo?.imagen || '';
  colImagen.appendChild(preview);
  form.appendChild(colImagen);

  // botones guardar/cancelar
  const acciones = document.createElement('div');
  acciones.className = 'form-actions';
  const btnCancelar = document.createElement('button');
  btnCancelar.type = 'button';
  btnCancelar.className = 'btn-cancelar';
  btnCancelar.textContent = 'Cancelar';
  acciones.appendChild(btnCancelar);
  const btnGuardar = document.createElement('button');
  btnGuardar.type = 'submit';
  btnGuardar.className = 'btn-guardar';
  btnGuardar.textContent = promo ? 'Guardar' : 'Agregar';
  acciones.appendChild(btnGuardar);
  form.appendChild(acciones);
  cont.appendChild(form);

  // preview de imagen al seleccionar
  inpImagen.addEventListener('change', function () {
    if (this.files?.[0]) {
      preview.src = URL.createObjectURL(this.files[0]);
      preview.style.display = '';
    } else {
      preview.style.display = 'none';
      preview.src = '';
    }
  });

  // actualizar lista de productos elegidos
  function actualizarListaElegidos() {
    listaElegidos.innerHTML = '';
    seleccionados.forEach((p, idx) => {
      const fila = document.createElement('div');
      fila.className = 'prod-item';
      const nombreSpan = document.createElement('span');
      nombreSpan.className = 'prod-item-name';
      nombreSpan.textContent = `${p.nombre} x ${p.cantidad}`;
      fila.appendChild(nombreSpan);
      const meta = document.createElement('div');
      meta.className = 'prod-item-meta';
      const btnQuitar = document.createElement('button');
      btnQuitar.type = 'button';
      btnQuitar.className = 'btn-quitar';
      btnQuitar.title = 'Quitar';
      btnQuitar.textContent = '×';
      btnQuitar.addEventListener('click', () => {
        seleccionados.splice(idx, 1);
        actualizarListaElegidos();
      });
      meta.appendChild(btnQuitar);
      fila.appendChild(meta);
      listaElegidos.appendChild(fila);
    });
  }

  actualizarListaElegidos();

  // agregar producto seleccionado
  btnAdd.addEventListener('click', () => {
    const id = selProd.value;
    const cantidad = parseInt(inputCant.value) || 0;
    if (!id || cantidad <= 0) return;
    const existente = seleccionados.find(x => x.id === id);
    if (existente) existente.cantidad += cantidad;
    else {
      const prod = PRODUCTOS_PROMO.find(p => String(p.id_producto) === id);
      if (!prod) return;
      seleccionados.push({ id, nombre: prod.nombre, cantidad });
    }
    actualizarListaElegidos();
  });

  // cancelar: limpia el contenedor
  btnCancelar.addEventListener('click', () => { cont.innerHTML = ''; });

  // envío del formulario
  form.addEventListener('submit', async e => {
    e.preventDefault();
    if (!seleccionados.length && !confirm('No hay productos. Guardar igual?')) return;
    const fd = new FormData(form);
    seleccionados.forEach(p => fd.append(`productos[${p.id}]`, p.cantidad));
    const url = promo ? 'editar_promocion.php' : 'agregar_promocion.php';
    const res = await obtenerJson(url, { method: 'POST', body: fd });
    if (res?.ok) {
      cont.innerHTML = '';
      mostrarListadoPromos();
    } else alert(res?.error || 'Error en el servidor');
  });
}
