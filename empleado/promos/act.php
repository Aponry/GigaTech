<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$id = $_POST['id_promocion'] ?? 0;
$nombre = trim($_POST['nombre'] ?? '');
$precio = $_POST['precio'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$productos = $_POST['productos'] ?? [];

if ($id <= 0 || $nombre == '' || !is_numeric($precio) || !is_array($productos)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'datos inválidos']);
    exit;
}

$precio = number_format((float) $precio, 2, '.', '');
$stmt = $conexion->prepare("UPDATE promocion SET nombre=?, precio=?, descripcion=? WHERE id_promocion=?");
$stmt->bind_param('sdsi', $nombre, $precio, $descripcion, $id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $conexion->error]);
    exit;
}

$conexion->query("DELETE FROM promocion_producto WHERE id_promocion=$id");
if (!empty($productos)) {
    $stmt2 = $conexion->prepare("INSERT INTO promocion_producto(id_promocion,id_producto,cantidad) VALUES(?,?,?)");
    foreach ($productos as $pid => $cant) {
        $c = (float) $cant;
        $stmt2->bind_param('iid', $id, $pid, $c);
        $stmt2->execute();
    }
}

echo json_encode(['ok' => true, 'rows' => $stmt->affected_rows]);
