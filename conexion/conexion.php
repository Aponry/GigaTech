<?php
$host = '127.0.0.1';      // o 'localhost'
$puerto = '3307';         // puerto de xampp (antes era 3306, se cambio porque interferia con mariadb)
$usuario = 'root';        // nombre de usuario de MySQL
$contrasena = 'holasoyfran';         // contraseña del usuario root de MySQL
$base_datos = 'pizzzaconmigo'; // nombre de la base de datos en phpmyadmin

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos, $puerto);

if ($conexion->connect_error) {
    die('Error de conexión: ' . $conexion->connect_error);
} else {
    echo 'Conectado';
}
?>
