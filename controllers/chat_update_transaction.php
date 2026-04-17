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

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Chat.php';
require_once __DIR__ . '/../models/Product.php';

$db   = new Database();
$conn = $db->getConnection();

$transactionModel = new Transaction($conn);
$messageModel     = new Message($conn);
$chatModel        = new Chat($conn);
$productModel     = new Product($conn);

$usuarioActual = intval($_SESSION["user_id"]);
$transaccionId = intval($_POST["transaccion_id"] ?? 0);
$nuevoEstado   = trim($_POST["estado"] ?? "");
$chatId        = intval($_POST["chat_id"] ?? 0);

if (!$transaccionId || !$nuevoEstado || !$chatId) {
    header("Location: {$BASE}/public/views/home.php");
    exit;
}

$transaccion = $transactionModel->getById($transaccionId);

if (!$transaccion) {
    header("Location: {$BASE}/public/views/home.php");
    exit;
}

// Verificar que el usuario pertenece a la transacción
if (
    $usuarioActual != $transaccion["comprador_id"] &&
    $usuarioActual != $transaccion["vendedor_id"]
) {
    header("Location: {$BASE}/public/views/home.php");
    exit;
}

$estadoActual = $transaccion["estado"];
$esComprador  = ($usuarioActual == $transaccion["comprador_id"]);
$esVendedor   = ($usuarioActual == $transaccion["vendedor_id"]);

// ── Máquina de estados ───────────────────────────────────────
// Transiciones válidas y quién puede ejecutarlas
$transicionesValidas = [
    'pendiente' => ['aceptada', 'cancelada'],
    'aceptada'  => ['enviado',  'cancelada'],
    'enviado'   => ['entregado','cancelada'],
];

// Estados terminales: no se puede avanzar
if (in_array($estadoActual, ['entregado', 'cancelada'])) {
    header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
    exit;
}

// Verificar que la transición es válida desde el estado actual
if (
    !isset($transicionesValidas[$estadoActual]) ||
    !in_array($nuevoEstado, $transicionesValidas[$estadoActual])
) {
    header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
    exit;
}

// ── Autorización por rol para cada transición ─────────────────
switch ($nuevoEstado) {
    case 'aceptada':
        // Solo el comprador acepta la transacción
        if (!$esComprador) {
            header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
            exit;
        }
        break;

    case 'enviado':
        // Solo el vendedor marca como enviado
        if (!$esVendedor) {
            header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
            exit;
        }
        break;

    case 'entregado':
        // Solo el comprador confirma la entrega
        if (!$esComprador) {
            header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
            exit;
        }
        break;

    case 'cancelada':
        // Cualquiera de los dos puede cancelar
        break;
}

// ── Ejecutar el cambio ────────────────────────────────────────
$transactionModel->updateStatus($transaccionId, $nuevoEstado);
$productoId = intval($transaccion["producto_id"]);

switch ($nuevoEstado) {

    case 'aceptada':
        $messageModel->enviarMensajeSistema(
            $chatId,
            "El comprador ha aceptado la transacción."
        );
        break;

    case 'enviado':
        $messageModel->enviarMensajeSistema(
            $chatId,
            "El vendedor ha marcado el producto como enviado."
        );
        break;

    case 'entregado':
        $productModel->cambiarEstadoPublicacion($productoId, "vendido");
        $messageModel->enviarMensajeSistema(
            $chatId,
            "El comprador ha confirmado la entrega. ¡Transacción completada!"
        );
        break;

    case 'cancelada':
        $productModel->cambiarEstadoPublicacion($productoId, "activo");
        $messageModel->enviarMensajeSistema(
            $chatId,
            "La transacción ha sido cancelada. El producto vuelve a estar disponible."
        );
        break;
}

header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
exit;
