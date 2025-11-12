let pollingInterval;

function checkOrderStatus() {
    fetch('php/obtener_pedido.php?id=<?= $id_pedido ?>')
        .then(response => response.json())
        .then(data => {
            if (data.pedido) {
                const status = data.pedido.estado;
                document.getElementById('order-status').textContent = status.charAt(0).toUpperCase() + status.slice(1);

                if (status !== 'pendiente') {
                    // Status changed
                    document.getElementById('waiting-screen').style.display = 'none';

                    if (status === 'cancelado') {
                        document.getElementById('cancelled-screen').style.display = 'block';
                    } else {
                        document.getElementById('confirmed-screen').style.display = 'block';
                    }

                    document.getElementById('pending-buttons').style.display = 'block';
                    clearInterval(pollingInterval);
                }
            }
        })
        .catch(error => {
            console.error('Error checking order status:', error);
        });
}

// Start polling every 5 seconds
pollingInterval = setInterval(checkOrderStatus, 5000);

// Clear cart
localStorage.removeItem('carrito');