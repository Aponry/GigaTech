<?php
// Indicamos que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');

// Iniciamos la sesión para manejar el carrito
session_start();

// Incluimos la conexión a la base de datos
require_once __DIR__ . '/conexion.php';

// Tomamos la conexión o null si falla
$db = $conexion ?? null;

// Si no hay conexión, devolvemos un error
if (!$db) {
    http_response_code(500); // Error de conexión
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtenemos los datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos JSON inválidos']);
    exit;
}

$id_producto = (int) ($input['id_producto'] ?? 0);
$cantidad = (int) ($input['cantidad'] ?? 1);
$ingredientes = $input['ingredientes'] ?? [];

// Validamos los datos
if ($id_producto <= 0 || $cantidad <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de producto o cantidad inválida']);
    exit;
}

// Inicializamos el carrito en la sesión si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Añadimos el producto al carrito
if (!isset($_SESSION['carrito'][$id_producto])) {
    $_SESSION['carrito'][$id_producto] = 0;
}
$_SESSION['carrito'][$id_producto] += $cantidad;

// Registro para validar la respuesta JSON
error_log('añadir_al_carrito.php: Agregado producto ID ' . $id_producto . ' con cantidad ' . $cantidad . ' al carrito');

// Cerramos la conexión
$db->close();

// Devolvemos la respuesta en formato JSON sin escapar unicode
echo json_encode(['success' => true, 'message' => 'Producto añadido al carrito'], JSON_UNESCAPED_UNICODE);
?>