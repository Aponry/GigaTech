const TIPOS = JSON.parse(document.getElementById('tipos-json').textContent || '[]'); // Cargar tipos de ingredientes

// Función para escapar caracteres especiales en el HTML
function esc(texto) {
  return String(texto ?? '').replace(/[&<>"'`=\/]/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '/': '&#x2F;', '`': '&#x60;', '=': '&#x3D;'
  })[c]);
}

// Función para hacer peticiones fetch con respuesta en formato JSON
async function fetchJson(url, opts = {}) {
  try {
    const r = await fetch(url, { cache: 'no-cache', ...opts });
    if (!r.ok) {
      const txt = await r.text().catch(() => null);
      throw new Error(`HTTP ${r.status} ${r.statusText}${txt ? ' — ' + txt : ''}`);
    }
    return await r.json();
  } catch (e) {
    console.error('fetchJson error:', url, e);
    return null;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('ver')?.addEventListener('click', mostrarLista); // Botón ver ingredientes
  document.getElementById('agregar')?.addEventListener('click', mostrarFormularioAgregar); // Botón agregar ingrediente
  document.getElementById('volver')?.addEventListener('click', () => location.href = '../menu.php'); // Botón volver

  const buscarInput = document.getElementById('buscarInput');
  const filtroTipo = document.getElementById('filtroTipo');
  const limpiarBtn = document.getElementById('limpiarFiltros');

  if (buscarInput) buscarInput.addEventListener('input', debounce(mostrarLista, 220)); // Búsqueda en tiempo real
  if (filtroTipo) filtroTipo.addEventListener('change', mostrarLista); // Filtro por tipo
  if (limpiarBtn) limpiarBtn.addEventListener('click', () => {
    if (buscarInput) buscarInput.value = '';
    if (filtroTipo) filtroTipo.value = '';
    mostrarLista();
  });

  mostrarLista(); // Mostrar lista inicial
});

// Función para debouncing en búsqueda
function debounce(fn, ms = 200) {
  let t;
  return function (...a) { clearTimeout(t); t = setTimeout(() => fn.apply(this, a), ms); };
}

// Mostrar la lista de ingredientes
async function mostrarLista() {
  const buscarInput = document.getElementById('buscarInput');
  const filtroTipo = document.getElementById('filtroTipo');

  const datos = await fetchJson('listar_ingredientes.php'); // Obtener ingredientes
  const cont = document.getElementById('lista');
  const contForm = document.getElementById('formulario');
  cont.innerHTML = '';
  contForm.innerHTML = '';

  if (!Array.isArray(datos) || !datos.length) {
    cont.textContent = 'No hay ingredientes';
    return;
  }

  let lista = datos.slice();
  const q = (buscarInput && buscarInput.value || '').toString().trim().toLowerCase();
  if (q) {
    lista = lista.filter(it =>
      String(it.id_ingrediente).includes(q) ||
      (it.nombre && it.nombre.toString().toLowerCase().includes(q))
    );
  }

  const tipoSel = filtroTipo && filtroTipo.value;
  if (tipoSel) {
    lista = lista.filter(it => (it.tipo_producto || '').toString().toLowerCase() === tipoSel.toString().toLowerCase());
  }

  const tabla = document.createElement('table');
  tabla.className = 'ingredientes-table';

  const thead = tabla.createTHead();
  const hr = thead.insertRow();
  ['ID', 'Nombre', 'Tipo', 'Costo', 'Acciones'].forEach(h => {
    const th = document.createElement('th');
    th.textContent = h;
    hr.appendChild(th);
  });

  const tbody = tabla.createTBody();
  lista.forEach(item => {
    const tr = tbody.insertRow();
    tr.insertCell().textContent = item.id_ingrediente;
    tr.insertCell().textContent = item.nombre;
    tr.insertCell().textContent = item.tipo_producto;
    tr.insertCell().textContent = (Number(item.costo) || 0).toFixed(2);

    const tdAcc = tr.insertCell();
    tdAcc.style.textAlign = 'center';
    tdAcc.style.whiteSpace = 'nowrap';

    // Botón para editar ingrediente
    const btnEditar = document.createElement('button');
    btnEditar.type = 'button';
    btnEditar.className = 'btn-editar';
    btnEditar.textContent = 'Editar';
    btnEditar.addEventListener('click', () => mostrarFormularioEditar(item));
    tdAcc.appendChild(btnEditar);

    // Botón para borrar ingrediente
    const btnBorrar = document.createElement('button');
    btnBorrar.type = 'button';
    btnBorrar.className = 'btn-borrar';
    btnBorrar.textContent = 'Borrar';
    btnBorrar.addEventListener('click', async () => {
      if (!confirm('¿Borrar ingrediente?')) return;
      btnBorrar.disabled = true;
      const fd = new FormData();
      fd.append('id_ingrediente', item.id_ingrediente);
      const res = await fetchJson('borrar_ingredientes.php', { method: 'POST', body: fd });
      btnBorrar.disabled = false;
      if (!res) return alert('Error al borrar');
      if (!res.ok) return alert(res.error || 'No se pudo borrar');
      mostrarLista();
    });
    tdAcc.appendChild(btnBorrar);
  });

  cont.appendChild(tabla);
}

