<?php
// pedido_stream.php - SSE para actualizaciones de pedido

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

// Obtener pedido completo
function getPedido($id_pedido) {
    global $conexion;
    $stmt = $conexion->prepare("SELECT * FROM pedido WHERE id_pedido = ?");
    $stmt->bind_param('i', $id_pedido);
    $stmt->execute();
    $result = $stmt->get_result();
    $pedido = $result->fetch_assoc();
    $stmt->close();

    if ($pedido) {
        // Obtener detalles
        $stmt_det = $conexion->prepare("SELECT * FROM detalle_pedido WHERE id_pedido = ?");
        $stmt_det->bind_param('i', $id_pedido);
        $stmt_det->execute();
        $result_det = $stmt_det->get_result();
        $pedido['detalles'] = $result_det->fetch_all(MYSQLI_ASSOC);
        $stmt_det->close();
    }

    return $pedido;
}

$pedido = getPedido($id_pedido);

if (!$pedido) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido no encontrado']);
    exit;
}

// SSE mode
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Disable output buffering
if (ob_get_level()) {
    ob_end_clean();
}

$last_estado = $pedido['estado'];

// Enviar estado inicial
echo "event: update\n";
echo "data: " . json_encode([
    'id_pedido' => $id_pedido,
    'estado' => $last_estado,
    'pedido' => $pedido
]) . "\n\n";
flush();

// Mantener conexión por un tiempo limitado
$timeout = 30; // 30 segundos
$start = time();

while (time() - $start < $timeout) {
    // Verificar si el estado cambió cada 2 segundos
    $current_pedido = getPedido($id_pedido);
    if ($current_pedido && $current_pedido['estado'] !== $last_estado) {
        $last_estado = $current_pedido['estado'];
        echo "event: update\n";
        echo "data: " . json_encode([
            'id_pedido' => $id_pedido,
            'estado' => $last_estado,
            'pedido' => $current_pedido
        ]) . "\n\n";
        flush();
        break; // Enviar y cerrar
    }

    sleep(2); // Esperar 2 segundos
}

echo "event: close\ndata: Connection closed\n\n";
flush();
?>