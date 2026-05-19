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
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../config/mail_config.php';
require_once __DIR__ . '/../config/mail_templates.php';
require_once __DIR__ . '/../vendor/autoload.php';

$db   = new Database();
$conn = $db->getConnection();

$transactionModel = new Transaction($conn);
$messageModel     = new Message($conn);
$chatModel        = new Chat($conn);
$productModel     = new Product($conn);
$notifModel       = new Notification($conn);

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

// Solo los participantes de la transacción pueden actuar
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

// Estados terminales: no se puede avanzar
if (in_array($estadoActual, ['entregado', 'cancelada'])) {
    header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
    exit;
}

// ── Máquina de estados ────────────────────────────────────────
// Mapa: estado_actual → [estados_destino_válidos]
$transicionesValidas = [
    'pendiente'             => ['aceptada', 'propuesta_intercambio', 'cancelada'],
    'propuesta_intercambio' => ['aceptada', 'pendiente',             'cancelada'],
    'aceptada'              => ['pago_pendiente',                    'cancelada'],
    'pago_pendiente'        => ['enviado',                           'cancelada'],
    'enviado'               => ['entregado',                         'cancelada'],
];

if (
    !isset($transicionesValidas[$estadoActual]) ||
    !in_array($nuevoEstado, $transicionesValidas[$estadoActual])
) {
    header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
    exit;
}

// ── Autorización por rol ──────────────────────────────────────
switch ($nuevoEstado) {
    case 'propuesta_intercambio':
        // Solo el comprador puede proponer
        if (!$esComprador) {
            header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
            exit;
        }
        break;

    case 'aceptada':
        // Desde pendiente: solo el comprador acepta (venta)
        // Desde propuesta_intercambio: solo el vendedor acepta la propuesta
        if ($estadoActual === 'pendiente' && !$esComprador) {
            header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
            exit;
        }
        if ($estadoActual === 'propuesta_intercambio' && !$esVendedor) {
            header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
            exit;
        }
        break;

    case 'pendiente':
        // Desde propuesta_intercambio: solo el vendedor puede rechazar
        if ($estadoActual === 'propuesta_intercambio' && !$esVendedor) {
            header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
            exit;
        }
        break;

    case 'pago_pendiente':
        // Solo el comprador informa que ha pagado
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
        // Cualquiera puede cancelar
        break;
}

// ── Ejecutar la transición ────────────────────────────────────
$productoId = intval($transaccion["producto_id"]);

