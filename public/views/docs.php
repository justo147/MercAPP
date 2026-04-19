<?php
session_start();
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../controllers/check_auth.php';
if (!isset($_SESSION["user_id"])) {
    header("Location: {$BASE}/public/views/auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>API Docs — MercApp</title>
  <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">
  <style>
    .method-get    { background: #0d6efd; color: #fff; }
    .method-post   { background: #198754; color: #fff; }
    .method-delete { background: #dc3545; color: #fff; }
    .method-badge  { font-size: .72rem; font-weight: 700; padding: .2em .55em;
                     border-radius: .3em; white-space: nowrap; }
    .endpoint-path { font-family: monospace; font-size: .85rem; }
    .section-title { border-left: 4px solid #0d6efd; padding-left: .6rem; }
    pre            { background: #f8f9fa; border-radius: .4rem; padding: 1rem;
                     font-size: .8rem; overflow-x: auto; }
    .params-cell code { display: inline-block; margin: 1px 2px; }
  </style>
</head>
<body>
<?php $showSearch = false; include "navbar.php"; ?>

<main class="container my-5">

  <!-- Cabecera -->
  <div class="d-flex align-items-center mb-4">
    <i class="bi bi-book-half fs-3 text-primary me-2"></i>
    <div>
      <h1 class="h3 mb-0 fw-bold">Documentación de la API</h1>
      <small class="text-muted">MercApp REST API — referencia completa de endpoints</small>
    </div>
  </div>

  <div class="alert alert-info d-flex gap-2 align-items-start">
    <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
    <div>
      Todos los endpoints requieren <strong>sesión activa</strong> salvo que se indique lo contrario.
      Las respuestas son <code>Content-Type: application/json; charset=UTF-8</code>
      excepto las exportaciones CSV. La ruta base es <code><?= htmlspecialchars($BASE) ?></code>.
    </div>
  </div>

  <!-- Índice rápido -->
  <div class="card shadow-sm mb-5">
    <div class="card-body">
      <h5 class="fw-semibold mb-3"><i class="bi bi-list-ul me-1"></i>Índice</h5>
      <div class="row g-0">
        <?php $sections = [
          'productos'      => ['Productos',       'bi-box-seam',       'primary'],
          'filtros'        => ['Filtros',          'bi-funnel',         'secondary'],
          'chat'           => ['Chat',             'bi-chat-dots',      'info'],
          'transacciones'  => ['Transacciones',    'bi-arrow-left-right','success'],
          'notificaciones' => ['Notificaciones',   'bi-bell',           'warning'],
          'valoraciones'   => ['Valoraciones',     'bi-star',           'warning'],
          'usuario'        => ['Usuario',          'bi-person',         'primary'],
          'admin'          => ['Admin',            'bi-shield-lock',    'danger'],
          'utilidades'     => ['Utilidades',       'bi-tools',          'secondary'],
        ]; ?>
        <?php foreach ($sections as $id => [$label, $icon, $color]): ?>
        <div class="col-6 col-md-4 col-lg-3 mb-2">
          <a href="#<?= $id ?>" class="text-decoration-none d-flex align-items-center gap-1 small">
            <i class="bi <?= $icon ?> text-<?= $color ?>"></i> <?= $label ?>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php
  // Helper para pintar filas de la tabla
  function row(string $method, string $path, string $desc, string $params = '—'): void {
      $colors = ['GET'=>'method-get','POST'=>'method-post','DELETE'=>'method-delete'];
      $cls = $colors[$method] ?? 'bg-secondary text-white';
      echo "<tr>
        <td><span class='method-badge {$cls}'>{$method}</span></td>
        <td class='endpoint-path'>{$path}</td>
        <td>{$desc}</td>
        <td class='params-cell small'>{$params}</td>
      </tr>";
  }
  ?>

  <!-- PRODUCTOS -->
  <h2 class="h5 section-title mb-3" id="productos">
    <i class="bi bi-box-seam text-primary me-1"></i>Productos
  </h2>
  <div class="table-responsive mb-5">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr>
      </thead>
      <tbody>
        <?php
        row('GET','/api/getProductsPaginated.php',
            'Lista productos activos con scroll infinito.',
            '<code>limit</code>, <code>offset</code>');
        row('GET','/api/search_products.php',
            'Búsqueda con filtros. Soporta proximidad por coordenadas (Haversine).',
            '<code>q</code>, <code>categoria</code>, <code>estado_producto</code>,
             <code>tipo_transaccion</code>, <code>precio_min</code>, <code>precio_max</code>,
             <code>ubicacion</code>, <code>lat</code>, <code>lon</code>,
             <code>distancia_km</code>, <code>orden</code>, <code>limit</code>, <code>offset</code>');
        row('GET','/api/mis_productos_activos.php',
            'Productos activos del usuario en sesión (para selector de intercambio).',
            '—');
        ?>
      </tbody>
    </table>
  </div>

  <!-- FILTROS -->
  <h2 class="h5 section-title mb-3" id="filtros">
    <i class="bi bi-funnel me-1"></i>Filtros
  </h2>
  <div class="table-responsive mb-5">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr>
      </thead>
      <tbody>
        <?php
        row('GET','/api/get_filters.php',
            'Devuelve categorías (con conteo de productos activos), estados de producto,
             tipos de transacción y estados de publicación.',
            '—');
        ?>
      </tbody>
    </table>
  </div>

  <!-- CHAT -->
  <h2 class="h5 section-title mb-3" id="chat">
    <i class="bi bi-chat-dots text-info me-1"></i>Chat
  </h2>
  <div class="table-responsive mb-5">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr>
      </thead>
      <tbody>
        <?php
        row('GET', '/api/chat_messages.php',
            'Mensajes de un chat (polling). Devuelve mensajes nuevos desde <code>after_id</code>.',
            '<code>chat_id</code>, <code>after_id</code>');
        row('GET', '/api/chat_unread_count.php',
            'Número total de mensajes no leídos del usuario en sesión.',
            '—');
        row('POST','/controllers/chat_send_message.php',
            'Envía un mensaje de texto o imagen a un chat.',
            '<code>chat_id</code>, <code>mensaje</code> o <code>imagen</code> (file)');
        row('POST','/controllers/chat_start_transaction.php',
            'Inicia una transacción vinculada al chat (estado inicial: <em>pendiente</em>).',
            '<code>chat_id</code>, <code>producto_id</code>');
        row('POST','/controllers/chat_update_transaction.php',
            'Avanza el estado de una transacción según la máquina de estados.
             Al completarse (<em>entregado</em>) envía email a comprador y vendedor.',
            '<code>transaccion_id</code>, <code>estado</code>, <code>chat_id</code>
             + campos opcionales según estado');
        ?>
      </tbody>
    </table>
  </div>

  <!-- TRANSACCIONES -->
  <h2 class="h5 section-title mb-3" id="transacciones">
    <i class="bi bi-arrow-left-right text-success me-1"></i>Transacciones
  </h2>
  <div class="table-responsive mb-5">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr>
      </thead>
      <tbody>
        <?php
        row('GET','/api/mis_transacciones.php',
            'Historial de transacciones del usuario con paginación y filtros de rol/estado.',
            '<code>page</code>, <code>limit</code>, <code>rol</code> (compra|venta), <code>estado</code>');
        ?>
      </tbody>
    </table>
  </div>

  <!-- NOTIFICACIONES -->
  <h2 class="h5 section-title mb-3" id="notificaciones">
    <i class="bi bi-bell text-warning me-1"></i>Notificaciones
  </h2>
  <div class="table-responsive mb-5">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr>
      </thead>
      <tbody>
        <?php
        row('GET', '/api/notificaciones.php',
            'Devuelve las últimas 15 notificaciones no leídas del usuario.',
            '—');
        row('POST','/api/notificaciones.php',
            'Marca todas las notificaciones del usuario como leídas.',
            '<code>accion=mark_all</code>');
        ?>
      </tbody>
    </table>
  </div>

  <!-- VALORACIONES -->
  <h2 class="h5 section-title mb-3" id="valoraciones">
    <i class="bi bi-star text-warning me-1"></i>Valoraciones
  </h2>
  <div class="table-responsive mb-5">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr>
      </thead>
      <tbody>
        <?php
        row('POST','/api/submit_rating.php',
            'Envía una valoración (1–5 estrellas) para el otro participante de una transacción completada.',
            '<code>transaccion_id</code>, <code>puntuacion</code>, <code>comentario</code>');
        row('GET', '/api/get_rating.php',
            'Obtiene la puntuación media y el número de reseñas de un usuario.',
            '<code>user_id</code>');
        ?>
      </tbody>
    </table>
  </div>

  <!-- USUARIO -->
  <h2 class="h5 section-title mb-3" id="usuario">
    <i class="bi bi-person text-primary me-1"></i>Usuario
  </h2>
  <div class="table-responsive mb-5">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr>
      </thead>
      <tbody>
        <?php
        row('POST','/api/set_theme.php',
            'Persiste la preferencia de tema (oscuro / claro) en la sesión PHP del servidor.',
            '<code>theme</code> (dark | light)');
        row('GET', '/api/normalize_address.php',
            'Proxy Nominatim (OpenStreetMap): normaliza direcciones españolas sin API key.
             Devuelve hasta 5 sugerencias con <code>label</code>, <code>display_name</code>,
             <code>lat</code> y <code>lon</code>. Mínimo 3 caracteres.',
            '<code>q</code>');
        ?>
      </tbody>
    </table>
  </div>

  <!-- ADMIN -->
  <h2 class="h5 section-title mb-3" id="admin">
    <i class="bi bi-shield-lock text-danger me-1"></i>Admin
    <span class="badge bg-danger ms-1" style="font-size:.7rem; vertical-align:middle;">Solo admins</span>
  </h2>
  <div class="table-responsive mb-5">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr>
      </thead>
      <tbody>
        <?php
        row('GET','/api/admin_users.php',
            'Lista usuarios con paginación, búsqueda y estadísticas.',
            '<code>page</code>, <code>limit</code>, <code>q</code>');
        row('GET','/api/admin_products.php',
            'Lista productos con paginación, búsqueda y filtro de estado.',
            '<code>page</code>, <code>limit</code>, <code>q</code>, <code>estado</code>');
        row('GET','/api/admin_reports.php',
            'Lista reportes con paginación y filtro de estado.',
            '<code>page</code>, <code>limit</code>, <code>estado</code>');
        row('GET','/api/admin_export_usuarios.php',
            'Descarga CSV (UTF-8 + BOM) con todos los usuarios. Compatible con Excel.',
            '—');
        row('GET','/api/admin_export_transacciones.php',
            'Descarga CSV (UTF-8 + BOM) con todas las transacciones y participantes.',
            '—');
        ?>
      </tbody>
    </table>
  </div>

  <!-- UTILIDADES -->
  <h2 class="h5 section-title mb-3" id="utilidades">
    <i class="bi bi-tools me-1"></i>Utilidades
  </h2>
  <div class="table-responsive mb-5">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr>
      </thead>
      <tbody>
        <?php
        row('GET','/api/check_session.php',
            'Comprueba si hay sesión activa. No requiere autenticación previa.',
            '—');
        ?>
      </tbody>
    </table>
  </div>

  <!-- Máquina de estados -->
  <h2 class="h5 section-title mb-3">Máquina de estados de transacciones</h2>
  <div class="card shadow-sm mb-5">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr><th>Estado actual</th><th>→ Siguiente estado</th><th>Actor</th><th>Campos requeridos</th></tr>
          </thead>
          <tbody>
            <tr><td><code>pendiente</code></td>     <td><code>aceptada</code></td>      <td>Comprador</td><td><code>metodo_pago</code>, <code>direccion_envio</code></td></tr>
            <tr><td><code>aceptada</code></td>      <td><code>pago_pendiente</code></td><td>Comprador</td><td>—</td></tr>
            <tr><td><code>pago_pendiente</code></td><td><code>enviado</code></td>        <td>Vendedor</td><td><code>numero_seguimiento</code> (opcional)</td></tr>
            <tr><td><code>enviado</code></td>       <td><code>entregado</code></td>      <td>Comprador</td><td>— (envía email de confirmación)</td></tr>
            <tr><td>cualquiera</td>                 <td><code>cancelada</code></td>      <td>Cualquiera</td><td>—</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Ejemplo de respuesta -->
  <h2 class="h5 section-title mb-3">Ejemplo de respuesta — <code>/api/search_products.php</code></h2>
  <pre><code>{
  "success": true,
  "data": [
    {
      "id": 42,
      "titulo": "Bicicleta de montaña Trek",
      "precio": "120.00",
      "ubicacion": "Sevilla",
      "lat": "37.3886300",
      "lon": "-5.9953400",
      "tipo_transaccion": "venta",
      "categoria": "Deportes",
      "estado_producto": "Bueno",
      "imagenes": [
        { "id": 7, "url": "uploads/products/Prod_42_abc123.webp", "orden": 1 }
      ]
    }
  ]
}</code></pre>

</main>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
