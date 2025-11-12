<?php
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

if (!$conexion) {
    echo json_encode(['error' => 'No se pudo conectar a la base de datos']);
    exit;
}

// Obtener estructura de la tabla pedido
$result = $conexion->query('DESCRIBE pedido');
$estructura = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $estructura[] = $row;
    }
    echo json_encode($estructura);
} else {
    echo json_encode(['error' => 'No se pudo obtener la estructura de la tabla']);
}

$conexion->close();
?>