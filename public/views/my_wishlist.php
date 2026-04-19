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

// Cargar categorías y estados para el formulario
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
</head>
<body>

<?php $showSearch = false; include "navbar.php"; ?>

<div class="container py-4" style="max-width:700px;">

  <div class="d-flex align-items-center mb-4">
    <i class="bi bi-stars fs-3 text-warning me-2"></i>
    <div>
      <h4 class="fw-bold mb-0">Mi lista de deseos</h4>
      <small class="text-muted">Lo que buscas para intercambiar o conseguir</small>
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
          <label class="form-label fw-semibold">¿Qué buscas? <span class="text-danger">*</span></label>
          <input type="text" id="etiquetas" class="form-control"
                 placeholder="Ej: bicicleta de montaña, cámara analógica, libros de PHP…"
                 maxlength="200" required>
          <div class="form-text">Describe con palabras clave lo que te gustaría conseguir.</div>
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
        <div class="mt-3 d-flex gap-2">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-plus-lg me-1"></i>Añadir deseo
          </button>
        </div>
      </form>
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

  cont.innerHTML = `
    <h6 class="fw-semibold text-muted mb-3">
      <i class="bi bi-list-ul me-1"></i>${json.data.length} deseo(s) en tu lista
    </h6>
    <div class="list-group shadow-sm" id="deseos-list"></div>`;

  const list = document.getElementById('deseos-list');
  json.data.forEach(d => {
    const li = document.createElement('div');
    li.className = 'list-group-item d-flex align-items-start gap-3 py-3';
    li.innerHTML = `
      <i class="bi bi-stars text-warning fs-5 mt-1 flex-shrink-0"></i>
      <div class="flex-grow-1">
        <div class="fw-semibold">${escHtml(d.etiquetas)}</div>
        <div class="small text-muted mt-1">
          ${d.categoria_nombre ? `<span class="badge bg-secondary me-1">${escHtml(d.categoria_nombre)}</span>` : ''}
          ${d.estado_producto_nombre ? `<span class="badge bg-light text-dark border">${escHtml(d.estado_producto_nombre)}</span>` : ''}
        </div>
      </div>
      <button class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="eliminarDeseo(${d.id})" title="Eliminar">
        <i class="bi bi-trash"></i>
      </button>`;
    list.appendChild(li);
  });
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str ?? '';
  return d.innerHTML;
}

async function eliminarDeseo(id) {
  if (!confirm('¿Eliminar este deseo?')) return;
  const fd = new FormData();
  fd.append('accion', 'delete');
  fd.append('id', id);
  await fetch(`${BASE}/api/deseos.php`, { method: 'POST', body: fd });
  cargarDeseos();
}

document.getElementById('form-deseo').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData();
  fd.append('accion',            'add');
  fd.append('etiquetas',         document.getElementById('etiquetas').value);
  fd.append('categoria_id',      document.getElementById('categoria_id').value);
  fd.append('estado_producto_id',document.getElementById('estado_producto_id').value);

  const res  = await fetch(`${BASE}/api/deseos.php`, { method: 'POST', body: fd });
  const json = await res.json();
  if (json.ok) {
    document.getElementById('etiquetas').value = '';
    document.getElementById('categoria_id').selectedIndex = 0;
    document.getElementById('estado_producto_id').selectedIndex = 0;
    cargarDeseos();
  } else {
    alert(json.error ?? 'Error al guardar');
  }
});

cargarDeseos();
</script>
</body>
</html>
