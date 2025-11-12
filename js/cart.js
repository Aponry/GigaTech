// Funcionalidad del carrito deslizante
const API_BASE = window.location.origin + '/GigaTech/GigaTech/cliente';

function createCartOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'cart-overlay';
    overlay.addEventListener('click', (e) => {
        // Only close if clicking directly on overlay, not on cart panel
        if (e.target === overlay) {
            closeCart();
        }
    });
    document.body.appendChild(overlay);
    return overlay;
}

function resolveImg(img) {
    if (!img) return 'img/Pizzaconmigo.png';
    if (img.startsWith('empleado/productos/img/')) {
        return '/GigaTech/GigaTech/' + img;
    }
    if (img.startsWith('empleado/promociones/img/')) {
        return '/GigaTech/GigaTech/' + img;
    }
    return img;
}

function openCart() {
    const panel = document.getElementById('cart-panel');
    const overlay = document.querySelector('.cart-overlay') || createCartOverlay();
    if (!panel) return;

    // Eliminar cualquier clase de animación previa
    panel.classList.remove('closing');

    // Asegurarse de que el panel esté visible
    panel.style.display = 'flex';

    // Mostrar overlay
    overlay.classList.add('active');

    // Agregar la clase open después de un pequeño retraso para activar la animación
    setTimeout(() => {
        panel.classList.add('open');
    }, 10);

    updateCartPanel();
}

function closeCart() {
    const panel = document.getElementById('cart-panel');
    const overlay = document.querySelector('.cart-overlay');
    if (!panel) return;

    // Remover la clase open y agregar closing para activar la animación de cierre
    panel.classList.remove('open');
    panel.classList.add('closing');

    // Ocultar overlay
    if (overlay) {
        overlay.classList.remove('active');
    }

    // Ocultar el panel después de que termine la animación
    panel.addEventListener('animationend', function handler() {
        panel.removeEventListener('animationend', handler);
        panel.style.display = 'none';
        panel.classList.remove('closing');
    });
}

function initCart() {
    const cartToggle = document.getElementById('cartToggle');
    if (cartToggle) {
        cartToggle.addEventListener('click', (e) => {
            e.preventDefault();
            openCart();
        });
    }

    const closeBtn = document.getElementById('close-cart');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeCart);
    }

    // Agregar evento de clic al icono de notificación
    const notification = document.getElementById('cart-notification');
    if (notification) {
        notification.addEventListener('click', (e) => {
            e.preventDefault();
            openCart();
        });
    }

    // Agregar evento de submit al formulario de finalizar pedido
    const checkoutForm = document.querySelector('.cart-footer-buttons form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', (e) => {
            // No prevenir el comportamiento por defecto, permitir que el formulario se envíe normalmente
        });
    }

    // Cargar carrito inicial
    fetch(API_BASE + '/php/obtener_carrito.php')
        .then(r => {
            if (!r.ok) {
                if (r.status === 500) {
                    console.error('Error del servidor (500): El servidor encontró un error interno');
                    throw new Error('Error del servidor: No se pudo obtener el carrito');
                } else if (r.status === 404) {
                    console.error('Recurso no encontrado (404)');
                    throw new Error('No se pudo encontrar el carrito');
                } else {
                    throw new Error('HTTP ' + r.status + ': ' + r.statusText);
                }
            }
            
            return r.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Respuesta no válida del servidor:', text);
                    throw new Error('La respuesta del servidor no es JSON válido');
                }
            });
        })
        .then(j => {
            if (!j || typeof j !== 'object') {
                throw new Error('Datos del carrito inválidos');
            }
            updateCartCount(j.count || 0);
        })
        .catch(error => {
            console.error('Error loading initial cart:', error);
            // En caso de error, mostrar carrito como vacío
            updateCartCount(0);
        });
}

function updateCartCount(count) {
    const cartToggle = document.getElementById('cartToggle');
    if (cartToggle) {
        cartToggle.textContent = `Carrito (${count})`;
    }

    // Actualizar icono de notificación
    const notification = document.getElementById('cart-notification');
    if (notification) {
        if (count > 0) {
            notification.textContent = count;
            notification.style.display = 'flex';
        } else {
            notification.style.display = 'none';
        }
    }
}