switch ($nuevoEstado) {

    // ── Comprador propone un intercambio ──────────────────────
    case 'propuesta_intercambio':
        $productoOfrecidoId = intval($_POST["producto_ofrecido_id"] ?? 0);
        $dineroExtra        = floatval($_POST["dinero_extra"] ?? 0);

        if ($productoOfrecidoId <= 0) {
            header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=producto_ofrecido");
            exit;
        }

        $transactionModel->proponerIntercambio($transaccionId, $productoOfrecidoId, $dineroExtra);

        $msgPropuesta = "El comprador ha propuesto un intercambio.";
        if ($dineroExtra > 0) {
            $msgPropuesta .= " Dinero extra ofrecido: " . number_format($dineroExtra, 2) . " €.";
        }
        $messageModel->enviarMensajeSistema($chatId, $msgPropuesta);
        break;

    // ── Vendedor rechaza la propuesta → vuelve a pendiente ────
    case 'pendiente':
        $transactionModel->rechazarPropuestaIntercambio($transaccionId);
        $messageModel->enviarMensajeSistema(
            $chatId,
            "El vendedor ha rechazado la propuesta de intercambio. El comprador puede hacer una nueva propuesta."
        );
        break;

    // ── aceptada: desde pendiente (comprador acepta venta)
    //             o desde propuesta_intercambio (vendedor acepta intercambio)
    case 'aceptada':
        if ($estadoActual === 'propuesta_intercambio') {
            // Vendedor acepta la propuesta de intercambio
            $transactionModel->aceptarPropuestaIntercambio($transaccionId);
            $messageModel->enviarMensajeSistema(
                $chatId,
                "El vendedor ha aceptado la propuesta de intercambio. El comprador debe confirmar el método de pago."
            );
            break;
        }

        // Comprador acepta una venta (estado pendiente → aceptada)
        $metodoPago          = trim($_POST["metodo_pago"]              ?? "");
        $direccionEnvio      = trim($_POST["direccion_envio"]          ?? "");
        $notas               = trim($_POST["notas_comprador"]          ?? "");
        $stripePaymentIntent = trim($_POST["stripe_payment_intent_id"] ?? "");

        $metodosValidos = ['efectivo', 'transferencia', 'bizum', 'paypal', 'stripe', 'otro'];
        if (!in_array($metodoPago, $metodosValidos)) {
            header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=metodo_pago");
            exit;
        }

        // ── Stripe ───────────────────────────────────────────────────────────
        if ($metodoPago === 'stripe') {
            if (!$stripePaymentIntent) {
                header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=stripe_no_intent");
                exit;
            }
            $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
            if (!$stripeSecretKey) {
                header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=stripe_config");
                exit;
            }
            try {
                \Stripe\Stripe::setApiKey($stripeSecretKey);
                $intent = \Stripe\PaymentIntent::retrieve($stripePaymentIntent);
                $intentTransaccionId = intval($intent->metadata->transaccion_id ?? 0);
                if ($intent->status !== 'succeeded' || $intentTransaccionId !== $transaccionId) {
                    header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=stripe_pago_fallido");
                    exit;
                }
            } catch (\Stripe\Exception\ApiErrorException $e) {
                header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=stripe_error");
                exit;
            }
            $transactionModel->aceptarConStripe($transaccionId, $direccionEnvio, $notas, $stripePaymentIntent);
            $msgStripe = "El comprador ha pagado con tarjeta (Stripe). El pago ha sido confirmado automáticamente.";
            if ($direccionEnvio) $msgStripe .= " Dirección de envío: {$direccionEnvio}.";
            $messageModel->enviarMensajeSistema($chatId, $msgStripe);
            $nuevoEstado = 'pago_pendiente';
            break;
        }

        // Otros métodos de pago
        $transactionModel->aceptar($transaccionId, $metodoPago, $direccionEnvio, $notas);
        $etiquetas = [
            'efectivo'      => 'Efectivo al entregar',
            'transferencia' => 'Transferencia bancaria',
            'bizum'         => 'Bizum',
            'paypal'        => 'PayPal',
            'otro'          => 'Otro método',
        ];
        $msgAceptada = "El comprador ha aceptado la transacción.";
        if (isset($etiquetas[$metodoPago])) $msgAceptada .= " Método de pago: {$etiquetas[$metodoPago]}.";
        if ($direccionEnvio) $msgAceptada .= " Dirección de envío: {$direccionEnvio}.";
        $messageModel->enviarMensajeSistema($chatId, $msgAceptada);
        break;

    // ── Comprador confirma pago (general o con datos de intercambio) ──────
    case 'pago_pendiente':
        $metodoPago     = trim($_POST["metodo_pago"]              ?? "");
        $direccionEnvio = trim($_POST["direccion_envio"]          ?? "");
        $notas          = trim($_POST["notas_comprador"]          ?? "");
        $stripePaymentIntent = trim($_POST["stripe_payment_intent_id"] ?? "");

        // Intercambio: el método de pago aún no estaba guardado, se recoge aquí
        if ($metodoPago && !$transaccion['metodo_pago']) {
            $metodosValidos = ['efectivo', 'transferencia', 'bizum', 'paypal', 'stripe', 'otro'];
            if (!in_array($metodoPago, $metodosValidos)) {
                header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=metodo_pago");
                exit;
            }

            if ($metodoPago === 'stripe') {
                if (!$stripePaymentIntent) {
                    header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=stripe_no_intent");
                    exit;
                }
                $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
                if (!$stripeSecretKey) {
                    header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=stripe_config");
                    exit;
                }
                try {
                    \Stripe\Stripe::setApiKey($stripeSecretKey);
                    $intent = \Stripe\PaymentIntent::retrieve($stripePaymentIntent);
                    $intentTransaccionId = intval($intent->metadata->transaccion_id ?? 0);
                    if ($intent->status !== 'succeeded' || $intentTransaccionId !== $transaccionId) {
                        header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=stripe_pago_fallido");
                        exit;
                    }
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=stripe_error");
                    exit;
                }
                $transactionModel->confirmarPagoConStripe($transaccionId, $direccionEnvio, $notas, $stripePaymentIntent);
                $msgPago = "El comprador ha pagado con tarjeta (Stripe). El pago ha sido confirmado automáticamente.";
                if ($direccionEnvio) $msgPago .= " Dirección de envío: {$direccionEnvio}.";
                $messageModel->enviarMensajeSistema($chatId, $msgPago);
                break;
            }

            $transactionModel->confirmarPagoIntercambio($transaccionId, $metodoPago, $direccionEnvio, $notas);
            $etiquetas = [
                'efectivo'      => 'Efectivo al entregar',
                'transferencia' => 'Transferencia bancaria',
                'bizum'         => 'Bizum',
                'paypal'        => 'PayPal',
                'otro'          => 'Otro método',
            ];
            $msgPago = "El comprador confirma el pago del intercambio.";
            if (isset($etiquetas[$metodoPago])) $msgPago .= " Método de pago: {$etiquetas[$metodoPago]}.";
            if ($direccionEnvio) $msgPago .= " Dirección de envío: {$direccionEnvio}.";
            $messageModel->enviarMensajeSistema($chatId, $msgPago);
        } else {
            // Venta normal: solo confirma que pagó
            $transactionModel->marcarPagado($transaccionId);
            $messageModel->enviarMensajeSistema(
                $chatId,
                "El comprador indica que ha realizado el pago. El vendedor debe confirmarlo antes de enviar."
            );
        }
        break;

    case 'enviado':
        $numeroSeguimiento = trim($_POST["numero_seguimiento"] ?? "");
        $transactionModel->marcarEnviado($transaccionId, $numeroSeguimiento);

        $msgEnvio = "El vendedor ha enviado el producto.";
        if ($numeroSeguimiento) {
            $msgEnvio .= " Número de seguimiento: {$numeroSeguimiento}.";
        }
        $messageModel->enviarMensajeSistema($chatId, $msgEnvio);
        break;

    case 'entregado':
        $transactionModel->marcarEntregado($transaccionId);
        $productModel->cambiarEstadoPublicacion($productoId, "vendido");
        $messageModel->enviarMensajeSistema(
            $chatId,
            "¡El comprador ha confirmado la entrega! Transacción completada con éxito."
        );

        // Enviar email de confirmación a comprador y vendedor
        $stmtUsers = $conn->prepare(
            "SELECT u.email, u.nombre, u.id FROM Usuario u WHERE u.id IN (:cid, :vid)"
        );
        $stmtUsers->execute([':cid' => $transaccion['comprador_id'], ':vid' => $transaccion['vendedor_id']]);
        $usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        $stmtProd = $conn->prepare("SELECT titulo, precio FROM Productos WHERE id = :pid");
        $stmtProd->execute([':pid' => $productoId]);
        $prodRow        = $stmtProd->fetch(PDO::FETCH_ASSOC);
        $productoTitulo = $prodRow['titulo'] ?? 'producto';
        $precioProd     = $prodRow['precio'] ? number_format((float)$prodRow['precio'], 2, ',', '.') . ' €' : 'Trueque';
        $fechaTrans     = date('d/m/Y');

        foreach ($usuarios as $u) {
            $esCompradorEmail = ($u['id'] == $transaccion['comprador_id']);
            $rol = $esCompradorEmail ? 'comprador' : 'vendedor';

            $html = mailTransaccionCompletada($u['nombre'], $rol, $productoTitulo, $precioProd, $fechaTrans);

            sendMail($u['email'], $u['nombre'], "Transacción completada — {$productoTitulo}", $html);
        }
        break;

    case 'cancelada':
        $transactionModel->cancelar($transaccionId);
        $productModel->cambiarEstadoPublicacion($productoId, "activo");
        $messageModel->enviarMensajeSistema(
            $chatId,
            "La transacción ha sido cancelada. El producto vuelve a estar disponible."
        );
        break;
}

