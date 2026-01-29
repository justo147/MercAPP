<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Product.php';

$limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 12;
$offset = isset($_GET["offset"]) ? intval($_GET["offset"]) : 0;

try {
    // Conexión a la BD
    $db = new Database();
    $conn = $db->getConnection();

    // Modelo
    $productModel = new Product($conn);

    // Obtener productos paginados
    $data = $productModel->getPaginated($limit, $offset);

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
