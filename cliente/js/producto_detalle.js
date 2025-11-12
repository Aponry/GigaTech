/* Funcionalidad básica para detalle de producto */
const BASE_API = window.location.origin + '/GigaTech/GigaTech/cliente';

// Formatea precios en formato uruguayo
function formatPrice(price) {
    if (typeof price !== 'number' || isNaN(price) || price <= 0) return '$0.00';
    return '$' + price.toLocaleString('es-UY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Variables globales
let producto = null;

// Carga detalle del producto por AJAX
function cargarDetalleProducto(idProducto) {
    const contenedor = document.getElementById('product-detail');
    if (!contenedor) {
        console.error('Contenedor de producto no encontrado');
        return;
    }

    fetch(BASE_API + '/php/obtener_detalle_producto.php?id=' + encodeURIComponent(idProducto))
        .then(respuesta => respuesta.json())
        .then(datos => {
            if (datos) {
                producto = datos;

                // Determinar límite de ingredientes basado en el tipo de producto
                let limite = 10;
                if (producto.tipo === 'pizza') {
                    limite = 4;
                } else if (producto.tipo === 'hamburguesa') {
                    limite = 8;
                }

                // Poblar información básica del producto
                const productNameEl = document.getElementById('product-name');
                if (productNameEl) productNameEl.textContent = producto.nombre || 'Producto no encontrado';

                const productDescEl = document.getElementById('product-description');
                if (productDescEl) productDescEl.textContent = producto.descripcion || '';

                const productPriceEl = document.getElementById('product-price');
                if (productPriceEl) productPriceEl.textContent = producto.precio_base > 0 ? formatPrice(producto.precio_base) : 'Precio a consultar';

                const addToCartEl = document.getElementById('add-to-cart');
                if (addToCartEl) addToCartEl.setAttribute('data-id', producto.id);

                // Resolver ruta de imagen
                let rutaImagen = producto.imagen || '../img/Pizzaconmigo.png';
                if (rutaImagen.startsWith('../empleado/productos/img/')) {
                    // Ya tiene la ruta correcta desde PHP
                } else if (rutaImagen.startsWith('prod_')) {
                    rutaImagen = '../empleado/productos/img/' + rutaImagen;
                } else if (!rutaImagen.startsWith('../')) {
                    rutaImagen = '../img/' + rutaImagen;
                }
                const productImageEl = document.getElementById('product-image');
                if (productImageEl) productImageEl.src = rutaImagen;

                // Agregar productos sugeridos: postres
                if (datos.postres && datos.postres.length > 0) {
                    const suggestedDessertsEl = document.getElementById('suggested-desserts');
                    if (suggestedDessertsEl) suggestedDessertsEl.style.display = 'block';
                    let htmlPostres = '';
                    datos.postres.forEach(postre => {
                        let rutaImagenPostre = postre.imagen || '../img/Pizzaconmigo.png';
                        if (rutaImagenPostre.startsWith('../empleado/productos/img/')) {
                            // Ya tiene la ruta correcta desde PHP
                        } else if (rutaImagenPostre.startsWith('prod_')) {
                            rutaImagenPostre = '../empleado/productos/img/' + rutaImagenPostre;
                        } else if (!rutaImagenPostre.startsWith('../')) {
                            rutaImagenPostre = '../img/' + rutaImagenPostre;
                        }
                        htmlPostres += `
                            <div class="suggested-item">
                                <img src="${rutaImagenPostre}" alt="${postre.nombre}" style="width:80px;height:auto;">
                                <p>${postre.nombre}</p>
                                <p>${formatPrice(postre.precio_base)}</p>
                                <div class="quantity-controls">
                                    <button class="qty-btn minus-btn" data-id="${postre.id_producto}" data-tipo="postre">-</button>
                                    <span class="qty-display" data-id="${postre.id_producto}" data-tipo="postre">0</span>
                                    <button class="qty-btn plus-btn" data-id="${postre.id_producto}" data-tipo="postre">+</button>
                                </div>
                            </div>
                        `;
                    });
                    const dessertsGridEl = document.getElementById('desserts-grid');
                    if (dessertsGridEl) dessertsGridEl.innerHTML = htmlPostres;
                }

                // Agregar productos sugeridos: bebidas
                if (datos.bebidas && datos.bebidas.length > 0) {
                    const suggestedDrinksEl = document.getElementById('suggested-drinks');
                    if (suggestedDrinksEl) suggestedDrinksEl.style.display = 'block';
                    let htmlBebidas = '';
                    datos.bebidas.forEach(bebida => {
                        let rutaImagenBebida = bebida.imagen || '../img/Pizzaconmigo.png';
                        if (rutaImagenBebida.startsWith('../empleado/productos/img/')) {
                            // Ya tiene la ruta correcta desde PHP
                        } else if (rutaImagenBebida.startsWith('prod_')) {
                            rutaImagenBebida = '../empleado/productos/img/' + rutaImagenBebida;
                        } else if (!rutaImagenBebida.startsWith('../')) {
                            rutaImagenBebida = '../img/' + rutaImagenBebida;
                        }
                        htmlBebidas += `
                            <div class="suggested-item">
                                <img src="${rutaImagenBebida}" alt="${bebida.nombre}" style="width:80px;height:auto;">
                                <p>${bebida.nombre}</p>
                                <p>${formatPrice(bebida.precio_base)}</p>
                                <div class="quantity-controls">
                                    <button class="qty-btn minus-btn" data-id="${bebida.id_producto}" data-tipo="bebida">-</button>
                                    <span class="qty-display" data-id="${bebida.id_producto}" data-tipo="bebida">0</span>
                                    <button class="qty-btn plus-btn" data-id="${bebida.id_producto}" data-tipo="bebida">+</button>
                                </div>
                            </div>
                        `;
                    });
                    const drinksGridEl = document.getElementById('drinks-grid');
                    if (drinksGridEl) drinksGridEl.innerHTML = htmlBebidas;
                }

                // Agregar event listeners para controles de cantidad
                const handleQuantityChange = (e) => {
                    // Prevent multiple triggering of the same event
                    if (e.target.classList.contains('qty-btn')) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const id = e.target.dataset.id || e.target.dataset.ingredientId;
                        const tipo = e.target.dataset.tipo;
                        const isPlus = e.target.classList.contains('plus-btn');

                        const qtyDisplay = document.querySelector(`.qty-display[data-id="${id}"][data-tipo="${tipo}"]`) ||
                                          document.querySelector(`.qty-display[data-ingredient-id="${id}"]`);

                        if (qtyDisplay) {
                            let currentQty = parseInt(qtyDisplay.textContent);

                            if (isPlus) {
                                // Check limits before increasing
                                if (tipo === 'postre' || tipo === 'bebida') {
                                    // Calculate total quantity for this type
                                    let totalQuantity = 0;
                                    document.querySelectorAll(`.qty-display[data-tipo="${tipo}"]`).forEach(display => {
                                        totalQuantity += parseInt(display.textContent) || 0;
                                    });
                                    
                                    if (totalQuantity >= 10) {
                                        alert(`Máximo 10 ${tipo === 'postre' ? 'postres' : 'bebidas'} en total permitidos`);
                                        return;
                                    }
                                    currentQty += 1; // Increment by 1 for desserts and drinks
                                } else if (e.target.dataset.ingredientId) {
                                    // Check ingredient limit
                                    let totalQuantity = 0;
                                    document.querySelectorAll('.ingredient-item').forEach(item => {
                                        const qtyDisp = item.querySelector('.qty-display');
                                        const qty = qtyDisp ? parseInt(qtyDisp.textContent) : 0;
                                        totalQuantity += qty;
                                    });

                                    if (totalQuantity >= limite) {
                                        alert(`Máximo ${limite} ingredientes permitidos en total`);
                                        return;
                                    }
                                    currentQty++; // Increment by 1 for ingredients
                                }
                            } else {
                                if (tipo === 'postre' || tipo === 'bebida') {
                                    if (currentQty > 0) {
                                        currentQty -= 1; // Decrement by 1 for desserts and drinks
                                    }
                                } else if (e.target.dataset.ingredientId) {
                                    if (currentQty > 0) {
                                        currentQty--; // Decrement by 1 for ingredients
                                    }
                                }
                            }
                            qtyDisplay.textContent = currentQty;

                            // Update ingredient count if it's an ingredient
                            if (e.target.dataset.ingredientId) {
                                updateIngredientCount();
                            }
                        }
                    }
                };

                // Add event listeners to dynamically created elements
                const addQuantityListeners = () => {
                    document.querySelectorAll('.qty-btn').forEach(btn => {
                        // Remove any existing event listeners to prevent duplicates
                        const clone = btn.cloneNode(true);
                        btn.parentNode.replaceChild(clone, btn);
                        clone.addEventListener('click', handleQuantityChange);
                    });
                };

                // Call it initially and after DOM updates
                addQuantityListeners();

                // Remove the document listener to prevent duplicate event handling
                // Add document listener for ingredients (since they're loaded after the initial setup)
                // document.addEventListener('click', handleQuantityChange);

                // Load ingredients if product allows them
                if (producto.ingredientes && producto.ingredientes.length > 0) {
                    const ingredientsSection = document.getElementById('ingredients-section');
                    const ingredientsList = document.getElementById('ingredients-list');
                    const ingredientControls = document.getElementById('ingredient-controls');
                    const ingredientCount = document.getElementById('ingredient-count');
                    const ingredientLimit = document.getElementById('ingredient-limit');

                    if (ingredientsSection && ingredientsList) {
                        ingredientsSection.style.display = 'block';
                        ingredientControls.style.display = 'block';

                        // Determine limit based on product type (already set above)
                        ingredientLimit.textContent = limite;

                        // Update ingredient limit display
                        const ingredientLimitText = document.getElementById('ingredient-limit-text');
                        if (ingredientLimitText) {
                            ingredientLimitText.textContent = `(máximo ${limite} ingredientes)`;
                        }

                        // Render ingredients
                        ingredientsList.innerHTML = producto.ingredientes.map(ing => `
                            <div class="ingredient-item" data-ingredient-id="${ing.id_ingrediente}" data-ingredient='${JSON.stringify(ing)}'>
                                <div class="ingredient-info">
                                    <span class="ingredient-name">${ing.nombre}</span>
                                    <span class="ingredient-price">(+$${ing.costo})</span>
                                </div>
                                <div class="quantity-controls">
                                    <button class="qty-btn minus-btn" data-ingredient-id="${ing.id_ingrediente}">-</button>
                                    <span class="qty-display" data-ingredient-id="${ing.id_ingrediente}">0</span>
                                    <button class="qty-btn plus-btn" data-ingredient-id="${ing.id_ingrediente}">+</button>
                                </div>
                            </div>
                        `).join('');

                        // Add event listeners to newly created ingredient buttons
                        document.querySelectorAll('.qty-btn').forEach(btn => {
                            btn.addEventListener('click', handleQuantityChange);
                        });

                        // Update ingredient count function
                        window.updateIngredientCount = function() {
                            let totalQuantity = 0;

                            document.querySelectorAll('.ingredient-item').forEach(item => {
                                const qtyDisplay = item.querySelector('.qty-display');
                                const quantity = qtyDisplay ? parseInt(qtyDisplay.textContent) : 0;
                                totalQuantity += quantity;
                            });

                            ingredientCount.textContent = totalQuantity;
                        };

                        // Force update count after rendering
                        updateIngredientCount();
                    }
                }

                // Agrega event listener al botón agregar al carrito
                const addToCartBtn = document.getElementById('add-to-cart');
                if (addToCartBtn) addToCartBtn.addEventListener('click', () => agregarAlCarrito(producto.id));
            } else {
                document.getElementById('product-detail').innerHTML = '<p>Producto no encontrado.</p>';
            }
        })
        .catch(error => {
            console.error('Error al cargar el detalle del producto:', error);
            document.getElementById('product-detail').innerHTML = '<p>No se pudo cargar el detalle del producto.</p>';
        });
}

// Carga detalle de la promoción por AJAX
function cargarDetallePromocion(idPromocion) {
    const contenedor = document.getElementById('product-detail');
    if (!contenedor) {
        console.error('Contenedor de producto no encontrado');
        return;
    }

    fetch(BASE_API + '/php/obtener_detalle_promocion.php?id=' + encodeURIComponent(idPromocion))
        .then(respuesta => respuesta.json())
        .then(datos => {
            if (datos && !datos.error) {
                // Poblar información básica de la promoción
                const productNameEl = document.getElementById('product-name');
                if (productNameEl) productNameEl.textContent = datos.nombre || 'Promoción no encontrada';

                const productDescEl = document.getElementById('product-description');
                if (productDescEl) productDescEl.textContent = datos.descripcion || '';

                const productPriceEl = document.getElementById('product-price');
                if (productPriceEl) productPriceEl.textContent = datos.precio > 0 ? formatPrice(parseFloat(datos.precio)) : 'Precio a consultar';

                // Resolver ruta de imagen
                let rutaImagen = datos.imagen || '../img/Pizzaconmigo.png';
                if (rutaImagen.startsWith('../empleado/promociones/img/')) {
                    // Ya tiene la ruta correcta desde PHP
                } else if (rutaImagen.startsWith('promo_')) {
                    rutaImagen = '../empleado/promociones/img/' + rutaImagen;
                } else if (!rutaImagen.startsWith('../')) {
                    rutaImagen = '../img/' + rutaImagen;
                }
                const productImageEl = document.getElementById('product-image');
                if (productImageEl) {
                    productImageEl.src = rutaImagen;
                }

                // Mostrar productos incluidos en la promoción
                if (datos.productos && datos.productos.length > 0) {
                    const ingredientsSection = document.getElementById('ingredients-section');
                    const ingredientsList = document.getElementById('ingredients-list');

                    if (ingredientsSection && ingredientsList) {
                        ingredientsSection.style.display = 'block';
                        document.getElementById('ingredients-section').querySelector('h2').textContent = 'Productos incluidos en la promoción';

                        ingredientsList.innerHTML = datos.productos.map(prod => `
                            <div class="ingredient-item" style="pointer-events: none; opacity: 0.8;">
                                <div class="ingredient-info">
                                    <span class="ingredient-name">${prod.nombre}</span>
                                    <span class="ingredient-price">x${prod.cantidad}</span>
                                </div>
                            </div>
                        `).join('');
                    }
                }

                // Agregar productos sugeridos: postres
                if (datos.postres && datos.postres.length > 0) {
                    const suggestedDessertsEl = document.getElementById('suggested-desserts');
                    if (suggestedDessertsEl) suggestedDessertsEl.style.display = 'block';
                    let htmlPostres = '';
                    datos.postres.forEach(postre => {
                        let rutaImagenPostre = postre.imagen || '../img/Pizzaconmigo.png';
                        if (rutaImagenPostre.startsWith('../empleado/productos/img/')) {
                            // Ya tiene la ruta correcta desde PHP
                        } else if (rutaImagenPostre.startsWith('prod_')) {
                            rutaImagenPostre = '../empleado/productos/img/' + rutaImagenPostre;
                        } else if (!rutaImagenPostre.startsWith('../')) {
                            rutaImagenPostre = '../img/' + rutaImagenPostre;
                        }
                        htmlPostres += `
                            <div class="suggested-item">
                                <img src="${rutaImagenPostre}" alt="${postre.nombre}" style="width:80px;height:auto;">
                                <p>${postre.nombre}</p>
                                <p>${formatPrice(postre.precio_base)}</p>
                                <div class="quantity-controls">
                                    <button class="qty-btn minus-btn" data-id="${postre.id_producto}" data-tipo="postre">-</button>
                                    <span class="qty-display" data-id="${postre.id_producto}" data-tipo="postre">0</span>
                                    <button class="qty-btn plus-btn" data-id="${postre.id_producto}" data-tipo="postre">+</button>
                                </div>
                            </div>
                        `;
                    });
                    const dessertsGridEl = document.getElementById('desserts-grid');
                    if (dessertsGridEl) dessertsGridEl.innerHTML = htmlPostres;
                }

                // Agregar productos sugeridos: bebidas
                if (datos.bebidas && datos.bebidas.length > 0) {
                    const suggestedDrinksEl = document.getElementById('suggested-drinks');
                    if (suggestedDrinksEl) suggestedDrinksEl.style.display = 'block';
                    let htmlBebidas = '';
                    datos.bebidas.forEach(bebida => {
                        let rutaImagenBebida = bebida.imagen || '../img/Pizzaconmigo.png';
                        if (rutaImagenBebida.startsWith('../empleado/productos/img/')) {
                            // Ya tiene la ruta correcta desde PHP
                        } else if (rutaImagenBebida.startsWith('prod_')) {
                            rutaImagenBebida = '../empleado/productos/img/' + rutaImagenBebida;
                        } else if (!rutaImagenBebida.startsWith('../')) {
                            rutaImagenBebida = '../img/' + rutaImagenBebida;
                        }
                        htmlBebidas += `
                            <div class="suggested-item">
                                <img src="${rutaImagenBebida}" alt="${bebida.nombre}" style="width:80px;height:auto;">
                                <p>${bebida.nombre}</p>
                                <p>${formatPrice(bebida.precio_base)}</p>
                                <div class="quantity-controls">
                                    <button class="qty-btn minus-btn" data-id="${bebida.id_producto}" data-tipo="bebida">-</button>
                                    <span class="qty-display" data-id="${bebida.id_producto}" data-tipo="bebida">0</span>
                                    <button class="qty-btn plus-btn" data-id="${bebida.id_producto}" data-tipo="bebida">+</button>
                                </div>
                            </div>
                        `;
                    });
                    const drinksGridEl = document.getElementById('drinks-grid');
                    if (drinksGridEl) drinksGridEl.innerHTML = htmlBebidas;
                }

                // Agregar event listeners para controles de cantidad (same as product)
                const handleQuantityChange = (e) => {
                    // Prevent multiple triggering of the same event
                    if (e.target.classList.contains('qty-btn')) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const id = e.target.dataset.id || e.target.dataset.ingredientId;
                        const tipo = e.target.dataset.tipo;
                        const isPlus = e.target.classList.contains('plus-btn');

                        const qtyDisplay = document.querySelector(`.qty-display[data-id="${id}"][data-tipo="${tipo}"]`) ||
                                          document.querySelector(`.qty-display[data-ingredient-id="${id}"]`);

                        if (qtyDisplay) {
                            let currentQty = parseInt(qtyDisplay.textContent);

                            if (isPlus) {
                                // Check limits before increasing
                                if (tipo === 'postre' || tipo === 'bebida') {
                                    // Calculate total quantity for this type
                                    let totalQuantity = 0;
                                    document.querySelectorAll(`.qty-display[data-tipo="${tipo}"]`).forEach(display => {
                                        totalQuantity += parseInt(display.textContent) || 0;
                                    });
                                    
                                    if (totalQuantity >= 10) {
                                        alert(`Máximo 10 ${tipo === 'postre' ? 'postres' : 'bebidas'} en total permitidos`);
                                        return;
                                    }
                                    currentQty += 1; // Increment by 1 for desserts and drinks
                                }
                            } else {
                                if (tipo === 'postre' || tipo === 'bebida') {
                                    if (currentQty > 0) {
                                        currentQty -= 1; // Decrement by 1 for desserts and drinks
                                    }
                                }
                            }
                            qtyDisplay.textContent = currentQty;
                        }
                    }
                };

                // Add event listeners to dynamically created elements
                const addQuantityListeners = () => {
                    document.querySelectorAll('.qty-btn').forEach(btn => {
                        // Remove any existing event listeners to prevent duplicates
                        const clone = btn.cloneNode(true);
                        btn.parentNode.replaceChild(clone, btn);
                        clone.addEventListener('click', handleQuantityChange);
                    });
                };

                // Call it initially and after DOM updates
                addQuantityListeners();

                // Remove the document listener to prevent duplicate event handling
                // Add document listener for ingredients (since they're loaded after the initial setup)
                // document.addEventListener('click', handleQuantityChange);

                // Configurar botón agregar al carrito para promoción
                const addToCartBtn = document.getElementById('add-to-cart');
                if (addToCartBtn) {
                    addToCartBtn.setAttribute('data-promo-id', datos.id_promocion);
                    addToCartBtn.addEventListener('click', () => agregarPromocionAlCarrito(datos.id_promocion));
                }
            } else {
                document.getElementById('product-detail').innerHTML = '<p>Promoción no encontrada.</p>';
            }
        })
        .catch(error => {
            console.error('Error al cargar el detalle de la promoción:', error);
            document.getElementById('product-detail').innerHTML = '<p>No se pudo cargar el detalle de la promoción.</p>';
        });
}

