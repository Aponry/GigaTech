<?php
// Respuesta en formato JSON
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$salida = [];

// Verifica conexión con la base de datos
if ($conexion) {
    // Consulta principal: obtener todas las promociones
    $sql = "SELECT id_promocion, nombre, precio, descripcion, imagen, activo 
            FROM promocion 
            ORDER BY id_promocion DESC";
    $res = $conexion->query($sql);

    // Si la consulta devuelve resultados
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            // Guarda el id de la promoción actual
            $idProm = (int) $row['id_promocion'];

            // Consulta secundaria: obtener los productos asociados a esta promoción
            $prodStmt = $conexion->prepare("
                SELECT p.id_producto, p.nombre, pp.cantidad
                FROM promocion_producto pp
                JOIN productos p ON pp.id_producto = p.id_producto
                WHERE pp.id_promocion = ?
            ");

            $productos = [];

            // Si la consulta preparada se ejecuta bien
            if ($prodStmt) {
                $prodStmt->bind_param('i', $idProm);
                $prodStmt->execute();
                $prodRes = $prodStmt->get_result();

                // Guarda los productos relacionados con la promoción
                while ($p = $prodRes->fetch_assoc())
                    $productos[] = $p;

                $prodStmt->close();
            }

            // Asigna la lista de productos al array de salida
            $row['productos'] = $productos;

            // Corrige la ruta de imagen si existe
            if (!empty($row['imagen']))
                $row['imagen'] = '../promociones/img/' . ltrim($row['imagen'], '/');

            // Agrega esta promoción completa (con productos) al array final
            $salida[] = $row;
        }
        $res->close();
    }
}

// Devuelve el JSON con todas las promociones
echo json_encode($salida, JSON_UNESCAPED_UNICODE);
