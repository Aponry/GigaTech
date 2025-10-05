<?php
// Indicamos que la respuesta será JSON
header('Content-Type: application/json');

// Incluir la conexión a la base de datos
require_once __DIR__ . '/../../conexion.php';

// Respuesta por defecto en caso de error
$respuesta = ['ok' => false, 'error' => 'Datos inválidos o incompletos.'];

// Verificamos que la solicitud sea POST y que la conexión esté establecida
$db = $conexion ?? null;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$db) {
    http_response_code(400); // Respuesta por error de solicitud
    echo json_encode($respuesta);
    exit;
}

// Recibimos los datos desde el formulario (POST)
$id_producto = filter_var($_POST['id_producto'] ?? 0, FILTER_VALIDATE_INT); // ID del producto
$nombre = trim($_POST['nombre'] ?? ''); // Nombre del producto
$tipo = strtolower(trim($_POST['tipo'] ?? '')); // Tipo de producto
$precio_base = filter_var($_POST['precio_base'] ?? 0, FILTER_VALIDATE_FLOAT); // Precio base
$descripcion = trim($_POST['descripcion'] ?? ''); // Descripción del producto

// Validamos los datos requeridos
$permitir_ingredientes = 0; // Por defecto no se permiten ingredientes
if (isset($_POST['permitir_ingredientes']) && in_array($tipo, ['pizza', 'hamburguesa'])) {
    $permitir_ingredientes = 1; // Si es tipo pizza o hamburguesa, se permiten ingredientes
}

// Si falta alguno de los campos obligatorios, se devuelve un error
if ($id_producto <= 0 || empty($nombre) || $precio_base <= 0 || empty($tipo)) {
    echo json_encode(['ok' => false, 'error' => 'Faltan datos obligatorios (ID, nombre, tipo, precio).']);
    exit;
}

// Consultamos el producto actual para manejar su imagen
$stmt_select = $db->prepare("SELECT imagen FROM productos WHERE id_producto = ?");
$stmt_select->bind_param("i", $id_producto);
$stmt_select->execute();
$producto_actual = $stmt_select->get_result()->fetch_assoc();
$stmt_select->close();

// Si ya existía una imagen, la guardamos para usarla si no suben una nueva
$ruta_imagen_final = $producto_actual['imagen'] ?? null;

// Si suben una nueva imagen...
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    // Eliminamos la imagen vieja si existe
    if (!empty($ruta_imagen_final) && file_exists(__DIR__ . '/../../' . $ruta_imagen_final)) {
        unlink(__DIR__ . '/../../' . $ruta_imagen_final);
    }

    // Definimos el directorio para las imágenes subidas
    $directorio_uploads = __DIR__ . '/../../uploads/productos/';
    if (!is_dir($directorio_uploads)) {
        mkdir($directorio_uploads, 0777, true); // Si no existe, lo creamos
    }

    // Generamos un nombre único para la imagen
    $nombre_archivo = uniqid('prod_', true) . '.' . strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    $ruta_completa = $directorio_uploads . $nombre_archivo;

    // Movemos la imagen al directorio de uploads
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_completa)) {
        $ruta_imagen_final = 'uploads/productos/' . $nombre_archivo; // Guardamos la ruta final de la imagen
    }
}

// Preparamos la consulta SQL para actualizar el producto
$sql = "UPDATE productos SET nombre = ?, tipo = ?, precio_base = ?, descripcion = ?, permitir_ingredientes = ?, imagen = ? WHERE id_producto = ?";
$stmt_update = $db->prepare($sql);
$stmt_update->bind_param("ssdsisi", $nombre, $tipo, $precio_base, $descripcion, $permitir_ingredientes, $ruta_imagen_final, $id_producto);

// Ejecutamos la consulta
if ($stmt_update->execute()) {
    $respuesta = ['ok' => true]; // Si se actualizó correctamente
} else {
    http_response_code(500); // Si hubo un error en la base de datos
    $respuesta['error'] = 'Error en la base de datos al actualizar: ' . $stmt_update->error;
}

// Cerramos la consulta y la conexión
$stmt_update->close();
$db->close();

// Devolvemos la respuesta en formato JSON
echo json_encode($respuesta);
