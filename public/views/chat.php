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
require_once __DIR__ . '/../../models/Mensaje.php';

$db = new Database();
$conn = $db->getConnection();

$chatModel = new Chat($conn);
$mensajeModel = new Message($conn);

// Seguridad
if (!$chatModel->userBelongsToChat($chatId, $usuarioActual)) {
    die("No tienes acceso a este chat");
}

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
    <title>Chat - MercApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h3 class="mb-3">
        Chat sobre: <?= htmlspecialchars($chat["producto_titulo"] ?? "Producto") ?>
    </h3>

    <div class="card mb-3">
        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
            <?php foreach ($mensajes as $msg): ?>
                <div class="mb-2 <?= $msg["usuario_id"] == $usuarioActual ? 'text-end' : 'text-start' ?>">
                    <div class="small text-muted">
                        <?= htmlspecialchars($msg["nombre"]) ?> · <?= $msg["fecha_envio"] ?>
                    </div>
                    <div class="d-inline-block px-3 py-2 rounded 
                        <?= $msg["usuario_id"] == $usuarioActual ? 'bg-primary text-white' : 'bg-secondary text-white' ?>">
                        <?= nl2br(htmlspecialchars($msg["contenido"])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <form method="POST" class="d-flex gap-2">
        <textarea name="mensaje" class="form-control" rows="2" placeholder="Escribe un mensaje..."></textarea>
        <button class="btn btn-primary">Enviar</button>
    </form>
</div>

</body>
</html>
