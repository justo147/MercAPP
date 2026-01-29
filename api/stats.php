<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/User.php';

$userId = intval($_GET["id"] ?? 0);

if ($userId <= 0) {
    echo json_encode([
        "success" => false,
        "error" => "ID inválido"
    ]);
    exit;
}

try {
    // Conexión a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // Modelo User
    $usuarioModel = new User($conn);

    // Obtener estadísticas completas
    $stats = $usuarioModel->obtenerEstadisticas($userId);

    echo json_encode([
        "success" => true,
        "data" => $stats
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "error" => "Error en el servidor",
        "details" => $e->getMessage()
    ]);
}
