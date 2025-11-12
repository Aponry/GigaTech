<?php
// Iniciar sesión para carrito y CSRF
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Generar token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verificar si el carrito está vacío o si se está viendo un pedido confirmado
$id_pedido = isset($_GET['id_pedido']) ? intval($_GET['id_pedido']) : 0;
$pedido_confirmado = false;
$pedido_pendiente = false;

if ($id_pedido > 0) {
    // Viendo estado del pedido
    require_once '../conexion.php';
    $sql = "SELECT estado FROM pedido WHERE id_pedido = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $id_pedido);
    $stmt->execute();
    $result = $stmt->get_result();
    $pedido = $result->fetch_assoc();
    $stmt->close();
    if ($pedido) {
        if ($pedido['estado'] == 'confirmado') {
            $pedido_confirmado = true;
        } elseif ($pedido['estado'] == 'pendiente') {
            $pedido_pendiente = true;
            // Obtener detalles completos del pedido
            $sql_pedido = "SELECT * FROM pedido WHERE id_pedido = ?";
            $stmt_pedido = $conexion->prepare($sql_pedido);
            $stmt_pedido->bind_param('i', $id_pedido);
            $stmt_pedido->execute();
            $pedido_data = $stmt_pedido->get_result()->fetch_assoc();
            $stmt_pedido->close();

            $sql_detalles = "SELECT dp.*, p.nombre as nombre_producto, pr.nombre as nombre_promocion, p.descripcion as descripcion_producto, pr.descripcion as descripcion_promocion, p.imagen as imagen_producto, pr.imagen as imagen_promocion
                             FROM detalle_pedido dp
                             LEFT JOIN productos p ON dp.id_producto = p.id_producto
                             LEFT JOIN promocion pr ON dp.id_promocion = pr.id_promocion
                             WHERE dp.id_pedido = ?";
            $stmt_detalles = $conexion->prepare($sql_detalles);
            $stmt_detalles->bind_param('i', $id_pedido);
            $stmt_detalles->execute();
            $detalles = $stmt_detalles->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_detalles->close();
        }
    } else {
        error_log("No pedido found for id_pedido: $id_pedido");
    }
} elseif (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:;">
    <title>Finalizar Pedido - PizzaConmigo</title>
    <link rel="icon" href="../img/PizzaConmigo.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php $vCss = file_exists('../css/modal.css') ? filemtime('../css/modal.css') : time(); ?>
    <link rel="stylesheet" href="../css/modal.css?v=<?= $vCss ?>">
    <?php $vCartCss = file_exists('css/carrito.css') ? filemtime('css/carrito.css') : time(); ?>
    <link rel="stylesheet" href="css/carrito.css?v=<?= $vCartCss ?>">
    <?php $vCheckoutCss = file_exists('css/checkout.css') ? filemtime('css/checkout.css') : time(); ?>
    <link rel="stylesheet" href="css/checkout.css?v=<?= $vCheckoutCss ?>">
</head>
<body>

    <main class="checkout-container fade-in">
        <?php if ($pedido_confirmado): ?>
            <h1 style="text-align: center; color: var(--primary); margin-bottom: 2rem;">Pedido Confirmado</h1>
            <p style="text-align: center;">Su pedido ha sido confirmado. Puede descargar el recibo a continuación.</p>
            <script src="js/pagar_inline.js"></script>
        <?php elseif ($pedido_pendiente): ?>
            <h1 style="text-align: center; color: var(--primary); margin-bottom: 2rem;">Esperando Confirmación</h1>
            <div id="waiting-screen" style="padding: 2rem;">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⏳</div>
                    <p style="font-size: 1.2rem; margin-bottom: 1rem;">Su pedido está siendo procesado.</p>
                    <p>ID del Pedido: <strong><?php echo $id_pedido; ?></strong></p>
                    <p>Estado: <strong id="order-status">Pendiente</strong></p>
                    <p>Por favor, espere mientras confirmamos su pedido...</p>
                </div>
                <div class="order-info" style="background: #f9f9f9; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                    <h3 style="color: black;">Información del Pedido</h3>
                    <div class="order-details">
                        <div class="order-detail">
                            <strong>ID Pedido</strong>
                            <span>#<?php echo $pedido_data['id_pedido']; ?></span>
                        </div>
                        <div class="order-detail">
                            <strong>Fecha</strong>
                            <span><?php echo date('d/m/Y H:i', strtotime($pedido_data['fecha'])); ?></span>
                        </div>
                        <div class="order-detail">
                            <strong>Cliente</strong>
                            <span><?php echo htmlspecialchars($pedido_data['nombre_cliente']); ?></span>
                        </div>
                        <div class="order-detail">
                            <strong>Teléfono</strong>
                            <span><?php echo htmlspecialchars($pedido_data['telefono_cliente']); ?></span>
                        </div>
                        <div class="order-detail">
                            <strong>Tipo</strong>
                            <span><?php echo $pedido_data['tipo_pedido'] === 'delivery' ? 'Delivery' : 'Retiro en Local'; ?></span>
                        </div>
                        <div class="order-detail">
                            <strong>Método de Pago</strong>
                            <span><?php echo htmlspecialchars($pedido_data['metodo_pago']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="order-items">
                    <h3 style="color: black;">Detalles del Pedido</h3>
                    <?php foreach ($detalles as $detalle): ?>
                        <div class="order-item" style="display: flex; align-items: center; padding: 15px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 10px; background: #f9f9f9;">
                            <img src="<?php echo htmlspecialchars($detalle['imagen_producto'] ?: $detalle['imagen_promocion'] ?: '../img/Pizzaconmigo.png'); ?>" alt="<?php echo htmlspecialchars($detalle['nombre_producto'] ?: $detalle['nombre_promocion']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; margin-right: 15px;">
                            <div style="flex: 1;">
                                <div style="font-weight: bold; margin-bottom: 5px;"><?php echo htmlspecialchars($detalle['nombre_producto'] ?: $detalle['nombre_promocion']); ?></div>
                                <div style="color: #666; font-size: 0.9rem; margin-bottom: 5px;"><?php echo htmlspecialchars($detalle['descripcion_producto'] ?: $detalle['descripcion_promocion']); ?></div>
                                <div style="font-size: 0.9rem;">
                                    <span>Cantidad: <?php echo $detalle['cantidad']; ?></span> |
                                    <span>Precio unitario: $<?php echo number_format($detalle['precio_unitario'], 2); ?></span>
                                </div>
                            </div>
                            <div style="font-weight: bold; color: #333;">$<?php echo number_format($detalle['subtotal'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                    <div class="order-total" style="text-align: right; font-size: 1.2rem; font-weight: bold; margin-top: 20px; padding: 10px; background: #333333; border-radius: 6px; color: white;">
                        Total: $<?php echo number_format($pedido_data['total'], 2); ?>
                    </div>
                </div>
            </div>
            <div id="confirmed-screen" style="display: none; text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
                <p style="font-size: 1.2rem; margin-bottom: 1rem;">¡Su pedido ha sido confirmado!</p>
                <p>Puede descargar el recibo a continuación.</p>
            </div>
            <div id="cancelled-screen" style="display: none; text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">❌</div>
                <p style="font-size: 1.2rem; margin-bottom: 1rem;">Su pedido ha sido cancelado.</p>
                <p>Por favor, contacte con nosotros si tiene alguna pregunta.</p>
            </div>
        <?php else: ?>
            <h1 style="text-align: center; color: var(--primary); margin-bottom: 2rem;">Finalizar Pedido</h1>

            <div id="errorMessage" class="error-message"></div>

            <form class="checkout-form" id="orderForm" onsubmit="return false">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Resumen Carrito -->
            <section class="card form-section">
                <h3>Resumen del Carrito</h3>
                <div id="cartItems">
                    <!-- Cart items will be loaded here -->
                </div>
                <div id="cartTotal" style="font-weight: bold; font-size: 1.25rem; color: var(--primary); text-align: right; margin-top: 1rem;">Total: $0</div>
            </section>

            <!-- Contacto -->
            <section class="card form-section">
                <h3>Contacto</h3>
                <div class="form-group">
                    <label for="nombre">Nombre completo *</label>
                    <input type="text" id="nombre" name="customer[nombre]" required>
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono *</label>
                    <input type="tel" id="telefono" name="customer[telefono]" placeholder="09-2006627" required>
                </div>
                <div class="form-group">
                    <label for="email">Email (opcional)</label>
                    <input type="email" id="email" name="customer[email]">
                </div>
            </section>

            <!-- Tipo de Entrega -->
            <section class="card form-section">
                <h3>Tipo de Entrega</h3>
                <div class="form-group">
                    <div class="payment-methods">
                        <div class="payment-method">
                            <input type="radio" id="delivery" name="customer[tipo_entrega]" value="delivery" required>
                            <label for="delivery">
                                Delivery
                            </label>
                        </div>
                        <div class="payment-method">
                            <input type="radio" id="retiro" name="customer[tipo_entrega]" value="retiro" required>
                            <label for="retiro">
                                Retiro en Local
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Dirección -->
            <section class="card form-section" id="direccionSection">
                <h3>Dirección de Entrega</h3>
                <div class="form-group">
                    <label for="direccion">Dirección *</label>
                    <input type="text" id="direccion" name="customer[direccion]" required>
                </div>
            </section>

            <!-- Comentarios -->
            <section class="card form-section">
                <h3>Comentarios</h3>
                <div class="form-group">
                    <label for="notas">Comentarios (opcional)</label>
                    <textarea id="notas" name="customer[notas]" placeholder="Instrucciones especiales, alergias, etc."></textarea>
                </div>
            </section>

            <!-- Método de Pago -->
            <section class="card form-section">
                <h3>Método de Pago</h3>
                <div class="payment-methods">
                    <div class="payment-method">
                        <input type="radio" id="transferencia" name="payment_method" value="transferencia" required>
                        <label for="transferencia">
                            Transferencia
                        </label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" id="pos" name="payment_method" value="tarjeta" required>
                        <label for="pos">
                            POS/Tarjeta
                        </label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" id="efectivo" name="payment_method" value="efectivo" required>
                        <label for="efectivo">
                            Efectivo
                        </label>
                    </div>
                </div>

                <!-- Bloque Transferencia -->
                <div id="bankInfo" class="bank-info">
                    <h4>Datos Bancarios</h4>
                    <p><strong>Banco:</strong> Banco República</p>
                    <p><strong>Cuenta:</strong> 123456789</p>
                    <p><strong>Titular:</strong> PizzaConmigo S.A.</p>
                    <p><strong>RUT:</strong> 123456789012</p>
                    <p><strong>Monto:</strong> <span id="transferAmount">$0</span></p>
                    <p>Por favor, envíe el comprobante de pago:</p>

                    <div id="uploadSection" class="upload-section">
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt fa-2x"></i>
                            <p>Arrastra y suelta el comprobante aquí o haz clic para seleccionar</p>
                            <input type="file" id="paymentProof" name="payment_proof" accept="image/*,.pdf" class="upload-input">
                        </div>
                        <div class="upload-preview" id="uploadPreview">
                            <img id="previewImage" class="preview-image" alt="Vista previa">
                            <p id="fileName"></p>
                        </div>
                        <div class="progress-bar" id="progressBar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                    </div>
                </div>
            </section>

            <button type="button" id="submitBtn" class="btn btn-primary" disabled onclick="window.checkoutManager.submitOrder()">
                <span class="spinner" id="spinner"></span>
                Pagar y enviar pedido
            </button>
        </form>

        <!-- Success Message -->
        <div id="successMessage" style="display: none; text-align: center; padding: 2rem; background: #d4edda; color: #155724; border-radius: 8px; margin-top: 2rem;">
            <h2>¡Pedido Enviado Exitosamente!</h2>
            <p>Gracias por tu compra. Tu pedido ha sido procesado.</p>
            <p><strong>ID del Pedido:</strong> <span id="orderId"></span></p>
            <p>Puedes hacer seguimiento de tu pedido contactándonos.</p>
        </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 2rem;">
            <?php if ($pedido_confirmado): ?>
                <a href="../index.php" class="btn btn-secondary" style="margin-right: 1rem;">Volver al Inicio</a>
                <a href="generar_receta_pdf.php?id_pedido=<?= $id_pedido ?>" class="btn btn-primary">Descargar Recibo (PDF)</a>
            <?php elseif ($pedido_pendiente): ?>
                <div id="pending-buttons" style="display: none;">
                    <a href="../index.php" class="btn btn-secondary" style="margin-right: 1rem; display: inline-block; width: auto;">Volver al Inicio</a>
                    <a href="generar_receta_pdf.php?id_pedido=<?= $id_pedido ?>" class="btn btn-primary" style="display: inline-block; width: auto;">Descargar Recibo (PDF)</a>
                </div>
            <?php else: ?>
                <a href="../index.php" class="btn btn-secondary">Volver al Inicio</a>
            <?php endif; ?>
        </div>
    </main>

    <?php if ($pedido_pendiente): ?>
    <script src="js/pagar_polling.js"></script>
    <?php else: ?>
    <script src="js/checkout.js"></script>
    <?php endif; ?>
</body>
</html>