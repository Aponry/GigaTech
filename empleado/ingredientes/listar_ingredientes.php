<?php
// Indicamos que la respuesta será en formato JSON
header('Content-Type: application/json; charset=utf-8');

// Includimos la conexión a la base de datos
require_once __DIR__ . '/../../conexion.php';

$db = $conexion ?? null; // Verificamos si la conexión es válida
if (!$db) {
    // Si no hay conexión, devolvemos un error 500
    http_response_code(500);
    echo json_encode([], JSON_UNESCAPED_UNICODE); // Respuesta vacía con formato JSON
    exit;
}

// Consulta SQL para obtener los ingredientes ordenados por nombre
$sql = "SELECT id_ingrediente, nombre, tipo_producto, costo, stock FROM ingrediente ORDER BY nombre ASC";
$res = $db->query($sql); // Ejecutamos la consulta

$lista = []; // Array para almacenar los resultados
if ($res) {
    // Si la consulta fue exitosa, recorremos los resultados
    while ($r = $res->fetch_assoc()) {
        // Formateamos el costo para tener 2 decimales
        $r['costo'] = number_format((float) ($r['costo'] ?? 0), 2, '.', '');
        $lista[] = $r; // Agregamos cada fila al array de resultados
    }
    $res->close(); // Cerramos el resultado de la consulta
}

// Devolvemos los datos como JSON
echo json_encode($lista, JSON_UNESCAPED_UNICODE); // Retornamos la lista de ingredientes
