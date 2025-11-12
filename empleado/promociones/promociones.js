// PRODUCTOS_PROMO: lista de productos para usar en los combos/promos, viene del HTML
const PRODUCTOS_PROMO = JSON.parse(document.getElementById('productos-json')?.textContent || '[]');

// escaparHtml: escapa caracteres especiales para evitar que se rompa el HTML
function escaparHtml(s) {
  return String(s ?? '').replace(/[&<>"'`=\/]/g, c => ({
    '&': '&',
    '<': '<',
    '>': '>',
    '"': '"',
    "'": '&#39;',
    '/': '&#x2F;',
    '`': '&#60;',
    '=': '&#x3D;',
  })[c]);
}

// escaparImagen: corrige la ruta de las imágenes de promociones
function escaparImagen(img) {
  if (!img) return '';
  
  // Si la imagen ya tiene la ruta correcta, la devolvemos tal cual
  if (img.startsWith('img/')) {
    return img;
  }
  
  // Si es solo el nombre del archivo, le agregamos la ruta correcta
  if (img.startsWith('promo_')) {
    return 'img/' + img;
  }
  
  // Para cualquier otro caso, devolvemos la imagen tal cual
  return img;
}

// fetchJson: hace fetch a la URL pasada y devuelve JSON
async function fetchJson(url, opts = {}) {
  try {
    const r = await fetch(url, { cache: 'no-cache', ...opts });
    if (!r.ok) {
      const errData = await r.json().catch(() => null);
      throw { status: r.status, data: errData };
    }
    return await r.json();
  } catch (e) {
    const errorMsg = e.data?.error || `Error de conexión (${e.status || 'Cliente'})`;
    alert(errorMsg); // simple alert for now
    return null;
  }
}

// mostrarMensaje: marcador de posición para mensajes
function mostrarMensaje(msg, type) {
  alert(msg);
}

// Función para filtrar promociones
function filtrarPromociones() {
  const searchInput = document.getElementById('searchInput');
  const query = searchInput ? searchInput.value.toLowerCase() : '';
  const items = document.querySelectorAll('#promociones-section tbody tr[data-name]');

  items.forEach(item => {
    // Obtener datos de la fila usando los atributos de datos
    const name = item.getAttribute('data-name') || '';
    const productos = item.getAttribute('data-productos') || '';
    const activo = item.getAttribute('data-activo') || '';
    
    // Verificar coincidencias
    const matchesName = name.includes(query);
    const matchesProductos = productos.includes(query);
    const matchesActivo = query === 'activo' || query === 'si' ? activo.includes('si') : 
                         query === 'inactivo' || query === 'no' ? activo.includes('no') : 
                         false;
    
    // Mostrar u ocultar fila según coincidencias
    const shouldShow = !query || matchesName || matchesProductos || matchesActivo;
    item.style.display = shouldShow ? 'table-row' : 'none';
  });
}

// Función para limpiar filtros
function limpiarFiltros() {
  const searchInput = document.getElementById('searchInput');
  if (searchInput) searchInput.value = '';
  filtrarPromociones();
}

