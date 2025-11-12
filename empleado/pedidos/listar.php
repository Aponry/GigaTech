<?php
// listar.php - Listar pedidos con filtros (JSON response)

session_start();

// Validación de sesión para acceso de empleado
if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'empleado') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

// Filtros
$status = $_GET['status'] ?? '';
$phone = $_GET['phone'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Construir consulta
$query = "SELECT p.id_pedido, p.fecha, p.total, p.estado, p.nombre_cliente, p.telefono_cliente, p.direccion_cliente, p.metodo_pago, p.notas
          FROM pedido p";

$conditions = [];
$params = [];
$types = '';

if ($status) {
    $conditions[] = "p.estado = ?";
    $params[] = $status;
    $types .= 's';
}

if ($phone) {
    $conditions[] = "p.telefono_cliente LIKE ?";
    $params[] = '%' . $phone . '%';
    $types .= 's';
}

if ($date_from) {
    $conditions[] = "DATE(p.fecha) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $conditions[] = "DATE(p.fecha) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if ($conditions) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}

$query .= " ORDER BY p.fecha DESC LIMIT 100"; // Limitar a 100 pedidos recientes

$stmt = $conexion->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$pedidos = [];
while ($row = $result->fetch_assoc()) {
    $pedidos[] = $row;
}

$stmt->close();

echo json_encode($pedidos);
?>