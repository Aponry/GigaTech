// Función para crear y mostrar el modal de pedidos
function createOrderModal() {
    // Verificar si el modal ya existe
    if (document.getElementById('order-modal')) {
        return document.getElementById('order-modal');
    }

    const modal = document.createElement('div');
    modal.id = 'order-modal';
    modal.className = 'order-modal';
    modal.innerHTML = `
        <div class="order-modal-content">
            <div class="order-modal-header">
                <h2>Mis Pedidos</h2>
                <button class="order-modal-close">&times;</button>
            </div>
            <div class="order-modal-body">
                <!-- Filtros -->
                <div class="filters" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); margin-bottom: 20px;">
                    <div class="filter-row" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: end;">
                        <div class="filter-group">
                            <label for="search-phone" style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50;">Buscar por teléfono:</label>
                            <input type="text" id="search-phone" placeholder="Ej: 099123456" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; width: 150px;">
                        </div>
                        <div class="filter-group">
                            <label for="search-id" style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50;">Buscar por ID:</label>
                            <input type="text" id="search-id" placeholder="Ej: 123" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; width: 120px;">
                        </div>
                        <div class="filter-group">
                            <button type="button" id="search-btn" style="padding: 8px 16px; background-color: #333333; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Buscar</button>
                        </div>
                        <div class="filter-group">
                            <button type="button" id="clear-filters" style="padding: 8px 16px; background-color: #333333; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Limpiar</button>
                        </div>
                    </div>
                </div>
                <!-- Tabla de pedidos -->
                <div style="overflow-x: auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);">
                    <table class="table" id="orders-table" style="width: 100%; border-collapse: collapse; margin: 0;">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: #2c3e50; border-bottom: 2px solid #dee2e6;">ID Pedido</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: #2c3e50; border-bottom: 2px solid #dee2e6;">Fecha</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: #2c3e50; border-bottom: 2px solid #dee2e6;">Total</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: #2c3e50; border-bottom: 2px solid #dee2e6;">Estado</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: #2c3e50; border-bottom: 2px solid #dee2e6;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los pedidos se cargan dinámicamente -->
                        </tbody>
                    </table>
                </div>
                <div id="order-content" style="display: none;">
                    <!-- Detalles del pedido -->
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Agregar eventos
    modal.querySelector('.order-modal-close').addEventListener('click', closeOrderModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeOrderModal();
        }
    });

    return modal;
}

// Función para abrir el modal de pedidos
function openOrderModal() {
    const modal = createOrderModal();
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Cargar pedidos desde localStorage
    loadOrdersFromStorage();

    // Agregar eventos de filtros
    const searchPhone = document.getElementById('search-phone');
    const searchId = document.getElementById('search-id');
    const searchBtn = document.getElementById('search-btn');
    const clearFilters = document.getElementById('clear-filters');

    // Clear other field when typing
    searchPhone.addEventListener('input', () => {
        if (searchPhone.value.trim()) {
            searchId.value = '';
        }
    });
    searchId.addEventListener('input', () => {
        if (searchId.value.trim()) {
            searchPhone.value = '';
        }
    });

    const performSearch = async () => {
        const phone = searchPhone.value.trim();
        const id = searchId.value.trim();

        if (phone) {
            // Fetch from server
            try {
                const response = await fetch(`cliente/php/obtener_pedidos.php?telefono=${encodeURIComponent(phone)}`);
                if (!response.ok) {
                    throw new Error('Error al buscar pedidos');
                }
                const pedidos = await response.json();
                if (pedidos.error) {
                    throw new Error(pedidos.error);
                }
                displayPedidos(pedidos);
            } catch (error) {
                console.error('Error fetching pedidos:', error);
                const tbody = document.querySelector('#orders-table tbody');
                tbody.innerHTML = '<tr><td colspan="5">Error al buscar pedidos.</td></tr>';
            }
        } else if (id) {
            // Fetch from server
            try {
                const response = await fetch(`cliente/php/obtener_pedidos.php?id_pedido=${encodeURIComponent(id)}`);
                if (!response.ok) {
                    throw new Error('Error al buscar pedidos');
                }
                const pedidos = await response.json();
                if (pedidos.error) {
                    throw new Error(pedidos.error);
                }
                displayPedidos(pedidos);
            } catch (error) {
                console.error('Error fetching pedidos:', error);
                const tbody = document.querySelector('#orders-table tbody');
                tbody.innerHTML = '<tr><td colspan="5">Error al buscar pedidos.</td></tr>';
            }
        } else {
            // Load from localStorage
            loadOrdersFromStorage();
        }
    };

    searchBtn.addEventListener('click', performSearch);

    // Permitir búsqueda con Enter
    searchPhone.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') performSearch();
    });
    searchId.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') performSearch();
    });

    clearFilters.addEventListener('click', () => {
        searchPhone.value = '';
        searchId.value = '';
        loadOrdersFromStorage();
    });

}

// Función para cerrar el modal de pedidos
function closeOrderModal() {
    const modal = document.getElementById('order-modal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        // Reset to table view
        document.getElementById('orders-table').style.display = 'table';
        document.getElementById('order-content').style.display = 'none';
        document.querySelector('.order-modal-header').style.display = 'flex';
    }
}

// Función para mostrar pedidos en la tabla
function displayPedidos(pedidos) {
    const tbody = document.querySelector('#orders-table tbody');

    if (pedidos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5">No se encontraron pedidos.</td></tr>';
        return;
    }

    // Ordenar por fecha descendente
    pedidos.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));

    tbody.innerHTML = '';
    pedidos.forEach(pedido => {
        const fecha = new Date(pedido.fecha).toLocaleString('es-UY');
        const estado = translateStatus(pedido.estado);
        const row = document.createElement('tr');
        row.style.borderBottom = '1px solid #dee2e6';
        row.innerHTML = `
            <td style="padding: 12px; font-weight: 500;">${pedido.id_pedido}</td>
            <td style="padding: 12px; color: #666;">${fecha}</td>
            <td style="padding: 12px; font-weight: 600; color: #333333;">$${parseFloat(pedido.total).toFixed(2)}</td>
            <td style="padding: 12px;"><span class="status ${pedido.estado.toLowerCase()}" style="padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 500; background-color: #333333; color: white;">${estado}</span></td>
            <td style="padding: 12px;"><button class="btn" onclick="viewOrderDetails(${pedido.id_pedido})" style="padding: 6px 12px; background-color: #333333; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">Ver Detalles</button></td>
        `;
        tbody.appendChild(row);
    });
}

// Función para cargar pedidos desde localStorage
function loadOrdersFromStorage() {
    const pedidos = JSON.parse(localStorage.getItem('pizzaconmigo_pedidos') || '[]');

    // Obtener filtros
    const searchPhone = document.getElementById('search-phone').value.toLowerCase();
    const searchId = document.getElementById('search-id').value;

    // Filtrar pedidos
    let filteredPedidos = pedidos.filter(pedido => {
        if (searchPhone && !pedido.telefono_cliente.toLowerCase().includes(searchPhone)) return false;
        if (searchId && !pedido.id_pedido.toString().includes(searchId)) return false;
        return true;
    });

    displayPedidos(filteredPedidos);
}

// Función para ver detalles de un pedido
function viewOrderDetails(idPedido) {
    loadOrderDetails(idPedido);
}

// Función para cargar los detalles de un pedido
function loadOrderDetails(orderId) {
    document.getElementById('orders-table').style.display = 'none';
    document.getElementById('order-content').style.display = 'block';
    const content = document.getElementById('order-content');
    content.innerHTML = `
        <div class="loading">
            <div class="loading-spinner"></div>
            <p>Cargando información del pedido...</p>
        </div>
    `;
    
    fetch(`cliente/php/obtener_pedido.php?id=${orderId}`)
        .then(response => {
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error('Pedido no encontrado');
                }
                throw new Error(`Error al cargar el pedido: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                if (data.error.includes('pedido') && data.error.includes('no encontrado')) {
                    showNotFound(orderId);
                } else {
                    throw new Error(data.error);
                }
            } else {
                displayOrderDetails(data);
                // Configurar actualización automática cada 30 segundos
                setupAutoRefresh(orderId);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (error.message.includes('pedido no encontrado')) {
                showNotFound(orderId);
            } else {
                showError(`Error al cargar el pedido: ${error.message}`);
            }
        });
}

