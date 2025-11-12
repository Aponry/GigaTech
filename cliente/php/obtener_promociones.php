<?php
// Indicamos que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');

// Incluimos la conexión a la base de datos
require_once __DIR__ . '/../../conexion.php';

// Tomamos la conexión o null si falla
$db = $conexion ?? null;

// Si no hay conexión, devolvemos un error
if (!$db) {
    http_response_code(500); // Error de conexión
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Consulta para obtener todas las promociones activas ordenadas por ID descendente
$sql = "SELECT id_promocion, nombre, precio, descripcion, imagen, activo
        FROM promocion
        WHERE activo = 1
        ORDER BY id_promocion DESC";

$resultado = $db->query($sql);

// Si la consulta falla, devolvemos un error
if (!$resultado) {
    http_response_code(500); // Error en la consulta
    echo json_encode(['error' => 'Error al ejecutar la consulta de promociones']);
    $db->close();
    exit;
}

$promociones = [];
// Iteramos cada fila para formatear tipos y preparar la salida
while ($fila = $resultado->fetch_assoc()) {
    // Convertimos a tipos correctos
    $fila['id_promocion'] = (int) $fila['id_promocion'];
    $fila['precio'] = (float) $fila['precio'];
    $fila['activo'] = (int) $fila['activo'];

    // Ajustamos la ruta de la imagen para que sea relativa al cliente
    if (!empty($fila['imagen'])) {
    $basename = basename($fila['imagen']); // promo_1760311526.webp
    $fila['imagen'] = 'empleado/promociones/img/' . $basename;
}


    // Consulta para obtener los productos asociados a esta promoción
    $id_promocion = $fila['id_promocion'];
    $sql_productos = "SELECT p.id_producto, p.nombre, pp.cantidad
                      FROM promocion_producto pp
                      JOIN productos p ON pp.id_producto = p.id_producto
                      WHERE pp.id_promocion = ?";
    $stmt = $db->prepare($sql_productos);

    $productos = [];
    if ($stmt) {
        $stmt->bind_param('i', $id_promocion);
        $stmt->execute();
        $res_productos = $stmt->get_result();

        while ($prod = $res_productos->fetch_assoc()) {
            $prod['id_producto'] = (int) $prod['id_producto'];
            $prod['cantidad'] = (int) $prod['cantidad'];
            $productos[] = $prod;
        }
        $stmt->close();
    }

    // Añadimos los productos a la promoción
    $fila['productos'] = $productos;

    // Registrar la ruta de la imagen para depuración
    error_log("Processed image path for promo {$fila['id_promocion']}: {$fila['imagen']}");
    // Añadimos la promoción al array
    $promociones[] = $fila;
}
$resultado->close(); // Cerramos el result set

// Cerramos la conexión
$db->close();

// Devolvemos las promociones en formato JSON sin escapar unicode
echo json_encode($promociones, JSON_UNESCAPED_UNICODE);
?>