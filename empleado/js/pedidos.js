// pedidos.js - Lógica para la gestión de pedidos en el panel de empleado

document.addEventListener('DOMContentLoaded', function() {
    const searchPhone = document.getElementById('search-phone');
    const statusFilter = document.getElementById('status-filter');
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');
    const btnBuscar = document.getElementById('btnBuscar');
    const clearFilters = document.getElementById('clearFilters');
    const ordersTable = document.getElementById('orders-table').querySelector('tbody');
    const modal = document.getElementById('order-modal');
    const modalContent = document.getElementById('modal-content');
    const closeModal = document.querySelector('.close');

    // Cargar pedidos iniciales
    loadOrders();

    // Filtros
    searchPhone.addEventListener('input', debounce(loadOrders, 300));
    statusFilter.addEventListener('change', loadOrders);
    dateFrom.addEventListener('change', loadOrders);
    dateTo.addEventListener('change', loadOrders);
    btnBuscar.addEventListener('click', loadOrders);

    clearFilters.addEventListener('click', () => {
        searchPhone.value = '';
        statusFilter.value = '';
        dateFrom.value = '';
        dateTo.value = '';
        loadOrders();
    });

    // Cerrar modal
    closeModal.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });

    // Delegar acciones
    ordersTable.addEventListener('click', function(e) {
        const button = e.target.closest('button');
        if (!button) return;

        const action = button.dataset.action;
        const idPedido = button.dataset.id;

        if (action === 'view') {
            viewOrderDetails(idPedido);
        } else if (action === 'edit') {
            editOrder(idPedido);
        } else if (action === 'delete') {
            deleteOrder(idPedido);
        } else if (action.startsWith('status-')) {
            const newStatus = action.split('-')[1];
            changeOrderStatus(idPedido, newStatus);
        }
    });

    async function loadOrders() {
        const params = new URLSearchParams();
        if (statusFilter.value) params.append('status', statusFilter.value);
        if (searchPhone.value) params.append('phone', searchPhone.value);
        if (dateFrom.value) params.append('date_from', dateFrom.value);
        if (dateTo.value) params.append('date_to', dateTo.value);

        try {
            const response = await fetch(`listar.php?${params}`);
            const orders = await response.json();

            renderOrders(orders);
        } catch (error) {
            console.error('Error cargando pedidos:', error);
        }
    }

    function renderOrders(orders) {
        ordersTable.innerHTML = '';

        orders.forEach(order => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${order.id_pedido}</td>
                <td>${new Date(order.fecha).toLocaleString('es-UY')}</td>
                <td>$${parseFloat(order.total).toFixed(2)}</td>
                <td>${order.nombre_cliente}</td>
                <td>${order.telefono_cliente}</td>
                <td><span class="status ${order.estado.replace('_', '-')}">${order.estado.replace('_', ' ')}</span></td>
                <td class="actions">
                    <button class="btn btn-secondary" data-action="view" data-id="${order.id_pedido}">Ver</button>
                    <button class="btn btn-primary" data-action="edit" data-id="${order.id_pedido}">Editar</button>
                    <button class="btn btn-danger" data-action="delete" data-id="${order.id_pedido}">Eliminar</button>
                    ${getActionButtons(order.estado, order.id_pedido)}
                </td>
            `;
            ordersTable.appendChild(row);
        });
    }

    function getActionButtons(currentStatus, idPedido) {
        const buttons = [];
        const statuses = {
            'pendiente': ['confirmado', 'cancelado'],
            'confirmado': ['en_preparacion', 'cancelado'],
            'en_preparacion': ['listo', 'cancelado'],
            'listo': ['en_reparto', 'cancelado'],
            'en_reparto': ['entregado'],
            'entregado': [],
            'cancelado': []
        };

        const availableStatuses = statuses[currentStatus] || [];

        availableStatuses.forEach(status => {
            let btnClass = 'btn-secondary';
            let label = status.replace('_', ' ');

            switch (status) {
                case 'confirmado':
                    btnClass = 'btn-success';
                    label = 'Confirmar';
                    break;
                case 'en_preparacion':
                    btnClass = 'btn-warning';
                    label = 'En Preparación';
                    break;
                case 'listo':
                    btnClass = 'btn-success';
                    label = 'Marcar Listo';
                    break;
                case 'en_reparto':
                    btnClass = 'btn-warning';
                    label = 'Asignar Reparto';
                    break;
                case 'entregado':
                    btnClass = 'btn-success';
                    label = 'Marcar Entregado';
                    break;
                case 'cancelado':
                    btnClass = 'btn-danger';
                    label = 'Cancelar';
                    break;
            }

            buttons.push(`<button class="btn ${btnClass}" data-action="status-${status}" data-id="${idPedido}">${label}</button>`);
        });

        return buttons.join('');
    }

    async function viewOrderDetails(idPedido) {
        try {
            const response = await fetch(`obtener_pedido.php?id_pedido=${idPedido}`);
            const order = await response.json();

            renderOrderModal(order, false);
            modal.style.display = 'block';
        } catch (error) {
            console.error('Error obteniendo detalles:', error);
        }
    }

    async function editOrder(idPedido) {
        try {
            const response = await fetch(`obtener_pedido.php?id_pedido=${idPedido}`);
            const order = await response.json();

            renderOrderModal(order, true);
            modal.style.display = 'block';
        } catch (error) {
            console.error('Error obteniendo detalles:', error);
        }
    }

    function renderOrderModal(order, isEdit = false) {
        const title = isEdit ? 'Editar Pedido' : 'Detalles del Pedido';
        let html = `
            <h2>${title} #${order.id_pedido}</h2>
            <div class="order-details">
                <p><strong>Fecha:</strong> ${new Date(order.fecha).toLocaleString('es-UY')}</p>
                <p><strong>Cliente:</strong> ${isEdit ? `<input type="text" id="edit-nombre" value="${order.nombre_cliente}">` : order.nombre_cliente}</p>
                <p><strong>Teléfono:</strong> ${isEdit ? `<input type="text" id="edit-telefono" value="${order.telefono_cliente}">` : order.telefono_cliente}</p>
                <p><strong>Dirección:</strong> ${isEdit ? `<input type="text" id="edit-direccion" value="${order.direccion_cliente || ''}">` : (order.direccion_cliente || 'N/A')}</p>
                <p><strong>Tipo:</strong> ${order.tipo_pedido}</p>
                <p><strong>Método de Pago:</strong> ${order.metodo_pago}</p>
                <p><strong>Notas:</strong> ${isEdit ? `<textarea id="edit-notas">${order.notas || ''}</textarea>` : (order.notas || 'N/A')}</p>
                <p><strong>Estado:</strong> ${isEdit ? `<select id="edit-estado">
                    <option value="pendiente" ${order.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                    <option value="confirmado" ${order.estado === 'confirmado' ? 'selected' : ''}>Confirmado</option>
                    <option value="en_preparacion" ${order.estado === 'en_preparacion' ? 'selected' : ''}>En Preparación</option>
                    <option value="listo" ${order.estado === 'listo' ? 'selected' : ''}>Listo</option>
                    <option value="en_reparto" ${order.estado === 'en_reparto' ? 'selected' : ''}>En Reparto</option>
                    <option value="entregado" ${order.estado === 'entregado' ? 'selected' : ''}>Entregado</option>
                    <option value="cancelado" ${order.estado === 'cancelado' ? 'selected' : ''}>Cancelado</option>
                </select>` : `<span class="status ${order.estado.replace('_', '-')}">${order.estado.replace('_', ' ')}</span>`}</p>

                <h3>Items del Pedido</h3>
        `;

        order.detalles.forEach(item => {
            html += `
                <div class="order-item">
                    <span>${item.nombre || 'Producto'} x${item.cantidad}</span>
                    <span>$${parseFloat(item.subtotal).toFixed(2)}</span>
                </div>
            `;
        });

        html += `
                <div class="order-item total">
                    <span>Total</span>
                    <span>$${parseFloat(order.total).toFixed(2)}</span>
                </div>
            </div>
        `;

        if (isEdit) {
            html += `<button class="btn btn-primary" id="save-edit">Guardar Cambios</button>`;
        }

        modalContent.innerHTML = html;

        if (isEdit) {
            document.getElementById('save-edit').addEventListener('click', () => saveOrderEdit(order.id_pedido));
        }
    }

    async function deleteOrder(idPedido) {
        if (confirm('¿Estás seguro de que quieres eliminar este pedido? ID: ' + idPedido)) {
            try {
                const response = await fetch('eliminar_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_pedido: idPedido
                    })
                });

                const result = await response.json();

                if (result.success) {
                    loadOrders(); // Recargar pedidos
                } else {
                    alert('Error eliminando pedido: ' + result.error);
                }
            } catch (error) {
                console.error('Error eliminando pedido:', error);
                alert('Error de conexión');
            }
        }
    }

    async function saveOrderEdit(idPedido) {
        const nombre = document.getElementById('edit-nombre').value.trim();
        const telefono = document.getElementById('edit-telefono').value.trim();
        const direccion = document.getElementById('edit-direccion').value.trim();
        const notas = document.getElementById('edit-notas').value.trim();
        const estado = document.getElementById('edit-estado').value;

        if (!nombre || !telefono) {
            alert('Nombre y teléfono son obligatorios');
            return;
        }

        try {
            // Update order details
            const response = await fetch('editar_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_pedido: idPedido,
                    nombre_cliente: nombre,
                    telefono_cliente: telefono,
                    direccion_cliente: direccion,
                    notas: notas,
                    estado: estado
                })
            });

            const result = await response.json();

            if (result.success) {
                modal.style.display = 'none';
                loadOrders(); // Recargar pedidos
            } else {
                alert('Error guardando cambios: ' + result.error);
            }
        } catch (error) {
            console.error('Error guardando cambios:', error);
            alert('Error de conexión');
        }
    }

    async function changeOrderStatus(idPedido, newStatus) {
        try {
            const response = await fetch('accion_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_pedido: idPedido,
                    accion: newStatus
                })
            });

            const result = await response.json();

            if (result.success) {
                // Recargar pedidos
                loadOrders();
                // Aquí se podría emitir evento para notificar al cliente
            } else {
                alert('Error cambiando estado: ' + result.error);
            }
        } catch (error) {
            console.error('Error cambiando estado:', error);
            alert('Error de conexión');
        }
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});