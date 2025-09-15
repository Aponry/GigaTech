<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../conexion.php';

$sql = "SELECT p.id_promocion, p.nombre, p.precio, p.descripcion, p.imagen,
        GROUP_CONCAT(pp.id_producto, ':', pp.cantidad) as productos
        FROM promocion p
        LEFT JOIN promocion_producto pp ON p.id_promocion=pp.id_promocion
        GROUP BY p.id_promocion
        ORDER BY p.id_promocion DESC";

$res = $conexion->query($sql);
$rows = [];
if ($res)
        while ($r = $res->fetch_assoc()) {
                // si no hay imagen, usar placeholder
                if (!$r['imagen'])
                        $r['imagen'] = '../../images/perfil.png';
                $rows[] = $r;
        }

echo json_encode($rows);
