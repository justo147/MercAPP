<?php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado');
}

$db   = new Database();
$conn = $db->getConnection();

$stmt = $conn->query(
    "SELECT t.id, p.titulo AS producto, uc.nombre AS comprador, uv.nombre AS vendedor,
            t.tipo, t.estado, t.precio_final, t.metodo_pago,
            t.direccion_envio, t.numero_seguimiento, t.fecha_transaccion,
            t.fecha_aceptacion, t.fecha_envio, t.fecha_entrega
     FROM Transacciones t
     JOIN Productos p    ON p.id = t.producto_id
     JOIN usuario uc     ON uc.id = t.comprador_id
     JOIN usuario uv     ON uv.id = t.vendedor_id
     ORDER BY t.id DESC"
);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="transacciones_' . date('Ymd_His') . '.csv"');
header('Cache-Control: no-cache');

echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, [
    'ID', 'Producto', 'Comprador', 'Vendedor', 'Tipo', 'Estado',
    'Precio final', 'Método pago', 'Dirección envío', 'Seguimiento',
    'Fecha inicio', 'Fecha aceptación', 'Fecha envío', 'Fecha entrega'
], ';');
foreach ($rows as $row) {
    fputcsv($out, [
        $row['id'], $row['producto'], $row['comprador'], $row['vendedor'],
        $row['tipo'], $row['estado'], $row['precio_final'], $row['metodo_pago'],
        $row['direccion_envio'], $row['numero_seguimiento'], $row['fecha_transaccion'],
        $row['fecha_aceptacion'], $row['fecha_envio'], $row['fecha_entrega'],
    ], ';');
}
fclose($out);
