<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

$chatId  = intval($_GET['chat_id']  ?? 0);
$sinceId = intval($_GET['since_id'] ?? 0);
$userId  = intval($_SESSION['user_id']);

if (!$chatId) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/../models/Message.php';

$db        = new Database();
$conn      = $db->getConnection();
$chatModel = new Chat($conn);
$msgModel  = new Message($conn);

if (!$chatModel->userBelongsToChat($chatId, $userId)) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = $conn->prepare("
    SELECT m.id, m.usuario_id, m.contenido, m.leido,
           m.fecha_envio,
           u.nombre AS sender_name
    FROM Mensajes m
    LEFT JOIN usuario u ON m.usuario_id = u.id
    WHERE m.chat_id = :chat AND m.id > :since
    ORDER BY m.fecha_envio ASC
");
$stmt->execute([':chat' => $chatId, ':since' => $sinceId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msgModel->markAsRead($chatId, $userId);
$msgModel->markSystemAsRead($chatId);

$messages = [];
foreach ($rows as $row) {
    if ($row['usuario_id'] === null) {
        $messages[] = [
            'id'              => (int)$row['id'],
            'tipo'            => 'sistema',
            'contenido_clean' => preg_replace('/^\[SISTEMA\]\s*/i', '', $row['contenido'] ?? ''),
        ];
    } else {
        $messages[] = [
            'id'            => (int)$row['id'],
            'tipo'          => 'normal',
            'es_mio'        => ($row['usuario_id'] == $userId),
            'sender_name'   => $row['sender_name'] ?? '',
            'fecha_envio'   => $row['fecha_envio'],
            'contenido_html'=> nl2br(htmlspecialchars($row['contenido'] ?? '')),
            'leido'         => (bool)$row['leido'],
        ];
    }
}

header('Content-Type: application/json');
echo json_encode(['ok' => true, 'messages' => $messages]);
