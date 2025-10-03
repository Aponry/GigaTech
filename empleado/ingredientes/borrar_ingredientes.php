<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$id = isset($_POST['id_ingrediente']) ? (int)$_POST['id_ingrediente'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'id inválido']); exit; }

$stmt = $conexion->prepare("DELETE FROM ingrediente WHERE id_ingrediente=?");
$stmt->bind_param('i',$id);

if ($stmt->execute()) echo json_encode(['ok'=>true,'rows'=>$stmt->affected_rows]);
else {
    if ($conexion->errno==1451) echo json_encode(['ok'=>false,'error'=>'No se puede borrar, está asociado a un producto']);
    else { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$conexion->error]); }
}
