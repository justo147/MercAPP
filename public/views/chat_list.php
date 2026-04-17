<?php
require_once __DIR__ . '/../../controllers/check_auth.php';
include   __DIR__ . '/../../controllers/chat_list.php';
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

    <?php
    $showSearch = false;
    include("navbar.php");
    ?>

    <div class="container py-4" style="max-width: 680px;">

        <h4 class="mb-4 fw-bold">
            <i class="bi bi-chat-dots me-2 text-primary"></i>Mis chats
        </h4>

        <?php if (empty($chats)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-chat-square-dots fs-1 d-block mb-3 opacity-25"></i>
                <p class="mb-0">Todavía no tienes ningún chat activo.</p>
                <a href="home.php" class="btn btn-outline-primary mt-3">
                    <i class="bi bi-search me-1"></i> Explorar productos
                </a>
            </div>
        <?php else: ?>
            <div class="list-group shadow-sm">
            <?php foreach ($chats as $chat): ?>
                <?php
                // Imagen con fallback seguro
                $imgSrc = !empty($chat["producto_imagen"])
                    ? $BASE . "/" . htmlspecialchars($chat["producto_imagen"])
                    : $BASE . "/public/img/default.jpg";

                // Último mensaje: truncar y limpiar prefijo sistema
                $ultimoMsg = $chat["ultimo_mensaje"] ?? "Sin mensajes";
                $ultimoMsg = preg_replace('/^\[SISTEMA\]\s*/i', '⚙ ', $ultimoMsg);
                if (mb_strlen($ultimoMsg) > 60) {
                    $ultimoMsg = mb_substr($ultimoMsg, 0, 60) . '…';
                }

                // Fecha relativa
                $fecha = '';
                if (!empty($chat["fecha_ultimo_mensaje"])) {
                    $ts   = strtotime($chat["fecha_ultimo_mensaje"]);
                    $diff = time() - $ts;
                    if ($diff < 60)          $fecha = 'Ahora';
                    elseif ($diff < 3600)    $fecha = floor($diff / 60) . ' min';
                    elseif ($diff < 86400)   $fecha = floor($diff / 3600) . ' h';
                    elseif ($diff < 604800)  $fecha = floor($diff / 86400) . ' d';
                    else                     $fecha = date('d/m/Y', $ts);
                }
                ?>
                <a href="<?= $BASE ?>/public/views/chat.php?id=<?= intval($chat["chat_id"]) ?>"
                   class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">

                    <img src="<?= $imgSrc ?>" alt="Producto"
                         class="rounded flex-shrink-0"
                         style="width:56px;height:56px;object-fit:cover;"
                         onerror="this.onerror=null;this.src='<?= $BASE ?>/public/img/default.jpg'">

                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-truncate">
                            <?= htmlspecialchars($chat["producto_titulo"] ?? "Producto") ?>
                        </div>
                        <div class="small text-muted text-truncate">
                            <?= htmlspecialchars($ultimoMsg) ?>
                        </div>
                    </div>

                    <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                        <?php if ($fecha): ?>
                            <span class="text-muted" style="font-size:.72rem;"><?= $fecha ?></span>
                        <?php endif; ?>
                        <?php if ($chat["no_leidos"] > 0): ?>
                            <span class="badge rounded-pill bg-danger">
                                <?= intval($chat["no_leidos"]) ?>
                            </span>
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
