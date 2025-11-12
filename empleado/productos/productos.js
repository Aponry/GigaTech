// TIPOS: lista de tipos de productos que vienen del HTML (probablemente renderizados desde PHP)
// ojo que si no existe el elemento #tipos-json devuelve array vacío
const tiposEl = document.getElementById('tipos-json');
const TIPOS = JSON.parse(tiposEl ? tiposEl.textContent : '[]');

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
// mostrarMensaje: marcador de posición para mensajes
function mostrarMensaje(msg, type) {
  alert(msg);
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
    mostrarMensaje(errorMsg, 'error');
    return null;
  }
}

// Visualización estática, no se necesita mostrarLista

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
  selTipo?.addEventListener('change', actualizar);
  actualizar();
}

// manejarPreviewImagen: preview de imagen antes de subirla
function manejarPreviewImagen(form) {
  const inputFile = form.querySelector('input[type="file"]');
  const previewImg = form.querySelector('.img-preview-form');
  if (!inputFile || !previewImg) return;
  if (!previewImg.getAttribute('src')) previewImg.style.display = 'none';

  inputFile?.addEventListener('change', e => {
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

  const btnCancelar = form.querySelector('#btnCancelar');
  // btnCancelar?.addEventListener('click', () => {}); // cancel, do nothing
  form?.addEventListener('submit', async e => {
    e.preventDefault();
    const endpoint = esEditar ? 'editar_producto.php' : 'agregar_producto.php';
    const res = await fetchJson(endpoint, { method: 'POST', body: new FormData(form) });
    if (res?.ok) location.reload(); // después de guardar recarga la página
  });
}

// funciones helper para mostrar forms
function mostrarFormAgregar() {
  const cont = document.getElementById('formulario');
  if (cont) renderizarFormulario(cont, 'Agregar Producto');
}
function mostrarFormEditar(producto) {
  const cont = document.getElementById('formulario');
  if (cont) renderizarFormulario(cont, 'Editando', producto);
}

// Función para filtrar productos
function filtrarProductos() {
  const searchInput = document.getElementById('searchInput');
  const query = searchInput ? searchInput.value.toLowerCase() : '';
  const filterTipo = document.getElementById('filterTipo');
  const tipoFilter = filterTipo ? filterTipo.value : '';
  const filterPermit = document.getElementById('filterPermit');
  const permitFilter = filterPermit ? filterPermit.value : '';
  const sections = document.querySelectorAll('.product-section');

  sections.forEach(section => {
    const items = section.querySelectorAll('div[data-name]');
    let hasVisible = false;
    items.forEach(item => {
      const name = item.getAttribute('data-name');
      const tipo = item.getAttribute('data-tipo');
      const permitir = item.getAttribute('data-permitir');

      const matchesName = name.includes(query);
      const matchesTipo = tipoFilter === '' || tipo === tipoFilter;
      const matchesPermit = permitFilter === '' || permitir === permitFilter;

      const visible = matchesName && matchesTipo && matchesPermit;
      item.style.display = visible ? 'block' : 'none';
      if (visible) hasVisible = true;
    });
    section.style.display = hasVisible ? 'block' : 'none';
  });
}

// Función para limpiar filtros
function limpiarFiltros() {
  const searchInput = document.getElementById('searchInput');
  if (searchInput) searchInput.value = '';
  const filterTipo = document.getElementById('filterTipo');
  if (filterTipo) filterTipo.value = '';
  const filterPermit = document.getElementById('filterPermit');
  if (filterPermit) filterPermit.value = '';
  filtrarProductos();
}

// --- Inicialización de eventos ---
// Delegación de eventos: se agrega una sola vez
document.addEventListener('click', e => {
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

      fetchJson('borrar_producto.php', {
        method: 'POST',
        body: fd
      }).then(res => {
        if (res?.ok) {
          // Eliminamos la card inmediatamente
          const card = btn.closest('.product-card');
          if (card) {
            card.parentElement.remove();
          }

          mostrarMensaje('Producto eliminado con éxito', 'success');
        } else if (res?.error) {
          mostrarMensaje(res.error, 'error');
        } else {
          mostrarMensaje('Error al eliminar el producto', 'error');
        }
      });
    }
  }
});

// botones de agregar, volver, buscar, limpiar, ver
const btnAgregar = document.getElementById('btnAgregar');
if (btnAgregar) btnAgregar.addEventListener('click', mostrarFormAgregar);

const btnBuscar = document.getElementById('btnBuscar');
if (btnBuscar) btnBuscar.addEventListener('click', filtrarProductos);

const clearFilters = document.getElementById('clearFilters');
if (clearFilters) clearFilters.addEventListener('click', limpiarFiltros);

const btnVer = document.getElementById('btnVer');
if (btnVer) btnVer.addEventListener('click', () => window.location.href = '../../cliente/products.php');

const volver = document.getElementById('volver');
if (volver) volver.addEventListener('click', () => location.href = '../menu.php');

// Agregar listener de eventos para input de búsqueda
document.getElementById('searchInput').addEventListener('input', e => {
  const query = e.target.value.toLowerCase();
  const sections = document.querySelectorAll('.product-section');
  sections.forEach(section => {
    const items = section.querySelectorAll('div[data-name]');
    let hasVisible = false;
    items.forEach(item => {
      const name = item.getAttribute('data-name');
      const match = name.includes(query);
      item.style.display = match ? 'block' : 'none';
      if (match) hasVisible = true;
    });
    section.style.display = hasVisible ? 'block' : 'none';
  });
});

// Inicialización del menú inferior y botón de scroll
// Obtener los tipos de productos desde la variable global definida en PHP
const tiposProductos = window.tiposProductos || [];

// Poblar el menú inferior con botones para cada tipo de producto
const productosButtons = document.getElementById('productos-buttons');
if (tiposProductos && productosButtons) {
  // Iterar sobre cada tipo y crear un botón
  tiposProductos.forEach(tipo => {
    const btn = document.createElement('button');
    btn.className = 'section-button';
    btn.textContent = tipo;
    // Event listener para scroll suave hacia la sección correspondiente
    btn.addEventListener('click', () => {
      const section = document.getElementById('productos-' + tipo);
      if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    productosButtons.appendChild(btn);
  });
}

// Funcionalidad del botón "volver arriba"
const scrollToTopBtn = document.getElementById('scrollToTop');
if (scrollToTopBtn) scrollToTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
