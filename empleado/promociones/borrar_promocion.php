<?php
// Devuelve respuestas en formato JSON
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

// Si no hay conexión con la base, corta
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'sin conexión']);
    exit;
}

// Toma el id de la promoción desde el POST
$id = isset($_POST['id_promocion']) ? (int)$_POST['id_promocion'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'id inválido']);
    exit;
}

// Borra primero los productos vinculados a esa promoción
$conexion->query("DELETE FROM promocion_producto WHERE id_promocion = " . $id);

// Luego elimina la promoción en sí
if ($conexion->query("DELETE FROM promocion WHERE id_promocion = " . $id)) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $conexion->error]);
}