// renderizarTabla: arma la tabla de promociones
function renderizarTabla(datos) {
  const cont = document.getElementById('promociones-section');
  if (!cont) return;

  if (!Array.isArray(datos) || !datos.length) {
    cont.innerHTML = '<p>No hay promociones</p>';
    return;
  }

  const tabla = document.createElement('table');
  const thead = tabla.createTHead();
  const filaH = thead.insertRow();
  ['ID', 'Imagen', 'Nombre', 'Precio', 'Descripción', 'Productos', 'Activo', 'Acciones'].forEach(h => {
    const th = document.createElement('th');
    th.textContent = h;
    filaH.appendChild(th);
  });

  const tbody = tabla.createTBody();

  datos.forEach(p => {
    const tr = tbody.insertRow();
    tr.setAttribute('data-name', (p.nombre || '').toLowerCase());
    tr.setAttribute('data-activo', p.activo == 1 ? 'si' : 'no');
    
    // Agregar productos como atributo de datos para búsqueda
    if (p.productos && p.productos.length) {
      const productosNombres = p.productos.map(prod => prod.nombre.toLowerCase()).join(',');
      tr.setAttribute('data-productos', productosNombres);
    }

    tr.insertCell().textContent = p.id_promocion;

    const tdImg = tr.insertCell();
    if (p.imagen) {
      const img = document.createElement('img');
      img.src = escaparImagen(p.imagen);
      img.alt = p.nombre ? `Imagen de ${p.nombre}` : 'Imagen promo';
      img.className = 'img-preview-table';
      tdImg.appendChild(img);
    }

    tr.insertCell().textContent = p.nombre ?? '';
    tr.insertCell().textContent = p.precio ?? '';
    tr.insertCell().textContent = p.descripcion ?? '';

    const tdProds = tr.insertCell();
    tdProds.textContent = p.productos?.length
      ? p.productos.map(x => `${x.nombre} x ${x.cantidad}`).join(', ')
      : 'Sin productos';
    tdProds.title = tdProds.textContent;

    tr.insertCell().textContent = p.activo == 1 ? 'Sí' : 'No';

    const tdAcc = tr.insertCell();
    // Crear un contenedor para los botones con mejor separación visual
    const buttonsContainer = document.createElement('div');
    buttonsContainer.style.display = 'flex';
    buttonsContainer.style.gap = '5px';
    buttonsContainer.style.flexWrap = 'wrap';
    
    // Botones de edición y borrado
    const editButton = document.createElement('button');
    editButton.className = 'btn-editar';
    editButton.textContent = 'Editar';
    editButton.setAttribute('data-id', p.id_promocion);
    
    const deleteButton = document.createElement('button');
    deleteButton.className = 'btn-borrar';
    deleteButton.textContent = 'Borrar';
    deleteButton.setAttribute('data-id', p.id_promocion);
    
    // Botón de activación/desactivación
    const toggleButton = document.createElement('button');
    toggleButton.className = p.activo == 1 ? 'btn-desactivar' : 'btn-activar';
    toggleButton.textContent = p.activo == 1 ? 'Desactivar' : 'Activar';
    toggleButton.setAttribute('data-id', p.id_promocion);
    
    // Añadir botones al contenedor
    buttonsContainer.appendChild(editButton);
    buttonsContainer.appendChild(deleteButton);
    buttonsContainer.appendChild(toggleButton);
    
    // Añadir contenedor a la celda
    tdAcc.appendChild(buttonsContainer);
  });

  cont.innerHTML = '';
  cont.appendChild(tabla);

  // Agregar event listeners a botones
  cont.querySelectorAll('.btn-editar').forEach(btn => {
    btn.addEventListener('click', () => mostrarFormulario('editar', parseInt(btn.dataset.id)));
  });

  cont.querySelectorAll('.btn-borrar').forEach(btn => {
    btn.addEventListener('click', () => borrarPromocion(parseInt(btn.dataset.id)));
  });
}

// cambiarActivo: cambia el estado activo de la promo
async function cambiarActivo(id, activo) {
  const body = new FormData();
  body.append('id_promocion', id);
  body.append('activo', activo ? 1 : 0);
  await fetchJson('editar_promocion.php', { method: 'POST', body });
}

