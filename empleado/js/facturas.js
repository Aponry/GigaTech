// facturas.js - Lógica para la gestión de facturas en el panel de empleado

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
    const printDaily = document.getElementById('print-daily');
    const printMonthly = document.getElementById('print-monthly');
    const printDates = document.getElementById('print-dates');
    const printId = document.getElementById('print-id');
    const printClient = document.getElementById('print-client');
    const datesModal = document.getElementById('dates-modal');
    const closeDatesModal = document.getElementById('close-dates-modal');
    const printDateFrom = document.getElementById('print-date-from');
    const printDateTo = document.getElementById('print-date-to');
    const printDatesConfirm = document.getElementById('print-dates-confirm');
    const idModal = document.getElementById('id-modal');
    const closeIdModal = document.getElementById('close-id-modal');
    const orderId = document.getElementById('order-id');
    const printIdConfirm = document.getElementById('print-id-confirm');
    const clientModal = document.getElementById('client-modal');
    const closeClientModal = document.getElementById('close-client-modal');
    const clientPhone = document.getElementById('client-phone');
    const printClientConfirm = document.getElementById('print-client-confirm');

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

    // Printing
    printDaily.addEventListener('click', () => {
        window.open('facturas/imprimir_dia.php', '_blank');
    });

    printMonthly.addEventListener('click', () => {
        window.open('facturas/imprimir_mes.php', '_blank');
    });

    printDates.addEventListener('click', () => {
        datesModal.style.display = 'block';
    });

    closeDatesModal.addEventListener('click', () => {
        datesModal.style.display = 'none';
    });

    printDatesConfirm.addEventListener('click', () => {
        const dateFrom = printDateFrom.value;
        const dateTo = printDateTo.value;
        if (dateFrom && dateTo) {
            window.open(`facturas/imprimir_fechas.php?fecha_desde=${encodeURIComponent(dateFrom)}&fecha_hasta=${encodeURIComponent(dateTo)}`, '_blank');
            datesModal.style.display = 'none';
            printDateFrom.value = '';
            printDateTo.value = '';
        } else {
            alert('Por favor seleccione ambas fechas');
        }
    });

    printId.addEventListener('click', () => {
        idModal.style.display = 'block';
    });

    closeIdModal.addEventListener('click', () => {
        idModal.style.display = 'none';
    });

    printIdConfirm.addEventListener('click', () => {
        const id = orderId.value.trim();
        if (id) {
            window.open(`facturas/imprimir_id.php?id_pedido=${encodeURIComponent(id)}`, '_blank');
            idModal.style.display = 'none';
            orderId.value = '';
        } else {
            alert('Por favor ingrese un ID de pedido');
        }
    });

    printClient.addEventListener('click', () => {
        clientModal.style.display = 'block';
    });

    closeClientModal.addEventListener('click', () => {
        clientModal.style.display = 'none';
    });

    printClientConfirm.addEventListener('click', () => {
        const phones = clientPhone.value.trim();
        if (phones) {
            window.open(`facturas/imprimir_cliente.php?telefonos=${encodeURIComponent(phones)}`, '_blank');
            clientModal.style.display = 'none';
            clientPhone.value = '';
        } else {
            alert('Por favor ingrese uno o más números de teléfono');
        }
    });

    // Delegar acciones
    ordersTable.addEventListener('click', function(e) {
        const button = e.target.closest('button');
        if (!button) return;

        const action = button.dataset.action;
        const idPedido = button.dataset.id;

        if (action === 'view') {
            viewOrderDetails(idPedido);
        }
    });

    async function loadOrders() {
        const params = new URLSearchParams();
        if (statusFilter.value) params.append('status', statusFilter.value);
        if (searchPhone.value) params.append('phone', searchPhone.value);
        if (dateFrom.value) params.append('date_from', dateFrom.value);
        if (dateTo.value) params.append('date_to', dateTo.value);

        try {
            const response = await fetch('pedidos/listar.php?' + params);
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
                </td>
            `;
            ordersTable.appendChild(row);
        });
    }

    async function viewOrderDetails(idPedido) {
        try {
            const response = await fetch(`pedidos/obtener_pedido.php?id_pedido=${idPedido}`);
            const order = await response.json();

            renderOrderModal(order);
            modal.style.display = 'block';
        } catch (error) {
            console.error('Error obteniendo detalles:', error);
        }
    }

    function renderOrderModal(order) {
        const title = 'Detalles de la Factura';
        let html = `
            <h2>${title} #${order.id_pedido}</h2>
            <div class="order-details">
                <p><strong>Fecha:</strong> ${new Date(order.fecha).toLocaleString('es-UY')}</p>
                <p><strong>Cliente:</strong> ${order.nombre_cliente}</p>
                <p><strong>Teléfono:</strong> ${order.telefono_cliente}</p>
                <p><strong>Dirección:</strong> ${order.direccion_cliente || 'N/A'}</p>
                <p><strong>Tipo:</strong> ${order.tipo_pedido}</p>
                <p><strong>Método de Pago:</strong> ${order.metodo_pago}</p>
                <p><strong>Notas:</strong> ${order.notas || 'N/A'}</p>
                <p><strong>Estado:</strong> <span class="status ${order.estado.replace('_', '-')}">${order.estado.replace('_', ' ')}</span></p>

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

        modalContent.innerHTML = html;
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