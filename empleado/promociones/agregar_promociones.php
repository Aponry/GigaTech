<?php
require_once __DIR__ . '/../../conexion.php';

$nombre = $_POST['nombre'];
$precio = $_POST['precio'];
$descripcion = $_POST['descripcion'] ?? '';
$imagen = '';

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
    $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $nombreArchivo = 'promo_' . time() . '.' . $ext;
    move_uploaded_file($_FILES['imagen']['tmp_name'], '../../images/' . $nombreArchivo);
    $imagen = 'images/' . $nombreArchivo;
}

$conexion->query("INSERT INTO promocion (nombre, precio, descripcion, imagen, activo) VALUES ('$nombre', '$precio', '$descripcion', '$imagen', 1)");
$idProm = $conexion->insert_id;

// Insertar productos de la promo
if (isset($_POST['productos'])) {
    foreach ($_POST['productos'] as $id => $cant) {
        $conexion->query("INSERT INTO promocion_producto (id_promocion, id_producto, cantidad) VALUES ($idProm, $id, $cant)");
    }
}
