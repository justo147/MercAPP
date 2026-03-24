<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Product.php';

session_start();

// Conexión a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Validación del ID recibido por GET
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($productId <= 0) {
    echo "Producto no válido.";
    exit;
}

// Obtención del producto principal
$productModel = new Product($conn);
$producto = $productModel->getById($productId);

if (!$producto) {
    echo "Producto no encontrado.";
    exit;
}
$vendedorId = $producto['usuario_id'] ?? null;
if ($vendedorId) {
    require_once __DIR__ . '/../../models/User.php';

    $userModel = new User($conn);
    $vendedor = $userModel->getById($vendedorId);

    // Ver si el usuario logueado sigue al vendedor
    $usuarioLogueado = $_SESSION['user_id'] ?? null;
    $yaSigue = false;

    if ($usuarioLogueado && $usuarioLogueado != $vendedorId) {
        $yaSigue = $userModel->sigueA($usuarioLogueado, $vendedorId);
    }
}

// Productos sugeridos (excluyendo el actual)
$productosSugeridos = $productModel->getRandomProducts(20, $productId);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($producto['titulo']) ?></title>

    <!-- Estilos y librerías externas -->
    <link rel="icon" href="../ico/logo_sinfondo.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/detail_product.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="../js/detailProduct.js" defer></script>
    <script src="../js/favorite.js" defer> </script>
</head>

