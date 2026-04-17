<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/bootstrap.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: {$BASE}/public/views/auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Chat.php';

$db = new Database();
$conn = $db->getConnection();

$chatModel = new Chat($conn);

$usuarioActual = intval($_SESSION["user_id"]);
$chats = $chatModel->getChatsByUser($usuarioActual);