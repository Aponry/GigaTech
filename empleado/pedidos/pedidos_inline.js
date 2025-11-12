// Poblar menú inferior con botones de estado
const statuses = ['pendiente', 'confirmado', 'en_preparacion', 'listo', 'en_reparto', 'entregado', 'cancelado'];
const pedidosButtons = document.getElementById('pedidos-buttons');
statuses.forEach(status => {
    const btn = document.createElement('button');
    btn.className = 'section-button';
    btn.textContent = status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    btn.addEventListener('click', () => {
        document.getElementById('status-filter').value = status;
        // Trigger loadOrders from pedidos.js
        const event = new Event('change');
        document.getElementById('status-filter').dispatchEvent(event);
    });
    pedidosButtons.appendChild(btn);
});

// Desplazarse hacia arriba
const scrollToTopBtn = document.getElementById('scrollToTop');
scrollToTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));