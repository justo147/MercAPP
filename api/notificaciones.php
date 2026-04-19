<?php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Notification.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$db   = new Database();
$conn = $db->getConnection();
$nm   = new Notification($conn);
$uid  = intval($_SESSION['user_id']);

// POST: marcar todas como leídas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = trim($_POST['accion'] ?? '');
    if ($accion === 'mark_all') {
        $nm->markAllAsRead($uid);
        echo json_encode(['ok' => true]);
    } else {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) $nm->markAsRead($id);
        echo json_encode(['ok' => true]);
    }
    exit;
}

// GET: devolver no leídas (máx. 15)
$items = $nm->getUnread($uid);
$items = array_slice($items, 0, 15);

echo json_encode([
    'count' => count($items),
    'items' => $items,
]);
