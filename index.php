<?php
?><!doctype html>
<html lang="es">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<meta http-equiv="Content-Security-Policy"
		content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:;">
	<title>PizzaConmigo</title>
	<link rel="icon" href="img/PizzaConmigo.ico" type="image/x-icon">
	<!-- Configuración de fuentes y preconexión mínima para optimizar el rendimiento -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<?php $vCss = file_exists(__DIR__ . '/css/modal.css') ? filemtime(__DIR__ . '/css/modal.css') : time(); ?>
	<link rel="stylesheet" href="css/modal.css?v=<?= $vCss ?>">
	<?php $vCartCss = file_exists(__DIR__ . '/cliente/css/carrito.css') ? filemtime(__DIR__ . '/cliente/css/carrito.css') : time(); ?>
	<link rel="stylesheet" href="cliente/css/carrito.css?v=<?= $vCartCss ?>">
	<?php $vPedidosCss = file_exists(__DIR__ . '/cliente/css/pedidos.css') ? filemtime(__DIR__ . '/cliente/css/pedidos.css') : time(); ?>
	<link rel="stylesheet" href="cliente/css/pedidos.css?v=<?= $vPedidosCss ?>">
	<?php $vHeroBtnCss = file_exists(__DIR__ . '/css/hero-btn-animation.css') ? filemtime(__DIR__ . '/css/hero-btn-animation.css') : time(); ?>
	<link rel="stylesheet" href="css/hero-btn-animation.css?v=<?= $vHeroBtnCss ?>">
	<?php $vPedidosModalCss = file_exists(__DIR__ . '/cliente/css/pedidos_modal.css') ? filemtime(__DIR__ . '/cliente/css/pedidos_modal.css') : time(); ?>
	<link rel="stylesheet" href="cliente/css/pedidos_modal.css?v=<?= $vPedidosModalCss ?>">
	<style>
		@media (min-width: 721px) {
			#products {
				display: flex;
				flex-direction: column;
				align-items: center;
			}
		}

		/* Mejoras en la navegación del carrusel */
		.carousel-prev, .carousel-next {
			background: rgba(0, 0, 0, 0.7);
			color: white;
			border: 2px solid rgba(255, 255, 255, 0.3);
			font-size: 1.5rem;
			font-weight: bold;
			width: 40px;
			height: 40px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: background-color 0.3s ease, border-color 0.3s ease;
			cursor: pointer;
		}

		.carousel-prev:hover, .carousel-next:hover {
			background: rgba(0, 0, 0, 0.9);
			border-color: white;
		}

		.carousel-indicators {
			display: flex;
			justify-content: center;
			gap: 8px;
			margin-top: 15px;
		}

		.carousel-dot {
			width: 16px;
			height: 16px;
			border-radius: 50%;
			border: 2px solid rgba(0, 0, 0, 0.3);
			background: rgba(255, 255, 255, 0.7);
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.carousel-dot:hover {
			background: rgba(255, 255, 255, 0.9);
			transform: scale(1.2);
		}

		.carousel-dot.active {
			background: rgba(0, 0, 0, 0.8);
			border-color: rgba(0, 0, 0, 0.8);
		}

		/* Estilos para mostrar tipo y precio de productos */
		.product-meta {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin: 5px 0;
			font-weight: 600;
		}

		.product-type {
			padding: 4px 8px;
			border-radius: 4px;
			font-size: 0.8rem;
			text-transform: uppercase;
		}
		
		/* Botón de pedidos */
		.orders-btn {
			background-color: #1976d2;
			color: white;
			border: none;
			padding: 10px 15px;
			border-radius: 5px;
			cursor: pointer;
			font-weight: bold;
			margin-right: 10px;
			transition: background-color 0.3s ease;
			height: 40px; /* Mismo tamaño que el botón del carrito */
			display: inline-flex;
			align-items: center;
			justify-content: center;
		}
		
		.orders-btn:hover {
			background-color: #1565c0;
		}
		
		@media (max-width: 768px) {
			.orders-btn {
				padding: 8px 12px;
				font-size: 0.9rem;
				height: 36px; /* Mismo tamaño que el botón del carrito en móviles */
			}
		}
	</style>
</head>

<body>
	<a class="skip-link" href="#products">Saltar al contenido</a>
	<header class="site-header">
		<div class="header-left">
			<a href="index.php" class="logo" aria-label="Ir al inicio">
				<img src="img/Pizzaconmigo.png" alt="PizzaConmigo"
					style="width:8em;height:auto;display:block;object-fit:contain;border-radius:12px;margin:0 auto;padding:0;" />
			</a>
			<div class="brand">
				<h1>PizzaConmigo</h1>
				<p class="tagline">Pizzas, hamburguesas y más</p>
			</div>
		</div>
		<div class="header-right">
			<!-- Contenedor para los botones de secciones dinámicas (pizzas, hamburguesas, etc.) -->
			<div id="sectionButtons" class="section-buttons" aria-hidden="false"></div>
			<button id="ordersBtn" class="cart-toggle" aria-label="Ver mis pedidos">
				<i class="fas fa-clipboard-list"></i> Mis Pedidos
			</button>
			<button id="cartToggle" class="cart-toggle" aria-label="Abrir carrito">Carrito (0)</button>
		</div>
	</header>

	<!-- Seccion hero -->
	<section class="hero">
		<div class="hero-content">
			<h2>¡Bienvenido a PizzaConmigo!</h2>
			<p>Disfruta de las mejores pizzas, hamburguesas y más, listas para llevar o delivery.</p>
			<a href="#products" class="hero-btn" id="ver-menu-btn">Ver Menú</a>
		</div>
	</section>

	<main id="products">
		<!-- Lista de productos de demostración simple. Las estructuras de datos deben ser mínimas y claras -->
	</main>

	<!-- Panel del carrito -->
	<div id="cart-panel" class="cart-panel">
		<div class="cart-header">
			<h3>Carrito</h3>
			<button id="close-cart">&times;</button>
		</div>
		<div id="cart-items"></div>
		<div id="cart-total">Total: $0</div>
		<div class="cart-footer-buttons">
			<button class="menu-btn" onclick="window.location.href='menu_completo.php'">Ver menú</button>
			<form method="POST" action="cliente/pagar.php"><button type="submit" class="checkout-btn">Finalizar pedido</button></form>
		</div>
	</div>

	<script type="module">
		// Che, este script carga los productos desde el backend y los muestra agrupados por tipo, como pizzas, hamburguesas, etc.
		// También maneja las promociones y el carrito, boludo.

		// Función para abrir modales de contacto, como teléfono, delivery, etc.
		window.openContactModal = (type) => {
			let title = '';
			let content = '';
			switch (type) {
				case 'contact':
					title = 'Información de Contacto';
					content = `
								<p><strong>Teléfono:</strong> 47344688</p>
								<p><strong>WhatsApp:</strong> <a href="https://api.whatsapp.com/send?phone=091918149" target="_blank" rel="noopener">📱 091918149</a></p>
								<p><strong>Dirección:</strong> Washington Beltran 1427, Salto, Uruguay</p>
								<p><strong>Instagram:</strong> <a href="https://www.instagram.com/pizzaconmigosalto/" target="_blank" rel="noopener">📷 @pizzaconmigosalto</a></p>
							`;
					break;
				case 'delivery':
					title = 'Opciones de Entrega';
					content = `
								<p>Ofrecemos servicio de delivery a domicilio y retiro en tienda.</p>
								<p><strong>Tiempo estimado:</strong> 15-30 minutos para delivery y retiro.</p>
							`;
					break;
				case 'hours':
					title = 'Horarios de Atención';
					content = `
								<p><strong>Cerrado:</strong> Lunes</p>
								<p><strong>Martes a Sábado:</strong> 19:30 a 23:30</p>
								<p><strong>Domingo:</strong> 20:00 a 00:30</p>
							`;
					break;
				case 'faqs':
					title = 'Preguntas Frecuentes';
					content = `
								<dl>
									<dt>¿Cuál es el área de cobertura para delivery?</dt>
									<dd>Cubrimos Salto y zonas aledañas. Si tu zona no está cubierta, puedes optar por retiro en tienda.</dd>
									<dt>¿Qué métodos de pago aceptan?</dt>
									<dd>Aceptamos efectivo, tarjetas de débito y crédito, y pagos vía transferencia bancaria.</dd>
									<dt>¿Puedo hacer pedidos personalizados?</dt>
									<dd>Sí, ofrecemos opciones de personalización como ingredientes extra, sin ciertos ingredientes, o pizzas vegetarianas. Contáctanos para detalles.</dd>
									<dt>¿Tienen opciones para alérgicos?</dt>
									<dd>Sí, podemos adaptar pedidos para alérgicos a gluten, lactosa, etc. Infórmanos tus restricciones al ordenar.</dd>
									<dt>¿Hay descuento por pedidos grandes?</dt>
									<dd>Ofrecemos promociones especiales para pedidos de grupos o eventos. Contáctanos para cotizaciones.</dd>
									<dt>¿Qué pasa si mi pedido llega tarde?</dt>
									<dd>Nos esforzamos por cumplir con los tiempos estimados. En caso de demora, contáctanos inmediatamente para resolverlo.</dd>
									<dt>¿Puedo cancelar o modificar mi pedido?</dt>
									<dd>Sí, puedes cancelar o modificar hasta 10 minutos antes de la hora estimada de entrega. Contáctanos vía WhatsApp.</dd>
								</dl>
							`;
					break;
			}
			const modal = document.createElement('div');
			modal.className = 'modal-backdrop';
			modal.innerHTML = `
						<div class="modal contact-modal">
							<div class="modal-body">
								<div class="modal-left">
									<h2>${title}</h2>
									<div class="contact-content">
										${content}
									</div>
								</div>
								<div class="modal-right">
									<button class="close-modal">Cerrar</button>
								</div>
							</div>
						</div>
					`;
			document.body.appendChild(modal);
			const innerModal = modal.querySelector('.modal');
			innerModal.classList.add('enter');
			modal.addEventListener('click', (e) => {
				if (e.target === modal || e.target.classList.contains('close-modal')) {
					modal.remove();
				}
			});
			modal.classList.add('enter');
		};

		(async function loadAndRender() {

			try {
				const capitalize = (s) => { if (!s) return s; s = String(s); return s.charAt(0).toUpperCase() + s.slice(1); };

				const res = await fetch('./cliente/php/obtener_productos.php');
				const json = await res.json();
				const products = Array.isArray(json) ? json : [];

				// Obtener promociones
				const resPromos = await fetch('./cliente/php/obtener_promociones.php');
				const promosJson = await resPromos.json();
				const promos = Array.isArray(promosJson) ? promosJson : [];

				const productsEl = document.getElementById('products');
				const groups = products.reduce((acc, p) => { (acc[p.tipo] = acc[p.tipo] || []).push(p); return acc; }, {});
				// ayudante para normalizar ruta de imagen
				const resolveImg = (img) => {
					if (!img) return 'img/Pizzaconmigo.png';
					if (img.startsWith('http') || img.startsWith('/')) return img;
					if (img.indexOf('empleado/') === 0) return img;
					if (img.startsWith('../')) return img; // Handle ../ prefixed paths
					// Mapear rutas de productos y promociones a empleado/productos/img/ y empleado/promociones/img/
					if (img.indexOf('productos/') === 0) return 'empleado/productos/img/' + img.substring(14);
					if (img.indexOf('promos/') === 0) return 'empleado/promociones/img/' + img.substring(6);
					// Las imágenes de promociones ya tienen el formato correcto, solo necesitan prefijo si no lo tienen
					if (img.indexOf('empleado/promociones/img/') === 0) return img;
					if (img.indexOf('promo_') === 0) return 'empleado/promociones/img/' + img;
					return 'empleado/productos/img/' + img;
				};

				// Carrusel de promociones (si hay)
				if (promos.length) {
					const promoSection = document.createElement('section');
					promoSection.className = 'promo-section';
					promoSection.innerHTML = `<h2 class="section-title"></h2>`;
					const carousel = document.createElement('div');
					carousel.id = 'promoCarousel';
					carousel.innerHTML = `
														<div class="carousel-viewport">
															<div class="carousel-track"></div>
														</div>
														<button class="carousel-prev" aria-label="Anterior promoción">‹</button>
														<button class="carousel-next" aria-label="Siguiente promoción">›</button>
														<div class="carousel-indicators"></div>
													`;
					promoSection.appendChild(carousel);
					document.body.insertBefore(promoSection, document.getElementById('products'));
					const track = carousel.querySelector('.carousel-track');
					const indicators = carousel.querySelector('.carousel-indicators');
					promos.forEach((p, i) => {
						const slide = document.createElement('div'); slide.className = 'promo-slide';
						const priceDisplay = '$' + (p.precio || 0).toLocaleString('es-UY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
						const productosList = p.productos && p.productos.length ? p.productos.map(prod => `<li>${prod.nombre} (x${prod.cantidad})</li>`).join('') : '<li>Productos incluidos en la promoción</li>';
						slide.innerHTML = `
															<div class="promo-content">
																<img src="${resolveImg(p.imagen)}" alt="${p.nombre}" style="border-radius:12px;width:20em;height:auto;margin:0 auto;padding:0;" />
																<div class="promo-text">
																	<h3>${p.nombre}</h3>
																	<p>${p.descripcion || ''}</p>
																	<div class="price">${priceDisplay}</div>
																	<ul>
																		${productosList}
																	</ul>
																	<button class="open-modal" data-id="${p.id_promocion}">Agregar al Carrito</button>
																</div>
															</div>
														`;
						track.appendChild(slide);
						const dot = document.createElement('button');
						dot.className = 'carousel-dot';
						dot.setAttribute('aria-label', `Ir a promoción ${i + 1}: ${p.nombre}`);
						dot.addEventListener('click', () => { idx = i; show(idx); });
						indicators.appendChild(dot);
					});
					// comportamiento del carrusel
					let idx = 0; const slides = track.children;
					function show(i) { track.style.transform = `translateX(${-i * 100}%)`; updateIndicators(); }
					function updateIndicators() {
						const dots = indicators.children;
						for (let j = 0; j < dots.length; j++) dots[j].classList.toggle('active', j === idx);
					}
					updateIndicators();
					carousel.querySelector('.carousel-next').addEventListener('click', () => { idx = (idx + 1) % slides.length; show(idx); });
					carousel.querySelector('.carousel-prev').addEventListener('click', () => { idx = (idx - 1 + slides.length) % slides.length; show(idx); });

					// Actualiza el texto del indicador de la diapositiva actual
					function updateCurrentSlide() {
						const currentPromo = promos[idx];
						const currentDot = indicators.children[idx];
						if (currentDot) {
							currentDot.setAttribute('aria-label', `Promoción actual: ${currentPromo.nombre}`);
						}
					}
					updateCurrentSlide();
					// Se actualiza al cambiar la diapositiva
					const originalShow = show;
					show = function(i) {
						originalShow(i);
						updateCurrentSlide();
					};
					let auto = setInterval(() => { idx = (idx + 1) % slides.length; show(idx); }, 4500);
					carousel.addEventListener('mouseenter', () => clearInterval(auto));
					carousel.addEventListener('mouseleave', () => { auto = setInterval(() => { idx = (idx + 1) % slides.length; show(idx); }, 4500); });
				}

				Object.keys(groups).forEach(tipo => {
					const displayTipo = capitalize(tipo);
					const section = document.createElement('section');
					section.className = 'product-section';
					section.id = `${tipo.toLowerCase()}-section`;
					section.innerHTML = `<h2 class="section-title">${displayTipo}</h2><div class="section-grid"></div>`;
					const grid = section.querySelector('.section-grid');
					const productsToShow = groups[tipo].slice(0, 5);
					productsToShow.forEach(p => {
						const card = document.createElement('article');
						card.className = 'product-card';
						const imgSrc = resolveImg(p.imagen);
						const priceDisplay = '$' + (p.precio_base || 0).toLocaleString('es-UY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
						card.innerHTML = `
													<img src="${imgSrc}" alt="${p.nombre}" class="product-img" style="border-radius:12px;width:15em;height:auto;margin:0 auto;padding:0;"/>
													<h3>${p.nombre}</h3>
													<div class="product-meta">
														<span class="product-type">${displayTipo}</span>
														<span class="price">${priceDisplay}</span>
													</div>
													<p class="muted">${p.descripcion || ''}</p>
																										<button data-id="${p.id_producto}" class="open-modal">+ Agregar</button>
												`;
						grid.appendChild(card);
					});
					if (groups[tipo].length >= 1) {
						const verMasBtn = document.createElement('a');
						verMasBtn.href = 'menu_completo.php';
						verMasBtn.className = 'ver-mas-btn';
						verMasBtn.textContent = 'Ver más';
						grid.appendChild(verMasBtn);
					}
					productsEl.appendChild(section);
				});

				// Poblar botones de sección (pizzas, hamburguesas, etc.) en el encabezado y una barra inferior adhesiva para saltos rápidos
				const buttonsContainer = document.getElementById('sectionButtons');
				const bottomBar = document.createElement('div');
				bottomBar.id = 'sectionButtonsBottom';
				bottomBar.className = 'section-buttons-bottom';
				document.body.appendChild(bottomBar);
				bottomBar.style.opacity = '1';
				bottomBar.style.pointerEvents = 'auto';
				const tipos = Object.keys(groups);
				if (buttonsContainer) {
					// No hay botones en el encabezado, las categorías están en la barra inferior
				}
				// Agregar botón de promociones primero
				const promoBtn = document.createElement('button');
				promoBtn.type = 'button';
				promoBtn.className = 'section-button';
				promoBtn.textContent = 'Promociones';
				promoBtn.addEventListener('click', () => {
					const promoSection = document.querySelector('.promo-section');
					if (promoSection) promoSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
				});
				bottomBar.appendChild(promoBtn);
				// Agregar botones dinámicos para la barra inferior
				tipos.forEach(tipo => {
					const displayTipo = capitalize(tipo);
					const btn = document.createElement('button');
					btn.type = 'button';
					btn.className = 'section-button';
					btn.textContent = displayTipo;
					btn.addEventListener('click', () => {
						const section = document.getElementById(`${tipo.toLowerCase()}-section`);
						if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
					});
					bottomBar.appendChild(btn);
				});

				// Botón independiente de desplazamiento hacia arriba en la parte inferior izquierda
				const scrollToTopBtn = document.createElement('button');
				scrollToTopBtn.id = 'scrollToTop';
				scrollToTopBtn.className = 'scroll-to-top';
				scrollToTopBtn.innerHTML = '↑';
				scrollToTopBtn.setAttribute('aria-label', 'Volver arriba');
				document.body.appendChild(scrollToTopBtn);
				scrollToTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));


				// Extraer paleta de colores de la imagen del logo y aplicar a variables CSS (lado cliente)
				const applyPaletteFromLogo = (src) => {
					return new Promise((resolve) => {
						const img = new Image();
						img.crossOrigin = '';
						img.src = src;
						img.onload = () => {
							try {
								const w = 64, h = 64;
								const c = document.createElement('canvas'); c.width = w; c.height = h;
								const ctx = c.getContext('2d'); ctx.drawImage(img, 0, 0, w, h);
								const data = ctx.getImageData(0, 0, w, h).data;
								let r = 0, g = 0, b = 0, count = 0;
								for (let i = 0; i < data.length; i += 4 * 4) { r += data[i]; g += data[i + 1]; b += data[i + 2]; count++; }
								r = Math.round(r / count); g = Math.round(g / count); b = Math.round(b / count);
								const luminance = (0.2126 * r + 0.7152 * g + 0.0722 * b);
								const textColor = luminance < 140 ? '#ffffff' : '#000000';
								const accent = `rgb(${r}, ${g}, ${b})`;
								// Versión más oscura
								const dark = `rgb(${Math.max(0, r - 40)}, ${Math.max(0, g - 40)}, ${Math.max(0, b - 40)})`;
								document.documentElement.style.setProperty('--modal-accent', accent);
								document.documentElement.style.setProperty('--modal-accent-2', dark);
								document.documentElement.style.setProperty('--modal-text', textColor);
								resolve({ r, g, b });
							} catch (e) { resolve(null); }
						};
						img.onerror = () => resolve(null);
					});
				};
				// aplicarPaletaDesdeLogo('img/Pizzaconmigo.png').then(() => {
				// Asegurar que el texto sea siempre negro para legibilidad en fondo blanco del modal
				// 	document.documentElement.style.setProperty('--modal-text', '#000000');
				// }).catch(() => { });

				// Delegar clics para abrir página de detalle del producto
				productsEl.addEventListener('click', (e) => {
					const btn = e.target.closest('button.open-modal');
					if (!btn) return;
					const id = btn.getAttribute('data-id');
					window.location.href = 'cliente/producto_detalle.php?id=' + id;
				});

				// Delegar clics para abrir página de detalle de promociones
				document.addEventListener('click', (e) => {
					const btn = e.target.closest('button.open-modal[data-id]');
					if (!btn) return;
					
					// Verificar si el botón está dentro del carrusel de promociones
					const promoSlide = btn.closest('.promo-slide');
					if (promoSlide) {
						const id = btn.getAttribute('data-id');
						window.location.href = 'cliente/producto_detalle.php?promo=' + id;
					}
				});

				// El carrito es manejado por js/cart.js

			} catch (err) {
				console.error('Error al cargar productos', err);
				document.getElementById('products').innerHTML = '<p class="muted">No se pudieron cargar productos desde el servidor.</p>';
			}

			// Crear pie de página (cuatro columnas)
			const footer = document.createElement('footer');
			footer.className = 'site-footer';
			footer.innerHTML = `
						<div class="footer-inner">
							<div class="footer-col footer-brand">
								<h4>Pizza Conmigo © 2025</h4>
							</div>
							<div class="footer-col footer-contact">
								<h4>Redes Sociales</h4>
								<div class="contact-buttons">
									<a href="https://www.instagram.com/pizzaconmigo" target="_blank" rel="noopener">Instagram</a>
									<a href="https://wa.me/1234567890" target="_blank" rel="noopener">WhatsApp</a>
								</div>
								<p><strong>Teléfono fijo:</strong> 47344688</p>
							</div>
							<div class="footer-col footer-info">
								<h4>Información</h4>
								<p><strong>Horarios:</strong> 19:30 a 00:00, cerrado domingos y lunes.</p>
								<p><strong>Ofertas:</strong> Todos los martes, miércoles y jueves.</p>
								<p><strong>Métodos de pago:</strong> Efectivo, POS o transferencia bancaria.</p>
								<p><strong>Pedidos:</strong> Para delivery o retiro en el local.</p>
								<p><strong>Tiempos de entrega:</strong> 15 a 30 minutos para ambos.</p>
							</div>
						</div>
					`;
			document.body.appendChild(footer);

			// Inicializa la funcion carrito
			initCart();
		})();

		// no hay global onCheckout aquí; manejado dentro del alcance del cargador arriba

		// Agregar animación de click al botón "Ver Menú" con scroll suave
		document.getElementById('ver-menu-btn').addEventListener('click', function(e) {
			e.preventDefault(); // Prevenir el comportamiento por defecto del enlace
			const targetSection = document.getElementById('products');
			if (targetSection) {
				// Agregar clase de animación para el movimiento deslizante
				this.classList.add('sliding-down');
				// Scroll suave hacia la sección del menú
				targetSection.scrollIntoView({
					behavior: 'smooth',
					block: 'start'
				});
				// Remover la clase después de la animación
				setTimeout(() => {
					this.classList.remove('sliding-down');
				}, 300);
			}
		});

		// Inicia la funcionalidad pedidos
		if (typeof createOrderModal === 'function') {
		    createOrderModal();
		}
		const ordersBtn = document.getElementById('ordersBtn');
		if (ordersBtn && typeof openOrderModal === 'function') {
		    ordersBtn.addEventListener('click', openOrderModal);
		}
	</script>
	<script src="js/cart.js"></script>
	<script src="cliente/js/pedidos.js"></script>
</body>

</html>