// crearSelectProdDom: arma el select de productos
function crearSelectProdDom(idSeleccionado = '') {
  const sel = document.createElement('select');
  sel.name = 'producto';
  sel.className = 'form-input';
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
  if (!cont) return;
  cont.innerHTML = '';

  let seleccionados = [];
  if (promo?.productos) {
    seleccionados = promo.productos.map(x => ({ id: String(x.id_producto), nombre: x.nombre, cantidad: Number(x.cantidad) }));
  }

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

  const colCampos = document.createElement('div');
  colCampos.className = 'form-group';

  // Sección de información básica de la promoción
  const basicInfoSection = document.createElement('div');
  basicInfoSection.className = 'form-section';
  
  const inpNombre = document.createElement('input');
  inpNombre.name = 'nombre';
  inpNombre.placeholder = 'Nombre';
  inpNombre.required = true;
  inpNombre.value = promo?.nombre ?? '';
  inpNombre.className = 'form-input';
  basicInfoSection.appendChild(inpNombre);

  const inpPrecio = document.createElement('input');
  inpPrecio.name = 'precio';
  inpPrecio.placeholder = 'Precio';
  inpPrecio.required = true;
  inpPrecio.type = 'number';
  inpPrecio.min = '0';
  inpPrecio.step = '0.01';
  inpPrecio.value = promo?.precio ?? '';
  inpPrecio.className = 'form-input';
  basicInfoSection.appendChild(inpPrecio);

  const inpDesc = document.createElement('textarea');
  inpDesc.name = 'descripcion';
  inpDesc.placeholder = 'Descripción';
  inpDesc.value = promo?.descripcion ?? '';
  inpDesc.className = 'form-input';
  basicInfoSection.appendChild(inpDesc);
  
  colCampos.appendChild(basicInfoSection);

  // Sección de selección de productos
  const productSection = document.createElement('div');
  productSection.className = 'form-section';
  
  const productHeader = document.createElement('h3');
  productHeader.textContent = 'Seleccionar productos';
  productSection.appendChild(productHeader);

  const contProdWrap = document.createElement('div');
  contProdWrap.className = 'product-selection-controls';
  
  const selProd = crearSelectProdDom('');
  selProd.id = 'selProd';
  contProdWrap.appendChild(selProd);
  
  const inputCant = document.createElement('input');
  inputCant.type = 'number';
  inputCant.id = 'cantProd';
  inputCant.min = '1';
  inputCant.value = '1';
  inputCant.className = 'form-input quantity-input';
  contProdWrap.appendChild(inputCant);
  
  const btnAdd = document.createElement('button');
  btnAdd.type = 'button';
  btnAdd.id = 'btnAddProd';
  btnAdd.textContent = 'Agregar producto';
  btnAdd.className = 'btn-agregar';
  contProdWrap.appendChild(btnAdd);
  
  productSection.appendChild(contProdWrap);

  const listaElegidos = document.createElement('div');
  listaElegidos.id = 'listaProdSeleccionados';
  productSection.appendChild(listaElegidos);
  
  colCampos.appendChild(productSection);
  form.appendChild(colCampos);

  // Sección de imagen
  const colImagen = document.createElement('div');
  colImagen.className = 'form-group';
  
  const imageSection = document.createElement('div');
  imageSection.className = 'form-section';
  
  const imageHeader = document.createElement('h3');
  imageHeader.textContent = 'Imagen de la promoción';
  imageSection.appendChild(imageHeader);
  
  const labelImg = document.createElement('label');
  labelImg.htmlFor = 'inputImagen';
  labelImg.textContent = 'Seleccionar imagen';
  labelImg.className = 'custom-file-label';
  
  const inpImagen = document.createElement('input');
  inpImagen.type = 'file';
  inpImagen.id = 'inputImagen';
  inpImagen.name = 'imagen';
  inpImagen.accept = 'image/*';
  // Mantener oculto el input real
  inpImagen.style.display = 'none';
  
  const fileNameSpan = document.createElement('span');
  fileNameSpan.className = 'file-name';
  
  const preview = document.createElement('img');
  preview.id = 'previewPromo';
  preview.className = 'img-preview-form';
  preview.style.display = promo?.imagen ? '' : 'none';
  preview.src = promo?.imagen ? escaparImagen(promo.imagen) : '';
  
  imageSection.appendChild(labelImg);
  imageSection.appendChild(inpImagen);
  imageSection.appendChild(fileNameSpan);
  imageSection.appendChild(preview);
  colImagen.appendChild(imageSection);
  form.appendChild(colImagen);

  const acciones = document.createElement('div');
  acciones.className = 'form-actions';
  const btnCancelar = document.createElement('button');
  btnCancelar.type = 'button';
  btnCancelar.textContent = 'Cancelar';
  acciones.appendChild(btnCancelar);
  const btnGuardar = document.createElement('button');
  btnGuardar.type = 'submit';
  btnGuardar.textContent = promo ? 'Guardar' : 'Agregar';
  acciones.appendChild(btnGuardar);
  form.appendChild(acciones);

  cont.appendChild(form);

  // Agregar funcionalidad de selección de imagen
  labelImg.addEventListener('click', (e) => {
    e.preventDefault();
    inpImagen.click();
  });

  inpImagen.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
      preview.src = URL.createObjectURL(file);
      preview.style.display = 'block';
      fileNameSpan.textContent = file.name;
    }
  });

  function actualizarListaElegidos() {
    listaElegidos.innerHTML = '';
    seleccionados.forEach((p, idx) => {
      const fila = document.createElement('div');
      fila.textContent = `${p.nombre} x ${p.cantidad}`;
      const btnQuitar = document.createElement('button');
      btnQuitar.type = 'button';
      btnQuitar.textContent = '×';
      btnQuitar.addEventListener('click', () => {
        seleccionados.splice(idx, 1);
        actualizarListaElegidos();
      });
      fila.appendChild(btnQuitar);
      listaElegidos.appendChild(fila);
    });
  }

  actualizarListaElegidos();

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

  btnCancelar.addEventListener('click', () => { cont.innerHTML = ''; });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    if (!seleccionados.length && !confirm('No hay productos. Guardar igual?')) return;
    const fd = new FormData(form);
    seleccionados.forEach(p => fd.append(`productos[${p.id}]`, p.cantidad));
    const url = promo ? 'editar_promocion.php' : 'agregar_promocion.php';
    const res = await fetchJson(url, { method: 'POST', body: fd });
    if (res?.ok) {
      cont.innerHTML = '';
      mostrarTabla();
    } else alert(res?.error || 'Error en el servidor');
  });
}

