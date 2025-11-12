<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir conexión a la base de datos
require_once __DIR__ . '/../conexion.php';
$db = $conexion ?? null;

// Obtener el ID del pedido desde la URL
$id_pedido = isset($_GET['id_pedido']) ? intval($_GET['id_pedido']) : 0;

if ($id_pedido <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener los detalles del pedido
$pedido = null;
if ($db) {
    $sql = "SELECT * FROM pedido WHERE id_pedido = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id_pedido);
    $stmt->execute();
    $result = $stmt->get_result();
    $pedido = $result->fetch_assoc();
    $stmt->close();
    
    if (!$pedido) {
        header('Location: index.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Confirmación de Pedido - PizzaConmigo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="../img/logo-removebg-preview.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php $vConfirmacionCss = file_exists('css/confirmacion.css') ? filemtime('css/confirmacion.css') : time(); ?>
    <link rel="stylesheet" href="css/confirmacion.css?v=<?= $vConfirmacionCss ?>">
</head>

<body>
    <div class="container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>¡Pedido Confirmado!</h1>
        <p>Gracias por tu compra. Tu pedido ha sido procesado exitosamente.</p>

        <?php if ($pedido): ?>
            <div class="customer-details">
                <h3>Datos del Cliente</h3>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($pedido['nombre_cliente'] ?? ''); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($pedido['email_cliente'] ?? ''); ?></p>
                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($pedido['telefono_cliente'] ?? ''); ?></p>
                <p><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido['direccion_cliente'] ?? ''); ?></p>
                <?php if (!empty($pedido['notas'])): ?>
                    <p><strong>Notas:</strong> <?php echo htmlspecialchars($pedido['notas']); ?></p>
                <?php endif; ?>
            </div>

            <div class="order-details">
                <h3>Detalles del Pedido</h3>
                <p><strong>ID del Pedido:</strong> <?php echo $pedido['id_pedido']; ?></p>
                <p><strong>Estado:</strong> <?php echo ucfirst($pedido['estado']); ?></p>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i:s', strtotime($pedido['fecha'])); ?></p>
                <p><strong>Total:</strong> $<?php echo number_format($pedido['total'], 2); ?></p>
            </div>
        <?php endif; ?>

        <a href="generar_receta_pdf.php?id_pedido=<?= $id_pedido ?>" class="back-btn" style="margin-right: 1rem;">Descargar Receta (PDF)</a>
        <a href="../index.php" class="back-btn">Volver al Inicio</a>
    </div>
</body>

</html>