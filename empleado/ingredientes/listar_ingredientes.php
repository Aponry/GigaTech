<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$sql = "SELECT id_ingrediente, nombre, tipo_producto, costo FROM ingrediente ORDER BY id_ingrediente DESC";
$res = $conexion->query($sql);

$rows = [];
if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
echo json_encode($rows);
