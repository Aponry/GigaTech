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
$imagenRuta = '';
if (!empty($_FILES['imagen']['name'])) {
    $nombreArchivo = time().'_'.basename($_FILES['imagen']['name']);
    $destino = __DIR__.'/img/'.$nombreArchivo;
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
        $imagenRuta = 'empleado/productos/img/'.$nombreArchivo;
    }
}

$stmt = $conexion->prepare("INSERT INTO productos (nombre,tipo,precio_base,descripcion,imagen) VALUES (?,?,?,?,?)");
$stmt->bind_param('ssdss', $nombre, $tipo, $precio, $descripcion, $imagenRuta);
if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'id' => $conexion->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $conexion->error]);
}
