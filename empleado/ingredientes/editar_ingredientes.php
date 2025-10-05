<?php
// Incluir la conexión a la base de datos (ruta relativa)
// desde empleado/ingredientes -> ../../conexion.php
require_once __DIR__ . '/../../conexion.php';
$db = $conexion ?? null;
// Siempre devolver JSON
header('Content-Type: application/json; charset=utf-8');
if (!$db) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'sin conexión'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtenemos los datos que vienen por POST
$id = isset($_POST['id_ingrediente']) ? (int) $_POST['id_ingrediente'] : 0;
$nombre = trim($_POST['nombre'] ?? '');
$tipo = $_POST['tipo_producto'] ?? '';
$costoRaw = trim($_POST['costo'] ?? '');

// Leemos los tipos de ingrediente que están en la base
$valid_types = [];
$enumRes = $db->query("SHOW COLUMNS FROM ingrediente LIKE 'tipo_producto'");
if ($enumRes) {
    $row = $enumRes->fetch_assoc();
    if (!empty($row['Type'])) {
        preg_match_all("/'([^']+)'/", $row['Type'], $m);
        $valid_types = $m[1] ?? [];
    }
    $enumRes->close();
}

// Validamos que los datos sean correctos
if ($id <= 0 || $nombre === '' || $tipo === '' || $costoRaw === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'faltan campos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$costoNormalized = str_replace(',', '.', $costoRaw);
if (!is_numeric($costoNormalized)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'costo no numérico'], JSON_UNESCAPED_UNICODE);
    exit;
}
$costo = number_format((float) $costoNormalized, 2, '.', '');

// Chequeamos que el tipo de producto sea válido
if (!in_array($tipo, $valid_types, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'tipo inválido', 'tipo_recibido' => $tipo, 'tipos_validos' => $valid_types], JSON_UNESCAPED_UNICODE);
    exit;
}

// Preparamos la consulta para actualizar el ingrediente
$stmt = $db->prepare("UPDATE ingrediente SET nombre = ?, tipo_producto = ?, costo = ? WHERE id_ingrediente = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $db->error], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->bind_param('ssdi', $nombre, $tipo, $costo, $id);
if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'rows' => $stmt->affected_rows], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $db->error], JSON_UNESCAPED_UNICODE);
}
$stmt->close();
?>