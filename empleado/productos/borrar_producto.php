<?php
// Incluimos la conexión a la base de datos
require_once __DIR__ . '/../../conexion.php';
header('Content-Type: application/json'); // Indicamos que la respuesta será en formato JSON

// Verificamos que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

// Recibimos el ID del producto a eliminar
$id = intval($_POST['id_producto'] ?? 0); // Obtenemos el ID y lo convertimos a entero
error_log('Received delete request for product id: ' . $id);
if (!$id) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    // Primero verificamos si el producto está en uso en pedidos activos
    $stmt_check = $conexion->prepare('SELECT COUNT(*) as count FROM detalle_pedido dp JOIN pedido p ON dp.id_pedido = p.id_pedido WHERE dp.id_producto = ? AND p.estado NOT IN ("cancelado", "entregado", "pendiente")');
    $stmt_check->bind_param('i', $id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt_check->close();
    error_log('Active pedido count for product ' . $id . ': ' . $count);

    // Log estados of pedidos containing this product
    $stmt_estados = $conexion->prepare('SELECT DISTINCT p.estado FROM detalle_pedido dp JOIN pedido p ON dp.id_pedido = p.id_pedido WHERE dp.id_producto = ?');
    $stmt_estados->bind_param('i', $id);
    $stmt_estados->execute();
    $result_estados = $stmt_estados->get_result();
    $estados = [];
    while ($row = $result_estados->fetch_assoc()) {
        $estados[] = $row['estado'];
    }
    $stmt_estados->close();
    error_log('Estados of pedidos containing product ' . $id . ': ' . implode(', ', $estados));

    // Check for detalle_pedido in inactive pedidos
    $stmt_inactive = $conexion->prepare('SELECT COUNT(*) as inactive_count FROM detalle_pedido dp JOIN pedido p ON dp.id_pedido = p.id_pedido WHERE dp.id_producto = ? AND p.estado IN ("cancelado", "entregado", "pendiente")');
    $stmt_inactive->bind_param('i', $id);
    $stmt_inactive->execute();
    $result_inactive = $stmt_inactive->get_result();
    $inactive_count = $result_inactive->fetch_assoc()['inactive_count'];
    $stmt_inactive->close();
    error_log('Inactive pedido detalle count for product ' . $id . ': ' . $inactive_count);

    // Also check for orphaned detalle_pedido
    $stmt_orphan = $conexion->prepare('SELECT COUNT(*) as orphan_count FROM detalle_pedido WHERE id_producto = ? AND id_pedido NOT IN (SELECT id_pedido FROM pedido)');
    $stmt_orphan->bind_param('i', $id);
    $stmt_orphan->execute();
    $result_orphan = $stmt_orphan->get_result();
    $orphan_count = $result_orphan->fetch_assoc()['orphan_count'];
    $stmt_orphan->close();
    error_log('Orphaned detalle_pedido count for product ' . $id . ': ' . $orphan_count);

    if ($count > 0) {
        echo json_encode(['ok' => false, 'error' => 'No se puede borrar este producto porque está en uso en pedidos activos.']);
        exit;
    } elseif ($orphan_count > 0) {
        // Allow deletion and clean up orphans
        error_log('Cleaning up orphaned detalle_pedido for product ' . $id);
        $stmt_clean = $conexion->prepare('DELETE FROM detalle_pedido WHERE id_producto = ? AND id_pedido NOT IN (SELECT id_pedido FROM pedido)');
        $stmt_clean->bind_param('i', $id);
        $stmt_clean->execute();
        $stmt_clean->close();
    }

    // Clean up detalle_pedido for inactive orders
    if ($inactive_count > 0) {
        error_log('Cleaning up detalle_pedido for inactive orders for product ' . $id);
        $stmt_clean_inactive = $conexion->prepare('DELETE FROM detalle_pedido WHERE id_producto = ? AND id_pedido IN (SELECT id_pedido FROM pedido WHERE estado IN ("cancelado", "entregado", "pendiente"))');
        $stmt_clean_inactive->bind_param('i', $id);
        $stmt_clean_inactive->execute();
        $stmt_clean_inactive->close();
    }

    // Preparamos la consulta para eliminar el producto por ID
    $stmt = $conexion->prepare('DELETE FROM productos WHERE id_producto = ?');
    $stmt->bind_param('i', $id); // Asociamos el ID a la consulta
    $stmt->execute(); // Ejecutamos la consulta
    error_log('Delete executed, affected rows: ' . $stmt->affected_rows);

    // Verificamos si se eliminó alguna fila (producto)
    if ($stmt->affected_rows > 0) {
        echo json_encode(['ok' => true]); // Si se eliminó el producto
    } else {
        echo json_encode(['ok' => false, 'error' => 'El producto no existe']); // Si no se encontró el producto
    }
} catch (mysqli_sql_exception $e) {
    error_log('Exception during delete: ' . $e->getMessage());
    // Otros errores de base de datos
    echo json_encode(['ok' => false, 'error' => 'Error en base de datos: ' . $e->getMessage()]);
}

// Cerramos la conexión
$conexion->close();
