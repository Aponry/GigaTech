<?php
include 'conexion.php';

if (isset($_POST['id'])) {
  $id = $_POST['id'];
  $sql = "DELETE FROM producto WHERE id_producto = $id";
  $conexion->query($sql);
}

$conexion->close();
header("Location: listar.php");
exit;

//Script para eliminar productos(Temporal)
