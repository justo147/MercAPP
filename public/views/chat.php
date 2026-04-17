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

$db = new Database();
$conn = $db->getConnection();

$chatModel        = new Chat($conn);
$mensajeModel     = new Message($conn);
$transactionModel = new Transaction($conn);
$productoModel    = new Product($conn);
$ratingModel      = new Rating($conn);
$reportModel      = new Report($conn);

// Verificar acceso
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

// Solo el comprador puede valorar al vendedor tras la entrega
$mostrarModalValoracion = false;
if (
    $esComprador &&
    $transaccion &&
    $transaccion['estado'] === 'entregado' &&
    !$ratingModel->hasRated($transaccion['id'], $usuarioActual)
) {
    $mostrarModalValoracion = true;
}

// El otro usuario del chat (para el reporte)
$otroUsuarioId = $esVendedor
    ? $chat["usuario_comprador"]
    : $chat["usuario_vendedor"];

// Marcar mensajes como leídos al abrir el chat
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $mensajeModel->markAsRead($chatId, $usuarioActual);
    $mensajeModel->markSystemAsRead($chatId);
}

// ── Enviar mensaje ────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mensaje"])) {
    $contenido = trim($_POST["mensaje"] ?? "");
    if ($contenido !== "") {
        $mensajeModel->send($chatId, $usuarioActual, $contenido);
    }
    header("Location: chat.php?id=" . $chatId);
    exit;
}

