// escaparHtml no se usa en este archivo

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
    alert(errorMsg);
    return null;
  }
}

// mostrarMensaje: marcador de posición para mensajes
function mostrarMensaje(msg, type) {
  alert(msg);
}

// Función para filtrar ingredientes
function filtrarIngredientes() {
  const searchInput = document.getElementById('searchInput');
  const query = searchInput ? searchInput.value.toLowerCase() : '';
  const items = document.querySelectorAll('#ingredientes-section .product-card');

  items.forEach(item => {
    const name = item.getAttribute('data-name');
    const matchesName = name.includes(query);
    item.style.display = matchesName ? '' : 'none';
  });

  // Ocultar secciones si no hay tarjetas que coincidan
  const sections = document.querySelectorAll('#ingredientes-section .product-section');
  sections.forEach(section => {
    const cards = section.querySelectorAll('.product-card');
    let hasVisible = false;
    cards.forEach(card => {
      if (card.style.display !== 'none') {
        hasVisible = true;
      }
    });
    section.style.display = hasVisible ? '' : 'none';
  });
}

// Función para limpiar filtros
function limpiarFiltros() {
  const searchInput = document.getElementById('searchInput');
  if (searchInput) searchInput.value = '';
  filtrarIngredientes();
}

// renderizarCards: arma las cards de ingredientes
function renderizarCards(datos) {
  const cont = document.getElementById('ingredientes-section');
  if (!cont) return;

  if (!Array.isArray(datos) || !datos.length) {
    cont.innerHTML = '<p>No hay ingredientes</p>';
    return;
  }

  // Group by tipo
  const groups = datos.reduce((acc, ing) => {
    (acc[ing.tipo_producto] = acc[ing.tipo_producto] || []).push(ing);
    return acc;
  }, {});

  const displayTipos = { pizza: 'Pizza', hamburguesa: 'Hamburger' };

  cont.innerHTML = '';

  Object.keys(groups).forEach(tipo => {
    const section = document.createElement('section');
    section.className = 'product-section';
    section.id = 'ingredientes-' + tipo;
    section.innerHTML = `<h2 class="section-title">${displayTipos[tipo]}</h2><div class="section-grid"></div>`;
    const grid = section.querySelector('.section-grid');

    groups[tipo].forEach(ing => {
      const card = document.createElement('article');
      card.className = 'product-card';
      card.setAttribute('data-name', (ing.nombre || '').toLowerCase());
      card.setAttribute('data-tipo', ing.tipo_producto);
      card.innerHTML = `
        <h3>${ing.nombre}</h3>
        <div class="costo" style="font-weight: bold;">Costo: $${ing.costo}</div>
        <div class="price">Stock: ${ing.stock ?? 0}</div>
        <div class="card-actions">
          <button class="btn-editar" data-id="${ing.id_ingrediente}">Editar</button>
          <button class="btn-borrar" data-id="${ing.id_ingrediente}">Borrar</button>
        </div>
      `;
      grid.appendChild(card);
    });

    cont.appendChild(section);
  });
}

