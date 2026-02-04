<?php
// -------------------------------------------------------------
//  CARGA DE CONFIGURACIÓN Y MODELOS
// -------------------------------------------------------------
// Se incluye la configuración de la base de datos y el modelo Product
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Product.php';

// Se crea la conexión a la base de datos
$db = new Database();
$conn = $db->getConnection();

// -------------------------------------------------------------
//  VALIDACIÓN DEL ID DEL PRODUCTO
// -------------------------------------------------------------
// Se obtiene el ID del producto desde la URL (GET)
// Si no existe o no es válido, se detiene la ejecución
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($productId <= 0) {
    echo "Producto no válido.";
    exit;
}

// -------------------------------------------------------------
//  OBTENCIÓN DEL PRODUCTO PRINCIPAL
// -------------------------------------------------------------
$productModel = new Product($conn);
$producto = $productModel->getById($productId);

// Si el producto no existe, se muestra un mensaje y se detiene
if (!$producto) {
    echo "Producto no encontrado.";
    exit;
}

// -------------------------------------------------------------
//  PRODUCTOS SUGERIDOS
// -------------------------------------------------------------
// Se obtienen productos aleatorios EXCLUYENDO el actual
// Esto permite mostrar sugerencias sin repetir el producto principal
$productosSugeridos = $productModel->getRandomProducts(20, $productId);
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($producto['titulo']) ?></title>

    <!-- Icono y estilos globales -->
    <link rel="icon" href="../ico/logo_sinfondo.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos propios del proyecto -->
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/detail_product.css">

    <!-- Scripts externos -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Script del carrusel (flechas izquierda/derecha) -->
    <script src="../js/detailProduct.js" defer></script>
</head>

<body>

    <?php
    // Navbar con buscador activado
    $showSearch = true;
    include("navbar.php");
    ?>

    <main>
        <div class="product-detail">

            <!-- ---------------------------------------------------------
                 GALERÍA DE IMÁGENES DEL PRODUCTO
            ---------------------------------------------------------- -->
            <div class="product-gallery">

                <?php if (!empty($producto['imagenes'])): ?>
                    <!-- Imagen principal del producto -->
                    <img src="/MercApp/<?= htmlspecialchars($producto['imagenes'][0]['url']) ?>"
                        class="main-img"
                        alt="Imagen del producto">
                <?php else: ?>
                    <!-- Imagen por defecto si no tiene fotos -->
                    <img src="/uploads/products/default.jpg"
                        class="main-img"
                        alt="Imagen por defecto">
                <?php endif; ?>

                <!-- Miniaturas adicionales -->
                <div class="thumbs">
                    <?php foreach ($producto['imagenes'] as $img): ?>
                        <img src="/<?= htmlspecialchars($img['url']) ?>"
                            class="thumb"
                            alt="Miniatura">
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- ---------------------------------------------------------
                 INFORMACIÓN DEL PRODUCTO
            ---------------------------------------------------------- -->
            <div class="product-info">

                <!-- Título y ubicación -->
                <h1><?= htmlspecialchars($producto['titulo']) ?></h1>
                <p class="product-location">Ubicación: <?= htmlspecialchars($producto['ubicacion']) ?></p>

                <!-- Bloque de precios -->
                <div class="price-block">
                    <span class="price-current"><?= number_format($producto['precio'], 2) ?> €</span>

                    <?php if (!empty($producto['precio_original']) && $producto['precio_original'] > $producto['precio']): ?>
                        <!-- Precio original tachado -->
                        <span class="price-original"><?= number_format($producto['precio_original'], 2) ?> €</span>

                        <!-- Porcentaje de descuento -->
                        <span class="price-discount">
                            -<?= round(100 * ($producto['precio_original'] - $producto['precio']) / $producto['precio_original']) ?>%
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Botón de compra -->
                <form method="post" action="cart.php">
                    <input type="hidden" name="product_id" value="<?= $producto['id'] ?>">
                    <button type="submit" class="buy-button">Comprar</button>
                </form>

                <a href="/MercApp/controllers/chat_start.php?producto_id=<?= $producto["id"] ?>"
                    class="btn btn-primary d-flex align-items-center justify-content-center gap-2 py-2 px-4 shadow-sm">
                    <i class="bi bi-chat-dots"></i> <span>Contactar con el vendedor</span>
                </a>

                <!-- Enlace al chat con el vendedor -->
                <div class="chat-button">
                    <a href="/MercApp/controllers/chat_start.php?producto_id=<?= $producto["id"] ?>">
                        ¿Tienes dudas? Chatea con el vendedor
                    </a>
                </div>

                <!-- Extras informativos -->
                <div class="extras">
                    <p>📦 Envío a acordar con el vendedor</p>
                    <p>🛡️ Compra segura</p>
                </div>
            </div>

            <!-- ---------------------------------------------------------
                 DESCRIPCIÓN DEL PRODUCTO
            ---------------------------------------------------------- -->
            <p class="description">
                <?= nl2br(htmlspecialchars($producto['descripcion'])) ?>
            </p>

        </div>

        <!-- ---------------------------------------------------------
             PRODUCTOS SUGERIDOS (CARRUSEL)
        ---------------------------------------------------------- -->
        <section class="suggested-products">
            <h2>Productos sugeridos</h2>

            <?php if (!empty($productosSugeridos)): ?>

                <div class="carousel-wrapper">

                    <!-- Flecha izquierda del carrusel -->
                    <button class="arrow left" onclick="scrollCarousel(-1)">❮</button>

                    <!-- Contenedor horizontal con scroll -->
                    <div class="carousel" id="carousel">

                        <?php foreach ($productosSugeridos as $s): ?>

                            <?php
                            // Imagen segura: si no tiene imagen, se usa una por defecto
                            $img = 'MercApp/uploads/products/default.jpg';
                            if (!empty($s['imagenes']) && isset($s['imagenes'][0]['url'])) {
                                $img = $s['imagenes'][0]['url'];
                            }
                            ?>

                            <!-- Tarjeta individual del producto sugerido -->
                            <div class="item">
                                <a href="detail_product.php?id=<?= $s['id'] ?>">

                                    <!-- Imagen del producto sugerido -->
                                    <img src="/<?= htmlspecialchars($img) ?>" alt="Producto sugerido">

                                    <!-- Título -->
                                    <p class="title"><?= htmlspecialchars($s['titulo']) ?></p>

                                    <!-- Precio -->
                                    <p class="price"><?= number_format($s['precio'], 2) ?> €</p>
                                </a>
                            </div>

                        <?php endforeach; ?>
                    </div>

                    <!-- Flecha derecha del carrusel -->
                    <button class="arrow right" onclick="scrollCarousel(1)">❯</button>

                </div>

            <?php else: ?>
                <!-- Mensaje si no hay sugerencias -->
                <p>No hay productos sugeridos por ahora.</p>
            <?php endif; ?>
        </section>

    </main>

    <footer>
        <?php include __DIR__ . '/footer.php'; ?>
    </footer>

</body>

</html>
