<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Product.php';

session_start();

$db = new Database();
$conn = $db->getConnection();

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($productId <= 0) {
    header("Location: home.php");
    exit;
}

$productModel = new Product($conn);
$producto = $productModel->getById($productId);

if (!$producto) {
    header("Location: home.php");
    exit;
}

$vendedorId      = $producto['usuario_id'] ?? null;
$vendedor        = null;
$yaSigue         = false;
$usuarioLogueado = $_SESSION['user_id'] ?? null;

if ($vendedorId) {
    require_once __DIR__ . '/../../models/User.php';
    $userModel = new User($conn);
    $vendedor  = $userModel->getById($vendedorId);
    if ($usuarioLogueado && $usuarioLogueado != $vendedorId) {
        $yaSigue = $userModel->sigueA($usuarioLogueado, $vendedorId);
    }
}

$productosSugeridos = $productModel->getRandomProducts(20, $productId);

/* ── helpers ── */
$imagenes    = is_array($producto['imagenes']) ? $producto['imagenes'] : [];
$imgPrincipal = !empty($imagenes)
    ? $BASE . '/' . $imagenes[0]['url']
    : $BASE . '/public/img/default.jpg';

$precioStr = (!empty($producto['precio']) && (float)$producto['precio'] > 0)
    ? number_format((float)$producto['precio'], 2) . ' €'
    : 'Gratis';

$tipoBadge = [
    'venta'       => ['bg-primary',              'Venta'],
    'intercambio' => ['bg-warning text-dark',    'Intercambio'],
    'mixto'       => ['bg-success',              'Venta / Intercambio'],
];
[$tipoCls, $tipoLabel] = $tipoBadge[$producto['tipo_transaccion'] ?? '']
    ?? ['bg-secondary', ucfirst($producto['tipo_transaccion'] ?? '')];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($producto['titulo']) ?> — MercApp</title>

    <link rel="icon" href="../ico/logo_sinfondo.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/detail_product.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<?php $showSearch = true; include("navbar.php"); ?>

