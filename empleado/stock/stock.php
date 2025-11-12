<?php
require_once __DIR__ . '/../../conexion.php';

function resolveImg($img) {
    if (!$img) return '../../img/Pizzaconmigo.png';
    if (strpos($img, 'http') === 0 || strpos($img, '/') === 0) return $img;
    if (strpos($img, 'empleado/') === 0) return $img;
    if (strpos($img, 'productos/') === 0) return '../../cliente/img/' . $img;
    if (strpos($img, 'promos/') === 0) return '../../cliente/img/' . $img;
    return '../productos/img/' . basename($img);
}

// Manejar actualizaciones de stock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $id = $_POST['id'];
    $action = $_POST['action'];

    if ($type === 'producto') {
        $table = 'productos';
        $id_col = 'id_producto';
    } elseif ($type === 'ingrediente') {
        $table = 'ingrediente';
        $id_col = 'id_ingrediente';
    } else {
        die('Invalid type');
    }

    if ($action === 'add_stock') {
        $amount = (int)$_POST['amount'];
        $conexion->query("UPDATE $table SET stock = stock + $amount WHERE $id_col = $id");
    } elseif ($action === 'subtract_stock') {
        $amount = (int)$_POST['amount'];
        $conexion->query("UPDATE $table SET stock = stock - $amount WHERE $id_col = $id");
    } elseif ($action === 'set_stock') {
        $new_stock = (int)$_POST['new_stock'];
        $conexion->query("UPDATE $table SET stock = $new_stock WHERE $id_col = $id");
    }

    header("Location: stock.php");
    exit;
}

// Obtener productos
$productos = [];
if ($conexion) {
    $res = $conexion->query("SELECT id_producto, nombre, tipo, stock, imagen FROM productos ORDER BY tipo, nombre");
    while ($row = $res->fetch_assoc()) {
        $row['imagen'] = resolveImg($row['imagen']);
        $productos[$row['tipo']][] = $row;
    }
    $res->close();
}

// Obtener ingredientes
$ingredientes = [];
if ($conexion) {
    $res = $conexion->query("SELECT id_ingrediente, nombre, tipo_producto, stock FROM ingrediente ORDER BY tipo_producto, nombre");
    while ($row = $res->fetch_assoc()) {
        $ingredientes[$row['tipo_producto']][] = $row;
    }
    $res->close();
}

