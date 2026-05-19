<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Product.php';

$db   = new Database();
$conn = $db->getConnection();

$transaccionId = intval($_POST['transaccion_id'] ?? 0);
$usuarioActual = intval($_SESSION['user_id']);

if (!$transaccionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Transacción no válida']);
    exit;
}

$transactionModel = new Transaction($conn);
$transaccion      = $transactionModel->getById($transaccionId);

if (!$transaccion) {
    http_response_code(404);
    echo json_encode(['error' => 'Transacción no encontrada']);
    exit;
}

// Solo el comprador puede iniciar el pago
if ($usuarioActual != $transaccion['comprador_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Se puede pagar en estado pendiente (venta) o aceptada (intercambio con dinero extra)
$estadosPermitidos = ['pendiente', 'aceptada'];
if (!in_array($transaccion['estado'], $estadosPermitidos)) {
    http_response_code(400);
    echo json_encode(['error' => 'Estado de transacción no válido para pago']);
    exit;
}

// Obtener precio del producto
$productModel = new Product($conn);
$producto     = $productModel->getById($transaccion['producto_id']);

if (!$producto) {
    http_response_code(404);
    echo json_encode(['error' => 'Producto no encontrado']);
    exit;
}

// Para intercambio en aceptada: usar dinero_extra; para venta: usar precio del producto
$precio = ($transaccion['tipo'] === 'intercambio' && $transaccion['estado'] === 'aceptada')
    ? floatval($transaccion['dinero_extra'] ?? 0)
    : floatval($producto['precio'] ?? 0);

if ($precio < 0.50) {
    http_response_code(400);
    echo json_encode(['error' => 'El importe mínimo para pago con tarjeta es 0,50 €']);
    exit;
}

// Crear PaymentIntent con Stripe
$stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
if (!$stripeSecretKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuración de pagos no disponible']);
    exit;
}

try {
    \Stripe\Stripe::setApiKey($stripeSecretKey);

    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount'   => intval(round($precio * 100)), // en céntimos
        'currency' => 'eur',
        'metadata' => [
            'transaccion_id' => $transaccionId,
            'comprador_id'   => $usuarioActual,
            'producto_id'    => $transaccion['producto_id'],
            'producto_titulo' => $producto['titulo'] ?? '',
        ],
        'description' => 'MercApp — ' . ($producto['titulo'] ?? "Producto #{$transaccion['producto_id']}"),
    ]);

    echo json_encode([
        'client_secret'    => $paymentIntent->client_secret,
        'payment_intent_id' => $paymentIntent->id,
        'amount'           => $precio,
    ]);
} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear el pago: ' . $e->getMessage()]);
}
