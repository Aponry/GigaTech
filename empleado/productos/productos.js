// TIPOS: lista de tipos de productos que vienen del HTML (probablemente renderizados desde PHP)
// ojo que si no existe el elemento #tipos-json devuelve array vacío
const TIPOS = JSON.parse(document.getElementById('tipos-json')?.textContent || '[]');

// escaparHtml: escapa caracteres especiales para evitar que se rompa el HTML
// útil para prevenir XSS o que se meta cualquier cosa en los inputs
function escaparHtml(s) {
  return String(s ?? '').replace(/[&<>"'`=\/]/g, c => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
    '/': '&#x2F;',
    '`': '&#60;',
    '=': '&#x3D;',
  })[c]);
}

// fetchJson: hace fetch a la URL pasada y devuelve JSON
// si hay error lo maneja mostrando mensaje
// ojo que si el fetch falla por red, devuelve null
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
    mostrarMensaje(errorMsg, 'error'); // asumimos que mostrarMensaje es una función global
    return null;
  }
}

// debounceMostrar: evita que mostrarLista se llame mil veces al tipear en filtros
let _t;
function debounceMostrar() { clearTimeout(_t); _t = setTimeout(mostrarLista, 200); }

// mostrarLista: trae la lista de productos y renderiza la tabla
// ojo que no toca eventos de botones acá, solo pinta la tabla
async function mostrarLista() {
  const datos = await fetchJson('listar_productos.php'); // traemos los productos
  const listaEl = document.getElementById('lista');
  listaEl.innerHTML = ''; // limpiamos lista
  document.getElementById('formulario').innerHTML = ''; // limpiamos form si estaba

  if (!datos?.length) {
    listaEl.innerHTML = '<p>No hay productos para mostrar.</p>';
    return;
  }

  // agarramos filtros y busqueda
  const q = document.getElementById('searchInput').value.trim().toLowerCase();
  const tipoSel = document.getElementById('filterTipo').value.toLowerCase();
  const permite = document.getElementById('filterPermit').value;

  // filtramos según búsqueda, tipo y permitir ingredientes
  const items = datos.filter(p => (
    (!q || p.id_producto.includes(q) || p.nombre.toLowerCase().includes(q) || p.tipo.toLowerCase().includes(q)) &&
    (!tipoSel || p.tipo.toLowerCase() === tipoSel) &&
    (permite === '' || String(p.permitir_ingredientes) === permite)
  ));

  // armamos la tabla
  const tabla = document.createElement('table');
  tabla.className = 'productos-table';
  tabla.innerHTML = `<thead><tr><th>ID</th><th>Foto</th><th>Nombre</th><th>Tipo</th><th>Precio</th><th>Descripción</th><th>Permite ing.</th><th>Acciones</th></tr></thead>`;
  const tbody = document.createElement('tbody');

  items.forEach(p => {
    const tr = document.createElement('tr');
    // si hay imagen, la mostramos, sino "Sin foto"
    const imgSrc = p.imagen ? `empleado/productos/${p.imagen}` : '';
    const imgHtml = imgSrc ? `<img src="${escaparHtml(imgSrc)}" alt="${escaparHtml(p.nombre)}" class="img-preview-tabla">` : '<span>Sin foto</span>';

    tr.innerHTML = `
      <td class="col-id">${p.id_producto}</td>
      <td class="col-img">${imgHtml}</td>
      <td class="col-nombre">${escaparHtml(p.nombre)}</td>
      <td class="col-tipo">${escaparHtml(p.tipo)}</td>
      <td class="col-precio">${Number(p.precio_base).toFixed(2)}</td>
      <td class="col-desc">${escaparHtml(p.descripcion)}</td>
      <td class="col-permite">${p.permitir_ingredientes ? 'Sí' : 'No'}</td>
      <td class="col-acciones">
        <div class="action-group">
          <button type="button" class="btn-editar" data-id="${p.id_producto}">Editar</button>
          <button type="button" class="btn-borrar" data-id="${p.id_producto}">Borrar</button>
        </div>
      </td>`;
    tbody.appendChild(tr);
  });

  tabla.appendChild(tbody);
  listaEl.appendChild(tabla);
}

// --- Formulario ---
// crearSelectTiposHTML: arma el select de tipos para el formulario
function crearSelectTiposHTML(sel = '') {
  return `<select name="tipo" class="form-select" required>
    <option value="">Seleccionar...</option>
    ${TIPOS.map(t => `<option value="${escaparHtml(t)}" ${t.toLowerCase() === sel.toLowerCase() ? 'selected' : ''}>${escaparHtml(t.charAt(0).toUpperCase() + t.slice(1))}</option>`).join('')}
  </select>`;
}

// controlarPermitirIngredientes: muestra/oculta checkbox según tipo de producto
function controlarPermitirIngredientes(form) {
  const selTipo = form.querySelector('select[name="tipo"]');
  const chkBlock = form.querySelector('.emp-ingred-block');
  if (!selTipo || !chkBlock) return;
  const actualizar = () => {
    const tipo = selTipo.value.toLowerCase();
    const esPermitido = tipo === 'pizza' || tipo === 'hamburguesa';
    chkBlock.style.display = esPermitido ? 'flex' : 'none';
    if (!esPermitido) chkBlock.querySelector('input').checked = false; // cuidado, resetea si no es pizza/hamburguesa
  };
  selTipo.addEventListener('change', actualizar);
  actualizar();
}

