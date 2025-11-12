const tiposIngrediente = ['pizza', 'hamburguesa'];
const displayTipos = { pizza: 'Pizza', hamburguesa: 'Hamburger' };

// Poblar menú inferior
const ingredientesButtons = document.getElementById('ingredientes-buttons');
if (ingredientesButtons) {
  tiposIngrediente.forEach(tipo => {
    const btn = document.createElement('button');
    btn.className = 'section-button';
    btn.textContent = displayTipos[tipo];
    btn.addEventListener('click', () => {
      const section = document.getElementById('ingredientes-' + tipo);
      if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    ingredientesButtons.appendChild(btn);
  });
}

// Desplazarse hacia arriba
const scrollToTopBtn = document.getElementById('scrollToTop');
if (scrollToTopBtn) scrollToTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));