<?php
// Incluimos la conexión a la base de datos
require_once __DIR__ . '/../../conexion.php';

// Inicializamos la lista de tipos disponibles con un valor por defecto
$tiposDisponibles = ['otro'];

// Verificamos que la conexión a la base de datos esté activa
if ($conexion) {
    // Consultamos la estructura de la columna 'tipo_producto' de la tabla 'ingrediente'
    $res = $conexion->query("SHOW COLUMNS FROM ingrediente LIKE 'tipo_producto'");

    // Si la consulta es exitosa, procesamos los tipos disponibles
    if ($res) {
        $fila = $res->fetch_assoc();
        // Si la columna tiene valores ENUM, los extraemos
        if (!empty($fila['Type'])) {
            preg_match_all("/'([^']+)'/", $fila['Type'], $m); // Usamos regex para extraer los valores del ENUM
            if (!empty($m[1])) {
                $tiposDisponibles = $m[1]; // Asignamos los tipos encontrados a la variable
            }
        }
        $res->close(); // Cerramos el resultado de la consulta
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ingredientes</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Encabezado principal -->
  <header class="encabezado-principal" role="banner" style="text-align:center;margin-bottom:12px;">
    <h1 class="titulo-pagina" style="margin:0;">Ingredientes</h1>
  </header>

  <!-- Sección de controles y búsqueda -->
  <section class="fila-controles" aria-label="Controles y búsqueda" style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:14px;">
    <div class="controles-izquierda" role="group" aria-label="Acciones" style="display:flex;gap:8px;align-items:center;">
      <button id="volver" type="button">Volver</button>
      <button id="agregar" type="button">Agregar</button>
      <button id="ver" type="button">Ver</button>
    </div>

    <div class="controles-busqueda" role="search" aria-label="Buscar ingredientes" style="display:flex;gap:8px;align-items:center;">
      <!-- Campo de búsqueda -->
      <input id="buscarInput" type="search" placeholder="Buscar por ID o nombre..." aria-label="Buscar por id o nombre">
      
      <!-- Filtro de tipo -->
      <select id="filtroTipo" aria-label="Filtrar por tipo">
        <option value="">Todos los tipos</option>
        <?php foreach ($tiposDisponibles as $t): ?>
          <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($t), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      
      <!-- Botón para limpiar filtros -->
      <button id="limpiarFiltros" title="Limpiar filtros">Limpiar</button>
    </div>
  </section>

  <main>
    <div id="formulario" aria-live="polite"></div>
    <div id="lista" aria-live="polite"></div>
  </main>

  <!-- Pasamos los tipos disponibles al JS como JSON -->
  <script id="tipos-json" type="application/json"><?= json_encode($tiposDisponibles, JSON_UNESCAPED_UNICODE) ?></script>
  
  <!-- Cargamos el script de funcionalidad para la página -->
  <script src="ingredientes.js" defer></script>
</body>
</html>
