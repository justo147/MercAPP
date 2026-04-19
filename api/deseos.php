<?php
/**
 * API de Deseos (wishlist de intercambio).
 * GET  → lista los deseos del usuario en sesión
 * POST accion=add    → añade un deseo
 * POST accion=delete → elimina un deseo
 */
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$uid  = intval($_SESSION['user_id']);
$db   = new Database();
$conn = $db->getConnection();

/* ── GET: listar ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare(
        "SELECT d.id, d.etiquetas, d.categoria_id, d.estado_producto_id,
                c.nombre AS categoria_nombre,
                ep.nombre AS estado_producto_nombre
         FROM Deseos d
         LEFT JOIN Categorias c         ON c.id = d.categoria_id
         LEFT JOIN EstadoProducto ep    ON ep.id = d.estado_producto_id
         WHERE d.usuario_id = :uid
         ORDER BY d.id DESC"
    );
    $stmt->execute([':uid' => $uid]);
    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

/* ── POST ────────────────────────────────────────────────────── */
$accion = trim($_POST['accion'] ?? '');

if ($accion === 'add') {
    $etiquetas        = trim($_POST['etiquetas']        ?? '');
    $categoriaId      = intval($_POST['categoria_id']   ?? 0) ?: null;
    $estadoProductoId = intval($_POST['estado_producto_id'] ?? 0) ?: null;

    if (empty($etiquetas)) {
        http_response_code(400);
        echo json_encode(['error' => 'Las etiquetas son obligatorias']);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO Deseos (usuario_id, categoria_id, estado_producto_id, etiquetas)
         VALUES (:uid, :cat, :ep, :etiq)"
    );
    $ok = $stmt->execute([
        ':uid'  => $uid,
        ':cat'  => $categoriaId,
        ':ep'   => $estadoProductoId,
        ':etiq' => $etiquetas,
    ]);
    echo json_encode(['ok' => $ok, 'id' => $conn->lastInsertId()]);
    exit;
}

if ($accion === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requerido']);
        exit;
    }
    // Solo puede borrar el propio usuario
    $stmt = $conn->prepare("DELETE FROM Deseos WHERE id = :id AND usuario_id = :uid");
    $ok   = $stmt->execute([':id' => $id, ':uid' => $uid]);
    echo json_encode(['ok' => $ok]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Acción no reconocida']);
