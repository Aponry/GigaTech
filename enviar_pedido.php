<?php
// enviar_pedido.php - Endpoint para procesar pedidos en español
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';
$db = $conexion ?? null;

if (!$db) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'status' => 'error', 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'status' => 'error', 'message' => 'JSON inválido']);
    exit;
}

// Validar CSRF token
$csrf_token = $data['csrf_token'] ?? '';
if (empty($csrf_token) || !isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
    error_log('enviar_pedido.php: Token CSRF inválido - recibido: ' . substr($csrf_token, 0, 10) . '...');
    http_response_code(403);
    echo json_encode(['ok' => false, 'status' => 'error', 'message' => 'Token CSRF inválido']);
    exit;
}

// Validar carrito
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    error_log('enviar_pedido.php: Carrito vacío o no existe');
    http_response_code(400);
    echo json_encode(['ok' => false, 'status' => 'error', 'message' => 'Carrito vacío']);
    exit;
}

// Validar datos del cliente
$cliente = $data['cliente'] ?? [];
$nombre = trim($cliente['nombre'] ?? '');
$email = trim($cliente['email'] ?? '');
$telefono = trim($cliente['telefono'] ?? '');
$direccion = trim($cliente['direccion'] ?? '');
$notas = trim($cliente['notas'] ?? '');
$tipo_entrega = trim($cliente['tipo_entrega'] ?? '');
$metodo_pago = trim($data['metodo_pago'] ?? '');
$upload_id = $data['upload_id'] ?? null;

$errores = [];

// Validaciones
if (empty($nombre)) $errores[] = 'El nombre es obligatorio';
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inválido';
if (empty($telefono)) $errores[] = 'El teléfono es obligatorio';
if ($tipo_entrega === 'delivery' && empty($direccion)) $errores[] = 'La dirección es obligatoria para delivery';
if (!in_array($tipo_entrega, ['delivery', 'retiro'])) $errores[] = 'Tipo de entrega inválido';
if (!in_array($metodo_pago, ['efectivo', 'tarjeta', 'transferencia'])) $errores[] = 'Método de pago inválido';

if (!empty($errores)) {
    error_log('enviar_pedido.php: Errores de validación - ' . implode(', ', $errores));
    http_response_code(400);
    echo json_encode(['ok' => false, 'status' => 'error', 'message' => implode(', ', $errores)]);
    exit;
}

try {
    // Iniciar transacción
    $db->begin_transaction();
    error_log('enviar_pedido.php: Transacción iniciada');

    // Recalcular precios desde la base de datos
    $total = recalcularTotalCarrito($db);
    error_log('enviar_pedido.php: Total recalculado: ' . $total);

    // Insertar pedido
    $sql_pedido = "INSERT INTO pedido (total, fecha, estado, tipo_pedido, nombre_cliente, email_cliente, telefono_cliente, direccion_cliente, notas, metodo_pago) VALUES (?, NOW(), 'confirmacion', ?, ?, ?, ?, ?, ?, ?)";
    $stmt_pedido = $db->prepare($sql_pedido);
    if (!$stmt_pedido) {
        throw new Exception('Error al preparar inserción de pedido: ' . $db->error);
    }

    $estado_inicial = 'pendiente';
    error_log('enviar_pedido.php: Estado inicial: ' . $estado_inicial);
    $stmt_pedido->bind_param('dsssssss', $total, $tipo_entrega, $nombre, $email, $telefono, $direccion, $notas, $metodo_pago);
    $stmt_pedido->execute();
    $id_pedido = $stmt_pedido->insert_id;
    error_log('enviar_pedido.php: Pedido insertado - ID: ' . $id_pedido);
    $stmt_pedido->close();

    // Insertar detalles del pedido
    foreach ($_SESSION['carrito'] as $item) {
        if (isset($item['id_promocion'])) {
            // Promoción
            $precio_unitario = obtenerPrecioPromocion($item['id_promocion'], $db);
            $subtotal = $precio_unitario * $item['cantidad'];

            $sql_detalle = "INSERT INTO detalle_pedido (id_pedido, id_promocion, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
            $stmt_detalle = $db->prepare($sql_detalle);
            $stmt_detalle->bind_param('iiidd', $id_pedido, $item['id_promocion'], $item['cantidad'], $precio_unitario, $subtotal);
        } else {
            // Producto regular
            $precio_base = obtenerPrecioProducto($item['id_producto'], $db);
            $ing_total = 0;

            if (isset($item['ingredientes']) && is_array($item['ingredientes'])) {
                foreach ($item['ingredientes'] as $ing) {
                    $ing_precio = obtenerPrecioIngrediente($ing['id_ingrediente'], $db);
                    $ing_total += $ing_precio * ($ing['cantidad'] ?? 1);
                }
            }

            $precio_unitario = $precio_base + $ing_total;
            $subtotal = $precio_unitario * $item['cantidad'];

            $sql_detalle = "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
            $stmt_detalle = $db->prepare($sql_detalle);
            $stmt_detalle->bind_param('iiidd', $id_pedido, $item['id_producto'], $item['cantidad'], $precio_unitario, $subtotal);
        }

        $stmt_detalle->execute();
        $stmt_detalle->close();
    }

    // Vincular comprobante de pago si existe (simulado - tabla no existe)
    if ($upload_id && is_numeric($upload_id)) {
        error_log('enviar_pedido.php: Simulando vinculación upload ID: ' . $upload_id . ' al pedido ID: ' . $id_pedido);
        // Simular la actualización
    }

    // Confirmar transacción
    $db->commit();
    error_log('enviar_pedido.php: Transacción confirmada');

    // Limpiar carrito
    unset($_SESSION['carrito']);
    error_log('enviar_pedido.php: Carrito limpiado');

    // Enviar notificación al admin
    enviarNotificacionAdmin($id_pedido, $cliente, $total, $tipo_entrega, $metodo_pago);
    error_log('enviar_pedido.php: Notificación enviada al admin');

    // Respuesta exitosa
    echo json_encode([
        'ok' => true,
        'order_id' => $id_pedido,
        'status' => $estado_inicial,
        'total' => $total
    ]);

} catch (Exception $e) {
    $db->rollback();
    error_log('Error en enviar_pedido.php: ' . $e->getMessage());
    error_log('enviar_pedido.php: Rollback realizado');
    http_response_code(500);
    echo json_encode(['ok' => false, 'status' => 'error', 'message' => 'Error interno del servidor']);
}

