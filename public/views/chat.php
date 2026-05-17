<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Chat no válido');
}

$chatId        = intval($_GET['id']);
$usuarioActual = intval($_SESSION['user_id']);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Chat.php';
require_once __DIR__ . '/../../models/Message.php';
require_once __DIR__ . '/../../models/Transaction.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Rating.php';
require_once __DIR__ . '/../../models/Report.php';
require_once __DIR__ . '/../../models/Notification.php';

$db               = new Database();
$conn             = $db->getConnection();
$chatModel        = new Chat($conn);
$mensajeModel     = new Message($conn);
$transactionModel = new Transaction($conn);
$productoModel    = new Product($conn);
$ratingModel      = new Rating($conn);
$reportModel      = new Report($conn);
$notifModel       = new Notification($conn);

if (!$chatModel->userBelongsToChat($chatId, $usuarioActual)) {
    die('No tienes acceso a este chat.');
}

$chat        = $chatModel->getById($chatId);
$transaccion = null;
if (!empty($chat['transaccion_id'])) {
    $transaccion = $transactionModel->getById($chat['transaccion_id']);
}

$esVendedor  = ($usuarioActual == $chat['usuario_vendedor']);
$esComprador = ($usuarioActual == $chat['usuario_comprador']);

$mostrarModalValoracion = false;
if (
    $esComprador &&
    $transaccion &&
    $transaccion['estado'] === 'entregado' &&
    !$ratingModel->hasRated($transaccion['id'], $usuarioActual)
) {
    $mostrarModalValoracion = true;
}

$otroUsuarioId = $esVendedor ? $chat['usuario_comprador'] : $chat['usuario_vendedor'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mensajeModel->markAsRead($chatId, $usuarioActual);
    $mensajeModel->markSystemAsRead($chatId);
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensaje'])) {
    $contenido = trim($_POST['mensaje'] ?? '');
    if ($contenido !== '') {
        $mensajeModel->send($chatId, $usuarioActual, $contenido);
        $chatData       = $chatModel->getById($chatId);
        $destinatarioId = ($chatData['usuario_comprador'] == $usuarioActual)
            ? intval($chatData['usuario_vendedor'])
            : intval($chatData['usuario_comprador']);
        $nombreRemit = htmlspecialchars($_SESSION['name'] ?? 'Alguien');
        $notifModel->create(
            $destinatarioId,
            'mensaje',
            "{$nombreRemit} te ha enviado un mensaje en el chat sobre \"{$chatData['producto_titulo']}\"."
        );
    }
    header('Location: chat.php?id=' . $chatId);
    exit;
}

// Rating (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valoracion'])) {
    header('Content-Type: application/json');
    if (!$esComprador) {
        echo json_encode(['ok' => false, 'error' => 'Solo el comprador puede valorar.']);
        exit;
    }
    $transaccionId = intval($_POST['transaccion_id'] ?? 0);
    $fiabilidad    = intval($_POST['fiabilidad']     ?? 0);
    $comunicacion  = intval($_POST['comunicacion']   ?? 0);
    $puntualidad   = intval($_POST['puntualidad']    ?? 0);
    $comentario    = trim($_POST['comentario']       ?? '');
    $transaccionVal = $transactionModel->getById($transaccionId);
    if (
        $transaccionVal &&
        $transaccionVal['estado'] === 'entregado' &&
        $usuarioActual == $transaccionVal['comprador_id'] &&
        !$ratingModel->hasRated($transaccionId, $usuarioActual) &&
        $fiabilidad   >= 1 && $fiabilidad   <= 5 &&
        $comunicacion >= 1 && $comunicacion <= 5 &&
        $puntualidad  >= 1 && $puntualidad  <= 5
    ) {
        $puntuacion = round(($fiabilidad + $comunicacion + $puntualidad) / 3);
        $ok = $ratingModel->create(
            $transaccionId,
            $usuarioActual,
            $transaccionVal['vendedor_id'],
            $puntuacion,
            $comentario ?: null,
            $fiabilidad,
            $comunicacion,
            $puntualidad
        );
        echo json_encode(['ok' => $ok]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la valoración.']);
    }
    exit;
}

