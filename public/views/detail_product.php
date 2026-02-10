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
    $showSearch = true;
    include("navbar.php");
    ?>

    <main class="container my-5">

        <div class="row g-4">

            <!-- Galería de imágenes -->
            <div class="col-md-6">

                <div class="card p-3">

                    <!-- Imagen principal -->
                    <?php if (!empty($producto['imagenes'])): ?>
                        <img src="/MercApp/<?= htmlspecialchars($producto['imagenes'][0]['url']) ?>"
                            class="img-fluid rounded mb-3"
                            alt="Imagen del producto">
                    <?php else: ?>
                        <img src="/uploads/products/default.jpg"
                            class="img-fluid rounded mb-3"
                            alt="Imagen por defecto">
                    <?php endif; ?>

                    <!-- Miniaturas -->
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($producto['imagenes'] as $img): ?>
                            <img src="/<?= htmlspecialchars($img['url']) ?>"
                                class="img-thumbnail"
                                style="width: 80px; height: 80px; object-fit: cover;"
                                alt="Miniatura">
                        <?php endforeach; ?>
                    </div>

                </div>

            </div>

        </div>


        <!-- ---------------------------------------------------------
                 INFORMACIÓN DEL PRODUCTO
            ---------------------------------------------------------- -->
        <div class="col-md-6">

            <div class="card p-4">

                <h1 class="h3"><?= htmlspecialchars($producto['titulo']) ?></h1>
                <p class="text-muted mb-2">
                    <i class="bi bi-geo-alt"></i>
                    Ubicación: <?= htmlspecialchars($producto['ubicacion']) ?>
                </p>

                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="fs-3 fw-bold text-success">
                        <?= number_format($producto['precio'], 2) ?> €
                    </span>

                    <?php if (!empty($producto['precio_original']) && $producto['precio_original'] > $producto['precio']): ?>
                        <span class="text-muted text-decoration-line-through">
                            <?= number_format($producto['precio_original'], 2) ?> €
                        </span>

                        <span class="badge bg-danger">
                            -<?= round(100 * ($producto['precio_original'] - $producto['precio']) / $producto['precio_original']) ?>%
                        </span>
                    <?php endif; ?>
                </div>

                <form method="post" action="cart.php" class="mb-3">
                    <input type="hidden" name="product_id" value="<?= $producto['id'] ?>">
                    <button type="submit" class="btn btn-success w-100 py-2">
                        Comprar
                    </button>
                </form>

                <a href="/MercApp/controllers/chat_start.php?producto_id=<?= $producto["id"] ?>"
                    class="btn btn-primary w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-chat-dots"></i>
                    Contactar con el vendedor
                </a>

                <div class="text-center mb-3">
                    <a href="/MercApp/controllers/chat_start.php?producto_id=<?= $producto["id"] ?>">
                        ¿Tienes dudas? Chatea con el vendedor
                    </a>
                </div>

                <div class="border-top pt-3">
                    <p>📦 Envío a acordar con el vendedor</p>
                    <p>🛡️ Compra segura</p>
                </div>

            </div>

        </div>

        </div>

        <!-- ---------------------------------------------------------
             DESCRIPCIÓN DEL PRODUCTO
        ---------------------------------------------------------- -->
        <div class="card mt-4 p-4">
            <h4>Descripción</h4>
            <p><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
        </div>

        <!-- ---------------------------------------------------------
             PRODUCTOS SUGERIDOS (CARRUSEL)
        ---------------------------------------------------------- -->
        <section class="mt-5">
            <h2 class="mb-3">Productos sugeridos</h2>

            <?php if (!empty($productosSugeridos)): ?>

                <div id="carouselSugeridos" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">

                        <?php foreach ($productosSugeridos as $index => $s): ?>
                            <?php
                            $img = 'MercApp/uploads/products/default.jpg';
                            if (!empty($s['imagenes']) && isset($s['imagenes'][0]['url'])) {
                                $img = $s['imagenes'][0]['url'];
                            }
                            ?>

                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <div class="d-flex justify-content-center">
                                    <a href="detail_product.php?id=<?= $s['id'] ?>" class="text-center">
                                        <img src="/<?= htmlspecialchars($img) ?>"
                                            class="d-block"
                                            style="width: 200px; height: 200px; object-fit: cover;"
                                            alt="Producto sugerido">

                                        <p class="mt-2 fw-bold"><?= htmlspecialchars($s['titulo']) ?></p>
                                        <p class="text-success"><?= number_format($s['precio'], 2) ?> €</p>
                                    </a>
                                </div>
                            </div>

                        <?php endforeach; ?>

                    </div>

                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselSugeridos" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>

                    <button class="carousel-control-next" type="button" data-bs-target="#carouselSugeridos" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>

                </div>

            <?php else: ?>
                <p>No hay productos sugeridos por ahora.</p>
            <?php endif; ?>
        </section>

    </main>

    <?php include __DIR__ . '/footer.php'; ?>

</body>


</html>