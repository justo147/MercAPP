<?php
// Stripe webhook handler — recibe eventos asíncronos de Stripe
// Endpoint público: no lleva session_start ni autenticación propia

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Chat.php';

$payload   = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secret    = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

$stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
if (!$stripeSecretKey) {
    http_response_code(500);
    exit;
}

\Stripe\Stripe::setApiKey($stripeSecretKey);

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit;
}

if ($event->type === 'payment_intent.succeeded') {
    $intent        = $event->data->object;
    $transaccionId = intval($intent->metadata->transaccion_id ?? 0);

    if ($transaccionId > 0) {
        $db   = new Database();
        $conn = $db->getConnection();

        $transactionModel = new Transaction($conn);
        $transaccion      = $transactionModel->getById($transaccionId);

        // Solo procesar si aún está en aceptada (no procesado ya por el formulario)
        if ($transaccion && $transaccion['estado'] === 'aceptada') {
            $transactionModel->marcarPagado($transaccionId);

            // Buscar el chat asociado para dejar mensaje sistema
            $chatModel = new Chat($conn);
            $stmt = $conn->prepare(
                "SELECT id FROM Chat WHERE transaccion_id = :tid LIMIT 1"
            );
            $stmt->execute([':tid' => $transaccionId]);
            $chatId = $stmt->fetchColumn();

            if ($chatId) {
                $messageModel = new Message($conn);
                $messageModel->enviarMensajeSistema(
                    $chatId,
                    "Pago con tarjeta confirmado por Stripe (webhook). El vendedor puede preparar el envío."
                );
            }
        }
    }
}

http_response_code(200);
echo json_encode(['received' => true]);
