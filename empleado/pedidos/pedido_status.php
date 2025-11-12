<?php
// pedido_status.php - Endpoint de polling para estado de pedido

require_once __DIR__ . '/../../conexion.php';

$id_pedido = $_GET['id_pedido'] ?? null;

if (!$id_pedido || !is_numeric($id_pedido)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de pedido inválido']);
    exit;
}

if (!$conexion) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

// Obtener estado y updated_at
$stmt = $conexion->prepare("SELECT estado, fecha FROM pedido WHERE id_pedido = ?");
$stmt->bind_param('i', $id_pedido);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido no encontrado']);
    exit;
}

header('Content-Type: application/json');
echo json_encode([
    'id_pedido' => (int)$id_pedido,
    'estado' => $row['estado'],
    'updated_at' => $row['fecha']
]);
?>