// manejarPreviewImagen: preview de imagen antes de subirla
function manejarPreviewImagen(form) {
  const inputFile = form.querySelector('input[type="file"]');
  const previewImg = form.querySelector('.img-preview-form');
  if (!inputFile || !previewImg) return;
  if (!previewImg.getAttribute('src')) previewImg.style.display = 'none';

  inputFile.addEventListener('change', e => {
    const file = e.target.files[0];
    const fileNameSpan = form.querySelector('.file-name');
    if (file) {
      previewImg.src = URL.createObjectURL(file);
      previewImg.style.display = 'block';
      if (fileNameSpan) fileNameSpan.textContent = file.name; // muestra el nombre del archivo
    }
  });
}

// renderizarFormulario: arma el HTML del form, tanto para agregar como para editar
function renderizarFormulario(cont, titulo, p = {}) {
  const esEditar = !!p.id_producto;
  const imgSrc = p.imagen ? `empleado/productos/${p.imagen}` : '';
  cont.innerHTML = `
    <form id="formProducto" enctype="multipart/form-data">
      <h2>${titulo}${esEditar ? `: ${escaparHtml(p.nombre)}` : ''}</h2>
      ${esEditar ? `<input type="hidden" name="id_producto" value="${p.id_producto}">` : ''}
      <div class="form-grid">
        <div class="form-group"><label>Nombre</label><input name="nombre" class="form-input" required value="${escaparHtml(p.nombre || '')}"></div>
        <div class="tipo-ingredientes-container">
          <div class="form-group"><label>Tipo</label>${crearSelectTiposHTML(p.tipo || '')}</div>
          <div class="form-group emp-ingred-block">
            <label class="emp-ingred-label">
              <input type="checkbox" name="permitir_ingredientes" value="1" ${p.permitir_ingredientes ? 'checked' : ''}><strong>Permitir ingredientes</strong>
            </label>
            <div class="form-hint">(Solo Pizzas/Hamburguesas)</div>
          </div>
        </div>
        <div class="form-group"><label>Precio</label><input type="number" step="0.01" min="0" name="precio_base" class="form-input" required value="${p.precio_base || ''}"></div>
        <div class="form-group"><label>Descripción</label><textarea name="descripcion" class="form-input">${escaparHtml(p.descripcion || '')}</textarea></div>
        <div class="form-group">
          <label>Imagen</label>
          <label for="inputImagen" class="custom-file-label">${esEditar ? 'Cambiar imagen' : 'Seleccionar imagen'}</label>
          <input type="file" name="imagen" accept="image/*" id="inputImagen">
          <span class="file-name"></span>
          <img class="img-preview-form" src="${escaparHtml(imgSrc)}" alt="Vista previa">
        </div>
      </div>
      <div class="form-actions">
        <button type="button" id="btnCancelar">Cancelar</button>
        <button type="submit">${esEditar ? 'Guardar Cambios' : 'Agregar'}</button>
      </div>
    </form>`;

  const form = document.getElementById('formProducto');
  controlarPermitirIngredientes(form); // cuidado: esto oculta el checkbox si no es pizza/hamburguesa
  manejarPreviewImagen(form);

  form.querySelector('#btnCancelar').addEventListener('click', mostrarLista); // vuelve a la lista sin enviar
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const endpoint = esEditar ? 'editar_producto.php' : 'agregar_producto.php';
    const res = await fetchJson(endpoint, { method: 'POST', body: new FormData(form) });
    if (res?.ok) mostrarLista(); // después de guardar recarga la lista
  });
}

// funciones helper para mostrar forms
function mostrarFormAgregar() { renderizarFormulario(document.getElementById('formulario'), 'Agregar Producto'); }
function mostrarFormEditar(producto) { renderizarFormulario(document.getElementById('formulario'), 'Editando', producto); }

// --- Inicialización de eventos ---
document.addEventListener('DOMContentLoaded', () => {
  const listaEl = document.getElementById('lista');

  // Delegación de eventos: se agrega una sola vez (cuidado, no poner esto dentro de mostrarLista)
  listaEl.addEventListener('click', e => {
    const btn = e.target.closest('button[data-id]');
    if (!btn) return;
    const id = btn.dataset.id;

    if (btn.classList.contains('btn-editar')) {
      // buscamos el producto para editar y lo pasamos al form
      fetchJson('listar_productos.php').then(datos => {
        const prod = datos.find(p => p.id_producto == id);
        if (prod) mostrarFormEditar(prod);
      });
    } else if (btn.classList.contains('btn-borrar')) {
      // cuidado: confirm dispara ventana, no acumular eventos por usar addEventListener en cada render
      if (confirm('¿Seguro que querés borrar el producto?')) {
        const fd = new FormData();
        fd.append('id_producto', id);
        fetchJson('borrar_producto.php', { method: 'POST', body: fd }).then(res => res?.ok && mostrarLista());
      }
    }
  });

  // botones de ver, agregar, volver
  document.getElementById('btnVer').addEventListener('click', mostrarLista);
  document.getElementById('btnAgregar').addEventListener('click', mostrarFormAgregar);
  document.getElementById('volver').addEventListener('click', () => location.href = '../menu.php');

  // filtros: input/search/select, con debounce
  ['searchInput', 'filterTipo', 'filterPermit'].forEach(id => document.getElementById(id)?.addEventListener('input', debounceMostrar));

  // limpiar filtros
  document.getElementById('clearFilters')?.addEventListener('click', () => {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterTipo').value = '';
    document.getElementById('filterPermit').value = '';
    mostrarLista();
  });

  mostrarLista(); // al cargar, mostramos la lista
});
