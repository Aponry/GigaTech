<?php
// obtener_pedido.php - Obtener detalles completos de un pedido (JSON response)

session_start();

// Validación de sesión para acceso de empleado
if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'empleado') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

$id_pedido = $_GET['id_pedido'] ?? '';

if (!is_numeric($id_pedido)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de pedido inválido']);
    exit;
}

// Obtener pedido
$stmt = $conexion->prepare("SELECT * FROM pedido WHERE id_pedido = ?");
$stmt->bind_param('i', $id_pedido);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();
$stmt->close();

if (!$pedido) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido no encontrado']);
    exit;
}

// Obtener detalles
$stmt_det = $conexion->prepare("SELECT dp.*, p.nombre as nombre FROM detalle_pedido dp LEFT JOIN productos p ON dp.id_producto = p.id_producto WHERE dp.id_pedido = ?");
$stmt_det->bind_param('i', $id_pedido);
$stmt_det->execute();
$result_det = $stmt_det->get_result();
$pedido['detalles'] = $result_det->fetch_all(MYSQLI_ASSOC);
$stmt_det->close();

// Para promociones, obtener nombre de promocion
foreach ($pedido['detalles'] as &$detalle) {
    if ($detalle['id_promocion']) {
        $stmt_promo = $conexion->prepare("SELECT nombre FROM promocion WHERE id_promocion = ?");
        $stmt_promo->bind_param('i', $detalle['id_promocion']);
        $stmt_promo->execute();
        $result_promo = $stmt_promo->get_result();
        $promo = $result_promo->fetch_assoc();
        $detalle['nombre'] = $promo['nombre'] ?? 'Promoción';
        $stmt_promo->close();
    }
}

echo json_encode($pedido);
?>