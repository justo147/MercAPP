<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Acceso denegado."]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Estadísticas de usuarios
    $stmt = $conn->query("SELECT estado, COUNT(*) AS total FROM usuario GROUP BY estado");
    $usersByState = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $usersByState[$row['estado']] = (int) $row['total'];
    }

    // Estadísticas de productos
    $stmt = $conn->query(
        "SELECT ep.nombre, COUNT(*) AS total
         FROM Productos p
         JOIN EstadoPublicacion ep ON p.estado_publicacion_id = ep.id
         GROUP BY ep.nombre"
    );
    $prodsByState = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $prodsByState[$row['nombre']] = (int) $row['total'];
    }

    // Estadísticas de reportes
    $stmt = $conn->query("SELECT estado, COUNT(*) AS total FROM Reportes GROUP BY estado");
    $reportsByState = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $reportsByState[$row['estado']] = (int) $row['total'];
    }

    echo json_encode([
        "success" => true,
        "stats" => [
            "usuarios" => [
                "total"       => array_sum($usersByState),
                "activos"     => $usersByState['activo'] ?? 0,
                "suspendidos" => $usersByState['suspendido'] ?? 0,
                "eliminados"  => $usersByState['eliminado'] ?? 0,
            ],
            "productos" => [
                "total"    => array_sum($prodsByState),
                "activos"  => $prodsByState['activo'] ?? 0,
                "pausados" => $prodsByState['pausado'] ?? 0,
                "vendidos" => $prodsByState['vendido'] ?? 0,
            ],
            "reportes" => [
                "total"      => array_sum($reportsByState),
                "pendientes" => $reportsByState['pendiente'] ?? 0,
                "revisados"  => $reportsByState['revisado'] ?? 0,
                "rechazados" => $reportsByState['rechazado'] ?? 0,
            ],
        ]
    ]);

} catch (Exception $e) {
    error_log("admin_stats error: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Error interno del servidor."]);
}
