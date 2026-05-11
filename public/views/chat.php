<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Chat no válido");
}

$chatId        = intval($_GET["id"]);
$usuarioActual = intval($_SESSION["user_id"]);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Chat.php';
require_once __DIR__ . '/../../models/Message.php';
require_once __DIR__ . '/../../models/Transaction.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Rating.php';
require_once __DIR__ . '/../../models/Report.php';
require_once __DIR__ . '/../../models/Notification.php';

$db = new Database();
$conn = $db->getConnection();

$chatModel        = new Chat($conn);
$mensajeModel     = new Message($conn);
$transactionModel = new Transaction($conn);
$productoModel    = new Product($conn);
$ratingModel      = new Rating($conn);
$reportModel      = new Report($conn);
$notifModel       = new Notification($conn);

if (!$chatModel->userBelongsToChat($chatId, $usuarioActual)) {
    die("No tienes acceso a este chat.");
}

$chat = $chatModel->getById($chatId);

$transaccion = null;
if (!empty($chat["transaccion_id"])) {
    $transaccion = $transactionModel->getById($chat["transaccion_id"]);
}

$esVendedor  = ($usuarioActual == $chat["usuario_vendedor"]);
$esComprador = ($usuarioActual == $chat["usuario_comprador"]);

$mostrarModalValoracion = false;
if (
    $esComprador &&
    $transaccion &&
    $transaccion['estado'] === 'entregado' &&
    !$ratingModel->hasRated($transaccion['id'], $usuarioActual)
) {
    $mostrarModalValoracion = true;
}

$otroUsuarioId = $esVendedor ? $chat["usuario_comprador"] : $chat["usuario_vendedor"];

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $mensajeModel->markAsRead($chatId, $usuarioActual);
    $mensajeModel->markSystemAsRead($chatId);
}

// ── Enviar mensaje ────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mensaje"])) {
    $contenido = trim($_POST["mensaje"] ?? "");
    if ($contenido !== "") {
        $mensajeModel->send($chatId, $usuarioActual, $contenido);

        // Notificar al otro participante del chat
        $chatData       = $chatModel->getById($chatId);
        $destinatarioId = ($chatData["usuario_comprador"] == $usuarioActual)
            ? intval($chatData["usuario_vendedor"])
            : intval($chatData["usuario_comprador"]);
        $nombreRemit    = htmlspecialchars($_SESSION["name"] ?? "Alguien");
        $notifModel->create(
            $destinatarioId,
            'mensaje',
            "{$nombreRemit} te ha enviado un mensaje en el chat sobre \"{$chatData['producto_titulo']}\"."
        );
    }
    header("Location: chat.php?id=" . $chatId);
    exit;
}

// ── Valoración (AJAX) ─────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["valoracion"])) {
    header("Content-Type: application/json");

    if (!$esComprador) {
        echo json_encode(['ok' => false, 'error' => 'Solo el comprador puede valorar.']);
        exit;
    }

    $transaccionId = intval($_POST["transaccion_id"] ?? 0);
    $fiabilidad    = intval($_POST["fiabilidad"]     ?? 0);
    $comunicacion  = intval($_POST["comunicacion"]   ?? 0);
    $puntualidad   = intval($_POST["puntualidad"]    ?? 0);
    $comentario    = trim($_POST["comentario"]       ?? '');

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

// ── Reporte ───────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reporte_motivo"])) {
    $motivo     = trim($_POST["reporte_motivo"] ?? "");
    $productoId = intval($chat["producto_id"] ?? 0);
    if ($motivo !== "" && $productoId > 0) {
        $reportModel->create($usuarioActual, $productoId, $motivo);
    }
    header("Location: chat.php?id=" . $chatId . "&reporte=ok");
    exit;
}

$mensajes = $mensajeModel->getByChat($chatId);

// ── Helpers visuales ─────────────────────────────────────────
$pasos = ['pendiente', 'aceptada', 'pago_pendiente', 'enviado', 'entregado'];

function stepIndex(string $estado): int {
    global $pasos;
    $idx = array_search($estado, $pasos);
    return $idx === false ? -1 : $idx;
}

function stepClass(string $current, string $step): string {
    if ($current === $step) return "text-primary fw-bold";
    if (stepIndex($current) > stepIndex($step)) return "text-success";
    return "text-muted";
}

function iconClass(string $current, string $step): string {
    if ($current === $step) return "text-primary";
    if (stepIndex($current) > stepIndex($step)) return "text-success";
    return "text-muted";
}

