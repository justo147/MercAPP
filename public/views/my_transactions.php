<?php
session_start();
require_once __DIR__ . '/../../config/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis transacciones — MercApp</title>

    <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/homeStyle.css">

    <script src="../js/theme.js" defer></script>

    <style>
        .estado-badge { font-size: .75rem; }
        .tx-img { width: 56px; height: 56px; object-fit: cover; border-radius: .375rem; flex-shrink: 0; }
        .rol-tag { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; }
        .filter-btn.active { font-weight: 600; }
    </style>
</head>
<body>

<?php
$showSearch = false;
include 'navbar.php';
?>

<div class="container py-4" style="max-width: 760px;">

    <h4 class="mb-4 fw-bold">
        <i class="bi bi-clock-history me-2 text-primary"></i>Mis transacciones
    </h4>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4 border-0 bg-light">
        <div class="card-body py-2 px-3 d-flex flex-wrap gap-2 align-items-center">

            <span class="text-muted small me-1">Rol:</span>
            <div class="btn-group btn-group-sm" id="filtro-rol">
                <button class="btn btn-outline-primary filter-btn active" data-val="">Todos</button>
                <button class="btn btn-outline-primary filter-btn" data-val="compra">
                    <i class="bi bi-bag me-1"></i>Compras
                </button>
                <button class="btn btn-outline-primary filter-btn" data-val="venta">
                    <i class="bi bi-tag me-1"></i>Ventas
                </button>
            </div>

            <span class="text-muted small ms-2 me-1">Estado:</span>
            <select id="filtro-estado" class="form-select form-select-sm" style="width:auto;">
                <option value="">Todos</option>
                <option value="pendiente">Pendiente</option>
                <option value="aceptada">Aceptada</option>
                <option value="pago_pendiente">Pago pendiente</option>
                <option value="enviado">Enviado</option>
                <option value="entregado">Entregado</option>
                <option value="cancelada">Cancelada</option>
            </select>

        </div>
    </div>

    <!-- Contenedor de resultados -->
    <div id="tx-lista"></div>

    <!-- Paginación -->
    <nav id="tx-paginacion" class="mt-3"></nav>

</div>

<?php include __DIR__ . '/footer.php'; ?>

<script>
// BASE ya está definido por navbar.php

let estado = { page: 1, limit: 10, rol: '', estadoFiltro: '' };

function badgeEstado(e) {
    const map = {
        pendiente:      ['warning',   'bi-hourglass-split', 'Pendiente'],
        aceptada:       ['info',      'bi-hand-thumbs-up',  'Aceptada'],
        pago_pendiente: ['warning',   'bi-credit-card',     'Pago pendiente'],
        enviado:        ['primary',   'bi-truck',           'Enviado'],
        entregado:      ['success',   'bi-check-circle',    'Entregado'],
        cancelada:      ['danger',    'bi-x-circle',        'Cancelada'],
    };
    const [color, icon, label] = map[e] ?? ['secondary', 'bi-question', e];
    return `<span class="badge text-bg-${color} estado-badge">
                <i class="bi ${icon} me-1"></i>${label}
            </span>`;
}

function fechaRelativa(fecha) {
    if (!fecha) return '';
    const diff = Math.floor((Date.now() - new Date(fecha)) / 1000);
    if (diff < 60)     return 'Hace un momento';
    if (diff < 3600)   return `Hace ${Math.floor(diff/60)} min`;
    if (diff < 86400)  return `Hace ${Math.floor(diff/3600)} h`;
    if (diff < 604800) return `Hace ${Math.floor(diff/86400)} d`;
    return new Date(fecha).toLocaleDateString('es-ES');
}

