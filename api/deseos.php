<?php
/**
 * API de Deseos (wishlist de intercambio).
 *
 * GET                  → lista los deseos del usuario en sesión
 * GET ?accion=matches  → productos que coinciden con UN deseo concreto
 * POST accion=add      → añade un deseo
 * POST accion=delete   → elimina un deseo
 */
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$uid  = intval($_SESSION['user_id']);
$db   = new Database();
$conn = $db->getConnection();

/* ═══════════════════════════════════════════════════════════════
   GET: listar deseos  /  GET?accion=matches&id=X  buscar matches
═══════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $accion = trim($_GET['accion'] ?? '');

    /* ── buscar productos que coinciden con un deseo ── */
    if ($accion === 'matches') {
        $deseoId = intval($_GET['id'] ?? 0);
        if (!$deseoId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de deseo requerido']);
            exit;
        }

        // Obtener el deseo (solo del propio usuario)
        $stmt = $conn->prepare(
            "SELECT * FROM Deseos WHERE id = :id AND usuario_id = :uid LIMIT 1"
        );
        $stmt->execute([':id' => $deseoId, ':uid' => $uid]);
        $deseo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$deseo) {
            http_response_code(404);
            echo json_encode(['error' => 'Deseo no encontrado']);
            exit;
        }

        $productos = buscarCoincidencias($conn, $deseo, $uid);
        echo json_encode(['data' => $productos, 'total' => count($productos)]);
        exit;
    }

    /* ── listar todos los deseos con su conteo de coincidencias ── */
    $stmt = $conn->prepare(
        "SELECT d.id, d.etiquetas, d.categoria_id, d.estado_producto_id,
                c.nombre  AS categoria_nombre,
                ep.nombre AS estado_producto_nombre
         FROM Deseos d
         LEFT JOIN Categorias c      ON c.id  = d.categoria_id
         LEFT JOIN EstadoProducto ep ON ep.id = d.estado_producto_id
         WHERE d.usuario_id = :uid
         ORDER BY d.id DESC"
    );
    $stmt->execute([':uid' => $uid]);
    $deseos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Añadir conteo de coincidencias a cada deseo
    foreach ($deseos as &$d) {
        $d['coincidencias'] = count(buscarCoincidencias($conn, $d, $uid));
    }
    unset($d);

    echo json_encode(['data' => $deseos]);
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   POST
═══════════════════════════════════════════════════════════════ */
$accion = trim($_POST['accion'] ?? '');

if ($accion === 'add') {
    $etiquetas        = trim($_POST['etiquetas']            ?? '');
    $categoriaId      = intval($_POST['categoria_id']       ?? 0) ?: null;
    $estadoProductoId = intval($_POST['estado_producto_id'] ?? 0) ?: null;

    if (empty($etiquetas)) {
        http_response_code(400);
        echo json_encode(['error' => 'Las etiquetas son obligatorias']);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO Deseos (usuario_id, categoria_id, estado_producto_id, etiquetas)
         VALUES (:uid, :cat, :ep, :etiq)"
    );
    $ok = $stmt->execute([
        ':uid'  => $uid,
        ':cat'  => $categoriaId,
        ':ep'   => $estadoProductoId,
        ':etiq' => $etiquetas,
    ]);

    $nuevoId = (int)$conn->lastInsertId();

    // Contar coincidencias inmediatas para devolver al frontend
    $coincidencias = 0;
    if ($ok) {
        $deseo         = ['etiquetas' => $etiquetas, 'categoria_id' => $categoriaId, 'estado_producto_id' => $estadoProductoId];
        $coincidencias = count(buscarCoincidencias($conn, $deseo, $uid));
    }

    echo json_encode(['ok' => $ok, 'id' => $nuevoId, 'coincidencias' => $coincidencias]);
    exit;
}

if ($accion === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requerido']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM Deseos WHERE id = :id AND usuario_id = :uid");
    $ok   = $stmt->execute([':id' => $id, ':uid' => $uid]);
    echo json_encode(['ok' => $ok]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Acción no reconocida']);

/* ═══════════════════════════════════════════════════════════════
   FUNCIÓN COMPARTIDA: buscar productos que encajan con un deseo
   - Coincidencia de keywords (etiquetas) en título O descripción
   - Opcionalmente filtrada por categoria_id y estado_producto_id
   - Solo productos activos de otros usuarios
═══════════════════════════════════════════════════════════════ */
function buscarCoincidencias(PDO $conn, array $deseo, int $usuarioId): array
{
    // Extraer palabras clave: separar por coma, espacio o punto y coma
    $palabras = array_filter(
        array_map('trim', preg_split('/[\s,;]+/', $deseo['etiquetas'] ?? '')),
        fn($p) => mb_strlen($p) >= 2
    );

    if (empty($palabras)) return [];

    // Construir cláusulas LIKE para título y descripción
    $wherePalabras = [];
    $params        = [':uid' => $usuarioId];

    foreach ($palabras as $i => $p) {
        $key              = ":kw{$i}";
        $params[$key]     = "%{$p}%";
        $wherePalabras[]  = "(p.titulo LIKE {$key} OR p.descripcion LIKE {$key})";
    }

    $sqlKw = '(' . implode(' OR ', $wherePalabras) . ')';

    // Filtro de categoría (solo si el deseo la especifica)
    $sqlCat = '';
    if (!empty($deseo['categoria_id'])) {
        $params[':cat']  = intval($deseo['categoria_id']);
        $sqlCat          = ' AND p.categoria_id = :cat';
    }

    // Filtro de estado del producto (solo si el deseo lo especifica)
    $sqlEP = '';
    if (!empty($deseo['estado_producto_id'])) {
        $params[':ep'] = intval($deseo['estado_producto_id']);
        $sqlEP         = ' AND p.estado_producto_id = :ep';
    }

    $sql = "
        SELECT p.id, p.titulo, p.precio, p.ubicacion, p.tipo_transaccion,
               c.nombre  AS categoria,
               ep.nombre AS estado_producto,
               u.nombre  AS vendedor_nombre,
               u.foto_perfil AS vendedor_foto,
               img.url   AS imagen
        FROM Productos p
        JOIN Categorias c         ON c.id  = p.categoria_id
        JOIN EstadoProducto ep    ON ep.id = p.estado_producto_id
        JOIN EstadoPublicacion epu ON epu.id = p.estado_publicacion_id
        JOIN usuario u            ON u.id  = p.usuario_id
        LEFT JOIN Imagenes_prod img ON img.id_producto = p.id AND img.orden = 1
        WHERE epu.nombre = 'activo'
          AND p.usuario_id != :uid
          AND {$sqlKw}
          {$sqlCat}
          {$sqlEP}
        ORDER BY p.fecha_publicacion DESC
        LIMIT 20
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
