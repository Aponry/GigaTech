<?php
// Respuesta JSON
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

// Si no hay conexión, corta
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'sin conexión']);
    exit;
}

// Toma el id de la promoción
$id = isset($_POST['id_promocion']) ? (int) $_POST['id_promocion'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'id inválido']);
    exit;
}

// Si solo se cambia el estado del checkbox "activo"
if (isset($_POST['activo']) && !isset($_POST['nombre']) && !isset($_POST['precio']) && !isset($_FILES['imagen'])) {
    $activo = $_POST['activo'] ? 1 : 0;
    $q = $conexion->prepare("UPDATE promocion SET activo = ? WHERE id_promocion = ?");
    if (!$q) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $conexion->error]);
        exit;
    }
    $q->bind_param('ii', $activo, $id);
    if ($q->execute())
        echo json_encode(['ok' => true]);
    else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $q->error]);
    }
    $q->close();
    exit;
}

// Toma y limpia los datos del formulario
$nombre = $conexion->real_escape_string(trim($_POST['nombre'] ?? ''));
$precio_raw = $_POST['precio'] ?? '0';
$descripcion = $conexion->real_escape_string(trim($_POST['descripcion'] ?? ''));
$precio = (float) str_replace(',', '.', $precio_raw);

// Si no hay nombre, error
if ($nombre === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'nombre requerido']);
    exit;
}

// Si se sube imagen nueva, la guarda y actualiza ruta
$imagen_db = '../empleado/promociones/img/';
if (!empty($_FILES['imagen']['name']) && isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
    $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $archivo = 'promo_' . time() . '.' . $ext;
    $dest = __DIR__ . '/img/' . $archivo;

    // Verificar si la imagen se mueve correctamente
    if (!(is_uploaded_file($_FILES['imagen']['tmp_name']) && move_uploaded_file($_FILES['imagen']['tmp_name'], $dest))) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'no se pudo mover la imagen']);
        exit;
    }

    // Actualiza con imagen nueva
    $imagen_db = '../empleado/promociones/img/' . $archivo;
    $stmt = $conexion->prepare("UPDATE promocion SET nombre = ?, precio = ?, descripcion = ?, imagen = ? WHERE id_promocion = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $conexion->error]);
        exit;
    }
    $stmt->bind_param('sdssi', $nombre, $precio, $descripcion, $imagen_db, $id);
} else {
    // Actualiza sin tocar la imagen
    $stmt = $conexion->prepare("UPDATE promocion SET nombre = ?, precio = ?, descripcion = ? WHERE id_promocion = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $conexion->error]);
        exit;
    }
    $stmt->bind_param('sdsi', $nombre, $precio, $descripcion, $id);
}

// Ejecuta el update general
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $stmt->error]);
    $stmt->close();
    exit;
}
$stmt->close();

// Limpia productos anteriores de la promoción
$conexion->query("DELETE FROM promocion_producto WHERE id_promocion = " . $id);

// Inserta los nuevos productos asociados
if (isset($_POST['productos']) && is_array($_POST['productos'])) {
    $ins = $conexion->prepare("INSERT INTO promocion_producto (id_promocion, id_producto, cantidad) VALUES (?, ?, ?)");
    if ($ins) {
        foreach ($_POST['productos'] as $idProd => $cant) {
            $idP = (int) $idProd;
            $c = (float) str_replace(',', '.', $cant);
            if ($idP > 0 && $c > 0) {
                $ins->bind_param('iid', $id, $idP, $c);
                $ins->execute();
            }
        }
        $ins->close();
    }
}

// Todo correcto
echo json_encode(['ok' => true]);
