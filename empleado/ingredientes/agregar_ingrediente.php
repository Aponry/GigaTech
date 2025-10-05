<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$db = $conexion ?? null;
if (!$db) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'sin conexión'], JSON_UNESCAPED_UNICODE);
    exit;
}

// recibimos datos
$nombre = trim($_POST['nombre'] ?? '');
$tipo = $_POST['tipo_producto'] ?? '';
$costoRaw = trim($_POST['costo'] ?? '');

// leemos valores reales del ENUM para validar
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

// validación básica
if ($nombre === '' || $tipo === '' || $costoRaw === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'faltan campos'], JSON_UNESCAPED_UNICODE);
    exit;
}

// normalizar costo (acepta coma o punto)
$costoNormalized = str_replace(',', '.', $costoRaw);
if (!is_numeric($costoNormalized)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'costo no numérico'], JSON_UNESCAPED_UNICODE);
    exit;
}
$costo = number_format((float)$costoNormalized, 2, '.', '');

// validar tipo contra enum real
if (!in_array($tipo, $valid_types, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'tipo inválido', 'tipo_recibido' => $tipo, 'tipos_validos' => $valid_types], JSON_UNESCAPED_UNICODE);
    exit;
}

// insertar
$stmt = $db->prepare("INSERT INTO ingrediente (nombre, tipo_producto, costo) VALUES (?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $db->error], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->bind_param('ssd', $nombre, $tipo, $costo);
if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'id' => $db->insert_id], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $db->error], JSON_UNESCAPED_UNICODE);
}
$stmt->close();
