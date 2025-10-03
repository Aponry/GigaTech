<?php
require_once __DIR__ . '/../../conexion.php';

$id = $_POST['id_promocion'];
$conexion->query("DELETE FROM promocion_producto WHERE id_promocion=$id");
$conexion->query("DELETE FROM promocion WHERE id_promocion=$id");