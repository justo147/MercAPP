<?php
/**
 * Marca como leídos todos los mensajes no leídos del usuario actual
 * en todos sus chats activos.
 */
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$userId = intval($_SESSION['user_id']);

$db   = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    UPDATE Mensajes m
    INNER JOIN Chats c ON m.chat_id = c.id
    SET m.leido = 1
    WHERE m.leido = 0
      AND m.usuario_id IS NOT NULL
      AND m.usuario_id <> :uid
      AND (c.usuario_vendedor = :uid2 OR c.usuario_comprador = :uid3)
");
$stmt->execute([':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId]);

echo json_encode(['ok' => true, 'actualizados' => $stmt->rowCount()]);
