<?php
// Devuelve la respuesta como JSON
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

// Verifica conexión a la base
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'sin conexión a la base']);
    exit;
}

// Sanitiza y valida los datos del formulario
$nombre = $conexion->real_escape_string(trim($_POST['nombre'] ?? ''));
$precio_raw = $_POST['precio'] ?? '0';
$descripcion = $conexion->real_escape_string(trim($_POST['descripcion'] ?? ''));

if ($nombre === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'nombre requerido']);
    exit;
}

$precio = (float) str_replace(',', '.', $precio_raw);

// Si se sube una imagen, se guarda en /images y se registra su ruta
$imagen_db = '../img/';
if (!empty($_FILES['imagen']['name']) && isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
    $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $archivo = 'promo_' . time() . '.' . $ext;
    $dest = __DIR__ . '../img/' . $archivo;

    if (is_uploaded_file($_FILES['imagen']['tmp_name']) && move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
        $imagen_db = '../img/' . $archivo;
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'no se pudo guardar la imagen']);
        exit;
    }
}

// Inserta la promoción principal
$stmt = $conexion->prepare("INSERT INTO promocion (nombre, precio, descripcion, imagen, activo) VALUES (?, ?, ?, ?, 1)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $conexion->error]);
    exit;
}
$stmt->bind_param('sdss', $nombre, $precio, $descripcion, $imagen_db);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $stmt->error]);
    $stmt->close();
    exit;
}
$idProm = $conexion->insert_id;
$stmt->close();

// Si vienen productos asociados, se vinculan en la tabla intermedia
if (isset($_POST['productos']) && is_array($_POST['productos'])) {
    $ins = $conexion->prepare("INSERT INTO promocion_producto (id_promocion, id_producto, cantidad) VALUES (?, ?, ?)");
    if ($ins) {
        foreach ($_POST['productos'] as $idProd => $cant) {
            $idP = (int)$idProd;
            $c = (float) str_replace(',', '.', $cant);
            if ($idP > 0 && $c > 0) {
                $ins->bind_param('iid', $idProm, $idP, $c);
                $ins->execute();
            }
        }
        $ins->close();
    }
}

// Devuelve confirmación con el id nuevo
echo json_encode(['ok' => true, 'id' => $idProm]);
