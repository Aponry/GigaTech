<?php
$tipos = ['pizza', 'bebida', 'hamburguesa', 'otro'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Alta de Productos</title>
</head>
<body>
  <h1>Alta de Productos</h1>
  <form action="Insertar.php" method="POST">
    <div>
      <label for="nombre">Producto</label><br/>
      <input type="text" id="nombre" name="nombre" required />
    </div>
    <div>
      <label for="tipo">Tipo de Producto 3dsahk</label><br/>
      <select id="tipo" name="tipo" required>
        <option value="" selected disabled hidden>Seleccioná tipo</option>
        <?php foreach ($tipos as $t): ?>
          <option value="<?php echo htmlspecialchars($t, ENT_QUOTES); ?>"><?php echo htmlspecialchars($t, ENT_QUOTES); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="precio_base">Precio Base</label><br/>
      <input type="number" step="0.01" id="precio_base" name="precio_base" required />
    </div>
    <div>
      <input type="submit" value="Guardar Producto" />
    </div>
  </form>
  <div>
    <a href="Listar.php">Ver productos cargados</a>
  </div>
</body>
</html>
