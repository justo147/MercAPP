<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Acceso denegado."]);
    exit;
}

$limit  = isset($_GET["limit"]) ? max(1, intval($_GET["limit"])) : 10;
$page   = isset($_GET["page"])  ? max(1, intval($_GET["page"]))  : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET["q"])      ? trim($_GET["q"])      : "";
$estado = isset($_GET["estado"]) ? trim($_GET["estado"]) : "";

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $conditions = [];
    $params     = [];

    if (!empty($search)) {
        $conditions[] = "(p.titulo LIKE :search OR u.nombre LIKE :search OR u.email LIKE :search)";
        $params[':search'] = "%$search%";
    }
    if (!empty($estado)) {
        $conditions[] = "ep.nombre = :estado";
        $params[':estado'] = $estado;
    }

    $where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT
                p.id, p.titulo, p.precio, p.tipo_transaccion, p.fecha_publicacion,
                u.id   AS usuario_id,
                u.nombre AS usuario_nombre,
                u.email  AS usuario_email,
                c.nombre AS categoria,
                ep.nombre   AS estado_publicacion,
                eprod.nombre AS estado_producto,
                (SELECT url FROM Imagenes_prod WHERE id_producto = p.id ORDER BY orden ASC LIMIT 1) AS imagen
            FROM Productos p
            JOIN usuario u         ON p.usuario_id           = u.id
            JOIN Categorias c      ON p.categoria_id         = c.id
            JOIN EstadoPublicacion ep   ON p.estado_publicacion_id = ep.id
            JOIN EstadoProducto    eprod ON p.estado_producto_id   = eprod.id
            $where
            ORDER BY p.fecha_publicacion DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countSql = "SELECT COUNT(*)
                 FROM Productos p
                 JOIN usuario u ON p.usuario_id = u.id
                 JOIN EstadoPublicacion ep ON p.estado_publicacion_id = ep.id
                 $where";
    $countStmt = $conn->prepare($countSql);
    foreach ($params as $k => $v) {
        $countStmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    echo json_encode([
        "success" => true,
        "data"    => $products,
        "total"   => $total,
        "page"    => $page,
        "limit"   => $limit,
    ]);

} catch (Exception $e) {
    error_log("admin_get_products error: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Error interno del servidor."]);
}
