<?php
include 'conexion.php';

$sql = "SELECT * FROM producto";
$resultado = $conexion->query($sql);
//Script para listar todos los productos con boton de eliminar(Tambien temporal)
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Lista de Productos</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
  <div class="max-w-4xl mx-auto bg-white p-6 rounded-xl shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-gray-700">Productos Cargados</h1>

    <?php if ($resultado->num_rows > 0): ?>
      <table class="w-full table-auto">
        <thead>
          <tr class="bg-gray-200 text-left">
            <th class="p-2">ID</th>
            <th class="p-2">Nombre</th>
            <th class="p-2">Tipo</th>
            <th class="p-2">Precio</th>
            <th class="p-2">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php while($fila = $resultado->fetch_assoc()): ?>
            <tr class="border-b">
              <td class="p-2"><?php echo $fila["id_producto"]; ?></td>
              <td class="p-2"><?php echo $fila["nombre"]; ?></td>
              <td class="p-2"><?php echo $fila["tipo"]; ?></td>
              <td class="p-2">$<?php echo $fila["precio_base"]; ?></td>
              <td class="p-2">
                <form action="eliminar.php" method="POST" onsubmit="return confirm('¿Seguro que querés eliminar?');">
                  <input type="hidden" name="id" value="<?php echo $fila["id_producto"]; ?>">
                  <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-gray-500">No hay productos cargados.</p>
    <?php endif; ?>

  </div>
     <a href="index.html" class="text-sm text-blue-600 hover:underline">Volver atras</a>
</body>
</html>

<?php $conexion->close(); ?>
