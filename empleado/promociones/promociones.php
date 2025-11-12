<?php
require_once __DIR__ . '/../../conexion.php';

// Obtener productos para el formulario
$productos = [];
if ($conexion) {
    $r = $conexion->query("SELECT id_producto, nombre FROM productos ORDER BY nombre");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $productos[] = $row;
        }
        $r->close();
    }
}
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <title>Promociones</title>
  <link rel="icon" href="../../img/logo-removebg-preview.ico" type="image/x-icon">
  <style>
    /* Reset y base */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: system-ui, sans-serif;
    }

    body {
      background: #f4f6f8;
      color: #2d3a4a;
      padding: 20px;
    }

    h1 {
      color: #1f3d68;
      margin-bottom: 16px;
      text-align: center;
    }

    h2 {
      color: #1f3d68;
      margin-bottom: 16px;
    }

    /* Controles superiores */
    .top-row {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 20px;
    }

    .left-controls,
    .search-controls {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
    }

    input,
    select,
    button,
    textarea {
      font-size: 1rem;
      font-family: inherit;
    }

    #searchInput {
      padding: 8px 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      background: #fff;
    }

    /* Botones */
    button {
      padding: 8px 12px;
      border: 0;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
    }

    button:hover {
      transform: translateY(-1px);
    }

    #volver {
      background-color: #f0f0f0;
      color: #2d3a4a;
    }

    #btnAgregar,
    .form-actions button[type="submit"] {
      background-color: #27ae60;
      color: #fff;
    }

    #btnBuscar {
      background-color: #2f5f9c;
      color: #fff;
    }

    #clearFilters,
    .form-actions button[type="button"] {
      background-color: #e74c3c;
      color: #fff;
    }

    .btn-editar {
      background-color: #333333;
      color: #fff;
    }

    .btn-borrar {
      background-color: #e74c3c;
      color: #fff;
    }

    /* Table */
    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      overflow: hidden;
      margin-bottom: 20px;
    }

    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #dee2e6;
    }

    th {
      background: #f8f9fa;
      font-weight: 600;
      color: #1f3d68;
    }

    .img-preview-table {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
    }

    /* Form elements */
    .form-label {
      font-weight: 600;
      color: #1f3d68;
      font-size: 0.9rem;
      margin-bottom: 6px;
    }

    .form-input {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      background: #fff;
    }

    .img-preview-form {
      max-width: 200px;
      max-height: 200px;
      border-radius: 8px;
      margin-top: 10px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .form-actions {
      grid-column: span 2;
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }

    /* Sticky menus */
    html { scroll-behavior: smooth; }
    .section-buttons-bottom {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #fff;
        border-top: 1px solid #d2d8e0;
        padding: 10px;
        display: flex;
        justify-content: space-around;
        z-index: 1000;
        box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
    }
    .section-button {
        background: none;
        border: none;
        padding: 12px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        color: #495057;
        min-width: fit-content;
        transition: all 0.2s;
    }
    .section-button:hover {
        background: #f8f9fa;
    }
    .scroll-to-top {
        position: fixed;
        bottom: 70px;
        left: 20px;
        background: #333333;
        color: #fff;
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        font-size: 20px;
        cursor: pointer;
        z-index: 1000;
        transition: all 0.2s;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .scroll-to-top:hover {
        background: #555555;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
    }

    .menu-part {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        flex: 1;
    }
    .menu-label {
        font-weight: bold;
        font-size: 12px;
        color: #2f5f9c;
        text-align: center;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .mb-5 {
      margin-bottom: 3rem;
    }

    @media (max-width: 720px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
      .form-actions {
        grid-column: span 1;
      }
    }

    @media (max-width: 480px) {
      body {
        padding: 10px;
      }
      .container {
        padding: 0 10px;
      }
      .top-row {
        flex-direction: column;
        align-items: center;
        gap: 10px;
      }
    }
  </style>
</head>

<body>
  <h1>Promociones</h1>

  <div class="container">
  <div class="top-row">
    <div class="left-controls">
      <button id="volver" type="button">Volver</button>
      <button id="btnAgregar" type="button">Agregar promoción</button>
    </div>

    <div class="search-controls" role="search" aria-label="Buscar promociones">
      <input id="searchInput" type="search" placeholder="Buscar por nombre..."
        aria-label="Buscar por nombre">
      <button id="btnBuscar" type="button">Buscar</button>
      <button id="clearFilters" title="Limpiar filtros" type="button">Limpiar</button>
    </div>
  </div>

  <div id="formulario" aria-live="polite"></div>

  <div class="mb-5" id="promociones-section">
    <!-- Table will be populated by JS -->
  </div>
  </div>

  <div id="sectionButtonsBottom" class="section-buttons-bottom">
    <div class="menu-part">
      <span class="menu-label">Promociones</span>
      <div class="buttons" id="promociones-buttons"></div>
    </div>
  </div>
  <button id="scrollToTop" class="scroll-to-top" aria-label="Volver arriba">↑</button>

  <script type="application/json" id="productos-json"><?= json_encode($productos, JSON_UNESCAPED_UNICODE) ?></script>
  <script src="promociones.js"></script>
</body>

</html>
