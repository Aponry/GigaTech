<?php
header('Content-Type: application/json; charset=utf-8');
ob_clean();
ob_start();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../conexion.php';
$db = $conexion ?? null;

$promo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($promo_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de promoción inválido']);
    exit;
}

try {
    // Obtener detalles de promoción
    $sql_promo = "SELECT p.id_promocion, p.nombre, p.descripcion, p.precio, p.imagen
                  FROM promocion p
                  WHERE p.id_promocion = ?";

    $stmt_promo = $db->prepare($sql_promo);
    $stmt_promo->bind_param('i', $promo_id);
    $stmt_promo->execute();
    $result_promo = $stmt_promo->get_result();

    if ($result_promo->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Promoción no encontrada']);
        exit;
    }

    $promocion = $result_promo->fetch_assoc();
    $stmt_promo->close();

    // Obtener productos de promoción
    $sql_productos = "SELECT pp.id_producto, pp.cantidad, p.nombre, p.descripcion,
                             p.precio_base, p.imagen, p.tipo
                      FROM promocion_producto pp
                      JOIN productos p ON pp.id_producto = p.id_producto
                      WHERE pp.id_promocion = ?
                      ORDER BY pp.id_producto";

    $stmt_productos = $db->prepare($sql_productos);
    $stmt_productos->bind_param('i', $promo_id);
    $stmt_productos->execute();
    $result_productos = $stmt_productos->get_result();

    $productos = [];
    while ($producto = $result_productos->fetch_assoc()) {
        // Corregir ruta de imagen para productos
        if (!empty($producto['imagen'])) {
            $producto['imagen'] = '../empleado/productos/img/' . basename($producto['imagen']);
        } else {
            $producto['imagen'] = '../img/Pizzaconmigo.png';
        }
        $productos[] = $producto;
    }
    $stmt_productos->close();


    // Obtener postres sugeridos (excluir si la promoción ya contiene postres)
    $exclude_desserts = [];
    foreach ($productos as $prod) {
        if ($prod['tipo'] === 'postre') {
            $exclude_desserts[] = $prod['id_producto'];
        }
    }
    $exclude_condition = !empty($exclude_desserts) ? "AND id_producto NOT IN (" . str_repeat('?,', count($exclude_desserts) - 1) . "?)" : "";

    $sql_postres = "SELECT id_producto, nombre, precio_base, imagen
                    FROM productos
                    WHERE tipo = 'postre' $exclude_condition
                    ORDER BY nombre
                    LIMIT 10";

    $stmt_postres = $db->prepare($sql_postres);
    if (!empty($exclude_desserts)) {
        $stmt_postres->bind_param(str_repeat('i', count($exclude_desserts)), ...$exclude_desserts);
    }
    $stmt_postres->execute();
    $result_postres = $stmt_postres->get_result();
    $postres = [];
    while ($postre = $result_postres->fetch_assoc()) {
        // Corregir ruta de imagen para postres
        if (!empty($postre['imagen'])) {
            $postre['imagen'] = '../empleado/productos/img/' . basename($postre['imagen']);
        } else {
            $postre['imagen'] = '../img/Pizzaconmigo.png';
        }
        // Ensure precio_base is cast to float
        $postre['precio_base'] = (float) $postre['precio_base'];
        error_log("Dessert price loaded: " . $postre['precio_base'] . " for " . $postre['nombre']);
        $postres[] = $postre;
    }
    $stmt_postres->close();

    // Obtener bebidas sugeridas (excluir si la promoción ya contiene bebidas)
    $exclude_drinks = [];
    foreach ($productos as $prod) {
        if ($prod['tipo'] === 'bebida') {
            $exclude_drinks[] = $prod['id_producto'];
        }
    }
    $exclude_condition = !empty($exclude_drinks) ? "AND id_producto NOT IN (" . str_repeat('?,', count($exclude_drinks) - 1) . "?)" : "";

    $sql_bebidas = "SELECT id_producto, nombre, precio_base, imagen
                    FROM productos
                    WHERE tipo = 'bebida' $exclude_condition
                    ORDER BY nombre
                    LIMIT 10";

    $stmt_bebidas = $db->prepare($sql_bebidas);
    if (!empty($exclude_drinks)) {
        $stmt_bebidas->bind_param(str_repeat('i', count($exclude_drinks)), ...$exclude_drinks);
    }
    $stmt_bebidas->execute();
    $result_bebidas = $stmt_bebidas->get_result();
    $bebidas = [];
    while ($bebida = $result_bebidas->fetch_assoc()) {
        // Corregir ruta de imagen para bebidas
        if (!empty($bebida['imagen'])) {
            $bebida['imagen'] = '../empleado/productos/img/' . basename($bebida['imagen']);
        } else {
            $bebida['imagen'] = '../img/Pizzaconmigo.png';
        }
        // Ensure precio_base is cast to float
        $bebida['precio_base'] = (float) $bebida['precio_base'];
        error_log("Drink price loaded: " . $bebida['precio_base'] . " for " . $bebida['nombre']);
        $bebidas[] = $bebida;
    }
    $stmt_bebidas->close();

    // Corregir ruta de imagen de promoción
    if (!empty($promocion['imagen'])) {
        $promocion['imagen'] = '../empleado/promociones/img/' . basename($promocion['imagen']);
    } else {
        $promocion['imagen'] = '../img/Pizzaconmigo.png';
    }

    // Formatear respuesta
    $response = [
        'id_promocion' => $promocion['id_promocion'],
        'nombre' => $promocion['nombre'],
        'descripcion' => $promocion['descripcion'],
        'precio' => floatval($promocion['precio']),
        'imagen' => $promocion['imagen'],
        'productos' => $productos,
        'postres' => $postres,
        'bebidas' => $bebidas,
        'tipo' => 'promocion'
    ];

} catch (Exception $e) {
    http_response_code(500);
    $response = ['error' => 'Error interno del servidor: ' . $e->getMessage()];
} catch (Error $e) {
    http_response_code(500);
    $response = ['error' => 'Error interno del servidor'];
}

ob_end_clean();
$json_response = json_encode($response, JSON_UNESCAPED_UNICODE);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al generar respuesta JSON: ' . json_last_error_msg()]);
} else {
    echo $json_response;
}

exit;
?>