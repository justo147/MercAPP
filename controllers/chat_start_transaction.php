<?php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: {$BASE}/public/views/auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: {$BASE}/public/views/home.php");
    exit;
}

$usuarioActual = intval($_SESSION["user_id"]);
$chatId        = intval($_POST["chat_id"] ?? 0);

if ($chatId <= 0) {
    header("Location: {$BASE}/public/views/home.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Transaction.php';

$db   = new Database();
$conn = $db->getConnection();

$chatModel        = new Chat($conn);
$productoModel    = new Product($conn);
$mensajeModel     = new Message($conn);
$transactionModel = new Transaction($conn);

$chat = $chatModel->getById($chatId);

if (!$chat) {
    header("Location: {$BASE}/public/views/home.php");
    exit;
}

// Solo el vendedor puede iniciar la transacción
if (intval($chat["usuario_vendedor"]) !== $usuarioActual) {
    header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
    exit;
}

// Evitar crear una segunda transacción si ya existe
if (!empty($chat["transaccion_id"])) {
    header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
    exit;
}

$productoId  = intval($chat["producto_id"]);
$compradorId = intval($chat["usuario_comprador"]);

// Crear transacción y vincularla al chat
$transactionId = $transactionModel->createFromChat($productoId, $compradorId, $usuarioActual);
$chatModel->setTransaction($chatId, $transactionId);

// Pausar el producto mientras se negocia
$productoModel->reservarProducto($productoId);

// Notificación interna
$mensajeModel->enviarMensajeSistema(
    $chatId,
    "El vendedor ha iniciado una transacción. El producto ha sido pausado mientras se negocia."
);

header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
exit;
