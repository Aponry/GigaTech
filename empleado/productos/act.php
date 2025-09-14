<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$id = isset($_POST['id_producto']) ? (int) $_POST['id_producto'] : 0;
$nombre = trim($_POST['nombre'] ?? '');
$tipo = $_POST['tipo'] ?? '';
$precio = $_POST['precio'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

$allowed = ['pizza', 'bebida', 'hamburguesa', 'otro'];
if ($id <= 0 || $nombre === '' || !in_array($tipo, $allowed) || !is_numeric($precio)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'datos inválidos']);
    exit;
}

$precio = number_format((float) $precio, 2, '.', '');
$imagenRuta = '';
if (!empty($_FILES['imagen']['name'])) {
    $nombreArchivo = time().'_'.basename($_FILES['imagen']['name']);
    $destino = __DIR__.'/img/'.$nombreArchivo;
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
        $imagenRuta = 'empleado/productos/img/'.$nombreArchivo;
    }
}

if ($imagenRuta !== '') {
    $stmt = $conexion->prepare("UPDATE productos SET nombre=?, tipo=?, precio_base=?, descripcion=?, imagen=? WHERE id_producto=?");
    $stmt->bind_param('ssdssi', $nombre, $tipo, $precio, $descripcion, $imagenRuta, $id);
} else {
    $stmt = $conexion->prepare("UPDATE productos SET nombre=?, tipo=?, precio_base=?, descripcion=? WHERE id_producto=?");
    $stmt->bind_param('ssdsi', $nombre, $tipo, $precio, $descripcion, $id);
}

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'rows' => $stmt->affected_rows]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $conexion->error]);
}

