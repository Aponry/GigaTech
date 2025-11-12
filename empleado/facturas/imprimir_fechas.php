<?php
// imprimir_fechas.php - Generar PDF con facturas en un rango de fechas

session_start();

// Verificación de seguridad para acceso de empleado
if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'empleado') {
    die('Acceso denegado');
}

require_once '../../conexion.php';
require_once '../../tcpdf/tcpdf.php';

// Obtener fechas
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

if (!$fecha_desde || !$fecha_hasta) {
    die('Fechas requeridas');
}

// Query para pedidos en el rango
$query = "SELECT p.id_pedido, p.fecha, p.total, p.estado, p.nombre_cliente, p.telefono_cliente, p.metodo_pago
          FROM pedido p
          WHERE DATE(p.fecha) BETWEEN ? AND ?
          ORDER BY p.fecha DESC";

$stmt = $conexion->prepare($query);
$stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
$stmt->execute();
$result = $stmt->get_result();

$pedidos = [];
while ($row = $result->fetch_assoc()) {
    $pedidos[] = $row;
}
$stmt->close();

// Crear PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('PizzaConmigo');
$pdf->SetTitle('Facturas del ' . date('d/m/Y', strtotime($fecha_desde)) . ' al ' . date('d/m/Y', strtotime($fecha_hasta)));
$pdf->SetSubject('Facturas por Fechas');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Facturas del ' . date('d/m/Y', strtotime($fecha_desde)) . ' al ' . date('d/m/Y', strtotime($fecha_hasta)), 0, 1, 'C');

$pdf->Ln(10);

if (empty($pedidos)) {
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'No hay pedidos en el rango de fechas seleccionado.', 0, 1, 'C');
} else {
    // Cabecera de tabla
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(25, 7, 'ID', 1);
    $pdf->Cell(45, 7, 'Fecha', 1);
    $pdf->Cell(35, 7, 'Total', 1);
    $pdf->Cell(50, 7, 'Cliente', 1);
    $pdf->Cell(35, 7, 'Teléfono', 1);
    $pdf->Ln();

    $pdf->SetFont('helvetica', '', 9);
    $totalRango = 0;
    foreach ($pedidos as $pedido) {
        $pdf->Cell(25, 6, $pedido['id_pedido'], 1);
        $pdf->Cell(45, 6, date('d/m/Y H:i', strtotime($pedido['fecha'])), 1);
        $pdf->Cell(35, 6, '$' . number_format($pedido['total'], 2), 1);
        $pdf->Cell(50, 6, $pedido['nombre_cliente'], 1);
        $pdf->Cell(35, 6, $pedido['telefono_cliente'], 1);
        $pdf->Ln();
        $totalRango += $pedido['total'];
    }

    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Total del Rango: $' . number_format($totalRango, 2), 0, 1, 'R');
}

$pdf->Output('facturas_' . $fecha_desde . '_a_' . $fecha_hasta . '.pdf', 'I');
?>