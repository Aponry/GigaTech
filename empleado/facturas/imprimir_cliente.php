<?php
// imprimir_cliente.php - Generar PDF con facturas de un cliente

session_start();

// Verificación de seguridad para acceso de empleado
if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'empleado') {
    die('Acceso denegado');
}

require_once '../../conexion.php';
require_once '../../tcpdf/tcpdf.php';

// Obtener teléfonos de los clientes
$telefonosStr = $_GET['telefonos'] ?? '';

if (!$telefonosStr) {
    die('Teléfonos requeridos');
}

$telefonos = array_map('trim', explode(',', $telefonosStr));
$placeholders = str_repeat('?,', count($telefonos) - 1) . '?';

// Query para pedidos de los clientes
$query = "SELECT p.id_pedido, p.fecha, p.total, p.estado, p.tipo_pedido, p.nombre_cliente, p.telefono_cliente, p.metodo_pago
          FROM pedido p
          WHERE p.telefono_cliente IN ($placeholders)
          ORDER BY p.fecha DESC";

$stmt = $conexion->prepare($query);
$stmt->bind_param(str_repeat('s', count($telefonos)), ...$telefonos);
$stmt->execute();
$result = $stmt->get_result();

$pedidos = [];
$clientes = [];
while ($row = $result->fetch_assoc()) {
    $pedidos[] = $row;
    $clientes[$row['telefono_cliente']] = $row['nombre_cliente'];
}
$stmt->close();

// Crear PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('PizzaConmigo');
$pdf->SetTitle('Facturas de Clientes - ' . implode(', ', array_unique($clientes)));
$pdf->SetSubject('Facturas de Clientes');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 16);
$title = count($telefonos) == 1 ? 'Facturas del Cliente - ' . reset($clientes) . ' (' . $telefonos[0] . ')' : 'Facturas de Clientes - ' . implode(', ', array_unique($clientes));
$pdf->Cell(0, 10, $title, 0, 1, 'C');

$pdf->Ln(10);

if (empty($pedidos)) {
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'No hay pedidos para este cliente.', 0, 1, 'C');
} else {
    // Cabecera de tabla
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(25, 7, 'ID', 1);
    $pdf->Cell(45, 7, 'Fecha', 1);
    $pdf->Cell(35, 7, 'Total', 1);
    $pdf->Cell(45, 7, 'Estado', 1);
    $pdf->Cell(40, 7, 'Método Pago', 1);
    $pdf->Ln();

    $pdf->SetFont('helvetica', '', 9);
    $totalCliente = 0;
    foreach ($pedidos as $pedido) {
        $pdf->Cell(25, 6, $pedido['id_pedido'], 1);
        $pdf->Cell(45, 6, date('d/m/Y H:i', strtotime($pedido['fecha'])), 1);
        $pdf->Cell(35, 6, '$' . number_format($pedido['total'], 2), 1);
        $pdf->Cell(45, 6, $pedido['estado'], 1);
        $pdf->Cell(40, 6, $pedido['metodo_pago'], 1);
        $pdf->Ln();
        $totalCliente += $pedido['total'];
    }

    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Total del Cliente: $' . number_format($totalCliente, 2), 0, 1, 'R');
}

$filename = count($telefonos) == 1 ? 'facturas_cliente_' . $telefonos[0] . '.pdf' : 'facturas_clientes_' . implode('_', $telefonos) . '.pdf';
$pdf->Output($filename, 'I');
?>