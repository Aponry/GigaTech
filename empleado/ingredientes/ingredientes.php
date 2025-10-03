<?php
require_once __DIR__ . '/../../conexion.php';

$result = $conexion->query("SHOW COLUMNS FROM ingrediente LIKE 'tipo_producto'");
$row = $result->fetch_assoc();
preg_match_all("/'([^']+)'/", $row['Type'], $matches);
$tipos = $matches[1];
?>
<link rel="stylesheet" href="style.css">
<h1>Ingredientes</h1>

<button onclick="location.href='../menu.php'">Volver</button>
<button id="agregar">Agregar</button>
<button id="ver">Ver</button>

<div id="formulario"></div>
<div id="lista"></div>

<script>
const tipos = <?php echo json_encode($tipos); ?>;

function verLista() {
    fetch('listar_ingredientes.php')
        .then(r => r.json())
        .then(datos => {
            const lista = document.getElementById('lista');
            lista.innerHTML = '';
            document.getElementById('formulario').innerHTML = '';

            if (!datos.length) { lista.innerText = 'No hay ingredientes'; return; }

            let tabla = document.createElement('table');
            let encabezado = tabla.insertRow();
            ['ID', 'Nombre', 'Tipo', 'Costo', 'Acciones'].forEach(h => {
                let th = document.createElement('th'); th.innerText = h; encabezado.appendChild(th);
            });

            datos.forEach(i => {
                let fila = tabla.insertRow();
                [i.id_ingrediente, i.nombre, i.tipo_producto, i.costo].forEach(d => {
                    let cel = fila.insertCell(); cel.innerText = d;
                });
                let celAcc = fila.insertCell();
                let btn = document.createElement('button'); btn.innerText = 'Editar';
                btn.addEventListener('click', () => editar(i));
                celAcc.appendChild(btn);
            });

            lista.appendChild(tabla);
        });
}

document.getElementById('agregar').addEventListener('click', () => {
    let s = '<select name="tipo_producto">';
    tipos.forEach(t => s += `<option value="${t}">${t}</option>`); s += '</select>';
    document.getElementById('formulario').innerHTML = `<form id="formAgregar">
        <input name="nombre" placeholder="Nombre" required>
        ${s}
        <input name="costo" placeholder="Costo" required>
        <button>Agregar</button>
    </form>`;
    document.getElementById('lista').innerHTML = '';

    document.getElementById('formAgregar').addEventListener('submit', e => {
        e.preventDefault();
        fetch('agregar_ingrediente.php', { method: 'POST', body: new FormData(e.target) }).then(() => verLista());
    });
});

function editar(i) {
    let s = '<select name="tipo_producto">';
    tipos.forEach(t => s += `<option value="${t}" ${t === i.tipo_producto ? 'selected' : ''}>${t}</option>`); s += '</select>';
    document.getElementById('formulario').innerHTML = `<form id="formEditar">
        <input type="hidden" name="id_ingrediente" value="${i.id_ingrediente}">
        <input name="nombre" value="${i.nombre}" placeholder="Nombre" required>
        ${s}
        <input name="costo" value="${i.costo}" placeholder="Costo" required>
        <button>Guardar</button>
        <button type="button" id="borrar">Borrar</button>
    </form>`;

    document.getElementById('formEditar').addEventListener('submit', e => {
        e.preventDefault();
        fetch('editar_ingrediente.php', { method: 'POST', body: new FormData(e.target) }).then(() => verLista());
    });

    document.getElementById('borrar').addEventListener('click', () => {
        if (!confirm('Borrar?')) return;
        let f = new FormData();
        f.append('id_ingrediente', i.id_ingrediente);
        fetch('borrar_ingrediente.php', { method: 'POST', body: f })
            .then(res => res.json())
            .then(data => {
                if (!data.ok) {
                    alert(data.error);
                } else {
                    verLista();
                }
            });
    });
}

document.getElementById('ver').addEventListener('click', verLista);
verLista();
</script>