// Agrega promoción al carrito
function agregarPromocionAlCarrito(idPromocion) {
    // Recopilar postres seleccionados
    const selectedDesserts = [];
    document.querySelectorAll('.suggested-item').forEach(item => {
        const qtyDisplay = item.querySelector('.qty-display');
        const cantidad = qtyDisplay ? parseInt(qtyDisplay.textContent) : 0;
        const tipo = item.querySelector('.qty-btn').dataset.tipo;

        if (tipo === 'postre' && cantidad > 0) {
            const id = item.querySelector('.qty-btn').dataset.id;
            selectedDesserts.push({
                id_producto: parseInt(id),
                cantidad: cantidad
            });
        }
    });

    // Recopilar bebidas seleccionadas
    const selectedDrinks = [];
    document.querySelectorAll('.suggested-item').forEach(item => {
        const qtyDisplay = item.querySelector('.qty-display');
        const cantidad = qtyDisplay ? parseInt(qtyDisplay.textContent) : 0;
        const tipo = item.querySelector('.qty-btn').dataset.tipo;

        if (tipo === 'bebida' && cantidad > 0) {
            const id = item.querySelector('.qty-btn').dataset.id;
            selectedDrinks.push({
                id_producto: parseInt(id),
                cantidad: cantidad
            });
        }
    });

    const formData = new FormData();
    formData.append('action', 'add_promo');
    formData.append('id_promocion', idPromocion);
    formData.append('cantidad', 1);
    if (selectedDesserts.length > 0) {
        formData.append('postres', JSON.stringify(selectedDesserts));
    }
    if (selectedDrinks.length > 0) {
        formData.append('bebidas', JSON.stringify(selectedDrinks));
    }

    fetch(BASE_API + '/php/obtener_carrito.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.text().then(text => {
            try {
                const data = JSON.parse(text);
                return data;
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response: ' + text);
            }
        });
    })
    .then(data => {
        if (data.success) {
            // Update cart count in UI
            const cartToggle = document.getElementById('cartToggle');
            if (cartToggle) {
                cartToggle.textContent = `Carrito (${data.cartCount})`;
            }

            // Reset all quantity displays to 0
            resetAllQuantities();
        } else {
            console.error('Error en respuesta:', data.error);
            alert('❌ Error: ' + (data.error || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        alert('❌ Error de conexión: ' + error.message);
    });
}

// Agrega al carrito (producto principal + complementos seleccionados)
function agregarAlCarrito(idProducto) {
    // Recopilar ingredientes seleccionados
    const selectedIngredients = [];
    document.querySelectorAll('.ingredient-item').forEach(item => {
        const qtyDisplay = item.querySelector('.qty-display');
        const cantidad = qtyDisplay ? parseInt(qtyDisplay.textContent) : 0;

        if (cantidad > 0) {
            const ingredientData = JSON.parse(item.dataset.ingredient);
            selectedIngredients.push({
                id_ingrediente: ingredientData.id_ingrediente,
                nombre: ingredientData.nombre,
                precio: parseFloat(ingredientData.costo),
                cantidad: cantidad
            });
        }
    });

    // Recopilar postres seleccionados
    const selectedDesserts = [];
    document.querySelectorAll('.suggested-item').forEach(item => {
        const qtyDisplay = item.querySelector('.qty-display');
        const cantidad = qtyDisplay ? parseInt(qtyDisplay.textContent) : 0;
        const tipo = item.querySelector('.qty-btn').dataset.tipo;

        if (tipo === 'postre' && cantidad > 0) {
            const id = item.querySelector('.qty-btn').dataset.id;
            selectedDesserts.push({
                id_producto: parseInt(id),
                cantidad: cantidad
            });
        }
    });

    // Recopilar bebidas seleccionadas
    const selectedDrinks = [];
    document.querySelectorAll('.suggested-item').forEach(item => {
        const qtyDisplay = item.querySelector('.qty-display');
        const cantidad = qtyDisplay ? parseInt(qtyDisplay.textContent) : 0;
        const tipo = item.querySelector('.qty-btn').dataset.tipo;

        if (tipo === 'bebida' && cantidad > 0) {
            const id = item.querySelector('.qty-btn').dataset.id;
            selectedDrinks.push({
                id_producto: parseInt(id),
                cantidad: cantidad
            });
        }
    });

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('id_producto', idProducto);
    formData.append('cantidad', 1);
    if (selectedIngredients.length > 0) {
        formData.append('ingredientes', JSON.stringify(selectedIngredients));
    }
    if (selectedDesserts.length > 0) {
        formData.append('postres', JSON.stringify(selectedDesserts));
    }
    if (selectedDrinks.length > 0) {
        formData.append('bebidas', JSON.stringify(selectedDrinks));
    }

    fetch(BASE_API + '/php/obtener_carrito.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.text().then(text => {
            try {
                const data = JSON.parse(text);
                return data;
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response: ' + text);
            }
        });
    })
    .then(data => {
        if (data.success) {
            // Update cart count in UI
            const cartToggle = document.getElementById('cartToggle');
            if (cartToggle) {
                cartToggle.textContent = `Carrito (${data.cartCount})`;
            }

            // Reset all quantity displays to 0
            resetAllQuantities();
        } else {
            console.error('Error en respuesta:', data.error);
            alert('❌ Error: ' + (data.error || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        alert('❌ Error de conexión: ' + error.message);
    });
}

// Función para resetear todas las cantidades a 0
function resetAllQuantities() {
    // Restablecer cantidades de ingredientes
    document.querySelectorAll('.ingredient-item .qty-display').forEach(display => {
        display.textContent = '0';
    });

    // Restablecer cantidades de postres
    document.querySelectorAll('.suggested-item .qty-display[data-tipo="postre"]').forEach(display => {
        display.textContent = '0';
    });

    // Restablecer cantidades de bebidas
    document.querySelectorAll('.suggested-item .qty-display[data-tipo="bebida"]').forEach(display => {
        display.textContent = '0';
    });

    // Actualizar visualización del conteo de ingredientes si existe
    const ingredientCount = document.getElementById('ingredient-count');
    if (ingredientCount) {
        ingredientCount.textContent = '0';
    }
}

// Inicializa al cargar DOM
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const idProducto = urlParams.get('id');
    const idPromocion = urlParams.get('promo');

    if (idPromocion) {
        cargarDetallePromocion(idPromocion);
    } else if (idProducto) {
        cargarDetalleProducto(idProducto);
    } else {
        console.error('No product ID or promotion ID in URL');
    }
});