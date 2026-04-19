<?php
require_once __DIR__ . '/../../controllers/check_auth.php';
include   __DIR__ . '/../../controllers/chat_list.php';

// Etiquetas de filtros
$filtros = [
    ''                => ['Todos',             'bi-chat-dots'],
    'no_leidos'       => ['No leídos',         'bi-envelope-exclamation'],
    'con_transaccion' => ['Con transacción',   'bi-arrow-left-right'],
    'sin_transaccion' => ['Sin transacción',   'bi-chat-text'],
    'abierto'         => ['Transacción activa','bi-hourglass-split'],
    'cerrado'         => ['Cerrada/Entregada', 'bi-check-circle'],
];

// Badges de estado de transacción
$badgeEstado = [
    'pendiente'      => ['warning', 'Pendiente'],
    'aceptada'       => ['info',    'Aceptada'],
    'pago_pendiente' => ['primary', 'Pago pendiente'],
    'enviado'        => ['primary', 'Enviado'],
    'entregado'      => ['success', 'Entregado'],
    'cancelada'      => ['secondary','Cancelada'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis chats — MercApp</title>

    <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/homeStyle.css">
</head>
<body>

    <?php $showSearch = false; include("navbar.php"); ?>

    <div class="container py-4" style="max-width: 720px;">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold mb-0">
                <i class="bi bi-chat-dots me-2 text-primary"></i>Mis chats
            </h4>
            <?php $totalNoLeidos = array_sum(array_column($chats, 'no_leidos')); ?>
            <?php if ($totalNoLeidos > 0): ?>
                <span class="badge bg-danger rounded-pill"><?= $totalNoLeidos ?> sin leer</span>
            <?php endif; ?>
        </div>

        <!-- Filtros -->
        <div class="d-flex flex-wrap gap-2 mb-4">
            <?php foreach ($filtros as $val => [$label, $icon]): ?>
                <?php $activo = ($filtroActual === $val); ?>
                <a href="?filtro=<?= htmlspecialchars($val) ?>"
                   class="btn btn-sm <?= $activo ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($chats)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-chat-square-dots fs-1 d-block mb-3 opacity-25"></i>
                <?php if ($filtroActual): ?>
                    <p class="mb-0">No hay chats con este filtro.</p>
                    <a href="?" class="btn btn-outline-secondary mt-3 btn-sm">Ver todos</a>
                <?php else: ?>
                    <p class="mb-0">Todavía no tienes ningún chat activo.</p>
                    <a href="home.php" class="btn btn-outline-primary mt-3">
                        <i class="bi bi-search me-1"></i> Explorar productos
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="list-group shadow-sm">
            <?php foreach ($chats as $chat):
                // Imagen del producto
                $imgProd = !empty($chat["producto_imagen"])
                    ? $BASE . "/" . htmlspecialchars($chat["producto_imagen"])
                    : $BASE . "/public/img/default.jpg";

                // Avatar del otro usuario
                $otraFoto = !empty($chat["otro_foto"])
                    ? $BASE . "/" . htmlspecialchars($chat["otro_foto"])
                    : null;

                // Último mensaje
                $ultimoMsg = $chat["ultimo_mensaje"] ?? "Sin mensajes";
                $ultimoMsg = preg_replace('/^\[SISTEMA\]\s*/i', '⚙ ', $ultimoMsg);
                if (mb_strlen($ultimoMsg) > 65) $ultimoMsg = mb_substr($ultimoMsg, 0, 65) . '…';

                // Fecha relativa
                $fecha = '';
                if (!empty($chat["fecha_ultimo_mensaje"])) {
                    $ts   = strtotime($chat["fecha_ultimo_mensaje"]);
                    $diff = time() - $ts;
                    if ($diff < 60)         $fecha = 'Ahora';
                    elseif ($diff < 3600)   $fecha = floor($diff/60) . ' min';
                    elseif ($diff < 86400)  $fecha = floor($diff/3600) . ' h';
                    elseif ($diff < 604800) $fecha = floor($diff/86400) . ' d';
                    else                    $fecha = date('d/m/Y', $ts);
                }

                $noLeidos = intval($chat["no_leidos"]);
                $estado   = $chat["transaccion_estado"] ?? null;
                [$badgeCls, $badgeTxt] = $badgeEstado[$estado] ?? ['', ''];
            ?>
                <a href="<?= $BASE ?>/public/views/chat.php?id=<?= intval($chat["chat_id"]) ?>"
                   class="list-group-item list-group-item-action py-3 <?= $noLeidos > 0 ? 'border-start border-primary border-3' : '' ?>">

                    <div class="d-flex align-items-center gap-3">

                        <!-- Imagen producto -->
                        <img src="<?= $imgProd ?>" alt="Producto"
                             class="rounded flex-shrink-0"
                             style="width:52px;height:52px;object-fit:cover;"
                             onerror="this.onerror=null;this.src='<?= $BASE ?>/public/img/default.jpg'">

                        <!-- Contenido central -->
                        <div class="flex-grow-1 min-w-0">
                            <!-- Fila 1: título + fecha -->
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="fw-semibold text-truncate" style="max-width:300px;">
                                    <?= htmlspecialchars($chat["producto_titulo"] ?? "Producto") ?>
                                </span>
                                <span class="text-muted flex-shrink-0 ms-2" style="font-size:.72rem;"><?= $fecha ?></span>
                            </div>

                            <!-- Fila 2: otro usuario + último mensaje -->
                            <div class="d-flex align-items-center gap-1 mt-1">
                                <?php if ($otraFoto): ?>
                                    <img src="<?= $otraFoto ?>" alt=""
                                         class="rounded-circle flex-shrink-0"
                                         style="width:18px;height:18px;object-fit:cover;"
                                         onerror="this.style.display='none'">
                                <?php else: ?>
                                    <i class="bi bi-person-circle text-secondary flex-shrink-0" style="font-size:.9rem;"></i>
                                <?php endif; ?>
                                <span class="small text-muted me-1 flex-shrink-0">
                                    <?= htmlspecialchars($chat["otro_nombre"] ?? "") ?>:
                                </span>
                                <span class="small text-muted text-truncate">
                                    <?= htmlspecialchars($ultimoMsg) ?>
                                </span>
                            </div>

                            <!-- Fila 3: badge transacción -->
                            <?php if ($estado): ?>
                                <div class="mt-1">
                                    <span class="badge bg-<?= $badgeCls ?> text-<?= $badgeCls === 'warning' ? 'dark' : 'white' ?>"
                                          style="font-size:.68rem;">
                                        <i class="bi bi-arrow-left-right me-1"></i><?= $badgeTxt ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Badge no leídos -->
                        <?php if ($noLeidos > 0): ?>
                            <span class="badge rounded-pill bg-danger flex-shrink-0"><?= $noLeidos ?></span>
                        <?php endif; ?>

                    </div>
                </a>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <footer>
        <?php include __DIR__ . '/footer.php'; ?>
    </footer>

</body>
</html>
