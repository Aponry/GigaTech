<?php
// Habilitar reporte de errores para diagnóstico
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores directamente para no romper el JSON
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
ob_clean();
ob_start();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Incluir conexión a la base de datos
require_once __DIR__ . '/../../conexion.php';
$db = $conexion ?? null;

$action = $_POST['action'] ?? $_GET['action'] ?? 'get';

try {
    switch ($action) {
        case 'add':
            $id_producto = (int) ($_POST['id_producto'] ?? 0);
            $cantidad = (int) ($_POST['cantidad'] ?? 1);
            $ingredientes = isset($_POST['ingredientes']) ? json_decode($_POST['ingredientes'], true) : [];
            $postres = isset($_POST['postres']) ? json_decode($_POST['postres'], true) : [];
            $bebidas = isset($_POST['bebidas']) ? json_decode($_POST['bebidas'], true) : [];

            if ($id_producto <= 0) {
                throw new Exception('ID de producto inválido');
            }

            // Validar límite de ingredientes para el producto principal
            $tipo_producto = getProductType($id_producto, $db);
            $limite_ingredientes = 10; // Default
            if ($tipo_producto === 'pizza') {
                $limite_ingredientes = 4;
            } elseif ($tipo_producto === 'hamburguesa') {
                $limite_ingredientes = 8;
            }

            $total_ingredientes = 0;
            foreach ($ingredientes as $ing) {
                $total_ingredientes += $ing['cantidad'];
            }
            if ($total_ingredientes > $limite_ingredientes) {
                throw new Exception("Máximo $limite_ingredientes ingredientes permitidos por producto");
            }

            // Agregar producto principal al carrito (siempre crear nueva entrada para productos personalizados)
            addProductToCart($id_producto, $cantidad, $ingredientes, $db, true);

            // Agregar postres seleccionados al carrito
            foreach ($postres as $postre) {
                if ($postre['cantidad'] > 0) {
                    addProductToCart($postre['id_producto'], $postre['cantidad'], [], $db);
                }
            }

            // Agregar bebidas seleccionadas al carrito
            foreach ($bebidas as $bebida) {
                if ($bebida['cantidad'] > 0) {
                    addProductToCart($bebida['id_producto'], $bebida['cantidad'], [], $db);
                }
            }

            $response = [
                'success' => true,
                'cartCount' => array_sum(array_column($_SESSION['carrito'], 'cantidad')),
                'message' => 'Producto y complementos agregados al carrito'
            ];
            break;

        case 'add_promo':
            $id_promocion = (int) ($_POST['id_promocion'] ?? 0);
            $cantidad = (int) ($_POST['cantidad'] ?? 1);
            $postres = isset($_POST['postres']) ? json_decode($_POST['postres'], true) : [];
            $bebidas = isset($_POST['bebidas']) ? json_decode($_POST['bebidas'], true) : [];

            if ($id_promocion <= 0 || $cantidad <= 0) {
                throw new Exception('ID de promoción o cantidad inválida');
            }

            // Add promotion to cart
            addPromotionToCart($id_promocion, $cantidad, $db);

            // Add selected desserts to cart
            foreach ($postres as $postre) {
                if ($postre['cantidad'] > 0) {
                    addProductToCart($postre['id_producto'], $postre['cantidad'], [], $db);
                }
            }

            // Add selected drinks to cart
            foreach ($bebidas as $bebida) {
                if ($bebida['cantidad'] > 0) {
                    addProductToCart($bebida['id_producto'], $bebida['cantidad'], [], $db);
                }
            }

            $response = [
                'success' => true,
                'cartCount' => array_sum(array_column($_SESSION['carrito'], 'cantidad')),
                'message' => 'Promoción y complementos agregados al carrito'
            ];
            break;

        case 'add_single':
            // Lógica original para agregar producto único
            $id_producto = (int) ($_POST['id_producto'] ?? 0);
            $cantidad = (int) ($_POST['cantidad'] ?? 1);
            $ingredientes = isset($_POST['ingredientes']) ? json_decode($_POST['ingredientes'], true) : [];
            $force_new = false; // Definir la variable force_new

            if ($id_producto <= 0 || $cantidad <= 0) {
                throw new Exception('ID de producto o cantidad inválida');
            }

            // Obtener tipo de producto para determinar límite de ingredientes
            $sql_tipo = "SELECT tipo FROM productos WHERE id_producto = ?";
            $stmt_tipo = $db->prepare($sql_tipo);
            if (!$stmt_tipo) {
                throw new Exception('Error al preparar la consulta de tipo de producto: ' . $db->error);
            }
            $stmt_tipo->bind_param('i', $id_producto);
            $stmt_tipo->execute();
            $resultado_tipo = $stmt_tipo->get_result();
            $tipo_producto = $resultado_tipo->fetch_assoc()['tipo'] ?? '';
            $stmt_tipo->close();

            // Determinar límite de ingredientes basado en el tipo de producto
            $limite_ingredientes = 10; // Default
            if ($tipo_producto === 'pizza') {
                $limite_ingredientes = 4;
            } elseif ($tipo_producto === 'hamburguesa') {
                $limite_ingredientes = 8;
            }

            // Validar límite de ingredientes
            $total_ingredientes = 0;
            foreach ($ingredientes as $ing) {
                $total_ingredientes += $ing['cantidad'];
            }
            if ($total_ingredientes > $limite_ingredientes) {
                throw new Exception("Máximo $limite_ingredientes ingredientes permitidos por producto");
            }

            // Crear una clave única para ingredientes
            $ing_key = '';
            if (!empty($ingredientes)) {
                usort($ingredientes, function($a, $b) {
                    return $a['id_ingrediente'] <=> $b['id_ingrediente'];
                });
                $ing_key = json_encode($ingredientes);
            }

            // Verificar si existe un elemento con el mismo producto e ingredientes
            // Si force_new es verdadero, siempre crear nueva entrada (para productos personalizados)
            $found = false;
            if (!$force_new) {
                foreach ($_SESSION['carrito'] as &$item) {
                    if (isset($item['id_producto']) && $item['id_producto'] == $id_producto && isset($item['ing_key']) && $item['ing_key'] == $ing_key) {
                        $item['cantidad'] += $cantidad;
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found || $force_new) {
                $_SESSION['carrito'][] = [
                    'id_producto' => $id_producto,
                    'cantidad' => $cantidad,
                    'ingredientes' => $ingredientes,
                    'ing_key' => $ing_key,
                    'timestamp' => time()
                ];
            }

            $response = [
                'success' => true,
                'cartCount' => array_sum(array_column($_SESSION['carrito'], 'cantidad')),
                'message' => 'Producto agregado al carrito',
                'added_product' => $id_producto
            ];
            break;

        case 'update':
            $index = (int) ($_POST['index'] ?? -1);
            $cantidad = (int) ($_POST['cantidad'] ?? 1);

            if ($index < 0 || $cantidad <= 0 || !isset($_SESSION['carrito'][$index])) {
                throw new Exception('Índice o cantidad inválida');
            }

            $_SESSION['carrito'][$index]['cantidad'] = $cantidad;

            $response = [
                'success' => true,
                'message' => 'Cantidad actualizada'
            ];
            break;

        case 'remove':
            $index = (int) ($_POST['index'] ?? -1);

            if ($index < 0 || !isset($_SESSION['carrito'][$index])) {
                throw new Exception('Índice inválido');
            }

            array_splice($_SESSION['carrito'], $index, 1);

            $response = [
                'success' => true,
                'message' => 'Producto removido'
            ];
            break;

        default:
            // Obtener carrito con detalles completos de productos
            $items = [];
            $total = 0.0;
            $subtotal = 0.0;
            $discounts = 0.0;
            $taxes = 0.0;

            if ($db && !empty($_SESSION['carrito'])) {
                // Obtener todos los IDs de productos
                $product_ids = array_column(array_filter($_SESSION['carrito'], function($item) {
                    return !isset($item['id_promocion']);
                }), 'id_producto');
                
                if (!empty($product_ids)) {
                    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';

                    // Obtener detalles de productos
                    $sql = "SELECT id_producto, nombre, precio_base, imagen, descripcion, tipo FROM productos WHERE id_producto IN ($placeholders)";
                    $stmt = $db->prepare($sql);
                    if (!$stmt) {
                        throw new Exception('Error al preparar la consulta de productos: ' . $db->error);
                    }
                    $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    $productos = [];
                    while ($row = $result->fetch_assoc()) {
                        $productos[$row['id_producto']] = $row;
                    }
                    $stmt->close();
                }

                // Construir elementos del carrito
                foreach ($_SESSION['carrito'] as $index => $cart_item) {
                    if (isset($cart_item['id_promocion'])) {
                        // Manejar elementos de promoción
                        $id_promo = $cart_item['id_promocion'];
                        $qty = $cart_item['cantidad'];

                        // Obtener detalles de promoción
                        $sql_promo_detail = "SELECT nombre, descripcion, imagen FROM promocion WHERE id_promocion = ?";
                        $stmt_promo_detail = $db->prepare($sql_promo_detail);
                        if (!$stmt_promo_detail) {
                            throw new Exception('Error al preparar la consulta de promoción: ' . $db->error);
                        }
                        $stmt_promo_detail->bind_param('i', $id_promo);
                        $stmt_promo_detail->execute();
                        $promo_result = $stmt_promo_detail->get_result();
                        $promo_data = $promo_result->fetch_assoc();
                        $stmt_promo_detail->close();

                        if ($promo_data) {
                            $subtotal_item = $cart_item['precio_unitario'] * $qty;
                            $subtotal += $subtotal_item;
                            $total += $subtotal_item;

                            // Corregir ruta de imagen
                            $imagen = $promo_data['imagen'];
                            if (!empty($imagen)) {
                                $imagen = '../empleado/promociones/img/' . basename($imagen);
                            } else {
                                $imagen = '../img/Pizzaconmigo.png';
                            }

                            $items[] = [
                                'id_promocion' => $id_promo,
                                'nombre' => $promo_data['nombre'],
                                'descripcion' => $promo_data['descripcion'],
                                'tipo' => 'promocion',
                                'precio_unitario' => $cart_item['precio_unitario'],
                                'cantidad' => $qty,
                                'subtotal' => $subtotal_item,
                                'imagen' => $imagen,
                                'ingredientes' => [],
                                'ing_total' => 0,
                                'extras' => [],
                                'extras_total' => 0
                            ];
                        }
                    } else {
                        // Manejar elementos de producto regular
                        $id = $cart_item['id_producto'];
                        $qty = $cart_item['cantidad'];
                        $ingredientes_data = $cart_item['ingredientes'] ?? [];

                        if (isset($productos[$id])) {
                            $prod = $productos[$id];
                            $precio_unitario = $prod['precio_base'];

                            // Calculate ingredient total
                            $ing_total = 0;
                            foreach ($ingredientes_data as $ing) {
                                $ing_total += $ing['precio'] * $ing['cantidad'];
                            }

                            $subtotal_item = ($precio_unitario + $ing_total) * $qty;
                            $subtotal += $subtotal_item;
                            $total += $subtotal_item;

                            // Corregir ruta de imagen
                            $imagen = $prod['imagen'];
                            if (!empty($imagen)) {
                                $imagen = '../empleado/productos/img/' . basename($imagen);
                            } else {
                                $imagen = '../img/Pizzaconmigo.png';
                            }

                            $items[] = [
                                'id_producto' => $id,
                                'nombre' => $prod['nombre'],
                                'descripcion' => $prod['descripcion'],
                                'tipo' => $prod['tipo'],
                                'precio_unitario' => $precio_unitario,
                                'cantidad' => $qty,
                                'subtotal' => $subtotal_item,
                                'imagen' => $imagen,
                                'ingredientes' => $ingredientes_data,
                                'ing_total' => $ing_total,
                                'extras' => [],
                                'extras_total' => 0
                            ];
                        }
                    }
                }
            }

            $response = [
                'count' => array_sum(array_column($_SESSION['carrito'], 'cantidad')),
                'items' => $items,
                'subtotal' => $subtotal,
                'discounts' => $discounts,
                'taxes' => $taxes,
                'total' => $total
            ];
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    $response = ['error' => $e->getMessage()];
} catch (Error $e) {
    http_response_code(500);
    $response = ['error' => 'Error interno del servidor: ' . $e->getMessage()];
}

ob_end_clean();
$json_response = json_encode($response, JSON_UNESCAPED_UNICODE);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al generar respuesta JSON: ' . json_last_error_msg()]);
} else {
    echo $json_response;
}

function getProductType($id_producto, $db) {
    if (!$db) {
        return '';
    }
    
    $sql = "SELECT tipo FROM productos WHERE id_producto = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return '';
    }
    $stmt->bind_param('i', $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();
    $tipo = $result->fetch_assoc()['tipo'] ?? '';
    $stmt->close();
    return $tipo;
}

function addPromotionToCart($id_promocion, $cantidad, $db) {
    if (!$db) {
        throw new Exception('No hay conexión a la base de datos');
    }
    
    // Obtener detalles de promoción
    $sql_promo = "SELECT nombre, precio FROM promocion WHERE id_promocion = ?";
    $stmt_promo = $db->prepare($sql_promo);
    if (!$stmt_promo) {
        throw new Exception('Error al preparar la consulta de promoción: ' . $db->error);
    }
    $stmt_promo->bind_param('i', $id_promocion);
    $stmt_promo->execute();
    $result_promo = $stmt_promo->get_result();

    if ($result_promo->num_rows === 0) {
        throw new Exception('Promoción no encontrada');
    }

    $promocion = $result_promo->fetch_assoc();
    $stmt_promo->close();

    // Crear clave única para promoción
    $promo_key = 'promo_' . $id_promocion;

    // Verificar si la promoción ya existe en el carrito
    $found = false;
    foreach ($_SESSION['carrito'] as &$item) {
        if (isset($item['promo_key']) && $item['promo_key'] == $promo_key) {
            $item['cantidad'] += $cantidad;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['carrito'][] = [
            'id_promocion' => $id_promocion,
            'nombre' => $promocion['nombre'],
            'precio_unitario' => $promocion['precio'],
            'cantidad' => $cantidad,
            'promo_key' => $promo_key,
            'tipo' => 'promocion',
            'timestamp' => time()
        ];
    }
}

function addProductToCart($id_producto, $cantidad, $ingredientes, $db, $force_new = false) {
    if (!$db) {
        throw new Exception('No hay conexión a la base de datos');
    }
    
    // Obtener tipo de producto para determinar límite de ingredientes
    $tipo_producto = getProductType($id_producto, $db);

    // Determinar límite de ingredientes basado en el tipo de producto
    $limite_ingredientes = 10; // Default
    if ($tipo_producto === 'pizza') {
        $limite_ingredientes = 4;
    } elseif ($tipo_producto === 'hamburguesa') {
        $limite_ingredientes = 8;
    }

    // Validar límite de ingredientes
    $total_ingredientes = 0;
    foreach ($ingredientes as $ing) {
        $total_ingredientes += $ing['cantidad'];
    }
    if ($total_ingredientes > $limite_ingredientes) {
        throw new Exception("Máximo $limite_ingredientes ingredientes permitidos por producto");
    }

    // Crear una clave única para ingredientes
    $ing_key = '';
    if (!empty($ingredientes)) {
        usort($ingredientes, function($a, $b) {
            return $a['id_ingrediente'] <=> $b['id_ingrediente'];
        });
        $ing_key = json_encode($ingredientes);
    }

    // Verificar si existe un elemento con el mismo producto e ingredientes
    // Si force_new es verdadero, siempre crear nueva entrada (para productos personalizados)
    $found = false;
    if (!$force_new) {
        foreach ($_SESSION['carrito'] as &$item) {
            if (isset($item['id_producto']) && $item['id_producto'] == $id_producto && isset($item['ing_key']) && $item['ing_key'] == $ing_key) {
                $item['cantidad'] += $cantidad;
                $found = true;
                break;
            }
        }
    }
    
    if (!$found || $force_new) {
        $_SESSION['carrito'][] = [
            'id_producto' => $id_producto,
            'cantidad' => $cantidad,
            'ingredientes' => $ingredientes,
            'ing_key' => $ing_key,
            'timestamp' => time()
        ];
    }
}

exit;
?>