<?php
// Script para agregar la columna metodo_pago a la tabla pedido
require_once 'conexion.php';

if (!$conexion) {
    die('Error de conexión: ' . mysqli_connect_error());
}

// Verificar si la columna existe
$checkQuery = "SHOW COLUMNS FROM pedido LIKE 'metodo_pago'";
$result = $conexion->query($checkQuery);

if ($result && $result->num_rows == 0) {
    // Agregar la columna
    $alterQuery = "ALTER TABLE pedido ADD COLUMN metodo_pago VARCHAR(50) NOT NULL DEFAULT '' AFTER notas";
    if ($conexion->query($alterQuery)) {
        echo "Columna 'metodo_pago' agregada exitosamente.\n";
    } else {
        echo "Error al agregar columna: " . $conexion->error . "\n";
    }
} else {
    echo "La columna 'metodo_pago' ya existe.\n";
}

$conexion->close();
?>