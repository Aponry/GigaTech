<?php
// accion_pedido.php - Cambiar estado de pedido (JSON response)

session_start();

// Validación de sesión para acceso de empleado
if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'empleado') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

// Manejar solo solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Validación de entrada
$id_pedido = $input['id_pedido'] ?? '';
$accion = $input['accion'] ?? '';

$allowed_actions = ['pendiente_aprobacion', 'confirmado', 'en_preparacion', 'listo', 'en_reparto', 'entregado', 'cancelado'];

if (!is_numeric($id_pedido) || !in_array($accion, $allowed_actions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

// Actualizar el campo estado de la tabla pedido usando declaración preparada
$stmt = $conexion->prepare("UPDATE pedido SET estado = ? WHERE id_pedido = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
    exit;
}

$stmt->bind_param('si', $accion, $id_pedido);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error actualizando estado']);
}

$stmt->close();
?>