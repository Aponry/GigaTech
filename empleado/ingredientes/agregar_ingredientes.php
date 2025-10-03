<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$nombre = trim($_POST['nombre'] ?? '');
$tipo = $_POST['tipo_producto'] ?? '';
$costo = $_POST['costo'] ?? '';

$tipos_validos = ['pizza','hamburguesa','otro'];
if ($nombre === '' || !in_array($tipo, $tipos_validos) || !is_numeric($costo)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'datos inválidos']);
    exit;
}

$costo = number_format((float)$costo, 2, '.', '');
$stmt = $conexion->prepare("INSERT INTO ingrediente (nombre,tipo_producto,costo) VALUES (?,?,?)");
$stmt->bind_param('ssd', $nombre,$tipo,$costo);

if ($stmt->execute()) echo json_encode(['ok'=>true,'id'=>$conexion->insert_id]);
else { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$conexion->error]); }