// Función para crear el select de tipos de ingredientes
function crearSelectTipos(seleccion = '') {
  const s = document.createElement('select');
  s.name = 'tipo_producto';
  TIPOS.forEach(t => {
    const o = document.createElement('option');
    o.value = t;
    o.textContent = t.charAt(0).toUpperCase() + t.slice(1);
    if (String(t).toLowerCase() === String(seleccion).toLowerCase()) o.selected = true;
    s.appendChild(o);
  });
  return s.outerHTML;
}

// Función para mostrar el formulario de agregar ingrediente
function mostrarFormularioAgregar() {
  const cont = document.getElementById('formulario');
  cont.innerHTML = `
    <form id="formAgregar" novalidate>
      <input name="nombre" placeholder="Nombre" required>
      ${crearSelectTipos('')}
      <input name="costo" placeholder="Costo" required inputmode="decimal">
      <div class="form-actions" style="margin-top:8px;">
        <button type="button" id="cancelAgregar" class="btn-cancel">Cancelar</button>
        <button type="submit" class="btn-save">Agregar</button>
      </div>
    </form>
  `;
  document.getElementById('cancelAgregar')?.addEventListener('click', () => cont.innerHTML = '');

  const form = document.getElementById('formAgregar');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    const fd = new FormData(form);
    if (fd.has('costo')) fd.set('costo', String(fd.get('costo') || '').trim().replace(',', '.'));
    const res = await fetchJson('agregar_ingrediente.php', { method: 'POST', body: fd });
    btn.disabled = false;
    if (!res) { alert('Error al agregar'); return; }
    if (!res.ok) { alert(res.error || 'No se pudo agregar'); return; }
    cont.innerHTML = '';
    mostrarLista();
  });
}

// Función para mostrar el formulario de editar ingrediente
function mostrarFormularioEditar(item) {
  const cont = document.getElementById('formulario');
  cont.innerHTML = `
    <form id="formEditar" novalidate>
      <input type="hidden" name="id_ingrediente" value="${esc(item.id_ingrediente)}">
      <input name="nombre" value="${esc(item.nombre)}" placeholder="Nombre" required>
      ${crearSelectTipos(item.tipo_producto || '')}
      <input name="costo" value="${esc(Number(item.costo || 0).toFixed(2))}" placeholder="Costo" required inputmode="decimal">
      <div class="form-actions" style="margin-top:8px;">
        <button type="button" id="cancelEditar" class="btn-cancel">Cancelar</button>
        <button type="submit" class="btn-save">Guardar</button>
        <button type="button" id="deleteEditar" class="btn-delete">Borrar</button>
      </div>
    </form>
  `;

  document.getElementById('cancelEditar')?.addEventListener('click', () => cont.innerHTML = '');

  const form = document.getElementById('formEditar');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    const fd = new FormData(form);
    if (fd.has('costo')) fd.set('costo', String(fd.get('costo') || '').trim().replace(',', '.'));
    const res = await fetchJson('editar_ingredientes.php', { method: 'POST', body: fd });
    btn.disabled = false;
    if (!res) { alert('Error al guardar'); return; }
    if (!res.ok) { alert(res.error || 'No se pudo guardar'); return; }
    cont.innerHTML = '';
    mostrarLista();
  });

  document.getElementById('deleteEditar')?.addEventListener('click', async () => {
    if (!confirm('¿Borrar ingrediente?')) return;
    const btn = document.getElementById('deleteEditar');
    btn.disabled = true;
    const fd = new FormData();
    fd.append('id_ingrediente', item.id_ingrediente);
    const res = await fetchJson('borrar_ingredientes.php', { method: 'POST', body: fd });
    btn.disabled = false;
    if (!res) return alert('Error al borrar');
    if (!res.ok) return alert(res.error || 'No se pudo borrar');
    cont.innerHTML = '';
    mostrarLista();
  });
}
