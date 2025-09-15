<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$id = intval($_POST['id_promocion'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$precio = $_POST['precio'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$productos = $_POST['productos'] ?? [];

if ($id <= 0 || $nombre === '' || !is_numeric($precio) || !is_array($productos)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

$precio = number_format((float) $precio, 2, '.', '');

// Buscar imagen actual
$imgVieja = '';
$sel = $conexion->prepare("SELECT imagen FROM promocion WHERE id_promocion=?");
$sel->bind_param('i', $id);
$sel->execute();
$sel->bind_result($imgVieja);
$sel->fetch();
$sel->close();

$rutaImagen = $imgVieja;

// Si viene nueva imagen
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
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
    if ($imgVieja && file_exists(__DIR__ . '/img/' . basename($imgVieja))) {
        @unlink(__DIR__ . '/img/' . basename($imgVieja));
    }
}

// Actualizar promo
$upd = $conexion->prepare("UPDATE promocion SET nombre=?, precio=?, descripcion=?, imagen=? WHERE id_promocion=?");
$upd->bind_param('sdssi', $nombre, $precio, $descripcion, $rutaImagen, $id);
if (!$upd->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $upd->error]);
    exit;
}

// Actualizar productos vinculados
$conexion->query("DELETE FROM promocion_producto WHERE id_promocion=$id");
$detalle = $conexion->prepare("INSERT INTO promocion_producto(id_promocion, id_producto, cantidad) VALUES(?,?,?)");
foreach ($productos as $idProd => $cant) {
    $detalle->bind_param('iii', $id, $idProd, $cant);
    $detalle->execute();
}

echo json_encode(['ok' => true]);
