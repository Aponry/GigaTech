<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';


$sql = "SELECT id_producto, nombre, tipo, precio_base, descripcion, imagen FROM productos ORDER BY id_producto DESC";
$res = $conexion->query($sql);

$lista = [];
if ($res) {
    while($r = $res->fetch_assoc()) $lista[] = $r;
}

echo json_encode($lista);
