<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Product.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "No autenticado"]);
    exit;
}

$userId = $_SESSION['user_id'];

$limit  = isset($_GET['limit']) ? intval($_GET['limit']) : 6;
$page   = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$q    = isset($_GET['q'])    ? trim($_GET['q'])       : "";
$dias = isset($_GET['dias']) ? intval($_GET['dias'])  : 30;
$dias = in_array($dias, [7, 30, 90, 0]) ? $dias : 30; // whitelist

try {
    $db = new Database();
    $conn = $db->getConnection();

    $productModel = new Product($conn);

    $productos = $productModel->getProductsFromFollowing($userId, $limit, $offset, $q, $dias);
    $total     = $productModel->countProductsFromFollowing($userId, $q, $dias);

    echo json_encode([
        "success"   => true,
        "productos" => $productos,
        "total"     => $total,
        "page"      => $page,
        "limit"     => $limit
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "error"   => "Error en el servidor",
        "details" => $e->getMessage()
    ]);
}
