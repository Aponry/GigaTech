<?php
?><!doctype html>
<html lang="es">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<meta http-equiv="Content-Security-Policy"
		content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:;">
	<title>Menú Completo - PizzaConmigo</title>
	<!-- Fonts & minimal preconnect for performance -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<?php $vCss = file_exists(__DIR__ . '/css/modal.css') ? filemtime(__DIR__ . '/css/modal.css') : time(); ?>
	<link rel="stylesheet" href="css/modal.css?v=<?= $vCss ?>">
	<?php $vCartCss = file_exists(__DIR__ . '/cliente/css/carrito.css') ? filemtime(__DIR__ . '/cliente/css/carrito.css') : time(); ?>
	<link rel="stylesheet" href="cliente/css/carrito.css?v=<?= $vCartCss ?>">
	<?php $vMenuCss = file_exists(__DIR__ . '/css/menu_completo.css') ? filemtime(__DIR__ . '/css/menu_completo.css') : time(); ?>
	<link rel="stylesheet" href="css/menu_completo.css?v=<?= $vMenuCss ?>">
	<?php $vPedidosModalCss = file_exists(__DIR__ . '/cliente/css/pedidos_modal.css') ? filemtime(__DIR__ . '/cliente/css/pedidos_modal.css') : time(); ?>
	<link rel="stylesheet" href="cliente/css/pedidos_modal.css?v=<?= $vPedidosModalCss ?>">
</head>

