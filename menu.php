<?php
require_once 'conexion.php';
$res = $conexion->query("SELECT id_producto, nombre, tipo, precio_base, imagen, descripcion FROM productos ORDER BY id_producto DESC");
$productos = [];
if ($res) {
    while ($r = $res->fetch_assoc())
        $productos[] = $r;
}
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
    while ($r = $pp_res->fetch_assoc()) {
        $promo_items[$r['id_promocion']] = $r['productos'];
    }
}
$tipos = ['pizza', 'hamburguesa', 'bebida', 'otro'];
$productos_todos = $productos;
usort($productos_todos, function ($a, $b) {
    if ($a['tipo'] == 'pizza' && $b['tipo'] != 'pizza')
        return -1;
    if ($b['tipo'] == 'pizza' && $a['tipo'] != 'pizza')
        return 1;
    return strcmp($a['tipo'], $b['tipo']);
});
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Menú · PizzaConmigo</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div id="backdrop"></div>

    <header class="site-header">
        <div class="header-left">
            <img id="logopizza" src="images/Logo Pizzeria.png" alt="logo" onclick="location.href='index.php'">
        </div>
        <div class="header-right">
            <button id="menuToggle" class="menu-toggle" aria-expanded="false" aria-controls="dropdownMenu">Menú</button>
            <nav class="tipo-nav" id="tipoNav">
                <button class="tipoBtn" data-tipo="pizza">Pizzas</button>
                <button class="tipoBtn" data-tipo="hamburguesa">Hamburguesas</button>
                <button class="tipoBtn" data-tipo="bebida">Bebidas</button>
                <button class="tipoBtn" data-tipo="otro">Otros</button>
                <button class="tipoBtn tipoTodos" data-tipo="todos">Todos</button>
                <button class="tipoBtn" data-tipo="promos">Promociones</button>
            </nav>
            <button id="botonCarrito" class="icon-carrito" aria-label="Carrito">Carrito</button>
        </div>
    </header>

    <div id="dropdownMenu" class="dropdown-menu" aria-hidden="true" role="menu">
        <button class="dropItem tipoBtn" data-tipo="pizza" role="menuitem">Pizzas</button>
        <button class="dropItem tipoBtn" data-tipo="hamburguesa" role="menuitem">Hamburguesas</button>
        <button class="dropItem tipoBtn" data-tipo="bebida" role="menuitem">Bebidas</button>
        <button class="dropItem tipoBtn" data-tipo="otro" role="menuitem">Otros</button>
        <button class="dropItem tipoBtn" data-tipo="todos" role="menuitem">Todos</button>
        <button class="dropItem tipoBtn" data-tipo="promos" role="menuitem">Promociones</button>
    </div>

    <main>
        <section class="promos-grid seccionTipo" id="seccion-promos" style="display:none;">
            <h2 class="section-title">Promociones</h2>
            <div class="grid-promos">
                <?php foreach ($promos as $pr):
                    $pid = $pr['id_promocion']; ?>
                    <article class="promo-card" data-id="<?= htmlspecialchars($pid) ?>"
                        data-nombre="<?= htmlspecialchars($pr['nombre']) ?>"
                        data-precio="<?= htmlspecialchars($pr['precio']) ?>">
                        <img src="empleado/promos/img/<?= basename($pr['imagen']) ?>"
                            alt="<?= htmlspecialchars($pr['nombre']) ?>">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($pr['nombre']) ?></h3>
                            <div class="meta">$<?= htmlspecialchars($pr['precio']) ?></div>
                            <?php if (!empty($promo_items[$pid])): ?>
                                <p class="desc"><strong>Incluye:</strong> <?= htmlspecialchars($promo_items[$pid]) ?></p>
                            <?php else: ?>
                                <p class="desc"><?= htmlspecialchars($pr['descripcion']) ?></p>
                            <?php endif; ?>
                            <div class="card-actions"><button class="agregarPromo" data-id="<?= htmlspecialchars($pid) ?>"
                                    data-nombre="<?= htmlspecialchars($pr['nombre']) ?>"
                                    data-precio="<?= htmlspecialchars($pr['precio']) ?>">Agregar</button></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <?php foreach ($tipos as $t): ?>
            <section class="seccionTipo" id="seccion-<?= $t ?>" style="display:none;">
                <h2 class="section-title"><?= ucfirst($t) ?></h2>
                <div class="grid-large">
                    <?php foreach ($productos as $p):
                        if ($p['tipo'] == $t): ?>
                            <article class="producto big" data-id="<?= $p['id_producto'] ?>"
                                data-nombre="<?= htmlspecialchars($p['nombre']) ?>" data-precio="<?= $p['precio_base'] ?>"
                                data-tipo="<?= $p['tipo'] ?>">
                                <img src="empleado/productos/img/<?= basename($p['imagen']) ?>"
                                    alt="<?= htmlspecialchars($p['nombre']) ?>">
                                <div class="info">
                                    <h3><?= htmlspecialchars($p['nombre']) ?></h3>
                                    <div class="meta"><?= htmlspecialchars($p['tipo']) ?> · $<?= $p['precio_base'] ?></div>
                                    <p class="desc"><?= htmlspecialchars($p['descripcion']) ?></p>
                                    <div class="acciones"><button class="agregarCarrito">Agregar</button></div>
                                </div>
                            </article>
                        <?php endif; endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <section class="seccionTipo" id="seccion-todos" style="display:none;">
            <h2 class="section-title">Todos</h2>
            <div class="big-grid">
                <?php foreach ($productos_todos as $p): ?>
                    <article class="producto big" data-id="<?= $p['id_producto'] ?>"
                        data-nombre="<?= htmlspecialchars($p['nombre']) ?>" data-precio="<?= $p['precio_base'] ?>"
                        data-tipo="<?= $p['tipo'] ?>">
                        <img src="empleado/productos/img/<?= basename($p['imagen']) ?>"
                            alt="<?= htmlspecialchars($p['nombre']) ?>">
                        <div class="info">
                            <h3><?= htmlspecialchars($p['nombre']) ?></h3>
                            <div class="meta"><?= htmlspecialchars($p['tipo']) ?> · $<?= $p['precio_base'] ?></div>
                            <p class="desc"><?= htmlspecialchars($p['descripcion']) ?></p>
                            <div class="acciones"><button class="agregarCarrito">Agregar</button></div>
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

    <script src="js/carrito.js"></script>
    <script src="js/menu.js"></script>
    <script src="js/carrusel.js"></script>
</body>

</html>