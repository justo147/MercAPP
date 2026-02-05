<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: /MercApp/public/views/auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /MercApp/public/views/home.php");
    exit;
}

$usuarioActual = intval($_SESSION["user_id"]);
$chatId = intval($_POST["chat_id"] ?? 0);

if ($chatId <= 0) {
    header("Location: /MercApp/public/views/home.php");
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

// Verificar si ya existe una transacción activa
$transaccionActiva = $transactionModel->getActiveTransactionByProduct($productoId);

if ($transaccionActiva) {
    header("Location: /MercApp/public/views/chat.php?id=" . $chatId);
    exit;
}

// Crear nueva transacción
$transactionId = $transactionModel->createFromChat(
    $productoId,
    $compradorId,
    $vendedorId
);

// Cambiar estado de publicación a 'pausado'
$productoModel->reservarProducto($productoId);

// Mensaje automático
$mensajeModel->enviarMensajeSistema(
    $chatId,
    "El vendedor ha iniciado una transacción. El producto ha sido pausado."
);

// Redirigir al chat
header("Location: /MercApp/public/views/chat.php?id=" . $chatId);
exit;
