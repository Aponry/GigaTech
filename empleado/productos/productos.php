<?php
// Incluir el archivo de conexión a la base de datos desde el directorio padre
require_once __DIR__ . '/../../conexion.php';

function resolveImg($img) {
    if (!$img) return '../../img/Pizzaconmigo.png';
    if (strpos($img, 'http') === 0 || strpos($img, '/') === 0) return $img;
    if (strpos($img, 'empleado/') === 0) return $img;
    if (strpos($img, 'productos/') === 0) return '../../cliente/img/' . $img;
    if (strpos($img, 'promos/') === 0) return '../../cliente/img/' . $img;
    $base = basename($img);
    $base = str_replace('Imagen', '', $base); // Remove 'Imagen' prefix if present
    return 'img/' . $base;
}

// Obtener productos de la base de datos
// Array para almacenar los productos agrupados por tipo
$productos = [];
if ($conexion) {
    // Consulta para obtener todos los productos ordenados por tipo y nombre
    $res = $conexion->query("SELECT id_producto, nombre, tipo, precio_base, descripcion, imagen, stock, permitir_ingredientes FROM productos ORDER BY tipo, nombre");
    while ($row = $res->fetch_assoc()) {
        // Resolver la ruta de la imagen usando la función definida
        $original = $row['imagen'];
        $row['imagen'] = resolveImg($row['imagen']);
        error_log('Product id ' . $row['id_producto'] . ' - Original imagen: ' . $original . ', resolved: ' . $row['imagen']);
        // Agrupar productos por tipo en el array
        $productos[$row['tipo']][] = $row;
    }
    $res->close();
}

$tipos_productos = array_keys($productos);

// Consultar la estructura de la columna 'tipo' en la tabla productos para obtener los valores ENUM disponibles
// Esto permite poblar el filtro de tipos dinámicamente basado en la base de datos
$db = $conexion ?? null;
$consultaColumnas = $db ? $db->query("SHOW COLUMNS FROM productos LIKE 'tipo'") : false;
$filaColumna = $consultaColumnas ? $consultaColumnas->fetch_assoc() : null;

$tiposDisponibles = [];
if (!empty($filaColumna['Type'])) {
  // Extraemos los valores del ENUM usando una expresión regular, por ejemplo: enum('pizza','bebida') → ['pizza', 'bebida']
  preg_match_all("/'([^']+)'/", $filaColumna['Type'], $coincidencias);
  $tiposDisponibles = $coincidencias[1] ?? [];
}

// Si no encontramos tipos en la base de datos, definimos "otro" por defecto
if (empty($tiposDisponibles)) {
  $tiposDisponibles = ['otro'];
}
?>
<!-- Inicio del documento HTML -->
<!-- Define el tipo de documento y el idioma español -->
<!doctype html>
<html lang="es">

<!-- Cabecera del documento HTML -->
<head>
  <!-- Codificación de caracteres UTF-8 -->
  <meta charset="utf-8">
  <!-- Configuración de viewport para dispositivos móviles -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Favicon -->
  <link rel="icon" href="../../img/PizzaConmigo.ico" type="image/x-icon">
  <!-- Enlace al archivo CSS local -->
  <link rel="stylesheet" href="style.css">
  <!-- Enlace a los iconos de Bootstrap desde CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <!-- Título de la página -->
  <title>Productos</title>
</head>

