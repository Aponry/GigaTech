<?php
include 'conexion.php';

$nombre = $_POST['nombre'];
$tipo = $_POST['tipo'];
$precio_base = $_POST['precio_base'];

$sql = "INSERT INTO producto (nombre, precio_base, tipo) VALUES ('$nombre', '$precio_base', '$tipo')";

if ($conexion->query($sql) === TRUE) {
    echo "Producto guardado.";
} else {
    echo "Error: " . $conexion->error;
}

$conexion->close();

//Sript para insetar productos(temporal)

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
<a href="tabladeproductos.php" class="text-sm text-blue-600 hover:underline">Volver atras</a>

</body>
</html>