// Función para mostrar la tabla de pedidos
function showOrdersTable() {
    document.getElementById('orders-table').style.display = 'table';
    document.getElementById('order-content').style.display = 'none';
    document.querySelector('.filters').style.display = 'block';
    document.querySelector('.order-modal-header').style.display = 'flex';
    loadOrdersFromStorage();
}

// Función para mostrar los detalles del pedido
function displayOrderDetails(data) {
    document.getElementById('orders-table').style.display = 'none';
    document.getElementById('order-content').style.display = 'block';
    document.querySelector('.filters').style.display = 'none';
    document.querySelector('.order-modal-header').style.display = 'none';

    const content = document.getElementById('order-content');
    const pedido = data.pedido;
    const detalles = data.detalles;

    // Actualizar estado en localStorage si cambió
    updateLocalStorageStatus(pedido.id_pedido, pedido.estado);

    // Formatear fecha
    const fecha = new Date(pedido.fecha).toLocaleString('es-UY', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    // Traducir estado
    const estado = translateStatus(pedido.estado);

    content.innerHTML = `
        <div class="order-info">
            <h3 style="color: black;">Información del Pedido</h3>
            <div class="order-details">
                <div class="order-detail">
                    <strong>ID Pedido</strong>
                    <span>#${pedido.id_pedido}</span>
                </div>
                <div class="order-detail">
                    <strong>Fecha</strong>
                    <span>${fecha}</span>
                </div>
                <div class="order-detail">
                    <strong>Cliente</strong>
                    <span>${pedido.nombre_cliente}</span>
                </div>
                <div class="order-detail">
                    <strong>Teléfono</strong>
                    <span>${pedido.telefono_cliente}</span>
                </div>
                <div class="order-detail">
                    <strong>Tipo</strong>
                    <span>${pedido.tipo_pedido === 'local' ? 'Para llevar' : 'Delivery'}</span>
                </div>
                <div class="order-detail">
                    <strong>Método de Pago</strong>
                    <span>${pedido.metodo_pago || 'N/A'}</span>
                </div>
            </div>
            <div class="order-status ${pedido.estado.toLowerCase()}" style="background-color: #333333; color: white; padding: 5px; border-radius: 4px;">
                Estado: ${estado}
            </div>
        </div>

        <div class="order-items">
            <h3 style="color: black;">Detalles del Pedido</h3>
            ${detalles.map(detalle => {
                const nombre = detalle.nombre_producto || detalle.nombre_promocion || 'Producto';
                const descripcion = detalle.descripcion_producto || detalle.descripcion_promocion || '';
                const imagen = detalle.imagen_producto || detalle.imagen_promocion || 'img/Pizzaconmigo.png';
                const resolveImg = (img) => {
                    if (!img) return 'img/Pizzaconmigo.png';
                    if (img.startsWith('promo_')) return 'empleado/promociones/img/' + img;
                    if (img.startsWith('img/')) return 'empleado/productos/img/' + img.substring(4);
                    return 'empleado/productos/img/' + img;
                };
                const imgSrc = resolveImg(imagen);
                const ingredientesHtml = detalle.ingredientes && detalle.ingredientes.length > 0 ?
                    `<div style="font-size: 0.85rem; color: #555; margin-top: 5px;">
                        <strong>Ingredientes:</strong> ${detalle.ingredientes.map(ing => ing.nombre).join(', ')}
                    </div>` : '';
                return `
                    <div class="order-item" style="display: flex; align-items: center; padding: 15px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 10px; background: #f9f9f9;">
                        <img src="${imgSrc}" alt="${nombre}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; margin-right: 15px;">
                        <div style="flex: 1;">
                            <div style="font-weight: bold; margin-bottom: 5px;">${nombre}</div>
                            <div style="color: #666; font-size: 0.9rem; margin-bottom: 5px;">${descripcion}</div>
                            <div style="font-size: 0.9rem;">
                                <span>Cantidad: ${detalle.cantidad}</span> |
                                <span>Precio unitario: $${parseFloat(detalle.precio_unitario).toFixed(2)}</span>
                            </div>
                            ${ingredientesHtml}
                        </div>
                        <div style="font-weight: bold; color: #333;">$${parseFloat(detalle.subtotal).toFixed(2)}</div>
                    </div>
                `;
            }).join('')}
            <div class="order-total" style="text-align: right; font-size: 1.2rem; font-weight: bold; margin-top: 20px; padding: 10px; background: #333333; border-radius: 6px; color: white;">
                Total: $${parseFloat(pedido.total).toFixed(2)}
            </div>
        </div>
        <button onclick="showOrdersTable()" style="display: block; margin: 20px auto; padding: 10px 20px; background-color: #333333; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Volver a Mis Pedidos</button>
    `;
}

// Función para actualizar estado en localStorage
function updateLocalStorageStatus(idPedido, newEstado) {
    const pedidos = JSON.parse(localStorage.getItem('pizzaconmigo_pedidos') || '[]');
    const pedido = pedidos.find(p => p.id_pedido == idPedido);
    if (pedido && pedido.estado !== newEstado) {
        pedido.estado = newEstado;
        localStorage.setItem('pizzaconmigo_pedidos', JSON.stringify(pedidos));
    }
}

// Función para traducir el estado del pedido
function translateStatus(status) {
    const statusMap = {
        'pendiente': 'Pendiente',
        'confirmado': 'Confirmado',
        'en_preparacion': 'En Preparación',
        'listo': 'Listo',
        'en_reparto': 'En Reparto',
        'entregado': 'Entregado',
        'cancelado': 'Cancelado'
    };

    return statusMap[status.toLowerCase()] || status;
}

// Función para mostrar errores
function showError(message) {
    document.getElementById('orders-table').style.display = 'none';
    document.getElementById('order-content').style.display = 'block';
    const content = document.getElementById('order-content');
    content.innerHTML = `<div class="error-message">${message}</div>`;
}

// Función para mostrar mensaje cuando no se encuentra un pedido
function showNotFound(orderId) {
    // Remove from localStorage
    const pedidos = JSON.parse(localStorage.getItem('pizzaconmigo_pedidos') || '[]');
    const updatedPedidos = pedidos.filter(p => p.id_pedido != orderId);
    localStorage.setItem('pizzaconmigo_pedidos', JSON.stringify(updatedPedidos));

    document.getElementById('orders-table').style.display = 'none';
    document.getElementById('order-content').style.display = 'block';
    const content = document.getElementById('order-content');
    content.innerHTML = `
        <div class="not-found-message">
            <i class="fas fa-exclamation-circle"></i>
            <h3>Pedido No Encontrado</h3>
            <p>No se encontró ningún pedido con el ID: <strong>${orderId}</strong></p>
            <p>Este pedido ha sido eliminado y removido de tu lista.</p>
            <button onclick="showOrdersTable()" style="display: block; margin: 20px auto; padding: 10px 20px; background-color: #333333; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Volver a Mis Pedidos</button>
        </div>
    `;
}

// Configurar actualización automática
let refreshInterval;
function setupAutoRefresh(orderId) {
    // Limpiar intervalo anterior si existe
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    
    // Configurar nuevo intervalo de actualización (cada 30 segundos)
    refreshInterval = setInterval(() => {
        fetch(`cliente/php/obtener_pedido.php?id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.error) {
                    displayOrderDetails(data);
                }
            })
            .catch(error => {
                console.error('Error en actualización automática:', error);
            });
    }, 30000); // 30 segundos
}

// Detener actualización automática
function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}


// Limpiar intervalo cuando se cierra el modal
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('order-modal-close') ||
        e.target.classList.contains('order-modal') && e.target.id === 'order-modal') {
        stopAutoRefresh();
    }
});