<!-- Cuerpo del documento HTML -->
<body>
  <!-- Título principal de la página -->
  <h1>Productos</h1>

  <!-- Contenedor principal con ancho máximo -->
  <div class="container">
  <!-- Fila superior con controles -->
  <div class="top-row">
    <!-- Controles del lado izquierdo -->
    <div class="left-controls">
      <!-- Botones principales del panel de administración -->
      <!-- Botón para volver al menú anterior -->
      <button id="volver" type="button" onclick="window.location.href='../menu.php'">Volver</button>
      <!-- Botón para agregar un nuevo producto -->
      <button id="btnAgregar" type="button">Agregar producto</button>
      <!-- Botón para editar producto -->
      <button id="btnEditar" type="button">Editar producto</button>
      <!-- Botón para eliminar producto -->
      <button id="btnEliminar" type="button">Eliminar producto</button>
      <!-- Botón para ver detalles (probablemente activado por JS) -->
      <button id="btnVer" type="button">Ver</button>
    </div>

    <!-- Controles de búsqueda y filtros -->
    <div class="search-controls" role="search" aria-label="Buscar productos">
      <!-- Campo de texto para buscar productos por nombre -->
      <input id="searchInput" type="search" placeholder="Buscar por nombre..."
        aria-label="Buscar por nombre">

      <!-- Filtro por tipo de producto (pizza, bebida, etc.) generado dinámicamente -->
      <select id="filterTipo" aria-label="Filtrar por tipo">
        <option value="">Todos los tipos</option>
        <?php foreach ($tiposDisponibles as $tipo): ?>
          <!-- Opciones con escape de HTML para seguridad -->
          <option value="<?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars(ucfirst($tipo), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Filtro para mostrar productos que permiten o no ingredientes -->
      <select id="filterPermit" aria-label="Filtrar por permite ingredientes">
        <option value="">Todos</option>
        <option value="1">Permiten ingredientes</option>
        <option value="0">No permiten ingredientes</option>
      </select>

      <!-- Botones de acción para búsqueda y limpieza de filtros -->
      <button id="btnBuscar" class="btn-buscar" type="button">Buscar</button>
      <button id="clearFilters" title="Limpiar filtros" type="button">Limpiar</button>
    </div>
  </div>

  <!-- Contenedor donde se cargan formularios dinámicamente con JavaScript -->
  <div id="formulario" aria-live="polite"></div>

  <!-- Sección principal que muestra todos los productos agrupados por tipo -->
  <div class="mb-5" id="productos-section">
    <!-- Bucle PHP para iterar sobre cada tipo de producto -->
    <?php foreach ($productos as $tipo => $items): ?>
      <!-- Sección para cada tipo de producto con ID único -->
      <section class="product-section" id="productos-<?php echo $tipo; ?>">
        <!-- Título del tipo de producto con primera letra mayúscula -->
        <h2 class="mb-3"><?php echo htmlspecialchars(ucfirst($tipo)); ?></h2>
        <!-- Grid para organizar las tarjetas de productos -->
        <div class="section-grid">
          <!-- Bucle para cada producto dentro del tipo -->
          <?php foreach ($items as $item): ?>
            <!-- Div contenedor con atributos de datos para filtros JS -->
            <div data-name="<?php echo htmlspecialchars(strtolower($item['nombre'])); ?>" data-tipo="<?php echo htmlspecialchars($item['tipo']); ?>" data-permitir="<?php echo $item['permitir_ingredientes']; ?>">
              <!-- Tarjeta del producto -->
              <div class="product-card">
                <!-- Imagen del producto -->
                <img class="card-img-top" src="<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                <!-- Cuerpo de la tarjeta con información -->
                <div class="card-body">
                  <!-- Nombre del producto -->
                  <h5 class="card-title"><?php echo htmlspecialchars($item['nombre']); ?></h5>
                  <!-- Descripción del producto -->
                  <p class="card-text"><?php echo htmlspecialchars($item['descripcion'] ?? ''); ?></p>
                  <!-- Precio base formateado -->
                  <p class="card-text">Precio: $<?php echo number_format($item['precio_base'], 2); ?></p>
                  <!-- Stock con badge verde -->
                  <p class="card-text"><span class="badge bg-success">Stock: <?php echo $item['stock']; ?></span></p>
                  <!-- Tipo con badge primario -->
                  <p class="card-text"><span class="badge bg-primary">Tipo: <?php echo htmlspecialchars($item['tipo']); ?></span></p>
                  <!-- Permite ingredientes con badge condicional -->
                  <p class="card-text"><span class="badge <?php echo $item['permitir_ingredientes'] ? 'bg-success' : 'bg-danger'; ?>">Permite Ingredientes: <?php echo $item['permitir_ingredientes'] ? 'Sí' : 'No'; ?></span></p>
                  <!-- ID del producto en pequeño -->
                  <p class="card-text"><small>ID: <?php echo $item['id_producto']; ?></small></p>
                  <!-- Botones de editar y borrar -->
                  <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-editar btn-sm" data-id="<?php echo $item['id_producto']; ?>">Editar</button>
                    <button type="button" class="btn btn-borrar btn-sm" data-id="<?php echo $item['id_producto']; ?>">Borrar</button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </div>
  </div>

  <!-- Menú inferior fijo con botones de navegación por tipo -->
  <div id="sectionButtonsBottom" class="section-buttons-bottom">
    <div class="menu-part">
      <!-- Etiqueta del menú -->
      <span class="menu-label">Productos</span>
      <!-- Contenedor donde se agregan los botones dinámicamente -->
      <div class="buttons" id="productos-buttons"></div>
    </div>
  </div>
  <!-- Botón para volver arriba de la página -->
  <button id="scrollToTop" class="scroll-to-top" aria-label="Volver arriba">↑</button>


  <!-- Script JSON embebido para pasar los tipos disponibles al JavaScript -->
  <script type="application/json" id="tipos-json"><?= json_encode($tiposDisponibles, JSON_UNESCAPED_UNICODE) ?></script>
  <!-- Incluir archivo JavaScript externo para funcionalidades de productos -->
  <script src="productos.js"></script>
  <script src="productos_inline.js"></script>
</body>

</html>