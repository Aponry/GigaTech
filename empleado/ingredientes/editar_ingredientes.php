<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$id = isset($_POST['id_ingrediente']) ? (int)$_POST['id_ingrediente'] : 0;
$nombre = trim($_POST['nombre'] ?? '');
$tipo = $_POST['tipo_producto'] ?? '';
$costo = $_POST['costo'] ?? '';

$tipos_validos = ['pizza','hamburguesa','otro'];
if ($id <= 0 || $nombre === '' || !in_array($tipo,$tipos_validos) || !is_numeric($costo)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'datos inválidos']);
    exit;
}

$costo = number_format((float)$costo, 2, '.', '');
$stmt = $conexion->prepare("UPDATE ingrediente SET nombre=?, tipo_producto=?, costo=? WHERE id_ingrediente=?");
$stmt->bind_param('ssdi',$nombre,$tipo,$costo,$id);

if ($stmt->execute()) echo json_encode(['ok'=>true,'rows'=>$stmt->affected_rows]);
else { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$conexion->error]); }