// ── Notificar al otro participante sobre el cambio de estado ──
$mensajesEstado = [
    'propuesta_intercambio' => 'El comprador ha propuesto un intercambio.',
    'pendiente'      => 'El vendedor ha rechazado la propuesta. El comprador puede hacer una nueva.',
    'aceptada'       => 'La transacción ha avanzado al siguiente paso.',
    'pago_pendiente' => 'El comprador indica que ha realizado el pago.',
    'enviado'        => 'El vendedor ha marcado el pedido como enviado.',
    'entregado'      => '¡La entrega ha sido confirmada! Transacción completada.',
    'cancelada'      => 'La transacción ha sido cancelada.',
];
if (isset($mensajesEstado[$nuevoEstado])) {
    // Notificar al que NO ejecutó la acción
    $destinatarioNotif = $esComprador ? intval($transaccion["vendedor_id"]) : intval($transaccion["comprador_id"]);
    $chatDataNotif     = $chatModel->getById($chatId);
    $tituloProducto    = $chatDataNotif["producto_titulo"] ?? "un producto";
    $notifModel->create(
        $destinatarioNotif,
        'mensaje',
        $mensajesEstado[$nuevoEstado] . " ({$tituloProducto})",
        "{$BASE}/public/views/chat.php?id={$chatId}"
    );
}

header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
exit;
