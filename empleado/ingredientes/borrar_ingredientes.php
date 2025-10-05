<?php
// Configuramos el tipo de respuesta a JSON
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

// Conexión a la base de datos
$db = $conexion ?? null;
if (!$db) {
    // Si no hay conexión, devolvemos error 500
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'sin conexión'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtenemos el id del ingrediente a eliminar
$id = isset($_POST['id_ingrediente']) ? (int) $_POST['id_ingrediente'] : 0;
if ($id <= 0) {
    // Si el id es inválido, devolvemos error 400
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'id inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Preparamos la consulta para eliminar el ingrediente
$stmt = $db->prepare("DELETE FROM ingrediente WHERE id_ingrediente = ?");
if (!$stmt) {
    // Si hay error preparando la consulta, devolvemos error 500
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $db->error], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->bind_param('i', $id);

// Ejecutamos la consulta
if ($stmt->execute()) {
    // Si la eliminación fue exitosa, devolvemos respuesta con el número de filas afectadas
    echo json_encode(['ok' => true, 'rows' => $stmt->affected_rows], JSON_UNESCAPED_UNICODE);
} else {
    // Si ocurre un error en la ejecución, verificamos el tipo de error
    $errno = $db->errno;
    if ($errno == 1451) {
        // Si el error es por una restricción de clave foránea, devolvemos error específico
        echo json_encode(['ok' => false, 'error' => 'No se puede borrar: está asociado a un producto'], JSON_UNESCAPED_UNICODE);
    } else {
        // Para otros errores, devolvemos el error de la base de datos
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $db->error], JSON_UNESCAPED_UNICODE);
    }
}

// Cerramos la consulta
$stmt->close();
?>