// Función que recalcula el total del carrito desde la base de datos, para evitar trampas con precios
function recalcularTotalCarrito($db) {
    $total = 0.0;

    foreach ($_SESSION['carrito'] as $item) {
        if (isset($item['id_promocion'])) {
            $precioUnitario = obtenerPrecioPromocion($item['id_promocion'], $db);
            $total += $precioUnitario * $item['cantidad'];
        } else {
            $precioBase = obtenerPrecioProducto($item['id_producto'], $db);
            $totalIngredientes = 0;

            if (isset($item['ingredientes']) && is_array($item['ingredientes'])) {
                foreach ($item['ingredientes'] as $ing) {
                    $precioIngrediente = obtenerPrecioIngrediente($ing['id_ingrediente'], $db);
                    $totalIngredientes += $precioIngrediente * ($ing['cantidad'] ?? 1);
                }
            }

            $total += ($precioBase + $totalIngredientes) * $item['cantidad'];
        }
    }

    return $total;
}

function obtenerPrecioProducto($id_producto, $db) {
    $sql = "SELECT precio_base FROM productos WHERE id_producto = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();
    $precio = $result->fetch_assoc()['precio_base'] ?? 0;
    $stmt->close();
    return (float) $precio;
}

function obtenerPrecioPromocion($id_promocion, $db) {
    $sql = "SELECT precio FROM promocion WHERE id_promocion = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id_promocion);
    $stmt->execute();
    $result = $stmt->get_result();
    $precio = $result->fetch_assoc()['precio'] ?? 0;
    $stmt->close();
    return (float) $precio;
}

function obtenerPrecioIngrediente($id_ingrediente, $db) {
    $sql = "SELECT costo FROM ingredientes WHERE id_ingrediente = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id_ingrediente);
    $stmt->execute();
    $result = $stmt->get_result();
    $precio = $result->fetch_assoc()['costo'] ?? 0;
    $stmt->close();
    return (float) $precio;
}


function enviarNotificacionAdmin($id_pedido, $cliente, $total, $tipo_entrega, $metodo_pago) {
    // Implementación básica de notificación por email
    // En un entorno real, esto debería usar un sistema de notificaciones más robusto

    $asunto = "Nuevo pedido recibido - ID $id_pedido";
    $mensaje = "
    Nuevo pedido recibido:

    ID del pedido: $id_pedido

    Cliente:
    Nombre: {$cliente['nombre']}
    Email: {$cliente['email']}
    Teléfono: {$cliente['telefono']}
    Dirección: {$cliente['direccion']}
    Notas: {$cliente['notas']}

    Tipo de entrega: $tipo_entrega
    Método de pago: $metodo_pago
    Total: $$total

    Estado inicial: pendiente

    Por favor, revise el pedido en el panel de administración.
    ";

    // Enviar email (requiere configuración de mail en PHP)
    $headers = "From: pedidos@pizzaconmigo.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Email del admin - debería estar en configuración
    $admin_email = 'admin@pizzaconmigo.com';

    $result = mail($admin_email, $asunto, $mensaje, $headers);
    error_log('enviar_pedido.php: Email enviado a ' . $admin_email . ' - Resultado: ' . ($result ? 'éxito' : 'fallo'));
}
?>