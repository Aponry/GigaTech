<?php
require_once __DIR__ . '/../../conexion.php';

// Obtener ingredientes
$ingredientes = [];
if ($conexion) {
    $res = $conexion->query("SELECT id_ingrediente, nombre, tipo_producto, costo, stock FROM ingrediente WHERE tipo_producto IN ('pizza', 'hamburguesa') ORDER BY tipo_producto, nombre");
    while ($row = $res->fetch_assoc()) {
        $ingredientes[$row['tipo_producto']][] = $row;
    }
    $res->close();
}

?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="../../css/modal.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <title>Ingredientes</title>
  <link rel="icon" href="../../img/logo-removebg-preview.ico" type="image/x-icon">
  <link rel="stylesheet" href="ingredientes.css">
</head>

<body>
  <h1>Ingredientes</h1>

  <div class="container">
  <div class="top-row">
    <div class="left-controls">
      <button id="volver" type="button">Volver</button>
      <button id="btnAgregar" type="button">Agregar ingrediente</button>
    </div>

    <div class="search-controls" role="search" aria-label="Buscar ingredientes">
      <input id="searchInput" type="search" placeholder="Buscar por nombre..."
        aria-label="Buscar por nombre">
      <button id="btnBuscar" type="button">Buscar</button>
      <button id="clearFilters" title="Limpiar filtros" type="button">Limpiar</button>
    </div>
  </div>

  <div id="formulario" aria-live="polite"></div>

  <main id="ingredientes-section"></main>
  </div>

  <div id="sectionButtonsBottom" class="section-buttons-bottom">
    <div class="menu-part">
      <span class="menu-label">Ingredientes</span>
      <div class="buttons" id="ingredientes-buttons"></div>
    </div>
  </div>
  <button id="scrollToTop" class="scroll-to-top" aria-label="Volver arriba">↑</button>

  <script src="ingredientes.js"></script>
  <script src="ingredientes_inline.js"></script>
</body>

</html>
