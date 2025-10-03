<?php
require_once __DIR__ . '/../../conexion.php';


$result = $conexion->query("SHOW COLUMNS FROM productos LIKE 'tipo'");
$row = $result->fetch_assoc();
preg_match_all("/'([^']+)'/", $row['Type'], $matches);
$tipos = $matches[1];
?>
<link rel="stylesheet" href="style.css">
<h1>Productos</h1>

<button onclick="location.href='../menu.php'">Volver</button>
<button id="btnAgregar">Agregar</button>
<button id="btnVer">Ver</button>

<div id="formulario"></div>
<div id="lista"></div>

<script>
const tipos = <?php echo json_encode($tipos); ?>;

function mostrarLista() {
    fetch('listar_productos.php')
        .then(r => r.json())
        .then(datos => {
            const lista = document.getElementById('lista');
            lista.innerHTML = '';
            document.getElementById('formulario').innerHTML = '';

            if (!datos.length) { lista.innerText = 'No hay productos'; return; }

            let tabla = document.createElement('table');
            let encabezado = tabla.insertRow();
            ['ID', 'Imagen', 'Nombre', 'Tipo', 'Precio', 'Descripción', 'Acciones'].forEach(h => {
                let th = document.createElement('th'); th.innerText = h; encabezado.appendChild(th);
            });

            datos.forEach(p => {
                let fila = tabla.insertRow();
                fila.insertCell().innerText = p.id_producto;

                let celImagen = fila.insertCell();
                let imgEl = document.createElement('img');
                imgEl.src = p.imagen ? ('../' + p.imagen) : '../images/default.png';
                imgEl.style.width = '60px';
                imgEl.style.height = '60px';
                imgEl.style.objectFit = 'cover';
                celImagen.appendChild(imgEl);

                [p.nombre, p.tipo, p.precio_base, p.descripcion].forEach(d => {
                    fila.insertCell().innerText = d;
                });

                let celAcc = fila.insertCell();
                let btn = document.createElement('button'); 
                btn.innerText = 'Editar';
                btn.addEventListener('click', () => editarProducto(p));
                celAcc.appendChild(btn);
            });

            lista.appendChild(tabla);
        });
}

document.getElementById('btnAgregar').addEventListener('click', () => {
    let sel = '<select name="tipo">';
    tipos.forEach(t => sel += `<option value="${t}">${t}</option>`);
    sel += '</select>';

    document.getElementById('formulario').innerHTML = `<form id="formAgregar" enctype="multipart/form-data">
        <input name="nombre" placeholder="Nombre" required>
        ${sel}
        <input name="precio" placeholder="Precio" required>
        <input name="descripcion" placeholder="Descripción">
        <input type="file" name="imagen" accept="image/*" required onchange="document.getElementById('previewAgregar').src = window.URL.createObjectURL(this.files[0])">
        <img id="previewAgregar" style="width:60px;height:60px;object-fit:cover;margin-top:5px;">
        <button>Agregar</button>
    </form>`;

    document.getElementById('lista').innerHTML = '';

    document.getElementById('formAgregar').addEventListener('submit', e => {
        e.preventDefault();
        fetch('agregar_producto.php', { method: 'POST', body: new FormData(e.target) })
            .then(() => location.reload());
    });
});

function editarProducto(p) {
    let sel = '<select name="tipo">';
    tipos.forEach(t => sel += `<option value="${t}" ${t === p.tipo ? 'selected' : ''}>${t}</option>`);
    sel += '</select>';

    document.getElementById('formulario').innerHTML = `<form id="formEditar" enctype="multipart/form-data">
        <input type="hidden" name="id_producto" value="${p.id_producto}">
        <input name="nombre" value="${p.nombre}" placeholder="Nombre" required>
        ${sel}
        <input name="precio" value="${p.precio_base}" placeholder="Precio" required>
        <input name="descripcion" value="${p.descripcion}" placeholder="Descripción">
        <div>Imagen actual: <img id="previewEditar" src="${p.imagen ? ('../' + p.imagen) : '../images/default.png'}" style="width:60px;height:60px;object-fit:cover;"></div>
        <input type="file" name="imagen" accept="image/*" onchange="document.getElementById('previewEditar').src = window.URL.createObjectURL(this.files[0])">
        <button>Guardar</button>
        <button type="button" id="btnBorrar">Borrar</button>
    </form>`;

    document.getElementById('formEditar').addEventListener('submit', e => {
        e.preventDefault();
        fetch('editar_producto.php', { method: 'POST', body: new FormData(e.target) })
            .then(() => location.reload());
    });

    document.getElementById('btnBorrar').addEventListener('click', () => {
        if (!confirm('Borrar producto?')) return;
        let f = new FormData();
        f.append('id_producto', p.id_producto);
        fetch('borrar_producto.php', { method: 'POST', body: f })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) alert(res.error);
                else location.reload();
            });
    });
}

document.getElementById('btnVer').addEventListener('click', mostrarLista);
mostrarLista();
</script>
