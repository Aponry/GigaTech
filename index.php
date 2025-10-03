<?php
require_once 'conexion.php';
$promo_res = $conexion->query("SELECT id_promocion, nombre, precio, imagen, descripcion FROM promocion ORDER BY id_promocion ASC");
$promos = [];
if ($promo_res) {
    while ($r = $promo_res->fetch_assoc())
        $promos[] = $r;
}
$pp_res = $conexion->query("
    SELECT pp.id_promocion,
           GROUP_CONCAT(CONCAT(p.nombre, ' (x', pp.cantidad, ')') SEPARATOR ', ') AS productos
    FROM promocion_producto pp
    JOIN productos p ON pp.id_producto = p.id_producto
    GROUP BY pp.id_promocion
");
$promo_items = [];
if ($pp_res) {
    while ($r = $pp_res->fetch_assoc())
        $promo_items[$r['id_promocion']] = $r['productos'];
}
$prod_res = $conexion->query("SELECT id_producto, nombre, tipo, precio_base, imagen FROM productos ORDER BY RAND() LIMIT 6");
$productos = [];
if ($prod_res) {
    while ($r = $prod_res->fetch_assoc())
        $productos[] = $r;
}
//praelcocofjrkg
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>PizzaConmigo</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div id="backdrop"></div>

    <header class="site-header">
        <div class="header-left">
            <img id="logopizza" src="images/Logo Pizzeria.png" alt="logo" onclick="location.href='index.php'">
        </div>
        <div class="header-right">
            <a class="btn btn-menu" href="menu.php">Ver Menú</a>
            <button id="botonCarrito" class="icon-carrito" aria-label="Abrir carrito">Carrito</button>
        </div>
    </header>

    <main>
        <section id="carrusel" aria-label="Promociones">
            <div class="promo-wrapper">
                <?php foreach ($promos as $pr):
                    $pid = $pr['id_promocion']; ?>
                    <div class="promo" data-id="<?= htmlspecialchars($pid) ?>"
                        data-nombre="<?= htmlspecialchars($pr['nombre']) ?>"
                        data-precio="<?= htmlspecialchars($pr['precio']) ?>">
                        <img src="empleado/promos/img/<?= basename($pr['imagen']) ?>"
                            alt="<?= htmlspecialchars($pr['nombre']) ?>">
                        <div class="promo-info">
                            <h3><?= htmlspecialchars($pr['nombre']) ?></h3>
                            <p class="p-desc"><?= htmlspecialchars($pr['descripcion']) ?></p>
                            <?php if (!empty($promo_items[$pid])): ?>
                                <p class="p-incluye"><strong>Incluye:</strong> <?= htmlspecialchars($promo_items[$pid]) ?></p>
                            <?php endif; ?>
                            <div class="p-row">
                                <div class="p-price">$<?= htmlspecialchars($pr['precio']) ?></div>
                                <button class="agregarPromo" data-id="<?= htmlspecialchars($pid) ?>"
                                    data-nombre="<?= htmlspecialchars($pr['nombre']) ?>"
                                    data-precio="<?= htmlspecialchars($pr['precio']) ?>">Agregar</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="prev" aria-label="Anterior">&lt;</button>
            <button class="next" aria-label="Siguiente">&gt;</button>
        </section>

        <section id="productos">
            <h2 class="section-title">Productos Destacados</h2>
            <div class="grid-large">
                <?php foreach ($productos as $p): ?>
                    <article class="producto" data-id="<?= $p['id_producto'] ?>"
                        data-nombre="<?= htmlspecialchars($p['nombre']) ?>" data-precio="<?= $p['precio_base'] ?>"
                        data-tipo="<?= htmlspecialchars($p['tipo']) ?>">
                        <img src="empleado/productos/img/<?= basename($p['imagen']) ?>"
                            alt="<?= htmlspecialchars($p['nombre']) ?>">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($p['nombre']) ?></h3>
                            <div class="meta"><?= htmlspecialchars($p['tipo']) ?> · $<?= $p['precio_base'] ?></div>
                            <div class="card-actions">
                                <button class="agregarCarrito">Agregar</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <div id="carrito" aria-hidden="true">
        <div class="headerCarrito">
            <h3>Carrito</h3>
            <button id="cerrarCarrito" aria-label="Cerrar">X</button>
        </div>
        <div class="items"></div>
        <div class="sugerencias"></div>
        <div class="totalCarrito">Total: $<span id="total">0</span></div>
        <button id="finalizar" class="btn btn-primary">Finalizar Pedido</button>
    </div>

    <footer class="site-footer">
        <p>PizzaConmigo - Todos los derechos reservados &copy; <?= date('Y') ?></p>
    </footer>

    <script src="js/carrusel.js"></script>
    <script src="js/carrito.js"></script>
    <script src="js/menu.js"></script>
</body>

</html>
#d
#a
#hol