<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/User.php';

// 1. PROTECCIÓN ESTRICTA: Solo 'admin' puede ejecutar esto
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Acceso denegado. Se requieren permisos de administrador."]);
    exit;
}

$limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 20;
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET["q"]) ? trim($_GET["q"]) : "";

try {
    $db = new Database();
    $conn = $db->getConnection();
    $userModel = new User($conn);

    $users = $userModel->getAllUsersPaginated($limit, $offset, $search);
    $total = $userModel->countAllUsers($search);

    echo json_encode([
        "success" => true,
        "data" => $users,
        "total" => $total,
        "page" => $page,
        "limit" => $limit
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Error interno del servidor.", "details" => $e->getMessage()]);
}