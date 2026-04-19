<?php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Transaction.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$usuarioId = intval($_SESSION['user_id']);
$limit     = max(1, min(50, intval($_GET['limit'] ?? 10)));
$page      = max(1, intval($_GET['page']  ?? 1));
$offset    = ($page - 1) * $limit;
$rol       = in_array($_GET['rol'] ?? '', ['compra', 'venta']) ? $_GET['rol'] : '';
$estado    = trim($_GET['estado'] ?? '');

$estadosValidos = ['pendiente', 'aceptada', 'pago_pendiente', 'enviado', 'entregado', 'cancelada'];
if ($estado !== '' && !in_array($estado, $estadosValidos)) {
    $estado = '';
}

$db   = new Database();
$conn = $db->getConnection();
$tm   = new Transaction($conn);

$transacciones = $tm->getByUserDetailed($usuarioId, $limit, $offset, $rol, $estado);
$total         = $tm->countByUser($usuarioId, $rol, $estado);

echo json_encode([
    'data'   => $transacciones,
    'total'  => $total,
    'page'   => $page,
    'limit'  => $limit,
    'pages'  => (int) ceil($total / $limit),
]);
