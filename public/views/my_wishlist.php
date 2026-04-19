<?php
session_start();
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../controllers/check_auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: {$BASE}/public/views/auth/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';
$db   = new Database();
$conn = $db->getConnection();

$categorias      = $conn->query("SELECT id, nombre FROM Categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$estadosProducto = $conn->query("SELECT id, nombre FROM EstadoProducto ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi lista de deseos — MercApp</title>

  <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">
  <style>
    .match-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:.75rem; }
    .match-card { border-radius:.5rem; overflow:hidden; border:1px solid #dee2e6; transition:.15s; }
    .match-card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.1); }
    .match-card img { width:100%; height:110px; object-fit:cover; }
    .coincidencias-wrap { display:none; }   /* se abre por JS */
  </style>
</head>
<body>

<?php $showSearch = false; include "navbar.php"; ?>

<div class="container py-4" style="max-width:760px;">

  <div class="d-flex align-items-center mb-4">
    <i class="bi bi-stars fs-3 text-warning me-2"></i>
    <div>
      <h4 class="fw-bold mb-0">Mi lista de deseos</h4>
      <small class="text-muted">Añade lo que buscas; te avisamos cuando aparezca en el catálogo</small>
    </div>
  </div>

  <!-- Formulario añadir -->
  <div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold">
      <i class="bi bi-plus-circle me-1 text-success"></i>Añadir nuevo deseo
    </div>
    <div class="card-body">
      <form id="form-deseo">
        <div class="mb-3">
          <label class="form-label fw-semibold">
            ¿Qué buscas? <span class="text-danger">*</span>
          </label>
          <input type="text" id="etiquetas" class="form-control"
                 placeholder="Ej: bicicleta montaña, cámara analógica, libros PHP…"
                 maxlength="200" required>
          <div class="form-text">
            Palabras clave separadas por comas o espacios.
            Buscamos en el título y descripción de los productos activos.
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Categoría preferida</label>
            <select id="categoria_id" class="form-select">
              <option value="">Cualquier categoría</option>
              <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Estado del artículo</label>
            <select id="estado_producto_id" class="form-select">
              <option value="">Cualquier estado</option>
              <?php foreach ($estadosProducto as $ep): ?>
                <option value="<?= $ep['id'] ?>"><?= htmlspecialchars($ep['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="mt-3">
          <button type="submit" class="btn btn-success" id="btn-add">
            <i class="bi bi-plus-lg me-1"></i>Añadir deseo
          </button>
        </div>
      </form>
      <!-- Coincidencias inmediatas al añadir -->
      <div id="result-add" class="mt-3"></div>
    </div>
  </div>

  <!-- Lista de deseos -->
  <div id="lista-deseos">
    <div class="text-center py-4 text-muted">
      <div class="spinner-border spinner-border-sm me-2"></div> Cargando…
    </div>
  </div>

</div>

<?php include __DIR__ . '/footer.php'; ?>

<script>
// BASE ya está declarado por navbar.php

/* ── helpers ───────────────────────────────────────────────── */
function esc(str) {
  const d = document.createElement('div');
  d.textContent = str ?? '';
  return d.innerHTML;
}

function badgeTipo(tipo) {
  const m = { venta:'primary', intercambio:'warning', mixto:'success' };
  const c = m[tipo] ?? 'secondary';
  return `<span class="badge bg-${c}" style="font-size:.65rem;">${esc(tipo)}</span>`;
}

function renderMatchGrid(productos) {
  if (!productos.length) return '<p class="text-muted small mb-0">Sin productos coincidentes ahora mismo.</p>';
  const DEFAULT = `${BASE}/public/img/default.jpg`;
  return `<div class="match-grid">` + productos.map(p => {
    const img = p.imagen ? `${BASE}/${esc(p.imagen)}` : DEFAULT;
    const precio = p.precio ? `${parseFloat(p.precio).toFixed(2)} €` : 'Gratis';
    return `
      <a href="${BASE}/public/views/detail_product.php?id=${p.id}"
         class="match-card text-decoration-none text-dark" target="_blank">
        <img src="${img}" alt="${esc(p.titulo)}"
             onerror="this.src='${DEFAULT}'">
        <div class="p-2">
          <div class="small fw-semibold text-truncate" title="${esc(p.titulo)}">${esc(p.titulo)}</div>
          <div class="small text-primary fw-bold">${precio}</div>
          <div class="mt-1 d-flex gap-1 flex-wrap">
            ${badgeTipo(p.tipo_transaccion)}
            <span class="badge bg-light text-dark border" style="font-size:.62rem;">${esc(p.estado_producto)}</span>
          </div>
          <div class="text-muted mt-1" style="font-size:.7rem;">
            <i class="bi bi-person me-1"></i>${esc(p.vendedor_nombre)}
          </div>
        </div>
      </a>`;
  }).join('') + '</div>';
}

/* ── cargar y renderizar deseos ─────────────────────────────── */
async function cargarDeseos() {
  const res  = await fetch(`${BASE}/api/deseos.php`);
  const json = await res.json();
  const cont = document.getElementById('lista-deseos');

  if (!json.data || json.data.length === 0) {
    cont.innerHTML = `
      <div class="text-center py-5 text-muted">
        <i class="bi bi-stars fs-1 d-block mb-3 opacity-25"></i>
        <p>Todavía no tienes deseos. ¡Añade uno arriba!</p>
      </div>`;
    return;
  }

  const totalCoincidencias = json.data.reduce((s, d) => s + (d.coincidencias || 0), 0);
  cont.innerHTML = `
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h6 class="fw-semibold text-muted mb-0">
        <i class="bi bi-list-ul me-1"></i>${json.data.length} deseo(s)
      </h6>
      ${totalCoincidencias > 0
        ? `<span class="badge bg-success">${totalCoincidencias} coincidencia(s) en catálogo</span>`
        : ''}
    </div>
    <div id="deseos-list" class="d-flex flex-column gap-3"></div>`;

  const list = document.getElementById('deseos-list');

  json.data.forEach(d => {
    const hasMatch = d.coincidencias > 0;
    const card = document.createElement('div');
    card.className = 'card shadow-sm border' + (hasMatch ? ' border-success' : '');
    card.dataset.id = d.id;
    card.innerHTML = `
      <div class="card-body py-3">
        <div class="d-flex align-items-start gap-3">
          <i class="bi bi-stars ${hasMatch ? 'text-success' : 'text-warning'} fs-5 mt-1 flex-shrink-0"></i>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold">${esc(d.etiquetas)}</div>
            <div class="small text-muted mt-1 d-flex flex-wrap gap-1">
              ${d.categoria_nombre  ? `<span class="badge bg-secondary">${esc(d.categoria_nombre)}</span>`                  : ''}
              ${d.estado_producto_nombre ? `<span class="badge bg-light text-dark border">${esc(d.estado_producto_nombre)}</span>` : ''}
            </div>
          </div>
          <div class="d-flex gap-2 flex-shrink-0 align-items-center">
            ${hasMatch ? `
              <button class="btn btn-sm btn-success btn-ver-matches"
                      data-id="${d.id}" data-open="0">
                <i class="bi bi-search me-1"></i>${d.coincidencias} coincidencia${d.coincidencias > 1 ? 's' : ''}
              </button>` : `
              <span class="small text-muted">Sin resultados aún</span>`}
            <button class="btn btn-sm btn-outline-danger btn-del" data-id="${d.id}" title="Eliminar">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
        <!-- Panel de coincidencias (oculto) -->
        <div class="coincidencias-wrap mt-3" id="matches-${d.id}">
          <div class="text-center py-2 text-muted small">
            <div class="spinner-border spinner-border-sm me-1"></div>Cargando productos…
          </div>
        </div>
      </div>`;
    list.appendChild(card);
  });

  // Eventos: ver coincidencias
  list.querySelectorAll('.btn-ver-matches').forEach(btn => {
    btn.addEventListener('click', () => toggleMatches(btn));
  });

  // Eventos: eliminar
  list.querySelectorAll('.btn-del').forEach(btn => {
    btn.addEventListener('click', () => eliminarDeseo(btn.dataset.id));
  });
}

/* ── toggle del panel de coincidencias ─────────────────────── */
async function toggleMatches(btn) {
  const id    = btn.dataset.id;
  const panel = document.getElementById(`matches-${id}`);
  const open  = btn.dataset.open === '1';

  if (open) {
    panel.style.display = 'none';
    btn.dataset.open    = '0';
    btn.innerHTML       = btn.innerHTML.replace('Ocultar', btn.dataset.label ?? 'coincidencias');
    return;
  }

  panel.style.display = 'block';
  btn.dataset.open    = '1';
  if (!btn.dataset.label) btn.dataset.label = btn.textContent.trim();

  // Si ya tiene contenido cargado, solo mostrar
  if (panel.dataset.loaded === '1') return;

  try {
    const res  = await fetch(`${BASE}/api/deseos.php?accion=matches&id=${id}`);
    const json = await res.json();
    panel.innerHTML = renderMatchGrid(json.data || []);
    panel.dataset.loaded = '1';

    // Actualizar texto del botón con el conteo real
    const n = json.total ?? 0;
    btn.innerHTML = `<i class="bi bi-chevron-up me-1"></i>Ocultar (${n})`;
  } catch {
    panel.innerHTML = '<p class="text-danger small">Error al cargar coincidencias.</p>';
  }
}

/* ── eliminar deseo ─────────────────────────────────────────── */
async function eliminarDeseo(id) {
  if (!confirm('¿Eliminar este deseo?')) return;
  const fd = new FormData();
  fd.append('accion', 'delete');
  fd.append('id', id);
  await fetch(`${BASE}/api/deseos.php`, { method: 'POST', body: fd });
  cargarDeseos();
}

/* ── añadir deseo ───────────────────────────────────────────── */
document.getElementById('form-deseo').addEventListener('submit', async e => {
  e.preventDefault();
  const btn      = document.getElementById('btn-add');
  const resultEl = document.getElementById('result-add');
  btn.disabled   = true;
  btn.innerHTML  = '<span class="spinner-border spinner-border-sm me-1"></span>Buscando…';

  const fd = new FormData();
  fd.append('accion',             'add');
  fd.append('etiquetas',          document.getElementById('etiquetas').value);
  fd.append('categoria_id',       document.getElementById('categoria_id').value);
  fd.append('estado_producto_id', document.getElementById('estado_producto_id').value);

  try {
    const res  = await fetch(`${BASE}/api/deseos.php`, { method: 'POST', body: fd });
    const json = await res.json();

    if (!json.ok) {
      resultEl.innerHTML = `<div class="alert alert-danger">${esc(json.error ?? 'Error al guardar')}</div>`;
      return;
    }

    // Reset formulario
    e.target.reset();

    // Mostrar resultado inmediato
    if (json.coincidencias > 0) {
      resultEl.innerHTML = `
        <div class="alert alert-success d-flex align-items-center gap-2">
          <i class="bi bi-check-circle-fill fs-5"></i>
          <div>
            Deseo añadido. <strong>¡Hay ${json.coincidencias} producto(s) en el catálogo que ya coinciden!</strong>
            Puedes verlos en tu lista.
          </div>
        </div>`;
    } else {
      resultEl.innerHTML = `
        <div class="alert alert-info d-flex align-items-center gap-2">
          <i class="bi bi-bell fs-5"></i>
          <div>Deseo añadido. Te avisaremos cuando aparezca algo que encaje.</div>
        </div>`;
    }

    cargarDeseos();
  } catch {
    resultEl.innerHTML = '<div class="alert alert-danger">Error de conexión.</div>';
  } finally {
    btn.disabled  = false;
    btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Añadir deseo';
  }
});

cargarDeseos();
</script>
</body>
</html>
