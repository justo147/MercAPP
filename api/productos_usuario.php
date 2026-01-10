<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Validar que venga el ID por GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        "error" => "Falta el parámetro id o no es válido"
    ]);
    exit;
}

$usuarioId = intval($_GET['id']);

$database = new Database();
$pdo = $database->getConnection();

$sql = "
SELECT 
    p.id,
    p.titulo,
    p.descripcion,
    p.precio,
    p.tipo_transaccion,
    p.fecha_publicacion,
    p.ubicacion,
    c.nombre AS categoria,
    ep.nombre AS estado_producto,
    epu.nombre AS estado_publicacion
FROM Productos p
JOIN Categorias c ON p.categoria_id = c.id
JOIN EstadoProducto ep ON p.estado_producto_id = ep.id
JOIN EstadoPublicacion epu ON p.estado_publicacion_id = epu.id
WHERE p.usuario_id = ?
ORDER BY p.fecha_publicacion DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$usuarioId]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Añadir imágenes a cada producto
foreach ($productos as &$producto) {
    $sqlImg = "SELECT url, orden FROM Imagenes_prod WHERE id_producto = ? ORDER BY orden ASC";
    $stmtImg = $pdo->prepare($sqlImg);
    $stmtImg->execute([$producto['id']]);
    $producto['imagenes'] = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($productos);
