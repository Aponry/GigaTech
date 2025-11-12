<?php
// Sacar ID del producto o promoción de la URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$promo_id = isset($_GET['promo']) ? intval($_GET['promo']) : 0;
?>
<!doctype html>
<html lang="es">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<meta http-equiv="Content-Security-Policy"
		content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:;">
	<title>Detalle del Producto - PizzaConmigo</title>
	<!-- Fonts & minimal preconnect for performance -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
	<link rel="icon" href="../img/PizzaConmigo.ico" type="image/x-icon">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<?php $vCss = file_exists(__DIR__ . '/../css/modal.css') ? filemtime(__DIR__ . '/../css/modal.css') : time(); ?>
	<link rel="stylesheet" href="../css/modal.css?v=<?= $vCss ?>">
	<?php $vCartCss = file_exists(__DIR__ . '/../cliente/css/carrito.css') ? filemtime(__DIR__ . '/../cliente/css/carrito.css') : time(); ?>
	<link rel="stylesheet" href="../cliente/css/carrito.css?v=<?= $vCartCss ?>">
	<?php $vHeroBtnCss = file_exists(__DIR__ . '/../css/hero-btn-animation.css') ? filemtime(__DIR__ . '/../css/hero-btn-animation.css') : time(); ?>
	<link rel="stylesheet" href="../css/hero-btn-animation.css?v=<?= $vHeroBtnCss ?>">
	<?php $vProductoDetalleCss = file_exists(__DIR__ . '/css/producto_detalle.css') ? filemtime(__DIR__ . '/css/producto_detalle.css') : time(); ?>
	<link rel="stylesheet" href="css/producto_detalle.css?v=<?= $vProductoDetalleCss ?>">
</head>

<body>
	<header class="site-header">
		<div class="header-left">
			<a href="../index.php" class="logo" aria-label="Ir al inicio">
				<img src="../img/Pizzaconmigo.png" alt="PizzaConmigo"
					style="width:8em;height:auto;display:block;object-fit:contain;border-radius:12px;margin:0 auto;padding:0;" />
			</a>
			<div class="brand">
				<h1>PizzaConmigo</h1>
				<p class="tagline">Pizzas, hamburguesas y más</p>
			</div>
		</div>
		<div class="header-right">
			<!-- container for dynamic section buttons (pizzas, hamburguesas, etc) -->
			<div id="sectionButtons" class="section-buttons" aria-hidden="false"></div>
			<button id="cartToggle" class="cart-toggle" aria-label="Abrir carrito">Carrito (0)</button>
		</div>
	</header>

	<main id="product-detail">
		<!-- Horizontal container for all sections -->
		<div class="horizontal-container">
			<!-- Product Section -->
			<section class="product-section">
				<div class="product-image-container">
					<img id="product-image" src="" alt="Imagen del producto" class="product-image" />
				</div>
				<div class="product-info">
					<h1 id="product-name" class="product-title">Cargando...</h1>
					<p id="product-description" class="product-description">Cargando descripción...</p>
					<div id="product-price" class="price product-price">Cargando precio...</div>
					<div id="quantity-controls" class="quantity-controls" style="display: none; margin: 0.5rem 0; display: flex; flex-direction: column; gap: 0.5rem;">
						<div id="ingredient-controls" class="ingredient-controls" style="display: none;">
							<label>Ingredientes seleccionados: <span id="ingredient-count">0</span>/<span id="ingredient-limit">4</span></label>
						</div>
					</div>
					<div id="cart-info" style="display: none; margin: 0.5rem 0; padding: 0.5rem; background: #f8f9fa; border-radius: 4px; font-size: 0.9em;"></div>
					<button id="add-to-cart" class="open-modal add-to-cart-btn" data-id="" style="margin-top: 1rem;">Agregar al Carrito</button>
				</div>
			</section>

			<!-- Ingredients Section -->
			<section class="ingredients-section" id="ingredients-section" style="display: none;">
				<h2>Ingredientes <small id="ingredient-limit-text" style="font-size: 0.8em; color: #666;">(máximo 4 ingredientes)</small></h2>
				<div id="ingredients-list" class="ingredients-list"></div>
			</section>

			<!-- Desserts Section -->
			<section class="suggested-section">
				<div id="suggested-desserts" class="suggestion-card" style="display: none;">
					<h2>¿Estás para algo dulce? <small style="font-size: 0.8em; color: #666;">Hasta 10 opciones</small></h2>
					<div id="desserts-grid" class="suggested-grid"></div>
				</div>
			</section>

			<!-- Drinks Section -->
			<section class="suggested-section">
				<div id="suggested-drinks" class="suggestion-card" style="display: none;">
					<h2>¿Algo para tomar? <small style="font-size: 0.8em; color: #666;">Hasta 10 opciones</small></h2>
					<div id="drinks-grid" class="suggested-grid"></div>
				</div>
			</section>

		</div>
	</main>

	<!-- Cart Panel -->
	<div id="cart-panel" class="cart-panel">
		<div class="cart-header">
			<h3>Carrito</h3>
			<button id="close-cart">&times;</button>
		</div>
		<div id="cart-items"></div>
		<div id="cart-total">Total: $0</div>
		<div class="cart-footer-buttons">
			<button class="menu-btn" onclick="window.location.href='../menu_completo.php'">Ver menú</button>
			<form method="POST" action="pagar.php"><button type="submit" class="checkout-btn">Finalizar pedido</button></form>
		</div>
	</div>

	<script src="../js/cart.js"></script>
	<script src="js/producto_detalle.js"></script>
</body>

</html>