<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$nombre = trim($_POST['nombre'] ?? '');
$precio = $_POST['precio'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$productos = $_POST['productos'] ?? [];

if ($nombre === '' || !is_numeric($precio) || !is_array($productos)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

$precio = number_format((float) $precio, 2, '.', '');

// Imagen obligatoria
if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Imagen requerida']);
    exit;
}

$archivoNombre = time() . '_' . basename($_FILES['imagen']['name']);
$destino = __DIR__ . '/img/' . $archivoNombre;
if (!is_dir(dirname($destino)))
    mkdir(dirname($destino), 0755, true);
if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la imagen']);
    exit;
}

$rutaImagen = 'img/' . $archivoNombre;

// Insertar promo
$stmt = $conexion->prepare("INSERT INTO promocion(nombre, precio, descripcion, imagen) VALUES(?,?,?,?)");
$stmt->bind_param('sdss', $nombre, $precio, $descripcion, $rutaImagen);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $stmt->error]);
    exit;
}

$idPromo = $conexion->insert_id;

// Insertar productos vinculados
$detalle = $conexion->prepare("INSERT INTO promocion_producto(id_promocion, id_producto, cantidad) VALUES(?,?,?)");
foreach ($productos as $idProd => $cant) {
    $detalle->bind_param('iii', $idPromo, $idProd, $cant);
    $detalle->execute();
}

echo json_encode(['ok' => true]);
