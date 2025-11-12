// pagar.js - Lógica para la pantalla de pago

document.addEventListener('DOMContentLoaded', function() {
    // Obtener parámetros de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const orderId = urlParams.get('id_pedido');

    // Si hay un ID de pedido, mostrar la pantalla de espera
    if (orderId) {
        showWaitingScreen(orderId);
    }
});

function showWaitingScreen(orderId) {
    // Esta función se encargaría de mostrar la pantalla de espera
    // La lógica real está implementada en pagar.php
    console.log('Mostrando pantalla de espera para el pedido:', orderId);
}

const API_BASE = window.location.origin + '/GigaTech/GigaTech/cliente';

document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('.payment-method');
    const metodoPagoInput = document.getElementById('metodo_pago');
    const comprobanteGroup = document.getElementById('comprobante-group');
    const posButton = document.getElementById('pos-button');
    const confirmButton = document.getElementById('confirm-button');
    const tipoPedidoRadios = document.querySelectorAll('input[name="tipo_pedido"]');
    const direccionInput = document.getElementById('direccion');
    const form = document.getElementById('payment-form');
    const waitingScreen = document.getElementById('waiting-screen');
    const orderSummary = document.getElementById('order-summary');

    let selectedPayment = '';
    let eventSource = null;
    let pollingInterval = null;

    // Cargar resumen del pedido
    loadOrderSummary();

    // Selección de método de pago
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            paymentMethods.forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            selectedPayment = this.dataset.method;
            metodoPagoInput.value = selectedPayment;

            // Mostrar campos según método
            if (selectedPayment === 'transferencia') {
                comprobanteGroup.style.display = 'block';
                posButton.style.display = 'none';
            } else if (selectedPayment === 'pos') {
                comprobanteGroup.style.display = 'none';
                posButton.style.display = 'inline-block';
            } else {
                comprobanteGroup.style.display = 'none';
                posButton.style.display = 'none';
            }
        });
    });

    // Validar dirección para domicilio
    tipoPedidoRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'domicilio') {
                direccionInput.required = true;
                direccionInput.previousElementSibling.textContent = 'Dirección *';
            } else {
                direccionInput.required = false;
                direccionInput.previousElementSibling.textContent = 'Dirección (opcional)';
            }
        });
    });

    // Confirmar pedido
    confirmButton.addEventListener('click', async function(e) {
        e.preventDefault();

        if (!selectedPayment) {
            showError('Selecciona un método de pago');
            return;
        }

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const data = {
            items: [], // Se cargará desde el carrito
            total: 0,
            tipo_pedido: formData.get('tipo_pedido'),
            direccion_cliente: formData.get('direccion'),
            nombre_cliente: formData.get('nombre'),
            telefono_cliente: formData.get('telefono'),
            metodo_pago: selectedPayment,
            notas: formData.get('notas'),
            comprobante: formData.get('comprobante') || null
        };

        // Cargar items del carrito desde el servidor
        try {
            const cartResponse = await fetch(API_BASE + '/php/obtener_carrito.php');
            if (!cartResponse.ok) {
                throw new Error('Error al cargar el carrito');
            }
            const cartData = await cartResponse.json();
            const cartItems = cartData.items || [];
            data.items = cartItems.map(item => ({
                id_producto: item.id_producto || null,
                id_promocion: item.id_promocion || null,
                cantidad: item.cantidad,
                precio_unitario: item.precio_unitario,
                subtotal: item.subtotal
            }));
            data.total = cartData.total || 0;
        } catch (error) {
            showError('Error al cargar el carrito');
            return;
        }

        try {
            const response = await fetch('../../empleado/pedidos/crear_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.ok) {
                // Guardar en localStorage
                const pedidos = JSON.parse(localStorage.getItem('pizzaconmigo_pedidos') || '[]');
                const pedidoData = {
                    id_pedido: result.id_pedido,
                    fecha: new Date().toISOString(),
                    estado: 'pendiente',
                    total: data.total,
                    estado: result.estado,
                    nombre_cliente: data.nombre_cliente,
                    telefono_cliente: data.telefono_cliente,
                    datos_raw: data
                };
                pedidos.push(pedidoData);
                localStorage.setItem('pizzaconmigo_pedidos', JSON.stringify(pedidos));

                // Guardar cookie con teléfono por 30 días
                if (data.telefono_cliente) {
                    document.cookie = `pizzaconmigo_telefono=${encodeURIComponent(data.telefono_cliente)}; max-age=${30*24*60*60}; path=/`;
                }

                // Mostrar pantalla de espera
                showWaitingScreen(result.id_pedido, data);

                // Iniciar escucha de actualizaciones
                startListening(result.id_pedido);
            } else {
                showError(result.error || 'Error al crear el pedido');
            }
        } catch (error) {
            showError('Error de conexión');
        }
    });

    async function loadOrderSummary() {
        try {
            const response = await fetch(API_BASE + '/php/obtener_carrito.php');
            if (!response.ok) {
                throw new Error('Error al cargar el carrito');
            }
            const data = await response.json();
            const items = data.items || [];
            let html = '<h3>Resumen del Pedido</h3>';
            let total = 0;

            items.forEach(item => {
                const subtotal = item.subtotal || 0;
                html += `<div class="order-item">
                    <span>${item.nombre} x${item.cantidad}</span>
                    <span>$${subtotal.toFixed(2)}</span>
                </div>`;
                total += subtotal;
            });

            html += `<div class="order-item total">
                <span>Total</span>
                <span>$${total.toFixed(2)}</span>
            </div>`;

            orderSummary.innerHTML = html;
        } catch (error) {
            console.error('Error loading order summary:', error);
            orderSummary.innerHTML = '<p>Error al cargar el resumen del pedido</p>';
        }
    }

    function showWaitingScreen(idPedido, data) {
        form.style.display = 'none';
        waitingScreen.style.display = 'block';

        let html = `<h2>Esperando Confirmación</h2>
            <p>ID del Pedido: ${idPedido}</p>
            <p>Estado: Pendiente</p>
            <div class="order-summary">
                <h3>Datos del Cliente</h3>
                <p><strong>Nombre:</strong> ${data.nombre_cliente}</p>
                <p><strong>Teléfono:</strong> ${data.telefono_cliente}</p>
                <p><strong>Dirección:</strong> ${data.direccion_cliente || 'N/A'}</p>
                <p><strong>Método de Pago:</strong> ${data.metodo_pago}</p>
                <p><strong>Notas:</strong> ${data.notas || 'N/A'}</p>
                <h3>Items</h3>`;

        data.items.forEach(item => {
            html += `<div class="order-item">
                <span>${item.nombre || 'Producto'} x${item.cantidad}</span>
                <span>$${item.subtotal.toFixed(2)}</span>
            </div>`;
        });

        html += `<div class="order-item total">
            <span>Total</span>
            <span>$${data.total.toFixed(2)}</span>
        </div></div>`;

        waitingScreen.innerHTML = html;
    }

    function startListening(idPedido) {
        // Intentar SSE primero
        if (typeof EventSource !== 'undefined') {
            eventSource = new EventSource(`../../empleado/pedidos/pedido_stream.php?id_pedido=${idPedido}`);
            eventSource.addEventListener('update', function(event) {
                const data = JSON.parse(event.data);
                if (data.estado === 'confirmado') {
                    updateStatus('Confirmado');
                    stopListening();
                }
            });
            eventSource.onerror = function() {
                // Fallback a polling
                eventSource.close();
                startPolling(idPedido);
            };
        } else {
            startPolling(idPedido);
        }
    }

    function startPolling(idPedido) {
        pollingInterval = setInterval(async () => {
            try {
                const response = await fetch(`../../empleado/pedidos/pedido_status.php?id_pedido=${idPedido}`);
                const data = await response.json();
                if (data.estado === 'confirmado') {
                    updateStatus('Confirmado');
                    stopListening();
                }
            } catch (error) {
                // Continuar polling
            }
        }, 3000);
    }

    function stopListening() {
        if (eventSource) {
            eventSource.close();
        }
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
    }

    function updateStatus(status) {
        const statusElement = waitingScreen.querySelector('p:nth-child(3)');
        statusElement.textContent = `Estado: ${status}`;
        if (status === 'Confirmado') {
            statusElement.style.color = 'green';
        }
    }

    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error';
        errorDiv.textContent = message;
        form.insertBefore(errorDiv, form.firstChild);
        setTimeout(() => errorDiv.remove(), 5000);
    }
});