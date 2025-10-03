<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';


$id = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
if($id<=0){
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'id inválido']);
    exit;
}

$stmt = $conexion->prepare("DELETE FROM productos WHERE id_producto=?");
$stmt->bind_param('i',$id);
if($stmt->execute()){
    echo json_encode(['ok'=>true,'rows'=>$stmt->affected_rows]);
}else{
    if($conexion->errno==1451){
        echo json_encode(['ok'=>false,'error'=>'No se puede borrar, el producto está en uso']);
    }else{
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>$conexion->error]);
    }
}