$tipos_productos = array_keys($productos);
$tipos_ingredientes = array_keys($ingredientes);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stock</title>
    <link rel="icon" href="../../img/PizzaConmigo.ico">
    <link rel="stylesheet" href="../productos/style.css">
    <link rel="stylesheet" href="stock.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <h1>Stock de Productos e Ingredientes</h1>

    <div class="container">
    <div class="top-row">
        <div class="left-controls">
            <button id="volver" type="button" onclick="window.location.href='http://127.0.0.1/gigatech/GigaTech/empleado/menu.php'">Volver</button>
        </div>

        <div class="search-controls" role="search" aria-label="Buscar productos">
            <input id="searchInput" type="search" placeholder="Buscar por nombre..." aria-label="Buscar por nombre">
        </div>
    </div>

        <div class="mb-5" id="productos-section">
            <h2 class="mb-3">Productos</h2>
            <?php foreach ($productos as $tipo => $items): ?>
                <section class="product-section" id="productos-<?php echo $tipo; ?>">
                    <h2 class="section-title"><?php echo htmlspecialchars(ucfirst($tipo)); ?></h2>
                    <div class="section-grid">
                        <?php foreach ($items as $item): ?>
                            <div data-name="<?php echo htmlspecialchars(strtolower($item['nombre'])); ?>" class="product-card">
                                <div class="card">
                                    <img class="card-img-top" src="<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($item['nombre']); ?></h5>
                                        <p class="card-text">Stock: <span class="badge <?php echo $item['stock'] <= 5 ? 'bg-danger' : 'bg-success'; ?>"><?php echo $item['stock']; ?></span></p>
                                        <form method="post" class="mb-2">
                                            <input type="hidden" name="type" value="producto">
                                            <input type="hidden" name="id" value="<?php echo $item['id_producto']; ?>">
                                            <input type="hidden" name="action" value="add_stock">
                                            <div class="mb-2">
                                                <label for="amount_<?php echo $item['id_producto']; ?>" class="form-label">Cantidad a agregar:</label>
                                                <input type="number" id="amount_<?php echo $item['id_producto']; ?>" name="amount" class="form-control" placeholder="Ej: 10" min="1" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary">+Stock <i class="bi bi-question-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Aumenta el stock actual en la cantidad especificada"></i></button>
                                        </form>
                                        <form method="post" class="mb-2">
                                            <input type="hidden" name="type" value="producto">
                                            <input type="hidden" name="id" value="<?php echo $item['id_producto']; ?>">
                                            <input type="hidden" name="action" value="subtract_stock">
                                            <div class="mb-2">
                                                <label for="subtract_amount_<?php echo $item['id_producto']; ?>" class="form-label">Cantidad a restar:</label>
                                                <input type="number" id="subtract_amount_<?php echo $item['id_producto']; ?>" name="amount" class="form-control" placeholder="Ej: 5" min="1" required>
                                            </div>
                                            <button type="submit" class="btn btn-danger">-Stock <i class="bi bi-question-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Disminuye el stock actual en la cantidad especificada"></i></button>
                                        </form>
                                        <form method="post">
                                            <input type="hidden" name="type" value="producto">
                                            <input type="hidden" name="id" value="<?php echo $item['id_producto']; ?>">
                                            <input type="hidden" name="action" value="set_stock">
                                            <div class="mb-2">
                                                <label for="new_stock_<?php echo $item['id_producto']; ?>" class="form-label">Nuevo stock:</label>
                                                <input type="number" id="new_stock_<?php echo $item['id_producto']; ?>" name="new_stock" class="form-control" placeholder="Ej: 50" min="0" required>
                                            </div>
                                            <button type="submit" class="btn btn-secondary">Nuevo stock <i class="bi bi-question-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Establece un nuevo valor para el stock"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>

        <div class="mb-5" id="ingredientes-section">
            <h2 class="mb-3">Ingredientes</h2>
            <?php foreach ($ingredientes as $tipo => $items): ?>
                <section class="product-section" id="ingredientes-<?php echo $tipo; ?>">
                    <h2 class="section-title"><?php echo htmlspecialchars(ucfirst($tipo)); ?></h2>
                    <div class="section-grid">
                        <?php foreach ($items as $item): ?>
                            <div data-name="<?php echo htmlspecialchars(strtolower($item['nombre'])); ?>" class="product-card">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($item['nombre']); ?></h5>
                                        <p class="card-text">Stock: <span class="badge <?php echo $item['stock'] <= 5 ? 'bg-danger' : 'bg-success'; ?>"><?php echo $item['stock']; ?></span></p>
                                        <form method="post" class="mb-2">
                                            <input type="hidden" name="type" value="ingrediente">
                                            <input type="hidden" name="id" value="<?php echo $item['id_ingrediente']; ?>">
                                            <input type="hidden" name="action" value="add_stock">
                                            <div class="mb-2">
                                                <label for="amount_<?php echo $item['id_ingrediente']; ?>" class="form-label">Cantidad a agregar:</label>
                                                <input type="number" id="amount_<?php echo $item['id_ingrediente']; ?>" name="amount" class="form-control" placeholder="Ej: 10" min="1" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary">+Stock <i class="bi bi-question-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Aumenta el stock actual en la cantidad especificada"></i></button>
                                        </form>
                                        <form method="post" class="mb-2">
                                            <input type="hidden" name="type" value="ingrediente">
                                            <input type="hidden" name="id" value="<?php echo $item['id_ingrediente']; ?>">
                                            <input type="hidden" name="action" value="subtract_stock">
                                            <div class="mb-2">
                                                <label for="subtract_amount_<?php echo $item['id_ingrediente']; ?>" class="form-label">Cantidad a restar:</label>
                                                <input type="number" id="subtract_amount_<?php echo $item['id_ingrediente']; ?>" name="amount" class="form-control" placeholder="Ej: 5" min="1" required>
                                            </div>
                                            <button type="submit" class="btn btn-danger">-Stock <i class="bi bi-question-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Disminuye el stock actual en la cantidad especificada"></i></button>
                                        </form>
                                        <form method="post">
                                            <input type="hidden" name="type" value="ingrediente">
                                            <input type="hidden" name="id" value="<?php echo $item['id_ingrediente']; ?>">
                                            <input type="hidden" name="action" value="set_stock">
                                            <div class="mb-2">
                                                <label for="new_stock_<?php echo $item['id_ingrediente']; ?>" class="form-label">Nuevo stock:</label>
                                                <input type="number" id="new_stock_<?php echo $item['id_ingrediente']; ?>" name="new_stock" class="form-control" placeholder="Ej: 50" min="0" required>
                                            </div>
                                            <button type="submit" class="btn btn-secondary">Nuevo stock <i class="bi bi-question-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Establece un nuevo valor para el stock"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </div>

<div id="sectionButtonsBottom" class="section-buttons-bottom">
    <div class="menu-part">
        <span class="menu-label">Ingredientes</span>
        <div class="buttons" id="ingredientes-buttons"></div>
    </div>
    <div class="menu-part">
        <span class="menu-label">Productos</span>
        <div class="buttons" id="productos-buttons"></div>
    </div>
</div>
<button id="scrollToTop" class="scroll-to-top" aria-label="Volver arriba">↑</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="stock.js"></script>
</body>
</html>