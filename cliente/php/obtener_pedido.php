<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../conexion.php';

if (!$conexion) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

$id_pedido = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_pedido <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de pedido inválido']);
    exit;
}

try {
    // Obtener información del pedido
    $sql_pedido = "SELECT id_pedido, total, fecha, estado, tipo_pedido, nombre_cliente, email_cliente, telefono_cliente, direccion_cliente, notas, metodo_pago
                    FROM pedido
                    WHERE id_pedido = ?";
    $stmt_pedido = $conexion->prepare($sql_pedido);
    
    if (!$stmt_pedido) {
        throw new Exception('Error al preparar la consulta del pedido: ' . $conexion->error);
    }
    
    $stmt_pedido->bind_param('i', $id_pedido);
    $stmt_pedido->execute();
    $result_pedido = $stmt_pedido->get_result();
    
    if ($result_pedido->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Pedido no encontrado']);
        exit;
    }
    
    $pedido = $result_pedido->fetch_assoc();
    $stmt_pedido->close();
    
    // Obtener detalles del pedido
    $sql_detalles = "SELECT dp.id_producto, dp.id_promocion, dp.cantidad, dp.precio_unitario, dp.subtotal,
                            p.nombre as nombre_producto, p.descripcion as descripcion_producto, p.imagen as imagen_producto, p.tipo as tipo_producto, p.permitir_ingredientes,
                            prom.nombre as nombre_promocion, prom.descripcion as descripcion_promocion, prom.imagen as imagen_promocion
                     FROM detalle_pedido dp
                     LEFT JOIN productos p ON dp.id_producto = p.id_producto
                     LEFT JOIN promocion prom ON dp.id_promocion = prom.id_promocion
                     WHERE dp.id_pedido = ?";
    $stmt_detalles = $conexion->prepare($sql_detalles);

    if (!$stmt_detalles) {
        throw new Exception('Error al preparar la consulta de detalles: ' . $conexion->error);
    }

    $stmt_detalles->bind_param('i', $id_pedido);
    $stmt_detalles->execute();
    $result_detalles = $stmt_detalles->get_result();

    $detalles = [];
    while ($detalle = $result_detalles->fetch_assoc()) {
        // Si es un producto que permite ingredientes, obtener los ingredientes
        if ($detalle['id_producto'] && $detalle['permitir_ingredientes'] == 1) {
            $tipo = $detalle['tipo_producto'];
            $sql_ing = "SELECT id_ingrediente, nombre, costo FROM ingrediente WHERE tipo_producto = ? ORDER BY nombre";
            $stmt_ing = $conexion->prepare($sql_ing);
            if ($stmt_ing) {
                $stmt_ing->bind_param('s', $tipo);
                $stmt_ing->execute();
                $res_ing = $stmt_ing->get_result();
                $ingredientes = [];
                while ($ing = $res_ing->fetch_assoc()) {
                    $ing['id_ingrediente'] = (int) $ing['id_ingrediente'];
                    $ing['costo'] = (float) $ing['costo'];
                    $ing['nombre'] = $ing['nombre'];
                    $ingredientes[] = $ing;
                }
                $stmt_ing->close();
                $detalle['ingredientes'] = $ingredientes;
            } else {
                $detalle['ingredientes'] = [];
            }
        } else {
            $detalle['ingredientes'] = [];
        }
        $detalles[] = $detalle;
    }
    $stmt_detalles->close();
    
    // Preparar la respuesta
    $respuesta = [
        'pedido' => $pedido,
        'detalles' => $detalles
    ];
    
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}

$conexion->close();
?>