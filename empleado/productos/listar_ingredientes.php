<?php
// Incluimos la conexión a la base de datos
require_once __DIR__ . '/../../conexion.php';

// Tomamos la conexión o null si falla
$db = $conexion ?? null;

// Si no hay conexión, devolvemos un mensaje de error con código 500
if (!$db) {
    http_response_code(500); // Error de conexión
    echo json_encode(['ok' => false, 'error' => 'Sin conexión a la base']); // Enviamos error de conexión
    exit;
}

// Consulta SQL para traer los ingredientes ordenados por nombre
$sql = "SELECT id_ingrediente, nombre, tipo_producto, costo FROM ingrediente ORDER BY nombre";
$res = $db->query($sql);

// Inicializamos un array para guardar los ingredientes
$ingredientes = [];
if ($res) {
    // Iteramos sobre los resultados
    while ($fila = $res->fetch_assoc()) {
        // Añadimos cada ingrediente al array
        $ingredientes[] = $fila;
    }
    $res->close(); // Cerramos el result set
}

// Devolvemos los ingredientes en formato JSON
echo json_encode($ingredientes);
