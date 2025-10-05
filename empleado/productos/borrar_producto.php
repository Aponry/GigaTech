<?php
// Incluimos la conexión a la base de datos
require_once __DIR__ . '/../../conexion.php';
header('Content-Type: application/json'); // Indicamos que la respuesta será en formato JSON

// Verificamos que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

// Recibimos el ID del producto a eliminar
$id = intval($_POST['id_producto'] ?? 0); // Obtenemos el ID y lo convertimos a entero
if (!$id) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    // Preparamos la consulta para eliminar el producto por ID
    $stmt = $conexion->prepare('DELETE FROM productos WHERE id_producto = ?');
    $stmt->bind_param('i', $id); // Asociamos el ID a la consulta
    $stmt->execute(); // Ejecutamos la consulta

    // Verificamos si se eliminó alguna fila (producto)
    if ($stmt->affected_rows > 0) {
        echo json_encode(['ok' => true]); // Si se eliminó el producto
    } else {
        echo json_encode(['ok' => false, 'error' => 'El producto no existe']); // Si no se encontró el producto
    }
} catch (mysqli_sql_exception $e) {
    // Si hubo un error de constraint, significa que el producto está en uso
    if (str_contains($e->getMessage(), 'foreign key constraint fails')) {
        echo json_encode(['ok' => false, 'error' => 'No se puede borrar este producto porque está en una promoción.']);
    } else {
        // Otros errores de base de datos
        echo json_encode(['ok' => false, 'error' => 'Error en base de datos: ' . $e->getMessage()]);
    }
}

// Cerramos la conexión
$conexion->close();
