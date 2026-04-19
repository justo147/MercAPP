<?php
/**
 * Devuelve los productos activos del usuario en sesión.
 * Usado en el formulario de intercambio para que el comprador
 * elija qué producto ofrece a cambio.
 */
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Product.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$uid = intval($_SESSION['user_id']);

$db   = new Database();
$conn = $db->getConnection();

// Solo productos propios en estado activo
$stmt = $conn->prepare("
    SELECT p.id, p.titulo, p.precio, img.url AS imagen
    FROM Productos p
    LEFT JOIN Imagenes_prod img ON img.id_producto = p.id AND img.orden = 1
    JOIN EstadoPublicacion ep  ON ep.id = p.estado_publicacion_id
    WHERE p.usuario_id = :uid AND ep.nombre = 'activo'
    ORDER BY p.fecha_publicacion DESC
    LIMIT 30
");
$stmt->execute([':uid' => $uid]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $productos]);