<body>

    <?php
    // Navbar con buscador activo
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
                        <img src="<?= $BASE ?>/<?= htmlspecialchars($producto['imagenes'][0]['url']) ?>"
                            class="img-fluid rounded mb-3" alt="Imagen del producto">
                    <?php else: ?>
                        <img src="<?= $BASE ?>/uploads/products/default.jpg" class="img-fluid rounded mb-3" alt="Imagen por defecto">
                    <?php endif; ?>

                    <!-- Miniaturas -->
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($producto['imagenes'] as $img): ?>
                            <img src="<?= $BASE ?>/<?= htmlspecialchars($img['url']) ?>" class="img-thumbnail"
                                style="width: 80px; height: 80px; object-fit: cover;" alt="Miniatura">
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>

            <!-- Información del producto -->
            <div class="col-md-6">
                <div class="card p-4">

                    <h1 class="h3"><?= htmlspecialchars($producto['titulo']) ?></h1>

                    <p class="text-muted mb-2">
                        <i class="bi bi-geo-alt"></i>
                        Ubicación: <?= htmlspecialchars($producto['ubicacion']) ?>
                    </p>

                    <!-- Precio y descuento -->
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

                    <!-- Botón de favoritos (Bootstrap Icons) -->
                    <button id="favBtn"
                        class="btn btn-outline-danger mb-3 d-flex align-items-center justify-content-center"
                        style="width: 55px; height: 55px; border-radius: 50%; font-size: 1.6rem;"
                        onclick="toggleFavorito(<?= $producto['id'] ?>)">
                        <i class="bi bi-heart"></i>
                    </button>


                    <!-- Contactar con el vendedor -->
                    <a href="<?= $BASE ?>/controllers/chat_start.php?producto_id=<?= $producto["id"] ?>"
                        class="btn btn-primary w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-chat-dots"></i>
                        Contactar con el vendedor
                    </a>

                    <div class="text-center mb-3">
                        <a href="<?= $BASE ?>/controllers/chat_start.php?producto_id=<?= $producto["id"] ?>">
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

        <!-- Descripción -->
        <div class="card mt-4 p-4">
            <h4>Descripción</h4>
            <p><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
        </div>

        <!-- Perfil del vendedor -->
        <?php if (!empty($vendedor)): ?>
            <div class="card mt-4 p-4">

                <h4 class="mb-3">Perfil del vendedor</h4>

                <div class="d-flex align-items-center">

                    <!-- Foto -->
                    <a href="<?= $BASE ?>/public/views/profile.php?id=<?= $vendedorId ?>" class="me-3">
                        <?php if (!empty($vendedor['foto_perfil'])): ?>
                            <img src="<?= $BASE ?>/<?= htmlspecialchars($vendedor['foto_perfil']) ?>" class="rounded-circle"
                                width="80" height="80" style="object-fit: cover;">
                        <?php else: ?>
                            <i class="bi bi-person-circle text-secondary" style="font-size: 80px;"></i>
                        <?php endif; ?> 
                    </a>

                    <!-- Info -->
                    <div class="flex-grow-1">
                        <h5 class="mb-1">
                            <a href="<?= $BASE ?>/public/views/profile.php?id=<?= $vendedorId ?>">
                                <?= htmlspecialchars($vendedor['nombre'] . " " . $vendedor['apellidos']) ?>
                            </a>
                        </h5>

                        <p class="text-muted mb-1">
                            Miembro desde:
                            <?php
                            $fecha = new DateTime($vendedor['fecha_registro']);
                            echo $fecha->format('m/Y');
                            ?>
                        </p>
                    </div>

                    <!-- Botón seguir -->
                    <?php if ($usuarioLogueado && $usuarioLogueado != $vendedorId): ?>
                        <button id="btn-follow" class="btn <?= $yaSigue ? 'btn-secondary' : 'btn-primary' ?>"
                            data-seguidor="<?= $usuarioLogueado ?>" data-seguido="<?= $vendedorId ?>"
                            data-sigue="<?= $yaSigue ? '1' : '0' ?>">
                            <?= $yaSigue ? 'Siguiendo' : 'Seguir' ?>
                        </button>
                    <?php endif; ?>

                </div>

            </div>
        <?php endif; ?>

        <!-- Productos sugeridos -->
        <section class="mt-5">
            <h2 class="mb-3">Productos sugeridos</h2>

            <?php if (!empty($productosSugeridos)): ?>
                <div id="carouselSugeridos" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">

                        <?php foreach ($productosSugeridos as $index => $s): ?>
                            <?php
                            $img = "$BASE/uploads/products/default.jpg";
                            if (!empty($s['imagenes']) && isset($s['imagenes'][0]['url'])) {
                                $img = $s['imagenes'][0]['url'];
                            }
                            ?>

                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <div class="d-flex justify-content-center">
                                    <a href="detail_product.php?id=<?= $s['id'] ?>" class="text-center">
                                        <img src="/<?= htmlspecialchars($img) ?>" class="d-block"
                                            style="width: 200px; height: 200px; object-fit: cover;" alt="Producto sugerido">

                                        <p class="mt-2 fw-bold"><?= htmlspecialchars($s['titulo']) ?></p>
                                        <p class="text-success"><?= number_format($s['precio'], 2) ?> €</p>
                                    </a>
                                </div>
                            </div>

                        <?php endforeach; ?>

                    </div>

                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselSugeridos"
                        data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>

                    <button class="carousel-control-next" type="button" data-bs-target="#carouselSugeridos"
                        data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>

                </div>

            <?php else: ?>
                <p>No hay productos sugeridos por ahora.</p>
            <?php endif; ?>
        </section>

    </main>

    <?php include __DIR__ . '/footer.php'; ?>


    <!-- Pasamos el ID del producto desde PHP al archivo JS externo -->
    <script>
        window.productoIdGlobal = <?= $producto['id'] ?>;
    </script>

    <!-- Cargamos el archivo externo que gestiona los favoritos -->
    <script src="<?php echo $_ENV['BASE_PATH']; ?>/public/js/favorite.js"></script>


    <script>
        document.addEventListener("DOMContentLoaded", () => {

            const btnFollow = document.getElementById("btn-follow");
            if (!btnFollow) return;

            let sigue = btnFollow.dataset.sigue === "1";

            actualizarBoton();

            btnFollow.addEventListener("mouseenter", () => {
                if (sigue) btnFollow.textContent = "Dejar de seguir";
            });

            btnFollow.addEventListener("mouseleave", () => {
                actualizarBoton();
            });

            btnFollow.addEventListener("click", () => {

                btnFollow.disabled = true;

                const seguidor = btnFollow.dataset.seguidor;
                const seguido = btnFollow.dataset.seguido;

                const url = sigue
                    ? `${BASE}/controllers/unfollow.php`
                    : `${BASE}/controllers/follow.php`;

                fetch(url, {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `seguidor=${seguidor}&seguido=${seguido}`
                })
                    .then(res => res.json())
                    .then(data => {

                        if (!data.success) {
                            console.error("Error:", data);
                            return;
                        }

                        sigue = !sigue;
                        btnFollow.dataset.sigue = sigue ? "1" : "0";
                        actualizarBoton();
                    })
                    .catch(err => console.error(err))
                    .finally(() => btnFollow.disabled = false);
            });

            function actualizarBoton() {
                btnFollow.classList.toggle("btn-primary", !sigue);
                btnFollow.classList.toggle("btn-secondary", sigue);
                btnFollow.textContent = sigue ? "Siguiendo" : "Seguir";
            }

        });
    </script>


</body>

</html>