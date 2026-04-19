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
    'pendiente'     => ['aceptada',        'cancelada'],
    'aceptada'      => ['pago_pendiente',   'cancelada'],
    'pago_pendiente'=> ['enviado',          'cancelada'],
    'enviado'       => ['entregado',        'cancelada'],
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
    case 'aceptada':
        // Solo el comprador acepta y aporta datos de pago y envío
        if (!$esComprador) {
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
        // Solo el vendedor marca como enviado (confirma pago + añade tracking)
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

    case 'aceptada':
        $metodoPago     = trim($_POST["metodo_pago"]     ?? "");
        $direccionEnvio = trim($_POST["direccion_envio"] ?? "");
        $notas          = trim($_POST["notas_comprador"] ?? "");
        $productoOfrecidoId = intval($_POST["producto_ofrecido_id"] ?? 0);

        $tipoTransaccion = $transaccion['tipo'];
        $esIntercambio   = in_array($tipoTransaccion, ['intercambio', 'mixto']);

        // Para venta y mixto se requiere método de pago; intercambio puro no
        $metodosValidos = ['efectivo', 'transferencia', 'bizum', 'paypal', 'otro'];
        if (!$esIntercambio || $tipoTransaccion === 'mixto') {
            if (!in_array($metodoPago, $metodosValidos)) {
                header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=metodo_pago");
                exit;
            }
        }

        // En intercambio/mixto el comprador debe proponer un producto
        if ($esIntercambio && $productoOfrecidoId <= 0) {
            header("Location: {$BASE}/public/views/chat.php?id={$chatId}&error=producto_ofrecido");
            exit;
        }

        $transactionModel->aceptar($transaccionId, $metodoPago ?: null, $direccionEnvio, $notas);

        // Guardar producto ofrecido en Intercambio_Detalle
        if ($esIntercambio && $productoOfrecidoId > 0) {
            $transactionModel->addIntercambioProducto($transaccionId, $productoOfrecidoId);
        }

        $etiquetas = [
            'efectivo'      => 'Efectivo al entregar',
            'transferencia' => 'Transferencia bancaria',
            'bizum'         => 'Bizum',
            'paypal'        => 'PayPal',
            'otro'          => 'Otro método',
        ];
        $etiqueta = isset($etiquetas[$metodoPago]) ? $etiquetas[$metodoPago] : '';

        $msgAceptada = "El comprador ha aceptado la transacción.";
        if ($etiqueta)      $msgAceptada .= " Método de pago: {$etiqueta}.";
        if ($direccionEnvio) $msgAceptada .= " Dirección de envío: {$direccionEnvio}.";
        if ($esIntercambio && $productoOfrecidoId > 0) {
            $msgAceptada .= " El comprador ha propuesto un producto a cambio.";
        }

        $messageModel->enviarMensajeSistema($chatId, $msgAceptada);
        break;

    case 'pago_pendiente':
        $transactionModel->marcarPagado($transaccionId);
        $messageModel->enviarMensajeSistema(
            $chatId,
            "El comprador indica que ha realizado el pago. El vendedor debe confirmarlo antes de enviar."
        );
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

        $stmtProd = $conn->prepare("SELECT titulo FROM Productos WHERE id = :pid");
        $stmtProd->execute([':pid' => $productoId]);
        $productoTitulo = $stmtProd->fetchColumn() ?: 'producto';

        foreach ($usuarios as $u) {
            $esCompradorEmail = ($u['id'] == $transaccion['comprador_id']);
            $rol  = $esCompradorEmail ? 'comprador' : 'vendedor';
            $msg  = $esCompradorEmail
                ? "Has confirmado la recepción de <strong>{$productoTitulo}</strong>. ¡Gracias por usar MercApp!"
                : "El comprador ha confirmado la entrega de <strong>{$productoTitulo}</strong>. La transacción está completada.";

            $html = "<h2 style='color:#0d6efd'>MercApp — Transacción completada</h2>"
                  . "<p>Hola <strong>{$u['nombre']}</strong>,</p>"
                  . "<p>{$msg}</p>"
                  . "<p style='color:#6c757d;font-size:.9em'>Este es un correo automático. No respondas a este mensaje.</p>";

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
    'aceptada'       => 'El comprador ha aceptado la transacción.',
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
        $mensajesEstado[$nuevoEstado] . " ({$tituloProducto})"
    );
}

header("Location: {$BASE}/public/views/chat.php?id={$chatId}");
exit;
