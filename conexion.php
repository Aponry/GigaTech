<?php
$host = '127.0.0.1';      // o 'localhost'
$puerto = '3306';         // puerto de xampp (antes era 3307)
$usuario = 'usuariopizzeria';        // nombre de usuario de MySQL
$contrasena = 'V9p$Xz!rD7&bW#qLm@S3eT2^uNjF0yGhZwMkPvQa8RxCj';         // contraseña del usuario root de la base de datos
$base_datos = 'pizzzaconmigo'; // nombre de la base de datos en phpmyadmin

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos, $puerto);

if ($conexion->connect_error) {
    die('Error de conexión: ' . $conexion->connect_error);
} else {
     
}

//conexion con la base de datos
?>

