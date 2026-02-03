<?php
session_start();
header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Product.php';

$userId = $_SESSION["user_id"] ?? null;

$limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 12;
$offset = isset($_GET["offset"]) ? intval($_GET["offset"]) : 0;

try {
    // Conexión a la BD
    $db = new Database();
    $conn = $db->getConnection();

    // Modelo
    $productModel = new Product($conn);

    // Obtener productos paginados
    $data = $productModel->getPaginated($limit, $offset, $userId);

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "error" => "Error en el servidor",
        "details" => $e->getMessage()
    ]);
}
