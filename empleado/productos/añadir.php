<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$nombre = trim($_POST['nombre'] ?? '');
$tipo = $_POST['tipo'] ?? '';
$precio = $_POST['precio'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

$allowed = ['pizza', 'bebida', 'hamburguesa', 'otro'];
if ($nombre === '' || !in_array($tipo, $allowed) || !is_numeric($precio)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'datos inválidos']);
    exit;
}

$precio = number_format((float) $precio, 2, '.', '');
$stmt = $conexion->prepare("INSERT INTO productos (nombre,tipo,precio_base,descripcion) VALUES (?,?,?,?)");
$stmt->bind_param('ssds', $nombre, $tipo, $precio, $descripcion);
if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'id' => $conexion->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $conexion->error]);
}
