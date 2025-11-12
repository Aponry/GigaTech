<?php
require_once '../tcpdf/tcpdf.php';
require_once '../conexion.php';

if (!isset($_GET['id_pedido'])) {
    die('ID de pedido no proporcionado');
}

$id_pedido = intval($_GET['id_pedido']);

// Obtener pedido
$sql_pedido = "SELECT * FROM pedido WHERE id_pedido = ?";
$stmt_pedido = $conexion->prepare($sql_pedido);
$stmt_pedido->bind_param('i', $id_pedido);
$stmt_pedido->execute();
$result_pedido = $stmt_pedido->get_result();
$pedido = $result_pedido->fetch_assoc();
$stmt_pedido->close();

if (!$pedido) {
    die('Pedido no encontrado');
}

// Obtener detalles
$sql_detalles = "SELECT dp.*, p.nombre as nombre_producto, prom.nombre as nombre_promocion
                 FROM detalle_pedido dp
                 LEFT JOIN productos p ON dp.id_producto = p.id_producto
                 LEFT JOIN promociones prom ON dp.id_promocion = prom.id_promocion
                 WHERE dp.id_pedido = ?";
$stmt_detalles = $conexion->prepare($sql_detalles);
$stmt_detalles->bind_param('i', $id_pedido);
$stmt_detalles->execute();
$result_detalles = $stmt_detalles->get_result();
$detalles = [];
while ($row = $result_detalles->fetch_assoc()) {
    $detalles[] = $row;
}
$stmt_detalles->close();

// Crear PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('PizzaConmigo');
$pdf->SetTitle('Receta del Pedido #' . $id_pedido);
$pdf->SetSubject('Detalles del Pedido');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'PizzaConmigo - Receta del Pedido', 0, 1, 'C');

$pdf->Ln(10);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'ID del Pedido: ' . $pedido['id_pedido'], 0, 1);
$pdf->Cell(0, 10, 'Estado: ' . ucfirst($pedido['estado']), 0, 1);
$pdf->Cell(0, 10, 'Fecha: ' . date('d/m/Y H:i:s', strtotime($pedido['fecha'])), 0, 1);
$pdf->Cell(0, 10, 'Total: $' . number_format($pedido['total'], 2), 0, 1);

$pdf->Ln(10);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Datos del Cliente:', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 8, 'Nombre: ' . htmlspecialchars($pedido['nombre_cliente']), 0, 1);
if (!empty($pedido['email_cliente'])) {
    $pdf->Cell(0, 8, 'Email: ' . htmlspecialchars($pedido['email_cliente']), 0, 1);
}
$pdf->Cell(0, 8, 'Teléfono: ' . htmlspecialchars($pedido['telefono_cliente']), 0, 1);
if (!empty($pedido['direccion_cliente'])) {
    $pdf->Cell(0, 8, 'Dirección: ' . htmlspecialchars($pedido['direccion_cliente']), 0, 1);
}
if (!empty($pedido['notas'])) {
    $pdf->Cell(0, 8, 'Notas: ' . htmlspecialchars($pedido['notas']), 0, 1);
}

$pdf->Ln(10);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Detalles del Pedido:', 0, 1);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(80, 8, 'Producto/Promoción', 1);
$pdf->Cell(20, 8, 'Cantidad', 1);
$pdf->Cell(30, 8, 'Precio Unit.', 1);
$pdf->Cell(30, 8, 'Subtotal', 1);
$pdf->Ln();

$pdf->SetFont('helvetica', '', 10);
foreach ($detalles as $detalle) {
    $nombre = $detalle['nombre_producto'] ?: $detalle['nombre_promocion'];
    $pdf->Cell(80, 8, htmlspecialchars($nombre), 1);
    $pdf->Cell(20, 8, $detalle['cantidad'], 1);
    $pdf->Cell(30, 8, '$' . number_format($detalle['precio_unitario'], 2), 1);
    $pdf->Cell(30, 8, '$' . number_format($detalle['subtotal'], 2), 1);
    $pdf->Ln();
}

$pdf->Output('receta_pedido_' . $id_pedido . '.pdf', 'D');
?>