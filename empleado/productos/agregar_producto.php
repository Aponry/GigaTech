<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';


$nombre = trim($_POST['nombre'] ?? '');
$tipo = $_POST['tipo'] ?? '';
$precio = $_POST['precio'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

$tiposPermitidos = ['pizza','bebida','hamburguesa','otro'];
if ($nombre === '' || !in_array($tipo,$tiposPermitidos) || !is_numeric($precio)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Datos inválidos']);
    exit;
}

$precio = number_format((float)$precio,2,'.','');
$rutaImagen = '';
if(!empty($_FILES['imagen']['name'])){
    $archivo = time().'_'.basename($_FILES['imagen']['name']);
    $destino = __DIR__.'/img/'.$archivo;
    if(move_uploaded_file($_FILES['imagen']['tmp_name'],$destino)){
        $rutaImagen = 'productos/img/'.$archivo;
    }
}

$stmt = $conexion->prepare("INSERT INTO productos (nombre,tipo,precio_base,descripcion,imagen) VALUES (?,?,?,?,?)");
$stmt->bind_param('ssdss',$nombre,$tipo,$precio,$descripcion,$rutaImagen);
if($stmt->execute()){
    echo json_encode(['ok'=>true,'id'=>$conexion->insert_id]);
}else{
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$conexion->error]);
}
