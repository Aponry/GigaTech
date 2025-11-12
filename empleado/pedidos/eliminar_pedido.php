<?php
// eliminar_pedido.php - Eliminar un pedido (JSON response)

session_start();

// Verificación de seguridad para acceso de empleado
if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'empleado') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

require_once '../../conexion.php';

$input = json_decode(file_get_contents('php://input'), true);
$id_pedido = $input['id_pedido'] ?? '';

if (!is_numeric($id_pedido)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de pedido inválido']);
    exit;
}

try {
    // Iniciar transacción
    $conexion->begin_transaction();

    // Eliminar detalles del pedido primero
    $stmt_detalle = $conexion->prepare("DELETE FROM detalle_pedido WHERE id_pedido = ?");
    $stmt_detalle->bind_param('i', $id_pedido);
    $stmt_detalle->execute();
    $stmt_detalle->close();

    // Eliminar el pedido
    $stmt_pedido = $conexion->prepare("DELETE FROM pedido WHERE id_pedido = ?");
    $stmt_pedido->bind_param('i', $id_pedido);
    $stmt_pedido->execute();

    if ($stmt_pedido->affected_rows > 0) {
        $conexion->commit();
        echo json_encode(['success' => true]);
    } else {
        $conexion->rollback();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Pedido no encontrado']);
    }

    $stmt_pedido->close();
} catch (Exception $e) {
    $conexion->rollback();
    error_log('Error eliminando pedido: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>