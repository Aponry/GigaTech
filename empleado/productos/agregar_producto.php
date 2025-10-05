<?php
// Indicamos que la respuesta será en formato JSON
header('Content-Type: application/json; charset=utf-8');

// Incluimos la conexión a la base de datos
require_once __DIR__ . '/../../conexion.php';

$respuesta = ['ok' => false, 'error' => 'Datos inválidos o incompletos.'];
$db = $conexion ?? null;

// Verificamos que sea una solicitud POST y que la conexión a la base de datos esté establecida
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$db) {
    http_response_code(400); // Si no es POST, devolvemos error 400
    echo json_encode($respuesta);
    exit;
}

// Recibimos los datos del formulario
$nombre = trim($_POST['nombre'] ?? ''); // Nombre del producto
$tipo = strtolower(trim($_POST['tipo'] ?? '')); // Tipo de producto (convertimos a minúsculas)
$precio_base = filter_var($_POST['precio_base'] ?? 0, FILTER_VALIDATE_FLOAT); // Precio base del producto
$descripcion = trim($_POST['descripcion'] ?? ''); // Descripción del producto

// Control para que solo las pizzas y hamburguesas puedan tener ingredientes
$permitir_ingredientes = (isset($_POST['permitir_ingredientes']) && in_array($tipo, ['pizza', 'hamburguesa'])) ? 1 : 0;

// Validamos que los campos obligatorios estén completos
if (empty($nombre) || $precio_base <= 0 || empty($tipo)) {
    echo json_encode(['ok' => false, 'error' => 'Nombre, tipo y precio son obligatorios.']);
    exit;
}

// Ruta donde se guardarán las imágenes
$ruta_para_db = null;
$directorio_img = __DIR__ . '/img/'; // Carpeta para almacenar las imágenes

// Verificamos si hay una imagen subida
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    // Nos aseguramos de que el directorio exista
    if (!is_dir($directorio_img)) {
        mkdir($directorio_img, 0777, true); // Creamos el directorio si no existe
    }

    // Generamos un nombre único para la imagen
    $nombre_archivo = uniqid('prod_', true) . '.' . strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    $ruta_completa = $directorio_img . $nombre_archivo;

    // Movemos la imagen a la carpeta de destino
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_completa)) {
        // Guardamos la ruta relativa de la imagen en la base de datos
        $ruta_para_db = 'img/' . $nombre_archivo;
    }
}

// Preparamos la consulta para insertar el producto
$sql = "INSERT INTO productos (nombre, tipo, precio_base, descripcion, permitir_ingredientes, imagen) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $db->prepare($sql);
$stmt->bind_param("ssdsis", $nombre, $tipo, $precio_base, $descripcion, $permitir_ingredientes, $ruta_para_db);

// Ejecutamos la consulta
if ($stmt->execute()) {
    $respuesta = ['ok' => true]; // Si la inserción fue exitosa
} else {
    http_response_code(500); // Error en la base de datos
    $respuesta['error'] = 'Error al agregar en la base de datos.';
}

// Cerramos la sentencia y la conexión
$stmt->close();
$db->close();

// Devolvemos la respuesta en formato JSON
echo json_encode($respuesta);
