<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../conexion.php';
$db = $conexion ?? null;

if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

$telefono = trim($_GET['telefono'] ?? '');
$id_pedido = trim($_GET['id_pedido'] ?? '');

if (empty($telefono) && empty($id_pedido)) {
    http_response_code(400);
    echo json_encode(['error' => 'Teléfono o ID de pedido requerido']);
    exit;
}

try {
    if (!empty($telefono)) {
        $sql = "SELECT id_pedido, fecha, total, estado, nombre_cliente, telefono_cliente FROM pedido WHERE telefono_cliente = ? ORDER BY fecha DESC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $telefono);
    } elseif (!empty($id_pedido)) {
        $sql = "SELECT id_pedido, fecha, total, estado, nombre_cliente, telefono_cliente FROM pedido WHERE id_pedido = ? ORDER BY fecha DESC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id_pedido);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $pedidos = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode($pedidos);
} catch (Exception $e) {
    error_log('Error in obtener_pedidos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>