<body>
	<a class="skip-link" href="#menu-completo">Saltar al contenido</a>

	<header class="site-header">
		<div class="header-left">
			<a href="index.php" class="logo" aria-label="Ir al inicio">
				<img src="img/Pizzaconmigo.png" alt="PizzaConmigo"
					style="width:8em;height:auto;display:block;object-fit:contain;border-radius:12px;margin:0 auto;padding:0;" />
			</a>
			<div class="brand">
				<h1>Menú Completo</h1>
				<p class="tagline">Todos nuestros productos disponibles</p>
			</div>
		</div>
		<div class="header-right">
			<!-- Botón para volver al inicio, alineado junto al carrito -->
			<button class="back-btn" onclick="window.location.href='index.php'" aria-label="Volver al inicio">
				<i class="fas fa-arrow-left"></i> Volver
			</button>
			<button id="cartToggle" class="cart-toggle" aria-label="Abrir carrito">Carrito (0)</button>
		</div>
	</header>

	<!-- Navegación de categorías -->
	<nav class="category-nav">
		<div class="category-buttons" id="category-buttons">
			<!-- Botones dinámicos -->
		</div>
	</nav>

	<main id="menu-completo" class="menu-completo">
		<!-- Contenido del menú completo -->
	</main>

	<!-- Panel del carrito -->
	<div id="cart-panel">
		<div class="cart-header">
			<h3>Tu Pedido</h3>
			<button id="close-cart">&times;</button>
		</div>
		<div id="cart-items">
			<!-- Los items del carrito se cargarán aquí -->
		</div>
		<div id="cart-total">
			<!-- Total del carrito -->
		</div>
		<div class="cart-footer-buttons">
			<button class="menu-btn" onclick="window.location.href='index.php'">
				<i class="fas fa-arrow-left"></i> Seguir Comprando
			</button>
			<button class="checkout-btn" onclick="window.location.href='cliente/pagar.php'">
				<i class="fas fa-check-circle"></i> Finalizar pedido
			</button>
		</div>
	</div>

	<!-- Botón flotante de notificación del carrito -->
	<div id="cart-notification" class="cart-notification" style="display:none;"></div>

	<script>
		// Cargar productos y promociones, renderizar menú completo con navegación
		(async function loadMenuCompleto() {
			try {
				const capitalize = (s) => { if (!s) return s; s = String(s); return s.charAt(0).toUpperCase() + s.slice(1); };

				// Cargar productos
				const res = await fetch('./cliente/php/obtener_productos.php');
				const productsJson = await res.json();
				const products = Array.isArray(productsJson) ? productsJson : [];

				// Cargar promociones
				const resPromos = await fetch('./cliente/php/obtener_promociones.php');
				const promosJson = await resPromos.json();
				const promos = Array.isArray(promosJson) ? promosJson : [];

				const menuEl = document.getElementById('menu-completo');
				const categoryButtonsEl = document.getElementById('category-buttons');

				// Agrupar productos por tipo
				const productGroups = products.reduce((acc, p) => { (acc[p.tipo] = acc[p.tipo] || []).push(p); return acc; }, {});

				// Función para resolver rutas de imágenes
				const resolveImg = (img) => {
					if (!img) return 'img/Pizzaconmigo.png';
					if (img.startsWith('http') || img.startsWith('/')) return img;
					if (img.indexOf('empleado/') === 0) return img;
					if (img.startsWith('../')) return img;
					if (img.indexOf('productos/') === 0) return 'empleado/productos/img/' + img.substring(14);
					if (img.indexOf('promos/') === 0) return 'empleado/promociones/img/' + img.substring(6);
					// If backend returns paths like "img/filename", map to empleado/promociones/img/filename for promos
					if (img.indexOf('img/') === 0) return 'empleado/promociones/img/' + img.substring(4);
					return 'empleado/productos/img/' + img;
				};

				// Crear botones de navegación
				const categories = ['promociones', ...Object.keys(productGroups)];
				categories.forEach((categoria, index) => {
					const btn = document.createElement('button');
					btn.className = 'category-btn';
					btn.textContent = capitalize(categoria);
					btn.dataset.category = categoria;
					if (index === 0) btn.classList.add('active'); // Activar primera categoría por defecto
					categoryButtonsEl.appendChild(btn);
				});

				// Renderizar sección de promociones
				if (promos.length > 0) {
					const promoSection = document.createElement('section');
					promoSection.className = 'category-section active';
					promoSection.id = 'section-promociones';
					promoSection.innerHTML = `<h2 class="category-title">Promociones</h2><div class="products-grid"></div>`;
					const grid = promoSection.querySelector('.products-grid');

					promos.forEach(p => {
						const card = document.createElement('article');
						card.className = 'product-card-completo';
						const imgSrc = resolveImg(p.imagen);
						const priceDisplay = p.precio > 0 ? '$' + p.precio : 'Precio a consultar';
						const productosList = p.productos && p.productos.length ? p.productos.map(prod => prod.nombre).join(', ') : 'Productos incluidos';

						card.innerHTML = `
							<img src="${imgSrc}" alt="${p.nombre}" class="product-image" />
							<div class="product-info">
								<h3 class="product-name">${p.nombre}</h3>
								<p class="product-description">${p.descripcion || 'Promoción especial de nuestros productos.'}</p>
								<p class="product-description"><strong>Productos:</strong> ${productosList}</p>
								<div class="product-footer">
									<div class="product-price">${priceDisplay}</div>
									<button class="add-to-cart-btn" data-id="${p.id_promocion}" data-type="promo">Agregar al Carrito</button>
								</div>
							</div>
						`;
						grid.appendChild(card);
					});

					menuEl.appendChild(promoSection);
				}

				// Renderizar secciones de productos
				Object.keys(productGroups).forEach(tipo => {
					const displayTipo = capitalize(tipo);
					const section = document.createElement('section');
					section.className = 'category-section';
					section.id = `section-${tipo}`;
					section.innerHTML = `<h2 class="category-title">${displayTipo}</h2><div class="products-grid"></div>`;
					const grid = section.querySelector('.products-grid');

					productGroups[tipo].forEach(p => {
						const card = document.createElement('article');
						card.className = 'product-card-completo';
						const imgSrc = resolveImg(p.imagen);
						const priceDisplay = p.precio_base > 0 ? '$' + p.precio_base : 'Precio a consultar';

						card.innerHTML = `
							<img src="${imgSrc}" alt="${p.nombre}" class="product-image" />
							<div class="product-info">
								<h3 class="product-name">${p.nombre}</h3>
								<p class="product-description">${p.descripcion || 'Delicioso producto de nuestra carta.'}</p>
								<div class="product-footer">
									<div class="product-price">${priceDisplay}</div>
									<button class="add-to-cart-btn" data-id="${p.id_producto}" data-type="producto">Agregar al Carrito</button>
								</div>
							</div>
						`;
						grid.appendChild(card);
					});

					menuEl.appendChild(section);
				});

				// Manejar navegación de categorías
				categoryButtonsEl.addEventListener('click', (e) => {
					if (!e.target.classList.contains('category-btn')) return;

					// Remover clase active de todos los botones
					document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
					// Agregar clase active al botón clickeado
					e.target.classList.add('active');

					// Ocultar todas las secciones
					document.querySelectorAll('.category-section').forEach(section => section.classList.remove('active'));

					// Mostrar la sección correspondiente
					const categoria = e.target.dataset.category;
					const sectionToShow = document.getElementById(`section-${categoria}`);
					if (sectionToShow) {
						sectionToShow.classList.add('active');
					}
				});

				// Delegar clics para agregar al carrito
				menuEl.addEventListener('click', (e) => {
				    const btn = e.target.closest('.add-to-cart-btn');
				    if (!btn) return;
				    const id = btn.getAttribute('data-id');
				    const type = btn.getAttribute('data-type');
				    // Redirigir a la página de detalle del producto
				    window.location.href = `cliente/producto_detalle.php?id=${id}`;
				});

			} catch (err) {
				console.error('Error al cargar el menú completo', err);
				document.getElementById('menu-completo').innerHTML = '<p class="muted">No se pudieron cargar los productos.</p>';
			}
		})();

	</script>
	<script src="js/cart.js"></script>
</body>

</html>