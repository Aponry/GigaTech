<?php
// Suprimir salida de errores HTML para asegurar respuestas solo JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);
// Indicamos que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');
ob_clean();
ob_start();

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

try {
    // Obtenemos el ID del producto desde GET
    $id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Validamos que el ID sea válido
    if ($id_producto <= 0) {
        http_response_code(400); // Solicitud incorrecta
        echo json_encode(['error' => 'ID de producto inválido']);
        exit;
    }

    // Consulta para obtener los detalles del producto
    $sql = "SELECT productos.*
            FROM productos
            WHERE productos.id_producto = ?";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta del producto');
    }

    $stmt->bind_param('i', $id_producto);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        http_response_code(404); // No encontrado
        echo json_encode(['error' => 'Producto no encontrado']);
        $stmt->close();
        $db->close();
        exit;
    }

    $producto = $resultado->fetch_assoc();
    $stmt->close();

    // Convertimos tipos
    $producto['id_producto'] = (int) $producto['id_producto'];
    $producto['precio_base'] = (float) $producto['precio_base'];

    // Ajustamos la ruta de la imagen
    if (!empty($producto['imagen'])) {
        $producto['imagen'] = '../empleado/productos/img/' . basename($producto['imagen']);
    } else {
        $producto['imagen'] = '../img/Pizzaconmigo.png';
    }

    // Inicializamos el array de respuesta con estructura plana
    // Limpiar caracteres inválidos UTF-8 en los datos del producto
    $producto['nombre'] = mb_convert_encoding($producto['nombre'], 'UTF-8', 'UTF-8');
    $producto['tipo'] = mb_convert_encoding($producto['tipo'], 'UTF-8', 'UTF-8');
    $producto['descripcion'] = mb_convert_encoding($producto['descripcion'], 'UTF-8', 'UTF-8');

    // Obtener postres sugeridos
    $sql_postres = "SELECT id_producto, nombre, precio_base, imagen
                    FROM productos
                    WHERE tipo = 'postre'
                    ORDER BY nombre
                    LIMIT 10";

    $postres = [];
    $result_postres = $db->query($sql_postres);
    if ($result_postres) {
        while ($postre = $result_postres->fetch_assoc()) {
            // Corregir ruta de imagen para postres
            if (!empty($postre['imagen'])) {
                $postre['imagen'] = '../empleado/productos/img/' . basename($postre['imagen']);
            } else {
                $postre['imagen'] = '../img/Pizzaconmigo.png';
            }
            $postre['id_producto'] = (int) $postre['id_producto'];
            $postre['precio_base'] = (float) $postre['precio_base'];
            $postres[] = $postre;
        }
        $result_postres->close();
    }

    // Obtener bebidas sugeridas
    $sql_bebidas = "SELECT id_producto, nombre, precio_base, imagen
                    FROM productos
                    WHERE tipo = 'bebida'
                    ORDER BY nombre
                    LIMIT 10";

    $bebidas = [];
    $result_bebidas = $db->query($sql_bebidas);
    if ($result_bebidas) {
        while ($bebida = $result_bebidas->fetch_assoc()) {
            // Corregir ruta de imagen para bebidas
            if (!empty($bebida['imagen'])) {
                $bebida['imagen'] = '../empleado/productos/img/' . basename($bebida['imagen']);
            } else {
                $bebida['imagen'] = '../img/Pizzaconmigo.png';
            }
            $bebida['id_producto'] = (int) $bebida['id_producto'];
            $bebida['precio_base'] = (float) $bebida['precio_base'];
            $bebidas[] = $bebida;
        }
        $result_bebidas->close();
    }

    $respuesta = [
        'id' => $producto['id_producto'],
        'nombre' => $producto['nombre'],
        'tipo' => $producto['tipo'],
        'descripcion' => $producto['descripcion'],
        'precio_base' => $producto['precio_base'],
        'imagen' => $producto['imagen'],
        'ingredientes' => [],
        'postres' => $postres,
        'bebidas' => $bebidas,
        'relacionados' => []
    ];

    // Si el producto permite ingredientes, obtenemos los ingredientes disponibles
    if (isset($producto['permitir_ingredientes']) && $producto['permitir_ingredientes'] == 1) {
        $tipo = $producto['tipo'];
        $sql_ingredientes = "SELECT id_ingrediente, nombre, costo
                            FROM ingrediente
                            WHERE tipo_producto = ?
                            ORDER BY nombre";

        $stmt_ing = $db->prepare($sql_ingredientes);
        if ($stmt_ing) {
            $stmt_ing->bind_param('s', $tipo);
            $stmt_ing->execute();
            $res_ing = $stmt_ing->get_result();

            while ($ing = $res_ing->fetch_assoc()) {
                $ing['id_ingrediente'] = (int) $ing['id_ingrediente'];
                $ing['costo'] = (float) $ing['costo'];
                // Limpiar caracteres inválidos UTF-8 en ingredientes
                $ing['nombre'] = mb_convert_encoding($ing['nombre'], 'UTF-8', 'UTF-8');
                $respuesta['ingredientes'][] = $ing;
            }
            $stmt_ing->close();
        }
    }

    // Devolvemos la respuesta en formato JSON sin escapar unicode
    ob_end_clean();
    $json_response = json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al generar respuesta JSON: ' . json_last_error_msg()]);
    } else {
        echo $json_response;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

$db->close();
exit;
?>