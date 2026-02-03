<?php
// Cargar configuración de base de datos y el modelo Product
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Product.php';

// Crear instancia de la base de datos y obtener conexión PDO
$db = new Database();
$conn = $db->getConnection();

// Obtener el ID del producto desde la URL (GET)
$productId = 0;

if (isset($_GET['id'])) {
    $productId = (int) $_GET['id'];
}

// Validar que el ID sea correcto
if ($productId <= 0) {
    echo "Producto no válido.";
    exit;
}

// Crear instancia del modelo Product
$productModel = new Product($conn);

// Obtener los datos del producto por ID
$producto = $productModel->getById($productId);

// Si no existe el producto, mostrar mensaje y detener ejecución
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

    <!-- Favicon -->
    <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/detail_product.css">
    <link rel="stylesheet" href="../css/style-guide.css">


    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="../css/style-guide.css" rel="stylesheet">
</head>

<body>
    <?php
    $showSearch = true;
    include("navbar.php");
    ?>

    <main>
     <div class="product-gallery">

    <?php if (!empty($producto['imagenes'])): ?>
        <!-- 
            Si el producto tiene imágenes, se muestra la primera como imagen principal.
            htmlspecialchars() evita que una URL maliciosa pueda romper el HTML o ejecutar código.
        -->
        <img src="/MercApp/<?= htmlspecialchars($producto['imagenes'][0]['url']) ?>" class="main-img">
    <?php else: ?>
        <!-- 
            Si el producto NO tiene imágenes, se muestra una imagen por defecto.
            Esto evita errores visuales y mantiene la estructura de la página.
        -->
        <img src="/MercApp/public/img/default.jpg" class="main-img">
    <?php endif; ?>

    <div class="thumbs">
        <?php foreach ($producto['imagenes'] as $img): ?>
            <!-- 
                Se recorren todas las imágenes del producto y se muestran como miniaturas.
                Estas miniaturas suelen servir para cambiar la imagen principal al hacer clic.
                htmlspecialchars() protege la URL de cada miniatura.
            -->
            <img src="/MercApp/<?= htmlspecialchars($img['url']) ?>" class="thumb">
        <?php endforeach; ?>
    </div>

</div>


            <!-- Información a la derecha -->
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
                    <a href="../chat/iniciar.php?producto=<?= $producto['id'] ?>">
                        ¿Tienes dudas? Chatea con el vendedor
                    </a>
                </div>

                <div class="extras">
                    <p>📦 Envío a acordar con el vendedor</p>
                    <p>🛡️ Compra segura</p>
                </div>
            </div>

            <!-- Descripción abajo -->
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
