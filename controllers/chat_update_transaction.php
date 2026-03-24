<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    exit("No autorizado");
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/../models/Product.php';

$db = new Database();
$conn = $db->getConnection();

$transactionModel = new Transaction($conn);
$messageModel = new Message($conn);
$chatModel = new Chat($conn);
$productModel = new Product($conn);

$usuarioActual = intval($_SESSION["user_id"]);
$transaccionId = intval($_POST["transaccion_id"] ?? 0);
$nuevoEstado = $_POST["estado"] ?? "";
$chatId = intval($_POST["chat_id"] ?? 0);

if (!$transaccionId || !$nuevoEstado || !$chatId) {
    exit("Datos incompletos");
}

$transaccion = $transactionModel->getById($transaccionId);

if (!$transaccion) {
    exit("Transacción no encontrada");
}

// Validar que el usuario pertenece a la transacción
if (
    $usuarioActual != $transaccion["comprador_id"] &&
    $usuarioActual != $transaccion["vendedor_id"]
) {
    exit("No autorizado");
}

// Validar estados permitidos
$estadosValidos = ['pendiente', 'aceptada', 'enviado', 'entregado', 'cancelada'];
if (!in_array($nuevoEstado, $estadosValidos)) {
    exit("Estado no válido");
}

// Actualizar estado de la transacción (una sola vez)
$transactionModel->updateStatus($transaccionId, $nuevoEstado);

$productoId = intval($transaccion["producto_id"]);

switch ($nuevoEstado) {

    case "aceptada":
        $messageModel->enviarMensajeSistema(
            $chatId,
            "El vendedor ha aceptado la transacción."
        );
        break;

    case "enviado":
        // Solo el vendedor puede marcar como enviado
        if ($usuarioActual != $transaccion["vendedor_id"]) {
            exit("No autorizado");
        }
        $messageModel->enviarMensajeSistema(
            $chatId,
            "El vendedor ha marcado el producto como enviado."
        );
        break;

    case "entregado":
        // Solo el comprador puede marcar como entregado
        if ($usuarioActual != $transaccion["comprador_id"]) {
            exit("No autorizado");
        }
        // Marcar producto como vendido
        $productModel->cambiarEstadoPublicacion($productoId, "vendido");
        $messageModel->enviarMensajeSistema(
            $chatId,
            "El comprador ha marcado el producto como entregado. La transacción ha finalizado."
        );
        break;

    case "cancelada":
        // Ambos pueden cancelar
        $productModel->cambiarEstadoPublicacion($productoId, "activo");
        $messageModel->enviarMensajeSistema(
            $chatId,
            "La transacción ha sido cancelada."
        );
        break;
}

// Volver al chat
header("Location: {$BASE}/public/views/chat.php?id=" . $chatId);
exit;