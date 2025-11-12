<?php
session_start();
if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'empleado') {
    die('Acceso denegado');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel de Administración</title>
<link rel="icon" href="../img/logo-removebg-preview.ico">
<!-- Fuentes y preconnect mínimo para rendimiento -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<?php $vCss = file_exists(__DIR__ . '/../css/modal.css') ? filemtime(__DIR__ . '/../css/modal.css') : time(); ?>
<link rel="stylesheet" href="../css/modal.css?v=<?= $vCss ?>">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="menu.css">
</head>
<body>

<h1>Panel de Administración</h1>

<nav>
    <ul>
        <!-- Sección de productos -->
        <li><a href="productos/productos.php">Productos</a></li>
        <!-- Sección de promociones -->
        <li><a href="promociones/promociones.php">Promociones</a></li>
        <!-- Sección de ingredientes -->
        <li><a href="ingredientes/ingredientes.php">Ingredientes</a></li>
        <!-- Sección de clientes -->
        <li><a href="clientes/clientes.php">Clientes</a></li>
        <!-- Sección de pedidos -->
        <li><a href="pedidos/pedidos.php">Pedidos</a></li>
        <!-- Sección de facturas -->
        <li><a href="facturas.php">Facturas</a></li>
        <!-- Sección de stock -->
        <li><a href="stock/stock.php">Stock</a></li>
        <!-- Sección de estadísticas -->
        <li><a href="estadisticas/estadisticas.php">Estadísticas</a></li>
    </ul>
</nav>

<hr>

<p>Seleccioná una sección del menú para empezar.</p>

</body>
</html>