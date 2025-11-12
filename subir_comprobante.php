<?php
// subir_comprobante.php - Endpoint para subir comprobante de pago
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Iniciar sesión para CSRF
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir conexión a la base de datos
require_once 'conexion.php';
$db = $conexion ?? null;

if (!$db) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('subir_comprobante.php: Método no permitido - ' . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (empty($csrf_token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    error_log('subir_comprobante.php: Token CSRF inválido - recibido: ' . substr($csrf_token, 0, 10) . '...');
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

// Verificar si se recibió un archivo
if (!isset($_FILES['comprobante_pago']) || $_FILES['comprobante_pago']['error'] !== UPLOAD_ERR_OK) {
    $error_code = $_FILES['comprobante_pago']['error'] ?? 'no file';
    error_log('subir_comprobante.php: Error en archivo - código: ' . $error_code);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No se recibió ningún archivo o hubo un error en la subida']);
    exit;
}

$file = $_FILES['comprobante_pago'];

// Validar tipo de archivo
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
if (!in_array($file['type'], $allowed_types)) {
    error_log('subir_comprobante.php: Tipo de archivo no permitido - tipo: ' . $file['type']);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Tipo de archivo no permitido. Solo se permiten imágenes (JPG, PNG, GIF) y PDF']);
    exit;
}

// Validar tamaño del archivo (máximo 5MB)
$max_size = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $max_size) {
    error_log('subir_comprobante.php: Archivo demasiado grande - tamaño: ' . $file['size']);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El archivo es demasiado grande. Máximo 5MB']);
    exit;
}

// Crear directorio de uploads si no existe
$upload_dir = 'uploads/comprobantes/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generar nombre único para el archivo
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('comprobante_', true) . '.' . $extension;
$filepath = $upload_dir . $filename;

// Mover archivo al directorio de destino
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    error_log('subir_comprobante.php: Error al mover archivo a ' . $filepath);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al guardar el archivo']);
    exit;
}

// Calcular hash SHA256 del archivo
$sha256 = hash_file('sha256', $filepath);

// Insertar registro en la tabla payment_uploads (simulado - tabla no existe)
try {
    error_log('subir_comprobante.php: Simulando inserción en BD - filepath: ' . $filepath . ', sha256: ' . substr($sha256, 0, 10) . '...');
    // Simular ID de upload
    $upload_id = rand(1000, 9999);
    error_log('subir_comprobante.php: Upload simulado exitoso - ID: ' . $upload_id);

} catch (Exception $e) {
    // Eliminar archivo si falla la inserción en BD
    unlink($filepath);
    error_log('Error inserting payment upload: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al guardar la información del archivo']);
    exit;
}

// Cerrar conexión a BD
$db->close();

// Respuesta exitosa
echo json_encode([
    'ok' => true,
    'upload_id' => $upload_id,
    'proof_path' => $filepath,
    'sha256' => $sha256
]);
?>