<?php
require_once __DIR__ . '/../../conexion.php';

// Traer productos para seleccionar en la promo
$prodRes = $conexion->query("SELECT id_producto, nombre FROM productos ORDER BY nombre");
$productos = [];
while ($r = $prodRes->fetch_assoc())
    $productos[] = $r;
?>

<link rel="stylesheet" href="style.css">
<h1>Promociones</h1>

<button onclick="location.href='../menu1.html'">Volver</button>
<button id="btnAgregar">Agregar</button>
<button id="btnVer">Ver</button>

<div id="formulario"></div>
<div id="lista"></div>

<script>
    const productos = <?php echo json_encode($productos); ?>;

    function verLista() {
        fetch('listar_promociones.php')
            .then(r => r.json())
            .then(datos => {
                const lista = document.getElementById('lista');
                lista.innerHTML = '';
                document.getElementById('formulario').innerHTML = '';
                if (!datos.length) { lista.innerText = 'No hay promociones'; return; }

                let tabla = document.createElement('table');
                let encabezado = tabla.insertRow();
                ['ID', 'Imagen', 'Nombre', 'Precio', 'Descripción', 'Productos', 'Activo', 'Acciones'].forEach(h => {
                    let th = document.createElement('th'); th.innerText = h; encabezado.appendChild(th);
                });

                datos.forEach(promo => {
                    let fila = tabla.insertRow();
                    fila.insertCell().innerText = promo.id_promocion;

                    let celImg = fila.insertCell();
                    let img = document.createElement('img');
                    img.src = promo.imagen ? promo.imagen : '../../images/perfil.png';
                    img.style.width = '60px';
                    img.style.height = '60px';
                    img.style.objectFit = 'cover';
                    celImg.appendChild(img);

                    fila.insertCell().innerText = promo.nombre;
                    fila.insertCell().innerText = promo.precio;
                    fila.insertCell().innerText = promo.descripcion ?? '';

                    let celProds = fila.insertCell();
                    if (promo.productos) {
                        celProds.innerText = promo.productos.map(p => `${p.nombre} x ${p.cantidad}`).join(', ');
                    } else celProds.innerText = '';

                    let celActivo = fila.insertCell();
                    const chk = document.createElement('input');
                    chk.type = 'checkbox';
                    chk.checked = promo.activo == 1;
                    chk.addEventListener('change', () => {
                        fetch('editar_promocion.php', { method: 'POST', body: new URLSearchParams({ id_promocion: promo.id_promocion, activo: chk.checked ? 1 : 0 }) });
                    });
                    celActivo.appendChild(chk);

                    let celAcc = fila.insertCell();
                    let btn = document.createElement('button'); btn.innerText = 'Editar';
                    btn.addEventListener('click', () => mostrarFormulario(promo));
                    celAcc.appendChild(btn);
                });

                lista.appendChild(tabla);
            });
    }

    function mostrarFormulario(promo = null) {
        let seleccionados = [];

        if (promo && promo.productos) seleccionados = promo.productos;

        const contenedor = document.getElementById('formulario');
        contenedor.innerHTML = '';

        const form = document.createElement('form');
        form.id = 'formPromo';
        form.enctype = 'multipart/form-data';

        if (promo) {
            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id_promocion';
            inputId.value = promo.id_promocion;
            form.appendChild(inputId);
        }

        const inputNombre = document.createElement('input');
        inputNombre.name = 'nombre'; inputNombre.placeholder = 'Nombre';
        inputNombre.value = promo ? promo.nombre : '';
        form.appendChild(inputNombre);

        const inputPrecio = document.createElement('input');
        inputPrecio.name = 'precio'; inputPrecio.placeholder = 'Precio';
        inputPrecio.value = promo ? promo.precio : '';
        form.appendChild(inputPrecio);

        const inputDesc = document.createElement('input');
        inputDesc.name = 'descripcion'; inputDesc.placeholder = 'Descripción';
        inputDesc.value = promo ? promo.descripcion : '';
        form.appendChild(inputDesc);

        const inputImagen = document.createElement('input');
        inputImagen.type = 'file';
        inputImagen.name = 'imagen';
        inputImagen.accept = 'image/*';
        if (!promo) inputImagen.required = true;
        inputImagen.onchange = () => {
            const preview = document.getElementById('previewPromo');
            if (preview) preview.src = window.URL.createObjectURL(inputImagen.files[0]);
        };
        form.appendChild(inputImagen);

        const imgPreview = document.createElement('img');
        imgPreview.id = 'previewPromo';
        imgPreview.style.width = '80px';
        imgPreview.style.height = '80px';
        imgPreview.style.objectFit = 'cover';
        imgPreview.style.marginTop = '6px';
        imgPreview.src = promo && promo.imagen ? promo.imagen : '../../images/perfil.png';
        form.appendChild(imgPreview);

        const divProd = document.createElement('div');
        const selectProd = document.createElement('select');
        const optDefault = document.createElement('option');
        optDefault.value = ''; optDefault.innerText = 'Elegir producto'; selectProd.appendChild(optDefault);

        productos.forEach(p => {
            const o = document.createElement('option');
            o.value = p.id_producto; o.innerText = p.nombre;
            selectProd.appendChild(o);
        });

        const inputCant = document.createElement('input');
        inputCant.type = 'number'; inputCant.min = 1; inputCant.value = 1;

        const btnAgregarProd = document.createElement('button');
        btnAgregarProd.type = 'button'; btnAgregarProd.innerText = 'Agregar producto a la promoción';

        const divElegidos = document.createElement('div');

        btnAgregarProd.addEventListener('click', () => {
            const id = selectProd.value;
            const cant = parseInt(inputCant.value);
            if (!id || cant <= 0) return;
            let existente = seleccionados.find(p => p.id == id);
            if (existente) existente.cantidad += cant;
            else {
                let prod = productos.find(pr => pr.id_producto == id);
                seleccionados.push({ id: id, nombre: prod.nombre, cantidad: cant });
            }
            actualizarLista();
        });

        function actualizarLista() {
            divElegidos.innerHTML = '';
            seleccionados.forEach((p, i) => {
                const fila = document.createElement('div');
                fila.innerText = `${p.nombre} x ${p.cantidad} `;
                const btn = document.createElement('button');
                btn.type = 'button'; btn.innerText = 'Quitar';
                btn.addEventListener('click', () => { seleccionados.splice(i, 1); actualizarLista(); });
                fila.appendChild(btn);
                divElegidos.appendChild(fila);
            });
        }

        divProd.appendChild(selectProd); divProd.appendChild(inputCant); divProd.appendChild(btnAgregarProd); divProd.appendChild(divElegidos);
        form.appendChild(divProd);

        const btnSubmit = document.createElement('button');
        btnSubmit.innerText = promo ? 'Guardar' : 'Agregar';
        form.appendChild(btnSubmit);

        if (promo) {
            const btnBorrar = document.createElement('button');
            btnBorrar.type = 'button'; btnBorrar.innerText = 'Borrar';
            btnBorrar.addEventListener('click', () => {
                if (!confirm('Borrar?')) return;
                const f = new FormData();
                f.append('id_promocion', promo.id_promocion);
                fetch('borrar_promocion.php', { method: 'POST', body: f }).then(() => location.reload());
            });
            form.appendChild(btnBorrar);
        }

        form.addEventListener('submit', e => {
            e.preventDefault();
            const formData = new FormData(form);
            seleccionados.forEach(p => formData.append(`productos[${p.id}]`, p.cantidad));
            fetch(promo ? 'editar_promocion.php' : 'agregar_promocion.php', { method: 'POST', body: formData })
                .then(() => location.reload());
        });

        contenedor.appendChild(form);
        actualizarLista();
    }

    document.getElementById('btnAgregar').addEventListener('click', () => mostrarFormulario());
    document.getElementById('btnVer').addEventListener('click', verLista);
    verLista();
</script>