<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Product.php';

$db = new Database();
$conn = $db->getConnection();

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($productId <= 0) {
    echo "Producto no válido.";
    exit;
}

$productModel = new Product($conn);
$producto = $productModel->getById($productId);

if (!$producto) {
    echo "Producto no encontrado.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($producto['titulo']) ?></title>

    <link rel="icon" href="../ico/logo_sinfondo.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/detail_product.css">



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

</head>

<body>

    <?php
    $showSearch = true;
    include("navbar.php");
    ?>

    <main>
        <div class="product-detail">

            <!-- GALERÍA -->
            <div class="product-gallery">

                <?php if (!empty($producto['imagenes'])): ?>
                    <img src="/MercApp/<?= htmlspecialchars($producto['imagenes'][0]['url']) ?>" class="main-img" alt="Imagen del producto">
                <?php else: ?>
                    <img src="/MercApp/public/img/default.jpg" class="main-img" alt="Imagen por defecto">
                <?php endif; ?>

                <div class="thumbs">
                    <?php foreach ($producto['imagenes'] as $img): ?>
                        <img src="/MercApp/<?= htmlspecialchars($img['url']) ?>" class="thumb" alt="Miniatura">
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- INFORMACIÓN -->
            <div class="product-info">
                <h1><?= htmlspecialchars($producto['titulo']) ?></h1>
                <p class="product-location">Ubicación: <?= htmlspecialchars($producto['ubicacion']) ?></p>

                <div class="price-block">
                    <span class="price-current"><?= number_format($producto['precio'], 2) ?> €</span>

                    <?php if (!empty($producto['precio_original']) && $producto['precio_original'] > $producto['precio']): ?>
                        <span class="price-original"><?= number_format($producto['precio_original'], 2) ?> €</span>
                        <span class="price-discount">
                            -<?= round(100 * ($producto['precio_original'] - $producto['precio']) / $producto['precio_original']) ?>%
                        </span>
                    <?php endif; ?>
                </div>

                <form method="post" action="cart.php">
                    <input type="hidden" name="product_id" value="<?= $producto['id'] ?>">
                    <button type="submit" class="buy-button">Comprar</button>
                </form>

                <div class="chat-button">
                    <a href="/MercApp/controllers/chat_start.php?producto_id=<?= $producto["id"] ?>">
                        ¿Tienes dudas? Chatea con el vendedor
                    </a>
                </div>

                <div class="extras">
                    <p>📦 Envío a acordar con el vendedor</p>
                    <p>🛡️ Compra segura</p>
                </div>
            </div>

            <!-- DESCRIPCIÓN -->
            <p class="description">
                <?= nl2br(htmlspecialchars($producto['descripcion'])) ?>
            </p>

        </div>
    </main>

    <footer>
        <?php include __DIR__ . '/footer.php'; ?>
    </footer>

</body>

</html>