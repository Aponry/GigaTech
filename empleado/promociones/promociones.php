<?php
// promociones.php - página para listar, crear y editar promociones

require_once __DIR__ . '/../../conexion.php'; // Trae la conexión a la DB, ojo que si falla el require rompe todo

// Trae todos los productos para usarlos en los formularios de promo
$productos = [];
if ($conexion) { // si la conexión existe
    $r = $conexion->query("SELECT id_producto, nombre FROM productos ORDER BY nombre"); // pedimos id y nombre de todos los productos
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $productos[] = $row; // guardamos cada producto como array asociativo
        }
        $r->close(); // liberamos memoria del resultado
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Promociones</title>
    <link rel="stylesheet" href="style.css"> 
</head>

<body>
    <!-- Encabezado principal -->
    <header class="encabezado-principal" role="banner">
        <h1 class="titulo-pagina">Promociones</h1>
    </header>

    <!-- Fila superior con botones y búsqueda -->
    <section class="top-row" aria-label="Controles y búsqueda">
        <div class="controles-izquierda" role="group">
            <button id="volver" type="button">Volver</button>  
            <button id="agregar" type="button">Agregar</button> 
            <button id="ver" type="button">Ver</button>        
        </div>

        <!-- Campo de búsqueda + limpiar -->
        <div class="controles-busqueda" role="search">
            <input id="buscarInput" type="search" placeholder="Buscar por ID o nombre...">
            <button id="limpiarFiltros" type="button" title="Limpiar filtros">Limpiar</button>
        </div>
    </section>

    <!-- Contenedor principal dinámico -->
    <main>
        <div id="formulario" aria-live="polite"></div> 
        <div id="lista" aria-live="polite"></div>      
    </main>

    <!-- Pasamos los productos a JS como JSON, así no hace otra consulta -->
    <script id="productos-json" type="application/json"><?= json_encode($productos, JSON_UNESCAPED_UNICODE) ?></script>
    <script src="promociones.js" defer></script> <!-- Toda la lógica de interacción queda en JS -->
</body>
</html>
