// checkout.js - Manejo AJAX para el proceso de pago

class CheckoutManager {
    constructor() {
        this.csrfToken = document.querySelector('input[name="csrf_token"]').value;
        this.init();
    }

    init() {
        this.loadCart();
        this.setupFormValidation();
        this.setupPaymentMethodHandling();
        this.setupFileUpload();
    }

    setupPaymentMethodHandling() {
        // Este método se llama en init, pero la implementación está en handlePaymentMethod
        // Quizás es un marcador de posición, o podemos llamar a handlePaymentMethod aquí si es necesario
    }

    async loadCart() {
        try {
            const response = await fetch('php/obtener_carrito.php');
            const cartData = await response.json();

            const cartItemsEl = document.getElementById('cartItems');
            const cartTotalEl = document.getElementById('cartTotal');
            const transferAmountEl = document.getElementById('transferAmount');

            cartItemsEl.innerHTML = '';
            let total = 0;

            if (cartData.items && cartData.items.length > 0) {
                this.cartItemCount = cartData.items.length;
                cartData.items.forEach(item => {
                    const itemEl = document.createElement('div');
                    itemEl.className = 'cart-item';
                    itemEl.innerHTML = `
                        <div>
                            <img src="${item.imagen || 'img/Pizzaconmigo.png'}" alt="${item.nombre}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 10px;">
                            <strong>${item.nombre}</strong>
                            ${item.descripcion ? '<br><small>' + item.descripcion + '</small>' : ''}
                            ${item.ingredientes && item.ingredientes.length ? '<br><small>Ingredientes: ' + item.ingredientes.map(ing => ing.nombre + ' (x' + ing.cantidad + ')').join(', ') + '</small>' : ''}
                            <br><small>Cantidad: ${item.cantidad}</small>
                        </div>
                        <div>${item.subtotal}</div>
                    `;
                    cartItemsEl.appendChild(itemEl);
                    total += parseFloat(item.subtotal);
                });
            } else {
                this.cartItemCount = 0;
            }

            cartTotalEl.textContent = `Total: $${total.toFixed(2)}`;
            transferAmountEl.textContent = `$${total.toFixed(2)}`;

        } catch (error) {
            console.error('Error al cargar el carrito:', error);
            this.showError('Error al cargar el carrito');
        }
    }

