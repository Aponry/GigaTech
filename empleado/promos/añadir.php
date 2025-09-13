<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$nombre = trim($_POST['nombre'] ?? '');
$precio = $_POST['precio'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$productos = $_POST['productos'] ?? []; // array de id_producto => cantidad

if ($nombre == '' || !is_numeric($precio) || !is_array($productos)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'datos inválidos']);
    exit;
}

$precio = number_format((float) $precio, 2, '.', '');
$stmt = $conexion->prepare("INSERT INTO promocion(nombre, precio, descripcion) VALUES(?,?,?)");
$stmt->bind_param('sds', $nombre, $precio, $descripcion);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $conexion->error]);
    exit;
}

$idPromo = $conexion->insert_id;
if (!empty($productos)) {
    $stmt2 = $conexion->prepare("INSERT INTO promocion_producto(id_promocion,id_producto,cantidad) VALUES(?,?,?)");
    foreach ($productos as $id => $cant) {
        $c = (float) $cant;
        $stmt2->bind_param('iid', $idPromo, $id, $c);
        $stmt2->execute();
    }
}
echo json_encode(['ok' => true, 'id' => $idPromo]);
