<?php
// Indicamos que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');

// Incluimos la conexión a la base de datos
require_once __DIR__ . '/../../conexion.php';

// Tomamos la conexión o null si falla
$db = $conexion ?? null;

// Si no hay conexión, devolvemos un array vacío con código 500
if (!$db) {
    http_response_code(500); // Error de conexión
    echo json_encode([]); // Enviamos respuesta vacía
    exit;
}

// Consulta para traer todos los productos ordenados por ID descendente
$sql = "SELECT id_producto, nombre, tipo, precio_base, descripcion, imagen, permitir_ingredientes 
        FROM productos 
        ORDER BY id_producto DESC";

$resultado = $db->query($sql);

$productos = [];
if ($resultado) {
    // Iteramos cada fila para formatear tipos y preparar la salida
    while ($fila = $resultado->fetch_assoc()) {
        // Convertimos a tipos correctos
        $fila['id_producto'] = (int) $fila['id_producto'];
        $fila['precio_base'] = (float) $fila['precio_base'];
        $fila['permitir_ingredientes'] = (int) $fila['permitir_ingredientes'];

        // Ajustamos la ruta de la imagen para que sea relativa al proyecto
        if (!empty($fila['imagen'])) {
            $fila['imagen'] = '../../' . ltrim($fila['imagen'], '/');
        }

        // Añadimos el producto al array
        $productos[] = $fila;
    }
    $resultado->close(); // cerramos el result set
}

// Cerramos la conexión
$db->close();

// Devolvemos los productos en formato JSON sin escapar unicode
echo json_encode($productos, JSON_UNESCAPED_UNICODE);
