<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'id inválido']);
    exit;
}

$stmt = $conexion->prepare("SELECT id_producto, nombre, tipo, precio_base, descripcion FROM productos WHERE id_producto = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'no encontrado']);
    exit;
}
echo json_encode($row);

//necesario para eviar errores de inyeccion de sql2