<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Product.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Acceso denegado."]);
    exit;
}

$data       = json_decode(file_get_contents("php://input"), true);
$product_id = isset($data['id'])     ? intval($data['id'])     : 0;
$action     = isset($data['action']) ? trim($data['action'])   : '';

$valid_actions = ['activar', 'pausar', 'eliminar'];

if ($product_id <= 0 || !in_array($action, $valid_actions)) {
    echo json_encode(["success" => false, "error" => "Datos inválidos."]);
    exit;
}

try {
    $db           = new Database();
    $conn         = $db->getConnection();
    $productModel = new Product($conn);

    switch ($action) {
        case 'eliminar':
            $result = $productModel->deleteWithImages($product_id);
            break;
        case 'activar':
            $result = $productModel->reactivarPublicacion($product_id);
            break;
        case 'pausar':
            $result = $productModel->reservarProducto($product_id);
            break;
    }

    if ($result) {
        echo json_encode(["success" => true, "message" => "Operación completada correctamente."]);
    } else {
        echo json_encode(["success" => false, "error" => "No se pudo completar la operación."]);
    }

} catch (Exception $e) {
    error_log("admin_update_product error: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Error interno del servidor."]);
}
