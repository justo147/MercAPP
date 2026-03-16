<?php
require_once __DIR__ . '/../../controllers/check_auth.php';
include __DIR__ . '/../../controllers/chat_list.php';
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

</head>

<body>

    <!-- Navbar con opciones de perfil -->
    <?php
    $showSearch = false;
    include("navbar.php");
    ?>

    <div class="container py-4">
        <h3 class="mb-4">Mis chats</h3>

        <?php if (empty($chats)): ?>
            <div class="alert alert-info">No tienes chats activos.</div>
        <?php endif; ?>

        <?php foreach ($chats as $chat): ?>
            <a href="/MercApp/public/views/chat.php?id=<?= $chat["chat_id"] ?>" class="text-decoration-none text-dark">
                <div class="card mb-3 p-2">
                    <div class="d-flex align-items-center">

                        <!-- Imagen del producto -->
                        <img src="/MercApp/<?= htmlspecialchars($chat["producto_imagen"]) ?>" class="rounded me-3"
                            style="width: 70px; height: 70px; object-fit: cover;">

                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?= htmlspecialchars($chat["producto_titulo"]) ?></h6>

                            <div class="text-muted small">
                                <?= htmlspecialchars($chat["ultimo_mensaje"] ?? "Sin mensajes") ?>
                            </div>
                        </div>

                        <!-- Mensajes sin leer -->
                        <?php if ($chat["no_leidos"] > 0): ?>
                            <span class="badge bg-danger ms-2">
                                <?= $chat["no_leidos"] ?>
                            </span>
                        <?php endif; ?>

                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>


    <!-- footer de la pagina -->
    <footer>
        <?php include __DIR__ . '/footer.php'; ?>
    </footer>
</body>

</html>