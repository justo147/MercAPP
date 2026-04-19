<?php
session_start();
require_once __DIR__ . '/../../controllers/check_auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/User.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../views/auth/login.php");
    exit;
}

$userId    = (int)$_SESSION['user_id'];
$db        = new Database();
$conn      = $db->getConnection();
$userModel = new User($conn);

/* ── Usuarios que sigo ── */
$siguiendo    = $userModel->obtenerSeguidos($userId);
$siguiendoIds = array_column($siguiendo, 'id');

/* ── Sugerencias: usuarios activos con más productos que aún no sigo ── */
$sugerencias = [];
try {
    $excludeSql = '';
    $allParams  = [$userId];           // primer parámetro: uid

    if (!empty($siguiendoIds)) {
        $ph = implode(',', array_fill(0, count($siguiendoIds), '?'));
        $excludeSql  = "AND u.id NOT IN ($ph)";
        $allParams   = array_merge($allParams, $siguiendoIds);
    }

    $stmtSug = $conn->prepare("
        SELECT u.id, u.nombre, u.apellidos, u.foto_perfil,
               COUNT(p.id) AS total_productos
        FROM   Usuario u
        JOIN   Productos p          ON p.usuario_id      = u.id
        JOIN   EstadoPublicacion ep ON ep.id             = p.estado_publicacion_id
                                   AND ep.nombre         = 'activo'
        WHERE  u.id != ?
               $excludeSql
        GROUP  BY u.id
        HAVING total_productos > 0
        ORDER  BY total_productos DESC
        LIMIT  8
    ");
    $stmtSug->execute($allParams);
    $sugerencias = $stmtSug->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* silencioso */ }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siguiendo — MercApp</title>

    <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

    <style>
        /* ── Avatar genérico ── */
        .fw-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .fw-avatar-fb {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex; align-items: center; justify-content: center;
            color: #6c757d;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        /* ── Tarjeta de producto en el feed ── */
        .fw-prod-card {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #dee2e6;
            transition: transform .15s, box-shadow .15s;
            background: #fff;
        }
        .fw-prod-card:hover { transform: translateY(-4px); box-shadow: 0 6px 18px rgba(0,0,0,.1); }
        .fw-prod-card .fw-prod-img {
            width: 100%; height: 170px;
            object-fit: cover; display: block;
        }
        .fw-prod-card .fw-prod-body { padding: .75rem; }
        .fw-prod-title {
            font-size: .85rem; font-weight: 600;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .fw-prod-price { font-size: .95rem; font-weight: 700; color: #198754; }
        .fw-prod-seller { font-size: .72rem; color: #6c757d; }

        /* ── Tarjeta sugerencia ── */
        .fw-sug-item {
            display: flex; align-items: center; gap: 10px;
            padding: .6rem 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .fw-sug-item:last-child { border-bottom: none; }

        /* ── Skeleton loader ── */
        .fw-skeleton {
            background: linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);
            background-size: 200% 100%;
            animation: fw-shimmer 1.4s infinite;
            border-radius: 8px;
        }
        @keyframes fw-shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

        /* dark mode */
        body.dark-mode .fw-prod-card   { background: #1e1e2e; border-color: #3a3a4a; }
        body.dark-mode .fw-sug-item    { border-color: #3a3a4a; }
        body.dark-mode .fw-avatar-fb   { background: #2a2a3a; color: #aaa; }
        body.dark-mode .fw-skeleton    { background: linear-gradient(90deg,#2a2a3a 25%,#333345 50%,#2a2a3a 75%); background-size: 200% 100%; }
    </style>
</head>
<body>

<?php $showSearch = false; include "navbar.php"; ?>

<main class="container my-4" style="max-width:1200px;">

    <div class="row g-4 align-items-start">

        <!-- ══ FEED (izquierda) ══════════════════════════════════ -->
        <div class="col-lg-8">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-newspaper me-2 text-primary"></i>Novedades
                </h4>
                <span id="feed-total" class="text-muted small"></span>
            </div>

            <!-- Skeletons mientras carga -->
            <div id="feed-skeletons" class="row g-3">
                <?php for ($i = 0; $i < 8; $i++): ?>
                <div class="col-6 col-md-4 col-lg-4">
                    <div class="fw-prod-card p-0" style="height:250px;">
                        <div class="fw-skeleton" style="height:170px; border-radius:12px 12px 0 0;"></div>
                        <div class="p-2">
                            <div class="fw-skeleton mb-2" style="height:14px; width:80%;"></div>
                            <div class="fw-skeleton" style="height:14px; width:40%;"></div>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- Empty state (oculto hasta que se confirme vacío) -->
            <div id="feed-empty" class="text-center py-5 d-none">
                <i class="bi bi-newspaper fs-1 d-block mb-3 text-muted opacity-50"></i>
                <p class="fw-semibold text-muted">Aquí verás los productos de las personas que sigas</p>
                <p class="small text-muted">Empieza siguiendo a alguien de las sugerencias →</p>
            </div>

            <!-- Grid de productos -->
            <div class="row g-3" id="product-list"></div>

            <!-- Botón cargar más -->
            <div class="text-center mt-4 d-none" id="loadmore-wrap">
                <button id="loadMore" class="btn btn-outline-primary px-4">
                    <i class="bi bi-arrow-down-circle me-1"></i>Cargar más
                </button>
            </div>

        </div>

        <!-- ══ SIDEBAR (derecha) ══════════════════════════════════ -->
        <div class="col-lg-4">

            <!-- ── Siguiendo ── -->
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex align-items-center justify-content-between py-2">
                    <span class="fw-semibold small">
                        <i class="bi bi-person-check-fill me-1 text-primary"></i>Siguiendo
                    </span>
                    <span class="badge bg-primary rounded-pill"><?= count($siguiendo) ?></span>
                </div>

                <?php if (empty($siguiendo)): ?>
                <div class="card-body text-center py-4">
                    <i class="bi bi-person-plus fs-2 d-block mb-2 text-muted opacity-50"></i>
                    <p class="small text-muted mb-0">No sigues a nadie todavía.<br>
                    Mira las sugerencias de abajo.</p>
                </div>

                <?php else: ?>
                <ul class="list-group list-group-flush" id="siguiendo-list">
                    <?php foreach ($siguiendo as $u):
                        $nombre = htmlspecialchars(trim($u['nombre'] . ' ' . $u['apellidos']));
                    ?>
                    <li class="list-group-item d-flex align-items-center gap-2 py-2 px-3"
                        id="sgt-<?= $u['id'] ?>">

                        <a href="profile.php?id=<?= $u['id'] ?>" class="flex-shrink-0">
                            <?php if (!empty($u['foto_perfil'])): ?>
                            <img src="<?= $BASE ?>/<?= htmlspecialchars($u['foto_perfil']) ?>"
                                 alt="<?= $nombre ?>" class="fw-avatar"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                            <span class="fw-avatar-fb" style="display:none">
                                <i class="bi bi-person"></i>
                            </span>
                            <?php else: ?>
                            <span class="fw-avatar-fb"><i class="bi bi-person"></i></span>
                            <?php endif; ?>
                        </a>

                        <div class="flex-grow-1 min-w-0">
                            <a href="profile.php?id=<?= $u['id'] ?>"
                               class="text-body text-decoration-none small fw-semibold text-truncate d-block">
                                <?= $nombre ?>
                            </a>
                        </div>

                        <button class="btn btn-sm btn-outline-secondary fw-unfollow-btn flex-shrink-0"
                                data-id="<?= $u['id'] ?>" title="Dejar de seguir"
                                style="font-size:.72rem; padding:2px 7px;">
                            <i class="bi bi-person-dash"></i>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <!-- ── Sugerencias ── -->
            <?php if (!empty($sugerencias)): ?>
            <div class="card shadow-sm" id="sug-card">
                <div class="card-header py-2">
                    <span class="fw-semibold small">
                        <i class="bi bi-stars me-1 text-warning"></i>Sugerencias para ti
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($sugerencias as $s):
                        $sNombre = htmlspecialchars(trim($s['nombre'] . ' ' . $s['apellidos']));
                    ?>
                    <div class="fw-sug-item" id="sug-<?= $s['id'] ?>">

                        <a href="profile.php?id=<?= $s['id'] ?>" class="flex-shrink-0">
                            <?php if (!empty($s['foto_perfil'])): ?>
                            <img src="<?= $BASE ?>/<?= htmlspecialchars($s['foto_perfil']) ?>"
                                 alt="<?= $sNombre ?>" class="fw-avatar"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                            <span class="fw-avatar-fb" style="display:none">
                                <i class="bi bi-person"></i>
                            </span>
                            <?php else: ?>
                            <span class="fw-avatar-fb"><i class="bi bi-person"></i></span>
                            <?php endif; ?>
                        </a>

                        <div class="flex-grow-1 min-w-0">
                            <a href="profile.php?id=<?= $s['id'] ?>"
                               class="text-body text-decoration-none small fw-semibold text-truncate d-block">
                                <?= $sNombre ?>
                            </a>
                            <span class="text-muted" style="font-size:.7rem;">
                                <i class="bi bi-box-seam me-1"></i><?= (int)$s['total_productos'] ?> producto<?= $s['total_productos'] != 1 ? 's' : '' ?>
                            </span>
                        </div>

                        <button class="btn btn-sm btn-primary fw-follow-btn flex-shrink-0"
                                data-id="<?= $s['id'] ?>"
                                style="font-size:.72rem; padding:3px 8px; white-space:nowrap;">
                            <i class="bi bi-person-plus-fill me-1"></i>Seguir
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php elseif (empty($siguiendo)): ?>
            <!-- Sin sugerencias y sin seguidos: panel motivacional -->
            <div class="card shadow-sm text-center p-4">
                <i class="bi bi-people fs-1 d-block mb-2 text-muted opacity-50"></i>
                <p class="small text-muted mb-0">
                    Cuando otros usuarios publiquen productos, aparecerán aquí como sugerencias.
                </p>
            </div>
            <?php endif; ?>

        </div><!-- /sidebar -->

    </div><!-- /row -->

</main>

<?php include __DIR__ . '/footer.php'; ?>

<script>
/* ── Estado ────────────────────────────── */
let page       = 1;
const LIMIT    = 9;
let isLoading  = false;
let firstLoad  = true;

const DEFAULT_IMG = `${BASE}/public/img/default.jpg`;

/* ── Helpers ─────────────────────────────────────── */
function badgeTipo(tipo) {
    const m = { venta:'primary', intercambio:'warning text-dark', mixto:'success' };
    const cls = m[tipo] ?? 'secondary';
    return `<span class="badge bg-${cls}" style="font-size:.6rem;">${esc(tipo)}</span>`;
}
function esc(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
}

/* ── Renderizar producto ─────────────────────────── */
function renderProduct(p) {
    const img    = p.imagenes?.length ? `${BASE}/${p.imagenes[0].url}` : DEFAULT_IMG;
    const precio = (p.precio && parseFloat(p.precio) > 0)
        ? `${parseFloat(p.precio).toFixed(2)} €`
        : 'Gratis';

    const col = document.createElement('div');
    col.className = 'col-6 col-md-4';
    col.innerHTML = `
        <a href="detail_product.php?id=${p.id}" class="text-decoration-none text-body">
            <div class="fw-prod-card">
                <img src="${img}" class="fw-prod-img"
                     alt="${esc(p.titulo)}"
                     onerror="this.onerror=null; this.src='${DEFAULT_IMG}'">
                <div class="fw-prod-body">
                    <div class="fw-prod-title" title="${esc(p.titulo)}">${esc(p.titulo)}</div>
                    <div class="d-flex align-items-center gap-1 my-1">
                        ${badgeTipo(p.tipo_transaccion)}
                    </div>
                    <div class="fw-prod-price">${precio}</div>
                    <div class="fw-prod-seller mt-1">
                        <i class="bi bi-person me-1"></i>${esc(p.usuario_nombre ?? '')}
                    </div>
                </div>
            </div>
        </a>`;
    return col;
}

/* ── Cargar productos ─────────────────────────────── */
function loadProducts() {
    if (isLoading) return;
    isLoading = true;

    fetch(`${BASE}/api/products_following.php?page=${page}&limit=${LIMIT}`)
        .then(r => r.json())
        .then(data => {
            const skeletons = document.getElementById('feed-skeletons');
            const empty     = document.getElementById('feed-empty');
            const list      = document.getElementById('product-list');
            const wrap      = document.getElementById('loadmore-wrap');
            const totalEl   = document.getElementById('feed-total');

            /* Ocultar skeletons en primera carga */
            if (firstLoad && skeletons) {
                skeletons.remove();
                firstLoad = false;
            }

            if (!data.success || !data.productos.length) {
                if (page === 1) empty.classList.remove('d-none');
                wrap.classList.add('d-none');
                return;
            }

            /* Total */
            if (data.total != null) {
                totalEl.textContent = `${data.total} producto${data.total !== 1 ? 's' : ''}`;
            }

            data.productos.forEach(p => list.appendChild(renderProduct(p)));

            /* Botón "cargar más" */
            if (data.productos.length >= LIMIT) {
                wrap.classList.remove('d-none');
            } else {
                wrap.classList.add('d-none');
            }
        })
        .catch(() => {
            document.getElementById('feed-skeletons')?.remove();
            firstLoad = false;
        })
        .finally(() => isLoading = false);
}

document.getElementById('loadMore').addEventListener('click', () => {
    page++;
    loadProducts();
});

loadProducts();

/* ══ SEGUIR desde sugerencias ═══════════════════════ */
document.querySelectorAll('.fw-follow-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const seguido = this.dataset.id;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch(`${BASE}/controllers/follow.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `seguidor=<?= $userId ?>&seguido=${seguido}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                /* Mover item de sugerencias a siguiendo */
                const sugItem = document.getElementById(`sug-${seguido}`);
                if (sugItem) sugItem.remove();

                /* Recargar página para actualizar la lista y el feed */
                window.location.reload();
            } else {
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-person-plus-fill me-1"></i>Seguir';
            }
        })
        .catch(() => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-person-plus-fill me-1"></i>Seguir';
        });
    });
});

/* ══ DEJAR DE SEGUIR desde lista ════════════════════ */
document.querySelectorAll('.fw-unfollow-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const seguido = this.dataset.id;
        if (!confirm('¿Dejar de seguir a este usuario?')) return;

        this.disabled = true;

        fetch(`${BASE}/controllers/unfollow.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `seguidor=<?= $userId ?>&seguido=${seguido}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                this.disabled = false;
            }
        })
        .catch(() => this.disabled = false);
    });
});
</script>

</body>
</html>
