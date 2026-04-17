<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Report.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Acceso denegado."]);
    exit;
}

$data      = json_decode(file_get_contents("php://input"), true);
$report_id = isset($data['id'])     ? intval($data['id'])   : 0;
$estado    = isset($data['estado']) ? trim($data['estado']) : '';

$valid_states = ['pendiente', 'revisado', 'rechazado'];

if ($report_id <= 0 || !in_array($estado, $valid_states)) {
    echo json_encode(["success" => false, "error" => "Datos inválidos."]);
    exit;
}

try {
    $db          = new Database();
    $conn        = $db->getConnection();
    $reportModel = new Report($conn);

    $result = $reportModel->updateStatus($report_id, $estado, (int) $_SESSION['user_id']);

    if ($result) {
        echo json_encode(["success" => true, "message" => "Reporte actualizado correctamente."]);
    } else {
        echo json_encode(["success" => false, "error" => "No se pudo actualizar el reporte."]);
    }

} catch (Exception $e) {
    error_log("admin_update_report error: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Error interno del servidor."]);
}