// mostrarFormularioIngrediente: muestra formulario de alta/edición de ingrediente
function mostrarFormularioIngrediente(ingrediente = null) {
  const cont = document.getElementById('formulario');
  if (!cont) return;
  cont.innerHTML = '';

  const card = document.createElement('div');
  card.className = 'form-card';

  const form = document.createElement('form');
  form.id = 'formIngrediente';
  form.className = 'form-container';

  if (ingrediente) {
    const hid = document.createElement('input');
    hid.type = 'hidden';
    hid.name = 'id_ingrediente';
    hid.value = ingrediente.id_ingrediente;
    form.appendChild(hid);
  }

  const row = document.createElement('div');
  row.className = 'form-row';

  // Name field
  const nameDiv = document.createElement('div');
  const lblNombre = document.createElement('label');
  lblNombre.className = 'form-label';
  lblNombre.textContent = 'Nombre';
  nameDiv.appendChild(lblNombre);
  const inpNombre = document.createElement('input');
  inpNombre.name = 'nombre';
  inpNombre.placeholder = 'Nombre';
  inpNombre.required = true;
  inpNombre.value = ingrediente?.nombre ?? '';
  inpNombre.className = 'form-input';
  nameDiv.appendChild(inpNombre);
  row.appendChild(nameDiv);

  // Price field
  const priceDiv = document.createElement('div');
  const lblPrecio = document.createElement('label');
  lblPrecio.className = 'form-label';
  lblPrecio.textContent = 'Precio';
  priceDiv.appendChild(lblPrecio);
  const inpPrecio = document.createElement('input');
  inpPrecio.name = 'costo';
  inpPrecio.type = 'number';
  inpPrecio.step = '0.01';
  inpPrecio.placeholder = 'Precio';
  inpPrecio.required = true;
  inpPrecio.value = ingrediente?.costo ?? '';
  inpPrecio.className = 'form-input';
  priceDiv.appendChild(inpPrecio);
  row.appendChild(priceDiv);

  form.appendChild(row);

  // Description field
  const lblDesc = document.createElement('label');
  lblDesc.className = 'form-label';
  lblDesc.textContent = 'Descripción';
  form.appendChild(lblDesc);
  const inpDesc = document.createElement('textarea');
  inpDesc.name = 'descripcion';
  inpDesc.placeholder = 'Descripción';
  inpDesc.value = ingrediente?.descripcion ?? '';
  inpDesc.className = 'form-input';
  form.appendChild(inpDesc);

  const acciones = document.createElement('div');
  acciones.className = 'form-actions';
  const btnCancelar = document.createElement('button');
  btnCancelar.type = 'button';
  btnCancelar.textContent = 'Cancelar';
  acciones.appendChild(btnCancelar);
  const btnGuardar = document.createElement('button');
  btnGuardar.type = 'submit';
  btnGuardar.textContent = ingrediente ? 'Guardar' : 'Agregar';
  acciones.appendChild(btnGuardar);
  form.appendChild(acciones);

  card.appendChild(form);
  cont.appendChild(card);

  btnCancelar.addEventListener('click', () => { cont.innerHTML = ''; });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(form);
    const url = ingrediente ? 'editar_ingrediente.php' : 'agregar_ingrediente.php';
    const res = await fetchJson(url, { method: 'POST', body: fd });
    if (res?.ok) {
      cont.innerHTML = '';
      mostrarCards();
    } else alert(res?.error || 'Error en el servidor');
  });
}

// mostrarCards: carga y muestra las cards
async function mostrarCards() {
  const datos = await fetchJson('listar_ingredientes.php');
  renderizarCards(datos);
}

// funciones helper
function mostrarFormAgregar() {
  mostrarFormularioIngrediente();
}

function mostrarFormEditar(id) {
  fetchJson('listar_ingredientes.php').then(datos => {
    const ing = datos.find(i => i.id_ingrediente == id);
    if (ing) mostrarFormularioIngrediente(ing);
  });
}

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('btnAgregar')?.addEventListener('click', mostrarFormAgregar);
  document.getElementById('searchInput')?.addEventListener('input', filtrarIngredientes);
  document.getElementById('btnBuscar')?.addEventListener('click', filtrarIngredientes);
  document.getElementById('clearFilters')?.addEventListener('click', limpiarFiltros);
  document.getElementById('volver')?.addEventListener('click', () => location.href = '../menu.php');

  document.addEventListener('click', e => {
    const btn = e.target.closest('button[data-id]');
    if (!btn) return;
    const id = btn.dataset.id;

    if (btn.classList.contains('btn-editar')) {
      mostrarFormEditar(id);
    } else if (btn.classList.contains('btn-borrar')) {
      if (confirm('¿Borrar ingrediente?')) {
        const fd = new FormData();
        fd.append('id_ingrediente', id);
        fetchJson('borrar_ingrediente.php', { method: 'POST', body: fd }).then(res => {
          if (res?.ok) mostrarCards();
          else alert(res?.error || 'Error al borrar');
        });
      }
    }
  });

  mostrarCards();
});