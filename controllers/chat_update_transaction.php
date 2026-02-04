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
$estadosValidos = ['pendiente','aceptada','enviado','entregado','cancelada'];
if (!in_array($nuevoEstado, $estadosValidos)) {
    exit("Estado no válido");
}

// Actualizar estado de la transacción
$transactionModel->updateStatus($transaccionId, $nuevoEstado);

// Cambiar estado de publicación del producto
$productoId = intval($transaccion["producto_id"]);

switch ($nuevoEstado) {

    case "aceptada":
        // No cambia estado de publicación
        break;

    case "enviado":
        // Solo el vendedor puede marcar como enviado
        if ($usuarioActual != $transaccion["vendedor_id"]) {
            exit("No autorizado");
        }

        $transactionModel->updateStatus($transaccionId, "enviado");

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

        $transactionModel->updateStatus($transaccionId, "entregado");

        // Producto vendido
        $productModel->cambiarEstadoPublicacion($productoId, "vendido");

        $messageModel->enviarMensajeSistema(
            $chatId,
            "El comprador ha marcado el producto como entregado. La transacción ha finalizado."
        );
        break;

    case "cancelada":
        // Ambos pueden cancelar
        $transactionModel->updateStatus($transaccionId, "cancelada");

        // Reactivar publicación
        $productModel->cambiarEstadoPublicacion($productoId, "activo");

        $messageModel->enviarMensajeSistema(
            $chatId,
            "La transacción ha sido cancelada."
        );
        break;
}


// Mensaje automático
$messageModel->enviarMensajeSistema(
    $chatId,
    "La transacción ha cambiado a estado: $nuevoEstado"
);

// Volver al chat
header("Location: /MercApp/public/views/chat.php?id=" . $chatId);
exit;
