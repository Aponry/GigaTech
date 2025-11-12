<?php
// pedidos.php - Panel de gestión de pedidos para empleados

session_start();

// Verificación de seguridad para acceso de empleado
if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'empleado') {
    die('Acceso denegado');
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedidos</title>
    <link rel="icon" href="../../img/PizzaConmigo.ico">
    <link rel="stylesheet" href="../css/pedidos.css">
    <link rel="stylesheet" href="pedidos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
    <h1>Gestión de Pedidos</h1>

    <div class="container">
    <div class="top-row">
        <div class="left-controls">
            <button id="volver" type="button" onclick="window.location.href='../menu.php'">Volver</button>
        </div>

        <div class="search-controls" role="search" aria-label="Buscar pedidos">
            <input id="search-phone" type="search" placeholder="Buscar por teléfono..."
                aria-label="Buscar por teléfono">

            <select id="status-filter" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="confirmado">Confirmado</option>
                <option value="en_preparacion">En Preparación</option>
                <option value="listo">Listo</option>
                <option value="en_reparto">En Reparto</option>
                <option value="entregado">Entregado</option>
                <option value="cancelado">Cancelado</option>
            </select>

            <input id="date-from" type="date" aria-label="Fecha desde">
            <input id="date-to" type="date" aria-label="Fecha hasta">

            <button id="btnBuscar" class="btn-buscar" type="button">Buscar</button>
            <button id="clearFilters" title="Limpiar filtros" type="button">Limpiar</button>
        </div>
    </div>

        <!-- Tabla de pedidos -->
        <table class="table" id="orders-table">
            <thead>
                <tr>
                    <th>ID Pedido</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Los pedidos se cargan dinámicamente por JS -->
            </tbody>
        </table>
    </div>

    <!-- Modal para detalles del pedido -->
    <div id="order-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="modal-content">
                <!-- Contenido del modal se carga dinámicamente -->
            </div>
        </div>
    </div>

    <div id="sectionButtonsBottom" class="section-buttons-bottom">
        <div class="menu-part">
            <span class="menu-label">Pedidos</span>
            <div class="buttons" id="pedidos-buttons"></div>
        </div>
    </div>
    <button id="scrollToTop" class="scroll-to-top" aria-label="Volver arriba">↑</button>

    <script src="../js/pedidos.js"></script>
    <script src="pedidos_inline.js"></script>
</body>
</html>