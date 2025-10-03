<?php
require_once __DIR__ . '/../../conexion.php';

$id = $_POST['id_promocion'];

// Si viene checkbox para activo
if (isset($_POST['activo'])) {
    $activo = $_POST['activo'] ? 1 : 0;
    $conexion->query("UPDATE promocion SET activo=$activo WHERE id_promocion=$id");
    exit;
}

$nombre = $_POST['nombre'];
$precio = $_POST['precio'];
$descripcion = $_POST['descripcion'] ?? '';
$imagen = '';

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
    $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $nombreArchivo = 'promo_' . time() . '.' . $ext;
    move_uploaded_file($_FILES['imagen']['tmp_name'], '../../images/' . $nombreArchivo);
    $imagen = 'images/' . $nombreArchivo;
    $conexion->query("UPDATE promocion SET nombre='$nombre', precio='$precio', descripcion='$descripcion', imagen='$imagen' WHERE id_promocion=$id");
} else {
    $conexion->query("UPDATE promocion SET nombre='$nombre', precio='$precio', descripcion='$descripcion' WHERE id_promocion=$id");
}

// Actualizar productos
$conexion->query("DELETE FROM promocion_producto WHERE id_promocion=$id");
if (isset($_POST['productos'])) {
    foreach ($_POST['productos'] as $idProd => $cant) {
        $conexion->query("INSERT INTO promocion_producto (id_promocion, id_producto, cantidad) VALUES ($id, $idProd, $cant)");
    }
}