// ── Enviar valoración (AJAX) — solo el comprador puede valorar ───
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["valoracion"])) {
    header("Content-Type: application/json");

    // Rechazar si quien envía no es el comprador del chat
    if (!$esComprador) {
        echo json_encode(['ok' => false, 'error' => 'Solo el comprador puede enviar una valoración.']);
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

// ── Enviar reporte ────────────────────────────────────────────
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

// Helper inline — devuelve CSS según el estado del paso
function stepClass(string $current, string $step): string
{
    return $current === $step ? "text-primary fw-bold" : "text-muted";
}
function iconClass(string $current, string $step): string
{
    $done = ['aceptada', 'enviado', 'entregado'];
    $idx  = array_search($step, $done);
    $idxC = array_search($current, $done);
    if ($current === $step) return "text-primary";
    if ($idx !== false && $idxC !== false && $idxC > $idx) return "text-success";
    return "text-muted";
}
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

    <style>
        .msg-sistema {
            text-align: center;
            margin: .75rem 0;
        }
        .msg-sistema .bubble {
            display: inline-block;
            background: #f0f0f0;
            color: #555;
            border-radius: 20px;
            padding: .3rem .9rem;
            font-size: .8rem;
            font-style: italic;
        }
        .dark-mode .msg-sistema .bubble {
            background: #2a2a2a;
            color: #aaa;
        }
        .bubble-out {
            background: #0d6efd;
            color: #fff;
            border-radius: 18px 18px 4px 18px;
        }
        .bubble-in {
            background: #e9ecef;
            color: #212529;
            border-radius: 18px 18px 18px 4px;
        }
        .dark-mode .bubble-in {
            background: #2d2d2d;
            color: #e0e0e0;
        }
        .chat-box {
            max-height: 420px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }
        .step-bar .step { flex: 1; text-align: center; }
        .step-bar .step-icon { font-size: 1.5rem; }
    </style>
</head>

<body>

    <?php
    $showSearch = false;
    include("navbar.php");
    ?>

    <div class="container py-4" style="max-width: 780px;">

        <?php if (isset($_GET['reporte']) && $_GET['reporte'] === 'ok'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-1"></i> Reporte enviado. Lo revisaremos próximamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Cabecera del chat -->
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

            <!-- Botón reportar -->
            <?php if (!empty($chat["producto_id"])): ?>
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalReporte">
                <i class="bi bi-flag me-1"></i> Reportar
            </button>
            <?php endif; ?>
        </div>

        <!-- ── Timeline de transacción ──────────────────────── -->
        <?php if ($transaccion): ?>
            <?php $estado = $transaccion["estado"]; ?>

            <div class="card mb-3 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex step-bar">

                        <div class="step">
                            <div class="step-icon <?= $estado === 'cancelada' ? 'text-danger' : ($estado === 'pendiente' ? 'text-warning' : 'text-success') ?>">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <div class="small <?= stepClass($estado,'pendiente') ?>">Pendiente</div>
                        </div>

                        <div class="step">
                            <div class="step-icon <?= iconClass($estado,'aceptada') ?>">
                                <i class="bi bi-hand-thumbs-up"></i>
                            </div>
                            <div class="small <?= stepClass($estado,'aceptada') ?>">Aceptada</div>
                        </div>

                        <div class="step">
                            <div class="step-icon <?= iconClass($estado,'enviado') ?>">
                                <i class="bi bi-truck"></i>
                            </div>
                            <div class="small <?= stepClass($estado,'enviado') ?>">Enviado</div>
                        </div>

                        <div class="step">
                            <div class="step-icon <?= $estado === 'entregado' ? 'text-success' : 'text-muted' ?>">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="small <?= stepClass($estado,'entregado') ?>">Entregado</div>
                        </div>

                        <?php if ($estado === 'cancelada'): ?>
                        <div class="step">
                            <div class="step-icon text-danger"><i class="bi bi-x-circle"></i></div>
                            <div class="small text-danger fw-bold">Cancelada</div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- Aviso contextual -->
            <?php if ($estado === 'pendiente'): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-hourglass me-1"></i>
                    <?= $esComprador
                        ? 'El vendedor ha iniciado una transacción. Acéptala o cancélala.'
                        : 'Esperando a que el comprador acepte la transacción.' ?>
                </div>
            <?php elseif ($estado === 'aceptada'): ?>
                <div class="alert alert-info">
                    <i class="bi bi-truck me-1"></i>
                    <?= $esVendedor
                        ? 'La transacción ha sido aceptada. Marca el producto como enviado cuando lo hagas.'
                        : 'El vendedor debe marcar el producto como enviado.' ?>
                </div>
            <?php elseif ($estado === 'enviado'): ?>
                <div class="alert alert-success">
                    <i class="bi bi-box-seam me-1"></i>
                    <?= $esComprador
                        ? 'El producto está en camino. Confírmalo cuando lo recibas.'
                        : 'Esperando a que el comprador confirme la entrega.' ?>
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

            <!-- Botones de acción -->
            <?php if (!in_array($estado, ['entregado', 'cancelada'])): ?>
            <form action="<?= $BASE ?>/controllers/chat_update_transaction.php" method="POST" class="mb-3">
                <input type="hidden" name="transaccion_id" value="<?= intval($transaccion['id']) ?>">
                <input type="hidden" name="chat_id"        value="<?= $chatId ?>">

                <?php if ($estado === 'pendiente' && $esComprador): ?>
                    <button name="estado" value="aceptada" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-hand-thumbs-up me-1"></i> Aceptar transacción
                    </button>
                <?php endif; ?>

                <?php if ($estado === 'aceptada' && $esVendedor): ?>
                    <button name="estado" value="enviado" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-truck me-1"></i> Marcar como enviado
                    </button>
                <?php endif; ?>

                <?php if ($estado === 'enviado' && $esComprador): ?>
                    <button name="estado" value="entregado" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-check-circle me-1"></i> Confirmar entrega
                    </button>
                <?php endif; ?>

                <button name="estado" value="cancelada"
                        class="btn btn-outline-danger w-100"
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

        <!-- ── Mensajes ─────────────────────────────────────── -->
        <div class="card shadow-sm mb-3">
            <div class="card-body chat-box" id="chat-box">
                <?php foreach ($mensajes as $msg): ?>

                    <?php if ($msg["usuario_id"] === null): ?>
                        <!-- Mensaje del sistema -->
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

        <!-- Formulario de envío -->
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
         MODAL — REPORTAR PRODUCTO
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
                            medidas necesarias. Usar esta función de forma abusiva puede conllevar
                            la suspensión de tu cuenta.
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
                            <label for="motivo-custom" class="form-label fw-semibold">
                                Detalle adicional
                            </label>
                            <textarea id="motivo-custom" class="form-control" rows="3"
                                      maxlength="500"
                                      placeholder="Describe brevemente el problema..."></textarea>
                        </div>

                        <!-- Campo oculto que recibe el motivo final -->
                        <input type="hidden" name="reporte_motivo" id="reporte-motivo-final">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Cancelar</button>
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
        const radios  = document.querySelectorAll('input[name="reporte_motivo_preset"]');
        const custom  = document.getElementById('motivo-custom');
        const final   = document.getElementById('reporte-motivo-final');
        const btnEnv  = document.getElementById('btn-enviar-reporte');

        function actualizar() {
            const sel = document.querySelector('input[name="reporte_motivo_preset"]:checked');
            if (!sel) { btnEnv.disabled = true; return; }
            if (sel.value === 'Otro') {
                const txt = custom.value.trim();
                final.value   = txt || 'Otro';
                btnEnv.disabled = txt === '';
            } else {
                final.value   = sel.value;
                btnEnv.disabled = false;
            }
        }

        radios.forEach(r => r.addEventListener('change', actualizar));
        custom.addEventListener('input', actualizar);
    })();
    </script>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════
         MODAL — VALORACIÓN (post-entrega)
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
                    <p class="text-muted small mb-4">
                        Puntúa del 1 (peor) al 5 (mejor) cada criterio.
                    </p>

                    <?php
                    $criterios = [
                        'fiabilidad'  => ['bi-shield-check', 'Fiabilidad'],
                        'comunicacion'=> ['bi-chat-dots',    'Comunicación'],
                        'puntualidad' => ['bi-clock',        'Puntualidad'],
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
                    <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Ahora no</button>
                    <button type="button" id="btn-enviar-valoracion"
                            class="btn btn-primary" disabled>
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

    <!-- Scroll automático al final del chat -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const box = document.getElementById('chat-box');
        if (box) box.scrollTop = box.scrollHeight;
    });
    </script>

</body>
</html>