<main class="container my-4" style="max-width:1100px;">

    <div class="row g-4 align-items-start">

        <!-- ══ GALERÍA ══════════════════════════════════════ -->
        <div class="col-lg-6">
            <div class="dp-gallery">

                <!-- Imagen principal -->
                <div class="dp-main-wrap">
                    <img id="dp-main-img"
                         src="<?= htmlspecialchars($imgPrincipal) ?>"
                         alt="<?= htmlspecialchars($producto['titulo']) ?>"
                         class="dp-main-img"
                         onerror="this.onerror=null; this.src='<?= $BASE ?>/public/img/default.jpg'">
                </div>

                <!-- Miniaturas (solo si hay más de 1) -->
                <?php if (count($imagenes) > 1): ?>
                <div class="dp-thumbs mt-2">
                    <?php foreach ($imagenes as $i => $img):
                        $src = htmlspecialchars($BASE . '/' . $img['url']);
                    ?>
                    <img src="<?= $src ?>"
                         alt="Imagen <?= $i + 1 ?>"
                         class="dp-thumb <?= $i === 0 ? 'dp-thumb--active' : '' ?>"
                         onerror="this.onerror=null; this.src='<?= $BASE ?>/public/img/default.jpg'"
                         onclick="dpSetMain(this, '<?= $src ?>')">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- ══ INFORMACIÓN ══════════════════════════════════ -->
        <div class="col-lg-6">
            <div class="dp-info">

                <!-- Badges: tipo + estado + categoría -->
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge <?= $tipoCls ?>"><?= $tipoLabel ?></span>
                    <?php if (!empty($producto['estado_producto'])): ?>
                    <span class="badge bg-light text-dark border">
                        <?= htmlspecialchars($producto['estado_producto']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($producto['categoria'])): ?>
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-tag me-1"></i><?= htmlspecialchars($producto['categoria']) ?>
                    </span>
                    <?php endif; ?>
                </div>

                <h1 class="dp-title"><?= htmlspecialchars($producto['titulo']) ?></h1>

                <!-- Precio -->
                <div class="dp-price-wrap mb-3">
                    <span class="dp-price"><?= $precioStr ?></span>
                    <?php if (!empty($producto['precio_original']) && (float)$producto['precio_original'] > (float)$producto['precio']): ?>
                    <span class="dp-price-old"><?= number_format((float)$producto['precio_original'], 2) ?> €</span>
                    <span class="badge bg-danger ms-1">
                        -<?= round(100 * ($producto['precio_original'] - $producto['precio']) / $producto['precio_original']) ?>%
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Ubicación -->
                <?php if (!empty($producto['ubicacion'])): ?>
                <p class="dp-location mb-3">
                    <i class="bi bi-geo-alt-fill me-1 text-danger"></i>
                    <?= htmlspecialchars($producto['ubicacion']) ?>
                </p>
                <?php endif; ?>

                <hr class="my-3">

                <!-- Botones de acción -->
                <div class="d-flex gap-2 mb-3">
                    <button id="favBtn"
                            class="btn btn-outline-danger dp-fav-btn"
                            onclick="toggleFavorito(<?= $producto['id'] ?>)"
                            title="Añadir a favoritos">
                        <i class="bi bi-heart fs-5"></i>
                    </button>
                    <?php if ($usuarioLogueado && $usuarioLogueado != $vendedorId): ?>
                    <a href="<?= $BASE ?>/controllers/chat_start.php?producto_id=<?= $producto['id'] ?>"
                       class="btn btn-primary flex-grow-1 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-chat-dots-fill"></i>
                        Contactar con el vendedor
                    </a>
                    <?php else: ?>
                    <button class="btn btn-primary flex-grow-1" disabled>
                        <i class="bi bi-chat-dots-fill me-1"></i>
                        <?= $usuarioLogueado == $vendedorId ? 'Tu producto' : 'Contactar con el vendedor' ?>
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Garantías -->
                <div class="dp-guarantees">
                    <span><i class="bi bi-truck me-1 text-primary"></i>Envío a acordar con el vendedor</span>
                    <span><i class="bi bi-shield-check me-1 text-success"></i>Compra segura en MercApp</span>
                </div>

            </div>
        </div>

    </div><!-- /row -->

    <!-- ══ DESCRIPCIÓN ══════════════════════════════════════ -->
    <div class="card mt-4 shadow-sm">
        <div class="card-body">
            <h5 class="fw-semibold mb-3">
                <i class="bi bi-file-text me-2 text-muted"></i>Descripción
            </h5>
            <?php if (!empty(trim($producto['descripcion'] ?? ''))): ?>
            <p class="mb-0 lh-lg"><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
            <?php else: ?>
            <p class="text-muted fst-italic mb-0">El vendedor no ha añadido descripción.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ VENDEDOR ════════════════════════════════════════ -->
    <?php if (!empty($vendedor)): ?>
    <div class="card mt-3 shadow-sm">
        <div class="card-body">
            <h5 class="fw-semibold mb-3">
                <i class="bi bi-person-circle me-2 text-muted"></i>Sobre el vendedor
            </h5>
            <div class="d-flex align-items-center gap-3">

                <a href="<?= $BASE ?>/public/views/profile.php?id=<?= $vendedorId ?>" class="flex-shrink-0">
                    <?php if (!empty($vendedor['foto_perfil'])): ?>
                    <img src="<?= $BASE ?>/<?= htmlspecialchars($vendedor['foto_perfil']) ?>"
                         alt="Foto de perfil"
                         class="dp-seller-avatar"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    <span class="dp-seller-avatar-fb" style="display:none">
                        <i class="bi bi-person fs-2"></i>
                    </span>
                    <?php else: ?>
                    <span class="dp-seller-avatar-fb">
                        <i class="bi bi-person fs-2"></i>
                    </span>
                    <?php endif; ?>
                </a>

                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold">
                        <a href="<?= $BASE ?>/public/views/profile.php?id=<?= $vendedorId ?>"
                           class="text-decoration-none text-body">
                            <?= htmlspecialchars(trim(($vendedor['nombre'] ?? '') . ' ' . ($vendedor['apellidos'] ?? ''))) ?>
                        </a>
                    </div>
                    <small class="text-muted">
                        <i class="bi bi-calendar3 me-1"></i>Miembro desde
                        <?= (new DateTime($vendedor['fecha_registro']))->format('m/Y') ?>
                    </small>
                </div>

                <?php if ($usuarioLogueado && $usuarioLogueado != $vendedorId): ?>
                <button id="btn-follow"
                        class="btn btn-sm <?= $yaSigue ? 'btn-secondary' : 'btn-primary' ?>"
                        data-seguidor="<?= $usuarioLogueado ?>"
                        data-seguido="<?= $vendedorId ?>"
                        data-sigue="<?= $yaSigue ? '1' : '0' ?>">
                    <?= $yaSigue ? 'Siguiendo' : 'Seguir' ?>
                </button>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ SUGERIDOS ══════════════════════════════════════ -->
    <?php if (!empty($productosSugeridos)): ?>
    <section class="mt-5 mb-4">
        <h5 class="fw-semibold mb-3">
            <i class="bi bi-grid me-2 text-muted"></i>Productos sugeridos
        </h5>
        <div class="dp-sug-wrap">
            <button class="dp-sug-arrow" onclick="dpScrollSug(-1)" aria-label="Anterior">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="dp-sug-scroll" id="dp-sugeridos">
                <?php foreach ($productosSugeridos as $s):
                    $sImgRaw = !empty($s['imagenes'][0]['url']) ? $s['imagenes'][0]['url'] : null;
                    $sImg    = $sImgRaw ? $BASE . '/' . $sImgRaw : $BASE . '/public/img/default.jpg';
                    $sPrecio = (!empty($s['precio']) && (float)$s['precio'] > 0)
                        ? number_format((float)$s['precio'], 2) . ' €'
                        : 'Gratis';
                ?>
                <a href="detail_product.php?id=<?= (int)$s['id'] ?>"
                   class="dp-sug-card text-decoration-none text-body">
                    <img src="<?= htmlspecialchars($sImg) ?>"
                         alt="<?= htmlspecialchars($s['titulo']) ?>"
                         onerror="this.onerror=null; this.src='<?= $BASE ?>/public/img/default.jpg'">
                    <div class="dp-sug-body">
                        <div class="dp-sug-title"><?= htmlspecialchars($s['titulo']) ?></div>
                        <div class="dp-sug-price"><?= $sPrecio ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <button class="dp-sug-arrow" onclick="dpScrollSug(1)" aria-label="Siguiente">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- Scripts ──────────────────────────────────── -->
