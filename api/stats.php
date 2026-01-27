<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/User.php';

header("Content-Type: application/json");

$userId = intval($_GET["id"] ?? 0);

if ($userId <= 0) {
    echo json_encode(["error" => "ID inválido"]);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $usuarioModel = new Usuario($pdo);
    $stats = $usuarioModel->obtenerEstadisticas($userId);

    echo json_encode($stats);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
