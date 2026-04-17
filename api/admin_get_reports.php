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
$estado = isset($_GET["estado"]) ? trim($_GET["estado"]) : "";

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $conditions = [];
    $params     = [];

    if (!empty($estado)) {
        $conditions[] = "r.estado = :estado";
        $params[':estado'] = $estado;
    }

    $where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT
                r.id, r.motivo, r.fecha, r.estado,
                u.id    AS reportador_id,
                u.nombre AS reportador_nombre,
                u.email  AS reportador_email,
                p.id    AS producto_id,
                p.titulo AS producto_titulo,
                pu.id    AS propietario_id,
                pu.nombre AS propietario_nombre,
                pu.email  AS propietario_email
            FROM Reportes r
            JOIN usuario u          ON r.usuario_reportador = u.id
            LEFT JOIN Productos p   ON r.producto_id        = p.id
            LEFT JOIN usuario pu    ON p.usuario_id         = pu.id
            $where
            ORDER BY
                CASE r.estado WHEN 'pendiente' THEN 0 ELSE 1 END,
                r.fecha DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countSql  = "SELECT COUNT(*) FROM Reportes r $where";
    $countStmt = $conn->prepare($countSql);
    foreach ($params as $k => $v) {
        $countStmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    echo json_encode([
        "success" => true,
        "data"    => $reports,
        "total"   => $total,
        "page"    => $page,
        "limit"   => $limit,
    ]);

} catch (Exception $e) {
    error_log("admin_get_reports error: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Error interno del servidor."]);
}
