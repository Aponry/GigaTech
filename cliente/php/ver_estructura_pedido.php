<?php
require_once __DIR__ . '/../../conexion.php';

if (!$conexion) {
    die('No se pudo conectar a la base de datos');
}

// Obtener estructura de la tabla pedido
$result = $conexion->query('DESCRIBE pedido');

echo "<h2>Estructura de la tabla 'pedido'</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
}
echo "</table>";

$conexion->close();
?>