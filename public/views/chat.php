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

$db = new Database();
$conn = $db->getConnection();

$chatModel = new Chat($conn);
$mensajeModel = new Message($conn);

// Seguridad
if (!$chatModel->userBelongsToChat($chatId, $usuarioActual)) {
    die("No tienes acceso a este chat");
}

// Marcar mensajes como leídos
$mensajeModel->markAsRead($chatId, $usuarioActual);


// Enviar mensaje
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $contenido = trim($_POST["mensaje"] ?? "");

    if ($contenido !== "") {
        $mensajeModel->send($chatId, $usuarioActual, $contenido);
    }

    header("Location: chat.php?id=" . $chatId);
    exit;
}

// Obtener mensajes
$mensajes = $mensajeModel->getByChat($chatId);
$chat = $chatModel->getById($chatId);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo $_SESSION["name"] ?? 'Usuario' ?></title>

    <!-- Favicon -->
    <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">

    <!-- Bootstrap CSS y JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">


    <!-- CSS personalizado -->
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/homeStyle.css">

    <!-- JS de tema -->
    <script src="../js/theme.js" defer></script>
</head>

<body>

    <!-- Navbar con opciones de perfil -->
    <?php
    $showSearch = false;
    include("navbar.php");
    ?>

    <br>
    <!-- titulo -->
    <div class="container py-4">
        <h3 class="mb-3">
            Chat sobre: <?= htmlspecialchars($chat["producto_titulo"] ?? "Producto") ?>
        </h3>
        <!-- foto -->
        <div class="d-flex align-items-center mb-3">
            <?php
            $img = $chat["producto_imagen"]
                ? "../../" . htmlspecialchars($chat["producto_imagen"])
                : "../../uploads/products/default.jpg";
            ?>
            <img src="<?= $img ?>" alt="Producto" class="me-3 rounded"
                style="width: 80px; height: 80px; object-fit: cover;">


            <div>
                <h5 class="mb-0">
                    <?= htmlspecialchars($chat["producto_titulo"]) ?>
                </h5>
                <a href="detail_product.php?id=<?= $chat["producto_id"] ?>" class="small text-primary">
                    Ver producto
                </a>
            </div>
        </div>

        <!-- chat -->
        <div class="card mb-3 no-hover">
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($mensajes as $msg): ?>
                    <div class="mb-2 <?= $msg["usuario_id"] == $usuarioActual ? 'text-end' : 'text-start' ?>">

                        <!-- Nombre y fecha -->
                        <div class="small text-muted">
                            <?= htmlspecialchars($msg["sender_name"]) ?> · <?= $msg["fecha_envio"] ?>
                        </div>

                        <!-- Burbuja del mensaje -->
                        <div class="d-inline-block px-3 py-2 rounded 
                    <?= $msg["usuario_id"] == $usuarioActual ? 'bg-primary text-white' : 'bg-secondary text-white' ?>">
                            <?= nl2br(htmlspecialchars($msg["contenido"])) ?>
                        </div>

                        <!-- Indicador de leído SOLO para mensajes del usuario actual -->
                        <?php if ($msg["usuario_id"] == $usuarioActual): ?>
                            <div class="small text-muted mt-1">
                                <?= $msg["leido"] ? "✓✓ Leído" : "✓ Enviado" ?>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="POST" class="d-flex gap-2">
            <textarea name="mensaje" class="form-control" rows="2" placeholder="Escribe un mensaje..."></textarea>
            <button class="btn btn-primary">Enviar</button>
        </form>
    </div>
    <!-- Volver al producto -->
    <div class="mt-3">
        <a href="detail_product.php?id=<?= $chat["producto_id"] ?>" class="btn btn-outline-secondary">
            Volver al producto
        </a>
    </div>


    <!-- footer de la pagina -->
    <footer>
        <?php include __DIR__ . '/footer.php'; ?>
    </footer>

</body>

</html>