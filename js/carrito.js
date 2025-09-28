document.addEventListener('DOMContentLoaded', () => {
    const KEY = 'carrito_pizzaconmigo_v1';
    const botonCarrito = document.getElementById('botonCarrito');
    const carritoEl = document.getElementById('carrito');
    const cerrarBtn = document.getElementById('cerrarCarrito');
    const itemsEl = carritoEl?.querySelector('.items');
    const totalEl = document.getElementById('total');
    const finalizarBtn = document.getElementById('finalizar');
    const backdrop = document.getElementById('backdrop');

    if (!carritoEl || !itemsEl || !totalEl) return;

    let sugerenciasEl = carritoEl.querySelector('.sugerencias');
    if (!sugerenciasEl) {
        sugerenciasEl = document.createElement('div');
        sugerenciasEl.className = 'sugerencias';
        sugerenciasEl.innerHTML = '<h4>Sugerencias</h4><div class="reco-list"></div>';
        const ref = carritoEl.querySelector('.totalCarrito') || null;
        if (ref) carritoEl.insertBefore(sugerenciasEl, ref);
        else carritoEl.appendChild(sugerenciasEl);
    }
    let recoList = sugerenciasEl.querySelector('.reco-list');
    if (!recoList) {
        sugerenciasEl.innerHTML = '<h4>Sugerencias</h4><div class="reco-list"></div>';
        recoList = sugerenciasEl.querySelector('.reco-list');
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s]));
    }

    function buildCatalogLocal() {
        const c = { productos: [], promos: [] };
        document.querySelectorAll('.producto').forEach(p => {
            c.productos.push({
                id: p.dataset.id || '',
                nombre: p.dataset.nombre || p.querySelector('h3,h4')?.innerText || '',
                precio: parseFloat(p.dataset.precio || p.dataset.precio_base) || 0,
                tipo: p.dataset.tipo || ''
            });
        });
        document.querySelectorAll('.promo, .promo-card').forEach(pr => {
            const id = pr.dataset.id || pr.querySelector('.agregarPromo')?.dataset.id || '';
            const nombre = pr.dataset.nombre || pr.querySelector('h3,h4')?.innerText || '';
            const precio = parseFloat(pr.dataset.precio) || parseFloat(pr.querySelector('.agregarPromo')?.dataset.precio) || 0;
            c.promos.push({ id, nombre, precio });
        });
        return c;
    }

    let catalog = buildCatalogLocal();

    let carrito = [];
    try {
        carrito = JSON.parse(localStorage.getItem(KEY) || '[]');
        if (!Array.isArray(carrito)) carrito = [];
    } catch (e) {
        carrito = [];
    }

    function save() { localStorage.setItem(KEY, JSON.stringify(carrito)); }
    function calcularTotal() { return carrito.reduce((s, i) => s + (Number(i.precio || 0) * Number(i.cantidad || 0)), 0); }

    function getImgPathForProduct(id) {
        const el = Array.from(document.querySelectorAll('.producto')).find(x => String(x.dataset.id) == String(id));
        return el?.querySelector('img')?.src || 'images/perfil.png';
    }
    function getImgPathForPromo(id) {
        const el = Array.from(document.querySelectorAll('.promo, .promo-card')).find(x => String(x.dataset.id) == String(id));
        return el?.querySelector('img')?.src || 'images/perfil.png';
    }

    function renderSugerencias() {
        catalog = buildCatalogLocal();
        if (!recoList) return;
        const tipos = Array.from(new Set(carrito.map(i => i.tipo).filter(Boolean)));
        const relacionados = tipos.length ? catalog.productos.filter(p => tipos.includes(p.tipo) && !carrito.some(c => c.id == p.id)).slice(0, 6) : [];
        const promosRel = catalog.promos.slice(0, 4);
        recoList.innerHTML = '';
        relacionados.forEach(p => {
            const d = document.createElement('div');
            d.className = 'mini';
            const img = escapeHtml(getImgPathForProduct(p.id));
            d.innerHTML = `<img src="${img}" alt="${escapeHtml(p.nombre)}"><div style="font-weight:600">${escapeHtml(p.nombre)}</div><div style="font-size:12px">$${Number(p.precio).toFixed(2)}</div><button class="mini-add" data-id="${escapeHtml(p.id)}" aria-label="Agregar">+</button>`;
            recoList.appendChild(d);
        });
        promosRel.forEach(pr => {
            const d = document.createElement('div');
            d.className = 'mini';
            const img = escapeHtml(getImgPathForPromo(pr.id));
            d.innerHTML = `<img src="${img}" alt="${escapeHtml(pr.nombre)}"><div style="font-weight:600">${escapeHtml(pr.nombre)}</div><div style="font-size:12px">$${Number(pr.precio).toFixed(2)}</div><button class="mini-add-promo" data-id="${escapeHtml(pr.id)}" aria-label="Agregar">+</button>`;
            recoList.appendChild(d);
        });
    }

    function render() {
        itemsEl.innerHTML = '';
        if (carrito.length === 0) itemsEl.innerHTML = '<p>Carrito vacío.</p>';
        carrito.forEach((it, idx) => {
            const row = document.createElement('div');
            row.className = 'item';
            row.innerHTML = `
        <div class="info">
          <div style="font-weight:700">${escapeHtml(it.nombre)}</div>
          <div style="font-size:13px;color:#666">${escapeHtml(it.tipo || '')}</div>
        </div>
        <div style="text-align:right">
          <input class="cant" data-idx="${idx}" type="number" min="1" max="10" value="${Number(it.cantidad || 1)}">
          <div style="margin-top:6px">$${(Number(it.precio || 0) * Number(it.cantidad || 0)).toFixed(2)}</div>
          <button class="borrar" data-idx="${idx}">Eliminar</button>
        </div>
      `;
            itemsEl.appendChild(row);
        });
        totalEl.innerText = calcularTotal().toFixed(2);
        save();
        renderSugerencias();
    }

    function getDataFrom(el) {
        const btn = el.closest('button') || el;
        if (btn && btn.dataset && btn.dataset.id) {
            return {
                id: btn.dataset.id,
                nombre: btn.dataset.nombre || btn.dataset.name || (btn.closest('.producto')?.dataset.nombre) || (btn.closest('.promo, .promo-card')?.dataset.nombre) || '',
                precio: parseFloat(btn.dataset.precio || btn.dataset.price || 0) || 0,
                tipo: btn.dataset.tipo || (btn.closest('.producto')?.dataset.tipo) || (btn.closest('.promo, .promo-card') ? 'promo' : '')
            };
        }
        const cont = el.closest('.producto, .promo, .promo-card');
        if (cont) {
            return {
                id: cont.dataset.id || cont.dataset.id_producto || cont.dataset.id_promocion || '',
                nombre: cont.dataset.nombre || cont.querySelector('h3,h4')?.innerText || '',
                precio: parseFloat(cont.dataset.precio || cont.dataset.precio_base) || 0,
                tipo: cont.dataset.tipo || (cont.classList.contains('promo') || cont.classList.contains('promo-card') ? 'promo' : '')
            };
        }
        return null;
    }

    function agregar(data) {
        if (!data || !data.id) return;
        const idx = carrito.findIndex(x => x.id == data.id);
        if (idx >= 0) {
            if (carrito[idx].cantidad >= 10) {
                alert('Cantidad máxima por producto: 10');
                return;
            }
            carrito[idx].cantidad = Math.min(10, carrito[idx].cantidad + 1);
        } else {
            carrito.push({ id: String(data.id), nombre: data.nombre || 'Sin nombre', precio: Number(data.precio) || 0, cantidad: 1, tipo: data.tipo || '' });
        }
        render(); abrir();
    }

    document.addEventListener('click', e => {
        if (e.target.matches('.agregarCarrito') || e.target.closest('.agregarCarrito')) {
            const btn = e.target.closest('.agregarCarrito');
            agregar(getDataFrom(btn));
        }
        if (e.target.matches('.agregarPromo') || e.target.closest('.agregarPromo')) {
            const btn = e.target.closest('.agregarPromo');
            agregar(getDataFrom(btn));
        }
        if (e.target.classList.contains('mini-add')) {
            const id = e.target.dataset.id;
            catalog = buildCatalogLocal();
            const prod = catalog.productos.find(p => String(p.id) == String(id));
            if (prod) agregar(prod);
        }
        if (e.target.classList.contains('mini-add-promo')) {
            const id = e.target.dataset.id;
            catalog = buildCatalogLocal();
            const pr = catalog.promos.find(p => String(p.id) == String(id));
            if (pr) agregar({ id: pr.id, nombre: pr.nombre, precio: pr.precio, tipo: 'promo' });
        }
        if (e.target.classList.contains('borrar')) {
            const idx = parseInt(e.target.dataset.idx, 10);
            carrito.splice(idx, 1);
            render();
        }
    });

    itemsEl.addEventListener('input', e => {
        if (e.target.classList.contains('cant')) {
            const idx = parseInt(e.target.dataset.idx, 10);
            let val = parseInt(e.target.value, 10) || 1;
            if (val < 1) val = 1;
            if (val > 10) { val = 10; alert('Máximo por producto: 10'); }
            if (carrito[idx]) carrito[idx].cantidad = val;
            render();
        }
    });

    function abrir() {
        carritoEl.classList.add('open');
        carritoEl.setAttribute('aria-hidden', 'false');
        backdrop && backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
        document.documentElement.classList.add('cart-open');
    }
    function cerrar() {
        carritoEl.classList.remove('open');
        carritoEl.setAttribute('aria-hidden', 'true');
        backdrop && backdrop.classList.remove('show');
        document.body.style.overflow = '';
        document.documentElement.classList.remove('cart-open');
    }
    botonCarrito && botonCarrito.addEventListener('click', () => abrir());
    cerrarBtn && cerrarBtn.addEventListener('click', () => cerrar());
    backdrop && backdrop.addEventListener('click', () => cerrar());

    finalizarBtn && finalizarBtn.addEventListener('click', () => {
        if (carrito.length === 0) { alert('Carrito vacío'); return; }
        alert('Pedido enviado. Total: $' + calcularTotal().toFixed(2));
        carrito = []; render(); cerrar();
    });

    render();
});
