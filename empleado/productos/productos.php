<?php
require_once __DIR__ . '/../../conexion.php';
$db = $conexion ?? null;

// Pedimos las columnas de la tabla “productos” y buscamos la que se llama “tipo”
$consultaColumnas = $db ? $db->query("SHOW COLUMNS FROM productos LIKE 'tipo'") : false;
$filaColumna = $consultaColumnas ? $consultaColumnas->fetch_assoc() : null;

$tiposDisponibles = [];
if (!empty($filaColumna['Type'])) {
  // Extraemos los valores del ENUM usando una expresión regular, por ejemplo: enum('pizza','bebida') → ['pizza', 'bebida']
  preg_match_all("/'([^']+)'/", $filaColumna['Type'], $coincidencias);
  $tiposDisponibles = $coincidencias[1] ?? [];
}

// Si no encontramos tipos en la base, definimos “otro” por defecto
if (empty($tiposDisponibles)) {
  $tiposDisponibles = ['otro'];
}
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <title>Productos</title>
</head>

<body>
  <h1>Productos</h1>

  <div class="top-row">
    <div class="left-controls">
      <!-- Botones principales del panel -->
      <button id="volver" type="button">Volver</button>
      <button id="btnAgregar" type="button">Agregar producto</button>
      <button id="btnVer" type="button">Ver</button>
    </div>

    <div class="search-controls" role="search" aria-label="Buscar productos">
      <!-- Campo de texto para buscar -->
      <input id="searchInput" type="search" placeholder="Buscar por ID, nombre o producto..."
        aria-label="Buscar por ID, nombre o producto">

      <!-- Filtro por tipo de producto (pizza, bebida, etc.) -->
      <select id="filterTipo" aria-label="Filtrar por tipo">
        <option value="">Todos los tipos</option>
        <?php foreach ($tiposDisponibles as $tipo): ?>
          <option value="<?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars(ucfirst($tipo), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Filtro para mostrar los que permiten o no ingredientes -->
      <select id="filterPermit" aria-label="Filtrar por permite ingredientes">
        <option value="">Todos</option>
        <option value="1">Permiten ingredientes</option>
        <option value="0">No permiten ingredientes</option>
      </select>

      <!-- Botones de acción para búsqueda y limpieza -->
      <button id="btnBuscar" class="btn-buscar" type="button">Buscar</button>
      <button id="clearFilters" title="Limpiar filtros" type="button">Limpiar</button>
    </div>
  </div>

  <!-- Contenedores donde se cargan el formulario y la lista con JS -->
  <div id="formulario" aria-live="polite"></div>
  <div id="lista" aria-live="polite"></div>

  <!-- Pasamos los tipos disponibles al JS como JSON para usar en los menús -->
  <script type="application/json" id="tipos-json"><?= json_encode($tiposDisponibles, JSON_UNESCAPED_UNICODE) ?></script>
  <script src="productos.js" defer></script>
</body>

</html>