// mostrarTabla: carga y muestra la tabla
async function mostrarTabla() {
  const datos = await fetchJson('listar_promociones.php');
  renderizarTabla(datos);
}

// funciones helper
function mostrarFormAgregar() {
  mostrarFormularioPromo();
}

function mostrarFormEditar(id) {
  fetchJson('listar_promociones.php').then(datos => {
    const promo = datos.find(p => p.id_promocion == id);
    if (promo) mostrarFormularioPromo(promo);
  });
}

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('btnAgregar')?.addEventListener('click', mostrarFormAgregar);
  document.getElementById('btnBuscar')?.addEventListener('click', filtrarPromociones);
  document.getElementById('clearFilters')?.addEventListener('click', limpiarFiltros);
  document.getElementById('volver')?.addEventListener('click', () => location.href = '../menu.php');

  document.addEventListener('click', e => {
    const btn = e.target.closest('button[data-id]');
    if (!btn) return;
    const id = btn.dataset.id;

    if (btn.classList.contains('btn-editar')) {
      mostrarFormEditar(id);
    } else if (btn.classList.contains('btn-borrar')) {
      if (confirm('¿Borrar promoción?')) {
        const fd = new FormData();
        fd.append('id_promocion', id);
        fetchJson('borrar_promocion.php', { method: 'POST', body: fd }).then(res => {
          if (res?.ok) mostrarTabla();
          else alert(res?.error || 'Error al borrar');
        });
      }
    } else if (btn.classList.contains('btn-activar') || btn.classList.contains('btn-desactivar')) {
      const estaActivo = btn.classList.contains('btn-desactivar');
      cambiarActivo(id, !estaActivo).then(() => {
        mostrarTabla();
      });
    }
  });

  mostrarTabla();
});