<script>
    window.productoIdGlobal = <?= (int)$producto['id'] ?>;

    /* ── Galería: cambiar imagen principal ── */
    function dpSetMain(thumb, src) {
        const main = document.getElementById('dp-main-img');
        if (main) main.src = src;
        document.querySelectorAll('.dp-thumb')
            .forEach(t => t.classList.remove('dp-thumb--active'));
        thumb.classList.add('dp-thumb--active');
    }

    /* ── Carrusel sugeridos ── */
    function dpScrollSug(dir) {
        const el = document.getElementById('dp-sugeridos');
        if (el) el.scrollBy({ left: dir * 320, behavior: 'smooth' });
    }
</script>

<script src="<?= htmlspecialchars($BASE) ?>/public/js/favorite.js"></script>

<script>
    /* ── Seguir / Dejar de seguir ── */
    document.addEventListener("DOMContentLoaded", () => {
        const btn = document.getElementById("btn-follow");
        if (!btn) return;

        let sigue = btn.dataset.sigue === "1";

        function actualizarBoton() {
            btn.classList.toggle("btn-primary",   !sigue);
            btn.classList.toggle("btn-secondary",  sigue);
            btn.textContent = sigue ? "Siguiendo" : "Seguir";
        }
        actualizarBoton();

        btn.addEventListener("mouseenter", () => {
            if (sigue) btn.textContent = "Dejar de seguir";
        });
        btn.addEventListener("mouseleave", actualizarBoton);

        btn.addEventListener("click", () => {
            btn.disabled = true;
            const url = sigue
                ? `${BASE}/controllers/unfollow.php`
                : `${BASE}/controllers/follow.php`;

            fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `seguidor=${btn.dataset.seguidor}&seguido=${btn.dataset.seguido}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    sigue = !sigue;
                    btn.dataset.sigue = sigue ? "1" : "0";
                    actualizarBoton();
                }
            })
            .catch(console.error)
            .finally(() => btn.disabled = false);
        });
    });
</script>

</body>
</html>
