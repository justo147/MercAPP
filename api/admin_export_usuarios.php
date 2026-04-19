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
    "SELECT u.id, u.nombre, u.email, u.fecha_registro, u.rol,
            ep.nombre AS estado
     FROM Usuario u
     LEFT JOIN EstadoUsuario ep ON ep.id = u.estado_id
     ORDER BY u.id ASC"
);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="usuarios_' . date('Ymd_His') . '.csv"');
header('Cache-Control: no-cache');

// BOM para compatibilidad con Excel
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Nombre', 'Email', 'Fecha registro', 'Rol', 'Estado'], ';');
foreach ($rows as $row) {
    fputcsv($out, [
        $row['id'],
        $row['nombre'],
        $row['email'],
        $row['fecha_registro'],
        $row['rol'],
        $row['estado'],
    ], ';');
}
fclose($out);
