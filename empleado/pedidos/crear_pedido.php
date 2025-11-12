<?php
// crear_pedido.php - Endpoint para crear pedidos desde el cliente

ob_clean();
header("Content-Type: application/json; charset=utf-8");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../../conexion.php';

if (!$conexion) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos JSON inválidos']);
    exit;
}

// Validar datos requeridos
$required = ['items', 'total', 'tipo_pedido', 'nombre_cliente', 'telefono_cliente', 'metodo_pago'];
foreach ($required as $field) {
    if (!isset($input[$field]) || (is_array($input[$field]) && empty($input[$field]))) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => "Campo requerido faltante: $field"]);
        exit;
    }
}

if ($input['tipo_pedido'] === 'domicilio' && empty($input['direccion_cliente'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Dirección requerida para pedidos a domicilio']);
    exit;
}

try {
    $conexion->begin_transaction();

    // Asociar o crear cliente
    $id_cliente = null;
    $telefono = $input['telefono_cliente'];
    $nombre = $input['nombre_cliente'];

    if ($telefono) {
        // Buscar cliente existente por teléfono
        $stmt_cliente = $conexion->prepare("SELECT id_cliente FROM clientes WHERE telefono = ?");
        $stmt_cliente->bind_param('s', $telefono);
        $stmt_cliente->execute();
        $result_cliente = $stmt_cliente->get_result();
        $cliente = $result_cliente->fetch_assoc();
        $stmt_cliente->close();

        if ($cliente) {
            $id_cliente = $cliente['id_cliente'];
        } else {
            // Crear nuevo cliente
            $stmt_insert_cliente = $conexion->prepare("INSERT INTO clientes (nombre, telefono) VALUES (?, ?)");
            $stmt_insert_cliente->bind_param('ss', $nombre, $telefono);
            $stmt_insert_cliente->execute();
            $id_cliente = $conexion->insert_id;
            $stmt_insert_cliente->close();
        }
    }

    // Insertar pedido
    $sql_pedido = "INSERT INTO pedido (id_cliente, total, fecha, estado, tipo_pedido, nombre_cliente, telefono_cliente, direccion_cliente, notas, metodo_pago) VALUES (?, ?, NOW(), 'pendiente', ?, ?, ?, ?, ?, ?)";
    $stmt_pedido = $conexion->prepare($sql_pedido);
    if (!$stmt_pedido) {
        throw new Exception('Error preparando insert de pedido: ' . $conexion->error);
    }

    $direccion = $input['direccion_cliente'] ?? null;
    $notas = $input['notas'] ?? null;

    error_log('crear_pedido.php: id_cliente=' . $id_cliente . ', total=' . $input['total'] . ', tipo_pedido=' . $input['tipo_pedido'] . ', nombre=' . $input['nombre_cliente'] . ', telefono=' . $input['telefono_cliente'] . ', direccion=' . ($direccion ?? 'null') . ', notas=' . ($notas ?? 'null') . ', metodo_pago=' . $input['metodo_pago']);

    $stmt_pedido->bind_param('idssssss',
        $id_cliente,
        $input['total'],
        $input['tipo_pedido'],
        $input['nombre_cliente'],
        $input['telefono_cliente'],
        $direccion,
        $notas,
        $input['metodo_pago']
    );

    if (!$stmt_pedido->execute()) {
        throw new Exception('Error ejecutando insert de pedido: ' . $stmt_pedido->error);
    }

    $id_pedido = $conexion->insert_id;
    $stmt_pedido->close();

    // Insertar detalles
    $sql_detalle = "INSERT INTO detalle_pedido (id_pedido, id_producto, id_promocion, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_detalle = $conexion->prepare($sql_detalle);
    if (!$stmt_detalle) {
        throw new Exception('Error preparando insert de detalle: ' . $conexion->error);
    }

    foreach ($input['items'] as $item) {
        $id_prod = $item['id_producto'] ?? null;
        $id_promo = $item['id_promocion'] ?? null;
        $cant = $item['cantidad'];
        $prec_unit = $item['precio_unitario'];
        $sub = $item['subtotal'];

        $stmt_detalle->bind_param('iiiidd',
            $id_pedido,
            $id_prod,
            $id_promo,
            $cant,
            $prec_unit,
            $sub
        );

        if (!$stmt_detalle->execute()) {
            throw new Exception('Error ejecutando insert de detalle: ' . $stmt_detalle->error);
        }
    }

    $stmt_detalle->close();

    // Insertar en factura si método de pago ya confirmado (POS o Transferencia)
    if (in_array(strtolower($input['metodo_pago']), ['pos', 'transferencia'])) {
        $sql_factura = "INSERT INTO factura (id_pedido, metodo_pago, comprobante_transferencia, fecha) VALUES (?, ?, ?, NOW())";
        $stmt_factura = $conexion->prepare($sql_factura);
        if ($stmt_factura) {
            $metodo_pago = strtoupper($input['metodo_pago']);
            $stmt_factura->bind_param('iss',
                $id_pedido,
                $metodo_pago,
                $input['comprobante'] ?? null
            );
            $stmt_factura->execute();
            $stmt_factura->close();
        }
        // No fallar si no existe tabla factura
    }

    $conexion->commit();

    echo json_encode([
        'ok' => true,
        'id_pedido' => $id_pedido,
        'estado' => 'pendiente',
        'mensaje' => ''
    ]);

} catch (Exception $e) {
    $conexion->rollback();
    error_log('Error en crear_pedido.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor']);
}
?>