    setupFormValidation() {
        const inputs = ['nombre', 'telefono', 'direccion'];
        inputs.forEach(id => {
            document.getElementById(id).addEventListener('input', () => this.validateForm());
        });

        // Permitir solo dígitos para el teléfono
        document.getElementById('telefono').addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '');
        });

        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', () => {
                this.handlePaymentMethod();
                this.validateForm();
            });
        });

        document.querySelectorAll('input[name="customer[tipo_entrega]"]').forEach(radio => {
            radio.addEventListener('change', () => {
                this.validateForm();
            });
        });

        this.uploadSuccessful = false;
        this.cartItemCount = 0;
    }

    validateForm() {
        const nombre = document.getElementById('nombre').value.trim();
        const telefono = document.getElementById('telefono').value.trim();
        const direccion = document.getElementById('direccion').value.trim();
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        const tipoEntrega = document.querySelector('input[name="customer[tipo_entrega]"]:checked');

        let errors = [];

        // Nombre: no vacío
        if (!nombre) {
            errors.push('El nombre es obligatorio.');
        }

        // Teléfono: debe empezar con 09 y tener 9 dígitos
        if (!telefono || !/^09\d{7}$/.test(telefono)) {
            errors.push('El teléfono debe tener 9 dígitos empezando con 09.');
        }

        // Tipo_entrega: debe estar seleccionado
        if (!tipoEntrega) {
            errors.push('Debe seleccionar un tipo de entrega.');
        }

        // Dirección: siempre requerida
        if (!direccion) {
            errors.push('La dirección es obligatoria.');
        }

        // Carrito: debe contener al menos 1 artículo
        if (this.cartItemCount < 1) {
            errors.push('El carrito debe contener al menos un artículo.');
        }

        // Método de pago: selección requerida
        if (!paymentMethod) {
            errors.push('Debe seleccionar un método de pago.');
        }

        // Si el método es transferencia: el comprobante debe cargarse y la carga debe ser exitosa
        if (paymentMethod && paymentMethod.value === 'transferencia') {
            if (!this.selectedFile) {
                errors.push('Debe subir un comprobante de pago para transferencia.');
            } else if (!this.uploadSuccessful) {
                errors.push('El comprobante debe subirse exitosamente antes de enviar.');
            }
        }

        const errorMessage = document.getElementById('errorMessage');
        if (errors.length > 0) {
            errorMessage.textContent = errors.join(' ');
            errorMessage.style.display = 'block';
            document.getElementById('submitBtn').disabled = true;
            return false;
        } else {
            errorMessage.style.display = 'none';
            document.getElementById('submitBtn').disabled = false;
            return true;
        }
    }

    handleDeliveryType() {
        const delivery = document.getElementById('delivery');
        const direccionGroup = document.getElementById('direccion').closest('.form-group');

        if (delivery.checked) {
            direccionGroup.style.display = 'block';
            document.getElementById('direccion').required = true;
        } else {
            direccionGroup.style.display = 'none';
            document.getElementById('direccion').required = false;
        }
    }

    handlePaymentMethod() {
        const transferencia = document.getElementById('transferencia');
        const bankInfo = document.getElementById('bankInfo');
        const uploadSection = document.getElementById('uploadSection');

        if (transferencia.checked) {
            bankInfo.style.display = 'block';
            uploadSection.style.display = 'block';
        } else {
            bankInfo.style.display = 'none';
            uploadSection.style.display = 'none';
        }
    }

    setupFileUpload() {
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('paymentProof');
        const preview = document.getElementById('uploadPreview');
        const previewImage = document.getElementById('previewImage');
        const fileName = document.getElementById('fileName');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');

        uploadArea.addEventListener('click', () => fileInput.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.handleFile(files[0], preview, previewImage, fileName, progressBar, progressFill);
            }
        });

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                this.handleFile(file, preview, previewImage, fileName, progressBar, progressFill);
            }
        });
    }

    handleFile(file, preview, previewImage, fileName, progressBar, progressFill) {
        if (!file.type.startsWith('image/') && file.type !== 'application/pdf') {
            alert('Por favor, selecciona una imagen o archivo PDF.');
            return;
        }

        if (file.size > 5 * 1024 * 1024) { // Límite de 5MB
            alert('El archivo es demasiado grande. Máximo 5MB.');
            return;
        }

        fileName.textContent = file.name;
        preview.style.display = 'block';

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewImage.style.display = 'none';
        }

        // Almacenar archivo para cargarlo más tarde
        this.selectedFile = file;
    }

    async uploadFile() {
        if (!this.selectedFile) return null;

        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('comprobante_pago', this.selectedFile);

        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');

        progressBar.style.display = 'block';
        progressFill.style.width = '0%';

        try {
            const response = await fetch('../subir_comprobante.php', {
                method: 'POST',
                body: formData,
                // Nota: Se necesitaría XMLHttpRequest para el progreso de carga, pero fetch no lo soporta directamente
                // Por ahora, simularemos el progreso
            });

            // Simular progreso
            let progress = 0;
            const interval = setInterval(() => {
                progress += 10;
                progressFill.style.width = progress + '%';
                if (progress >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        progressBar.style.display = 'none';
                    }, 1000);
                }
            }, 100);

            const result = await response.json();

            if (!result.ok) {
                throw new Error(result.error || 'Error al subir el archivo');
            }

            this.uploadSuccessful = true;
            this.validateForm(); // Revalidar después de una carga exitosa
            return result.upload_id;
        } catch (error) {
            console.error('Error de carga:', error);
            progressBar.style.display = 'none';
            this.uploadSuccessful = false;
            this.validateForm(); // Revalidar en caso de error
            throw error;
        }
    }


    async submitOrder() {
        if (!this.validateForm()) {
            return;
        }

        const submitBtn = document.getElementById('submitBtn');
        const spinner = document.getElementById('spinner');
        const errorMessage = document.getElementById('errorMessage');
        const checkoutForm = document.getElementById('orderForm');
        const successMessage = document.getElementById('successMessage');

        submitBtn.disabled = true;
        spinner.style.display = 'inline-block';
        errorMessage.style.display = 'none';

        try {
            // Cargar archivo si se seleccionó
            let uploadId = null;
            if (this.selectedFile) {
                uploadId = await this.uploadFile();
            }

            // Preparar datos del pedido
            const formData = new FormData(document.getElementById('orderForm'));
            const orderData = {
                csrf_token: this.csrfToken,
                cliente: {
                    nombre: formData.get('customer[nombre]'),
                    email: formData.get('customer[email]') || '',
                    telefono: formData.get('customer[telefono]'),
                    direccion: formData.get('customer[direccion]'),
                    notas: formData.get('customer[notas]') || '',
                    tipo_entrega: formData.get('customer[tipo_entrega]')
                },
                metodo_pago: formData.get('payment_method'),
                upload_id: uploadId
            };

            const response = await fetch('../enviar_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();

            if (result.ok) {
                // Guardar pedido en localStorage para "Mis pedidos"
                const pedidos = JSON.parse(localStorage.getItem('pizzaconmigo_pedidos') || '[]');
                pedidos.push({
                    id_pedido: result.order_id,
                    fecha: new Date().toISOString(),
                    total: result.total,
                    estado: result.status,
                    telefono_cliente: orderData.cliente.telefono,
                    nombre_cliente: orderData.cliente.nombre
                });
                localStorage.setItem('pizzaconmigo_pedidos', JSON.stringify(pedidos));

                // Limpiar carrito local
                localStorage.removeItem('carrito');

                // Redirigir a la página de espera
                window.location.href = `?id_pedido=${result.order_id}`;
            } else {
                throw new Error(result.message || 'Error al procesar el pedido');
            }
        } catch (error) {
            console.error('Error al enviar el pedido:', error);
            errorMessage.textContent = error.message || 'Error de conexión. Por favor, inténtalo de nuevo.';
            errorMessage.style.display = 'block';
            submitBtn.disabled = false;
            spinner.style.display = 'none';
        }
    }

    showError(message) {
        const errorMessage = document.getElementById('errorMessage');
        errorMessage.textContent = message;
        errorMessage.style.display = 'block';
    }
}

// Inicializar cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', () => {
    window.checkoutManager = new CheckoutManager();
});