function renderTarjeta(tx) {
    const userId   = <?= intval($_SESSION['user_id']) ?>;
    const esCompra = tx.comprador_id == userId;
    const otroNombre = esCompra ? tx.vendedor_nombre : tx.comprador_nombre;
    const otraFoto   = esCompra ? tx.vendedor_foto   : tx.comprador_foto;

    const imgProd = tx.producto_imagen
        ? `${BASE}/${tx.producto_imagen}`
        : `${BASE}/public/img/default.jpg`;

    const imgOtro = otraFoto
        ? `${BASE}/${otraFoto}`
        : `${BASE}/public/img/default.jpg`;

    const rolLabel = esCompra
        ? `<span class="badge text-bg-secondary rol-tag"><i class="bi bi-bag me-1"></i>Compra</span>`
        : `<span class="badge text-bg-dark rol-tag"><i class="bi bi-tag me-1"></i>Venta</span>`;

    const precio = tx.precio_final > 0
        ? `<span class="fw-semibold">${parseFloat(tx.precio_final).toFixed(2)} €</span>`
        : '';

    const chatLink = tx.chat_id
        ? `<a href="${BASE}/public/views/chat.php?id=${tx.chat_id}" class="btn btn-sm btn-outline-primary">
               <i class="bi bi-chat-dots me-1"></i>Ver chat
           </a>`
        : '';

    return `
    <div class="card shadow-sm mb-3 border-0">
        <div class="card-body d-flex gap-3 align-items-start">

            <img src="${imgProd}" alt="Producto" class="tx-img"
                 onerror="this.src='${BASE}/public/img/default.jpg'">

            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    ${rolLabel}
                    ${badgeEstado(tx.estado)}
                    <span class="text-muted small ms-auto">${fechaRelativa(tx.fecha_transaccion)}</span>
                </div>

                <div class="fw-semibold text-truncate">${tx.producto_titulo ?? 'Producto eliminado'}</div>

                <div class="d-flex align-items-center gap-2 mt-1 small text-muted">
                    <img src="${imgOtro}" class="rounded-circle"
                         width="20" height="20" style="object-fit:cover;"
                         onerror="this.src='${BASE}/public/img/default.jpg'">
                    ${escapeHtml(otroNombre ?? '')}
                    ${precio ? '·' : ''} ${precio}
                </div>

                ${tx.metodo_pago ? `
                <div class="small text-muted mt-1">
                    <i class="bi bi-credit-card me-1"></i>${metodoLabel(tx.metodo_pago)}
                    ${tx.numero_seguimiento
                        ? `· <i class="bi bi-box-seam me-1"></i><code>${tx.numero_seguimiento}</code>`
                        : ''}
                </div>` : ''}
            </div>

            <div class="d-flex flex-column gap-1 align-items-end flex-shrink-0">
                ${chatLink}
            </div>

        </div>
    </div>`;
}

function metodoLabel(m) {
    const map = {
        efectivo: 'Efectivo', transferencia: 'Transferencia',
        bizum: 'Bizum', paypal: 'PayPal', otro: 'Otro'
    };
    return map[m] ?? m;
}

function escapeHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function renderPaginacion(total, page, limit, pages) {
    const nav = document.getElementById('tx-paginacion');
    if (pages <= 1) { nav.innerHTML = ''; return; }

    let html = '<ul class="pagination pagination-sm justify-content-center">';

    html += `<li class="page-item ${page <= 1 ? 'disabled' : ''}">
                <button class="page-link" data-p="${page-1}">‹</button>
             </li>`;

    for (let i = 1; i <= pages; i++) {
        html += `<li class="page-item ${i === page ? 'active' : ''}">
                    <button class="page-link" data-p="${i}">${i}</button>
                 </li>`;
    }

    html += `<li class="page-item ${page >= pages ? 'disabled' : ''}">
                <button class="page-link" data-p="${page+1}">›</button>
             </li>`;

    html += '</ul>';
    nav.innerHTML = html;

    nav.querySelectorAll('button[data-p]').forEach(btn => {
        btn.addEventListener('click', () => {
            estado.page = parseInt(btn.dataset.p);
            cargar();
        });
    });
}

function cargar() {
    const lista = document.getElementById('tx-lista');
    lista.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Cargando...</div>';

    const params = new URLSearchParams({
        page:   estado.page,
        limit:  estado.limit,
        rol:    estado.rol,
        estado: estado.estadoFiltro,
    });

    fetch(`${BASE}/api/mis_transacciones.php?${params}`)
        .then(r => r.json())
        .then(res => {
            if (!res.data || res.data.length === 0) {
                lista.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                        <p class="mb-0">No tienes transacciones con estos filtros.</p>
                    </div>`;
                document.getElementById('tx-paginacion').innerHTML = '';
                return;
            }

            lista.innerHTML = res.data.map(renderTarjeta).join('');
            renderPaginacion(res.total, res.page, res.limit, res.pages);
        })
        .catch(() => {
            lista.innerHTML = '<div class="alert alert-danger">Error al cargar las transacciones.</div>';
        });
}

// Filtros
document.getElementById('filtro-rol').addEventListener('click', e => {
    const btn = e.target.closest('.filter-btn');
    if (!btn) return;
    document.querySelectorAll('#filtro-rol .filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    estado.rol  = btn.dataset.val;
    estado.page = 1;
    cargar();
});

document.getElementById('filtro-estado').addEventListener('change', e => {
    estado.estadoFiltro = e.target.value;
    estado.page = 1;
    cargar();
});

document.addEventListener('DOMContentLoaded', cargar);
</script>

</body>
</html>
