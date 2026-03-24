<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION["user_id"])) {
    header("Location: {$BASE}/public/views/auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: {$BASE}/public/views/home.php");
    exit;
}

$usuarioActual = intval($_SESSION["user_id"]);
$chatId = intval($_POST["chat_id"] ?? 0);

if ($chatId <= 0) {
    header("Location: {$BASE}/public/views/home.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Transaction.php';

$db = new Database();
$conn = $db->getConnection();

$chatModel = new Chat($conn);
$productoModel = new Product($conn);
$mensajeModel = new Message($conn);
$transactionModel = new Transaction($conn);

// Obtener datos del chat
$chat = $chatModel->getById($chatId);

if (!$chat) {
    exit("Chat no encontrado");
}

// Validar que el usuario actual es el vendedor
if (intval($chat["usuario_vendedor"]) !== $usuarioActual) {
    exit("No autorizado");
}

$productoId = intval($chat["producto_id"]);
$compradorId = intval($chat["usuario_comprador"]);
$vendedorId = $usuarioActual;

// Si el chat ya tiene una transacción, no crear otra
if (!empty($chat["transaccion_id"])) {
    header("Location: {$BASE}/public/views/chat.php?id=" . $chatId);
    exit;
}


// Crear nueva transacción
$transactionId = $transactionModel->createFromChat(
    $productoId,
    $compradorId,
    $vendedorId
);

// Vincular transacción al chat
$chatModel->setTransaction($chatId, $transactionId);

// Cambiar estado de publicación a 'pausado'
$productoModel->reservarProducto($productoId);

// Mensaje automático
$mensajeModel->enviarMensajeSistema(
    $chatId,
    "El vendedor ha iniciado una transacción. El producto ha sido pausado."
);

// Redirigir al chat
header("Location: {$BASE}/public/views/chat.php?id=" . $chatId);
exit;