function updateCartPanel() {
    fetch(API_BASE + '/php/obtener_carrito.php')
        .then(r => {
            // Validaciones de respuesta HTTP
            if (!r.ok) {
                let errorMsg;
                switch (r.status) {
                    case 500:
                        console.error('Error del servidor (500): El servidor encontró un error interno');
                        errorMsg = 'Error del servidor: No se pudo procesar la solicitud';
                        break;
                    case 404:
                        console.error('Recurso no encontrado (404)');
                        errorMsg = 'No se pudo encontrar el recurso solicitado';
                        break;
                    case 400:
                        console.error('Solicitud incorrecta (400)');
                        errorMsg = 'Solicitud incorrecta al servidor';
                        break;
                    default:
                        errorMsg = `Error HTTP ${r.status}: ${r.statusText}`;
                }
                throw new Error(errorMsg);
            }

            // Validación de contenido y tipo de respuesta
            const contentType = r.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.warn('Tipo de contenido inesperado:', contentType);
            }

            // Parsear respuesta JSON con manejo de errores
            return r.text().then(text => {
                try {
                    // Validar que la respuesta no esté vacía
                    if (!text || text.trim() === '') {
                        throw new Error('Respuesta vacía del servidor');
                    }
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Respuesta no válida del servidor:', text);
                    throw new Error('La respuesta del servidor no es JSON válido');
                }
            });
        })
        .then(j => {
            // Validación exhaustiva de la estructura de datos
            if (!j) {
                throw new Error('Datos del carrito nulos o indefinidos');
            }

            if (typeof j !== 'object') {
                throw new Error('Formato de datos del carrito inválido');
            }

            // Validar propiedades numéricas principales
            const validateNumber = (value, name, defaultValue = 0) => {
                const num = parseFloat(value);
                if (isNaN(num) || num < 0) {
                    console.warn(`Valor inválido para ${name}:`, value);
                    return defaultValue;
                }
                return num;
            };

            const subtotal = validateNumber(j.subtotal, 'subtotal');
            const discounts = validateNumber(j.discounts, 'discounts');
            const taxes = validateNumber(j.taxes, 'taxes');
            const total = validateNumber(j.total, 'total');

            // Validar lista de items
            let items = j.items;
            if (!Array.isArray(items)) {
                console.warn('Items no es un array, intentando convertir o inicializar');
                items = items ? (Array.isArray(items) ? items : [items]) : [];
            }

            // Validar elemento del DOM
            const itemsDiv = document.getElementById('cart-items');
            if (!itemsDiv) {
                console.error('No se encontró el elemento #cart-items en el DOM');
                return;
            }

            // Procesar cada item con validaciones robustas
            if (items.length === 0) {
                itemsDiv.innerHTML = '<p class="empty-cart-message">Tu carrito está vacío</p>';
            } else {
                const validItemsHtml = items.map((c, index) => {
                    // Validar item base requerido
                    if (!c || typeof c !== 'object') {
                        console.warn('Item de carrito inválido (no es objeto):', c);
                        return null;
                    }

                    // Validar campos obligatorios
                    if (!c.nombre || typeof c.nombre !== 'string') {
                        console.warn('Nombre del producto faltante o inválido:', c);
                        return null;
                    }

                    if (!c.precio_unitario) {
                        console.warn('Precio unitario faltante para producto:', c.nombre);
                        return null;
                    }

                    // Validar y sanitizar campos opcionales
                    const nombre = c.nombre.trim();
                    const precioUnitario = validateNumber(c.precio_unitario, 'precio_unitario', 0);
                    const descripcion = c.descripcion && typeof c.descripcion === 'string' ? c.descripcion.trim() : '';
                    const cantidad = Math.max(1, validateNumber(c.cantidad, 'cantidad', 1));
                    const subtotalItem = validateNumber(c.subtotal, 'subtotal', precioUnitario * cantidad);

                    // Generar HTML para ingredientes con validación
                    let ingredientesHtml = '';
                    if ((c.tipo === 'pizza' || c.tipo === 'hamburguesa') && 
                        c.ingredientes && Array.isArray(c.ingredientes) && c.ingredientes.length > 0) {
                        
                        const ingredientesValidos = c.ingredientes
                            .filter(ing => ing && ing.nombre && typeof ing.nombre === 'string')
                            .map(ing => {
                                const ingNombre = ing.nombre.trim();
                                const ingCantidad = Math.max(1, validateNumber(ing.cantidad, 'cantidad_ingrediente', 1));
                                const ingPrecio = validateNumber(ing.precio, 'precio_ingrediente', 0);
                                const ingTotal = ingPrecio * ingCantidad;
                                
                                return `<li><span class="ingredient-name">${ingNombre}</span> <span class="ingredient-qty">x${ingCantidad}</span> <span class="ingredient-price">(+$${ingTotal.toFixed(2)})</span></li>`;
                            });

                        if (ingredientesValidos.length > 0) {
                            const ingTotal = validateNumber(c.ing_total, 'ing_total', 0);
                            ingredientesHtml = `
                                <div class="cart-item-ingredients">
                                    <strong>Ingredientes:</strong>
                                    <ul>
                                        ${ingredientesValidos.join('')}
                                    </ul>
                                    <p class="ingredients-total">Total Ing: $${ingTotal.toFixed(2)}</p>
                                </div>
                            `;
                        }
                    }

                    // Generar HTML para extras con validación
                    let extrasHtml = '';
                    if (c.extras && Array.isArray(c.extras) && c.extras.length > 0) {
                        const extrasValidos = c.extras
                            .filter(ex => ex && ex.nombre && typeof ex.nombre === 'string')
                            .map(ex => {
                                const exNombre = ex.nombre.trim();
                                const exPrecio = validateNumber(ex.precio, 'precio_extra', 0);
                                return `<li>${exNombre} (+$${exPrecio.toFixed(2)})</li>`;
                            });

                        if (extrasValidos.length > 0) {
                            const extrasTotal = validateNumber(c.extras_total, 'extras_total', 0);
                            extrasHtml = `
                                <div class="cart-item-extras">
                                    <strong>Extras:</strong>
                                    <ul>
                                        ${extrasValidos.join('')}
                                    </ul>
                                    <p>Total Extras: $${extrasTotal.toFixed(2)}</p>
                                </div>
                            `;
                        }
                    }

                    return `
                        <div class="cart-item">
                            <div class="cart-item-main">
                                <img src="${resolveImg(c.imagen)}" alt="${nombre}" class="cart-item-image">
                                <div class="cart-item-content">
                                    <div class="cart-item-header">
                                        <span class="cart-item-name">${nombre}</span>
                                        <span class="cart-item-price">$${precioUnitario.toFixed(2)}</span>
                                    </div>
                                    ${descripcion ? `<span class="cart-item-description">${descripcion}</span>` : ''}
                                    <div class="cart-item-summary">
                                        <span class="cart-item-qty">Cant: ${cantidad}</span>
                                        <span class="cart-item-subtotal">Subtotal: $${subtotalItem.toFixed(2)}</span>
                                    </div>
                                </div>
                                <div class="cart-item-controls">
                                    <button class="cart-qty-btn" data-index="${index}" data-action="minus">-</button>
                                    <span class="cart-qty-display">${cantidad}</span>
                                    <button class="cart-qty-btn" data-index="${index}" data-action="plus">+</button>
                                </div>
                            </div>
                            ${ingredientesHtml}
                            ${extrasHtml}
                        </div>
                    `;
                })
                .filter(html => html !== null) // Eliminar items inválidos
                .join('');

                itemsDiv.innerHTML = validItemsHtml;
            }

            // Actualizar totales con validación
            const totalEl = document.getElementById('cart-total');
            if (totalEl) {
                totalEl.innerHTML = `
                    <div class="cart-footer">
                        <p>Subtotal: $${subtotal.toFixed(2)}</p>
                        <p>Descuentos: -$${discounts.toFixed(2)}</p>
                        <p>Impuestos: $${taxes.toFixed(2)}</p>
                        <p>Total: $${total.toFixed(2)}</p>
                    </div>
                `;
            }

            // Actualizar contador del carrito
            updateCartCount(items.length);

            // Configurar eventos para botones de cantidad con validación mejorada
            const buttons = document.querySelectorAll('.cart-qty-btn');
            buttons.forEach(btn => {
                // Eliminar listeners previos para evitar duplicados
                const clone = btn.cloneNode(true);
                btn.parentNode.replaceChild(clone, btn);
                
                clone.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const indexStr = e.target.dataset.index;
                    const action = e.target.dataset.action;
                    
                    // Validaciones robustas de entrada
                    if (!indexStr) {
                        console.error('Falta atributo data-index en botón de cantidad');
                        return;
                    }
                    
                    const index = parseInt(indexStr, 10);
                    if (isNaN(index) || index < 0 || index >= items.length) {
                        console.error('Índice de carrito fuera de rango:', index, 'Longitud máxima:', items.length - 1);
                        return;
                    }
                    
                    if (!action || !['plus', 'minus'].includes(action)) {
                        console.error('Acción inválida en botón de cantidad:', action);
                        return;
                    }
                    
                    const controls = e.target.closest('.cart-item-controls');
                    if (!controls) {
                        console.error('No se encontraron controles de cantidad');
                        return;
                    }
                    
                    const qtyDisplay = controls.querySelector('.cart-qty-display');
                    if (!qtyDisplay) {
                        console.error('No se encontró el display de cantidad');
                        return;
                    }
                    
                    const currentQty = parseInt(qtyDisplay.textContent) || 1;
                    const delta = action === 'plus' ? 1 : -1;
                    const newQty = currentQty + delta;
                    
                    // Validación de cantidad mínima
                    if (newQty <= 0) {
                        // Confirmación antes de eliminar
                        if (confirm('¿Está seguro que desea eliminar este artículo del carrito?')) {
                            updateCartItem(index, 0); // Cantidad 0 indica eliminación
                        }
                        return;
                    }
                    
                    // Validación de límite máximo (por ejemplo, 99 unidades)
                    if (newQty > 99) {
                        alert('No se pueden agregar más de 99 unidades de un producto');
                        return;
                    }
                    
                    // Actualizar cantidad
                    updateCartItem(index, newQty);
                });
            });

            // Función auxiliar para actualizar items del carrito
            function updateCartItem(index, cantidad) {
                // Mostrar estado de carga
                const buttons = document.querySelectorAll(`.cart-qty-btn[data-index="${index}"]`);
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                });

                const endpoint = cantidad === 0 ? 'remove' : 'update';
                const body = cantidad === 0 
                    ? `action=remove&index=${index}`
                    : `action=update&index=${index}&cantidad=${cantidad}`;

                fetch(API_BASE + '/php/obtener_carrito.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body
                })
                .then(response => {
                    // Validar respuesta HTTP
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
                    }
                    
                    // Manejar respuesta según el tipo de contenido
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json().then(data => {
                            if (data.error) {
                                throw new Error(data.error);
                            }
                            return data;
                        });
                    } else {
                        return response.text().then(text => {
                            // Respuesta no JSON, pero éxito HTTP
                            return { success: true, message: 'Operación exitosa' };
                        });
                    }
                })
                .then(result => {
                    // Actualizar panel después de éxito
                    updateCartPanel();
                })
                .catch(error => {
                    console.error('Error en operación del carrito:', error);
                    alert(`No se pudo ${cantidad === 0 ? 'eliminar' : 'actualizar'} el producto: ${error.message}`);
                    // Restaurar estado original
                    buttons.forEach(btn => {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                    });
                });
            }
        })
        .catch(error => {
            console.error('Error crítico al actualizar el panel del carrito:', error);
            
            // Manejo diferenciado de errores
            let userMessage;
            if (error.message.includes('Error del servidor')) {
                userMessage = 'Error del servidor: No se pudo procesar la solicitud. Por favor, intente más tarde.';
            } else if (error.message.includes('JSON')) {
                userMessage = 'Error de comunicación con el servidor. Por favor, recargue la página.';
            } else if (error.message.includes('Network')) {
                userMessage = 'Error de red: No se pudo conectar con el servidor. Verifique su conexión e intente nuevamente.';
            } else {
                userMessage = 'Error inesperado al cargar el carrito. Por favor, recargue la página.';
            }
            
            // Actualizar UI con mensaje de error
            const itemsDiv = document.getElementById('cart-items');
            if (itemsDiv) {
                itemsDiv.innerHTML = `<p class="error-message">${userMessage}</p>`;
            }
            
            // Asegurar que el contador del carrito refleje el estado correcto
            updateCartCount(0);
        });
}

// Llamar init cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', initCart);