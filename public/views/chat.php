<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Chat no válido");
}

$chatId = intval($_GET["id"]);
$usuarioActual = intval($_SESSION["user_id"]);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Chat.php';
require_once __DIR__ . '/../../models/Message.php';
require_once __DIR__ . '/../../models/Transaction.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Rating.php';

$db = new Database();
$conn = $db->getConnection();

$chatModel = new Chat($conn);
$mensajeModel = new Message($conn);
$transactionModel = new Transaction($conn);
$productoModel = new Product($conn);
$ratingModel   = new Rating($conn);

// Seguridad
if (!$chatModel->userBelongsToChat($chatId, $usuarioActual)) {
    die("No tienes acceso a este chat");
}

// Info del chat
$chat = $chatModel->getById($chatId);

$transaccion = null;

if (!empty($chat["transaccion_id"])) {
    $transaccion = $transactionModel->getById($chat["transaccion_id"]);
}

// ¿Debe abrirse el modal de valoración?
// Condición: transacción entregada + el usuario aún no ha valorado
$mostrarModalValoracion = false;
if (
    $transaccion &&
    $transaccion['estado'] === 'entregado' &&
    !$ratingModel->hasRated($transaccion['id'], $usuarioActual)
) {
    $mostrarModalValoracion = true;
}


// Roles
$esVendedor = ($usuarioActual == $chat["usuario_vendedor"]);
$esComprador = ($usuarioActual == $chat["usuario_comprador"]);

// Solo marcar mensajes como leídos si es una petición GET (el usuario abre el chat)
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $mensajeModel->markAsRead($chatId, $usuarioActual);
    $mensajeModel->markSystemAsRead($chatId);
}


// Enviar mensaje normal
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mensaje"])) {
    $contenido = trim($_POST["mensaje"] ?? "");

    if ($contenido !== "") {
        $mensajeModel->send($chatId, $usuarioActual, $contenido);
    }

    header("Location: chat.php?id=" . $chatId);
    exit;
}

