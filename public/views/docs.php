<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Documentación técnica - MercApp</title>

  <!-- Favicon -->
  <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- CSS personalizado -->
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">
  <link rel="stylesheet" href="../css/help.css">

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

  <style>
    /* ── API REST inline docs ───────────────────────── */
    #api-content { display: none; padding: 1.5rem 0; }
    .method-get    { background: #0d6efd; color: #fff; }
    .method-post   { background: #198754; color: #fff; }
    .method-delete { background: #dc3545; color: #fff; }
    .method-badge  { font-size: .72rem; font-weight: 700; padding: .2em .55em;
                     border-radius: .3em; white-space: nowrap; }
    .endpoint-path { font-family: monospace; font-size: .85rem; }
    .section-title { border-left: 4px solid #0d6efd; padding-left: .6rem; }
    pre            { background: #f8f9fa; border-radius: .4rem; padding: 1rem;
                     font-size: .8rem; overflow-x: auto; }
  </style>
</head>

<body>
  <?php
  $showSearch = false;
  include("../../public/views/navbar.php");
  ?>

  <h1 id="centro-ayuda">Documentación técnica</h1>

  <p class="d-flex justify-content-center" title="Breve descripción">
    Esta vista reúne la documentación técnica completa del proyecto,
    permitiendo un acceso rápido y centralizado a todos los detalles relevantes.
  </p>

  <h2 class="d-flex justify-content-center fs-4" title="Lenguajes documentados">Me interesa saber de...</h2>

  <ul class="nav nav-tabs justify-content-center gap-3" id="docTabs">
    <li class="nav-item">
      <button class="nav-link active" data-doc="php" title="PHP">
        <i class="bi bi-filetype-php me-1"></i>Documentación PHPDoc
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-doc="js" title="JavaScript">
        <i class="bi bi-filetype-js me-1"></i>Documentación JSDoc
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-doc="test" title="Test de rendimiento">
        <i class="bi bi-speedometer2 me-1"></i>Tests
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-doc="api" title="API REST">
        <i class="bi bi-braces me-1"></i>API REST
      </button>
    </li>
  </ul>

  <!-- Iframe para PHP / JS / Tests -->
  <div class="container-fluid mt-4 px-4" id="iframe-content">
    <div class="ratio ratio-16x9">
      <iframe
        id="docFrame"
        src="../../docs/php/index.html"
        class="rounded"
        style="border: none; width: 100%; height: 100%;">
      </iframe>
    </div>
  </div>

  <!-- Contenido inline para la pestaña API REST -->
  <div class="container my-4" id="api-content">

    <div class="alert alert-info d-flex gap-2 align-items-start">
      <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
      <div>
        Todos los endpoints requieren <strong>sesión activa</strong> salvo que se indique lo contrario.
        Las respuestas son <code>application/json; charset=UTF-8</code> excepto las exportaciones CSV.
      </div>
    </div>

    <?php
    function apiRow(string $method, string $path, string $desc, string $params = '—'): void {
        $colors = ['GET' => 'method-get', 'POST' => 'method-post', 'DELETE' => 'method-delete'];
        $cls = $colors[$method] ?? 'bg-secondary text-white';
        echo "<tr>
          <td><span class='method-badge {$cls}'>{$method}</span></td>
          <td class='endpoint-path'>{$path}</td>
          <td>{$desc}</td>
          <td class='small'>{$params}</td>
        </tr>";
    }
    ?>

    <!-- Productos -->
    <h2 class="h5 section-title mb-3" id="api-productos">
      <i class="bi bi-box-seam text-primary me-1"></i>Productos
    </h2>
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light"><tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr></thead>
        <tbody>
          <?php
          apiRow('GET','/api/getProductsPaginated.php',
              'Lista productos activos con scroll infinito.',
              '<code>limit</code>, <code>offset</code>');
          apiRow('GET','/api/search_products.php',
              'Búsqueda con filtros. Soporta proximidad por coordenadas (Haversine).',
              '<code>q</code>, <code>categoria</code>, <code>estado_producto</code>,
               <code>tipo_transaccion</code>, <code>precio_min</code>, <code>precio_max</code>,
               <code>ubicacion</code>, <code>lat</code>, <code>lon</code>,
               <code>distancia_km</code>, <code>orden</code>, <code>limit</code>, <code>offset</code>');
          apiRow('GET','/api/mis_productos_activos.php',
              'Productos activos del usuario en sesión (selector de intercambio).',
              '—');
          apiRow('GET','/api/get_filters.php',
              'Categorías (con conteo de activos), estados de producto, tipos de transacción y estados de publicación.',
              '—');
          ?>
        </tbody>
      </table>
    </div>

    <!-- Chat y Transacciones -->
    <h2 class="h5 section-title mb-3">
      <i class="bi bi-chat-dots text-info me-1"></i>Chat y Transacciones
    </h2>
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light"><tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr></thead>
        <tbody>
          <?php
          apiRow('GET', '/api/chat_messages.php',
              'Mensajes de un chat (polling) desde un ID concreto.',
              '<code>chat_id</code>, <code>after_id</code>');
          apiRow('GET', '/api/chat_unread_count.php',
              'Número total de mensajes no leídos del usuario.', '—');
          apiRow('POST','/controllers/chat_send_message.php',
              'Envía un mensaje de texto o imagen.',
              '<code>chat_id</code>, <code>mensaje</code> / <code>imagen</code>');
          apiRow('POST','/controllers/chat_start_transaction.php',
              'Inicia una transacción vinculada al chat (estado inicial: <em>pendiente</em>).',
              '<code>chat_id</code>, <code>producto_id</code>');
          apiRow('POST','/controllers/chat_update_transaction.php',
              'Avanza el estado. Al llegar a <em>entregado</em> envía email a ambos participantes.',
              '<code>transaccion_id</code>, <code>estado</code>, <code>chat_id</code> + extras');
          apiRow('GET', '/api/mis_transacciones.php',
              'Historial de transacciones del usuario con paginación.',
              '<code>page</code>, <code>limit</code>, <code>rol</code>, <code>estado</code>');
          ?>
        </tbody>
      </table>
    </div>

    <!-- Notificaciones, Valoraciones y Usuario -->
    <h2 class="h5 section-title mb-3">
      <i class="bi bi-bell text-warning me-1"></i>Notificaciones, Valoraciones y Usuario
    </h2>
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light"><tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr></thead>
        <tbody>
          <?php
          apiRow('GET', '/api/notificaciones.php',
              'Últimas 15 notificaciones no leídas del usuario.', '—');
          apiRow('POST','/api/notificaciones.php',
              'Marca todas las notificaciones como leídas.', '<code>accion=mark_all</code>');
          apiRow('POST','/api/submit_rating.php',
              'Envía valoración (1–5 estrellas) para el otro participante.',
              '<code>transaccion_id</code>, <code>puntuacion</code>, <code>comentario</code>');
          apiRow('GET', '/api/get_rating.php',
              'Puntuación media y número de reseñas de un usuario.', '<code>user_id</code>');
          apiRow('POST','/api/set_theme.php',
              'Persiste la preferencia de tema en la sesión del servidor.',
              '<code>theme</code> (dark | light)');
          apiRow('GET', '/api/normalize_address.php',
              'Proxy Nominatim: normaliza y geocodifica direcciones españolas.',
              '<code>q</code> (mín. 3 caracteres)');
          apiRow('GET', '/api/check_session.php',
              'Comprueba si hay sesión activa. No requiere autenticación.', '—');
          ?>
        </tbody>
      </table>
    </div>

    <!-- Admin -->
    <h2 class="h5 section-title mb-3">
      <i class="bi bi-shield-lock text-danger me-1"></i>Admin
      <span class="badge bg-danger ms-1" style="font-size:.7rem;vertical-align:middle;">Solo admins</span>
    </h2>
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light"><tr><th>Método</th><th>Ruta</th><th>Descripción</th><th>Parámetros</th></tr></thead>
        <tbody>
          <?php
          apiRow('GET','/api/admin_users.php',
              'Lista usuarios con paginación y búsqueda.',
              '<code>page</code>, <code>limit</code>, <code>q</code>');
          apiRow('GET','/api/admin_products.php',
              'Lista productos con paginación, búsqueda y filtro.',
              '<code>page</code>, <code>limit</code>, <code>q</code>, <code>estado</code>');
          apiRow('GET','/api/admin_reports.php',
              'Lista reportes con paginación y filtro de estado.',
              '<code>page</code>, <code>limit</code>, <code>estado</code>');
          apiRow('GET','/api/admin_export_usuarios.php',
              'Descarga CSV (UTF-8 + BOM Excel) con todos los usuarios.', '—');
          apiRow('GET','/api/admin_export_transacciones.php',
              'Descarga CSV (UTF-8 + BOM Excel) con todas las transacciones.', '—');
          ?>
        </tbody>
      </table>
    </div>

    <!-- Máquina de estados -->
    <h2 class="h5 section-title mb-3">Máquina de estados de transacciones</h2>
    <div class="card shadow-sm mb-4">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr><th>Estado actual</th><th>→ Siguiente</th><th>Actor</th><th>Campos extra</th></tr>
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

    <!-- Ejemplo JSON -->
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
      "imagenes": [
        { "url": "uploads/products/Prod_42_abc.webp", "orden": 1 }
      ]
    }
  ]
}</code></pre>

  </div><!-- /api-content -->

  <script>
    const docFrame  = document.getElementById("docFrame");
    const iframeCtn = document.getElementById("iframe-content");
    const apiCtn    = document.getElementById("api-content");
    const tabs      = document.querySelectorAll("#docTabs .nav-link");

    tabs.forEach(tab => {
      tab.addEventListener("click", () => {
        tabs.forEach(t => t.classList.remove("active"));
        tab.classList.add("active");

        const tipo = tab.dataset.doc;

        if (tipo === "api") {
          iframeCtn.style.display = "none";
          apiCtn.style.display    = "block";
        } else {
          iframeCtn.style.display = "block";
          apiCtn.style.display    = "none";

          if (tipo === "php") {
            docFrame.src = "../../docs/php/index.html";
          } else if (tipo === "js") {
            docFrame.src = "../../docs/js/index.html";
          } else if (tipo === "test") {
            docFrame.src = "../../docs/tests/index_test.html";
          }
        }
      });
    });
  </script>

  <footer>
    <?php include __DIR__ . '/footer.php'; ?>
  </footer>
</body>
</html>
