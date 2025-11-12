<?php
// editar_pedido.php - Editar un pedido (JSON response)

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
$nombre_cliente = trim($input['nombre_cliente'] ?? '');
$telefono_cliente = trim($input['telefono_cliente'] ?? '');
$direccion_cliente = trim($input['direccion_cliente'] ?? '');
$notas = trim($input['notas'] ?? '');
$estado = $input['estado'] ?? '';

if (!is_numeric($id_pedido)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de pedido inválido']);
    exit;
}

$allowed_statuses = ['pendiente', 'confirmado', 'en_preparacion', 'listo', 'en_reparto', 'entregado', 'cancelado'];

if (empty($nombre_cliente) || empty($telefono_cliente)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nombre y teléfono son obligatorios']);
    exit;
}

if (!empty($estado) && !in_array($estado, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Estado inválido']);
    exit;
}

try {
    $sql = "UPDATE pedido SET nombre_cliente = ?, telefono_cliente = ?, direccion_cliente = ?, notas = ?";
    $params = [$nombre_cliente, $telefono_cliente, $direccion_cliente, $notas];
    $types = 'ssss';

    if (!empty($estado)) {
        $sql .= ", estado = ?";
        $params[] = $estado;
        $types .= 's';
    }

    $sql .= " WHERE id_pedido = ?";
    $params[] = $id_pedido;
    $types .= 'i';

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Pedido no encontrado']);
    }

    $stmt->close();
} catch (Exception $e) {
    error_log('Error editando pedido: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>