// Enviar valoración
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["valoracion"])) {
    $transaccionId = intval($_POST["transaccion_id"] ?? 0);
    $fiabilidad    = intval($_POST["fiabilidad"]     ?? 0);
    $comunicacion  = intval($_POST["comunicacion"]   ?? 0);
    $puntualidad   = intval($_POST["puntualidad"]    ?? 0);
    $comentario    = trim($_POST["comentario"]       ?? '');

    $transaccionVal = $transactionModel->getById($transaccionId);

    if (
        $transaccionVal &&
        $transaccionVal['estado'] === 'entregado' &&
        !$ratingModel->hasRated($transaccionId, $usuarioActual) &&
        $fiabilidad  >= 1 && $fiabilidad  <= 5 &&
        $comunicacion >= 1 && $comunicacion <= 5 &&
        $puntualidad >= 1 && $puntualidad  <= 5
    ) {
        $esCompradorVal  = ($usuarioActual == $transaccionVal['comprador_id']);
        $usuarioValorado = $esCompradorVal
            ? $transaccionVal['vendedor_id']
            : $transaccionVal['comprador_id'];
        $puntuacion = round(($fiabilidad + $comunicacion + $puntualidad) / 3);

        $ok = $ratingModel->create(
            $transaccionId,
            $usuarioActual,
            $usuarioValorado,
            $puntuacion,
            $comentario ?: null,
            $fiabilidad,
            $comunicacion,
            $puntualidad
        );

        echo json_encode(['ok' => $ok]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la valoración']);
    }
    exit;
}

// Obtener mensajes
$mensajes = $mensajeModel->getByChat($chatId);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>

    <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/homeStyle.css">

    <script src="../js/theme.js" defer></script>
</head>

<body>

    <?php
    $showSearch = false;
    include("navbar.php");
    ?>

    <div class="container py-4">

        <h3 class="mb-3">
            Chat sobre: <?= htmlspecialchars($chat["producto_titulo"] ?? "Producto") ?>
        </h3>

        <!-- Producto -->
        <div class="d-flex align-items-center mb-3">
            <?php
            $img = $chat["producto_imagen"]
                ? "../../" . htmlspecialchars($chat["producto_imagen"])
                : "../../uploads/products/default.jpg";
            ?>
            <img src="<?= $img ?>" alt="Producto" class="me-3 rounded"
                style="width: 80px; height: 80px; object-fit: cover;">

            <div>
                <h5 class="mb-0"><?= htmlspecialchars($chat["producto_titulo"]) ?></h5>
                <a href="detail_product.php?id=<?= $chat["producto_id"] ?>" class="small text-primary">
                    Ver producto
                </a>
            </div>
        </div>

        <!-- Timeline -->
        <?php if ($transaccion): ?>

            <?php
            $estado = $transaccion["estado"];

            // Función para marcar estado activo
            function stepActive($current, $state)
            {
                return $current === $state ? "text-primary fw-bold" : "text-muted";
            }

            // Función para icono activo/inactivo
            function iconActive($current, $state)
            {
                return $current === $state ? "text-primary" : "text-muted";
            }
            ?>

            <div class="card mb-3 no-hover">
                <div class="card-body">

                    <div class="d-flex justify-content-between text-center">

                        <!-- Pendiente -->
                        <div class="flex-fill">
                            <i class="bi bi-hourglass-split fs-3 <?= iconActive('pendiente', $estado) ?>"></i>
                            <div class="<?= stepActive('pendiente', $estado) ?>">Pendiente</div>
                        </div>

                        <!-- Aceptada -->
                        <div class="flex-fill">
                            <i class="bi bi-hand-thumbs-up fs-3 <?= iconActive('aceptada', $estado) ?>"></i>
                            <div class="<?= stepActive('aceptada', $estado) ?>">Aceptada</div>
                        </div>

                        <!-- Enviado -->
                        <div class="flex-fill">
                            <i class="bi bi-truck fs-3 <?= iconActive('enviado', $estado) ?>"></i>
                            <div class="<?= stepActive('enviado', $estado) ?>">Enviado</div>
                        </div>

                        <!-- Entregado -->
                        <div class="flex-fill">
                            <i class="bi bi-check-circle fs-3 <?= iconActive('entregado', $estado) ?>"></i>
                            <div class="<?= stepActive('entregado', $estado) ?>">Entregado</div>
                        </div>

                        <!-- Cancelada -->
                        <div class="flex-fill">
                            <i class="bi bi-x-circle fs-3 <?= iconActive('cancelada', $estado) ?>"></i>
                            <div class="<?= stepActive('cancelada', $estado) ?>">Cancelada</div>
                        </div>

                    </div>

                </div>
            </div>

        <?php endif; ?>


        <!-- Avisos inteligentes -->
        <?php if ($transaccion): ?>

            <?php if ($estado === "pendiente"): ?>
                <div class="alert alert-warning text-center">
                    Esperando que el comprador acepte la transacción.
                </div>

            <?php elseif ($estado === "aceptada"): ?>
                <?php if ($esVendedor): ?>
                    <div class="alert alert-info text-center">
                        Debes marcar el producto como enviado.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        El vendedor debe marcar el producto como enviado.
                    </div>
                <?php endif; ?>

            <?php elseif ($estado === "enviado"): ?>
                <?php if ($esComprador): ?>
                    <div class="alert alert-success text-center">
                        Marca como entregado cuando recibas el producto.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success text-center">
                        Esperando que el comprador confirme la entrega.
                    </div>
                <?php endif; ?>

            <?php elseif ($estado === "entregado"): ?>
                <div class="alert alert-success text-center">
                    La transacción ha sido completada con éxito.
                </div>

            <?php elseif ($estado === "cancelada"): ?>
                <div class="alert alert-danger text-center">
                    La transacción ha sido cancelada.
                </div>
            <?php endif; ?>

        <?php endif; ?>


        <!-- ESTADO DE TRANSACCIÓN -->
        <?php if ($transaccion): ?>
            <div class="alert alert-info text-center">
                Estado de la transacción:
                <strong><?= htmlspecialchars($transaccion["estado"]) ?></strong>
            </div>

            <?php $estado = $transaccion["estado"]; ?>

            <!-- BOTONES SEGÚN ESTADO -->
            <form action="<?= $BASE ?>/controllers/chat_update_transaction.php" method="POST" class="mb-3">
                <input type="hidden" name="transaccion_id" value="<?= $transaccion["id"] ?>">
                <input type="hidden" name="chat_id" value="<?= $chatId ?>">

                <?php if ($estado === "pendiente"): ?>

                    <?php if ($esComprador): ?>
                        <button name="estado" value="aceptada" class="btn btn-success w-100 mb-2">
                            Aceptar transacción
                        </button>
                    <?php endif; ?>

                    <button name="estado" value="cancelada" class="btn btn-danger w-100">
                        Cancelar transacción
                    </button>

                <?php elseif ($estado === "aceptada"): ?>

                    <?php if ($esVendedor): ?>
                        <button name="estado" value="enviado" class="btn btn-primary w-100 mb-2">
                            Marcar como enviado
                        </button>
                    <?php endif; ?>

                    <button name="estado" value="cancelada" class="btn btn-danger w-100">
                        Cancelar transacción
                    </button>

                <?php elseif ($estado === "enviado"): ?>

                    <?php if ($esComprador): ?>
                        <button name="estado" value="entregado" class="btn btn-success w-100 mb-2">
                            Marcar como entregado
                        </button>
                    <?php endif; ?>

                    <button name="estado" value="cancelada" class="btn btn-danger w-100">
                        Cancelar transacción
                    </button>

                <?php endif; ?>
            </form>

        <?php endif; ?>

        <!-- BOTÓN INICIAR TRANSACCIÓN -->
        <?php if ($esVendedor && !$transaccion): ?>
            <form action="<?= $BASE ?>/controllers/chat_start_transaction.php" method="POST" class="mb-3">
                <input type="hidden" name="chat_id" value="<?= $chatId ?>">
                <button class="btn btn-success w-100">
                    Iniciar transacción
                </button>
            </form>
        <?php endif; ?>

        <!-- CHAT -->
        <div class="card mb-3 no-hover">
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($mensajes as $msg): ?>
                    <div class="mb-2 <?= $msg["usuario_id"] == $usuarioActual ? 'text-end' : 'text-start' ?>">

                        <div class="small text-muted">
                            <?= $msg["usuario_id"] === null ? "Sistema" : htmlspecialchars($msg["sender_name"]) ?>
                            · <?= $msg["fecha_envio"] ?>
                        </div>

                        <div
                            class="d-inline-block px-3 py-2 rounded 
                        <?= $msg["usuario_id"] == $usuarioActual ? 'bg-primary text-white' : 'bg-secondary text-white' ?>">
                            <?= nl2br(htmlspecialchars($msg["contenido"])) ?>
                        </div>

                        <?php if ($msg["usuario_id"] == $usuarioActual): ?>
                            <div class="small text-muted mt-1">
                                <?= $msg["leido"] ? "✓✓ Leído" : "✓ Enviado" ?>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- BLOQUEO DEL CHAT -->
        <?php if ($transaccion && in_array($transaccion["estado"], ["entregado", "cancelada"])): ?>

            <div class="alert alert-secondary text-center">
                La transacción ha finalizado. Este chat está cerrado.
            </div>

        <?php else: ?>

            <form method="POST" class="d-flex gap-2">
                <textarea name="mensaje" class="form-control" rows="2" placeholder="Escribe un mensaje..."></textarea>
                <button class="btn btn-primary">Enviar</button>
            </form>

        <?php endif; ?>

        <div class="mt-3">
            <a href="chat_list.php" class="btn btn-outline-secondary">
                Volver a la lista de Chats
            </a>
        </div>

    </div>

    <footer>
        <?php include __DIR__ . '/footer.php'; ?>
    </footer>

    <!-- =====================================================
         MODAL DE VALORACIÓN
         Se abre automáticamente cuando la transacción está
         entregada y el usuario aún no ha valorado.
    ====================================================== -->
    <?php if ($mostrarModalValoracion && $transaccion): ?>
    <div class="modal fade" id="modalValoracion" tabindex="-1"
         aria-labelledby="modalValoracionLabel" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalValoracionLabel">
                        <i class="bi bi-star-fill text-warning me-2"></i>
                        Valora tu experiencia
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <p class="text-muted small mb-4">
                        Tu opinión ayuda a construir una comunidad de confianza.
                        Puntúa del 1 (peor) al 5 (mejor) cada criterio.
                    </p>

                    <!-- Fiabilidad -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-shield-check me-1"></i> Fiabilidad
                        </label>
                        <div class="star-group d-flex gap-2 fs-3" data-field="fiabilidad">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star star-btn" data-value="<?= $i ?>"
                                   style="cursor:pointer; color: #ccc;"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="fiabilidad" id="inp-fiabilidad" value="0">
                    </div>

                    <!-- Comunicación -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-chat-dots me-1"></i> Comunicación
                        </label>
                        <div class="star-group d-flex gap-2 fs-3" data-field="comunicacion">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star star-btn" data-value="<?= $i ?>"
                                   style="cursor:pointer; color: #ccc;"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="comunicacion" id="inp-comunicacion" value="0">
                    </div>

                    <!-- Puntualidad -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-clock me-1"></i> Puntualidad
                        </label>
                        <div class="star-group d-flex gap-2 fs-3" data-field="puntualidad">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star star-btn" data-value="<?= $i ?>"
                                   style="cursor:pointer; color: #ccc;"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="puntualidad" id="inp-puntualidad" value="0">
                    </div>

                    <!-- Comentario -->
                    <div class="mb-3">
                        <label for="comentario-val" class="form-label fw-semibold">
                            <i class="bi bi-pencil me-1"></i> Comentario <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <textarea id="comentario-val" class="form-control" rows="3"
                                  maxlength="500"
                                  placeholder="Cuéntanos cómo fue la experiencia..."></textarea>
                    </div>

                    <!-- Resumen media -->
                    <div id="resumen-media" class="alert alert-light text-center d-none">
                        Puntuación media: <strong id="media-val">—</strong>
                        <span class="text-warning" id="media-stars"></span>
                    </div>

                    <!-- Mensajes de feedback -->
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
        data-transaccion-id="<?= (int) $transaccion['id'] ?>"
        data-chat-id="<?= (int) $chatId ?>"
        defer
    ></script>
    <?php endif; ?>

</body>

</html>