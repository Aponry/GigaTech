<?php
$host = '127.0.0.1';      // o 'localhost'
$puerto = '3306';         // puerto de xampp (antes era 3307)

$usuario = 'root';              ##'usuariopizzeria';        // nombre de usuario de MySQL
$contrasena = '';              ##'V9p$Xz!rD7&bW#qLm@S3eT2^uNjF0yGhZwMkPvQa8RxCj';         // contraseña del usuario root de la base de datos

$base_datos = 'pizzaconmigo'; // nombre de la base de datos en phpmyadmin

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos, $puerto);

if ($conexion->connect_error) {
    // die('Error de conexión: ' . $conexion->connect_error);
} else {

}

// Función para normalizar rutas de imágenes, che, esto es para que las fotos de productos y promos se vean bien desde cualquier lado
function normalizarRutaImagen($imagen, $tipo = 'producto') {
    $imagen = (string)$imagen;
    if ($imagen === '') {
        return '/gigatech/gigatech/img/Pizzaconmigo.png';
    }
    if ($tipo === 'promocion') {
        return '../empleado/promociones/img/' . basename($imagen);
    } else {
        return '../empleado/productos/img/' . basename($imagen);
    }
}

// Función para calcular el subtotal de un ítem del carrito, boludo, suma precio base más ingredientes y extras
function calcularSubtotalItem($item) {
    $cantidad = (float)($item['cantidad'] ?? 1);
    $precioUnitario = (float)($item['precio'] ?? 0);
    $ingredientes = $item['ingredientes'] ?? [];
    $extras = $item['extras'] ?? [];

    $totalIngredientes = 0.0;
    foreach ($ingredientes as $ing) {
        $totalIngredientes += (float)($ing['precio'] ?? 0) * (float)($ing['cantidad'] ?? 0);
    }

    $totalExtras = 0.0;
    foreach ($extras as $ex) {
        $totalExtras += (float)($ex['precio'] ?? 0);
    }

    return $cantidad * $precioUnitario + $totalIngredientes + $totalExtras;
}

?>

