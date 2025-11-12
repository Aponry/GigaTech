<?php
// Script para actualizar la estructura de la tabla pedido
require_once 'conexion.php';

if (!$conexion) {
    die('Error de conexión a la base de datos');
}

// Verificar si los campos ya existen
$checkColumnsQuery = "SHOW COLUMNS FROM pedido LIKE '%cliente'";
$result = $conexion->query($checkColumnsQuery);

$existingColumns = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $existingColumns[] = $row['Field'];
    }
}

$columnsExist = true;
$requiredColumns = ['nombre_cliente', 'email_cliente', 'telefono_cliente', 'direccion_cliente', 'notas'];
foreach ($requiredColumns as $column) {
    if (!in_array($column, $existingColumns)) {
        $columnsExist = false;
        break;
    }
}

if (!$columnsExist) {
    // Agregar las columnas necesarias
    $alterTableQuery = "ALTER TABLE pedido 
                        ADD COLUMN nombre_cliente VARCHAR(100) NOT NULL DEFAULT '' AFTER total,
                        ADD COLUMN email_cliente VARCHAR(100) NOT NULL DEFAULT '' AFTER nombre_cliente,
                        ADD COLUMN telefono_cliente VARCHAR(20) NOT NULL DEFAULT '' AFTER email_cliente,
                        ADD COLUMN direccion_cliente TEXT AFTER telefono_cliente,
                        ADD COLUMN notas TEXT AFTER direccion_cliente";

    if ($conexion->query($alterTableQuery)) {
        echo "Tabla 'pedido' actualizada exitosamente.\n";
    } else {
        echo "Error al actualizar la tabla 'pedido': " . $conexion->error . "\n";
    }
} else {
    echo "La tabla 'pedido' ya tiene las columnas necesarias.\n";
}

$conexion->close();
?>