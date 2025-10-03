<?php
require_once __DIR__ . '/../../conexion.php';

$res = $conexion->query("SELECT * FROM promocion");
$promociones = [];
while ($r = $res->fetch_assoc()) {
    // Traer productos asociados
    $prodRes = $conexion->query("SELECT p.id_producto, p.nombre, pp.cantidad FROM promocion_producto pp JOIN productos p ON pp.id_producto = p.id_producto WHERE pp.id_promocion = {$r['id_promocion']}");
    $productos = [];
    while ($pr = $prodRes->fetch_assoc()) $productos[] = $pr;
    $r['productos'] = $productos;
    $promociones[] = $r;
}
echo json_encode($promociones);