$etiquetaMetodo = [
    'efectivo'      => ['bi-cash-coin',          'Efectivo al entregar'],
    'transferencia' => ['bi-bank',               'Transferencia bancaria'],
    'bizum'         => ['bi-phone',              'Bizum'],
    'paypal'        => ['bi-paypal',             'PayPal'],
    'stripe'        => ['bi-credit-card-2-front','Tarjeta (Stripe) — Pagado'],
    'otro'          => ['bi-three-dots',         'Otro método'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat — <?= htmlspecialchars($chat["producto_titulo"] ?? "Producto") ?></title>

    <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/homeStyle.css">

    <script src="../js/theme.js" defer></script>
    <script src="../js/address_autocomplete.js" defer></script>
    <script src="https://js.stripe.com/v3/" defer></script>

    <style>
        .msg-sistema { text-align:center; margin:.75rem 0; }
        .msg-sistema .bubble {
            display:inline-block; background:#f0f0f0; color:#555;
            border-radius:20px; padding:.3rem .9rem;
            font-size:.8rem; font-style:italic;
        }
        .dark-mode .msg-sistema .bubble { background:#2a2a2a; color:#aaa; }
        .bubble-out { background:#0d6efd; color:#fff; border-radius:18px 18px 4px 18px; }
        .bubble-in  { background:#e9ecef; color:#212529; border-radius:18px 18px 18px 4px; }
        .dark-mode .bubble-in { background:#2d2d2d; color:#e0e0e0; }
        .chat-box   { max-height:420px; overflow-y:auto; scroll-behavior:smooth; }
        .step-bar .step { flex:1; text-align:center; }
        .step-bar .step-icon { font-size:1.4rem; }
        .step-connector { flex:1; height:2px; background:#dee2e6; align-self:center; margin:0 4px; }
        .step-connector.done { background:#198754; }
        .address-suggestions { background:#fff; border:1px solid #dee2e6; }
        .dark-mode .address-suggestions { background:#2d2d2d; border-color:#444; color:#e0e0e0; }
        .dark-mode .address-suggestions .list-group-item {
            background:#2d2d2d; color:#e0e0e0; border-color:#444;
        }

        /* ── Stripe Elements ─────────────────────────────────────── */
        #stripe-payment-block {
            display: none;
            animation: fadeIn .25s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

        #stripe-card-element {
            padding: .6rem .8rem;
            border: 1px solid #dee2e6;
            border-radius: .375rem;
            background: #fff;
            transition: border-color .15s;
        }
        #stripe-card-element.StripeElement--focus { border-color: #0d6efd; box-shadow: 0 0 0 .2rem rgba(13,110,253,.25); }
        #stripe-card-element.StripeElement--invalid { border-color: #dc3545; }
        .dark-mode #stripe-card-element { background:#1e1e2e; border-color:#444; color:#e0e0e0; }

        .stripe-badge {
            display: inline-flex; align-items: center; gap: .3rem;
            font-size: .7rem; color: #6c757d; font-weight: 500;
        }
        .stripe-badge img { height: 18px; }

        #stripe-error-msg {
            font-size: .85rem;
            color: #dc3545;
        }

        .mp-stripe-label {
            background: linear-gradient(90deg,#635bff 0%,#0d6efd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
    </style>
</head>
<body>

<?php
$showSearch = false;
include("navbar.php");
?>

<div class="container py-4" style="max-width:800px;">

    <?php if (isset($_GET['reporte']) && $_GET['reporte'] === 'ok'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-1"></i> Reporte enviado. Lo revisaremos próximamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'metodo_pago'): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-1"></i> Debes seleccionar un método de pago.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php
    $stripeErrors = [
        'stripe_no_intent'   => 'No se recibió confirmación del pago. Inténtalo de nuevo.',
        'stripe_config'      => 'El sistema de pagos con tarjeta no está configurado correctamente.',
        'stripe_pago_fallido'=> 'El pago con tarjeta no fue completado o no es válido.',
        'stripe_error'       => 'Error de comunicación con Stripe. Inténtalo de nuevo.',
    ];
    $errCode = $_GET['error'] ?? '';
    if (isset($stripeErrors[$errCode])):
    ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-credit-card me-1"></i> <?= htmlspecialchars($stripeErrors[$errCode]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['stripe']) && $_GET['stripe'] === 'ok'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-1"></i>
            ¡Pago con tarjeta confirmado! El vendedor recibirá el aviso para preparar el envío.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Cabecera -->
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="d-flex align-items-center gap-3">
            <?php
            $img = !empty($chat["producto_imagen"])
                ? $BASE . "/" . htmlspecialchars($chat["producto_imagen"])
                : $BASE . "/public/img/default.jpg";
            ?>
            <img src="<?= $img ?>" alt="Producto"
                 class="rounded shadow-sm"
                 style="width:64px;height:64px;object-fit:cover;"
                 onerror="this.onerror=null;this.src='<?= $BASE ?>/public/img/default.jpg'">
            <div>
                <h5 class="mb-0 fw-bold"><?= htmlspecialchars($chat["producto_titulo"] ?? "Producto") ?></h5>
                <a href="detail_product.php?id=<?= intval($chat["producto_id"]) ?>"
                   class="small text-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Ver producto
                </a>
            </div>
        </div>
        <?php if (!empty($chat["producto_id"])): ?>
        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalReporte">
            <i class="bi bi-flag me-1"></i> Reportar
        </button>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         BLOQUE DE TRANSACCIÓN
    ══════════════════════════════════════════════════════════ -->
    <?php if ($transaccion): ?>
        <?php $estado = $transaccion["estado"]; ?>

        <!-- Timeline visual -->
        <div class="card mb-3 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center step-bar">

                    <?php
                    $pasosMostrar = [
                        'pendiente'      => ['bi-hourglass-split', 'Pendiente'],
                        'aceptada'       => ['bi-hand-thumbs-up',  'Aceptada'],
                        'pago_pendiente' => ['bi-credit-card',     'Pago'],
                        'enviado'        => ['bi-truck',           'Enviado'],
                        'entregado'      => ['bi-check-circle',    'Entregado'],
                    ];
                    $pasoKeys = array_keys($pasosMostrar);
                    foreach ($pasosMostrar as $key => [$icono, $label]):
                        $isLast = ($key === 'entregado');
                    ?>
                        <div class="step">
                            <div class="step-icon <?= iconClass($estado, $key) ?>">
                                <?php if ($estado === 'cancelada' && $key === 'pendiente'): ?>
                                    <i class="bi bi-x-circle text-danger"></i>
                                <?php else: ?>
                                    <i class="bi <?= $icono ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="small mt-1 <?= stepClass($estado, $key) ?>"><?= $label ?></div>
                        </div>
                        <?php if (!$isLast): ?>
                            <div class="step-connector <?= (stepIndex($estado) > stepIndex($key)) ? 'done' : '' ?>"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($estado === 'cancelada'): ?>
                        <div class="step-connector"></div>
                        <div class="step">
                            <div class="step-icon text-danger"><i class="bi bi-x-circle"></i></div>
                            <div class="small text-danger fw-bold">Cancelada</div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Resumen de datos de la transacción -->
        <?php if (in_array($estado, ['aceptada','pago_pendiente','enviado','entregado'])): ?>
        <div class="card mb-3 shadow-sm border-0 bg-light">
            <div class="card-body py-2 px-3">
                <div class="row g-2 small">
                    <?php if ($transaccion['metodo_pago']): ?>
                    <div class="col-12 col-sm-4">
                        <?php
                        [$icPago, $lblPago] = $etiquetaMetodo[$transaccion['metodo_pago']] ?? ['bi-question', $transaccion['metodo_pago']];
                        ?>
                        <i class="bi <?= $icPago ?> me-1 text-primary"></i>
                        <strong>Pago:</strong> <?= htmlspecialchars($lblPago) ?>
                        <?php if ($transaccion['metodo_pago'] === 'stripe'): ?>
                            <span class="badge bg-success ms-1" style="font-size:.65rem">
                                <i class="bi bi-check-circle me-1"></i>Confirmado
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($transaccion['direccion_envio']): ?>
                    <div class="col-12 col-sm-8">
                        <i class="bi bi-geo-alt me-1 text-primary"></i>
                        <strong>Envío a:</strong> <?= htmlspecialchars($transaccion['direccion_envio']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($transaccion['numero_seguimiento']): ?>
                    <div class="col-12">
                        <i class="bi bi-box-seam me-1 text-primary"></i>
                        <strong>Nº seguimiento:</strong>
                        <code><?= htmlspecialchars($transaccion['numero_seguimiento']) ?></code>
                    </div>
                    <?php endif; ?>
                    <?php if ($transaccion['notas_comprador']): ?>
                    <div class="col-12">
                        <i class="bi bi-chat-left-text me-1 text-secondary"></i>
                        <strong>Notas:</strong> <?= htmlspecialchars($transaccion['notas_comprador']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php
        $esIntercambio   = in_array($transaccion['tipo'] ?? '', ['intercambio', 'mixto']);
        $intercambioDetalle = $esIntercambio ? $transactionModel->getIntercambioDetalle($transaccion['id']) : [];
        ?>

        <!-- Aviso contextual -->
        <?php if ($estado === 'pendiente'): ?>
            <div class="alert alert-warning">
                <i class="bi bi-hourglass me-1"></i>
                <?php if ($esIntercambio): ?>
                    <?= $esComprador
                        ? 'El vendedor quiere hacer un intercambio. Elige qué producto ofreces a cambio y acepta o cancela.'
                        : 'Esperando a que el comprador proponga un producto para el intercambio.' ?>
                <?php else: ?>
                    <?= $esComprador
                        ? 'El vendedor ha iniciado una transacción. Revisa los detalles, elige cómo pagar y acepta o cancela.'
                        : 'Esperando a que el comprador acepte la transacción.' ?>
                <?php endif; ?>
            </div>
        <?php elseif ($estado === 'aceptada'): ?>
            <div class="alert alert-info">
                <i class="bi bi-credit-card me-1"></i>
                <?= $esComprador
                    ? 'Has aceptado la transacción. Cuando hayas realizado el pago, pulsa "Ya he pagado" para notificar al vendedor.'
                    : 'El comprador ha aceptado. Espera a que confirme el pago antes de enviar el producto.' ?>
            </div>
        <?php elseif ($estado === 'pago_pendiente'): ?>
            <?php if ($transaccion['metodo_pago'] === 'stripe'): ?>
            <div class="alert alert-success">
                <i class="bi bi-credit-card-2-front me-1"></i>
                <?= $esVendedor
                    ? '<strong>Pago con tarjeta confirmado por Stripe.</strong> El dinero está asegurado. Prepara el producto para el envío.'
                    : '<strong>Tu pago con tarjeta fue procesado correctamente.</strong> El vendedor preparará el envío.' ?>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-hourglass-split me-1"></i>
                <?= $esVendedor
                    ? 'El comprador indica que ha realizado el pago. Compruébalo y marca el producto como enviado.'
                    : 'Has notificado el pago. Espera a que el vendedor confirme y envíe el producto.' ?>
            </div>
            <?php endif; ?>
        <?php elseif ($estado === 'enviado'): ?>
            <div class="alert alert-success">
                <i class="bi bi-truck me-1"></i>
                <?= $esComprador
                    ? 'El producto está en camino. Cuando lo recibas, confírmalo para cerrar la transacción.'
                    : 'Esperando a que el comprador confirme la recepción.' ?>
            </div>
        <?php elseif ($estado === 'entregado'): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-1"></i>
                ¡Transacción completada con éxito!
            </div>
        <?php elseif ($estado === 'cancelada'): ?>
            <div class="alert alert-danger">
                <i class="bi bi-x-circle me-1"></i>
                La transacción fue cancelada. El producto está de nuevo disponible.
            </div>
        <?php endif; ?>

        <!-- Botones / formularios de acción -->
        <?php if (!in_array($estado, ['entregado', 'cancelada'])): ?>

            <!-- ACEPTAR (comprador, desde pendiente) -->
            <?php if ($estado === 'pendiente' && $esComprador): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-header fw-semibold">
                    <i class="bi bi-hand-thumbs-up me-1"></i>
                    <?= $esIntercambio ? 'Proponer intercambio' : 'Aceptar transacción' ?>
                </div>
                <div class="card-body">
                    <form action="<?= $BASE ?>/controllers/chat_update_transaction.php" method="POST">
                        <input type="hidden" name="transaccion_id" value="<?= intval($transaccion['id']) ?>">
                        <input type="hidden" name="chat_id"        value="<?= $chatId ?>">
                        <input type="hidden" name="estado"         value="aceptada">

                        <?php if ($esIntercambio): ?>
                        <!-- Selector de producto a ofrecer en el intercambio -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-arrow-left-right me-1"></i>
                                Tu producto a cambio <span class="text-danger">*</span>
                            </label>
                            <select name="producto_ofrecido_id" id="sel-producto-ofrecido"
                                    class="form-select" required>
                                <option value="">Cargando tus productos...</option>
                            </select>
                            <div id="preview-producto-ofrecido" class="mt-2 d-none">
                                <img id="img-producto-ofrecido" src="" alt=""
                                     class="rounded" style="width:56px;height:56px;object-fit:cover;">
                                <span id="titulo-producto-ofrecido" class="small ms-2"></span>
                            </div>
                            <div class="form-text">Solo se muestran tus productos activos.</div>
                        </div>
                        <?php endif; ?>

                        <?php if (!$esIntercambio || $transaccion['tipo'] === 'mixto'): ?>
                        <!-- Método de pago (no aplica en intercambio puro) -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Método de pago
                                <?= $transaccion['tipo'] === 'mixto' ? '<span class="text-danger">*</span>' : '' ?>
                                <?= $transaccion['tipo'] === 'mixto' ? '<small class="text-muted fw-normal">(parte en dinero)</small>' : '' ?>
                            </label>
                            <div class="d-grid gap-2">
                                <?php
                                // Obtener precio del producto para mostrar en el botón Stripe
                                $productoParaStripe = $productoModel->getById($chat['producto_id'] ?? 0);
                                $precioProducto     = floatval($productoParaStripe['precio'] ?? 0);
                                $stripeDisponible   = $precioProducto >= 0.50;

                                $metodos = [
                                    'efectivo'      => ['bi-cash-coin', 'Efectivo al entregar'],
                                    'transferencia' => ['bi-bank',      'Transferencia bancaria'],
                                    'bizum'         => ['bi-phone',     'Bizum'],
                                    'paypal'        => ['bi-paypal',    'PayPal'],
                                    'otro'          => ['bi-three-dots','Otro método'],
                                ];
                                foreach ($metodos as $val => [$ic, $lbl]):
                                ?>
                                <div class="form-check border rounded p-2">
                                    <input class="form-check-input" type="radio"
                                           name="metodo_pago" id="mp-<?= $val ?>" value="<?= $val ?>"
                                           <?= $transaccion['tipo'] !== 'intercambio' ? 'required' : '' ?>>
                                    <label class="form-check-label w-100" for="mp-<?= $val ?>">
                                        <i class="bi <?= $ic ?> me-1"></i> <?= $lbl ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>

                                <!-- ── Stripe ───────────────────────────────── -->
                                <div class="form-check border rounded p-2 border-primary-subtle"
                                     style="background:linear-gradient(135deg,rgba(99,91,255,.04) 0%,rgba(13,110,253,.04) 100%)">
                                    <input class="form-check-input" type="radio"
                                           name="metodo_pago" id="mp-stripe" value="stripe"
                                           <?= $transaccion['tipo'] !== 'intercambio' ? 'required' : '' ?>
                                           <?= !$stripeDisponible ? 'disabled' : '' ?>>
                                    <label class="form-check-label w-100 d-flex align-items-center justify-content-between"
                                           for="mp-stripe">
                                        <span>
                                            <i class="bi bi-credit-card-2-front me-1"></i>
                                            <span class="mp-stripe-label">Tarjeta de crédito / débito</span>
                                        </span>
                                        <span class="stripe-badge">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 25" height="18">
                                                <path d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a8.33 8.33 0 0 1-4.56 1.1c-4.01 0-6.83-2.5-6.83-7.48 0-4.19 2.39-7.52 6.3-7.52 3.92 0 5.96 3.28 5.96 7.5 0 .4-.04 1.26-.06 1.48zm-5.92-5.62c-1.03 0-2.17.73-2.17 2.58h4.25c0-1.85-1.07-2.58-2.08-2.58zM40.95 20.3c-1.44 0-2.32-.6-2.9-1.04l-.02 4.63-4.12.87V6.27h3.78l.19 1.02a4.7 4.7 0 0 1 3.23-1.29c2.9 0 5.62 2.6 5.62 7.4 0 5.23-2.7 6.9-5.78 6.9zm-.94-9.54c-.85 0-1.56.31-1.98.75l.02 5.25c.42.44 1.13.72 1.96.72 1.5 0 2.54-1.65 2.54-3.36 0-1.87-1.03-3.36-2.54-3.36zM28.24 5.07c-1.44 0-2.36 1.06-2.36 2.56 0 1.51.92 2.56 2.36 2.56s2.36-1.05 2.36-2.56c0-1.5-.92-2.56-2.36-2.56zm2.07 15.19h-4.14V6.27h4.14V20.26zM18.99 20.3c-4.14 0-6.32-2.23-6.32-7.4 0-4.95 2.28-7.6 6.32-7.6 4.06 0 6.32 2.65 6.32 7.6 0 5.17-2.18 7.4-6.32 7.4zm0-11.45c-1.26 0-2.1 1.17-2.1 4.05 0 2.86.84 4.05 2.1 4.05 1.27 0 2.1-1.2 2.1-4.05 0-2.88-.83-4.05-2.1-4.05zM11.68 20.26H7.56V6.27h4.12V20.26zm0-17.39H7.56V.45h4.12v2.42zM4.9 20.26H.77V.45h4.13V20.26z" fill="#635bff"/>
                                            </svg>
                                            Seguro · Cifrado
                                        </span>
                                    </label>
                                    <?php if (!$stripeDisponible): ?>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-info-circle me-1"></i>
                                        No disponible cuando el precio es inferior a 0,50 €.
                                    </div>
                                    <?php else: ?>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-lock-fill me-1 text-success"></i>
                                        Pago seguro · <?= number_format($precioProducto, 2) ?> €
                                        · No se guarda tu tarjeta
                                    </div>
                                    <?php endif; ?>
                                </div>

                            </div>

                            <!-- Bloque Stripe Elements (se muestra al seleccionar stripe) -->
                            <?php if ($stripeDisponible): ?>
                            <div id="stripe-payment-block" class="mt-3 p-3 border rounded-3 border-primary-subtle">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-semibold small">
                                        <i class="bi bi-credit-card me-1 text-primary"></i>
                                        Datos de tu tarjeta
                                    </span>
                                    <div class="d-flex gap-1 opacity-75">
                                        <img src="https://raw.githubusercontent.com/itsalb3rt/payment-icons/master/src/visa.svg"
                                             height="20" alt="Visa" title="Visa">
                                        <img src="https://raw.githubusercontent.com/itsalb3rt/payment-icons/master/src/mastercard.svg"
                                             height="20" alt="Mastercard" title="Mastercard">
                                        <img src="https://raw.githubusercontent.com/itsalb3rt/payment-icons/master/src/amex.svg"
                                             height="20" alt="Amex" title="American Express">
                                    </div>
                                </div>
                                <div id="stripe-card-element"></div>
                                <div id="stripe-error-msg" class="mt-2 d-none">
                                    <i class="bi bi-exclamation-circle me-1"></i>
                                    <span id="stripe-error-text"></span>
                                </div>
                                <div class="mt-2 d-flex align-items-center gap-1 text-muted" style="font-size:.75rem">
                                    <i class="bi bi-shield-lock-fill text-success"></i>
                                    Conexión cifrada TLS · Procesado por Stripe
                                </div>
                                <input type="hidden" name="stripe_payment_intent_id" id="stripe-pi-id">
                            </div>
                            <?php endif; ?>

                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="direccion_envio" class="form-label fw-semibold">
                                <i class="bi bi-geo-alt me-1"></i> Dirección de envío
                                <small class="text-muted fw-normal">(opcional para recogida en mano)</small>
                            </label>
                            <input type="text" id="direccion_envio" name="direccion_envio"
                                   class="form-control"
                                   placeholder="Escribe tu dirección para buscar..."
                                   autocomplete="off">
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i> Sugerencias de OpenStreetMap.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notas_comprador" class="form-label fw-semibold">
                                Notas para el vendedor
                                <small class="text-muted fw-normal">(opcional)</small>
                            </label>
                            <textarea id="notas_comprador" name="notas_comprador"
                                      class="form-control" rows="2" maxlength="300"
                                      placeholder="Instrucciones especiales, horario de recogida..."></textarea>
                        </div>

                        <button type="submit" id="btn-aceptar-transaccion" class="btn btn-success w-100">
                            <i class="bi bi-hand-thumbs-up me-1" id="btn-aceptar-icon"></i>
                            <span id="btn-aceptar-texto">
                                <?= $esIntercambio ? 'Proponer intercambio' : 'Aceptar y confirmar datos' ?>
                            </span>
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($esIntercambio): ?>
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                const sel     = document.getElementById('sel-producto-ofrecido');
                const preview = document.getElementById('preview-producto-ofrecido');
                const img     = document.getElementById('img-producto-ofrecido');
                const tit     = document.getElementById('titulo-producto-ofrecido');
                if (!sel) return;

                fetch(`${BASE}/api/mis_productos_activos.php`)
                    .then(r => r.json())
                    .then(res => {
                        sel.innerHTML = '<option value="">— Selecciona un producto —</option>';
                        (res.data || []).forEach(p => {
                            const opt = document.createElement('option');
                            opt.value = p.id;
                            opt.textContent = `${p.titulo}${p.precio ? ' (' + parseFloat(p.precio).toFixed(2) + ' €)' : ''}`;
                            opt.dataset.imagen = p.imagen || '';
                            sel.appendChild(opt);
                        });
                        if (!res.data || res.data.length === 0) {
                            sel.innerHTML = '<option value="">No tienes productos activos para intercambiar</option>';
                        }
                    });

                sel.addEventListener('change', () => {
                    const opt = sel.options[sel.selectedIndex];
                    if (opt.value && opt.dataset.imagen) {
                        img.src = `${BASE}/${opt.dataset.imagen}`;
                        tit.textContent = opt.textContent;
                        preview.classList.remove('d-none');
                    } else {
                        preview.classList.add('d-none');
                    }
                });
            });
            </script>
            <?php endif; ?>

            <?php endif; ?>

            <?php if (!empty($intercambioDetalle) && in_array($estado, ['aceptada','pago_pendiente','enviado','entregado'])): ?>
            <div class="card mb-3 border-0 bg-light shadow-sm">
                <div class="card-body py-2 px-3">
                    <div class="fw-semibold small mb-2">
                        <i class="bi bi-arrow-left-right me-1 text-primary"></i> Productos del intercambio
                    </div>
                    <?php foreach ($intercambioDetalle as $det): ?>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <?php if ($det['imagen']): ?>
                        <img src="<?= $BASE . '/' . htmlspecialchars($det['imagen']) ?>"
                             class="rounded" style="width:40px;height:40px;object-fit:cover;"
                             onerror="this.src='<?= $BASE ?>/public/img/default.jpg'">
                        <?php endif; ?>
                        <span class="small"><?= htmlspecialchars($det['titulo'] ?? 'Producto eliminado') ?></span>
                        <?php if ($det['precio']): ?>
                        <span class="ms-auto small fw-semibold text-primary">
                            <?= number_format($det['precio'], 2) ?> €
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- YA HE PAGADO (comprador, desde aceptada) -->
            <?php if ($estado === 'aceptada' && $esComprador): ?>
            <form action="<?= $BASE ?>/controllers/chat_update_transaction.php" method="POST" class="mb-2">
                <input type="hidden" name="transaccion_id" value="<?= intval($transaccion['id']) ?>">
                <input type="hidden" name="chat_id"        value="<?= $chatId ?>">
                <input type="hidden" name="estado"         value="pago_pendiente">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-credit-card me-1"></i> Ya he realizado el pago
                </button>
            </form>
            <?php endif; ?>

            <!-- MARCAR ENVIADO (vendedor, desde pago_pendiente) -->
            <?php if ($estado === 'pago_pendiente' && $esVendedor): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-header fw-semibold">
                    <i class="bi bi-truck me-1"></i> Marcar como enviado
                </div>
                <div class="card-body">
                    <form action="<?= $BASE ?>/controllers/chat_update_transaction.php" method="POST">
                        <input type="hidden" name="transaccion_id" value="<?= intval($transaccion['id']) ?>">
                        <input type="hidden" name="chat_id"        value="<?= $chatId ?>">
                        <input type="hidden" name="estado"         value="enviado">

                        <div class="mb-3">
                            <label for="numero_seguimiento" class="form-label fw-semibold">
                                Número de seguimiento
                                <small class="text-muted fw-normal">(opcional)</small>
                            </label>
                            <input type="text" id="numero_seguimiento" name="numero_seguimiento"
                                   class="form-control"
                                   placeholder="Ej: ES123456789ES, 1Z9999W999…">
                            <div class="form-text">
                                Puedes dejarlo en blanco si es entrega en mano o aún no tienes el número.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-truck me-1"></i> Confirmar envío
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- CONFIRMAR ENTREGA (comprador, desde enviado) -->
            <?php if ($estado === 'enviado' && $esComprador): ?>
            <form action="<?= $BASE ?>/controllers/chat_update_transaction.php" method="POST" class="mb-2">
                <input type="hidden" name="transaccion_id" value="<?= intval($transaccion['id']) ?>">
                <input type="hidden" name="chat_id"        value="<?= $chatId ?>">
                <input type="hidden" name="estado"         value="entregado">
                <button type="submit" class="btn btn-success w-100 mb-2"
                        onclick="return confirm('¿Confirmas que has recibido el producto correctamente?')">
                    <i class="bi bi-check-circle me-1"></i> Confirmar recepción del producto
                </button>
            </form>
            <?php endif; ?>

            <!-- CANCELAR (cualquiera) -->
            <form action="<?= $BASE ?>/controllers/chat_update_transaction.php" method="POST" class="mb-3">
                <input type="hidden" name="transaccion_id" value="<?= intval($transaccion['id']) ?>">
                <input type="hidden" name="chat_id"        value="<?= $chatId ?>">
                <input type="hidden" name="estado"         value="cancelada">
                <button type="submit" class="btn btn-outline-danger w-100"
                        onclick="return confirm('¿Seguro que quieres cancelar la transacción?')">
                    <i class="bi bi-x-circle me-1"></i> Cancelar transacción
                </button>
            </form>

        <?php endif; ?>

    <?php endif; ?>

    <!-- Iniciar transacción (vendedor, sin transacción activa) -->
    <?php if ($esVendedor && !$transaccion): ?>
        <form action="<?= $BASE ?>/controllers/chat_start_transaction.php" method="POST" class="mb-3">
            <input type="hidden" name="chat_id" value="<?= $chatId ?>">
            <button class="btn btn-success w-100">
                <i class="bi bi-bag-check me-1"></i> Iniciar transacción
            </button>
        </form>
    <?php endif; ?>

    <!-- ── Mensajes ──────────────────────────────────────────── -->
    <div class="card shadow-sm mb-3">
        <div class="card-body chat-box" id="chat-box">
            <?php foreach ($mensajes as $msg): ?>

                <?php if ($msg["usuario_id"] === null): ?>
                    <div class="msg-sistema">
                        <span class="bubble">
                            <i class="bi bi-info-circle me-1"></i>
                            <?= htmlspecialchars(
                                preg_replace('/^\[SISTEMA\]\s*/i', '', $msg["contenido"])
                            ) ?>
                        </span>
                    </div>

                <?php else: ?>
                    <?php $esMio = ($msg["usuario_id"] == $usuarioActual); ?>
                    <div class="mb-2 d-flex <?= $esMio ? 'justify-content-end' : 'justify-content-start' ?>">
                        <div style="max-width:75%">
                            <div class="small text-muted mb-1 <?= $esMio ? 'text-end' : '' ?>">
                                <?= htmlspecialchars($msg["sender_name"] ?? '') ?>
                                · <?= htmlspecialchars($msg["fecha_envio"]) ?>
                            </div>
                            <div class="px-3 py-2 <?= $esMio ? 'bubble-out' : 'bubble-in' ?>">
                                <?= nl2br(htmlspecialchars($msg["contenido"])) ?>
                            </div>
                            <?php if ($esMio): ?>
                                <div class="small text-muted mt-1 text-end">
                                    <?= $msg["leido"] ? "✓✓ Leído" : "✓ Enviado" ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endforeach; ?>

            <?php if (empty($mensajes)): ?>
                <p class="text-center text-muted small py-4">
                    <i class="bi bi-chat-dots me-1"></i> Sé el primero en escribir algo.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Enviar mensaje -->
    <?php if ($transaccion && in_array($transaccion["estado"], ["entregado", "cancelada"])): ?>
        <div class="alert alert-secondary text-center mb-3">
            <i class="bi bi-lock me-1"></i> Chat cerrado — la transacción ha finalizado.
        </div>
    <?php else: ?>
        <form method="POST" class="d-flex gap-2 mb-3">
            <textarea name="mensaje" class="form-control" rows="2"
                      placeholder="Escribe un mensaje..."
                      style="resize:none;"></textarea>
            <button class="btn btn-primary align-self-end">
                <i class="bi bi-send"></i>
            </button>
        </form>
    <?php endif; ?>

    <a href="chat_list.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a mis chats
    </a>

</div><!-- /container -->

<?php include __DIR__ . '/footer.php'; ?>

<!-- ══════════════════════════════════════════════════════
     MODAL — REPORTAR
══════════════════════════════════════════════════════ -->
<?php if (!empty($chat["producto_id"])): ?>
<div class="modal fade" id="modalReporte" tabindex="-1" aria-labelledby="modalReporteLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="chat.php?id=<?= $chatId ?>">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="modalReporteLabel">
                        <i class="bi bi-flag-fill me-2"></i>Reportar producto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Indica el motivo del reporte. Nuestro equipo lo revisará y tomará las
                        medidas necesarias.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                        <div class="d-grid gap-2">
                            <?php
                            $motivos = [
                                'Producto falso o fraudulento',
                                'Precio engañoso',
                                'Contenido inapropiado o ilegal',
                                'El vendedor no responde',
                                'Posible estafa',
                                'Otro',
                            ];
                            foreach ($motivos as $m):
                            ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="reporte_motivo_preset"
                                       id="motivo-<?= md5($m) ?>"
                                       value="<?= htmlspecialchars($m) ?>"
                                       onchange="document.getElementById('motivo-custom').disabled = (this.value !== 'Otro');">
                                <label class="form-check-label" for="motivo-<?= md5($m) ?>">
                                    <?= htmlspecialchars($m) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="motivo-custom" class="form-label fw-semibold">Detalle adicional</label>
                        <textarea id="motivo-custom" class="form-control" rows="3"
                                  maxlength="500"
                                  placeholder="Describe brevemente el problema..." disabled></textarea>
                    </div>
                    <input type="hidden" name="reporte_motivo" id="reporte-motivo-final">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btn-enviar-reporte" disabled>
                        <i class="bi bi-flag me-1"></i> Enviar reporte
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const radios = document.querySelectorAll('input[name="reporte_motivo_preset"]');
    const custom = document.getElementById('motivo-custom');
    const final  = document.getElementById('reporte-motivo-final');
    const btn    = document.getElementById('btn-enviar-reporte');

    function actualizar() {
        const sel = document.querySelector('input[name="reporte_motivo_preset"]:checked');
        if (!sel) { btn.disabled = true; return; }
        if (sel.value === 'Otro') {
            const txt = custom.value.trim();
            final.value  = txt || 'Otro';
            btn.disabled = txt === '';
        } else {
            final.value  = sel.value;
            btn.disabled = false;
        }
    }

    radios.forEach(r => r.addEventListener('change', actualizar));
    custom.addEventListener('input', actualizar);
})();
</script>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════
     MODAL — VALORACIÓN
══════════════════════════════════════════════════════ -->
<?php if ($mostrarModalValoracion && $transaccion): ?>
<div class="modal fade" id="modalValoracion" tabindex="-1"
     aria-labelledby="modalValoracionLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalValoracionLabel">
                    <i class="bi bi-star-fill text-warning me-2"></i>Valora tu experiencia
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-4">Puntúa del 1 (peor) al 5 (mejor) cada criterio.</p>

                <?php
                $criterios = [
                    'fiabilidad'   => ['bi-shield-check', 'Fiabilidad'],
                    'comunicacion' => ['bi-chat-dots',    'Comunicación'],
                    'puntualidad'  => ['bi-clock',        'Puntualidad'],
                ];
                foreach ($criterios as $campo => [$icono, $label]):
                ?>
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="bi <?= $icono ?> me-1"></i> <?= $label ?>
                    </label>
                    <div class="star-group d-flex gap-2 fs-3" data-field="<?= $campo ?>">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star star-btn" data-value="<?= $i ?>"
                               style="cursor:pointer;color:#ccc;"></i>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="inp-<?= $campo ?>" value="0">
                </div>
                <?php endforeach; ?>

                <div class="mb-3">
                    <label for="comentario-val" class="form-label fw-semibold">
                        <i class="bi bi-pencil me-1"></i>
                        Comentario <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <textarea id="comentario-val" class="form-control" rows="3"
                              maxlength="500"
                              placeholder="Cuéntanos cómo fue la experiencia..."></textarea>
                </div>

                <div id="resumen-media" class="alert alert-light text-center d-none">
                    Puntuación media: <strong id="media-val">—</strong>
                    <span class="text-warning" id="media-stars"></span>
                </div>
                <div id="rating-feedback" class="d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Ahora no</button>
                <button type="button" id="btn-enviar-valoracion" class="btn btn-primary" disabled>
                    <i class="bi bi-send me-1"></i> Enviar valoración
                </button>
            </div>
        </div>
    </div>
</div>

<script
    src="../js/rating.js"
    data-transaccion-id="<?= intval($transaccion['id']) ?>"
    data-chat-id="<?= $chatId ?>"
    defer
></script>
<?php endif; ?>

<!-- Autocompletado de dirección + scroll automático + Stripe -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Scroll al final del chat
    const box = document.getElementById('chat-box');
    if (box) box.scrollTop = box.scrollHeight;

    // Autocomplete de dirección (si el input existe en esta página)
    if (typeof initAddressAutocomplete === 'function') {
        initAddressAutocomplete('direccion_envio', '<?= $BASE ?>/api/normalize_address.php');
    }

    // El modal de valoración lo abre rating.js automáticamente

    // ── Stripe Elements ───────────────────────────────────────────────────────
    const stripeBlock  = document.getElementById('stripe-payment-block');
    const stripeErrorEl = document.getElementById('stripe-error-msg');
    const stripeErrorTx = document.getElementById('stripe-error-text');
    const btnAceptar   = document.getElementById('btn-aceptar-transaccion');
    const btnTexto     = document.getElementById('btn-aceptar-texto');
    const btnIcon      = document.getElementById('btn-aceptar-icon');
    const piInput      = document.getElementById('stripe-pi-id');
    const formAceptar  = btnAceptar ? btnAceptar.closest('form') : null;

    if (!stripeBlock || !formAceptar) return; // no hay formulario de aceptación

    const STRIPE_PK      = '<?= htmlspecialchars($_ENV['STRIPE_KEY'] ?? '') ?>';
    const TRANSACCION_ID = <?= intval($transaccion['id'] ?? 0) ?>;

    const stripe      = Stripe(STRIPE_PK);
    const elements    = stripe.elements();
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontFamily: 'system-ui, sans-serif',
                fontSize: '15px',
                color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#212529',
                '::placeholder': { color: '#adb5bd' },
            },
            invalid: { color: '#dc3545' },
        },
        hidePostalCode: true,
    });
    cardElement.mount('#stripe-card-element');

    cardElement.on('change', e => {
        if (e.error) {
            stripeErrorTx.textContent = e.error.message;
            stripeErrorEl.classList.remove('d-none');
        } else {
            stripeErrorEl.classList.add('d-none');
        }
    });

    // Mostrar/ocultar bloque Stripe según método elegido
    document.querySelectorAll('input[name="metodo_pago"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'stripe' && radio.checked) {
                stripeBlock.style.display = 'block';
                btnTexto.textContent = `Pagar ${parseFloat('<?= $precioProducto ?? 0 ?>').toFixed(2).replace('.', ',')} € con tarjeta`;
                btnIcon.className = 'bi bi-lock-fill me-1';
                btnAceptar.classList.replace('btn-success', 'btn-primary');
            } else if (radio.checked) {
                stripeBlock.style.display = 'none';
                btnTexto.textContent = '<?= $esIntercambio ? 'Proponer intercambio' : 'Aceptar y confirmar datos' ?>';
                btnIcon.className = 'bi bi-hand-thumbs-up me-1';
                btnAceptar.classList.replace('btn-primary', 'btn-success');
            }
        });
    });

    // Interceptar envío del formulario si el método es Stripe
    formAceptar.addEventListener('submit', async e => {
        const stripeRadio = document.getElementById('mp-stripe');
        if (!stripeRadio || !stripeRadio.checked) return; // flujo normal

        e.preventDefault();
        btnAceptar.disabled = true;
        btnTexto.textContent = 'Procesando pago…';
        btnIcon.className = 'spinner-border spinner-border-sm me-1';
        stripeErrorEl.classList.add('d-none');

        try {
            // 1. Crear PaymentIntent en el servidor
            const resp = await fetch('<?= $BASE ?>/api/stripe_create_payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `transaccion_id=${TRANSACCION_ID}`,
            });

            if (!resp.ok) {
                const err = await resp.json();
                throw new Error(err.error || 'Error al iniciar el pago');
            }

            const { client_secret } = await resp.json();

            // 2. Confirmar pago con Stripe.js
            const { paymentIntent, error } = await stripe.confirmCardPayment(client_secret, {
                payment_method: { card: cardElement },
            });

            if (error) {
                throw new Error(error.message);
            }

            if (paymentIntent.status !== 'succeeded') {
                throw new Error('El pago no pudo completarse. Inténtalo de nuevo.');
            }

            // 3. Guardar el ID del PaymentIntent y enviar el formulario
            piInput.value = paymentIntent.id;
            formAceptar.submit();

        } catch (err) {
            stripeErrorTx.textContent = err.message;
            stripeErrorEl.classList.remove('d-none');
            btnAceptar.disabled = false;
            btnTexto.textContent = `Pagar ${parseFloat('<?= $precioProducto ?? 0 ?>').toFixed(2).replace('.', ',')} € con tarjeta`;
            btnIcon.className = 'bi bi-lock-fill me-1';
        }
    });
});
</script>

</body>
</html>
