<?php
// Indicamos que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');

// Incluimos la conexión a la base de datos
require_once __DIR__ . '/../../conexion.php';

// Tomamos la conexión o null si falla
$db = $conexion ?? null;

// Si no hay conexión, devolvemos un array vacío con código de error
if (!$db) {
    http_response_code(500); // Error de conexión
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Consulta para obtener todos los productos ordenados por ID descendente
$sql = "SELECT id_producto, nombre, tipo, precio_base, descripcion, imagen, permitir_ingredientes
        FROM productos
        ORDER BY id_producto DESC";

$resultado = $db->query($sql);

// Si la consulta falla, devolvemos un error
if (!$resultado) {
    http_response_code(500); // Error en la consulta
    echo json_encode(['error' => 'Error al ejecutar la consulta']);
    $db->close();
    exit;
}

$productos = [];
// Iteramos cada fila para formatear tipos y preparar la salida
while ($fila = $resultado->fetch_assoc()) {
    // Convertimos a tipos correctos
    $fila['id_producto'] = (int) $fila['id_producto'];
    $fila['precio_base'] = (float) $fila['precio_base'];
    $fila['permitir_ingredientes'] = (int) $fila['permitir_ingredientes'];

    // Ajustamos la ruta de la imagen para que sea relativa al cliente
    if (!empty($fila['imagen'])) {
        $fila['imagen'] = ltrim($fila['imagen'], '/');
        if (strpos($fila['imagen'], 'img/') === 0) {
            $fila['imagen'] = 'productos/' . substr($fila['imagen'], 4);
        }
        // Cambiar a la nueva ruta: empleado/productos/img/
        $fila['imagen'] = 'empleado/productos/img/' . basename($fila['imagen']);
    }

    // Añadimos el producto al array
    $productos[] = $fila;
}
$resultado->close(); // Cerramos el result set

// Cerramos la conexión
$db->close();

// Devolvemos los productos en formato JSON sin escapar unicode
echo json_encode($productos, JSON_UNESCAPED_UNICODE);
?>