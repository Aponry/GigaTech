document.addEventListener('DOMContentLoaded', () => {
    const botones = Array.from(document.querySelectorAll('.tipoBtn'));
    const secciones = Array.from(document.querySelectorAll('.seccionTipo'));
    const tipoNav = document.querySelector('.tipo-nav');
    const menuToggle = document.getElementById('menuToggle');
    const dropdown = document.getElementById('dropdownMenu');

    function mostrar(tipo) {
        secciones.forEach(s => s.style.display = 'none');
        const el = document.getElementById('seccion-' + tipo);
        if (el) el.style.display = 'block';
        botones.forEach(b => b.classList.toggle('active', (b.dataset.tipo || '') === tipo));
        Array.from(dropdown?.querySelectorAll('.dropItem') || []).forEach(d => d.classList.toggle('active', d.dataset.tipo === tipo));
        if (dropdown && dropdown.classList.contains('open')) closeDropdown();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function openDropdown() {
        if (!dropdown) return;
        dropdown.classList.add('open');
        dropdown.setAttribute('aria-hidden', 'false');
        menuToggle.setAttribute('aria-expanded', 'true');
        document.documentElement.classList.add('menu-open');
        document.body.style.overflow = 'hidden';
        const first = dropdown.querySelector('.dropItem');
        if (first) first.focus();
    }
    function closeDropdown() {
        if (!dropdown) return;
        dropdown.classList.remove('open');
        dropdown.setAttribute('aria-hidden', 'true');
        menuToggle.setAttribute('aria-expanded', 'false');
        document.documentElement.classList.remove('menu-open');
        document.body.style.overflow = '';
    }
    function toggleDropdown() {
        if (!dropdown) return;
        if (dropdown.classList.contains('open')) closeDropdown();
        else openDropdown();
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDropdown();
        });
    }

    if (dropdown) {
        dropdown.addEventListener('click', (e) => {
            const btn = e.target.closest('.dropItem');
            if (!btn) return;
            const tipo = btn.dataset.tipo;
            if (!tipo) return;
            mostrar(tipo);
        });
    }

    if (tipoNav) {
        tipoNav.addEventListener('click', (e) => {
            const btn = e.target.closest('.tipoBtn');
            if (!btn) return;
            const tipo = btn.dataset.tipo;
            if (!tipo) return;
            mostrar(tipo);
        });
    }

    document.addEventListener('click', (e) => {
        if (!dropdown) return;
        if (!dropdown.contains(e.target) && e.target !== menuToggle) closeDropdown();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDropdown();
    });

    const defaultBtn = document.querySelector('.tipoBtn[data-tipo="pizza"]') || botones[0];
    if (defaultBtn) {
        mostrar(defaultBtn.dataset.tipo || (botones[0] && botones[0].dataset.tipo));
    }

    function updateNavVisibility() {
        if (window.innerWidth > 700) {
            if (dropdown) closeDropdown();
        }
    }
    updateNavVisibility();
    window.addEventListener('resize', updateNavVisibility);
});
