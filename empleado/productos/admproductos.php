<?php
require_once __DIR__ . '/../../conexion.php';

$result = $conexion->query("SHOW COLUMNS FROM productos LIKE 'tipo'");
$row = $result->fetch_assoc();
preg_match_all("/'([^']+)'/", $row['Type'], $matches);
$tipos = $matches[1];
?>

<h1>Productos</h1>

<button onclick="location.href='../menu1.html'">Volver</button>
<button id="agregar">Agregar</button>
<button id="ver">Ver</button>

<div id="formulario"></div>
<div id="lista"></div>

<script>
    const tipos = <?php echo json_encode($tipos); ?>;

    function verLista() {
        fetch('listar.php')
            .then(r => r.json())
            .then(datos => {
                const lista = document.getElementById('lista');
                lista.innerHTML = '';
                document.getElementById('formulario').innerHTML = '';

                if (!datos.length) { lista.innerText = 'No hay productos'; return; }

                let tabla = document.createElement('table');
                let encabezado = tabla.insertRow();
                ['ID', 'Nombre', 'Tipo', 'Precio', 'Descripción', 'Acciones'].forEach(h => {
                    let th = document.createElement('th'); th.innerText = h; encabezado.appendChild(th);
                });

                datos.forEach(p => {
                    let fila = tabla.insertRow();
                    [p.id_producto, p.nombre, p.tipo, p.precio_base, p.descripcion].forEach(d => {
                        let cel = fila.insertCell(); cel.innerText = d;
                    });
                    let celAcc = fila.insertCell();
                    let btn = document.createElement('button'); btn.innerText = 'Editar';
                    btn.addEventListener('click', () => editar(p));
                    celAcc.appendChild(btn);
                });

                lista.appendChild(tabla);
            });
    }

    document.getElementById('agregar').addEventListener('click', () => {
        let s = '<select name="tipo">';
        tipos.forEach(t => s += `<option value="${t}">${t}</option>`); s += '</select>';
        document.getElementById('formulario').innerHTML = `<form id="formAgregar">
        <input name="nombre" placeholder="Nombre">
        ${s}
        <input name="precio" placeholder="Precio">
        <input name="descripcion" placeholder="Descripción">
        <button>Agregar</button>
    </form>`;
        document.getElementById('lista').innerHTML = '';

        document.getElementById('formAgregar').addEventListener('submit', e => {
            e.preventDefault();
            fetch('añadir.php', { method: 'POST', body: new FormData(e.target) }).then(() => location.reload());
        });
    });

    function editar(p) {
        let s = '<select name="tipo">';
        tipos.forEach(t => s += `<option value="${t}" ${t === p.tipo ? 'selected' : ''}>${t}</option>`); s += '</select>';
        document.getElementById('formulario').innerHTML = `<form id="formEditar">
        <input type="hidden" name="id_producto" value="${p.id_producto}">
        <input name="nombre" value="${p.nombre}" placeholder="Nombre">
        ${s}
        <input name="precio" value="${p.precio_base}" placeholder="Precio">
        <input name="descripcion" value="${p.descripcion}" placeholder="Descripción">
        <button>Guardar</button>
        <button type="button" id="borrar">Borrar</button>
    </form>`;

        document.getElementById('formEditar').addEventListener('submit', e => {
            e.preventDefault();
            fetch('act.php', { method: 'POST', body: new FormData(e.target) }).then(() => location.reload());
        });

        document.getElementById('borrar').addEventListener('click', () => {
            if (!confirm('Borrar?')) return;
            let f = new FormData(); f.append('id_producto', p.id_producto);
            fetch('borrar.php', { method: 'POST', body: f }).then(() => location.reload());
        });
    }

    document.getElementById('ver').addEventListener('click', verLista);

    verLista();
</script>