// Report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reporte_motivo'])) {
    $motivo     = trim($_POST['reporte_motivo'] ?? '');
    $productoId = intval($chat['producto_id'] ?? 0);
    if ($motivo !== '' && $productoId > 0) {
        $reportModel->create($usuarioActual, $productoId, $motivo);
    }
    header('Location: chat.php?id=' . $chatId . '&reporte=ok');
    exit;
}

$mensajes = $mensajeModel->getByChat($chatId);

// Pre-process messages for Twig
foreach ($mensajes as &$msg) {
    if ($msg['usuario_id'] === null) {
        $msg['contenido_clean'] = preg_replace('/^\[SISTEMA\]\s*/i', '', $msg['contenido'] ?? '');
    }
    $msg['contenido_html'] = nl2br(htmlspecialchars($msg['contenido'] ?? ''));
}
unset($msg);

$pasos     = ['pendiente', 'aceptada', 'pago_pendiente', 'enviado', 'entregado'];
$stepIndex = array_flip($pasos);

$esIntercambio      = $transaccion && in_array($transaccion['tipo'] ?? '', ['intercambio', 'mixto']);
$intercambioDetalle = $esIntercambio ? $transactionModel->getIntercambioDetalle($transaccion['id']) : [];

$productoParaStripe = $productoModel->getById($chat['producto_id'] ?? 0);
$precioProducto     = floatval($productoParaStripe['precio'] ?? 0);
$stripeDisponible   = $precioProducto >= 0.50;

$etiquetaMetodo = [
    'efectivo'      => ['bi-cash-coin',           'Efectivo al entregar'],
    'transferencia' => ['bi-bank',                'Transferencia bancaria'],
    'bizum'         => ['bi-phone',               'Bizum'],
    'paypal'        => ['bi-paypal',              'PayPal'],
    'stripe'        => ['bi-credit-card-2-front', 'Tarjeta (Stripe) — Pagado'],
    'otro'          => ['bi-three-dots',          'Otro método'],
];

// Toast messages from URL params
$chatToasts = [];
if (isset($_GET['reporte']) && $_GET['reporte'] === 'ok') {
    $chatToasts[] = ['success', 'Reporte enviado. Lo revisaremos próximamente.'];
}
$stripeErrors = [
    'stripe_no_intent'    => 'No se recibió confirmación del pago. Inténtalo de nuevo.',
    'stripe_config'       => 'El sistema de pagos con tarjeta no está configurado correctamente.',
    'stripe_pago_fallido' => 'El pago con tarjeta no fue completado o no es válido.',
    'stripe_error'        => 'Error de comunicación con Stripe. Inténtalo de nuevo.',
    'metodo_pago'         => 'Debes seleccionar un método de pago.',
];
$errCode = $_GET['error'] ?? '';
if (isset($stripeErrors[$errCode])) {
    $chatToasts[] = ['error', $stripeErrors[$errCode]];
}
if (isset($_GET['stripe']) && $_GET['stripe'] === 'ok') {
    $chatToasts[] = ['success', '¡Pago con tarjeta confirmado! El vendedor recibirá el aviso para preparar el envío.'];
}

require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('chat.html.twig', [
    'chatId'                 => $chatId,
    'chat'                   => $chat,
    'transaccion'            => $transaccion,
    'mensajes'               => $mensajes,
    'esVendedor'             => $esVendedor,
    'esComprador'            => $esComprador,
    'usuarioActual'          => $usuarioActual,
    'otroUsuarioId'          => $otroUsuarioId,
    'mostrarModalValoracion' => $mostrarModalValoracion,
    'esIntercambio'          => $esIntercambio,
    'intercambioDetalle'     => $intercambioDetalle,
    'etiquetaMetodo'         => $etiquetaMetodo,
    'stepIndex'              => $stepIndex,
    'stripeKey'              => $_ENV['STRIPE_KEY'] ?? '',
    'stripeDisponible'       => $stripeDisponible,
    'precioProducto'         => $precioProducto,
    'chatToasts'             => $chatToasts,
]);
